<?php

/**
 * WP Geo Query API.
 * The WP Geo query API can be used to retrieve geo location
 * information from WordPress.
 */

add_action( 'the_post', 'wpgeo_setup_postdata' );

/**
 * WP Geo Query Class.
 * Creates geo queries and returns results.
 */
class WPGeo_Query {
	
	/**
	 * WP Query
	 */
	var $wp_query; // object An instance of WP_Query.
	
	/**
	 * Constructor
	 * Sets up the WordPress query, if parameter is not empty.
	 *
	 * @param string $query URL query string.
	 */
	function WPGeo_Query( $query = '' ) {
		$this->wp_query = new WP_Query( $query );
	}
	
}

/**
 * Setup Post Data.
 * Setup additional global variables for geo data
 * while iterating through the loop.
 *
 * @param object $post Post data.
 */
function wpgeo_setup_postdata( $post ) {
	global $wpgeo_latitude, $wpgeo_longitude;
	
	$coord = new WPGeo_Coord( get_post_meta( $post->ID, WPGEO_LATITUDE_META, true ), get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true ) );
	
	if ( $coord->is_valid_coord() ) {
		$wpgeo_latitude  = $coord->latitude();
		$wpgeo_longitude = $coord->longitude();
	}
}

?>