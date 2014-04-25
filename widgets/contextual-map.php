<?php

/**
 * WP Geo Contextual Widget
 * Adds a map widget to WordPress (requires WP Geo plugin).
 * The widget displays markers for posts on the current page.
 *
 * @version 1.5
 * @author Marco Alionso Ramirez <marco@onemarco.com>
 *         updated by Ben Huson <ben@thewhiteroom.net>
 */
class WPGeo_Contextual_Map_Widget extends WPGeo_Widget {
	
	/**
	 * Widget Constuctor
	 */
	function WPGeo_Contextual_Map_Widget() {
		$this->WPGeo_Widget(
			'wpgeo_contextual_map_widget',
			__( 'WP Geo Contextual Map', 'wp-geo' ),
			array(
				'classname'   => 'wpgeo_contextual_map_widget',
				'description' => __( 'Displays markers from the current page', 'wp-geo' )
			)
		);
	}
	
	/**
	 * Widget Output
	 *
	 * @param array $args
	 * @param array $instance Widget values.
	 */
	function widget( $args, $instance ) {
		global $wpgeo, $posts;
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
			$wp_geo_options = get_option( 'wp_geo_options' );
			$instance = $this->validate_display_instance( $instance );
			
			// Start write widget
			$map_args = wp_parse_args( $instance, array(
				'id'    => $args['widget_id'],
				'posts' => $posts
			) );
			$map_content = $this->add_widget_map( $map_args );
			echo $this->wrap_content( $map_content, $args, $instance );
		}
	}
	
	/**
	 * Update Widget
	 *
	 * @param array $new_instance New widget values.
	 * @param array $old_instance Old widget values.
	 * @return array New values.
	 */
	function update( $new_instance, $old_instance ) {
		return $this->validate_update( $new_instance, $old_instance );
	}
	
	/**
	 * Widget Options Form
	 *
	 * @param array $instance Widget values.
	 */
	function form( $instance ) {
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$instance = $this->validate_instance( (array)$instance );

		do_action( 'wpgeo_widget_form_fields', $instance, $this );
	}
		
}

// Widget Hook
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Contextual_Map_Widget" );' ) );
