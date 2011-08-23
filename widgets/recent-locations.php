<?php



/**
 * @package    WP Geo
 * @subpackage WP Geo Recent Locations Widget class
 */



/**
 * @class        WP Geo Recent Locations Widget
 * @description  Adds a map widget to WordPress (requires WP Geo plugin).
 *               The widget displays markers for recent posts.
 * @author       Ben Huson <ben@thewhiteroom.net>
 * @version      1.0
 */
class WPGeo_Recent_Locations_Widget extends WP_Widget {
	
	
	
	/**
	 * Widget Constuctor
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
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
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
				$map_content = $this->add_map( $width, $height, $maptype, $showpolylines, $zoom, $args['widget_id'] . '-map', $number, $post_type );
				
				if ( !empty( $map_content ) ) {
					$html_content = $before_widget;
					if ( !empty( $title ) ) {
						$html_content .= $before_title . $title . $after_title;
					}
					$html_content .= $map_content . $after_widget;
				}
				
				echo $html_content;
			
			}
		
		}
		
	}
	
	
	
	/**
	 * Update Widget
	 *
	 * @param $new_instance (array) New widget values.
	 * @param $old_instance (array) Old widget values.
	 *
	 * @return (array) New values.
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
	 *
	 * @param $instance (array) Widget values.
	 */
	function form( $instance ) {
		
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Defaults
		$instance = wp_parse_args( (array)$instance, array(
			'title'          => 'Map',
			'width'          => '100%',
			'height'         => '150',
			'number'         => 1,
			'maptype'        => $wp_geo_options['google_map_type'],
			'show_polylines' => '',
			'zoom'           => null,
			'post_type'      => array( 'post' ),
		) );
		
		// Validation
		if ( $instance['zoom'] === null ) {
			$instance['zoom'] = $wp_geo_options['default_map_zoom'];
		}
		
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
	 * @method       Show Polylines Options
	 * @description  Polylines options menu for the map.
	 * @param        $args = Array of arguments.
	 * @return       (array or string) Array or HTML select menu.
	 */
	function show_polylines_options( $args = null ) {
		
		// Defaults
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
		
		// Default return
		return $map_type_array;
		
	}	
	
	
	
	/**
	 * @method       Add Map
	 * @description  Add the map to the widget.
	 * @param        $width = Map width
	 * @param        $height = Map height
	 * @param        $maptype = Map Type
	 * @param        $showpolylines = Show Polylines
	 * @param        $zoom = Zoom
	 * @return       (string) HTML JavaScript.
	 * @note         TO DO: integrate the code better into the existing one.
	 */
	function add_map( $width = '100%', $height = 150, $maptype = '', $showpolylines = false, $zoom = null, $id = 'wp_geo_map_widget', $number = 1, $post_type = 'post' ) {
	
		global $wpgeo;
		
		$html_js = '';
		
		// If Google API Key...
		if ( $wpgeo->checkGoogleAPIKey() ) {
			
			// Could use meta_query in WP 3.1+
			$posts = get_posts( array(
				'numberposts'  => $number,
				'meta_key'     => WPGEO_LATITUDE_META,
				'meta_value'   => 0,
				'meta_compare' => '>',
				'post_type'    => $post_type
			) );
		
			// Set default width and height
			if ( empty( $width ) ) {
				$width = '100%';
			}
			if ( empty( $height ) ) {
				$height = '150';
			}
			
			// Get the basic settings of wp geo
			$wp_geo_options = get_option( 'wp_geo_options' );
			
			// Find the coordinates for the posts
			$coords = array();
			for ( $i = 0; $i < count( $posts ); $i++ ) {
			
				$post 		= $posts[$i];
				$latitude 	= get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
				$longitude 	= get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
				$post_id 	= get_post( $post->ID );
				$title 	    = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
				if ( empty( $title ) ) {
					$title = $post_id->post_title;
				}
				
				if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
					$push = array(
						'id' 		=> $post->ID,
						'latitude' 	=> $latitude,
						'longitude' => $longitude,
						'title' 	=> $title,
						'post'		=> $post
					);
					array_push( $coords, $push );
				}
				
			}
			
			// Markers JS (output)
			$markers_js = '';
			
			// Only show map widget if there are coords to show
			if ( count( $coords ) > 0 ) {
			
				$google_maps_api_key = $wpgeo->get_google_api_key();
				if ( !is_numeric( $zoom ) ) {
					$zoom = $wp_geo_options['default_map_zoom'];
				}
				
				if ( empty( $maptype ) ) {
					$maptype = empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];
				}
				
				// Polylines
				$polyline_js = '';
				if ( $showpolylines ) {
					$polyline = new WPGeo_Polyline( array(
						'color' => $wp_geo_options['polyline_colour']
					) );
					for ( $i = 0; $i < count( $coords ); $i++ ) {
						$polyline->add_coord( $coords[$i]['latitude'], $coords[$i]['longitude'] );
					}
					$polyline_js = WPGeo_API_GMap2::render_map_overlay( 'map', WPGeo_API_GMap2::render_polyline( $polyline ) );
				}
				
				for ( $i = 0; $i < count( $coords ); $i++ ) {
					$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', 'small', $coords[$i]['post'], 'widget' );
					$markers_js .= 'marker' . $i . ' = wpgeo_createMarker(new GLatLng(' . $coords[$i]['latitude'] . ', ' . $coords[$i]['longitude'] . '), ' . $icon . ', "' . addslashes( __( $coords[$i]['title'] ) ) . '", "' . get_permalink( $coords[$i]['id'] ) . '");' . "\n";
				}
							
				// HTML JS
				$wpgeo->includeGoogleMapsJavaScriptAPI();
				
				$small_marker = $wpgeo->markers->get_marker_by_id( 'small' );
				
				$html_js .= '
					<script type="text/javascript">
					//<![CDATA[
					
					/**
					 * Widget Recent Locations
					 */
					
					// Define variables
					var map = "";
					var bounds = "";
					
					// Add events to load the map
					GEvent.addDomListener(window, "load", createMapWidget);
					GEvent.addDomListener(window, "unload", GUnload);
					
					// Create the map
					function createMapWidget() {
						if (GBrowserIsCompatible()) {
							map = new GMap2(document.getElementById("' . $id . '"));
							' . WPGeo_API_GMap2::render_map_control( 'map', 'GSmallZoomControl3D' ) . '
							map.setCenter(new GLatLng(0, 0), 0);
							map.setMapType(' . $maptype . ');
							bounds = new GLatLngBounds();
							
							// Add the markers	
							'.	$markers_js .'
							
							// Draw the polygonal lines between points
							' . $polyline_js . '
							
							// Center the map to show all markers
							var center = bounds.getCenter();
							var zoom = map.getBoundsZoomLevel(bounds)
							if (zoom > ' . $zoom . ') {
								zoom = ' . $zoom . ';
							}
							map.setCenter(center, zoom);
						}
					}
					
					//]]>
					</script>';
				
				// Set width and height
				if ( is_numeric( $width ) )
					$width = $width . 'px';
				if ( is_numeric( $height ) )
					$height = $height . 'px';
				
				$html_js .= '<div class="wp_geo_map" id="' . $id . '" style="width:' . $width . '; height:' . $height . ';"></div>';
			
			}
			
			return $html_js;
		
		}
		
	}
	
	
		
}



// Widget Hooks
add_action( 'widgets_init', create_function( '', 'return register_widget( "WPGeo_Recent_Locations_Widget" );' ) );



?>