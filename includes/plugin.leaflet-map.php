<?php
/**
 * This enables direct integration for https://de.wordpress.org/plugins/leaflet-map/
 */
if (defined('OSM_PROXY_DISABLE_PLUGIN_LEAFMAP_MAP_INTEGRATION')
    && !OSM_PROXY_DISABLE_PLUGIN_LEAFMAP_MAP_INTEGRATION) {
    return;
}

add_filter('default_option_leaflet_map_tile_url', function ($default) {
    return apply_filters('osm_tiles_proxy_get_proxy_url', $default);
}, 10, 1);

add_filter('default_option_leaflet_js_url', function ($default) {
    return apply_filters('osm_tiles_proxy_get_leaflet_js_url', $default);
}, 10, 1);

add_filter('default_option_leaflet_css_url', function ($default) {
    return apply_filters('osm_tiles_proxy_get_leaflet_css_url', $default);
}, 10, 1);

add_filter('default_option_leaflet_map_tile_url_subdomains', function ($default) {
    return 'a';
}, 10, 1);

add_filter('default_option_leaflet_default_attribution', function ($default) {
    ob_start();
    ?><a href="https://leafletjs.com" target="_blank">Leaflet</a> |
    <a href="https://moerchen.io/" target="_blank">Tiles Proxy</a> |
    © <a href="http://www.openstreetmap.org/copyright">OSM Contributors</a><?php
    return ob_get_clean();
}, 10, 1);

