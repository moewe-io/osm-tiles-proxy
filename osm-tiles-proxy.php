<?php

/*
 * Plugin Name: OpenStreetMap Tiles Proxy
 * Plugin URI: https://github.com/moewe-io/osm-tiles-proxy
 * Description: Helper plugin for embedding OpenStreetMaps
 * Version: 1.0.0
 * Author: MOEWE
 * Author URI: https://www.moewe.io/
 * Text Domain: osm-tiles-proxy
 */


class MOEWE_OSM_Tiles_Proxy
{

    function __construct()
    {
        add_action('rest_api_init', array($this, 'add_osm_proxy'));
    }


    function add_osm_proxy()
    {
        register_rest_route('osm-tiles-proxy/v1', '/tiles/(?P<s>\w+)/(?P<z>\d+)/(?P<x>\d+)/(?P<y>\d+).png', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_osm_tile'),
        ));
    }

    /**
     * @param $request WP_REST_Request
     */
    function get_osm_tile($request)
    {
        // TODO Check referer
        // TODO Cache files locally

        $remote_url = apply_filters('osm-tiles-proxy/get-osm-tiles-url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        $remote_url = str_replace('{s}', $request->get_param('s'), $remote_url);
        $remote_url = str_replace('{x}', $request->get_param('x'), $remote_url);
        $remote_url = str_replace('{y}', $request->get_param('y'), $remote_url);
        $remote_url = str_replace('{z}', $request->get_param('z'), $remote_url);
        header('Content-Description: File Transfer');
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=604800'); // 1 week for no specific reason
        readfile($remote_url);
    }
}

new MOEWE_OSM_Tiles_Proxy();


// Updates
require 'libs/plugin-update-checker-4.4/plugin-update-checker.php';
Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/moewe-io/osm-tiles-proxy/',
    __FILE__,
    'osm-tiles-proxy'
)->setBranch('master');