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
	
	$defaults = array(
		'post_id'   => $post->ID,
		'latitude'  => null,
		'longitude' => null,
		'zoom'      => 5,
		'echo'      => 1
	);
	
	// Validate Args
	$r = wp_parse_args( $args, $defaults );
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
	
		$q = 'q=' . $r['latitude'] . ',' . $r['longitude'];
		$z = $r['zoom'] ? '&z=' . $r['zoom'] : '';
		
		$url = 'http://maps.google.co.uk/maps?' . $q . $z;
		$url = apply_filters( 'wpgeo_map_link', $url, $r );
		
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
	
	if ( $id > 0 && !is_feed() ) {
		if ( $wpgeo->show_maps() && $show_post_map != 'TOP' && $show_post_map != 'BOTTOM' && $wpgeo->checkGoogleAPIKey() ) {
			
			$map_width = $wp_geo_options['default_map_width'];
			$map_height = $wp_geo_options['default_map_height'];
		
			if ( is_numeric( $map_width ) ) {
				$map_width = $map_width . 'px';
			}
		
			if ( is_numeric( $map_height ) ) {
				$map_height = $map_height . 'px';
			}
			
			return '<div class="wp_geo_map" id="wp_geo_map_' . $id . '" style="width:' . $map_width . '; height:' . $map_height . ';"></div>';
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
		'post_type'       => null,
		'post_status'     => 'publish',
		'orderby'         => 'post_date',
		'order'           => 'DESC',
		'markers'         => 'large'
	);
	
	// Validate Args
	$r = wp_parse_args( $query, $defaults );
	if ( is_numeric( $r['width'] ) ) {
		$r['width'] .= 'px';
	}
	if ( is_numeric( $r['height'] ) ) {
		$r['height'] .= 'px';
	}
	
	$posts = get_posts( $query );
	
	$output = '
		<div id="' . $id . '" class="wpgeo_map" style="width:' . $r['width'] . '; height:' . $r['height'] . ';float:' . $r['align'] . '"></div>
		<script type="text/javascript">
		<!--
		jQuery(window).load( function() {
			if ( GBrowserIsCompatible() ) {
				var bounds = new GLatLngBounds();
				map = new GMap2(document.getElementById("' . $id . '"));
				map.addControl(new GLargeMapControl3D());
				map.setMapType(' . $r['type'] . ');
				';
	if ( $posts ) :
		foreach ( $posts as $post ) {
			$latitude = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
			$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
			if ( is_numeric($latitude) && is_numeric($longitude) ) {
				$marker = get_post_meta($post->ID, WPGEO_MARKER_META, true);
				if ( empty( $marker ) )
					$marker = $r['markers'];
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $marker, $post, 'wpgeo_map' );
				$polyline_coords_js .= 'new GLatLng(' . $latitude . ', ' . $longitude . '),';
				$output .= '
					var center = new GLatLng(' . $latitude . ',' . $longitude . ');
					var marker = new wpgeo_createMarker2(map, center, ' . $icon . ', \'' . esc_js( $post->post_title ) . '\', \'' . get_permalink($post->ID) . '\');
					bounds.extend(center);
					';
			}
		}
		if ( $r['polylines'] == 'Y' ) {
			$output .= 'map.addOverlay(wpgeo_createPolyline([' . $polyline_coords_js . '], "' . $r['polyline_colour'] . '", 2, 0.50));';
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



?>