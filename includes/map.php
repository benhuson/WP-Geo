<?php

/**
 * WP Geo Map
 * The WPGeo_Map class manages data for a single map
 * and handles the output of that map.
 */
class WPGeo_Map {
	
	var $id;
	var $width = '100%';
	var $height = '350';
	
	var $points;
	var $polylines;
	var $maptypes;
	var $feeds;
	
	var $zoom = 5;
	var $maptype = 'G_NORMAL_MAP';
	var $mapcentre;
	
	var $mapcontrol = '';
	var $show_map_scale = false;
	var $show_map_overview = false;
	var $show_streetview_control = false;
	var $show_polyline = false;

	/**
	 * Constructor
	 *
	 * @param string $id Map ID.
	 */
	function __construct( $id = 0 ) {
		$wp_geo_options = get_option( 'wp_geo_options' );

		$this->id         = $this->validate_map_id( $id );
		$this->points     = array();
		$this->polylines  = array();
		$this->maptypes   = array();
		$this->feeds      = array();
		$this->mapcentre  = new WPGeo_Coord( $wp_geo_options['default_map_latitude'], $wp_geo_options['default_map_longitude'] );
		$this->mapcontrol = $wp_geo_options['default_map_control'];
	}

	/**
	 * Validate Map ID
	 *
	 * @param string $id The map ID.
	 * @return string Map ID.
	 */
	function validate_map_id( $id ) {
		global $wpgeo;
		$id = str_replace( '-', '_', sanitize_html_class( $id ) );
		return $wpgeo->maps->get_unique_map_id( $id );
	}
	
	/**
	 * Add Feed
	 */
	function add_feed( $url ) {
		$this->feeds[] = $url;
	}

	function set_size( $w, $h ) {
		$this->set_width( $w );
		$this->set_height( $h );
	}
	
	function set_width( $w ) {
		$this->width = $w;
	}
	
	function set_height( $h ) {
		$this->height = $h;
	}

	function get_width() {
		return $this->width;
	}

	function get_height() {
		return $this->height;
	}

	function get_dom_id() {
		return 'wpgeo_map_' . $this->id;
	}
	
	function get_js_id() {
		return 'wpgeo_map_' . $this->id;
	}
	
	/**
	 * Get the HTML for a map.
	 *
	 * @param array $atts Optional. Array of attributes and content for the map <div>.
	 * @return string HTML.
	 */
	function get_map_html( $atts = null ) {
		$atts = wp_parse_args( $atts, array(
			'classes' => array(),
			'styles'  => array(),
			'content' => ''
		) );
		
		// Classes
		if ( ! is_array( $atts['classes'] ) ) {
			$atts['classes'] = array( $atts['classes'] );
		}
		$atts['classes'][] = 'wpgeo_map';
		$atts['classes'][] = 'wp_geo_map'; // For legacy compatibility
		$atts['classes'] = array_unique( $atts['classes'] );
		
		// Styles
		$atts['styles'] = wp_parse_args( $atts['styles'], array(
			'width'  => $this->width,
			'height' => $this->height
		) );
		$styles = '';
		foreach ( $atts['styles'] as $style => $value) {
			if ( in_array( $style, array( 'width', 'height' ) ) ) {
				$value = wpgeo_css_dimension( $value );
			}
			$styles .= $style . ':' . $value . ';';
		}
		
		return sprintf( '<div id="%s" class="%s" style="%s">%s</div>', esc_attr( $this->get_dom_id() ), esc_attr( implode( ' ', $atts['classes'] ) ), esc_attr( $styles ), $atts['content'] );
	}
	
	/**
	 * Get the Javascript for a map.
	 *
	 * @todo Deprecate.
	 */
	function get_map_javascript() {
		return '';
	}
	
	/**
	 * Add a Point (Marker) to this map.
	 *
	 * @param object $coord WPGeo_Coord.
	 * @param array $args (optional) Array of marker options.
	 */
	function add_point( $coord, $args = null ) {
		$this->points[] = new WPGeo_Point( $coord, $args );
	}
	// @todo Deprecate
	function addPoint( $lat, $long, $icon = 'large', $title = '', $link = '' ) {
		$coord = new WPGeo_Coord( $lat, $long );
		$this->add_point( $coord, array(
			'icon'  => $icon,
			'title' => $title,
			'link'  => $link
		) );
	}

	/**
	 * Get Point
	 *
	 * @param   int  $n  N-th point.
	 * @return  object   WPGeo_Point.
	 */
	function get_point( $n = 0 ) {
		if ( count( $this->points ) >= $n + 1 ) {
			return $this->points[$n];
		}
		return false;
	}

	/**
	 * Show polylines on this map?
	 *
	 * @param bool $bool True/false.
	 */
	function showPolyline( $bool = true ) {
		$this->show_polyline = $bool;
	}
	
	/**
	 * Add Polyline
	 *
	 * @param object $polyline WPGeo_Polyline.
	 */
	function add_polyline( $polyline ) {
		$this->polylines[] = $polyline;
	}
	
	/**
	 * Set Map Control
	 * Set the type of map control that should be used for this map.
	 *
	 * @param string $mapcontrol Type of map control.
	 */
	function setMapControl( $mapcontrol = 'GLargeMapControl3D' ) {
		$this->mapcontrol = $mapcontrol;
	}
	
	/**
	 * Set the type of map
	 *
	 * @param string $maptype Type of map.
	 */
	function set_map_type( $maptype = 'G_NORMAL_MAP' ) {
		if ( $this->is_valid_map_type( $maptype ) ) {
			$this->maptype = $maptype;
		}
	}
	// @todo Deprecate
	function setMapType( $maptype = 'G_NORMAL_MAP' ) {
		$this->set_map_type( $maptype );
	}
	
	/**
	 * get the type of map
	 *
	 * @return string Type of map.
	 */
	function get_map_type() {
		return $this->maptype;
	}

	/**
	 * Get Map Types
	 *
	 * @return  array  Map types.
	 */
	function get_map_types() {
		return $this->maptypes;
	}

	/**
	 * Set the centre point of the map
	 *
	 * @param object $coord WPGeo_Coord.
	 */
	function set_map_centre( $coord ) {
		$this->mapcentre = $coord;
	}
	// @todo Deprecate
	function setMapCentre( $latitude, $longitude ) {
		$coord = new WPGeo_Coord( $latitude, $longitude );
		$this->set_map_centre( $coord );
	}
	
	/**
	 * Get the centre point of the map
	 *
	 * @param return WPGeo_Coord.
	 */
	function get_map_centre() {
		return $this->mapcentre;
	}
	
	/**
	 * Add a type of map
	 *
	 * @param string $maptype Type of map.
	 */
	function addMapType( $maptype ) {
		if ( $this->is_valid_map_type( $maptype ) ) {
			$this->maptypes[] = $maptype;
			$this->maptypes = array_unique( $this->maptypes );
		}
	}
	
	/**
	 * Is Valid Map Type
	 * Check to see if a map type is allowed.
	 *
	 * @param string $maptype Type of map.
	 */
	function is_valid_map_type( $maptype ) {
		global $wpgeo;

		$types = array_keys( $wpgeo->api->map_types() );
		return in_array( $maptype, $types );
	}
	
	/**
	 * Set the default zoom of this map
	 *
	 * @param int $zoom Zoom.
	 */
	function set_map_zoom( $zoom = 5 ) {
		$this->zoom = absint( $zoom );
	}
	// @todo Deprecate
	function setMapZoom( $zoom = 5 ) {
		$this->set_map_zoom( $zoom );
	}
	
	/**
	 * Get the default zoom of this map
	 *
	 * @return int Zoom.
	 */
	function get_map_zoom() {
		return $this->zoom;
	}
	
	/**
	 * Show Map Scale
	 * Show the scale at the bottom of the map?
	 *
	 * @param bool $bool True/false.
	 */
	function showMapScale( $bool = true ) {
		$this->show_map_scale = $bool;
	}

	/**
	 * Show Map Overview
	 * Show the mini overview map?
	 *
	 * @param bool $bool True/false.
	 */
	function showMapOverview( $bool = true ) {
		$this->show_map_overview = $bool;
	}

	/**
	 * Show Street View Control
	 *
	 * @param  bool  Show control?
	 */
	function show_streetview_control( $bool = true ) {
		$this->show_streetview_control = $bool;
	}

	/**
	 * Show Control
	 *
	 * @param   string  $control  Check wether control should show.
	 * @return  bool              Show control?
	 */
	function show_control( $control ) {
		switch ( $control ) {
			case 'scale' :
				return $this->show_map_scale;
			case 'overview' :
				return $this->show_map_overview;
			case 'pan' :
				if ( in_array( $this->mapcontrol, array( 'GLargeMapControl3D', 'GLargeMapControl', 'GSmallMapControl' ) ) ) {
					return true;
				}
				break;
			case 'zoom' :
				if ( in_array( $this->mapcontrol, array( 'GLargeMapControl3D', 'GLargeMapControl', 'GSmallMapControl', 'GSmallZoomControl3D', 'GSmallZoomControl' ) ) ) {
					return true;
				}
				break;
			case 'streetview' :
				return $this->show_streetview_control;
		}
		return false;
	}

}
