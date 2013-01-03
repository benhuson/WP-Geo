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
	function WPGeo_Maps() {
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
		if ( $map->id == 0 ) {
			$map->id = count( $this->maps ) + 1;
		}
		$this->maps[] = $map;
		return $map;
	}
	
	/**
	 * Get the javascript to display all maps
	 *
	 * @return string JavaScript.
	 */
	function get_maps_javascript() {
		$javascript = '';
		foreach ( $this->maps as $map ) {
			$javascript .= $map->get_map_javascript();
		}
		return $javascript;
	}
	
}

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
	
	var $mapcontrol = 'GLargeMapControl3D';
	var $show_map_scale = false;
	var $show_map_overview = false;
	var $show_polyline = false;
	
	/**
	 * Constructor
	 *
	 * @param string $id Map ID.
	 */
	function WPGeo_Map( $id = 0 ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		$this->id        = $this->validate_map_id( $id );
		$this->points    = array();
		$this->polylines = array();
		$this->maptypes  = array();
		$this->feeds     = array();
		$this->mapcentre = new WPGeo_Coord( $wp_geo_options['default_map_latitude'], $wp_geo_options['default_map_longitude'] );
	}
	
	/**
	 * Validate Map ID
	 *
	 * @param string $id The map ID.
	 * @return string Map ID.
	 */
	function validate_map_id( $id ) {
		return str_replace( '-', '_', sanitize_html_class( $id ) );
	}
	
	/**
	 * Add Feed
	 */
	function add_feed( $url ) {
		$this->feeds[] = $url;
	}
	
	/**
	 * Render Map JavaScript
	 * Outputs the javascript to display maps.
	 *
	 * @param string $map_id The map ID.
	 * @return string JavaScript.
	 */
	function renderMapJS( $map_id = false ) {
		global $wpgeo;
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// ID of div for map output
		$map_id = $map_id ? $map_id : $this->id;
		$div = 'wpgeo_map_' . $map_id;
		
		// Map Types
		$maptypes = $this->maptypes;
		$maptypes[] = $this->maptype;
		$maptypes = array_unique( $maptypes );
		$js_maptypes = WPGeo_API_GMap2::render_map_types( 'map_' . $map_id, $maptypes );
		
		// Markers
		$js_markers = '';
		$js_markers_v3 = '';
		if ( count( $this->points ) > 0 ) {
			for ( $i = 0; $i < count( $this->points ); $i++ ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $this->points[$i]['icon'], $this->id, 'wpgeo_map' );
				$js_markers .= 'var marker_' . $map_id .'_' . $i . ' = new wpgeo_createMarker2(map_' . $map_id . ', new GLatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '), ' . $icon . ', \'' . addslashes( __( $this->points[$i]['title'] ) ) . '\', \'' . $this->points[$i]['link'] . '\');' . "\n";
				$js_markers .= 'bounds.extend(new GLatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '));';
				// @todo Tooltip, icon and link for v3
				$js_markers_v3 .= 'var marker_' . $map_id .'_' . $i . ' = new google.maps.Marker({ position:new google.maps.LatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '), map:map_' . $map_id . ', icon: ' . $icon . ' });' . "\n";
				$js_markers_v3 .= 'bounds.extend(new google.maps.LatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '));' . "\n";
			}
		}
		
		// Show Polyline
		$js_polyline = '';
		$js_polyline_v3 = '';
		if ( $wp_geo_options['show_polylines'] == 'Y' ) {
			if ( $this->show_polyline ) {
				if ( count( $this->points ) > 1 ) {
					$polyline = new WPGeo_Polyline( array(
						'color' => $wp_geo_options['polyline_colour']
					) );
					for ( $i = 0; $i < count( $this->points ); $i++ ) {
						$polyline->add_coord( $this->points[$i]['latitude'], $this->points[$i]['longitude'] );
					}
					$js_polyline .= WPGeo_API_GMap2::render_map_overlay( 'map_' . $map_id, WPGeo_API_GMap2::render_polyline( $polyline ) );
					// v3
					$polyline_js_3_coords = array();
					foreach ( $polyline->coords as $c ) {
						$polyline_js_3_coords[] = 'new google.maps.LatLng(' . $c->latitude . ', ' . $c->longitude . ')';
					}
					$js_polyline_v3 = 'var polyline = new google.maps.Polyline({
							path: [' . implode( ',', $polyline_js_3_coords ) . '],
							strokeColor: "' . $polyline->color . '",
							strokeOpacity: ' . $polyline->opacity . ',
							strokeWeight: ' . $polyline->thickness . ',
							geodesic : ' . $polyline->geodesic . '
						});
						polyline.setMap(map);';
				}
			}
		}
		
		// Zoom
		$js_zoom = '';
		$js_center_v3 = 'var center = new google.maps.LatLng(' . $this->mapcentre->latitude() . ', ' . $this->mapcentre->longitude() . ');';
		if ( count( $this->points ) > 1 ) {
			$js_zoom .= 'map_' . $map_id . '.setCenter(bounds.getCenter(), map_' . $map_id . '.getBoundsZoomLevel(bounds));';
			$js_center_v3 = 'var center = bounds.getCenter();';
		}
		if ( count( $this->points ) == 1 ) {
			if ( $this->mapcentre->is_valid_coord() ) {
				$js_zoom .= 'map_' . $map_id . '.setCenter(new GLatLng(' . $this->mapcentre->latitude() . ', ' . $this->mapcentre->longitude() . '));';
			}
		}
		
		// Controls
		$js_controls = '';
		if ( $this->show_map_scale )
			$js_controls .= WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, 'GScaleControl' );
		if ( $this->show_map_overview )
			$js_controls .= WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, 'GOverviewMapControl' );
		
		// Map Javascript
		if ( 'googlemapsv3' == $wpgeo->get_api_string() ) {
			$js = '
				if (document.getElementById("' . $div . '")) {
					var bounds = new google.maps.LatLngBounds();
					
					var mapOptions = {
						center    : new google.maps.LatLng(' . $this->points[0]['latitude'] . ', ' . $this->points[0]['longitude'] . '),
						zoom      : ' . $this->zoom . ',
						// @todo Map Types
						mapTypeId : ' . apply_filters( 'wpgeo_api_string', 'ROADMAP', $this->maptype, 'maptype' ) . ',
						// @todo Map Control
					};
					map_' . $map_id . ' = new google.maps.Map(document.getElementById("' . $div . '"), mapOptions);
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', 'map_' . $map_id ) . '
					' . $js_markers_v3 . '
					' . $js_polyline_v3 . '
					' . $js_center_v3 . '
					var zoom = map_' . $map_id . '.getBounds(bounds);
					if (zoom > ' . $this->zoom . ') {
						zoom = ' . $this->zoom . ';
					}
					map_' . $map_id . '.setCenter(center);
					if (zoom) {
						map_' . $map_id . '.setZoom(zoom);
					}
				}
				';
		} else {
			$js = '
				if (document.getElementById("' . $div . '")) {
					var bounds = new GLatLngBounds();
		
					map_' . $map_id . ' = new GMap2(document.getElementById("' . $div . '"));
					var center = new GLatLng(' . $this->points[0]['latitude'] . ', ' . $this->points[0]['longitude'] . ');
					map_' . $map_id . '.setCenter(center, ' . $this->zoom . ');
					
					' . $js_maptypes . '
					map_' . $map_id . '.setMapType(' . $this->maptype . ');
					
					' . WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, 'GMapTypeControl' );
			if ( $this->mapcontrol != "" ) {
				$js .= WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, $this->mapcontrol );
			}
			$js .= '
					var center_' . $map_id .' = new GLatLng(' . $this->points[0]['latitude'] . ', ' . $this->points[0]['longitude'] . ');
					
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', 'map_' . $map_id ) . '
					
					' . $js_markers . '
					' . $js_polyline . '
					' . $js_zoom . '
					' . $js_controls . '
					
				}';
		}
		return $js;
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
	
	function get_dom_id() {
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
	 * @return string JavaScript.
	 */
	function get_map_javascript() {
		$js = '
			wpgeo_map_' . $this->id . ' = new GMap2(document.getElementById("wpgeo_map_' . $this->id . '"));
			';
		return $js;
	}
	
	/**
	 * Add a Marker to this map.
	 *
	 * @param object $coord WPGeo_Coord.
	 * @param array $args (optional) Array of marker options.
	 */
	function add_marker( $coord, $args = null ) {
		$marker = new WPGeo_Point( $coord, $args );
		$this->points[] = array(
			'latitude'  => $marker->coord->latitude(), 
			'longitude' => $marker->coord->longitude(),
			'icon'      => $marker->icon,
			'title'     => $marker->title,
			'link'      => $marker->link,
		);
	}
	// @todo Deprecate
	function addPoint( $lat, $long, $icon = 'large', $title = '', $link = '' ) {
		$coord = new WPGeo_Coord( $lat, $long );
		$this->add_marker( $coord, array(
			'icon'  => $icon,
			'title' => $title,
			'link'  => $link
		) );
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
		$types = array(
			'G_PHYSICAL_MAP',
			'G_NORMAL_MAP',
			'G_SATELLITE_MAP',
			'G_HYBRID_MAP'
		);
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
	
}

/**
 * WP Geo Map API
 * Renders various GMap objects.
 */
class WPGeo_API_GMap2 {
	
	/**
	 * Render Map Types
	 *
	 * @param string $map JavaScript map var.
	 * @param array $maptypes Array of map types.
	 * @return string JavaScript.
	 */
	function render_map_types( $map, $maptypes ) {
		$output = '';
		if ( is_array( $maptypes ) ) {
			if ( in_array( 'G_PHYSICAL_MAP', $maptypes ) )
				$output .= $map . '.addMapType(G_PHYSICAL_MAP);';
			if ( ! in_array( 'G_NORMAL_MAP', $maptypes ) )
				$output .= $map . '.removeMapType(G_NORMAL_MAP);';
			if ( ! in_array( 'G_SATELLITE_MAP', $maptypes ) )
				$output .= $map . '.removeMapType(G_SATELLITE_MAP);';
			if ( ! in_array( 'G_HYBRID_MAP', $maptypes ) )
				$output .= $map . '.removeMapType(G_HYBRID_MAP);';
		}
		return $output;
	}
	
	/**
	 * Render Map Overlay
	 *
	 * @param string $map JavaScript map var.
	 * @param string $overlay Overlay var.
	 * @return string JavaScript.
	 */
	function render_map_overlay( $map, $overlay ) {
		if ( is_string( $overlay ) ) {
			$output = $map . '.addOverlay(' . $overlay . ');';
		} else {
			$output = '';
		}
		return $output;
	}
	
	/**
	 * Render Map Control
	 *
	 * @param string $map JavaScript map var.
	 * @param string $control Control class name.
	 * @return string JavaScript.
	 */
	function render_map_control( $map, $control ) {
		//if ( is_string( $control ) ) {
			$output = $map . '.addControl(new ' . $control . '());';
		//}
		return $output;
	}
	
	/**
	 * Render Polyline
	 *
	 * @param string $polyline WPGeo_Polyline object.
	 * @return string JavaScript.
	 */
	function render_polyline( $polyline ) {
		
		// Coords
		$coords = array();
		foreach ( $polyline->coords as $coord ) {
			$coords[] = WPGeo_API_GMap2::render_coord( $coord );
		}
		
		// Options
		$options = array();
		if ( $polyline->geodesic ) {
			$options[] = 'geodesic:true';
		}
		return 'new GPolyline([' . implode( ',', $coords ) . '],"' . $polyline->color . '",' . $polyline->thickness . ',' . $polyline->opacity . ',{' . implode( ',', $options ) . '})';
	}
	
	/**
	 * Render Coord
	 *
	 * @param string $coord WPGeo_Coord object.
	 * @return string JavaScript.
	 */
	function render_coord( $coord ) {
		return 'new GLatLng(' . $coord->latitude . ',' . $coord->longitude . ')';
	}
	
}

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
	function WPGeo_Polyline( $args = null ) {
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
	
}

/**
 * Point Class
 */
class WPGeo_Point {
	
	var $coord = null;
	var $icon  = 'large';
	var $title = '';
	var $link  = '';
	
	/**
	 * Constructor
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 */
	function WPGeo_Point( $coord, $args = null ) {
		$args = wp_parse_args( $args, array(
			'icon'  => 'large',
			'title' => '',
			'link'  => ''
		) );
		$this->coord = $coord;
		$this->icon  = $args['icon'];
		$this->title = $args['title'];
		$this->link  = $args['link'];
	}
	
}

/**
 * Coord Class
 */
class WPGeo_Coord {
	
	var $latitude  = null;
	var $longitude = null;
	
	/**
	 * Constructor
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 */
	function WPGeo_Coord( $latitude, $longitude ) {
		$this->latitude  = $latitude;
		$this->longitude = $longitude;
		if ( $this->is_valid_coord() ) {
			$this->latitude  = floatval( $this->latitude );
			$this->longitude = floatval( $this->longitude );
		}
	}

	/**
	 * Is Valid Geo Coord
	 *
	 * @param float $lat Latitude.
	 * @param float $long Longitude.
	 * @return bool
	 */
	function is_valid_coord() {
		if ( is_numeric( $this->latitude ) && is_numeric( $this->longitude ) )
			return true;
		return false;
	}

	/**
	 * Get Longitude
	 *
	 * @return float
	 */
	function latitude() {
		return $this->latitude;
	}

	/**
	 * Get Longitude
	 *
	 * @return float
	 */
	function longitude() {
		return $this->longitude;
	}
	
	/**
	 * Get Delimited
	 * Returns the latitude and longitude as a string.
	 * By default the values are delimited by a comma.
	 *
	 * @return string Delimited coordinate string.
	 */
	function get_delimited( $delimiter = ',' ) {
		return $this->latitude . $delimiter . $this->longitude; 
	}
	
}

?>