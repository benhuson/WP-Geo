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
			if ( ! empty( $instance['title'] ) )
				$html .= $args['before_title'] . $instance['title'] . $args['after_title'];
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
	 * Returns a message is no Google API Key set.
	 *
	 * @todo Check if there is a 'less hard-coded' way to write link to settings page
	 *
	 * @return string HTML message.
	 */
	function check_api_key_message() {
		global $wpgeo;
		if ( ! $wpgeo->checkGoogleAPIKey() ) {
			return '<p class="wp_geo_error">' . __( 'WP Geo is not currently active as you have not entered a Google API Key', 'wp-geo') . '. <a href="' . admin_url( '/options-general.php?page=wp-geo/includes/wp-geo.php' ) . '">' . __( 'Please update your WP Geo settings', 'wp-geo' ) . '</a>.</p>';
		}
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
		if ( ! $args['posts'] )
			return $html_js;
		
		// Create Map
		$map = new WPGeo_Map( $args['id'] );
		$map->set_size( $args['width'], $args['height'] );
		$map->set_map_centre( new WPGeo_Coord( 0, 0 ) );
		$map->set_map_zoom( $args['zoom'] );
		$map->set_map_type( $args['maptype'] );
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
			
			// Add points (from posts) to map
			foreach ( $args['posts'] as $post ) {
				$coord = new WPGeo_Coord( get_post_meta( $post->ID, WPGEO_LATITUDE_META, true ), get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true ) );
				if ( $coord->is_valid_coord() ) {
					$map->add_point( $coord, array(
						'icon'  => apply_filters( 'wpgeo_marker_icon', 'small', $post, 'widget' ),
						'title' => get_wpgeo_title( $post->ID ),
						'link'  => get_permalink( $post ),
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
						$polyline->add_coord( $point->coord );
					}
					$map->add_polyline( $polyline );
				}
				
				// Polylines
				if ( count( $map->polylines ) > 0 ) {
					foreach ( $map->polylines as $polyline ) {
					
						// v2 Polyline
						$coords = array();
						foreach ( $polyline->coords as $coord ) {
							$coords[] = 'new GLatLng(' . $coord->get_delimited() . ')';
						}
						$options = array();
						if ( $polyline->geodesic ) {
							$options[] = 'geodesic:true';
						}
						$polyline_js = $map->get_js_id() . '.addOverlay(new GPolyline([' . implode( ',', $coords ) . '],"' . $polyline->color . '",' . $polyline->thickness . ',' . $polyline->opacity . ',{' . implode( ',', $options ) . '}));';
						
						// v3 Polyline
						$polyline_js_3_coords = array();
						foreach ( $polyline->coords as $c ) {
							$polyline_js_3_coords[] = 'new google.maps.LatLng(' . $c->get_delimited() . ')';
						}
						$polyline_js_3 = 'var polyline = new google.maps.Polyline({
								path          : [' . implode( ',', $polyline_js_3_coords ) . '],
								strokeColor   : "' . $polyline->color . '",
								strokeOpacity : ' . $polyline->opacity . ',
								strokeWeight  : ' . $polyline->thickness . ',
								geodesic      : ' . $polyline->geodesic . '
							});
							polyline.setMap(' . $map->get_js_id() . ');';
					}
				}
				
				// Markers
				for ( $i = 0; $i < count( $map->points ); $i++ ) {
					$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', 'small', $map->points[$i]->args['post'], 'widget' );
					$markers_js .= 'var marker_' . $i . ' = wpgeoCreateMapMarker(' . $map->get_js_id() . ', new GLatLng(' . $map->points[$i]->coord->get_delimited() . '), ' . $icon . ', "' . addslashes( __( $map->points[$i]->title ) ) . '", "' . get_permalink( $map->points[$i]->args['post']->ID ) . '");' . "\n";
					$markers_js_3 .= 'var marker_' . $i . ' = new google.maps.Marker({ position:new google.maps.LatLng(' . $map->points[$i]->coord->get_delimited() . '), map:' . $map->get_js_id() . ', icon: ' . $icon . ' });' . "\n";
					if ( ! empty( $map->points[$i]->link ) ) {
						$markers_js_3 .= 'google.maps.event.addListener(marker_' . $i . ', "click", function() {
								window.location.href = "' . $map->points[$i]->link . '";
							});
							';
					}
					if ( ! empty( $map->points[$i]->title ) ) {
						$markers_js_3 .= '
							var tooltip_' . $i . ' = new Tooltip(marker_' . $i . ', \'' . esc_js( $map->points[$i]->title ) . '\');
							google.maps.event.addListener(marker_' . $i . ', "mouseover", function() {
								tooltip_' . $i . '.show();
							});
							google.maps.event.addListener(marker_' . $i . ', "mouseout", function() {
								tooltip_' . $i . '.hide();
							});
							';
					}
					$markers_js_3 .= 'bounds.extend(new google.maps.LatLng(' . $map->points[$i]->coord->get_delimited() . '));' . "\n";
				}
				
				$wpgeo->includeGoogleMapsJavaScriptAPI();
				$center_coord = $map->get_map_centre();
				
				if ( 'googlemapsv3' == $wpgeo->get_api_string() ) {
					$html_js .= '
						<script type="text/javascript">
						//<![CDATA[
						
						/**
						 * Widget Map (' . $map->get_dom_id() . ')
						 */
						var ' . $map->get_js_id() . ' = null;
						var marker = null;
						function createMapWidget3_' . $map->get_js_id() . '() {
							var mapOptions = {
								center            : new google.maps.LatLng(' . $center_coord->get_delimited() . '),
								zoom              : 0,
								mapTypeId         : ' . apply_filters( 'wpgeo_api_string', 'google.maps.MapTypeId.ROADMAP', $map->get_map_type(), 'maptype' ) . ',
								mapTypeControl    : false,
								streetViewControl : false
							};
							var bounds = new google.maps.LatLngBounds();
							' . $map->get_js_id() . ' = new google.maps.Map(document.getElementById("' . $map->get_dom_id() . '"), mapOptions);
							
							// Add the markers	
							'.	$markers_js_3 .'
							
							// Draw the polygonal lines between points
							' . $polyline_js_3 . '
							
							var center = bounds.getCenter();
							var zoom = ' . $map->get_js_id() . '.getBounds(bounds);
							if (zoom > ' . $map->get_map_zoom() . ') {
								zoom = ' . $map->get_map_zoom() . ';
							}
							' . $map->get_js_id() . '.setCenter(center);
							if (zoom) {
								' . $map->get_js_id() . '.setZoom(zoom);
							}
							
							' . apply_filters( 'wpgeo_map_js_preoverlays', '', $map->get_js_id() ) . '
						}
						google.maps.event.addDomListener(window, "load", createMapWidget3_' . $map->get_js_id() . ');
						
						//]]>
						</script>';
				} else {
					$html_js .= '
						<script type="text/javascript">
						//<![CDATA[
						
						/**
						 * Widget Map (' . $map->get_dom_id() . ')
						 */
						
						// Define variables
						var ' . $map->get_js_id() . ' = "";
						var bounds = "";
						
						// Add events to load the map
						GEvent.addDomListener(window, "load", createMapWidget_' . $map->get_js_id() . ');
						GEvent.addDomListener(window, "unload", GUnload);
						
						// Create the map
						function createMapWidget_' . $map->get_js_id() . '() {
							if (GBrowserIsCompatible()) {
								' . $map->get_js_id() . ' = new GMap2(document.getElementById("' . $map->get_dom_id() . '"));
								' . $map->get_js_id() . '.addControl(new GSmallZoomControl3D());
								' . $map->get_js_id() . '.setCenter(new GLatLng(' . $center_coord->get_delimited() . '), 0);
								' . $map->get_js_id() . '.setMapType(' . $map->get_map_type() . ');
								bounds = new GLatLngBounds();
								
								// Add the markers	
								'.	$markers_js .'
								
								// Draw the polygonal lines between points
								' . $polyline_js . '
								
								// Center the map to show all markers
								var center = bounds.getCenter();
								var zoom = ' . $map->get_js_id() . '.getBoundsZoomLevel(bounds)
								if (zoom > ' . $map->get_map_zoom() . ') {
									zoom = ' . $map->get_map_zoom() . ';
								}
								' . $map->get_js_id() . '.setCenter(center, zoom);
								
								' . apply_filters( 'wpgeo_map_js_preoverlays', '', $map->get_js_id() ) . '
							}
						}
						
						//]]>
						</script>';
				}
				
				$html_js .= $map->get_map_html( array(
					'classes' => array( 'wp_geo_map' )
				) );
			}
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

?>