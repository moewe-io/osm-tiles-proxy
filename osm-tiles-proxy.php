<?php
/*
 * Plugin Name: Tiles Proxy for OpenStreetMap
 * Plugin URI: https://wordpress.org/plugins/osm-tiles-proxy
 * Description: Helper plugin for embedding OpenStreetMaps
 * Version: 2.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.3
 * Author: MOEWE
 * Author URI: https://www.moewe.io/
 * Text Domain: osm-tiles-proxy
 */

use MOEWE\OSM_Tiles_proxy\Customizer;
use MOEWE\OSM_Tiles_proxy\Proxy;


define('OSM_PROXY_BASE_URL', plugins_url('/', __FILE__));
define('OSM_PROXY_BASE_DIR', __DIR__ . DIRECTORY_SEPARATOR);

include "includes/osm-tiles-proxy.class.php";
include "includes/osm-tiles-proxy.customizer-class.php";

new Proxy();
new Customizer();


add_action('wp_enqueue_scripts', 'osm_tiles_proxy_register_leaflet');

function osm_tiles_proxy_register_leaflet()
{
    wp_register_style('leaflet-js', apply_filters('osm_tiles_proxy_get_leaflet_js_url', false), false, '1.9.3');
    wp_register_script('leaflet-js', apply_filters('osm_tiles_proxy_get_leaflet_css_url', false), array(), '1.9.3');
}