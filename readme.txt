=== Tiles Proxy for OpenStreetMap ===
Contributors: adrian2k7,moewe, creabrain
Tags: openstreetmap, embed, gdpr
Donate link: https://www.moewe.io/
Requires at least: 5.1
Tested up to: 5.4
Stable tag: 2.0.0
License: GPL v3
License URI: http://www.gnu.org/copyleft/gpl.html

Tiles Proxy for OpenStreetMap provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

== Description ==

Tiles Proxy for OpenStreetMap provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

**Contribute**: [https://github.com/moewe-io/osm-tiles-proxy](https://github.com/moewe-io/osm-tiles-proxy)

**Known to work with these plugins**

* [Leaflet Map](https://de.wordpress.org/plugins/leaflet-map/)
* Another plugin? Let us [know](https://wordpress.org/support/plugin/osm-tiles-proxy).

**Notes**

* *Beware*: Depending on your map and the tiles you need, a lot of storage is needed.
* Might be slower than official servers (depends on your server)
* First requests might be slow as map tiles are cached on request

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
