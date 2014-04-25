<?php

/**
 * WP Geo Widget
 * A base class providing common WP Geo functionality for all widgets.
 */
class WPGeo_Widget extends WP_Widget {

	/**
	 * Constuctor
	 */
	function WPGeo_Widget( $id_base = false, $name, $widget_options = array(), $control_options = array() ) {
		$this->WP_Widget( $id_base, $name, $widget_options, $control_options );
		add_action( 'wpgeo_widget_form_fields', array( $this, 'widget_form_fields_default' ), 5, 2 );
		add_action( 'wpgeo_widget_form_fields', array( $this, 'widget_form_fields_settings' ), 9, 2 );
	}
	
	/**
	 * Wrap Content
	 *
	 * @param string $content Widget HTML content.
	 * @param array $args Parameters.
	 * @param array $instance Widget instance.
	 * @return string HTML widget output.
	 */
	function wrap_content( $content, $args, $instance ) {
		if ( ! empty( $content ) ) {
			$html = $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				$html .= $args['before_title'] . $instance['title'] . $args['after_title'];
			}
			$html .= $content . $args['after_widget'];
			return $html;
		}
		return '';
	}
	
	/**
	 * Validate Yes/No
	 *
	 * @param string $yesno String to check for 'Y' or 'N'.
	 * @return bool.
	 */
	function validate_yesno( $yesno ) {
		return in_array( $yesno, array( 'Y', 'N' ) ) ? $yesno : '';
	}
	
	/**
	 * Validate string
	 *
	 * @param string $string String to filter.
	 * @return string Validated string.
	 */
	function validate_string( $string ) {
		return strip_tags( stripslashes( $string ) );
	}
	
	/**
	 * Validate widget instance
	 *
	 * @param array $instance Widget values.
	 * @return array Widget values.
	 */
	function validate_instance( $instance ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		$validated_instance = wp_parse_args( $instance, array(
			'title'          => 'Map',
			'width'          => '100%',
			'height'         => '150',
			'number'         => 1,
			'maptype'        => $wp_geo_options['google_map_type'],
			'show_polylines' => '',
			'zoom'           => $wp_geo_options['default_map_zoom'],
			'post_type'      => array( 'post' ),
		) );
		
		// Validation
		if ( $validated_instance['zoom'] === null ) {
			$validated_instance['zoom'] = $wp_geo_options['default_map_zoom'];
		}
		if ( ! is_array( $validated_instance['post_type'] ) ) {
			$validated_instance['post_type'] = array( $validated_instance['post_type'] );
		}
		return $validated_instance;
	}
	
	/**
	 * Validate widget display instance
	 *
	 * @param array $instance Widget values.
	 * @return array Widget values.
	 */
	function validate_display_instance( $instance ) {
		$wp_geo_options = get_option( 'wp_geo_options' );

		// Validate the instance
		$instance['title']   = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', __( $instance['title'] ) );
		$instance['width']   = empty( $instance['width'] ) ? '' : $instance['width'];
		$instance['height']  = empty( $instance['height'] ) ? '' : $instance['height'];
		$instance['maptype'] = empty( $instance['maptype'] ) ? '' : $instance['maptype'];
		if ( $instance['show_polylines'] == 'Y' || $instance['show_polylines'] == 'N' ) {
			$instance['show_polylines'] = $instance['show_polylines'] == 'Y' ? true : false;
		} else {
			$instance['show_polylines'] = $wp_geo_options['show_polylines'] == 'Y' ? true : false;
		}
		$instance['zoom']    = is_numeric( $instance['zoom'] ) ? $instance['zoom'] : $wp_geo_options['default_map_zoom'];
		return $instance;
	}
	
	/**
	 * Validate Update
	 *
	 * @param array $new_instance New widget values.
	 * @param array $old_instance Old widget values.
	 * @return array New values.
	 */
	function validate_update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']          = $this->validate_string( $new_instance['title'] );
		$instance['width']          = $this->validate_string( $new_instance['width'] );
		$instance['height']         = $this->validate_string( $new_instance['height'] );
		$instance['maptype']        = $this->validate_string( $new_instance['maptype'] );
		$instance['show_polylines'] = $this->validate_yesno( $new_instance['show_polylines'] );
		$instance['zoom']           = absint( $new_instance['zoom'] );
		return $instance;
	}

	/**
	 * Check API Key Message
	 *
	 * Returned a message in the widget admin is no Google API Key set.
	 * Now handled per map API using the wpgeo_widget_form_fields hook.
	 *
	 * @deprecated  3.3.8
	 *
	 * @return  string  HTML message.
	 */
	function check_api_key_message() {
		return '';
	}

	/**
	 * Add widget map
	 *
	 * @param array $args Args.
	 * @return string Output.
	 */
	function add_widget_map( $args = null ) {
		global $wpgeo, $post;
		$wp_geo_options = get_option( 'wp_geo_options' );
		$current_post = $post->ID;
		
		$html_js = '';
		$markers_js = '';
		$polyline_js = '';
		$markers_js_3 = '';
		$polyline_js_3 = '';
		
		$args = wp_parse_args( $args, array(
			'width'         => '100%',
			'height'        => 150,
			'maptype'       => empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'],
			'show_polylines' => false,
			'zoom'          => $wp_geo_options['default_map_zoom'],
			'id'            => 'widget_map',
			'posts'         => null
		) );
		if ( ! $args['posts'] ) {
			return $html_js;
		}
		
		// Create Map
		$map = new WPGeo_Map( $args['id'] );
		$map->set_size( $args['width'], $args['height'] );
		$map->set_map_centre( new WPGeo_Coord( 0, 0 ) );
		$map->set_map_zoom( $args['zoom'] );
		$map->set_map_type( $args['maptype'] );
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
			
			// Add points (from posts) to map
			$count = 0;
			foreach ( $args['posts'] as $post ) {
				$coord = get_wpgeo_post_coord( $post->ID );
				if ( $coord->is_valid_coord() ) {
					$count++;
					if ( count( $count ) == 1 ) {
						$map->set_map_centre( $coord );
					}
					$map->add_point( $coord, array(
						'icon'  => apply_filters( 'wpgeo_marker_icon', 'small', $post, 'widget' ),
						'title' => get_wpgeo_title( $post->ID ),
						'link'  => apply_filters( 'wpgeo_marker_link', get_permalink( $post ), $post ),
						'post'  => $post
					) );
				}
			}
			
			// Only show map widget if there are coords to show
			if ( count( $map->points ) > 0 ) {
				
				// Add polylines (to connect points) to map
				if ( $args['show_polylines'] ) {
					$polyline = new WPGeo_Polyline( array(
						'color' => $wp_geo_options['polyline_colour']
					) );
					foreach ( $map->points as $point ) {
						$polyline->add_coord( $point->get_coord() );
					}
					$map->add_polyline( $polyline );
				}

				$html_js .= $map->get_map_html( array(
					'classes' => array( 'wp_geo_map' )
				) );
			}
			
			$wpgeo->maps->add_map( $map );
			
			return $html_js;
		}
	}
	
	/**
	 * Default Fields
	 * Title, width and height fields.
	 *
	 * @param array $instance Widget values.
	 * @param object $widget Widget.
	 */
	function widget_form_fields_default( $instance, $widget ) {
		if ( $widget == $this ) {
			echo '
				<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $instance['title'] . '" /></label></p>
				<p><label for="' . $this->get_field_id( 'width' ) . '">' . __( 'Width', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'width' ) . '" name="' . $this->get_field_name( 'width' ) . '" type="text" value="' . $instance['width'] . '" /></label></p>
				<p><label for="' . $this->get_field_id( 'height' ) . '">' . __( 'Height', 'wp-geo' ) . ': <input class="widefat" id="' . $this->get_field_id( 'height' ) . '" name="' . $this->get_field_name( 'height' ) . '" type="text" value="' . $instance['height'] . '" /></label></p>';
		}
	}
	
	/**
	 * Settings Fields
	 *
	 * @param array $instance Widget values.
	 * @param object $widget Widget.
	 */
	function widget_form_fields_settings( $instance, $widget ) {
		global $wpgeo;
		if ( $widget == $this ) {
			echo '<p><strong>' . __( 'Zoom', 'wp-geo' ) . ':</strong> ' . $wpgeo->selectMapZoom( null, null, array( 'return' => 'menu', 'selected' => $instance['zoom'], 'id' => $this->get_field_id( 'zoom' ), 'name' => $this->get_field_name( 'zoom' ) ) ) . '<br />
			<small>' . __( 'If not all markers fit, the map will automatically be zoomed so they do.', 'wp-geo' ) . '</small></p>';
			echo '<p><strong>' . __( 'Settings', 'wp-geo' ) . ':</strong></p>';
			echo '<p>' . __( 'Map Type', 'wp-geo' ) . ':<br />' . $wpgeo->google_map_types( null, null, array( 'return' => 'menu', 'selected' => $instance['maptype'], 'id' => $this->get_field_id( 'maptype' ), 'name' => $this->get_field_name( 'maptype' ) ) ) . '</p>';
			echo '<p>' . __( 'Polylines', 'wp-geo' ) . ':<br />' . wpgeo_show_polylines_options( array( 'return' => 'menu', 'selected' => $instance['show_polylines'], 'id' => $this->get_field_id( 'show_polylines' ), 'name' => $this->get_field_name( 'show_polylines' ) ) ) . '</p>';
		}
	}
	
}
