=== OpenStreetMap Tiles Proxy ===
Contributors: adrian2k7,moewe, creabrain
Tags: openstreetmap, embed, gdpr
Donate link: https://www.moewe.io/
Requires at least: 4.0
Tested up to: 4.9.6
Stable tag: 2.0.5
Requires PHP: 7.0
License: GPL v3
License URI: http://www.gnu.org/copyleft/gpl.html

OpenStreetMap Tiles Proxy provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

== Description ==

OpenStreetMap Tiles Proxy provides a basic proxy, which allows other OpenStreetMap plugins to load map tiles from your server instead from OpenStreetMap servers.

**Contribute**: [https://github.com/moewe-io/osm-tiles-proxy](https://github.com/moewe-io/osm-tiles-proxy)

** Known to work with these plugins **

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

There is no ui or something like this. So no screenshots needed.

== Changelog ==

= 1.2.0 =

* Public release in WordPress repo
* Disabled REST API by default
* Added local caching of tiles

= 1.1.0 =

* Added a list of URLs to the plugin overview

= 1.0.0 =

* First release
