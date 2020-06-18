<?php
/*
 * Plugin Name: Tiles Proxy for OpenStreetMap
 * Plugin URI: https://wordpress.org/plugins/osm-tiles-proxy
 * Description: Helper plugin for embedding OpenStreetMaps
 * Version: 2.1.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: MOEWE
 * Author URI: https://www.moewe.io/
 * Text Domain: osm-tiles-proxy
 */

use MOEWE\OSM_Tiles_proxy\Customizer;
use MOEWE\OSM_Tiles_proxy\Proxy;


define( 'OSM_PROXY_BASE_URL', plugins_url( '/', __FILE__ ) );
define( 'OSM_PROXY_BASE_DIR', __DIR__ . DIRECTORY_SEPARATOR );

include "includes/osm-tiles-proxy.class.php";
include "includes/osm-tiles-proxy.customizer-class.php";

new Proxy();
new Customizer();
