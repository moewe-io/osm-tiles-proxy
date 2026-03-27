<?php

namespace MOEWE\OSM_Tiles_proxy;

use WP_Customize_Setting;
use WP_Error;

class Customizer
{

    public function __construct()
    {
        add_action('customize_register', [$this, 'customize_register']);
        add_action('leaflet_map_loaded', [$this, 'leaflet_map_loaded']);
    }

    function customize_register(\WP_Customize_Manager $wp_customize): void
    {

        $max_x_y = pow(2, absint(get_option('osm_tiles_proxy_max_zoom', 20))) - 1;

        $wp_customize->add_section('osm_tiles_proxy_section', array(
            'title' => __('OSM Tiles Proxy', 'osm-tiles-proxy'),
            'description' => __('Use these settings to restrict the area, the proxy is allowed to download. This is useful to avoid bloating your servers space with map tiles you never need.', 'osm-tiles-proxy'),
        ));

        $wp_customize->add_setting('osm_tiles_proxy_min_zoom', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));

        $wp_customize->add_setting('osm_tiles_proxy_max_zoom', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => 20
        ));

        $wp_customize->add_setting('osm_tiles_proxy_min_x', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));

        $wp_customize->add_setting('osm_tiles_proxy_max_x', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => $max_x_y
        ));

        $wp_customize->add_setting('osm_tiles_proxy_min_y', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));

        $wp_customize->add_setting('osm_tiles_proxy_max_y', array(
            'type' => 'option',
            'sanitize_callback' => 'absint',
            'default' => $max_x_y
        ));

        $wp_customize->add_setting('osm-tiles-proxy-osm-url', array(
            'type' => 'option',
            'validate_callback' => [$this, 'validate_url_callback'],
            'default' => OSM_PROXY_DEFAULT_TILE_SERVER_URL
        ));

        // Zoom Levels
        $wp_customize->add_control('osm_tiles_proxy_min_zoom_control', array(
            'label' => __('Min. Zoom', 'osm-tiles-proxy'),
            'description' => __('Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Zoom_levels">zoom level</a> the proxy is allowed to download (0 = whole world, 20 = midi sized building).', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_min_zoom',
            'type' => 'number',
            'default' => 0,
            'input_attrs' => array(
                'min' => 0,
                'max' => 20,
            )
        ));

        $wp_customize->add_control('osm_tiles_proxy_max_zoom_control', array(
            'label' => __('Max. Zoom', 'osm-tiles-proxy'),
            'description' => __('Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Zoom_levels">zoom level</a> the proxy is allowed to download (0 = whole world, 20 = midi sized building).', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_max_zoom',
            'type' => 'number',
            'default' => 18,
            'input_attrs' => array(
                'min' => 0,
                'max' => 20,
            ),
        ));

        // X-Coordinates
        $wp_customize->add_control('osm_tiles_proxy_min_x_control', array(
            'label' => __('Min. X coordinate at zoom level 12', 'osm-tiles-proxy'),
            'description' => __('Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">x coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_min_x',
            'type' => 'number',
            'input_attrs' => array(
                'default' => 0,
                'min' => 0,
                'max' => $max_x_y,
            ),
        ));

        $wp_customize->add_control('osm_tiles_proxy_max_x_control', array(
            'label' => __('Max. X coordinate at zoom level 12', 'osm-tiles-proxy'),
            'description' => __('Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">x coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_max_x',
            'type' => 'number',
            'default' => $max_x_y,
            'input_attrs' => array(
                'min' => 0,
                'max' => $max_x_y,
            ),
        ));

        // Y-Coordinates
        $wp_customize->add_control('osm_tiles_proxy_min_y_control', array(
            'label' => __('Min. Y coordinate at zoom level 12', 'osm-tiles-proxy'),
            'description' => __('Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">y coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_min_y',
            'type' => 'number',
            'input_attrs' => array(
                'default' => 0,
                'min' => 0,
                'max' => $max_x_y,
            ),
        ));

        $wp_customize->add_control('osm_tiles_proxy_max_y_control', array(
            'label' => __('Max. Y coordinate at zoom level 12', 'osm-tiles-proxy'),
            'description' => __('Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">y coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm_tiles_proxy_max_y',
            'type' => 'number',
            'default' => $max_x_y,
            'input_attrs' => array(
                'min' => 0,
                'max' => $max_x_y,
            ),
        ));

        $wp_customize->add_control('osm-tiles-proxy-osm-url_control', array(
            'label' => __('Custom tiles server url', 'osm-tiles-proxy'),
            'description' => __('You can set custom tiles server. Leave empty to use the default server.', 'osm-tiles-proxy'),
            'section' => 'osm_tiles_proxy_section',
            'settings' => 'osm-tiles-proxy-osm-url',
            'type' => 'text',
            'default' => OSM_PROXY_DEFAULT_TILE_SERVER_URL,
            'input_attrs' => array(),
        ));
    }

    function validate_url_callback(WP_Error $validity, mixed $url, WP_Customize_Setting $setting): WP_Error
    {
        if (empty($url)) {
            return $validity; // Will use default url
        }

        if (!str_starts_with($url, 'https://')) {
            $validity->add('not_a_valid_url', __('The url should start with https://', 'osm-tiles-proxy'));
        }

        if (!str_contains($url, '{z}')
            || !str_contains($url, '{x}')
            || !str_contains($url, '{y}')) {
            $validity->add('mssing_variables', __('The url must contain placeholders for {z},{y} and {x}', 'osm-tiles-proxy'));
        }

        return $validity;
    }

    function leaflet_map_loaded(): void
    {
        if (is_customize_preview()) {
            wp_enqueue_script('osm-proxy-customizer-preview', OSM_PROXY_BASE_URL . 'assets/customizer-preview.js', ['wp_leaflet_map'], filemtime(OSM_PROXY_BASE_DIR . 'assets/customizer-preview.js'), true);
        }
    }
}