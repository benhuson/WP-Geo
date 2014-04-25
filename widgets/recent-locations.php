<?php

/**
 * WP Geo Recent Locations Widget
 * Adds a map widget to WordPress (requires WP Geo plugin).
 * The widget displays markers for recent posts.
 *
 * @version 1.5
 * @author Ben Huson <ben@thewhiteroom.net>
 */
class WPGeo_Recent_Locations_Widget extends WPGeo_Widget {
	
	/**
	 * Widget Constuctor
	 */
	function WPGeo_Recent_Locations_Widget() {
		$this->WPGeo_Widget(
			'wpgeo_recent_locations_widget',
			__( 'WP Geo Recent Locations', 'wp-geo' ),
			array(
				'classname'   => 'wpgeo_recent_locations_widget',
				'description' => __( 'Displays markers for recent posts', 'wp-geo' )
			)
		);
		add_action( 'wpgeo_widget_form_fields', array( $this, 'widget_form_fields_number' ), 10, 2 );
		add_action( 'wpgeo_widget_form_fields', array( $this, 'widget_form_fields_post_types' ), 50, 2 );
	}
	
	/**
	 * Widget Output
	 *
	 * @param array $args
	 * @param array $instance Widget values.
	 */
	function widget( $args, $instance ) {
		global $wpgeo;
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
			$wp_geo_options = get_option( 'wp_geo_options' );
			$instance = $this->validate_display_instance( $instance );
			$instance['number']    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 0;
			$instance['post_type'] = empty( $instance['post_type'] ) ? 'post' : $instance['post_type'];
			
			// Start write widget
			if ( $instance['number'] > 0 ) {
				$posts = get_posts( array(
					'numberposts'  => $instance['number'],
					'meta_key'     => WPGEO_LATITUDE_META,
					'meta_value'   => '',
					'meta_compare' => '!=',
					'post_type'    => $instance['post_type']
				) );
				$map_args = wp_parse_args( $instance, array(
					'id'    => $args['widget_id'],
					'posts' => $posts
				) );
				$map_content = $this->add_widget_map( $map_args );
				echo $this->wrap_content( $map_content, $args, $instance );
			}
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
		$instance = $this->validate_update( $new_instance, $old_instance );
		$instance['number']    = absint( $new_instance['number'] );
		$instance['post_type'] = $new_instance['post_type'];
		return $instance;
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
	
	/**
	 * Number form field
	 *
	 * @param array $instance Widget values.
	 * @param object $widget Widget.
	 */
	function widget_form_fields_number( $instance, $widget ) {
		if ( $widget == $this ) {
			echo '<p><label for="' . $this->get_field_id( 'number' ) . '">' . __( 'Number of markers to show', 'wp-geo' ) . ':</label> <input id="' . $this->get_field_id( 'number' ) . '" name="' . $this->get_field_name( 'number' ) . '" type="text" value="' . $instance['number'] . '" size="3"></p>';
		}
	}
	
	/**
	 * Post Types checkboxes
	 *
	 * @param array $instance Widget values.
	 * @param object $widget Widget.
	 */
	function widget_form_fields_post_types( $instance, $widget ) {
		global $wpgeo;
		if ( $widget == $this ) {
			$options = get_option( 'wp_geo_options' );
			echo '<p><strong>' . __( 'Show Post Types', 'wp-geo' ) . ':</strong></p>';
			$post_types = get_post_types( array(), 'objects' );
			$custom_post_type_checkboxes = '';
			foreach ( $post_types as $post_type ) {
				if ( $wpgeo->post_type_supports( $post_type->name ) ) {
					$checked = in_array( $post_type->name, $instance['post_type'] ) ? $post_type->name : false;
					$custom_post_type_checkboxes .= wpgeo_checkbox( $this->get_field_name( 'post_type' ) . '[]', $post_type->name, $checked ) . ' ' . $post_type->label . '<br />';
				}
			}
			echo $custom_post_type_checkboxes;
		}
	}
	
}

// Widget Hook
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Recent_Locations_Widget" );' ) );
