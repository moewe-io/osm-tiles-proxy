<?php

namespace MOEWE\OSM_Tiles_proxy;

use WP_Error;
use WP_REST_Request;

class Proxy
{

    const MAX_ZOOM = 20;
    // Area restrictions are stored as coordinates at zoom level 12
    const MAX_X_Y = 4095;

    private string $cache_404_pattern = '/\/cache\/osm-tiles\/(?P<s>[a-z0-9]{1,8})\/(?P<z>\d{1,2})\/(?P<x>\d{1,7})\/(?P<y>\d{1,7})\.png$/';

    private bool $cache_enabled;
    private bool $rest_api_enabled;
    private string $osm_url;

    function __construct()
    {
        $this->rest_api_enabled = get_option('osm-tiles-proxy-rest-api-enabled', false);
        $this->cache_enabled = get_option('osm-tiles-proxy-cache-enabled', true);
        $this->osm_url = get_option('osm-tiles-proxy-osm-url', OSM_PROXY_DEFAULT_TILE_SERVER_URL);
        $this->osm_url = empty($this->osm_url) ? OSM_PROXY_DEFAULT_TILE_SERVER_URL : $this->osm_url;

        add_action('rest_api_init', array($this, 'add_osm_proxy'));
        add_action('template_redirect', array($this, 'cache_on_404'), 0);

        add_filter('osm_tiles_proxy_get_proxy_url', [$this, 'get_proxy_url_cached'], 10, 1);
        add_filter('osm_tiles_proxy_get_proxy_rest_url', [$this, 'get_proxy_url_rest_api'], 10, 1);
        add_filter('osm_tiles_proxy_get_leaflet_js_url', [$this, 'get_leaflet_js_url'], 10, 1);
        add_filter('osm_tiles_proxy_get_leaflet_css_url', [$this, 'get_leaflet_css_url'], 10, 1);

        add_filter('osm_tiles_proxy_disable_clear_cache', [$this, 'is_clear_cache_disabled'], 10, 1);

        if (is_admin()) {
            add_filter('plugin_row_meta', array($this, 'init_row_meta'), 11, 2);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        // Add clear cache for caching plugins
        add_action('wpfc_delete_cache', [$this, 'clear_cache']);
        add_action('after_rocket_clean_domain', [$this, 'clear_cache']);
        add_action('wpsol_purge_cache', [$this, 'clear_cache']);
    }


    function add_osm_proxy(): void
    {
        register_rest_route('osm-tiles-proxy/v1', '/tiles/', [
                'methods' => 'DELETE',
                'callback' => [$this, 'clear_cache'],
                'permission_callback' => fn($request) => current_user_can('manage_options') ? true
                        : new WP_Error('rest_forbidden', esc_html__('You are not allowed to clear the cache.', 'osm-tiles-proxy'), ['status' => 401])
        ]);

        if ($this->rest_api_enabled) {
            register_rest_route('osm-tiles-proxy/v1', '/tiles/(?P<s>[a-z0-9]{1,8})/(?P<z>\d{1,2})/(?P<x>\d{1,7})/(?P<y>\d{1,7})\.png', array(
                    'methods' => 'GET',
                    'callback' => [$this, 'get_osm_tile'],
                    'permission_callback' => '__return_true'
            ));
        }
    }

    function cache_on_404(): void
    {
        if (!$this->cache_enabled || !is_404()) {
            return;
        }
        global $wp;

        if (!preg_match($this->cache_404_pattern, $wp->request, $matches)) {
            return;
        }

        $s = $matches['s'];
        $zoom = (int)$matches['z'];
        $x = (int)$matches['x'];
        $y = (int)$matches['y'];

        if (!$this->is_valid_subdomain($s) || !$this->is_tile_allowed($zoom, $x, $y)) {
            $this->redirect_out_of_range();
        }

        $target = WP_CONTENT_DIR . '/cache/osm-tiles/' . $s . '/' . $zoom . '/' . $x . '/' . $y . '.png';

        if (!is_file($target) && !$this->download_tile($s, $zoom, $x, $y, $target)) {
            $error_image = get_option('osm_tiles_proxy_download_error_image_url', OSM_PROXY_BASE_URL . 'assets/download_error.png');
            // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The image might be hosted somewhere else.
            wp_redirect($error_image, 307);
            die;
        }

        // The tile exists, but the web server still passes the request to WordPress: Deliver it directly instead of redirecting again.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public flag without side effects.
        if (isset($_GET['retry'])) {
            $this->send_tile(file_get_contents($target), wp_get_image_mime($target) ?: 'image/png');
        }

        wp_safe_redirect(add_query_arg('retry', '1', home_url($wp->request)));
        die;
    }

    /**
     * @param $request WP_REST_Request
     */
    function get_osm_tile($request)
    {
        $s = $request->get_param('s');
        $zoom = (int)$request->get_param('z');
        $x = (int)$request->get_param('x');
        $y = (int)$request->get_param('y');

        if (!$this->is_valid_subdomain($s) || !$this->is_tile_allowed($zoom, $x, $y)) {
            $this->redirect_out_of_range();
        }

        $tile = $this->fetch_tile($s, $zoom, $x, $y);
        if (!$tile) {
            return new WP_Error('osm_tiles_proxy_download_error', esc_html__('The tile could not be downloaded.', 'osm-tiles-proxy'), ['status' => 502]);
        }
        $this->send_tile($tile['body'], $tile['content_type']);
    }

    function is_valid_subdomain(string $s): bool
    {
        // The default server only knows a, b and c. Custom servers may use anything matching the url pattern.
        $allowed = OSM_PROXY_DEFAULT_TILE_SERVER_URL === $this->osm_url ? ['a', 'b', 'c'] : [];
        $allowed = apply_filters('osm_tiles_proxy_allowed_subdomains', $allowed);

        return empty($allowed) || in_array($s, $allowed, true);
    }

    function is_tile_allowed(int $zoom, int $x, int $y): bool
    {
        $min_zoom = (int)get_option('osm_tiles_proxy_min_zoom', 0);
        $max_zoom = min(self::MAX_ZOOM, (int)get_option('osm_tiles_proxy_max_zoom', self::MAX_ZOOM));

        if ($zoom > $max_zoom || $zoom < $min_zoom) {
            return false;
        }

        $tiles_per_axis = pow(2, $zoom);
        if ($x >= $tiles_per_axis || $y >= $tiles_per_axis) {
            return false;
        }

        // Tiles will always be downloaded, if current zoom <= unrestricted zoom
        $unrestricted_zoom = apply_filters('osm-tiles-proxy/get-unrestricted-zoom', 6);
        if ($zoom <= $unrestricted_zoom) {
            return true;
        }

        // A tile at zoom level 12 covers several tiles at higher zoom levels, so the max. values include the whole tile
        $min_x = floor($this->get_real_x_y(get_option('osm_tiles_proxy_min_x', 0), $zoom));
        $max_x = ceil($this->get_real_x_y((int)get_option('osm_tiles_proxy_max_x', self::MAX_X_Y) + 1, $zoom) - 1);

        $min_y = floor($this->get_real_x_y(get_option('osm_tiles_proxy_min_y', 0), $zoom));
        $max_y = ceil($this->get_real_x_y((int)get_option('osm_tiles_proxy_max_y', self::MAX_X_Y) + 1, $zoom) - 1);

        return $x <= $max_x && $x >= $min_x && $y <= $max_y && $y >= $min_y;
    }

    /**
     * @return array|null Body and content type of the tile or null, if the remote server didn't return an image.
     */
    function fetch_tile(string $s, int $zoom, int $x, int $y): ?array
    {
        $response = wp_remote_get($this->get_osm_remote_url($s, $zoom, $x, $y), array(
                'timeout' => 10,
                'limit_response_size' => 5 * MB_IN_BYTES
        ));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if ('' === $body || !is_string($content_type) || !str_starts_with($content_type, 'image/')) {
            return null;
        }

        return ['body' => $body, 'content_type' => $content_type];
    }

    function download_tile(string $s, int $zoom, int $x, int $y, string $target): bool
    {
        $tile = $this->fetch_tile($s, $zoom, $x, $y);

        if (!$tile || !wp_mkdir_p(dirname($target))) {
            return false;
        }

        // Write to a temporary file first, so parallel requests never get a partial tile
        $temp_file = $target . '.' . uniqid('', true) . '.tmp';

        if (false === file_put_contents($temp_file, $tile['body']) || !rename($temp_file, $target)) {
            wp_delete_file($temp_file);

            return false;
        }

        return true;
    }

    function send_tile(string $body, string $content_type): void
    {
        status_header(200);
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: public, max-age=604800'); // 1 week for no specific reason
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary image data.
        echo $body;
        die;
    }

    function redirect_out_of_range(): void
    {
        // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The image might be hosted somewhere else.
        wp_redirect(get_option('osm_tiles_proxy_out_of_range_image_url', OSM_PROXY_BASE_URL . 'assets/out_of_range.png'), 302);
        die;
    }

    function enqueue_admin_scripts($hook): void
    {
        if ('plugins.php' === $hook) {
            wp_enqueue_script('wp-api-fetch');
        }
    }

    function get_osm_remote_url($s, $z, $x, $y)
    {
        $remote_url = apply_filters('osm-tiles-proxy/get-osm-tiles-url', $this->osm_url);
        $remote_url = str_replace('{s}', $s, $remote_url);
        $remote_url = str_replace('{x}', $x, $remote_url);
        $remote_url = str_replace('{y}', $y, $remote_url);
        $remote_url = str_replace('{z}', $z, $remote_url);

        return $remote_url;
    }

    function get_proxy_url_cached($url = false)
    {
        if ($this->cache_enabled) {
            return content_url('/cache/osm-tiles/{s}/{z}/{x}/{y}.png');
        }
        return $url;
    }

    function get_proxy_url_rest_api($url = false)
    {
        if ($this->rest_api_enabled) {
            return rest_url('osm-tiles-proxy/v1/tiles/{s}/{z}/{x}/{y}.png');
        }
        return $url;
    }

    function get_leaflet_js_url($url): string
    {
        return OSM_PROXY_BASE_URL . "libs/leaflet/leaflet.js";
    }

    function get_leaflet_css_url($url): string
    {
        return OSM_PROXY_BASE_URL . "libs/leaflet/leaflet.css";
    }

    /**
     * Add additional useful links.
     *
     * @param $links array Already existing links.
     * @param $file string The current file.
     *
     * @return array Links including new ones.
     */
    function init_row_meta($links, $file): array
    {
        if (!str_contains($file, 'osm-tiles-proxy.php')) {
            return $links;
        }
        ob_start();
        ?>
        <section class="notice notice-info" style="display: block;padding: 10px; margin-top: 5px;">
            <strong><?php esc_html_e('Usage', 'osm-tiles-proxy') ?></strong>
            <p>
                <?php esc_html_e('Configure your OSM plugin to use the following urls instead.', 'osm-tiles-proxy') ?>
            </p>
            <table>
                <?php if ($this->cache_enabled) { ?>
                    <tr>
                        <th><?php esc_html_e('Tiles url (Caching)', 'osm-tiles-proxy') ?></th>
                        <td><?php echo esc_html(apply_filters('osm_tiles_proxy_get_proxy_url', '')) ?></td>
                    </tr>
                <?php } ?>
                <?php if ($this->rest_api_enabled) {
                    ?>
                    <tr>
                        <th><?php esc_html_e('Tiles url (REST API)', 'osm-tiles-proxy') ?></th>
                        <td><?php echo esc_html(apply_filters('osm_tiles_proxy_get_proxy_rest_url', '')) ?></td>
                    </tr>
                <?php } ?>

                <tr>
                    <th><?= esc_html__('Leaflet JS', 'osm-tiles-proxy') ?></th>
                    <td><?= esc_html(apply_filters('osm_tiles_proxy_get_leaflet_js_url', '')) ?></td>
                </tr>
                <tr>
                    <th><?= esc_html__('Leaflet CSS', 'osm-tiles-proxy') ?></th>
                    <td><?= esc_html(apply_filters('osm_tiles_proxy_get_leaflet_css_url', '')) ?></td>
                </tr>
                <tr>
                    <th><?= esc_html__('Remote tiles server', 'osm-tiles-proxy') ?></th>
                    <td><?= esc_html($this->osm_url) ?></td>
                </tr>
                <tr>
                    <th><?= esc_html__('Current cache size', 'osm-tiles-proxy') ?></th>
                    <td style="display: flex; align-items: center; gap: 1ch;">
                        <span class="osm-proxy-cache-size">
                        <?= esc_html(size_format(get_dirsize(WP_CONTENT_DIR . '/cache/osm-tiles')) ?: '0 MB') ?>
                       </span>
                        <button type="button" class="button action"
                                onclick="osm_proxy_clear_cache()"><?= esc_html__('Clear cache', 'osm-tiles-proxy') ?>
                        </button>
                    </td>
                </tr>
            </table>

            <p>
                <?php
                /* translators: %s: Donation url */
                echo wp_kses_post(sprintf(__('Like the plugin? <a target="_blank" href="%s">Donate</a>.', 'osm-tiles-proxy'),
                        esc_url(OSM_PROXY_DONATION_URL)));
                ?>
            </p>
        </section>
        <script>
            function osm_proxy_clear_cache() {
                wp.apiFetch({
                    path: '/osm-tiles-proxy/v1/tiles',
                    method: 'DELETE',
                    parse: false
                }).then((response) => {
                    document.querySelector('.osm-proxy-cache-size').innerText = '0 MB';
                });
            }
        </script>
        <?php
        $links[] = ob_get_clean();

        return $links;
    }

    function is_clear_cache_disabled($disabled = false): bool
    {
        return $disabled || defined('OSM_PROXY_DISABLE_CLEAR_CACHE') && OSM_PROXY_DISABLE_CLEAR_CACHE;
    }

    function clear_cache(): void
    {
        // @Deprecated: This filter is deprecated
        $disable_clear_cache = apply_filters('osm-tiles-proxy/disable_clear_cache', false);

        if (apply_filters('osm_tiles_proxy_disable_clear_cache', $disable_clear_cache)) {
            return;
        }
        $this->rrmdir(WP_CONTENT_DIR . '/cache/osm-tiles');
        delete_transient('dirsize_cache');
    }

    // Thanks: https://stackoverflow.com/a/3338133/1165132
    function rrmdir($directory): void
    {

        if (!is_dir($directory)) {
            return;
        }
        $objects = scandir($directory);
        foreach ($objects as $object) {
            if ("." !== $object && ".." !== $object) {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $object) && !is_link($directory . "/" . $object)) {
                    $this->rrmdir($directory . DIRECTORY_SEPARATOR . $object);
                } else {
                    wp_delete_file($directory . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($directory);
    }

    function get_real_x_y($x_y, $current_zoom)
    {
        $x_y = 12 == $current_zoom ? $x_y : $x_y * pow(2, ($current_zoom - 12));

        return apply_filters('osm-tiles-proxy/get_real_x_y', $x_y, $current_zoom);
    }
}
