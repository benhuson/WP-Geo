<?php



/**
 * @package    WP Geo
 * @subpackage Includes > Feeds Class
 * @author     Ben Huson <ben@thewhiteroom.net>
 */



class WPGeo_Feeds
{
	
	
	
	/**
	 * @method       Constructor
	 * @description  Initialise the class.
	 */
	
	function WPGeo_Feeds() {
	
		add_action( 'init', array( $this, 'init' ) );
		
	}
	
	
	
	/**
	 * @method       Init.
	 * @description  Initialise WP Geo feeds.
	 */
	
	function init() {
	
		// Add GeoRSS Feed Type
		add_feed( 'georss', array( 'WPGeo_Feeds', 'add_feed_georss' ) );
		add_filter( 'feed_content_type', array( $this, 'feed_content_type' ), 100 );
		add_filter( 'post_limits', array( $this, 'post_limits' ) );
		
		$this->add_feed_hooks();
		
	}
	
	
	
	/**
	 * @method       Post Limits
	 * @description  Adjusts the post limits on feeds.
	 */
	
	function post_limits( $limits ) {
	
		global $wp_query;
		
		// If GeoRSS feed, return all...
		if ( is_feed() && $wp_query->get('feed') == 'georss' ) {
			return '';
		}
		
		return $limits;
		
	}
	
	
	
	/**
	 * @method       Feed content type
	 * @description  Initialise WP Geo feeds.
	 */
	
	function feed_content_type( $type ) {
		
		if ( $type == 'georss' ) {
			$type = 'application/rss+xml';
		}
		
		return $type;
		
	}
	
	
	
	/**
	 * @method       Add GeoRSS Feed
	 * @description  Adds permalink rewrite GeoRSS feed.
	 */
	
	function add_feed_georss() {
		
		load_template( ABSPATH . 'wp-includes/feed-rss2.php' );
	
	}
	
	
	
	/**
	 * @method       Add Feed Hooks
	 * @description  Adds feed hooks to output GeoRSS info.
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
	 * @method       GeoRSS Namespace
	 * @description  Adds the geo RSS namespace to the feed.
	 */
	
	function georss_namespace() {
	
		global $wpgeo;
		
		if ( $wpgeo->show_maps() ) {
			echo 'xmlns:georss="http://www.georss.org/georss" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:ymaps="http://api.maps.yahoo.com/Maps/V2/AnnotatedMaps.xsd"';
 		}
	
	}
	
	

	/**
	 * @method       GeoRSS Item
	 * @description  Adds geo RSS nodes to the feed item.
	 */
	
	function georss_item() {
	
		global $wpgeo;
		
		if ( $wpgeo->show_maps() ) {
		
			global $post;
			
			// Get the post
			$id = $post->ID;		
		
			// Get latitude and longitude
			$latitude  = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
			$longitude = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
			
			// Need a map?
			if ( is_numeric($latitude) && is_numeric($longitude) ) {
				echo '<georss:point>' . $latitude . ' ' . $longitude . '</georss:point>';
				echo '<geo:lat>' . $latitude . '</geo:lat>';
				echo '<geo:long>' . $longitude . '</geo:long>';
			}
			
		}
		
	}
	
	
	
}



?>