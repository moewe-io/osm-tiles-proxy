=== Tiles Proxy for OpenStreetMap ===
Contributors: adrian2k7,moewe, creabrain
Tags: openstreetmap, embed, gdpr
Donate link: https://www.moewe.io/
Requires at least: 6.0
Requires PHP: 7.3
Tested up to: 6.1
Stable tag: 2.3.0
License: GPL v3
License URI: http://www.gnu.org/copyleft/gpl.html

Tiles Proxy for OpenStreetMap provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

== Description ==

Tiles Proxy for OpenStreetMap provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

**Contribute**: [https://github.com/moewe-io/osm-tiles-proxy](https://github.com/moewe-io/osm-tiles-proxy)

**Known to work with these plugins**

* [Leaflet Map](https://de.wordpress.org/plugins/leaflet-map/)
* [Custom Post Type to Map Store](https://wordpress.org/plugins/cpt-to-map-store/)
* [Geolocation](https://wordpress.org/plugins/geolocation/advanced/)
* Another plugin? Let us [know](https://wordpress.org/support/plugin/osm-tiles-proxy).

**Notes**

* *Beware*: Depending on your map and the tiles you need, a lot of storage is needed.
* Might be slower than official servers (depends on your server)
* First requests might be slow as map tiles are cached on request

**Filters**

You can use filters to get the URLs from the plugin:

`
  $proxy_cached_url   = apply_filters( 'osm_tiles_proxy_get_proxy_url', $proxy_cached_url );
  $proxy_rest_api_url = apply_filters( 'osm_tiles_proxy_get_proxy_rest_url', $proxy_rest_api_url );
  $leadlet_js_url     = apply_filters( 'osm_tiles_proxy_get_leaflet_js_url', $leadlet_js_url );
  $leadlet_css_url    = apply_filters( 'osm_tiles_proxy_get_leaflet_css_url', $leadlet_css_url );
`
**Constants**

To disable cache invalidation when WP Rocket or WP Fastest Cache invalidate their cache, you can set the following constant in your `wp-config.php`
`
define( 'OSM_PROXY_DISABLE_CLEAR_CACHE', true );
`

== Frequently Asked Questions ==

= Does this work with every plugin? =

The plugin must support changing the OpenStreetMap tile server.

== Upgrade Notice ==

Nothing special

== Screenshots ==

1. Needed URLs are shown in the plugins overview
2. Example usage with [Leaflet Map](https://de.wordpress.org/plugins/leaflet-map/)
3. Customizer overview

== Changelog ==

= 2.3.0 =

* The plugin will now automagically overwrite **default** settings for [Leaflet Map](https://de.wordpress.org/plugins/leaflet-map/).

= 2.2.1 =

* Added missing files to SVN, don't know why there are missing... Why can't they just support git, like everyone else?

= 2.2.0 =

* Updated to latest Leaflet.js (1.9.3)
  * **Note:** This might be incompatible, if you are still using 1.8.x
* Registered Leaflet JS and CSS, so it can be enqueued using `wp_enqueue_style('leaflet-js')` and `wp_enqueue_script('leaflet-js')`
* Fixed js/css urls shown in administration

= 2.1.0 =

* Added filters to get URLs for Leaflet and Proxy #5
* Added constant `OSM_PROXY_DISABLE_CLEAR_CACHE` to disable cache invalidation (for WP Rocket and WP Fastest Cache)

= 2.0.1 =

* Fixed wrong redirect to placeholder on every 404

= 2.0.0 =

* Updated Leaflet.js to 1.6.0
* Added a new Customizer panel, which allows you to define some restrictions for downloaded tiles
* Require PHP 7.2+, we urge everyone keep PHP updated and will stick to official end of security updates for PHP
* Downloaded tiles will be cleared, when "entire cache" is cleared in WP Rocket
* Downloaded tiles will be cleared, when cache is cleared in WP Fastest Cache

= 1.3.0 =

* Updated leaflet.js to 1.3.4
* Fix: Use WP_CONTENT_DIR instead of wp_upload_dir()

= 1.2.0 =

* Public release in WordPress repo
* Disabled REST API by default
* Added local caching of tiles

= 1.1.0 =

* Added a list of URLs to the plugin overview

= 1.0.0 =

* First release

