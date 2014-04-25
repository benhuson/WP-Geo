<?php

/**
 * WP Geo Category Widget
 * Adds a map widget to WordPress (requires WP Geo plugin).
 * The widget displays markers for posts in the current category.
 *
 * @version 1.5
 * @author David Keen
 *         updated by Ben Huson <ben@thewhiteroom.net>
 */
class WPGeo_Category_Map_Widget extends WPGeo_Widget {

	/**
	 * Constuctor
	 */
	function WPGeo_Category_Map_Widget() {
		$this->WPGeo_Widget(
			'wpgeo_category_map_widget',
			__( 'WP Geo Category Map', 'wp-geo' ),
			array(
				'classname'   => 'wpgeo_category_map_widget',
				'description' => __( 'Displays markers from the current category', 'wp-geo' )
			)
		);
	}
	
	/**
	 * Widget Output
	 *
	 * @todo Exclude current post?
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

			// Start write widget
			$post_cats = get_the_category();
			if ( count( $post_cats ) > 0 ) {
				$post_cat_id = $post_cats[0]->cat_ID;
				$posts = get_posts( array(
					'numberposts'  => -1,
					'meta_key'     => WPGEO_LATITUDE_META,
					'meta_value'   => '',
					'meta_compare' => '!=',
					'category'     => $post_cat_id
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
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Category_Map_Widget" );' ) );
