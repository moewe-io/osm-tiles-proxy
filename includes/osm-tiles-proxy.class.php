<?php

namespace MOEWE\OSM_Tiles_proxy;

use WP_REST_Request;

class Proxy {

    private $cache_404_pattern = '/\/cache\/osm-tiles\/(?P<s>\w+)\/(?P<z>\d+)\/(?P<x>\d+)\/(?P<y>\d+).png/m';

    private $cache_enabled;
    private $rest_api_enabled;
    private $osm_url;

    function __construct() {

        $this->rest_api_enabled = get_option( 'osm-tiles-proxy-rest-api-enabled', false );
        $this->cache_enabled = get_option( 'osm-tiles-proxy-cache-enabled', true );
        $this->osm_url = get_option( 'osm-tiles-proxy-osm-url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' );

        add_action( 'rest_api_init', array( $this, 'add_osm_proxy' ) );
        add_action( 'template_redirect', array( $this, 'cache_on_404' ), 0 );

        add_action( 'wpfc_delete_cache', [ $this, 'clear_cache' ] );
        add_action( 'after_rocket_clean_domain', [ $this, 'clear_cache' ] );

        add_filter( 'osm_tiles_proxy_get_proxy_url', [ $this, 'get_proxy_url_cached' ], 10, 1 );
        add_filter( 'osm_tiles_proxy_get_proxy_rest_url', [ $this, 'get_proxy_url_rest_api' ], 10, 1 );
        add_filter( 'osm_tiles_proxy_get_leaflet_js_url', [ $this, 'get_leaflet_js_url' ], 10, 1 );
        add_filter( 'osm_tiles_proxy_get_leaflet_css_url', [ $this, 'get_leaflet_css_url' ], 10, 1 );

        add_filter( 'osm_tiles_proxy_disable_clear_cache', [ $this, 'is_clear_cache_disabled' ], 10, 1 );

        if ( is_admin() ) {
            add_filter( 'plugin_row_meta', array( $this, 'init_row_meta' ), 11, 2 );
        }
    }


    function add_osm_proxy() {
        if ( ! $this->rest_api_enabled ) {
            return;
        }
        register_rest_route( 'osm-tiles-proxy/v1', '/tiles/(?P<s>\w+)/(?P<z>\d+)/(?P<x>\d+)/(?P<y>\d+).png', array(
            'methods'  => 'GET',
            'callback' => [ $this, 'get_osm_tile' ],
        ) );
    }

    function cache_on_404() {
        if ( ! $this->cache_enabled || ! is_404() ) {
            return;
        }
        global $wp;

        $out_of_range_url = get_option( 'osm_tiles_proxy_out_of_range_image_url', OSM_PROXY_BASE_URL . 'assets/out_of_range.png' );

        if ( ! preg_match_all( $this->cache_404_pattern, $wp->request, $matches, PREG_SET_ORDER, 0 ) ) {
            return;
        }

        $min_zoom = get_option( 'osm_tiles_proxy_min_zoom', 0 );
        $max_zoom = get_option( 'osm_tiles_proxy_max_zoom', 20 );

        $matches = $matches[0];
        $zoom = max( 0, min( 20, $matches['z'] ) ); // OSM is always 0 to 20
        $x = $matches['x'];
        $y = $matches['y'];

        if ( $zoom > $max_zoom || $zoom < $min_zoom ) {
            wp_redirect( $out_of_range_url, 302 );
            die;
        }

        $max_x_y = pow( 2, $max_zoom ) - 1;
        // Tiles will always be downloaded, if current zoom <= unrestricted zoom
        $unrestricted_zoom = apply_filters( 'osm-tiles-proxy/get-unrestricted-zoom', 6 );

        $min_x = floor( $this->get_real_x_y( get_option( 'osm_tiles_proxy_min_x', 0 ), $zoom ) );
        $max_x = ceil( $this->get_real_x_y( get_option( 'osm_tiles_proxy_max_x', $max_x_y ), $zoom ) );

        $min_y = floor( $this->get_real_x_y( get_option( 'osm_tiles_proxy_min_y', 0 ), $zoom ) );
        $max_y = ceil( $this->get_real_x_y( get_option( 'osm_tiles_proxy_max_y', $max_x_y ), $zoom ) );


        if ( $zoom > $unrestricted_zoom && ( $x > $max_x || $x < $min_x || $y > $max_y || $y < $min_y ) ) {
            wp_redirect( $out_of_range_url, 302 );
            die;
        }

        $base_path = WP_CONTENT_DIR . '/cache/osm-tiles';
        $download_url = $this->get_osm_remote_url( $matches['s'], $zoom, $x, $y );

        $download_target = $base_path . '/' . $matches['s'] . '/' . $zoom . '/' . $x . '/';

        wp_mkdir_p( $download_target );
        $download_target = $download_target . '/' . $y . '.png';

        wp_remote_get( $download_url, array(
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $download_target
        ) );

        wp_redirect( add_query_arg( 'retry', '1', home_url( $wp->request ) ) );
        die;
    }

    /**
     * @param $request WP_REST_Request
     */
    function get_osm_tile( $request ) {
        $remote_url = $this->get_osm_remote_url( $request->get_param( 's' ), $request->get_param( 'z' ), $request->get_param( 'x' ), $request->get_param( 'y' ) );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: image/png' );
        header( 'Cache-Control: public, max-age=604800' ); // 1 week for no specific reason
        readfile( $remote_url );
    }

    function get_osm_remote_url( $s, $z, $x, $y ) {
        $remote_url = apply_filters( 'osm-tiles-proxy/get-osm-tiles-url', $this->osm_url );
        $remote_url = str_replace( '{s}', $s, $remote_url );
        $remote_url = str_replace( '{x}', $x, $remote_url );
        $remote_url = str_replace( '{y}', $y, $remote_url );
        $remote_url = str_replace( '{z}', $z, $remote_url );

        return $remote_url;
    }

    function get_proxy_url_cached( $url = false ) {
        if ( $this->cache_enabled ) {
            return content_url( '/cache/osm-tiles/{s}/{z}/{x}/{y}.png' );
        }
        return $url;
    }

    function get_proxy_url_rest_api( $url = false ) {
        if ( $this->rest_api_enabled ) {
            return rest_url( 'osm-tiles-proxy/v1/tiles/{s}/{z}/{x}/{y}.png' );
        }
        return $url;
    }

    function get_leaflet_js_url( $url ) {
        return OSM_PROXY_BASE_URL . "/libs/leaflet/leaflet.js";
    }

    function get_leaflet_css_url( $url ) {
        return OSM_PROXY_BASE_URL . "/libs/leaflet/leaflet.css";
    }

    /**
     * Add additional useful links.
     *
     * @param $links array Already existing links.
     * @param $file string The current file.
     *
     * @return array Links including new ones.
     */
    function init_row_meta( $links, $file ) {
        if ( strpos( $file, 'osm-tiles-proxy.php' ) === false ) {
            return $links;
        }
        ob_start();
        ?>
        <section class="notice notice-info" style="display: block;padding: 10px; margin-top: 5px;">
            <strong><?php _e( 'Usage', 'osm-tiles-proxy' ) ?></strong>
            <p>
                <?php _e( 'Configure your OSM plugin to use the following urls instead.', 'osm-tiles-proxy' ) ?>
            </p>
            <table>
                <?php if ( $this->cache_enabled ) { ?>
                    <tr>
                        <th><?php _e( 'Tiles url (Caching)', 'osm-tiles-proxy' ) ?></th>
                        <td><?php echo apply_filters( 'osm_tiles_proxy_get_proxy_url', '' ) ?></td>
                    </tr>
                <?php } ?>
                <?php if ( $this->rest_api_enabled ) {
                    ?>
                    <tr>
                        <th><?php _e( 'Tiles url (REST API)', 'osm-tiles-proxy' ) ?></th>
                        <td><?php echo apply_filters( 'osm_tiles_proxy_get_proxy_rest_url', '' ) ?></td>
                    </tr>
                <?php } ?>

                <tr>
                    <th><?php _e( 'Leaflet JS', 'osm-tiles-proxy' ) ?></th>
                    <td><?php echo apply_filters( 'osm_tiles_proxy_get_leaflet_js_url', '' ) ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Leaflet CSS', 'osm-tiles-proxy' ) ?></th>
                    <td><?php echo apply_filters( 'osm_tiles_proxy_get_leaflet_css_url', '' ) ?></td>
                </tr>
            </table>
        </section>
        <?php
        $links[] = ob_get_clean();

        return $links;
    }

    function is_clear_cache_disabled( $disabled = false ) {
        return $disabled || defined( 'OSM_PROXY_DISABLE_CLEAR_CACHE' ) && OSM_PROXY_DISABLE_CLEAR_CACHE;
    }

    function clear_cache() {
        // @Deprecated: This filter is deprecated
        $disable_clear_cache = apply_filters( 'osm-tiles-proxy/disable_clear_cache', false );

        if ( apply_filters( 'osm_tiles_proxy_disable_clear_cache', $disable_clear_cache ) ) {
            return;
        }
        $this->rrmdir( WP_CONTENT_DIR . '/cache/osm-tiles' );
    }

    // Thanks: https://stackoverflow.com/a/3338133/1165132
    function rrmdir( $directory ) {
        if ( ! is_dir( $directory ) ) {
            return;
        }
        $objects = scandir( $directory );
        foreach ( $objects as $object ) {
            if ( $object != "." && $object != ".." ) {
                if ( is_dir( $directory . DIRECTORY_SEPARATOR . $object ) && ! is_link( $directory . "/" . $object ) ) {
                    $this->rrmdir( $directory . DIRECTORY_SEPARATOR . $object );
                } else {
                    unlink( $directory . DIRECTORY_SEPARATOR . $object );
                }
            }
        }
        rmdir( $directory );
    }

    function get_real_x_y( $x_y, $current_zoom ) {
        $x_y = 12 == $current_zoom ? $x_y : $x_y * pow( 2, ( $current_zoom - 12 ) );

        return apply_filters( 'osm-tiles-proxy/get_real_x_y', $x_y, $current_zoom );
    }
}
