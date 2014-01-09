<?php

/**
 * WP Geo API
 * Can be extended for other APIs.
 */
class WPGeo_API {

	/**
	 * Constructor
	 */
	function WPGeo_API() {
	}

	/**
	 * Map
	 * Gets map to display.
	 *
	 * @param   object  $map  WPGeo_Map object.
	 * @return  string        HTML.
	 */
	function map( $map ) {
		return '';
	}

	/**
	 * Input Map
	 * Gets input map to display.
	 *
	 * @param   object  $map  WPGeo_Map object.
	 * @return  string        HTML.
	 */
	function input_map( $map ) {
		return '';
	}

	/**
	 * Static Map URL
	 * Gets static map URL.
	 *
	 * @param   object  $map  WPGeo_Map object.
	 * @return  string        HTML.
	 */
	function static_map_url( $map ) {
		$point = $map->get_point();
		$coord = $point->get_coord();
		if ( ! $coord->is_valid_coord() ) {
			return '';
		}

		$center = $map->get_map_centre();
		$types = $this->static_map_types();

		$url = add_query_arg( array(
			'center'  => $center->get_delimited(),
			'zoom'    => $map->get_map_zoom(),
			'size'    => $map->get_width() . 'x' . $map->get_height(),
			'maptype' => $types[$map->get_map_type()],
			'markers' => 'color:red%7C' . $coord->get_delimited(),
			'sensor'  => 'false'
		), 'http://maps.googleapis.com/maps/api/staticmap' );
		return apply_filters( 'wpgeo_static_map_url', $url, $map );
	}

	/**
	 * Map Link
	 * Gets a link to an external map.
	 *
	 * @param   object  $map  WPGeo_Map object.
	 * @return  string        Map URL.
	 */
	function map_url( $map ) {
		$point = $map->get_point();
		$coord = $point->get_coord();
		if ( ! $coord->is_valid_coord() ) {
			return '';
		}

		$url = add_query_arg( array(
			'q' => $coord->get_delimited(),
			'z' => $map->get_map_zoom()
		),'http://maps.google.co.uk/maps' );
		return apply_filters( 'wpgeo_map_url', $url, $map );
	}

	/**
	 * Map Types
	 *
	 * @return  array  Map types.
	 */
	function map_types() {
		$types = array(
			'G_NORMAL_MAP'    => __( 'Normal', 'wp-geo' ),
			'G_SATELLITE_MAP' => __( 'Satellite (photographic map)', 'wp-geo' ),
			'G_HYBRID_MAP'    => __( 'Hybrid (photographic map with normal features)', 'wp-geo' ),
			'G_PHYSICAL_MAP'  => __( 'Physical (terrain map)', 'wp-geo' )
		);
		return $types;
	}

	/**
	 * Map Type Options
	 *
	 * @return  array  Map type options.
	 */
	function map_type_options() {
		$types = array(
			'G_NORMAL_MAP'    => 'show_map_type_normal',
			'G_SATELLITE_MAP' => 'show_map_type_satellite',
			'G_HYBRID_MAP'    => 'show_map_type_hybrid',
			'G_PHYSICAL_MAP'  => 'show_map_type_physical'
		);
		return $types;
	}

	/**
	 * Static Map Types
	 *
	 * @return  array  Static map types.
	 */
	function static_map_types() {
		return array(
			'G_NORMAL_MAP'    => 'roadmap',
			'G_SATELLITE_MAP' => 'satellite',
			'G_PHYSICAL_MAP'  => 'terrain',
			'G_HYBRID_MAP'    => 'hybrid'
		);
	}

	/**
	 * Map Controls
	 *
	 * @return  array  Map controls.
	 */
	function map_controls() {
		$controls = array(
			'GLargeMapControl3D'  => __( 'Large 3D pan/zoom control', 'wp-geo' ),
			'GLargeMapControl'    => __( 'Large pan/zoom control', 'wp-geo' ),
			'GSmallMapControl'    => __( 'Smaller pan/zoom control', 'wp-geo' ),
			'GSmallZoomControl3D' => __( 'Small 3D zoom control (no panning controls)', 'wp-geo' ),
			'GSmallZoomControl'   => __( 'Small zoom control (no panning controls)', 'wp-geo' ),
			''                    => __( 'No pan/zoom controls', 'wp-geo' )
		);
		return $controls;
	}

	/**
	 * Zoom Values
	 *
	 * @return  array  Zoom values.
	 */
	function zoom_values() {
		$values = array();
		for ( $i = 0; $i <= 19; $i++ ) {
			$values[$i] = $i;
			if ( $i == 0 ) {
				$values[$i] .= ' - ' . __( 'Zoomed Out', 'wp-geo' );
			} elseif ( $i == 19 ) {
				$values[$i] .= ' - ' . __( 'Zoomed In', 'wp-geo' );
			}
		}
		return $values;
	}

}
