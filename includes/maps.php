<?php

/**
 * WP Geo Maps
 * The WPGeo_Maps class manages all the maps present
 * on an HTML page and the output of those maps.
 */
class WPGeo_Maps {
	
	var $maps; // An array of WPGeo_Map objects
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->maps = array();
	}

	/**
	 * Add Map
	 * Adds a WPGeo_Map object to the maps array.
	 *
	 * @param object $map WPGeo_Map object.
	 * @return object WPGeo_Map object.
	 */
	function add_map( $map ) {
		if ( $map->id === 0 ) {
			$map->id = count( $this->maps ) + 1;
		}
		$this->maps[] = $map;
		return $map;
	}
	
	/**
	 * Get Unique Map ID
	 * Checks all existing maps to see if the ID already exists and if
	 * it does append a number to create a unique ID.
	 *
	 * @param string $id ID.
	 * @return string Unique ID.
	 */
	function get_unique_map_id( $id, $iteration = 1 ) {
		$combined_id = $id . '_' . $iteration;
		foreach ( $this->maps as $map ) {
			if ( $map->id == $combined_id ) {
				$combined_id = $this->get_unique_map_id( $id, $iteration + 1 );
				break;
			}
		}
		return $combined_id;
	}
	
	/**
	 * Get the javascript to display all maps
	 *
	 * @todo Deprecate.
	 */
	function get_maps_javascript() {
		return '';
	}
	
}
