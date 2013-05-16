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
		$coord = $map->get_point();
		$center = $map->get_map_centre();
		$types = $this->_static_map_types();

		$url = add_query_arg( array(
			'center'  => $center->get_delimited(),
			'zoom'    => $map->get_map_zoom(),
			'size'    => $map->get_width() . 'x' . $map->get_height(),
			'maptype' => $types[$map->get_map_type()],
			'markers' => 'color:red%7C' . $coord->coord->get_delimited(),
			'sensor'  => 'false'
		), 'http://maps.googleapis.com/maps/api/staticmap' );
		return $url;
	}

	/**
	 * Map Link
	 * Gets a link to an external map.
	 *
	 * @param   object  $map  WPGeo_Map object.
	 * @return  string        Map URL.
	 */
	function map_url( $map ) {
		return '';
	}

	/**
	 * Static Map Types
	 *
	 * @return  array Static map types.
	 */
	function _static_map_types() {
		return array(
			'G_NORMAL_MAP'    => 'roadmap',
			'G_SATELLITE_MAP' => 'satellite',
			'G_PHYSICAL_MAP'  => 'terrain',
			'G_HYBRID_MAP'    => 'hybrid'
		);
	}

}
