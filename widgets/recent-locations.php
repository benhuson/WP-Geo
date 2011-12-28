<?php

/**
 * WP Geo Recent Locations Widget v.1.1
 * Adds a map widget to WordPress (requires WP Geo plugin).
 * The widget displays markers for recent posts.
 *
 * Ben Huson <ben@thewhiteroom.net>
 */

class WPGeo_Recent_Locations_Widget extends WP_Widget {
	
	/**
	 * Constuctor
	 */
	function WPGeo_Recent_Locations_Widget() {
		$widget_ops = array(
			'classname'   => 'wpgeo_recent_locations_widget',
			'description' => __( 'Displays markers for recent posts', 'wp-geo' )
		);
		$this->WP_Widget( 'wpgeo_recent_locations_widget', __( 'WP Geo Recent Locations', 'wp-geo' ), $widget_ops );
	}
	
	/**
	 * Widget Output
	 */
	function widget( $args, $instance ) {
		global $wpgeo;
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
		
			// Extract the widget options
			extract( $args );
			$wp_geo_options = get_option( 'wp_geo_options' );
	
			// Get the options for the widget
			$title 			= empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', __( $instance['title'] ) );
			$width 			= empty( $instance['width'] ) ? '' : $instance['width'];
			$height 		= empty( $instance['height'] ) ? '' : $instance['height'];
			$number 		= isset( $instance['number'] ) ? absint( $instance['number'] ) : 0;
			$maptype 		= empty( $instance['maptype'] ) ? '' : $instance['maptype'];
			$showpolylines 	= $wp_geo_options['show_polylines'] == 'Y' ? true : false;
			$zoom 	 	    = is_numeric( $instance['zoom'] ) ? $instance['zoom'] : $wp_geo_options['default_map_zoom'];
			$post_type 		= empty( $instance['post_type'] ) ? 'post' : $instance['post_type'];
			
			if ( $number > 0 ) {
			
				// @todo Check this logic
				if ( $instance['show_polylines'] == 'Y' || $instance['show_polylines'] == 'N' ) {
					$showpolylines = $instance['show_polylines'] == 'Y' ? true : false;
				}
				
				// Start write widget
				$html_content = '';
				
				$posts = get_posts( array(
					'numberposts'  => $number,
					'meta_key'     => WPGEO_LATITUDE_META,
					'meta_value'   => 0,
					'meta_compare' => '>',
					'post_type'    => $post_type
				) );
				
				$map_content =  wpgeo_add_widget_map( array(
					'width'         => $width,
					'height'        => $height,
					'maptype'       => $maptype,
					'showpolylines' => $showpolylines,
					'zoom'          => $zoom,
					'id'            => $args['widget_id'] . '-map',
					'posts'         => $posts
				) );
				
				if ( !empty( $map_content ) ) {
					$html_content = $before_widget;
					if ( !empty( $title ) )
						$html_content .= $before_title . $title . $after_title;
					$html_content .= $map_content . $after_widget;
				}
				echo $html_content;
			}
		}
	}
	
	/**
	 * Update Widget
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']          = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['width']          = strip_tags( stripslashes( $new_instance['width'] ) );
		$instance['height']         = strip_tags( stripslashes( $new_instance['height'] ) );
		$instance['number']         = absint( $new_instance['number'] );
		$instance['maptype']        = strip_tags( stripslashes( $new_instance['maptype'] ) );
		$instance['show_polylines'] = in_array( $new_instance['show_polylines'], array( 'Y', 'N' ) ) ? $new_instance['show_polylines'] : '';
		$instance['zoom']           = absint( $new_instance['zoom'] );
		$instance['post_type']      = $new_instance['post_type'];
		return $instance;
	}
	
	/**
	 * Widget Options Form
	 */
	function form( $instance ) {
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Defaults
		$instance = wp_parse_args( $instance, array(
			'title'          => 'Map',
			'width'          => '100%',
			'height'         => '150',
			'number'         => 1,
			'maptype'        => $wp_geo_options['google_map_type'],
			'show_polylines' => '',
			'zoom'           => $wp_geo_options['default_map_zoom'],
			'post_type'      => array( 'post' ),
		) );
		
		$instance['post_type'] = (array)$instance['post_type'];
		if ( count( $instance['post_type'] ) == 0 ) {
			$instance['post_type'] = array( 'post' );
		}
		
		// Message if API key not set
		if ( !$wpgeo->checkGoogleAPIKey() ) {
			// @todo Check if there is a 'less hard-coded' way to write link to settings page
			echo '<p class="wp_geo_error">' . __( 'WP Geo is not currently active as you have not entered a Google API Key', 'wp-geo') . '. <a href="' . admin_url( '/options-general.php?page=wp-geo/includes/wp-geo.php' ) . '">' . __( 'Please update your WP Geo settings', 'wp-geo' ) . '</a>.</p>';
		}
		
		echo '
			<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $instance['title'] . '" /></label></p>
			<p><label for="' . $this->get_field_id( 'width' ) . '">' . __( 'Width', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'width' ) . '" name="' . $this->get_field_name( 'width' ) . '" type="text" value="' . $instance['width'] . '" /></label></p>
			<p><label for="' . $this->get_field_id( 'height' ) . '">' . __( 'Height', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'height' ) . '" name="' . $this->get_field_name( 'height' ) . '" type="text" value="' . $instance['height'] . '" /></label></p>';
		echo '<p><label for="' . $this->get_field_id( 'number' ) . '">' . __( 'Number of markers to show', 'wp-geo' ) . ':</label> <input id="' . $this->get_field_id( 'number' ) . '" name="' . $this->get_field_name( 'number' ) . '" type="text" value="' . $instance['number'] . '" size="3"></p>';
		echo '<p><strong>' . __( 'Zoom', 'wp-geo' ) . ':</strong> ' . $wpgeo->selectMapZoom( null, null, array( 'return' => 'menu', 'selected' => $instance['zoom'], 'id' => $this->get_field_id( 'zoom' ), 'name' => $this->get_field_name( 'zoom' ) ) ) . '<br />
			<small>' . __( 'If not all markers fit, the map will automatically be zoomed so they do.', 'wp-geo' ) . '</small></p>';
		echo '<p><strong>' . __( 'Settings', 'wp-geo' ) . ':</strong></p>';
		echo '<p>' . __( 'Map Type', 'wp-geo' ) . ':<br />' . $wpgeo->google_map_types( null, null, array( 'return' => 'menu', 'selected' => $instance['maptype'], 'id' => $this->get_field_id( 'maptype' ), 'name' => $this->get_field_name( 'maptype' ) ) ) . '</p>';
		echo '<p>' . __( 'Polylines', 'wp-geo' ) . ':<br />' . $this->show_polylines_options( array( 'return' => 'menu', 'selected' => $instance['show_polylines'], 'id' => $this->get_field_id( 'show_polylines' ), 'name' => $this->get_field_name( 'show_polylines' ) ) ) . '</p>';
		echo '<p><strong>' . __( 'Show Post Types', 'wp-geo' ) . ':</strong></p>';
		
		$post_types = get_post_types( array(), 'objects' );
		$custom_post_type_checkboxes = '';
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->query_var, 'wpgeo' ) || $post_type->name == 'post' || $post_type->name == 'page' ) {
				$checked = in_array( $post_type->name, $instance['post_type'] ) ? true : false;
				$custom_post_type_checkboxes .= $wpgeo->options_checkbox( $this->get_field_name( 'post_type' ) . '[]', $post_type->name, $checked ) . ' ' . __( $post_type->label, 'wp-geo' ) . '<br />';
			}
		}
		echo $custom_post_type_checkboxes;
	}
	
	/**
	 * Show Polylines Options
	 * Polylines options menu for the map.
	 */
	function show_polylines_options( $args = null ) {
		$args = wp_parse_args( $args, array(
			'id'       => 'show_polylines',
			'name'     => 'show_polylines',
			'return'   => 'array',
			'selected' => null
		) );
		
		// Array
		$map_type_array = array(
			''	=> __( 'Default', 'wp-geo' ),
			'Y'	=> __( 'Show Polylines', 'wp-geo' ),
			'N'	=> __( 'Hide Polylines', 'wp-geo' )
		);
		
		// Menu?
		if ( $args['return'] = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$is_selected = $args['selected'] == $key ? ' selected="selected"' : '';
				$menu .= '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
			}
			$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
			return $menu;
		}
		
		return $map_type_array;
	}
	
}

// Widget Hooks
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Recent_Locations_Widget" );' ) );

?>