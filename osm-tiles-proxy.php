<?php

/*
 * Plugin Name: Tiles Proxy for OpenStreetMap
 * Plugin URI: https://wordpress.org/plugins/osm-tiles-proxy
 * Description: Helper plugin for embedding OpenStreetMaps
 * Version: 1.2.0
 * Author: MOEWE
 * Author URI: https://www.moewe.io/
 * Text Domain: osm-tiles-proxy
 */


class MOEWE_OSM_Tiles_Proxy {

    private $cache_404_pattern = '/\/cache\/osm-tiles\/(?P<s>\w+)\/(?P<z>\d+)\/(?P<x>\d+)\/(?P<y>\d+).png/m';

    private $cache_enabled;
    private $rest_api_enabled;
    private $osm_url;

    function __construct() {

        $this->rest_api_enabled = get_option('osm-tiles-proxy-rest-api-enabled', false);
        $this->cache_enabled = get_option('osm-tiles-proxy-cache-enabled', true);
        $this->osm_url = get_option('osm-tiles-proxy-osm-url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

        add_action('rest_api_init', array($this, 'add_osm_proxy'));
        add_action('template_redirect', array($this, 'cache_on_404'), 0);

        if (is_admin()) {
            add_filter('plugin_row_meta', array($this, 'init_row_meta'), 11, 2);
        }
    }


    function add_osm_proxy() {
        if (!$this->rest_api_enabled) {
            return;
        }
        register_rest_route('osm-tiles-proxy/v1', '/tiles/(?P<s>\w+)/(?P<z>\d+)/(?P<x>\d+)/(?P<y>\d+).png', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_osm_tile'),
        ));
    }

    function cache_on_404() {
        if (!$this->cache_enabled || !is_404()) {
            return;
        }
        global $wp;
        $request = $wp->request;

        if (preg_match_all($this->cache_404_pattern, $request, $matches, PREG_SET_ORDER, 0)) {
            $matches = $matches[0];
            $base_path = dirname(wp_upload_dir()['basedir']) . '/cache/osm-tiles';
            $download_url = $this->get_osm_remote_url($matches['s'], $matches['z'], $matches['x'], $matches['y']);

            $download_target = $base_path . '/' . $matches['s'] . '/' . $matches['z'] . '/' . $matches['xrexpr'] . '/';

            wp_mkdir_p($download_target);
            $download_target = $download_target . '/' . $matches['y'] . '.png';

            wp_remote_get($download_url, array(
                'timeout'  => 300,
                'stream'   => true,
                'filename' => $download_target
            ));

            wp_redirect(add_query_arg('retry', '1', home_url($wp->request)));
            die;
        };
    }

    /**
     * @param $request WP_REST_Request
     */
    function get_osm_tile($request) {
        $remote_url = $this->get_osm_remote_url($request->get_param('s'), $request->get_param('z'), $request->get_param('x'), $request->get_param('y'));
        header('Content-Description: File Transfer');
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=604800'); // 1 week for no specific reason
        readfile($remote_url);
    }

    function get_osm_remote_url($s, $z, $x, $y) {
        $remote_url = apply_filters('osm-tiles-proxy/get-osm-tiles-url', $this->osm_url);
        $remote_url = str_replace('{s}', $s, $remote_url);
        $remote_url = str_replace('{x}', $x, $remote_url);
        $remote_url = str_replace('{y}', $y, $remote_url);
        $remote_url = str_replace('{z}', $z, $remote_url);
        return $remote_url;
    }

    /**
     * Add additional useful links.
     *
     * @param $links array Already existing links.
     * @param $file string The current file.
     * @return array Links including new ones.
     */
    function init_row_meta($links, $file) {
        if (strpos($file, 'osm-tiles-proxy.php') === false) {
            return $links;
        }
        ob_start();
        $leaflet_base_url = plugins_url('/libs/leaflet/leaflet', $file);
        ?>
        <section class="notice notice-info" style="display: block;padding: 10px; margin-top: 5px;">
            <strong><?php _e('Usage', 'osm-tiles-proxy') ?></strong>
            <p>
                <?php _e('Configure your OSM plugin to use the following urls instead.', 'osm-tiles-proxy') ?>
            </p>
            <table>
                <?php if ($this->cache_enabled) {
                    $osm_tiles_url = content_url('/cache/osm-tiles/{s}/{z}/{x}/{y}.png'); ?>
                    <tr>
                        <th><?php _e('Tiles url (Caching)', 'osm-tiles-proxy') ?></th>
                        <td><?php echo $osm_tiles_url ?></td>
                    </tr>
                <?php } ?>
                <?php if ($this->rest_api_enabled) {
                    $osm_tiles_url = rest_url('osm-tiles-proxy/v1/tiles/{s}/{z}/{x}/{y}.png'); ?>
                    <tr>
                        <th><?php _e('Tiles url (REST API)', 'osm-tiles-proxy') ?></th>
                        <td><?php echo $osm_tiles_url ?></td>
                    </tr>
                <?php } ?>

                <tr>
                    <th><?php _e('Leaflet JS', 'osm-tiles-proxy') ?></th>
                    <td><?php echo $leaflet_base_url; ?>.js</td>
                </tr>
                <tr>
                    <th><?php _e('Leaflet CSS', 'osm-tiles-proxy') ?></th>
                    <td><?php echo $leaflet_base_url; ?>.css</td>
                </tr>
            </table>
        </section>
        <?php
        $links[] = ob_get_clean();
        return $links;
    }
}

new MOEWE_OSM_Tiles_Proxy();