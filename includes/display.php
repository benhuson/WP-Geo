<?php

/**
 * WP Geo Display
 */

class WPGeo_Display {
	
	var $maps;
	var $n = 0;
	
	/**
	 * Constructor
	 */
	function WPGeo_Display() {
		$this->maps = array();
		
		// Hooks
		add_action( 'wp_footer', array( $this, 'wpgeo_footer' ) );
		add_shortcode( 'wpgeo', array( $this, 'shortcode_wpgeo' ) );
	}
	
	/**
	 * Get the ID of this display instance.
	 *
	 * @return int Numeric ID.
	 */
	function get_id() {
		$this->n++;
		return $this->n;
	}
	
	/**
	 * Add map to maps array.
	 *
	 * @param array $args Map configuration.
	 */
	function add_map( $map ) {
		$this->maps[$map->id] = $map;
	}
	
	/**
	 * WP Geo Footer
	 * Outputs the javascript to display the maps.
	 */
	function wpgeo_footer() {
		global $wpgeo;
		
		if ( count( $this->maps ) > 0 ) {
			do_action( $wpgeo->get_api_string( 'wpgeo_api_%s_js' ), $this->maps );
		}
	}
	
	/**
	 * Shortcode [wpgeo]
	 * Used to manually display a map in a post.
	 *
	 * @param array $atts Array of attributes.
	 * @param array $content Content between tags.
	 * @return string HTML Output.
	 */
	function shortcode_wpgeo( $atts, $content = null ) {
		$allowed_atts = array(
			'rss' => null,
			'kml' => null
		);
		extract( shortcode_atts( $allowed_atts, $atts ) );
		
		if ( $kml != null ) {
			$rss = $kml;
		}
		if ( $rss != null ) {
			$map = new WPGeo_Map( 'shortcode_' . $this->get_id() );
			$map->add_feed( $rss );
			$this->add_map( $map );
			$wp_geo_options = get_option( 'wp_geo_options' );
			
			return $map->get_map_html( array(
				'classes' => array( 'wpgeo', 'wpgeo-rss' ),
				'content' => $rss
			) );
		}
		return '';
	}
	
}

global $wpgeo_display;
$wpgeo_display = new WPGeo_Display();

?>