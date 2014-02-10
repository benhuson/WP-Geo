<?php

/**
 * WP Geo Latitude
 * Outputs the post latitude.
 *
 * @param int $post_id (optional) Post ID.
 */
function wpgeo_latitude( $post_id = null ) {
	echo get_wpgeo_latitude( $post_id );
}

/**
 * WP Geo Longitude
 * Outputs the post longitude.
 *
 * @param int $post_id (optional) Post ID.
 */
function wpgeo_longitude( $post_id = null ) {
	echo get_wpgeo_longitude( $post_id );
}

/**
 * WP Geo Title
 * Outputs the post title.
 *
 * @param int $post_id (optional) Post ID
 * @param bool $default_to_post_title (optional) Default to post title if point title empty.
 */
function wpgeo_title( $post_id = null, $default_to_post_title = true ) {
	echo get_wpgeo_title( $post_id, $default_to_post_title );
}

/**
 * Get WP Geo Latitude
 * Gets the post latitude.
 *
 * @param int $post_id (optional) Post ID.
 * @return float Latitude.
 */
function get_wpgeo_latitude( $post_id = 0 ) {
	global $post;
	
	$post_id = absint( $post_id );
	$post_id = $post_id > 0 || ! isset( $post ) ? $post_id : $post->ID;
	if ( $post_id > 0 ) {
		return get_post_meta( $post_id, WPGEO_LATITUDE_META, true );
	}
	return null;
}

/**
 * Get WP Geo Longitude
 * Gets the post longitude.
 *
 * @param int $post_id (optional) Post ID.
 * @return float Longitude.
 */
function get_wpgeo_longitude( $post_id = 0 ) {
	global $post;
	
	$post_id = absint( $post_id );
	$post_id = $post_id > 0 || ! isset( $post ) ? $post_id : $post->ID;
	if ( $post_id > 0 ) {
		return get_post_meta( $post_id, WPGEO_LONGITUDE_META, true );
	}
	return null;
}

/**
 * Get WP Geo Post Coord
 * Gets the post coordinates.
 *
 * @param int $post_id (optional) Post ID.
 * @return object WPGeo_Coord.
 */
function get_wpgeo_post_coord( $post_id = 0 ) {
	return new WPGeo_Coord( get_wpgeo_latitude( $post_id ), get_wpgeo_longitude( $post_id ) );
}

/**
 * Get WP Geo Title
 *
 * @param int $post_id (optional) Post ID.
 * @param bool $default_to_post_title (optional) Default to post title if point title empty.
 * @return string Title.
 */
function get_wpgeo_title( $post_id = 0, $default_to_post_title = true ) {
	global $post;
	
	if ( 'object' == gettype( $post_id ) && 'WP_Post' == get_class( $post_id ) ) {
		$post_id = $post_id->ID;
	} else {
		$post_id = absint( $post_id );
		$post_id = $post_id > 0 ? $post_id : $post->ID;
	}
	
	if ( $post_id > 0 ) {
		$title = get_post_meta( $post_id, WPGEO_TITLE_META, true );
		if ( empty( $title ) && $default_to_post_title ) {
			$p = get_post( $post_id );
			$title = isset( $p->post_title ) ? $p->post_title : '';
		}
		$title = apply_filters( 'wpgeo_point_title', $title, $post_id );
		return $title;
	}
	return '';
}

/**
 * WP Geo Map Link
 * Gets a link to an external map.
 *
 * @todo This should probably use API but fallback to Google Maps.
 *
 * @param   array   $args  (optional) Array of arguments.
 * @return  string         Map URL.
 */
function wpgeo_map_link( $args = null ) {
	global $wpgeo, $post;

	// Validate Args
	$r = wp_parse_args( $args, array(
		'post_id'   => $post->ID,
		'latitude'  => null,
		'longitude' => null,
		'zoom'      => null,
		'echo'      => 1
	) );
	$r['post_id'] = absint( $r['post_id'] );
	$r['echo']    = absint( $r['echo'] );

	// Coord
	$coord = new WPGeo_Coord( $r['latitude'], $r['longitude'] );
	if ( ! $coord->is_valid_coord() ) {
		$coord = get_wpgeo_post_coord( $r['post_id'] );
		if ( ! $coord->is_valid_coord() ) {
			return '';
		}
	}

	// Fetch wp geo options & post settings
	$wp_geo_options = get_option( 'wp_geo_options' );
	$settings = WPGeo::get_post_map_settings( $r['post_id'] );

	// Map Options
	if ( is_null( $r['zoom'] ) || ! is_numeric( $r['zoom'] ) ) {
		$r['zoom'] = isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ? $settings['zoom'] : $wp_geo_options['default_map_zoom'];
	}
	$zoom = absint( $r['zoom'] );

	// Map
	$map = new WPGeo_Map();
	$map->set_map_zoom( $zoom );
	$map->add_point( $coord );

	$url = $wpgeo->api->map_url( $map );
	$url = apply_filters( 'wpgeo_map_link', $url, $r );

	// Output
	if ( $r['echo'] == 0 ) {
		return $url;
	}
	echo $url;
}

/**
 * WP Geo Post Map
 * Outputs the HTML for a post map.
 *
 * @param int $post_id (optional) Post ID.
 */
function wpgeo_post_map( $post_id = null ) {
	echo get_wpgeo_post_map( $post_id );
}

/**
 * Get WP Geo Post Map
 * Gets the HTML for a post map.
 *
 * @param int $post_id (optional) Post ID.
 * @return string HTML.
 */
function get_wpgeo_post_map( $post_id = 0, $args = null ) {
	global $post, $wpgeo;
	
	$post_id = absint( $post_id );
	$post_id = $post_id > 0 ? $post_id : $post->ID;
	$this_post = get_post( $post_id );
	$wp_geo_options = get_option( 'wp_geo_options' );
	
	$args = wp_parse_args( $args, array(
		'width'          => $wp_geo_options['default_map_width'],
		'height'         => $wp_geo_options['default_map_height'],
		'maptype'        => empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'],
		'show_polylines' => false,
		'zoom'           => $wp_geo_options['default_map_zoom'],
		'id'             => $post_id,
		'posts'          => array( $this_post ),
		'styles'         => '',
		'content'        => ''
	) );
	
	$show_post_map = apply_filters( 'wpgeo_show_post_map', $wp_geo_options['show_post_map'], $post_id );
	
	$coord = get_wpgeo_post_coord( $post_id );
	if ( ! $coord->is_valid_coord() ) {
		return '';
	}
	
	if ( $post_id > 0 && ! is_feed() ) {
		if ( $wpgeo->show_maps() && $show_post_map != 'TOP' && $show_post_map != 'BOTTOM' && $wpgeo->checkGoogleAPIKey() ) {
			$meta = WPGeo::get_post_map_settings( $post_id );
			$marker_meta = get_post_meta( $post_id, WPGEO_MARKER_META, true );
			$marker_meta = empty( $marker_meta ) ? 'large' : $marker_meta;
			$title_meta = get_post_meta( $post_id, WPGEO_TITLE_META, true );
			$title_meta = empty( $title_meta ) ? get_the_title( $post_id ) : $title_meta;
			if ( is_numeric( $meta['zoom'] ) ) {
				$args['zoom'] = $meta['zoom'];
			}
			if ( ! empty( $meta['type'] ) ) {
				$args['maptype'] = $meta['type'];
			}
			$center_coord = $coord;
			if ( ! empty( $meta['centre'] ) ) {
				$center = explode( ',', $meta['centre'] );
				$center_coord = new WPGeo_Coord( $center[0], $center[1] );
			}
			
			$map = new WPGeo_Map( $post_id );
			if ( $center_coord->is_valid_coord() ) {
				$map->set_map_centre( $center_coord );
			}
			$map->set_map_zoom( $args['zoom'] );
			$map->set_map_type( $args['maptype'] );
			$map->add_point( $coord, array(
				'icon'  => apply_filters( 'wpgeo_marker_icon', $marker_meta, $this_post, 'post' ),
				'title' => $title_meta,
				'link'  => apply_filters( 'wpgeo_marker_link', get_permalink( $this_post ), $this_post ),
				'post'  => $this_post
			) );
			if ( ! empty( $args['width'] ) ) {
				$map->set_width( $args['width'] );
			}
			if ( ! empty( $args['height'] ) ) {
				$map->set_height( $args['height'] );
			}
			
			$map = $wpgeo->maps->add_map( $map );
			return $map->get_map_html( $args );
		}
	}
	return '';
}

/**
 * Create Input Map
 */
function wpgeo_create_input_map( $options = null ) {
	global $wpgeo, $wpgeo_map_id;
	
	$wpgeo_map_id++;
	$id = 'wpgeo_map_id_' . $wpgeo_map_id;
	$wp_geo_options = get_option('wp_geo_options');

	$defaults = array(
		'latitude'        => $wp_geo_options['default_map_latitude'],
		'longitude'       => $wp_geo_options['default_map_longitude'],
		'width'           => $wp_geo_options['default_map_width'],
		'height'          => $wp_geo_options['default_map_height'],
		'type'            => $wp_geo_options['google_map_type'],
		'align'           => 'none',
        'markers'         => 'large'
	);
	
	// Validate Args
	$r = wp_parse_args( $options, $defaults );
	$r['width']  = wpgeo_css_dimension( $r['width'] );
	$r['height'] = wpgeo_css_dimension( $r['height'] );
	
	// Point
	$point = new WPGeo_Coord( $defaults['latitude'], $defaults['longitude'] );
	if ( ! $point->is_valid_coord() ) {
		$point = new WPGeo_Coord( $defaults['default_map_latitude'], $defaults['default_map_longitude'] );
	}
	
	// Map
	$map = new WPGeo_Map( 'id_' . $wpgeo_map_id );
	$map->set_size( $r['width'], $r['height'] );
	$map->set_map_centre( $point );
	$map->set_map_zoom( $wp_geo_options['default_map_zoom'] );
	$map->set_map_type( $r['type'] );
	
	// Points
	$map->add_point( $point, array(
		'icon'  => apply_filters( 'wpgeo_marker_icon', $r['markers'], 0, 'input' )
	) );
	
	$wpgeo->maps->add_map( $map );
	return $map;
}

/**
 * Get WP Geo Map
 *
 * @param array $query Query args.
 * @param array $options Options array.
 * @return string Output.
 */
function get_wpgeo_map( $query, $options = null ) {
	global $wpgeo, $wpgeo_map_id;
	
	$wpgeo_map_id++;
	$id = 'wpgeo_map_id_' . $wpgeo_map_id;
	$wp_geo_options = get_option('wp_geo_options');
	
	$defaults = apply_filters( 'wpgeo_map_default_query_args', array(
		'width'           => $wp_geo_options['default_map_width'],
		'height'          => $wp_geo_options['default_map_height'],
		'type'            => $wp_geo_options['google_map_type'],
		'polylines'       => $wp_geo_options['show_polylines'],
		'polyline_colour' => $wp_geo_options['polyline_colour'],
		'zoom'            => $wp_geo_options['default_map_zoom'],
		'align'           => 'none',
		'numberposts'     => -1,
		'posts_per_page'  => -1,
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
        'post_ids'        => '',
        'post_mime_type'  => null,
        'post_parent'     => null
	) );
	
	// Validate Args
	$r = wp_parse_args( $query, $defaults );
	$r['width']  = wpgeo_css_dimension( $r['width'] );
	$r['height'] = wpgeo_css_dimension( $r['height'] );
	
	if ( $r['posts_per_page'] < $r['numberposts'] ) {
		$r['posts_per_page'] = $r['numberposts'];
	}

	// Set 'post__in' if 'post_ids' set, but don't overwrite.
	if ( ! empty( $r['post_ids'] ) && empty( $r['post__in'] ) ) {
		if ( is_array( $r['post_ids'] ) ) {
			$r['post__in'] = $r['post_ids'];
		} else {
			$r['post__in'] = explode( ',', $r['post_ids'] );
		}
	}

	$posts = get_posts( $r );
	
	// Map
	$map = new WPGeo_Map( 'id_' . $wpgeo_map_id );
	$map->set_size( $r['width'], $r['height'] );
	$map->set_map_centre( new WPGeo_Coord( $wp_geo_options['default_map_latitude'], $wp_geo_options['default_map_longitude'] ) );
	$map->set_map_zoom( $r['zoom'] );
	$map->set_map_type( $r['type'] );
	
	// Points
	if ( $posts ) {
		foreach ( $posts as $post ) {
			$coord = get_wpgeo_post_coord( $post->ID );
			if ( $coord->is_valid_coord() ) {
				$marker = get_post_meta( $post->ID, WPGEO_MARKER_META, true );
				if ( empty( $marker ) ) {
					$marker = $r['markers'];
				}
				$map->add_point( $coord, array(
					'icon'  => apply_filters( 'wpgeo_marker_icon', $marker, $post, 'template' ),
					'title' => get_wpgeo_title( $post->ID ),
					'link'  => apply_filters( 'wpgeo_marker_link', get_permalink( $post ), $post ),
					'post'  => $post
				) );
			}
		}
	}
	
	// Polylines
	if ( count( $map->points ) > 0 ) {
		if ( $r['polylines'] == 'Y' ) {
			$polyline = new WPGeo_Polyline( array(
				'color' => $r['polyline_colour']
			) );
			foreach ( $map->points as $point ) {
				$polyline->add_coord( $point->get_coord() );
			}
			$map->add_polyline( $polyline );
		}
	}
	
	$center_coord = $map->get_map_centre();
	
	$wpgeo->maps->add_map( $map );
	return $map->get_map_html( array( 'styles' => array( 'float' => $r['align'] ) ) );
}

/**
 * WP Geo Map
 *
 * @param array $query Query args.
 * @param array $options Options array.
 * @return string Output.
 */
function wpgeo_map( $query, $options = null ) {
	echo get_wpgeo_map( $query, $options );
}

/**
 * WP Geo Post Static Map
 * Outputs the HTML for a static post map.
 *
 * @param  int    $post_id  (optional) Post ID.
 * @param  array  $query    (optional) Parameters.
 */
function wpgeo_post_static_map( $post_id = 0, $query = null ) {
	echo get_wpgeo_post_static_map( $post_id, $query );
}

/**
 * Get WP Geo Post Static Map
 * Gets the HTML for a static post map.
 *
 * @param   int    $post_id  (optional) Post ID.
 * @param   array  $query    (optional) Parameters.
 * @return  string           HTML.
 */
function get_wpgeo_post_static_map( $post_id = 0, $query = null ) {
	global $post, $wpgeo;

	$post_id = absint( $post_id );
	$post_id = $post_id > 0 ? $post_id : $post->ID;

	// Show Map?
	if ( ! $post_id || is_feed() || ! $wpgeo->show_maps() || ! $wpgeo->checkGoogleAPIKey() ) {
		return '';
	}

	$coord = get_wpgeo_post_coord( $post_id );
	if ( ! $coord->is_valid_coord() ) {
		return '';
	}

	// Fetch wp geo options & post settings
	$wp_geo_options = get_option( 'wp_geo_options' );
	$settings = WPGeo::get_post_map_settings( $post_id );

	// Options
	$options = wp_parse_args( $query, array(
		'width'   => trim( $wp_geo_options['default_map_width'], 'px' ),
		'height'  => trim( $wp_geo_options['default_map_height'], 'px' ),
		'maptype' => $wp_geo_options['google_map_type'],
		'zoom'    => $wp_geo_options['default_map_zoom'],
	) );

	// Can't do percentage sizes so abort
	if ( strpos( $options['width'], '%' ) !== false || strpos( $options['height'], '%' ) !== false ) {
		return '';
	}

	// Map Options
	$zoom = isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ? $settings['zoom'] : $options['zoom'];
	$map_type = ! empty( $settings['type'] ) ? $settings['type'] : $options['maptype'];
	$center_coord = new WPGeo_Coord( $coord->latitude(), $coord->longitude() );
	if ( ! empty( $settings['centre'] ) ) {
		$center = explode( ',', $settings['centre'] );
		$maybe_center_coord = new WPGeo_Coord( $center[0], $center[1] );
		if ( $maybe_center_coord->is_valid_coord() ) {
			$center_coord = $maybe_center_coord;
		}
	}

	// Map
	$map = new WPGeo_Map();
	$map->set_map_centre( $center_coord );
	$map->set_map_zoom( $zoom );
	$map->set_map_type( $map_type );
	$map->set_size( $options['width'], $options['height'] );
	$map->add_point( $coord );

	$url = $wpgeo->api->static_map_url( $map );

	return sprintf( '<img id="wp_geo_static_map_%s" src="%s" class="wp_geo_static_map" />', $post_id, esc_attr( $url ) );
}
