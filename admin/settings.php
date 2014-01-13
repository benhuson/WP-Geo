<?php

/**
 * WP Geo Settings
 */
class WPGeo_Settings {
	
	/**
	 * Constructor
	 */
	function WPGeo_Settings() {
		$this->register_settings();
		$this->filter_plugin_action_links();
	}
	
	/**
	 * Settings API
	 */
	function register_settings() {
		
		// General Settings
		add_settings_section( 'wpgeo_api', __( 'API Settings', 'wp-geo' ), array( $this, 'api_settings_section' ), 'wp_geo_options' );
		add_settings_field( 'public_api', __( 'Public API', 'wp-geo' ), array( $this, 'public_api_field' ), 'wp_geo_options', 'wpgeo_api' );
		add_settings_field( 'admin_api', __( 'Admin API', 'wp-geo' ), array( $this, 'admin_api_field' ), 'wp_geo_options', 'wpgeo_api' );
		add_settings_field( 'google_api_key', __( 'Google API Key', 'wp-geo' ), array( $this, 'google_api_key_field' ), 'wp_geo_options', 'wpgeo_api' );
		add_settings_section( 'wpgeo_general', __( 'General Settings', 'wp-geo' ), array( $this, 'general_settings_section' ), 'wp_geo_options' );
		add_settings_field( 'google_map_type', __( 'Map Type', 'wp-geo' ), array( $this, 'google_map_type_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'show_post_map', __( 'Show Post Map', 'wp-geo' ), array( $this, 'show_post_map_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_map_location', __( 'Default Map Location', 'wp-geo' ), array( $this, 'default_map_location_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_map_width', __( 'Default Map Width', 'wp-geo' ), array( $this, 'default_map_width_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_map_height', __( 'Default Map Height', 'wp-geo' ), array( $this, 'default_map_height_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_map_zoom', __( 'Default Map Zoom', 'wp-geo' ), array( $this, 'default_map_zoom_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_map_controls', __( 'Default Map Controls', 'wp-geo' ), array( $this, 'default_map_controls_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'default_post_options', __( 'Default Post Options', 'wp-geo' ), array( $this, 'default_post_options_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'show_polylines', __( 'Polylines', 'wp-geo' ), array( $this, 'show_polylines_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'polyline_colour', __( 'Polyline Colour', 'wp-geo' ), array( $this, 'polyline_colour_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'supported_post_types', __( 'Supported Post Types', 'wp-geo' ), array( $this, 'supported_post_types_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'show_maps_on', __( 'Show Maps On', 'wp-geo' ), array( $this, 'show_maps_on_field' ), 'wp_geo_options', 'wpgeo_general' );
 		add_settings_field( 'feeds', __( 'Feeds', 'wp-geo' ), array( $this, 'feeds_field' ), 'wp_geo_options', 'wpgeo_general' );
 		
 		// Register Settings
 		register_setting( 'wp_geo_options', 'wp_geo_options', array( $this, 'wp_geo_options_validation' ) );
	}
	
	/**
	 * Options Validation
	 */
	function wp_geo_options_validation( $input ) {
		// Ensure unchecked checkboxes are set to 'N'
		$input = wp_parse_args( $input, array(
			'show_map_type_normal'          => 'N',
			'show_map_type_satellite'       => 'N',
			'show_map_type_hybrid'          => 'N',
			'show_map_type_physical'        => 'N',
			'show_map_scale'                => 'N',
			'show_map_overview'             => 'N',
			'show_streetview_control'       => 'N',
			'save_post_zoom'                => 'N',
			'save_post_map_type'            => 'N',
			'save_post_centre_point'        => 'N',
			'show_polylines'                => 'N',
			'supported_post_types'          => array(),
			'show_maps_on_home'             => 'N',
			'show_maps_on_pages'            => 'N',
			'show_maps_on_posts'            => 'N',
			'show_maps_in_datearchives'     => 'N',
			'show_maps_in_categoryarchives' => 'N',
			'show_maps_in_tagarchives'      => 'N',
			'show_maps_in_taxarchives'      => 'N',
			'show_maps_in_authorarchives'   => 'N',
			'show_maps_in_searchresults'    => 'N',
			'show_maps_on_excerpts'         => 'N',
			'add_geo_information_to_rss'    => 'N'
		) );
		return $input;
	}
	
	/**
	 * API Settings Section
	 */
	function api_settings_section() {
		echo '';
	}

	/**
	 * Public API Field
	 */
	function public_api_field() {
		$options = get_option( 'wp_geo_options' );
		$menu_options = array(
			'googlemapsv2' => __( 'Google Maps v2', 'wp-geo' ),
			'googlemapsv3' => __( 'Google Maps v3', 'wp-geo' )
		);
		echo wpgeo_select( 'wp_geo_options[public_api]', $menu_options, $options['public_api'], false, 'public_api' );
	}

	/**
	 * Admin API Field
	 */
	function admin_api_field() {
		$options = get_option( 'wp_geo_options' );
		$menu_options = array(
			'googlemapsv2' => __( 'Google Maps v2', 'wp-geo' ),
			'googlemapsv3' => __( 'Google Maps v3', 'wp-geo' )
		);
		echo wpgeo_select( 'wp_geo_options[admin_api]', $menu_options, $options['admin_api'], false, 'admin_api' );
	}

	/**
	 * Google API Key Field
	 */
	function google_api_key_field() {
		$options = get_option( 'wp_geo_options' );
		echo '<input name="wp_geo_options[google_api_key]" type="text" id="google_api_key" value="' . $options['google_api_key'] . '" class="regular-text" />';
	}

	/**
	 * General Settings Section
	 */
	function general_settings_section() {
		echo '<p>'
			. sprintf( __( "For more information and documentation about this plugin please visit the <a %s>WP Geo Plugin</a> home page.", 'wp-geo' ), 'href="http://www.benhuson.co.uk/wordpress-plugins/wp-geo/"' ) . '<br />'
			. sprintf( __( "If you experience any problems/bugs with the plugin, please <a %s>log it here</a>.", 'wp-geo' ), 'href="http://code.google.com/p/wp-geo/issues/list"' ) . 
			'</p>';
	}

	/**
	 * Google Map Type Field
	 */
	function google_map_type_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo $wpgeo->google_map_types( 'menu', $options['google_map_type'], array( 'id' => 'google_map_type', 'name' => 'wp_geo_options[google_map_type]' ) );
	}

	/**
	 * Show Post Map Field
	 */
	function show_post_map_field() {
		global $wpgeo;

		$options = get_option( 'wp_geo_options' );
		$menu_options = array(
			'TOP'    => __( 'At top of post', 'wp-geo' ),
			'BOTTOM' => __( 'At bottom of post', 'wp-geo' ),
			'HIDE'   => __( 'Manually', 'wp-geo' )
		);
		echo wpgeo_select( 'wp_geo_options[show_post_map]', $menu_options, $options['show_post_map'], false, 'show_post_map' ) . '<br />';
		echo wpgeo_checkbox( 'wp_geo_options[show_maps_on_excerpts]', 'Y', $options['show_maps_on_excerpts'], false, 'show_maps_on_excerpts' ) . ' ' . __( 'Show on excerpts', 'wp-geo' );
	}

	/**
	 * Default Map Location Field
	 */
	function default_map_location_field() {
		$options = get_option( 'wp_geo_options' );
		echo '<p>' . __( 'When creating a new post, the map will default to focussing on this area for you to position a marker.', 'wp-geo' ) . '</p>';
		echo '<table class="wpgeo-inner-admin-table">';
		echo '<tr><th><label for="default_map_latitude">' . __( 'Latitude', 'wp-geo' ) . '</label></th><td><input name="wp_geo_options[default_map_latitude]" type="text" id="default_map_latitude" value="' . $options['default_map_latitude'] . '" size="25" /></td></tr>';
		echo '<tr><th><label for="default_map_longitude">' . __( 'Longitude', 'wp-geo' ) . '</label></th><td><input name="wp_geo_options[default_map_longitude]" type="text" id="default_map_longitude" value="' . $options['default_map_longitude'] . '" size="25" /></td></tr>';
		echo '</table>';
	}

	/**
	 * Default Map Width Field
	 */
	function default_map_width_field() {
		$options = get_option( 'wp_geo_options' );
		echo '<input name="wp_geo_options[default_map_width]" type="text" id="default_map_width" value="' . $options['default_map_width'] . '" size="10" />';
	}

	/**
	 * Default Map Height Field
	 */
	function default_map_height_field() {
		$options = get_option( 'wp_geo_options' );
		echo '<input name="wp_geo_options[default_map_height]" type="text" id="default_map_height" value="' . $options['default_map_height'] . '" size="10" />';
	}

	/**
	 * Default Map Height Field
	 */
	function default_map_zoom_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo $wpgeo->selectMapZoom( 'menu', $options['default_map_zoom'], array( 'id' => 'default_map_zoom', 'name' => 'wp_geo_options[default_map_zoom]' ) );
	}

	/**
	 * Default Map Controls Field
	 */
	function default_map_controls_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo $wpgeo->selectMapControl( 'menu', $options['default_map_control'], array( 'id' => 'default_map_control', 'name' => 'wp_geo_options[default_map_control]' )  ). '<br />';
		echo '<p style="margin:1em 0 0 0;"><strong>' . __( 'Map Type Controls', 'wp-geo' ) . '</strong></p>';
		echo '<p style="margin:0;">' . __( 'You must select at least 2 map types for the control to show.', 'wp-geo' ) . '</p>';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_type_normal]', 'Y', $options['show_map_type_normal'], false, 'show_map_type_normal' ) . ' ' . __( 'Normal map', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_type_satellite]', 'Y', $options['show_map_type_satellite'], false, 'show_map_type_satellite' ) . ' ' . __( 'Satellite (photographic map)', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_type_hybrid]', 'Y', $options['show_map_type_hybrid'], false, 'show_map_type_hybrid' ) . ' ' . __( 'Hybrid (photographic map with normal features)', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_type_physical]', 'Y', $options['show_map_type_physical'], false, 'show_map_type_physical' ) . ' ' . __( 'Physical (terrain map)', 'wp-geo' ) . '</label><br />';
		echo '<p style="margin:1em 0 0 0;"><strong>' . __( 'Other Controls', 'wp-geo' ) . '</strong></p>';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_scale]', 'Y', $options['show_map_scale'], false, 'show_map_scale' ) . ' ' . __( 'Show map scale', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_map_overview]', 'Y', $options['show_map_overview'], false, 'show_map_overview' ) . ' ' . __( 'Show collapsible overview map (in the corner of the map)', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_streetview_control]', 'Y', $options['show_streetview_control'], false, 'show_streetview_control' ) . ' ' . __( 'Show Street View Control', 'wp-geo' ) . '</label>';
	}

	/**
	 * Default Post Options Field
	 */
	function default_post_options_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[save_post_zoom]', 'Y', $options['save_post_zoom'], false, 'save_post_zoom' ) . ' ' . __( 'Save custom map zoom for this post', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[save_post_map_type]', 'Y', $options['save_post_map_type'], false, 'save_post_map_type' ) . ' ' . __( 'Save custom map type for this post', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[save_post_centre_point]', 'Y', $options['save_post_centre_point'], false, 'save_post_centre_point' ) . ' ' . __( 'Save map centre point for this post', 'wp-geo' ) . '</label>';
	
	}

	/**
	 * Show Polylines Field
	 */
	function show_polylines_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_polylines]', 'Y', $options['show_polylines'], false, 'show_polylines' ) . ' ' . __( 'Show polylines (to connect multiple points on a single map)', 'wp-geo' ) . '</label>';
					
	}

	/**
	 * Polyline Colour Field
	 */
	function polyline_colour_field() {
		$options = get_option( 'wp_geo_options' );
		echo '<input name="wp_geo_options[polyline_colour]" type="text" id="polyline_colour" value="' . $options['polyline_colour'] . '" size="7" />';
	}

	/**
	 * Get All UI Supporting Post Types
	 *
	 * Returns an array of all post types (as objects) that support the WordPress admin UI.
	 * We should be able to add meta boxes to all of these.
	 *
	 * @param   array  $args  Parameters for get_post_types().
	 * @return  array         Array of post type objects.
	 */
	function get_all_ui_supporting_post_types( $args = null ) {
		$post_types = get_post_types( $args, 'objects' );
		return wp_list_filter( $post_types, array( 'show_ui' => 1 ) );
	}

	/**
	 * Supported Post Types Field
	 */
	function supported_post_types_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );

		$post_types = $this->get_all_ui_supporting_post_types();
		foreach ( $post_types as $post_type => $data ) {
			$checked = is_array( $options['supported_post_types'] ) && in_array( $post_type, $options['supported_post_types'] ) ? $post_type : '';
			$disabled = false;
			if ( post_type_supports( $post_type, 'wpgeo' ) ) {
				$disabled = true;
				$checked = $post_type;
			}
			echo '<label>' . wpgeo_checkbox( 'wp_geo_options[supported_post_types][]', $post_type, $checked, $disabled, 'supported_post_types_' . $post_type ) . ' ' . $data->label . '</label><br />';
		}
	}

	/**
	 * Show Maps On Field
	 */
	function show_maps_on_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );

		echo '<strong>' . __( 'Single Pages', 'wp-geo' ) . '</strong><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_on_pages]', 'Y', $options['show_maps_on_pages'], false, 'show_maps_on_pages' ) . ' ' . __( 'Pages', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_on_posts]', 'Y', $options['show_maps_on_posts'], false, 'show_maps_on_posts' ) . ' ' . __( 'Posts', 'wp-geo' ) . '</label><br />';

		// Custom Post Types
		$custom_post_type_checkboxes = '';
		$post_types = get_post_types( array( '_builtin' => false ), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->query_var, 'wpgeo' )) {
				$custom_post_type_checkboxes .= '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_on_customposttypes][' . $post_type->query_var . ']', 'Y', 'Y', true ) . ' ' . __( $post_type->label, 'wp-geo' ) . '</label><br />';
			} elseif ( $post_type->show_ui ) {
				$custom_post_type_checkbox_value = isset( $options['show_maps_on_customposttypes'][$post_type->query_var] ) ? $options['show_maps_on_customposttypes'][$post_type->query_var] : '';
				$custom_post_type_checkboxes .= '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_on_customposttypes][' . $post_type->query_var . ']', 'Y', $custom_post_type_checkbox_value, false ) . ' ' . __( $post_type->label, 'wp-geo' ) . '</label><br />';
			}
		}
		if ( ! empty( $custom_post_type_checkboxes ) ) {
			echo $custom_post_type_checkboxes;
		}

		echo '<strong>' . __( 'Posts on Taxonomy Pages', 'wp-geo' ) . '</strong><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_categoryarchives]', 'Y', $options['show_maps_in_categoryarchives'], false, 'show_maps_in_categoryarchives' ) . ' ' . __( 'Categories', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_tagarchives]', 'Y', $options['show_maps_in_tagarchives'], false, 'show_maps_in_tagarchives' ) . ' ' . __( 'Tags', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_taxarchives]', 'Y', $options['show_maps_in_taxarchives'], false, 'show_maps_in_taxarchives' ) . ' ' . __( 'All Taxonomies', 'wp-geo' ) . '</label><br />';

		echo '<strong>' . __( 'Posts on Other Pages', 'wp-geo' ) . '</strong><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_on_home]', 'Y', $options['show_maps_on_home'], false, 'show_maps_on_home' ) . ' ' . __( 'Posts Archive', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_datearchives]', 'Y', $options['show_maps_in_datearchives'], false, 'show_maps_in_datearchives' ) . ' ' . __( 'Date Archives', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_authorarchives]', 'Y', $options['show_maps_in_authorarchives'], false, 'show_maps_in_authorarchives' ) . ' ' . __( 'Author Archives', 'wp-geo' ) . '</label><br />';
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[show_maps_in_searchresults]', 'Y', $options['show_maps_in_searchresults'], false, 'show_maps_in_searchresults' ) . ' ' . __( 'Search Results', 'wp-geo' ) . '</label><br />';
	}
	
	/**
	 * Feeds Field
	 */
	function feeds_field() {
		global $wpgeo;
		$options = get_option( 'wp_geo_options' );
		echo '<label>' . wpgeo_checkbox( 'wp_geo_options[add_geo_information_to_rss]', 'Y', $options['add_geo_information_to_rss'], false, 'add_geo_information_to_rss' ) . ' ' . __( 'Add geographic information', 'wp-geo' ) . '</label>';
	}
	
	/**
	 * Filter Plugin Action Links
	 */
	function filter_plugin_action_links() {
		add_filter( 'plugin_action_links', array( $this, 'wpgeo_filter_plugin_action_links' ), 10, 2 );
	}
	
	/**
	 * Based on the Sociable plugin, this adds a 'Settings' option
	 * to the entry on the WP Plugins page.
	 *
	 * @param array $links The array of links displayed by the plugins page
	 * @param string $file The current plugin being filtered.
	 * @return array Array of links.
	 */
	function wpgeo_filter_plugin_action_links( $links, $file ) {
		if ( $file == 'wp-geo/wp-geo.php' ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=wp-geo' ) . '">' . __( 'Settings', 'wp-geo' ) . '</a>';
			if ( ! in_array( $settings_link, $links ) ) {
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
	
}
