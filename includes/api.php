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
		if ( ! $point->coord->is_valid_coord() )
			return '';

		$center = $map->get_map_centre();
		$types = $this->static_map_types();

		$url = add_query_arg( array(
			'center'  => $center->get_delimited(),
			'zoom'    => $map->get_map_zoom(),
			'size'    => $map->get_width() . 'x' . $map->get_height(),
			'maptype' => $types[$map->get_map_type()],
			'markers' => 'color:red%7C' . $point->coord->get_delimited(),
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
		if ( ! $point->coord->is_valid_coord() )
			return '';

		$url = add_query_arg( array(
			'q' => $point->coord->get_delimited(),
			'z' => $map->get_map_zoom()
		),'http://maps.google.co.uk/maps' );
		return apply_filters( 'wpgeo_map_url', $url, $map );
	}

	/**
	 * Static Map Types
	 *
	 * @return  array Static map types.
	 */
	function static_map_types() {
		return array(
			'G_NORMAL_MAP'    => 'roadmap',
			'G_SATELLITE_MAP' => 'satellite',
			'G_PHYSICAL_MAP'  => 'terrain',
			'G_HYBRID_MAP'    => 'hybrid'
		);
	}

}
