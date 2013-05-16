<?php

/**
 * WP Geo Query Class.
 * Creates and manipulates queries.
 */
class WPGeo_Query {

	/**
	 * Constructor
	 */
	function WPGeo_Query() {
		add_filter( 'post_limits', array( $this, 'post_limits' ) );
		add_filter( 'posts_join', array( $this, 'posts_join' ) );
		add_filter( 'posts_where', array( $this, 'posts_where' ) );
	}

	/**
	 * Get Custom Field Posts Join
	 * Join custom fields on to results.
	 *
	 * @todo Use $wpdb->prepare();
	 *
	 * @param   string  $join JOIN statement.
	 * @return  string  SQL.
	 */
	function get_custom_field_posts_join( $join ) {
		global $wpdb, $customFields;
		return $join . " JOIN $wpdb->postmeta postmeta ON (postmeta.post_id = $wpdb->posts.ID and postmeta.meta_key in ($customFields))";
	}

	/**
	 * Get Custom Field Posts Group
	 * Group by post id.
	 *
	 * @param   string  $group  GROUP BY statement.
	 * @return  string          SQL.
	 */
	function get_custom_field_posts_group( $group ) {
		global $wpdb;
		$group .= " $wpdb->posts.ID ";
		return $group;
	}

	/**
	 * Post Limits
	 * Removes limit on WP Geo feed to show all posts.
	 *
	 * @param   int  $limit  Current limit.
	 * @return  int          New Limit.
	 */
	function post_limits( $limit ) {
		global $wpgeo;

		if ( $wpgeo->is_wpgeo_feed() ) {
			if ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) ) {
				return 'LIMIT 0, ' . $_GET['limit'];
			}
		}
		return $limit;
	}

	/**
	 * Posts Join
	 * Joins the post meta tables onto the results of the posts table.
	 *
	 * @param   string  $join  Current JOIN statement.
	 * @return  string         Updated JOIN string.
	 */
	function posts_join( $join ) {
		global $wpdb, $wpgeo;

		if ( $wpgeo->is_wpgeo_feed() ) {
			$join .= " LEFT JOIN $wpdb->postmeta ON (" . $wpdb->posts . ".ID = $wpdb->postmeta.post_id)";
		}
		return $join;
	}

	/**
	 * Posts Where
	 * Adds extra WHERE clause to the posts results to only include posts with longitude and latitude.
	 *
	 * @param   string  $where  Current WHERE statement.
	 * @return  string          Updated WHERE string.
	 */
	function posts_where( $where ) {
		global $wpdb, $wpgeo;

		if ( $wpgeo->is_wpgeo_feed() ) {
			$where .= " AND ($wpdb->postmeta.meta_key = '" . WPGEO_LATITUDE_META . "' OR $wpdb->postmeta.meta_key = '" . WPGEO_LONGITUDE_META . "')";
		}
		return $where;
	}

}
