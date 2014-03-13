<?php

/**
 * WP Geo Admin
 */
class WPGeo_Admin {

	var $settings;
	var $editor;
	var $map;
	var $plugin_message = '';
	
	function WPGeo_Admin() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) );
		add_action( 'edit_attachment', array( $this, 'wpgeo_location_save_postdata' ) );
		add_action( 'save_post', array( $this, 'wpgeo_location_save_postdata' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
		add_action( 'after_plugin_row', array( $this, 'after_plugin_row' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}
	
	/**
	 * Admin Init
	 */
	function admin_init() {
		global $wpgeo;
		
		include_once( WPGEO_DIR . 'admin/editor.php' );
		include_once( WPGEO_DIR . 'admin/dashboard.php' );
		include_once( WPGEO_DIR . 'admin/settings.php' );
		
		// Register Settings
		$this->settings = new WPGeo_Settings();
		
		$this->map = new WPGeo_Map( 'admin_post' );
		
		add_action( 'admin_enqueue_scripts', array( $wpgeo, 'enqueue_scripts' ) );
		
		// Only show editor if Google API Key valid
		if ( $wpgeo->checkGoogleAPIKey() ) {
			if ( class_exists( 'WPGeo_Editor' ) ) {
				$this->editor = new WPGeo_Editor();
				$this->editor->add_buttons();
			}
		}
		
		// Dismiss Upgrade Message
		if ( isset( $_GET['wpgeo_action'] ) && $_GET['wpgeo_action'] = 'dismiss-update-msg' ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wpgeo_dismiss_update_msg' ) ) {
				update_option( 'wp_geo_show_version_msg', 'N' );
				$url = remove_query_arg( 'wpgeo_action', $_SERVER['PHP_SELF'] );
				$url = remove_query_arg( '_wpnonce', $url );
				wp_redirect( $url );
				exit();
			}
		}
	}
	
	/**
	 * Admin Notices
	 * Shows error message if WP Geo was unable to create the markers folder
	 * and if no Google API Key has been entered.
	 */
	function admin_notices(){
		global $wpgeo, $current_screen;
		if ( $current_screen->id == 'settings_page_wp-geo' ) {
			// Marker image folder message
			if ( ! $wpgeo->markers->marker_folder_exists() ) {
				echo '<div class="error"><p>' . sprintf( __( "Unable to create the markers folder %s.<br />Please create it and copy the marker images to it from %s", 'wp-geo' ), str_replace( ABSPATH, '', $wpgeo->markers->upload_dir ) . '/wp-geo/markers/', str_replace( ABSPATH, '', WPGEO_DIR ) . 'img/markers' ) . '</p></div>';
			}
			// Google API Key message
			if ( ! $wpgeo->checkGoogleAPIKey() ) {
				echo '<div class="error"><p>' . sprintf( __( "Before you can use WP Geo you must acquire a %s for your blog - the plugin will not function without it!", 'wp-geo' ), '<a href="https://developers.google.com/maps/documentation/javascript/v2/introduction#Obtaining_Key" target="_blank">' . __( 'Google API Key', 'wp-geo' ) . '</a>' ) . '</p></div>';
			}
		}
		// Version upgrade message
		if ( in_array( $current_screen->id, array( 'settings_page_wp-geo' ) ) ) {
			$wp_geo_show_version_msg = get_option( 'wp_geo_show_version_msg' );
			if ( current_user_can( 'manage_options' ) && $wp_geo_show_version_msg == 'Y' ) {
				echo '<div id="wpgeo_version_message" class="error below-h2" style="margin:5px 15px 2px 0px;">
						<p><strong style="color: #C00;">' . __( 'Important Notice: <a href="https://developers.google.com/maps/documentation/javascript/v2/reference">Version 2 of the Google Maps API is no longer available</a>', 'wp-geo' ) . '</strong><br />' . __( 'WP Geo has been updated to support Google Map API v3. The v2 API will be completed removed from future versions of WP Geo. You may need <a href="https://developers.google.com/maps/documentation/javascript/tutorial#api_key" target="_blank">create a new API key</a>, then update your WP Geo settings to use the Google Map API v3. If you have added custom code or plugins to work with WP Geo you may need to update them. Please <a href="https://github.com/benhuson/WP-Geo/issues">report bug issues here...</a>', 'wp-geo' ) . ' <a href="' . wp_nonce_url( add_query_arg( 'wpgeo_action', 'dismiss-update-msg', null ), 'wpgeo_dismiss_update_msg' ) . '">' . __( 'Dismiss', 'wp-geo' ) . '</a></p>
					</div>';
			}
		}
	}
	
	/**
	 * Admin Enqueue Scripts & Styles
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_style( 'wpgeo_admin', WPGEO_URL . 'css/wp-geo.css' );
	}
	
	/**
	 * Admin Head
	 * @todo Refactor mapScriptsInit()
	 */
	function admin_head() {
		global $wpgeo, $post_ID;
		
		// Only load if on a post or page
		if ( $wpgeo->show_maps() ) {
			$coord = get_wpgeo_post_coord( $post_ID );
			if ( ! $wpgeo->show_maps_external ) {
				echo $wpgeo->mapScriptsInit( $coord, 13, false, false );
			}
		}
	}
	
	/**
	 * Admin Menu
	 * Adds WP Geo settings page menu item.
	 */
	function admin_menu() {
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( __( 'WP Geo Options', 'wp-geo' ), __( 'WP Geo', 'wp-geo' ), 'manage_options', 'wp-geo', array( $this, 'options_page' ) );
		}
	}
	
	/**
	 * Options Page
	 */
	function options_page() {
		global $wpgeo;
		$wp_geo_options = get_option( 'wp_geo_options' );

		echo '<div class="wrap">
			<div id="icon-options-wpgeo" class="icon32" style="background: url(' . WPGEO_URL . 'img/logo/icon32.png) 2px 1px no-repeat;"><br></div>
			<h2>' . __( 'WP Geo Settings', 'wp-geo' ) . '</h2>
			<form action="options.php" method="post">';
		include( WPGEO_DIR . 'admin/donate-links.php' );
		
		do_settings_sections( 'wp_geo_options' );
		settings_fields( 'wp_geo_options' );
		echo '<p class="submit"><input type="submit" name="submit" value="' . __( 'Save Changes', 'wp-geo' ) . '" class="button-primary" /></p>
			</form>';
		echo '
				<h2 style="margin-top:30px;">' . __( 'Marker Settings', 'wp-geo' ) . '</h2>'
				. __( '<p>Custom marker images are automatically created in your WordPress uploads folder and used by WP Geo.<br />A copy of these images will remain in the WP Geo folder in case you need to revert to them at any time.<br />You may edit these marker icons if you wish - they must be PNG files. Each marker consist of a marker image and a shadow image. If you do not wish to show a marker shadow you should use a transparent PNG for the shadow file.</p><p>Currently you must update these images manually and the anchor point must be the same - looking to provide more control in future versions.</p>', 'wp-geo' ) . '
				' . $wpgeo->markers->get_admin_display();
		echo '<h2 style="margin-top:30px;">' . __( 'Documentation', 'wp-geo' ) . '</h2>'
			. __( '<p>If you set the Show Post Map setting to &quot;Manual&quot;, you can use the Shortcode <code>[wp_geo_map]</code> in a post to display a map (if a location has been set for the post). You can only include the Shortcode once within a post. If you select another Show Post Map option then the Shortcode will be ignored and the map will be positioned automatically.</p>', 'wp-geo' )
			. '</div>';
	}

	/**
	 * Add WP Geo Meta Boxes
	 *
	 * Adds meta boxes to all supported post types which have been regsitered using add_post_type_support().
	 * Use the wpgeo_add_post_type_support action to add/remove post type support.
	 */
	function add_meta_boxes() {
		global $wpgeo;

		// Check we can display a map
		if ( ! $wpgeo->checkGoogleAPIKey() ) {
			return;
		}

		// Only add for supported post types
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( $wpgeo->post_type_supports( $post_type ) ) {
				add_meta_box( 'wpgeo_location', __( 'WP Geo Location', 'wpgeo' ), array( $this, 'wpgeo_location_inner_custom_box' ), $post_type, 'advanced' );
			}
		}
	}

	/**
	 * WP Geo Location Inner Custom Box
	 */
	function wpgeo_location_inner_custom_box() {
		global $wpgeo, $post;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		$search   = '';
		$coord    = get_wpgeo_post_coord( $post->ID );
		$title    = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
		$marker   = get_post_meta( $post->ID, WPGEO_MARKER_META, true );
		$settings = WPGeo::get_post_map_settings( $post->ID );
		
		$wpgeo_map_settings_zoom = '';
		$wpgeo_map_settings_type = '';
		$wpgeo_map_settings_centre = '';
		$wpgeo_map_settings_zoom_checked = '';
		$wpgeo_map_settings_type_checked = '';
		$wpgeo_map_settings_centre_checked = '';
		
		$markers_menu = array(
			'selected'          => $marker,
			'echo'              => 0,
			'name'              => 'wp_geo_marker',
			'show_option_none'  => '(' . __( 'Use Default', 'wp-geo' ) . ')',
			'option_none_value' => ''
		);
		
		if ( ! empty( $settings['zoom'] ) ) {
			$wpgeo_map_settings_zoom = $settings['zoom'];
			$wpgeo_map_settings_zoom_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_zoom'] == 'Y' ) {
			$wpgeo_map_settings_zoom = $wp_geo_options['save_post_zoom'];
			$wpgeo_map_settings_zoom_checked = checked( true, true, false );
		}
		if ( ! empty( $settings['type'] ) ) {
			$wpgeo_map_settings_type = $settings['type'];
			$wpgeo_map_settings_type_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_map_type'] == 'Y' ) {
			$wpgeo_map_settings_type = $wp_geo_options['save_post_map_type'];
			$wpgeo_map_settings_type_checked = checked( true, true, false );
		}
		if ( ! empty( $settings['centre'] ) ) {
			$wpgeo_map_settings_centre = $settings['centre'];
			$wpgeo_map_settings_centre_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_centre_point'] == 'Y' ) {
			$wpgeo_map_settings_centre = $wp_geo_options['save_post_centre_point'];
			$wpgeo_map_settings_centre_checked = checked( true, true, false );
		}
		
		$map_html = $this->map->get_map_html( array(
			'classes' => array( 'wp_geo_map', 'wpgeo_map_admin_post' ),
			'styles'  => array(
				'width'   => '100%',
				'height'  => 300,
				'padding' => '0px',
				'margin'  => '0px'
			),
			'content' => __( 'Loading Google map, please wait...', 'wp-geo' )
		) );
		
		// Use nonce for verification
		echo '<input type="hidden" name="wpgeo_location_noncename" id="wpgeo_location_noncename" value="' . wp_create_nonce( 'wpgeo_edit_post' ) . '" />';
		
		// The actual fields for data entry
		echo '<table cellpadding="3" cellspacing="5" class="form-table">
			<tr>
				<th scope="row">' . __( 'Search for location', 'wp-geo' ) . '<br /><span style="font-weight:normal;">(' . __( 'town, postcode or address', 'wp-geo' ) . ')</span></th>
				<td><input name="wp_geo_search" type="text" size="45" id="wp_geo_search" value="' . $search . '" />
					<input type="hidden" name="wp_geo_base_country_code" id="wp_geo_base_country_code" value="' . apply_filters( 'wpgeo_base_country_code', '' ) . '" />
					<span class="submit"><input type="button" id="wp_geo_search_button" name="wp_geo_search_button" value="' . __( 'Search', 'wp-geo' ) . '" /></span></td>
			</tr>
			<tr>
				<td colspan="2">' . $map_html . '</td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Latitude', 'wp-geo' ) . ', ' . __( 'Longitude', 'wp-geo' ) . '</th>
				<td><input name="wp_geo_latitude" type="text" size="25" id="wp_geo_latitude" value="' . $coord->latitude() . '" /><br />
					<input name="wp_geo_longitude" type="text" size="25" id="wp_geo_longitude" value="' . $coord->longitude() . '" /><br />
					<a href="#" class="wpgeo-clear-location-fields">' . __( 'clear location', 'wp-geo' ) . '</a> | <a href="#" class="wpgeo-centre-location">' . __( 'centre location', 'wp-geo' ) . '</a>
				</td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Marker Title', 'wp-geo' ) . ' <small>(' . __( 'optional', 'wp-geo' ) . ')</small></th>
				<td><input name="wp_geo_title" type="text" size="25" style="width:100%;" id="wp_geo_title" value="' . $title . '" /></td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Marker Image', 'wp-geo' ) . '</th>
				<td>' . $wpgeo->markers->dropdown_markers( $markers_menu ) . '</td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Map Settings', 'wp-geo' ) . '</th>
				<td>
					<label for="wpgeo_map_settings_zoom"><input type="checkbox" name="wpgeo_map_settings_zoom" id="wpgeo_map_settings_zoom" value="' . $wpgeo_map_settings_zoom . '" ' . $wpgeo_map_settings_zoom_checked . ' /> ' . __( 'Save custom map zoom for this post', 'wp-geo' ) . '</label><br />
					<label for="wpgeo_map_settings_type"><input type="checkbox" name="wpgeo_map_settings_type" id="wpgeo_map_settings_type" value="' . $wpgeo_map_settings_type . '" ' . $wpgeo_map_settings_type_checked . ' /> ' . __( 'Save custom map type for this post', 'wp-geo' ) . '</label><br />
					<label for="wpgeo_map_settings_centre"><input type="checkbox" name="wpgeo_map_settings_centre" id="wpgeo_map_settings_centre" value="' . $wpgeo_map_settings_centre . '" ' . $wpgeo_map_settings_centre_checked . ' /> ' . __( 'Save map centre point for this post', 'wp-geo' ) . '</label>
				</td>
			</tr>
			' . apply_filters( 'wpgeo_edit_post_map_fields', '', $post->ID ) . '
		</table>';
	}
	
	/**
	 * WP Geo Location Save post data
	 * When the post is saved, saves our custom data.
	 *
	 * @todo Use update_post_meta() where appropriate, rather than always adding/deleting.
	 *
	 * @param int $post_id Post ID.
	 */
	function wpgeo_location_save_postdata( $post_id ) {
		global $wpgeo;

		if ( ! $wpgeo->checkGoogleAPIKey() ) {
			return;
		}
		
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['wpgeo_location_noncename'] ) || ! wp_verify_nonce( $_POST['wpgeo_location_noncename'], plugin_basename( 'wpgeo_edit_post' ) ) ) {
			return $post_id;
		}
		
		// Authenticate user
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( 'post' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		} elseif ( function_exists( 'get_post_type_object' ) ) {
			$post_type = get_post_type_object( $_POST['post_type'] );
			// @todo Should this be "edit_" . $post_type->capability_type
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return $post_id;
			}
		}
		
		$mydata = array();
		
		// Find and save the location data
		if ( isset( $_POST['wp_geo_latitude'] ) && isset( $_POST['wp_geo_longitude'] ) ) {
			
			// Only delete post meta if isset (to avoid deletion in bulk/quick edit mode)
			delete_post_meta( $post_id, WPGEO_LATITUDE_META );
			delete_post_meta( $post_id, WPGEO_LONGITUDE_META );
			
			$coord = new WPGeo_Coord( $_POST['wp_geo_latitude'], $_POST['wp_geo_longitude'] );
			if ( $coord->is_valid_coord() ) {
				add_post_meta( $post_id, WPGEO_LATITUDE_META, $coord->latitude() );
				add_post_meta( $post_id, WPGEO_LONGITUDE_META, $coord->longitude() );
				$mydata[WPGEO_LATITUDE_META]  = $coord->latitude();
				$mydata[WPGEO_LONGITUDE_META] = $coord->longitude();
			}
		}
		
		// Find and save the title data
		if ( isset($_POST['wp_geo_title']) ) {
			delete_post_meta( $post_id, WPGEO_TITLE_META );
			if ( ! empty( $_POST['wp_geo_title'] ) ) {
				add_post_meta( $post_id, WPGEO_TITLE_META, $_POST['wp_geo_title'] );
				$mydata[WPGEO_TITLE_META] = $_POST['wp_geo_title'];
			}
		}
		
		// Find and save the marker data
		if ( isset( $_POST['wp_geo_marker'] ) ) {
			if ( ! empty($_POST['wp_geo_marker'] ) ) {
				update_post_meta( $post_id, WPGEO_MARKER_META, $_POST['wp_geo_marker'] );
				$mydata[WPGEO_MARKER_META] = $_POST['wp_geo_marker'];
			} else {
				delete_post_meta( $post_id, WPGEO_MARKER_META );
			}
		}
		
		// Find and save the settings data
		delete_post_meta( $post_id, WPGEO_MAP_SETTINGS_META );
		$settings = array();
		if ( isset( $_POST['wpgeo_map_settings_zoom'] ) && ! empty( $_POST['wpgeo_map_settings_zoom'] ) ) {
			$settings['zoom'] = $_POST['wpgeo_map_settings_zoom'];
		}
		if ( isset( $_POST['wpgeo_map_settings_type'] ) && ! empty( $_POST['wpgeo_map_settings_type'] ) ) {
			$settings['type'] = $wpgeo->decode_api_string( $_POST['wpgeo_map_settings_type'], 'maptype' );
		}
		if ( isset( $_POST['wpgeo_map_settings_centre'] ) && ! empty( $_POST['wpgeo_map_settings_centre'] ) ) {
			$settings['centre'] = $_POST['wpgeo_map_settings_centre'];
		}

		add_post_meta( $post_id, WPGEO_MAP_SETTINGS_META, $settings );
		$mydata[WPGEO_MAP_SETTINGS_META] = $settings;
		
		return $mydata;
	}

	/**
	 * Plugin Row Meta
	 *
	 * Adds documentation, support and issue links below the plugin description on the plugins page.
	 *
	 * @param   array   $plugin_meta  Plugin meta display array.
	 * @param   string  $plugin_file  Plugin reference.
	 * @param   array   $plugin_data  Plugin data.
	 * @param   string  $status       Plugin status.
	 * @return  array                 Plugin meta array.
	 */
	function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( 'wp-geo/wp-geo.php' == $plugin_file ) {
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'http://github.com/benhuson/wp-geo/wiki', 'wp-geo' ), __( 'Documentation', 'wp-geo' ) );
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'http://wordpress.org/support/plugin/wp-geo', 'wp-geo' ), __( 'Support Forum', 'wp-geo' ) );
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'http://github.com/benhuson/wp-geo/issues', 'wp-geo' ), __( 'Submit an Issue', 'wp-geo' ) );
		}
		return $plugin_meta;
	}

	/**
	 * After Plugin Row
	 *
	 * This function can be used to insert text after the WP Geo plugin row on the plugins page.
	 * Useful if you need to tell people something important before they upgrade.
	 *
	 * @param  string  $plugin  Plugin reference.
	 */
	function after_plugin_row( $plugin ) {
		if ( 'wp-geo/wp-geo.php' == $plugin && ! empty( $this->plugin_message ) ) {
			echo '<tr><td colspan="3" class="plugin-update colspanchange" style="line-height:1.2em;"><div class="update-message" style="color:#CC0000;padding-top:3px;">' . $this->plugin_message . '</div></td></tr>';
		}
	}

}
