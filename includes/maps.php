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
		$js_maptypes = '';
		if ( is_array( $maptypes ) ) {
			$types = $wpgeo->api->map_types();
			foreach ( $types as $key => $val ) {
				if ( in_array( $key, $maptypes ) )
					$js_maptypes .= 'map_' . $map_id . '.addMapType(' . $key . ');';
			}
		}
		
		// Markers
		$js_markers = '';
		$js_markers_v3 = '';
		if ( count( $this->points ) > 0 ) {
			for ( $i = 0; $i < count( $this->points ); $i++ ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $this->points[$i]->icon, $this->id, 'wpgeo_map' );
				$js_markers .= 'var marker_' . $map_id .'_' . $i . ' = new wpgeo_createMarker2(map_' . $map_id . ', new GLatLng(' . $this->points[$i]->coord->get_delimited() . '), ' . $icon . ', \'' . addslashes( __( $this->points[$i]->title ) ) . '\', \'' . $this->points[$i]->link . '\');' . "\n";
				$js_markers .= 'bounds.extend(new GLatLng(' . $this->points[$i]->coord->get_delimited() . '));';
				// @todo Tooltip, icon and link for v3
				$js_markers_v3 .= 'var marker_' . $map_id .'_' . $i . ' = new google.maps.Marker({ position:new google.maps.LatLng(' . $this->points[$i]->coord->get_delimited() . '), map:map_' . $map_id . ', icon: ' . $icon . ' });' . "\n";
				$js_markers_v3 .= 'bounds.extend(new google.maps.LatLng(' . $this->points[$i]->coord->get_delimited() . '));' . "\n";
				if ( ! empty( $this->points[$i]->link ) ) {
					$js_markers_v3 .= 'google.maps.event.addListener(marker_' . $map_id .'_' . $i . ', "click", function() {
							window.location.href = "' . $this->points[$i]->link . '";
						});
						';
				}
				if ( ! empty( $this->points[$i]->title ) ) {
					$js_markers_v3 .= '
						var tooltip_' . $map_id .'_' . $i . ' = new Tooltip(marker_' . $map_id .'_' . $i . ', \'' . esc_js( $this->points[$i]->title ) . '\');
						google.maps.event.addListener(marker_' . $map_id .'_' . $i . ', "mouseover", function() {
							tooltip_' . $map_id .'_' . $i . '.show();
						});
						google.maps.event.addListener(marker_' . $map_id .'_' . $i . ', "mouseout", function() {
							tooltip_' . $map_id .'_' . $i . '.hide();
						});
						';
				}
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
						$polyline->add_coord( $this->points[$i]->coord );
					}
					// Coords
					$coords = array();
					foreach ( $polyline->coords as $coord ) {
						$coords[] = 'new GLatLng(' . $coord->get_delimited() . ')';
					}
					// Options
					$options = array();
					if ( $polyline->geodesic ) {
						$options[] = 'geodesic:true';
					}
					$js_polyline .= 'map_' . $map_id . '.addOverlay(new GPolyline([' . implode( ',', $coords ) . '],"' . $polyline->color . '",' . $polyline->thickness . ',' . $polyline->opacity . ',{' . implode( ',', $options ) . '}));';
					// v3
					$polyline_js_3_coords = array();
					foreach ( $polyline->coords as $c ) {
						$polyline_js_3_coords[] = 'new google.maps.LatLng(' . $c->get_delimited() . ')';
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
		$js_center_v3 = 'var center = new google.maps.LatLng(' . $this->mapcentre->get_delimited() . ');';
		if ( count( $this->points ) > 1 ) {
			$js_zoom .= 'map_' . $map_id . '.setCenter(bounds.getCenter(), map_' . $map_id . '.getBoundsZoomLevel(bounds));';
			$js_center_v3 = 'var center = bounds.getCenter();';
		}
		if ( count( $this->points ) == 1 ) {
			if ( $this->mapcentre->is_valid_coord() ) {
				$js_zoom .= 'map_' . $map_id . '.setCenter(new GLatLng(' . $this->mapcentre->get_delimited() . '));';
			}
		}
		
		// Controls
		$js_controls = '';
		if ( $this->show_map_scale )
			$js_controls .= 'map_' . $map_id . '.addControl(new GScaleControl());';
		if ( $this->show_map_overview )
			$js_controls .= 'map_' . $map_id . '.addControl(new GOverviewMapControl());';
		
		// Map Javascript
		if ( 'googlemapsv3' == $wpgeo->get_api_string() ) {
			$js = '
				if (document.getElementById("' . $div . '")) {
					var bounds = new google.maps.LatLngBounds();
					
					var mapOptions = {
						center    : new google.maps.LatLng(' . $this->points[0]->coord->get_delimited() . '),
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
					var center = new GLatLng(' . $this->points[0]->coord->get_delimited() . ');
					map_' . $map_id . '.setCenter(center, ' . $this->zoom . ');
					
					' . $js_maptypes . '
					map_' . $map_id . '.setMapType(' . $this->maptype . ');
					map_' . $map_id . '.addControl(new GMapTypeControl());
					';
			if ( $this->mapcontrol != "" ) {
				$js .= 'map_' . $map_id . '.addControl(new ' . $this->mapcontrol . '());';
			}
			$js .= '
					var center_' . $map_id .' = new GLatLng(' . $this->points[0]->coord->get_delimited() . ');
					
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
		if ( count( $this->points ) >= $n + 1 )
			return $this->points[$n];
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
	var $args  = null;
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
		$this->args  = $args;
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
			$this->latitude  = $this->sanitize_latlng( $this->latitude );
			$this->longitude = $this->sanitize_latlng( $this->longitude );
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
	 * Sanitize Lat/Lng
	 * Ensures the latitude or longitude is a floating number and that the decimal
	 * point is a full stop rather than a comma created by floatval() in some locales.
	 *
	 * @param number $n Latitude or Longitude.
	 * @return number
	 */
	function sanitize_latlng( $n ) {
		$n = floatval( $n );
		if ( defined( 'DECIMAL_POINT' ) ) {
			$pt = nl_langinfo( DECIMAL_POINT );
			$n = str_replace( $pt, '.', $n );
		}
		return $n;
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
