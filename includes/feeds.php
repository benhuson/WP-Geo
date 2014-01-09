<?php

/**
 * WP Geo Feeds
 */
class WPGeo_Feeds {

	/**
	 * Constructor
	 */
	function WPGeo_Feeds() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialise WP Geo feeds
	 */
	function init() {

		// Add GeoRSS Feed Type
		add_feed( 'georss', array( 'WPGeo_Feeds', 'add_feed_georss' ) );
		add_filter( 'feed_content_type', array( $this, 'feed_content_type' ), 100 );
		add_filter( 'post_limits', array( $this, 'post_limits' ) );

		$this->add_feed_hooks();
	}

	/**
	 * Post Limits
	 * Adjusts the post limits on feeds.
	 *
	 * @param string $limits Limits SQL.
	 * @return string SQL.
	 */
	function post_limits( $limits ) {
		global $wp_query;

		// If GeoRSS feed, return all...
		if ( is_feed() && $wp_query->get( 'feed' ) == 'georss' ) {
			return '';
		}
		return $limits;
	}

	/**
	 * Feed content type
	 *
	 * @param string $type Content mime type.
	 * @return string Content type.
	 */
	function feed_content_type( $type ) {
		if ( $type == 'georss' ) {
			$type = 'application/rss+xml';
		}
		return $type;
	}

	/**
	 * Add GeoRSS Feed
	 *
	 * @todo Does this need to exit after loading template?
	 */
	function add_feed_georss() {
		load_template( ABSPATH . 'wp-includes/feed-rss2.php' );
	}

	/**
	 * Add GeoRSS Feed Hooks
	 */
	function add_feed_hooks() {
		add_action( 'rss2_ns', array( $this, 'georss_namespace' ) );
		add_action( 'atom_ns', array( $this, 'georss_namespace' ) );
		add_action( 'rdf_ns', array( $this, 'georss_namespace' ) );
		add_action( 'rss_item', array( $this, 'georss_item' ) );
		add_action( 'rss2_item', array( $this, 'georss_item' ) );
		add_action( 'atom_entry', array( $this, 'georss_item' ) );
		add_action( 'rdf_item', array( $this, 'georss_item' ) );
	}

	/**
	 * Add the GeoRSS namespace to the feed
	 */
	function georss_namespace() {
		global $wpgeo;

		if ( $wpgeo->show_maps() ) {
			echo 'xmlns:georss="http://www.georss.org/georss" ';
			echo 'xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" ';
			echo 'xmlns:ymaps="http://api.maps.yahoo.com/Maps/V2/AnnotatedMaps.xsd" ';
 		}
	}

	/**
	 * GeoRSS Item
	 * Adds geo RSS nodes to the feed item.
	 */
	function georss_item() {
		global $wpgeo, $post;

		if ( $wpgeo->show_maps() ) {
			$coord = get_wpgeo_post_coord( $post->ID );
			if ( $coord->is_valid_coord() ) {
				echo '<georss:point>' . $coord->get_delimited( ' ' ) . '</georss:point>';
				echo '<geo:lat>' . $coord->latitude() . '</geo:lat>';
				echo '<geo:long>' . $coord->longitude() . '</geo:long>';
			}
		}
	}

}
