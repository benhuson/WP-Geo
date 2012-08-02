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
	var $points;
	var $zoom = 5;
	var $maptype = 'G_NORMAL_MAP';
	var $maptypes;
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
		$this->id = $id;
		$this->maptypes = array();
		$this->points = array();
	}
	
	/**
	 * Render Map JavaScript
	 * Outputs the javascript to display maps.
	 *
	 * @param string $map_id The map ID.
	 * @return string JavaScript.
	 */
	function renderMapJS( $map_id = false ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// ID of div for map output
		$map_id = $map_id ? $map_id : $this->id;
		$div = 'wp_geo_map_' . $map_id;
		
		// Map Types
		$maptypes = $this->maptypes;
		$maptypes[] = $this->maptype;
		$maptypes = array_unique( $maptypes );
		$js_maptypes = WPGeo_API_GMap2::render_map_types( 'map_' . $map_id, $maptypes );
		
		// Markers
		$js_markers = '';
		if ( count( $this->points ) > 0 ) {
			for ( $i = 0; $i < count( $this->points ); $i++ ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $this->points[$i]['icon'], $this->id, 'wpgeo_map' );
				$js_markers .= 'var marker_' . $map_id .'_' . $i . ' = new wpgeo_createMarker2(map_' . $map_id . ', new GLatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '), ' . $icon . ', \'' . addslashes( __( $this->points[$i]['title'] ) ) . '\', \'' . $this->points[$i]['link'] . '\');' . "\n";
				$js_markers .= 'bounds.extend(new GLatLng(' . $this->points[$i]['latitude'] . ', ' . $this->points[$i]['longitude'] . '));';
			}
		}
		
		// Show Polyline
		$js_polyline = '';
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
				}
			}
		}
		
		// Zoom
		$js_zoom = '';
		if ( count( $this->points ) > 1 ) {
			$js_zoom .= 'map_' . $map_id . '.setCenter(bounds.getCenter(), map_' . $map_id . '.getBoundsZoomLevel(bounds));';
		}
		if ( count( $this->points ) == 1 ) {
			if ( wpgeo_is_valid_geo_coord( $this->mapcentre['latitude'], $this->mapcentre['longitude'] ) ) {
				$js_zoom .= 'map_' . $map_id . '.setCenter(new GLatLng(' . $this->mapcentre['latitude'] . ', ' . $this->mapcentre['longitude'] . '));';
			}
		}
		
		// Controls
		$js_controls = '';
		if ( $this->show_map_scale )
			$js_controls .= WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, 'GScaleControl' );
		if ( $this->show_map_overview )
			$js_controls .= WPGeo_API_GMap2::render_map_control( 'map_' . $map_id, 'GOverviewMapControl' );
		
		// Map Javascript
		$js = '
			if (document.getElementById("' . $div . '"))
			{
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
		
		return $js;
	}
	
	/**
	 * Get the HTML for a map.
	 *
	 * @return string HTML.
	 */
	function get_map_html() {
		$wp_geo_options = get_option('wp_geo_options');
		
		// Extract args
		$allowed_args = array(
			'width'  => $wp_geo_options['default_map_width'],
			'height' => $wp_geo_options['default_map_height']
		);
		$args = wp_parse_args( $args, $allowed_args );
		
		$map_width  = wpgeo_css_dimension( $allowed_args['default_map_width'] );
		$map_height = wpgeo_css_dimension( $allowed_args['default_map_height'] );
		
		return '<div class="wpgeo_map" id="wpgeo_map_' . $this->id . '" style="width:' . $map_width . '; height:' . $map_height . ';"></div>';
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
	 * Add a point (marker) to this map.
	 *
	 * @param float $lat Latitude.
	 * @param float $long Longitude.
	 * @param string $icon (optional) Icon type.
	 * @param string $title (optional) Display title.
	 * @param string $link (optional) URL to link to when point is clicked.
	 */
	function addPoint( $lat, $long, $icon = 'large', $title = '', $link = '' ) {
		$this->points[] = array(
			'latitude'  => $lat, 
			'longitude' => $long,
			'icon'      => $icon,
			'title'     => $title,
			'link'      => $link,
		);
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
	function setMapType( $maptype = 'G_NORMAL_MAP' ) {
		if ( $this->is_valid_map_type( $maptype ) ) {
			$this->maptype = $maptype;
		}
	}
	
	/**
	 * Set the centre point of the map
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 */
	function setMapCentre( $latitude, $longitude ) {
		$this->mapcentre = array(
			'latitude'  => $latitude,
			'longitude' => $longitude
		);
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
	function setMapZoom( $zoom = 5 ) {
		$this->zoom = absint( $zoom );
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
		$defaults = array(
			'coords'    => $this->coords,
			'geodesic'  => $this->geodesic,
			'color'     => $this->color,
			'thickness' => $this->thickness,
			'opacity'   => $this->opacity
		);
		$args = wp_parse_args( $args, $defaults );
		$this->coords    = $args['coords'];
		$this->geodesic  = $args['geodesic'];
		$this->color     = $args['color'];
		$this->thickness = $args['thickness'];
		$this->opacity   = $args['opacity'];
	}
	
	/**
	 * Add Coord
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 */
	function add_coord( $latitude, $longitude ) {
		$this->coords[] = new WPGeo_Coord( $latitude, $longitude );
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
	}
	
}

?>