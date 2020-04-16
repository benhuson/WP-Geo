<?php

/**
 * Polyline Class
 */
class WPGeo_Polyline {

	var $coords    = array();
	var $geodesic  = true;
	var $color     = '#FFFFFF';
	var $thickness = 2;
	var $opacity   = 0.5;

	/**
	 * Constructor
	 *
	 * @param array $args Args.
	 */
	function __construct( $args = null ) {
		$args = wp_parse_args( $args, array(
			'coords'    => $this->coords,
			'geodesic'  => $this->geodesic,
			'color'     => $this->color,
			'thickness' => $this->thickness,
			'opacity'   => $this->opacity
		) );
		$this->coords    = $args['coords'];
		$this->geodesic  = $args['geodesic'];
		$this->color     = $args['color'];
		$this->thickness = $args['thickness'];
		$this->opacity   = $args['opacity'];
	}

	/**
	 * Add Coord
	 *
	 * @param float $coord WPGeo_Coord object (or deprecated Latitude).
	 * @param float $longitude Longitude (deprecated).
	 */
	function add_coord( $coord, $longitude = null ) {
		if ( is_object( $coord ) && get_class( $coord ) == 'WPGeo_Coord' ) {
			$this->coords[] = $coord;
		} else {
			$this->coords[] = new WPGeo_Coord( $coord, $longitude );
		}
	}

	/**
	 * Get Coords
	 *
	 * @return  array  Array of WPGeo_Coord objects.
	 */
	function get_coords() {
		return $this->coords;
	}

	/**
	 * Get Geodesic
	 *
	 * @return  string  Geodesic.
	 */
	function get_geodesic() {
		return $this->geodesic;
	}

	/**
	 * Is Geodesic?
	 *
	 * @return  bool  Is geodesic display.
	 */
	function is_geodesic() {
		if ( $this->geodesic ) {
			return true;
		}
		return false;
	}

	/**
	 * Get Color
	 *
	 * @return  string  Color.
	 */
	function get_color() {
		return $this->color;
	}

	/**
	 * Get Thickness
	 *
	 * @return  string  Thickness.
	 */
	function get_thickness() {
		return $this->thickness;
	}

	/**
	 * Get Opacity
	 *
	 * @return  string  Opacity.
	 */
	function get_opacity() {
		return $this->opacity;
	}

}
