<?php



/**
 * @package    WP Geo
 * @subpackage Includes > Template
 * @author     Ben Huson <ben@thewhiteroom.net>
 */



/**
 * @method       WP Geo Latitude
 * @description  Outputs the post latitude.
 * @param        $post_id = Post ID (optional)
 */

function wpgeo_latitude( $post_id = null ) {

	echo get_wpgeo_latitude( $post_id );
	
}



/**
 * @method       WP Geo Longitude
 * @description  Outputs the post longitude.
 * @param        $post_id = Post ID (optional)
 */

function wpgeo_longitude( $post_id = null ) {

	echo get_wpgeo_longitude( $post_id );
	
}



/**
 * @method       WP Geo Title
 * @description  Outputs the post title.
 * @param        $post_id = Post ID (optional)
 * @param        $default_to_post_title = Default to post title if point title empty (optional)
 */

function wpgeo_title( $post_id = null, $default_to_post_title = true ) {

	echo get_wpgeo_title( $post_id, $default_to_post_title );
	
}



/**
 * @method       Get WP Geo Latitude
 * @description  Gets the post latitude.
 * @param        $post_id = Post ID (optional)
 * @return       (float) Latitude
 */

function get_wpgeo_latitude( $post_id = null ) {

	global $post;
	
	$id = absint($post_id) > 0 ? absint($post_id) : $post->ID;
	
	if ( absint($id) > 0 ) {
		return get_post_meta( absint($id), WPGEO_LATITUDE_META, true );
	}
	
	return null;
	
}



/**
 * @method       Get WP Geo Longitude
 * @description  Gets the post longitude.
 * @param        $post_id = Post ID (optional)
 * @return       (float) Longitude
 */

function get_wpgeo_longitude( $post_id = null ) {
	
	global $post;
	
	$id = absint($post_id) > 0 ? absint($post_id) : $post->ID;
	
	if ( absint($id) > 0 ) {
		return get_post_meta( absint($id), WPGEO_LONGITUDE_META, true );
	}
	
	return null;
	
}



/**
 * @method       Get WP Geo Title
 * @description  Gets the post title.
 * @param        $post_id = Post ID (optional)
 * @param        $default_to_post_title = Default to post title if point title empty (optional)
 * @return       (string) Title
 */

function get_wpgeo_title( $post_id = null, $default_to_post_title = true ) {
	
	global $post;
	
	$id = absint( $post_id ) > 0 ? absint( $post_id ) : $post->ID;
	
	if ( absint( $id ) > 0 ) {
		$title = get_post_meta( $id, WPGEO_TITLE_META, true );
		if ( empty( $title ) && $default_to_post_title ) {
			$p = &get_post( $id );
			$title = isset( $p->post_title ) ? $p->post_title : '';
		}
		$title = apply_filters( 'wpgeo_point_title', $title, $id );
		return $title;
	}
	
	return null;
	
}



/**
 * @method       WP Geo Map Link
 * @description  Gets a link to an external map.
 * @param        $args = Array of arguments (optional)
 * @return       (string) Map URL
 */

function wpgeo_map_link( $args = null ) {
	
	global $post;
	
	// Validate Args
	$r = wp_parse_args( $args, array(
		'post_id'   => $post->ID,
		'latitude'  => null,
		'longitude' => null,
		'zoom'      => 5,
		'echo'      => 1
	) );
	$r['post_id']   = absint( $r['post_id'] );
	$r['latitude']  = (float) $r['latitude'];
	$r['longitude'] = (float) $r['longitude'];
	$r['zoom']      = absint( $r['zoom'] );
	$r['echo']      = absint( $r['echo'] );
	
	// If a post is specified override lat/lng...
	if ( !$r['latitude'] && !$r['longitude'] ) {
		$r['latitude']  = get_wpgeo_latitude( $r['post_id'] );
		$r['longitude'] = get_wpgeo_longitude( $r['post_id'] );
	}
	
	// If lat/lng...
	$url = '';
	if ( $r['latitude'] && $r['longitude'] ) {
		$url = apply_filters( 'wpgeo_map_link', '', $r );
	}
	
	// Output
	if ( $r['echo'] == 0 ) {
		return $url;
	} else {
		echo $url;
	}
	
}



/**
 * @method       WP Geo Post Map
 * @description  Outputs the HTML for a post map.
 * @param        $post_id = Post ID (optional)
 */

function wpgeo_post_map( $post_id = null ) {

	echo get_wpgeo_post_map( $post_id );
	
}



/**
 * @method       Get WP Geo Post Map
 * @description  Gets the HTML for a post map.
 * @param        $post_id = Post ID (optional)
 * @return       (string) HTML
 */

function get_wpgeo_post_map( $post_id = null ) {

	global $post, $wpgeo;
	
	$id = absint($post_id) > 0 ? absint($post_id) : $post->ID;
	$wp_geo_options = get_option( 'wp_geo_options' );
	
	$show_post_map = apply_filters( 'wpgeo_show_post_map', $wp_geo_options['show_post_map'], $id );
	
	$latitude  = get_post_meta( $id, WPGEO_LATITUDE_META, true );
	$longitude = get_post_meta( $id, WPGEO_LONGITUDE_META, true );
	if ( !is_numeric( $latitude ) || !is_numeric( $longitude ) )
		return '';
	
	if ( $id > 0 && !is_feed() ) {
		if ( $wpgeo->show_maps() && $show_post_map != 'TOP' && $show_post_map != 'BOTTOM' && $wpgeo->checkGoogleAPIKey() ) {
			return apply_filters( 'wpgeo_map', '', array(
				'id'      => 'wp_geo_map_' . $id,
				'classes' => array( 'wpgeo_map' ),
				'width'   => $wp_geo_options['default_map_width'],
				'height'  => $wp_geo_options['default_map_height']
			) );
		}
	}
	
	return '';
	
}



/**
 * @method  Get WP Geo Map
 */

function get_wpgeo_map( $query, $options = null ) {
	
	global $wpgeo_map_id;
	
	$wpgeo_map_id++;
	
	$id = 'wpgeo_map_id_' . $wpgeo_map_id;
	
	$wp_geo_options = get_option('wp_geo_options');
	
	$defaults = array(
		'width'           => $wp_geo_options['default_map_width'],
		'height'          => $wp_geo_options['default_map_height'],
		'type'            => $wp_geo_options['google_map_type'],
		'polylines'       => $wp_geo_options['show_polylines'],
		'polyline_colour' => $wp_geo_options['polyline_colour'],
		'align'           => 'none',
		'numberposts'     => -1,
		'post_type'       => 'post',
		'post_status'     => 'publish',
		'orderby'         => 'post_date',
		'order'           => 'DESC',
		'markers'         => 'large',
		'offset'          => 0,
		'category'        => null,
		'include'         => null,
		'exclude'         => null,
		'meta_key'        => null,
		'meta_value'      => null,
		'post_mime_type'  => null,
		'post_parent'     => null
	);
	
	// Validate Args
	$r = wp_parse_args( $query, $defaults );
	
	$posts = get_posts( $r );
	
	$output = apply_filters( 'wpgeo_map', '', array(
		'id'      => $id,
		'classes' => array( 'wpgeo_map' ),
		'styles'  => array( 'float:' . $r['align'] ),
		'width'   => $r['width'],
		'height'  => $r['height']
	) );
	$output .= '
		<script type="text/javascript">
		<!--
		jQuery(window).load( function() {
			if ( GBrowserIsCompatible() ) {
				var bounds = new GLatLngBounds();
				map = new GMap2(document.getElementById("' . $id . '"));
				' . WPGeo_API_GMap2::render_map_control( 'map', 'GLargeMapControl3D' ) . '
				map.setMapType(' . $r['type'] . ');
				';
	if ( $posts ) :
		$polyline = new WPGeo_Polyline( array(
			'color' => $r['polyline_colour']
		) );
		foreach ( $posts as $post ) {
			$latitude = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
			$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
			if ( is_numeric($latitude) && is_numeric($longitude) ) {
				$marker = get_post_meta($post->ID, WPGEO_MARKER_META, true);
				if ( empty( $marker ) )
					$marker = $r['markers'];
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $marker, $post, 'wpgeo_map' );
				$polyline->add_coord( $latitude, $longitude );
				$output .= '
					var center = new GLatLng(' . $latitude . ',' . $longitude . ');
					var marker = new wpgeo_createMarker2(map, center, ' . $icon . ', \'' . esc_js( $post->post_title ) . '\', \'' . get_permalink($post->ID) . '\');
					bounds.extend(center);
					';
			}
		}
		if ( $r['polylines'] == 'Y' ) {
			$output .= WPGeo_API_GMap2::render_map_overlay( 'map', WPGeo_API_GMap2::render_polyline( $polyline ) );
		}
		$output .= '
			zoom = map.getBoundsZoomLevel(bounds);
			map.setCenter(bounds.getCenter(), zoom);
			';
	else : 
		$output .= '
				map.setCenter(new GLatLng(' . $wp_geo_options['default_map_latitude'] . ', ' . $wp_geo_options['default_map_longitude'] . '), ' . $wp_geo_options['default_map_zoom'] . ');';
	endif;
	$output .= '
			}
		} );
		-->
		</script>
		';
	
	return $output;
	
}



/**
 * @method  WP Geo Map
 */

function wpgeo_map( $query, $options = null ) {

	echo get_wpgeo_map( $query, $options );
	
}



/**
 * @method       WP Geo Post Static Map
 * @description  Outputs the HTML for a static post map.
 * @param        $post_id = Post ID (optional)
 * @param        $query = Optional parameters
 */

function wpgeo_post_static_map( $post_id = null, $query = null ) {

	echo get_wpgeo_post_static_map( $post_id, $query );
	
}



/**
 * @method       Get WP Geo Post Static Map
 * @description  Gets the HTML for a static post map.
 * @param        $post_id = Post ID (optional)
 * @param        $query = Optional parameters
 * @return       (string) HTML
 */

function get_wpgeo_post_static_map( $post_id = null, $query = null ) {

	global $post, $wpgeo;
	
	$id = absint($post_id) > 0 ? absint($post_id) : $post->ID;

	$latitude  = get_post_meta( $id, WPGEO_LATITUDE_META, true );
	$longitude = get_post_meta( $id, WPGEO_LONGITUDE_META, true );

	if ( !is_numeric( $latitude ) || !is_numeric( $longitude ) )
		return '';

	if ( !$id || is_feed() ) {
		return '';
	}

	if ( !$wpgeo->show_maps() || !$wpgeo->checkGoogleAPIKey() ) {
		return '';
	}

	// fetch wp geo options & post settings
	$wp_geo_options = get_option( 'wp_geo_options' );
	$settings  = get_post_meta( $id, WPGEO_MAP_SETTINGS_META, true );
	
	// options
	$defaults = array(
		'width'   => trim( $wp_geo_options['default_map_width'], 'px' ),
		'height'  => trim( $wp_geo_options['default_map_height'], 'px' ),
		'maptype' => $wp_geo_options['google_map_type'],
		'zoom'    => $wp_geo_options['default_map_zoom'],
	);
	$options = wp_parse_args( $query, $defaults );
	
	// Can't do percentage sizes to abort
	if ( strpos( $options['width'], '%' ) !== false || strpos( $options['height'], '%' ) !== false ) {
		return '';
	}

	// translate WP-geo maptypes to static map type url param
	$types = array(
		'G_NORMAL_MAP' => 'roadmap',
		'G_SATELLITE_MAP' => 'satellite',
		'G_PHYSICAL_MAP' => 'terrain',
		'G_HYBRID_MAP' => 'hybrid'
	);	

	// default: center on location marker
	$centerLatitude = $latitude;
	$centerLongitude = $longitude;

	// custom map settings?
	if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
		$options['zoom'] = $settings['zoom'];
	}
	if ( !empty($settings['type']) ) {
		$options['maptype'] = $settings['type'];
	}
	if ( !empty($settings['centre']) ) {
		list($centerLatitude, $centerLongitude) = explode( ',', $settings['centre'] );
	}

	$url = 'http://maps.googleapis.com/maps/api/staticmap?';
	$url .= 'center=' . $centerLatitude . ',' . $centerLongitude;
	$url .= '&zoom=' . $options['zoom'];
	$url .= '&size=' . $options['width'] . 'x' . $options['height'];
	$url .= '&maptype=' . $types[$options['maptype']];
	$url .= '&markers=color:red%7C' . $latitude . ',' . $longitude;
	$url .= '&sensor=false';
	
	return '<img id="wp_geo_static_map_' . $id . '" src="' . $url . '" class="wp_geo_static_map" />';
}

/**
 * Add widget map
 */
function wpgeo_add_widget_map( $args = null ) {
	global $wpgeo, $post;
	$wp_geo_options = get_option( 'wp_geo_options' );
	$current_post = $post->ID;
	
	$html_js = '';
	$markers_js = '';
	$polyline_js = '';
	
	$args = wp_parse_args( $args, array(
		'width'         => '100%',
		'height'        => 150,
		'maptype'       => empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'],
		'showpolylines' => false,
		'zoom'          => $wp_geo_options['default_map_zoom'],
		'id'            => 'wpgeo_widget_map',
		'posts'         => null
	) );
	if ( !$args['posts'] )
		return $html_js;
	
	// If Google API Key...
	if ( $wpgeo->checkGoogleAPIKey() ) {
		
		// Find the coordinates for the posts
		$coords = array();
		for ( $i = 0; $i < count( $args['posts'] ); $i++ ) {
			$post 		= $args['posts'][$i];
			$latitude 	= get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
			$longitude 	= get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
			$post_id 	= get_post( $post->ID );
			$title 	    = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
			if ( empty( $title ) )
				$title = $post->post_title;
			if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
				array_push( $coords, array(
					'id' 		=> $post->ID,
					'latitude' 	=> $latitude,
					'longitude' => $longitude,
					'title' 	=> $title,
					'post'		=> $post
				) );
			}
		}
		
		// Only show map widget if there are coords to show
		if ( count( $coords ) > 0 ) {
			
			// Polylines
			if ( $args['showpolylines'] ) {
				$polyline = new WPGeo_Polyline( array(
					'color' => $wp_geo_options['polyline_colour']
				) );
				for ( $i = 0; $i < count( $coords ); $i++ ) {
					$polyline->add_coord( $coords[$i]['latitude'], $coords[$i]['longitude'] );
				}
				$polyline_js = WPGeo_API_GMap2::render_map_overlay( 'map', WPGeo_API_GMap2::render_polyline( $polyline ) );
			}
			
			// Markers
			for ( $i = 0; $i < count( $coords ); $i++ ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', 'small', $coords[$i]['post'], 'widget' );
				$markers_js .= 'marker' . $i . ' = wpgeo_createMarker(new GLatLng(' . $coords[$i]['latitude'] . ', ' . $coords[$i]['longitude'] . '), ' . $icon . ', "' . addslashes( __( $coords[$i]['title'] ) ) . '", "' . get_permalink( $coords[$i]['id'] ) . '");' . "\n";
			}
			
			$wpgeo->includeGoogleMapsJavaScriptAPI();
			$small_marker = $wpgeo->markers->get_marker_by_id( 'small' );
			
			$html_js .= '
				<script type="text/javascript">
				//<![CDATA[
				
				/**
				 * Widget Map (' . $args['id'] . ')
				 */
				
				// Define variables
				var map = "";
				var bounds = "";
				
				// Add events to load the map
				GEvent.addDomListener(window, "load", createMapWidget);
				GEvent.addDomListener(window, "unload", GUnload);
				
				// Create the map
				function createMapWidget() {
					if (GBrowserIsCompatible()) {
						map = new GMap2(document.getElementById("' . $args['id'] . '"));
						' . WPGeo_API_GMap2::render_map_control( 'map', 'GSmallZoomControl3D' ) . '
						map.setCenter(new GLatLng(0, 0), 0);
						map.setMapType(' . $args['maptype'] . ');
						bounds = new GLatLngBounds();
						
						// Add the markers	
						'.	$markers_js .'
						
						// Draw the polygonal lines between points
						' . $polyline_js . '
						
						// Center the map to show all markers
						var center = bounds.getCenter();
						var zoom = map.getBoundsZoomLevel(bounds)
						if (zoom > ' . $args['zoom'] . ') {
							zoom = ' . $args['zoom'] . ';
						}
						map.setCenter(center, zoom);
					}
				}
				
				//]]>
				</script>';
			
			$html_js .= apply_filters( 'wpgeo_map', '', array(
				'id'      => $args['id'],
				'classes' => array( 'wp_geo_map' ),
				'width'   => $args['width'],
				'height'  => $args['height']
			) );
		}
		
		return $html_js;
	}
}

?>