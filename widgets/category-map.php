<?php

/**
 * WP Geo Category Widget
 * Adds a map widget to WordPress (requires WP Geo plugin).
 * The widget displays markers for posts in the current category.
 *
 * @version 1.4
 * @author David Keen
 *         updated by Ben Huson <ben@thewhiteroom.net>
 */
class WPGeo_Category_Map_Widget extends WP_Widget {

	/**
	 * Constuctor
	 */
	function WPGeo_Category_Map_Widget() {
		$widget_ops = array(
			'classname'   => 'wpgeo_category_map_widget',
			'description' => __( 'Displays markers from the current category', 'wp-geo' )
		);
		$this->WP_Widget( 'wpgeo_category_map_widget', __( 'WP Geo Category Map', 'wp-geo' ), $widget_ops );
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

			// Extract the widget options
			extract( $args );
			$wp_geo_options = get_option( 'wp_geo_options' );

			// Get the options for the widget
			$title 			= empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', __( $instance['title'] ) );
			$width 			= empty( $instance['width'] ) ? '' : $instance['width'];
			$height 		= empty( $instance['height'] ) ? '' : $instance['height'];
			$maptype 		= empty( $instance['maptype'] ) ? '' : $instance['maptype'];
			$showpolylines 	= $wp_geo_options['show_polylines'] == 'Y' ? true : false;
			$zoom 	 	    = is_numeric( $instance['zoom'] ) ? $instance['zoom'] : $wp_geo_options['default_map_zoom'];

			// @todo Check this logic
			if ( $instance['show_polylines'] == 'Y' || $instance['show_polylines'] == 'N' ) {
				$showpolylines = $instance['show_polylines'] == 'Y' ? true : false;
			}

			// Start write widget
			$html_content = '';
			$post_cats = get_the_category();
			$post_cat_id = $post_cats[0]->cat_ID;
			$posts = get_posts( array(
				'numberposts'  => -1,
				'meta_key'     => WPGEO_LATITUDE_META,
				'meta_value'   => 0,
				'meta_compare' => '>',
				'category'     => $post_cat_id
			) );
			// @todo Exclude current post?
			
			$map_content = wpgeo_add_widget_map( array(
				'width'         => $width,
				'height'        => $height,
				'maptype'       => $maptype,
				'showpolylines' => $showpolylines,
				'zoom'          => $zoom,
				'id'            => $args['widget_id'] . '-map',
				'posts'         => $posts
			) );
			
			if ( ! empty( $map_content ) ) {
				$html_content = $before_widget;
				if ( ! empty( $title ) )
					$html_content .= $before_title . $title . $after_title;
				$html_content .= $map_content . $after_widget;
			}

			echo $html_content;
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
		$instance = $old_instance;
		$instance['title']          = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['width']          = strip_tags( stripslashes( $new_instance['width'] ) );
		$instance['height']         = strip_tags( stripslashes( $new_instance['height'] ) );
		$instance['maptype']        = strip_tags( stripslashes( $new_instance['maptype'] ) );
		$instance['show_polylines'] = in_array( $new_instance['show_polylines'], array( 'Y', 'N' ) ) ? $new_instance['show_polylines'] : '';
		$instance['zoom']           = absint( $new_instance['zoom'] );
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

		// Defaults
		$instance = wp_parse_args( (array)$instance, array(
			'title'          => 'Map',
			'width'          => '100%',
			'height'         => '150',
			'maptype'        => $wp_geo_options['google_map_type'],
			'show_polylines' => '',
			'zoom'           => $wp_geo_options['default_map_zoom'],
		) );
		
		// Message if API key not set
		if ( ! $wpgeo->checkGoogleAPIKey() ) {
			// @todo Check if there is a 'less hard-coded' way to write link to settings page
			echo '<p class="wp_geo_error">' . __( 'WP Geo is not currently active as you have not entered a Google API Key', 'wp-geo') . '. <a href="' . admin_url( '/options-general.php?page=wp-geo/includes/wp-geo.php' ) . '">' . __( 'Please update your WP Geo settings', 'wp-geo' ) . '</a>.</p>';
		}

		echo '
			<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $instance['title'] . '" /></label></p>
			<p><label for="' . $this->get_field_id( 'width' ) . '">' . __( 'Width', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'width' ) . '" name="' . $this->get_field_name( 'width' ) . '" type="text" value="' . $instance['width'] . '" /></label></p>
			<p><label for="' . $this->get_field_id( 'height' ) . '">' . __( 'Height', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'height' ) . '" name="' . $this->get_field_name( 'height' ) . '" type="text" value="' . $instance['height'] . '" /></label></p>';
		echo '<p><strong>' . __( 'Zoom', 'wp-geo' ) . ':</strong> ' . $wpgeo->selectMapZoom( null, null, array( 'return' => 'menu', 'selected' => $instance['zoom'], 'id' => $this->get_field_id( 'zoom' ), 'name' => $this->get_field_name( 'zoom' ) ) ) . '<br />
			<small>' . __( 'If not all markers fit, the map will automatically be zoomed so they do.', 'wp-geo' ) . '</small></p>';
		echo '<p><strong>' . __( 'Settings', 'wp-geo' ) . ':</strong></p>';
		echo '<p>' . __( 'Map Type', 'wp-geo' ) . ':<br />' . $wpgeo->google_map_types( null, null, array( 'return' => 'menu', 'selected' => $instance['maptype'], 'id' => $this->get_field_id( 'maptype' ), 'name' => $this->get_field_name( 'maptype' ) ) ) . '</p>';
		echo '<p>' . __( 'Polylines', 'wp-geo' ) . ':<br />' . $this->show_polylines_options( array( 'return' => 'menu', 'selected' => $instance['show_polylines'], 'id' => $this->get_field_id( 'show_polylines' ), 'name' => $this->get_field_name( 'show_polylines' ) ) ) . '</p>';
	}
	
	/**
	 * Show Polylines Options
	 * Polylines options menu for the map.
	 *
	 * @param array $args Array of arguments.
	 * @return array|string Array or HTML select menu.
	 */
	function show_polylines_options( $args = null ) {
		$args = wp_parse_args( (array)$args, array(
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
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Category_Map_Widget" );' ) );

?>