<?php

namespace MOEWE\OSM_Tiles_proxy;

class Customizer {

	public function __construct() {
		add_action( 'customize_register', [ $this, 'customize_register' ] );
		add_action( 'leaflet_map_loaded', [ $this, 'leaflet_map_loaded' ] );
	}

	/**
	 * @param $wp_customize \WP_Customize_Manager The manager
	 */
	function customize_register( $wp_customize ) {

		$max_x_y = pow( 2, absint( get_option( 'osm_tiles_proxy_max_zoom', 20 ) ) ) - 1;


		$wp_customize->add_section( 'osm_tiles_proxy_section', array(
			'title'       => __( 'OSM Tiles Proxy', 'osm-tiles-proxy' ),
			'description' => __( 'Use these settings to restrict the area, the proxy is allowed to download. This is useful to avoid bloating your servers space with map tiles you never need.', 'osm-tiles-proxy' ),
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_min_zoom', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => 0
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_max_zoom', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => 20
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_min_x', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => 0
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_max_x', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => $max_x_y
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_min_y', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => 0
		) );

		$wp_customize->add_setting( 'osm_tiles_proxy_max_y', array(
			'type'              => 'option',
			'sanitize_callback' => 'absint',
			'default'           => $max_x_y
		) );

		// Zoom Levels
		$wp_customize->add_control( 'osm_tiles_proxy_min_zoom_control', array(
			'label'       => __( 'Min. Zoom', 'osm-tiles-proxy' ),
			'description' => __( 'Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Zoom_levels">zoom level</a> the proxy is allowed to download (0 = whole world, 20 = midi sized building).', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_min_zoom',
			'type'        => 'number',
			'default'     => 0,
			'input_attrs' => array(
				'min' => 0,
				'max' => 20,
			)
		) );

		$wp_customize->add_control( 'osm_tiles_proxy_max_zoom_control', array(
			'label'       => __( 'Max. Zoom', 'osm-tiles-proxy' ),
			'description' => __( 'Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Zoom_levels">zoom level</a> the proxy is allowed to download (0 = whole world, 20 = midi sized building).', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_max_zoom',
			'type'        => 'number',
			'default'     => 18,
			'input_attrs' => array(
				'min' => 0,
				'max' => 20,
			),
		) );

		// X-Coordinates
		$wp_customize->add_control( 'osm_tiles_proxy_min_x_control', array(
			'label'       => __( 'Min. X coordinate at zoom level 12', 'osm-tiles-proxy' ),
			'description' => __( 'Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">x coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_min_x',
			'type'        => 'number',
			'input_attrs' => array(
				'default' => 0,
				'min'     => 0,
				'max'     => $max_x_y,
			),
		) );

		$wp_customize->add_control( 'osm_tiles_proxy_max_x_control', array(
			'label'       => __( 'Max. X coordinate at zoom level 12', 'osm-tiles-proxy' ),
			'description' => __( 'Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">x coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_max_x',
			'type'        => 'number',
			'default'     => $max_x_y,
			'input_attrs' => array(
				'min' => 0,
				'max' => $max_x_y,
			),
		) );

		// Y-Coordinates
		$wp_customize->add_control( 'osm_tiles_proxy_min_y_control', array(
			'label'       => __( 'Min. Y coordinate at zoom level 12', 'osm-tiles-proxy' ),
			'description' => __( 'Select the minimal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">y coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_min_y',
			'type'        => 'number',
			'input_attrs' => array(
				'default' => 0,
				'min'     => 0,
				'max'     => $max_x_y,
			),
		) );

		$wp_customize->add_control( 'osm_tiles_proxy_max_y_control', array(
			'label'       => __( 'Max. Y coordinate at zoom level 12', 'osm-tiles-proxy' ),
			'description' => __( 'Select the maximal <a target="_blank" href="https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#X_and_Y">y coordinate</a> the proxy is allowed to download.', 'osm-tiles-proxy' ),
			'section'     => 'osm_tiles_proxy_section',
			'settings'    => 'osm_tiles_proxy_max_y',
			'type'        => 'number',
			'default'     => $max_x_y,
			'input_attrs' => array(
				'min' => 0,
				'max' => $max_x_y,
			),
		) );
	}

	function leaflet_map_loaded() {
		if ( is_customize_preview() ) {
			wp_enqueue_script( 'osm-proxy-customizer-preview', OSM_PROXY_BASE_URL . 'assets/customizer-preview.js', ['wp_leaflet_map'], filemtime( OSM_PROXY_BASE_DIR . 'assets/customizer-preview.js' ), true );
		}
	}
}