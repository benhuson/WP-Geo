<?php

/**
 * WP Geo class
 * The main WP Geo class - this is where it all happens.
 */
class WPGeo {
	
	// Version Information
	var $version    = '3.2.6.4';
	var $db_version = 1;
	
	var $markers;
	var $show_maps_external = false;
	var $plugin_message = '';
	var $maps;
	var $maps2;
	var $editor;
	var $settings;
	var $feeds;
	
	var $default_map_latitude  = '51.492526418807465';
	var $default_map_longitude = '-0.15754222869873047';
	
	/**
	 * Constructor
	 */
	function WPGeo() {
		
		// Version
		$wp_geo_version = get_option( 'wp_geo_version' );
		if ( empty( $wp_geo_version ) || version_compare( $wp_geo_version, $this->version, '<' ) ) {
			update_option( 'wp_geo_show_version_msg', 'Y' );
			update_option( 'wp_geo_version', $this->version );
		}
		
		$this->maps    = array();
		$this->maps2   = new WPGeo_Maps();
		$this->markers = new WPGeo_Markers();
		$this->feeds   = new WPGeo_Feeds();
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Version upgrade message
	 */
	function version_upgrade_msg() {
		$wp_geo_show_version_msg = get_option( 'wp_geo_show_version_msg' );
		if ( current_user_can( 'manage_options' ) && $wp_geo_show_version_msg == 'Y' ) {
			echo '<div id="wpgeo_version_message" class="error below-h2" style="margin:5px 15px 2px 0px;">
					<p>WP Geo has been updated to use the WordPress widgets API. You will need to re-add your widgets. <a href="' . wp_nonce_url( add_query_arg( 'wpgeo_action', 'dismiss-update-msg', $_SERVER['PHP_SELF'] ), 'wpgeo_dismiss_update_msg' ) . '">Dismiss</a></p>
				</div>';
		}
	}
	
	/**
	 * Register Activation
	 * Runs when the plugin is activated - creates options etc.
	 */
	function register_activation() {
		$wpgeo = new WPGeo();
		
		$options = array(
			'google_api_key'                => '', 
			'google_map_type'               => 'G_NORMAL_MAP', 
			'show_post_map'                 => 'TOP', 
			'default_map_latitude'          => '51.492526418807465',
			'default_map_longitude'         => '-0.15754222869873047',
			'default_map_width'             => '100%', 
			'default_map_height'            => '300px',
			'default_map_zoom'              => '5',
			'default_map_control'           => 'GLargeMapControl3D',
			'show_map_type_normal'          => 'Y',
			'show_map_type_satellite'       => 'Y',
			'show_map_type_hybrid'          => 'Y',
			'show_map_type_physical'        => 'Y',
			'show_map_scale'                => 'N',
			'show_map_overview'             => 'N',
			'save_post_zoom'                => 'N',
			'save_post_map_type'            => 'N',
			'save_post_centre_point'        => 'N',
			'show_polylines'                => 'Y',
			'polyline_colour'               => '#FFFFFF',
			'show_maps_on_home'             => 'Y',
			'show_maps_on_pages'            => 'Y',
			'show_maps_on_posts'            => 'Y',
			'show_maps_in_datearchives'     => 'Y',
			'show_maps_in_categoryarchives' => 'Y',
			'show_maps_in_tagarchives'      => 'Y',
			'show_maps_in_taxarchives'      => 'Y',
			'show_maps_in_authorarchives'   => 'Y',
			'show_maps_in_searchresults'    => 'N',
			'show_maps_on_excerpts'         => 'N',
			'add_geo_information_to_rss'    => 'Y'
		);
		// @todo Rather than add_option() check values and use update?
		add_option( 'wp_geo_options', $options );
		$wp_geo_options = get_option( 'wp_geo_options' );
		foreach ( $options as $key => $val ) {
			if ( ! isset( $wp_geo_options[$key] ) ) {
				$wp_geo_options[$key] = $options[$key];
			} elseif ( empty( $wp_geo_options[$key] ) && in_array( $key, array( 'default_map_latitude', 'default_map_longitude' ) ) ) {
				$wp_geo_options[$key] = $options[$key];
			}
		}
		update_option( 'wp_geo_options', $wp_geo_options );
		
		// Files
		$wpgeo->markers->register_activation();
	}
	
	/**
	 * Is WP Geo Feed?
	 * Detects whether this is a WP Geo feed.
	 *
	 * @return boolean
	 */
	function is_wpgeo_feed() {
		if ( is_feed() && isset( $_GET['wpgeo'] ) ) {
			if ( $_GET['wpgeo'] == 'true' ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Post Limits
	 * Removes limit on WP Geo feed to show all posts.
	 *
	 * @param int $limit Current limit.
	 * @return int New Limit.
	 */
	function post_limits( $limit ) {
		global $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			if ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) ) {
				return 'LIMIT 0, ' . $_GET['limit'];
			}
		}
		return $limit;
	}
	
	/**
	 * Posts Join
	 * Joins the post meta tables onto the results of the posts table.
	 *
	 * @param string $join Current JOIN statement.
	 * @return string Updated JOIN string.
	 */
	function posts_join( $join ) {
		global $wpdb, $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			$join .= " LEFT JOIN $wpdb->postmeta ON (" . $wpdb->posts . ".ID = $wpdb->postmeta.post_id)";
		}
		return $join;
	}
	
	/**
	 * Posts Where
	 * Adds extra WHERE clause to the posts results to only include posts with longitude and latitude.
	 *
	 * @param string $where Current WHERE statement.
	 * @return string Updated WHERE string.
	 */
	function posts_where( $where ) {
		global $wpdb, $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			$where .= " AND ($wpdb->postmeta.meta_key = '" . WPGEO_LATITUDE_META . "' OR $wpdb->postmeta.meta_key = '" . WPGEO_LONGITUDE_META . "')";
		}
		return $where;
	}
	
	/**
	 * Check Google API Key
	 * Check that a Google API Key has been entered.
	 *
	 * @return boolean
	 */
	function checkGoogleAPIKey() {
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$api_key = $wpgeo->get_google_api_key();
		if ( empty( $api_key ) || ! isset( $api_key ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Get Google API Key
	 * Gets the Google API Key. Passes it through a filter so it can be overriden by another plugin.
	 *
	 * @return string API Key.
	 */
	function get_google_api_key() {
		$wp_geo_options = get_option( 'wp_geo_options' );
		return apply_filters( 'wpgeo_google_api_key', $wp_geo_options['google_api_key'] );
	}
	
	/**
	 * Category Map
	 * Outputs the HTML for a category map.
	 *
	 * @param array $args Arguments.
	 */
	function categoryMap( $args = null ) {
		global $post;
		
		$posts = array();
		while ( have_posts() ) {
			the_post();
			$posts[] = $post;
		}
		rewind_posts();
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$showmap = false;
		
		// Extract args
		$allowed_args = array(
			'width'  => $wp_geo_options['default_map_width'],
			'height' => $wp_geo_options['default_map_height']
		);
		$args = wp_parse_args( $args, $allowed_args );
		
		for ( $i = 0; $i < count( $posts ); $i++ ) {
			$post = $posts[$i];
			$latitude  = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
			$longitude = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
			if ( wpgeo_is_valid_geo_coord( $latitude, $longitude ) ) {
				$showmap = true;
			}
		}
		
		if ( $showmap && ! is_feed() && $this->checkGoogleAPIKey() ) {
			echo '<div class="wp_geo_map" id="wp_geo_map_visible" style="width:' . wpgeo_css_dimension( $args['width'] ) . '; height:' . wpgeo_css_dimension( $args['height'] ) . ';"></div>';
		}
	}
	
	/**
	 * Meta Tags
	 * Outputs geo-related meta tags.
	 */
	function meta_tags() {
		global $post;
		if ( is_single() ) {
			$lat   = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
			$long  = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
			$title = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
			$nl = "\n";
			
			if ( wpgeo_is_valid_geo_coord( $lat, $long ) ) {
				echo '<meta name="geo.position" content="' . $lat . ';' . $long . '" />' . $nl; // Geo-Tag: Latitude and longitude
				//echo '<meta name="geo.region" content="DE-BY" />' . $nl;                      // Geo-Tag: Country code (ISO 3166-1) and regional code (ISO 3166-2)
				//echo '<meta name="geo.placename" content="MÙnchen" />' . $nl;                 // Geo-Tag: City or the nearest town
				if ( ! empty( $title ) ) {
					echo '<meta name="DC.title" content="' . $title . '" />' . $nl;             // Dublin Core Meta Tag Title (used by some geo databases)
				}
				echo '<meta name="ICBM" content="' . $lat . ', ' . $long . '" />' . $nl;        // ICBM Tag (prior existing equivalent to the geo.position)
			}
		}
	}
	
	/**
	 * Enqueue scripts and styles
	 */
	function enqueue_scripts() {
		wp_register_style( 'wpgeo', WPGEO_URL . 'css/wp-geo.css' );
		wp_enqueue_style( 'wpgeo' );
	}
	
	/**
	 * WP Head
	 * Outputs HTML and JavaScript to the header.
	 */
	function wp_head() {
		global $wpgeo, $post;
		
		$js_map_inits = '';
		$js_marker_inits = '';
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		$this->meta_tags();
		
		$controltypes = array();
		if ( $wp_geo_options['show_map_type_normal'] == 'Y' )
			$controltypes[] = 'G_NORMAL_MAP';
		if ( $wp_geo_options['show_map_type_satellite'] == 'Y' )
			$controltypes[] = 'G_SATELLITE_MAP';
		if ( $wp_geo_options['show_map_type_hybrid'] == 'Y' )
			$controltypes[] = 'G_HYBRID_MAP';
		if ( $wp_geo_options['show_map_type_physical'] == 'Y' )
			$controltypes[] = 'G_PHYSICAL_MAP';
		
		echo '
			<script type="text/javascript">
			//<![CDATA[
			
			// WP Geo default settings
			var wpgeo_w = \'' . $wp_geo_options['default_map_width'] . '\';
			var wpgeo_h = \'' . $wp_geo_options['default_map_height'] . '\';
			var wpgeo_type = \'' . $wp_geo_options['google_map_type'] . '\';
			var wpgeo_zoom = ' . $wp_geo_options['default_map_zoom'] . ';
			var wpgeo_controls = \'' . $wp_geo_options['default_map_control'] . '\';
			var wpgeo_controltypes = \'' . implode(",", $controltypes) . '\';
			var wpgeo_scale = \'' . $wp_geo_options['show_map_scale'] . '\';
			var wpgeo_overview = \'' . $wp_geo_options['show_map_overview'] . '\';
			
			//]]>
			</script>
			';
		
		if ( $wpgeo->show_maps() || $wpgeo->widget_is_active() ) {
			$posts = array();
			while( have_posts() ) {
				the_post();
				$posts[] = $post;
			}
			rewind_posts();
			
			$this->markers->wp_head();
			
			$wp_geo_options = get_option( 'wp_geo_options' );
			$maptype = empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];
			$mapzoom = $wp_geo_options['default_map_zoom'];
			
			// Coords to show on map?
			$coords = array();
			for ( $i = 0; $i < count( $posts ); $i++ ) {
				$post      = $posts[$i];
				$latitude  = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
				$longitude = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
				$title     = get_wpgeo_title( $post->ID );
				$marker    = get_post_meta( $post->ID, WPGEO_MARKER_META, true );
				$settings  = get_post_meta( $post->ID, WPGEO_MAP_SETTINGS_META, true );
				
				$mymaptype = $maptype;
				if ( isset( $settings['type'] ) && ! empty( $settings['type'] ) ) {
					$mymaptype = $settings['type'];
				}
				$mymapzoom = $mapzoom;
				if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
					$mymapzoom = $settings['zoom'];
				}
				
				if ( wpgeo_is_valid_geo_coord( $latitude, $longitude ) ) {
					$push = array(
						'id'        => $post->ID,
						'latitude'  => $latitude,
						'longitude' => $longitude,
						'title'     => $title,
						'link'      => get_permalink( $post->ID ),
						'post'      => $post
					);
					array_push( $coords, $push );
					
					// ----------- Start - Create maps for visible posts and pages -----------
					$map = new WPGeo_Map( $post->ID );
					
					// Add point
					$marker_large = empty( $marker ) ? 'large' : $marker;
					$icon = apply_filters( 'wpgeo_marker_icon', $marker_large, $post, 'post' );
					$map->addPoint( $latitude, $longitude, $icon, $title, get_permalink( $post->ID ) );
					$map->setMapZoom( $mymapzoom );
					$map->setMapType( $mymaptype );
					
					if ( ! empty( $settings['centre'] ) ) {
						$centre = explode( ',', $settings['centre'] );
						if ( is_array( $centre ) && count( $centre ) == 2 ) {
							$map->setMapCentre( $centre[0], $centre[1] );
						} else {
							$map->setMapCentre( $latitude, $longitude );
						}
					} else {
						$map->setMapCentre( $latitude, $longitude );
					}
					
					if ( $wp_geo_options['show_map_type_physical'] == 'Y' )
						$map->addMapType( 'G_PHYSICAL_MAP' );
					if ( $wp_geo_options['show_map_type_normal'] == 'Y' )
						$map->addMapType( 'G_NORMAL_MAP' );
					if ( $wp_geo_options['show_map_type_satellite'] == 'Y' )
						$map->addMapType( 'G_SATELLITE_MAP' );
					if ( $wp_geo_options['show_map_type_hybrid'] == 'Y' )
						$map->addMapType( 'G_HYBRID_MAP' );
					
					if ( $wp_geo_options['show_map_scale'] == 'Y' )
						$map->showMapScale( true );
					if ( $wp_geo_options['show_map_overview'] == 'Y' )
						$map->showMapOverview( true );
					
					$map->setMapControl( $wp_geo_options['default_map_control'] );
					array_push( $this->maps, $map );
					// ----------- End - Create maps for visible posts and pages -----------
				}
			}
			
			// Need a map?
			if ( count( $coords ) > 0 ) {
			
				// ----------- Start - Create map for visible posts and pages -----------
				$map = new WPGeo_Map( 'visible' );
				$map->show_polyline = true;
				
				// Add points
				for ( $j = 0; $j < count( $coords ); $j++ ) {
					$marker_small = empty( $marker ) ? 'small' : $marker;
					$icon = apply_filters( 'wpgeo_marker_icon', $marker_small, $coords[$j]['post'], 'multiple' );
					$map->addPoint( $coords[$j]['latitude'], $coords[$j]['longitude'], $icon, $coords[$j]['title'], $coords[$j]['link'] );
				}
				
				$map->setMapZoom( $mapzoom );
				$map->setMapType( $maptype );
				
				if ( $wp_geo_options['show_map_type_physical'] == 'Y' )
					$map->addMapType( 'G_PHYSICAL_MAP' );
				if ( $wp_geo_options['show_map_type_normal'] == 'Y' )
					$map->addMapType( 'G_NORMAL_MAP' );
				if ( $wp_geo_options['show_map_type_satellite'] == 'Y' )
					$map->addMapType( 'G_SATELLITE_MAP' );
				if ( $wp_geo_options['show_map_type_hybrid'] == 'Y' )
					$map->addMapType( 'G_HYBRID_MAP' );
				
				if ( $wp_geo_options['show_map_scale'] == 'Y' )
					$map->showMapScale( true );
				if ( $wp_geo_options['show_map_overview'] == 'Y' )
					$map->showMapOverview( true );
					
				$map->setMapControl( $wp_geo_options['default_map_control'] );
				array_push( $this->maps, $map );
				// ----------- End - Create map for visible posts and pages -----------
				
				$google_maps_api_key = $wpgeo->get_google_api_key();
				$zoom = $wp_geo_options['default_map_zoom'];
				
				// Loop through maps to get Javascript
				$js_map_writes = '';
				foreach ( $this->maps as $map ) {
					$js_map_writes .= $map->renderMapJS();
				}
						
				// Script
				$wpgeo->includeGoogleMapsJavaScriptAPI();
				$html_content = '
				<script type="text/javascript">
				//<![CDATA[
				
				var map = null; ' . $js_map_inits . '
				var marker = null; ' . $js_marker_inits . '
				
				function init_wp_geo_map() {
					if (GBrowserIsCompatible()) {
						' . $js_map_writes . '
					}
				}
				if (document.all&&window.attachEvent) { // IE-Win
					window.attachEvent("onload", function () { init_wp_geo_map(); });
					window.attachEvent("onunload", GUnload);
				} else if (window.addEventListener) { // Others
					window.addEventListener("load", function () { init_wp_geo_map(); }, false);
					window.addEventListener("unload", GUnload, false);
				}
				//]]>
				</script>';
				
				echo $html_content;
			}
	
			// @todo Check if plugin head needed
			// @todo Check for Google API key
			// @todo Write Javascripts and CSS
		}
	}
	
	/**
	 * Init
	 * Runs actions on init if Google API Key exists.
	 */
	function init() {
	
		// Only show admin things if Google API Key valid
		if ( $this->checkGoogleAPIKey() ) {
			add_action( 'admin_menu', array( $this, 'add_custom_boxes' ) );
			add_action( 'save_post', array( $this, 'wpgeo_location_save_postdata' ) );
			
			// Do an action for plugins to detect wether WP Geo is ready
			do_action( 'wpgeo_init', $this );
		}
	}
	
	/**
	 * Init Later
	 * Called on WP action - runs after WordPress is ready.
	 */
	function init_later() {
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Support for custom post types
		// Don't add support if on the WP settings page though
		if ( ! is_admin() || ! isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && $_GET['page'] != 'wp-geo' ) ) {
			if ( function_exists( 'get_post_types' ) && function_exists( 'add_post_type_support' ) && isset( $wp_geo_options['show_maps_on_customposttypes'] ) ) {
				$post_types = get_post_types();
				foreach ( $post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );
					if ( $post_type_object->show_ui && array_key_exists( $post_type, $wp_geo_options['show_maps_on_customposttypes'] ) && $wp_geo_options['show_maps_on_customposttypes'][$post_type] == 'Y' ) {
						add_post_type_support( $post_type, 'wpgeo' );
					}
				}
			}
		}
		
		// Add extra markers
		$this->markers->add_extra_markers();
	}
	
	/**
	 * Admin Init
	 */
	function admin_init() {
		include_once( WPGEO_DIR . 'admin/editor.php' );
		include_once( WPGEO_DIR . 'admin/dashboard.php' );
		include_once( WPGEO_DIR . 'admin/settings.php' );
		
		// Register Settings
		$this->settings = new WPGeo_Settings();
		
		// Only show editor if Google API Key valid
		if ( $this->checkGoogleAPIKey() ) {
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
	 * Admin Head
	 */
	function admin_head() {
		global $wpgeo, $post_ID;
		
		echo '<link rel="stylesheet" href="' . WPGEO_URL . 'css/wp-geo.css" type="text/css" />';
		
		// Only load if on a post or page
		if ( $wpgeo->show_maps() ) {
			
			// Get post location
			$latitude  = get_post_meta( $post_ID, WPGEO_LATITUDE_META, true );
			$longitude = get_post_meta( $post_ID, WPGEO_LONGITUDE_META, true );
			$default_latitude  = $latitude;
			$default_longitude = $longitude;
			$default_zoom = 13;
			$panel_open   = false;
			$hide_marker  = false;
			
			if ( ! $wpgeo->show_maps_external ) {
				echo $wpgeo->mapScriptsInit( $default_latitude, $default_longitude, $default_zoom, $panel_open, $hide_marker );
			}
		}
	}
	
	/**
	 * Include Google Maps JavaScript API
	 * Queue JavaScripts required by WP Geo.
	 */
	function includeGoogleMapsJavaScriptAPI() {
		global $wpgeo;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		// Google AJAX API
		// Loads on all pages unless via proxy domain
		if ( wpgeo_check_domain() && $wpgeo->checkGoogleAPIKey() ) {
			//wp_register_script('google_jsapi', 'http://www.google.com/jsapi?key=' . $wpgeo->get_google_api_key(), false, '1.0');
			//wp_enqueue_script('google_jsapi');
		}
		
		if ( ( $wpgeo->show_maps() || $wpgeo->widget_is_active()) && $wpgeo->checkGoogleAPIKey() ) {
			$locale = $wpgeo->get_googlemaps_locale( '&hl=' );
			$googlemaps_js = add_query_arg( array(
				'v'      => 2,
				'hl'     => $wpgeo->get_googlemaps_locale(),
				'key'    => $wpgeo->get_google_api_key(),
				'sensor' => 'false'
			), 'http://maps.google.com/maps?file=api' );
			
			wp_register_script( 'googlemaps', $googlemaps_js, false, '2' );
			wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.js', array('googlemaps', 'wpgeotooltip'), '1.0' );
			wp_register_script( 'wpgeo-admin-post', WPGEO_URL . 'js/admin-post.js', array('jquery', 'googlemaps'), '1.0' );
			wp_register_script( 'wpgeotooltip', WPGEO_URL . 'js/tooltip.js', array('googlemaps', 'jquery'), '1.0' );
			//wp_register_script( 'jquerywpgeo', WPGEO_URL . 'js/jquery.wp-geo.js', array('jquery', 'googlemaps'), '1.0' );
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'googlemaps' );
			wp_enqueue_script( 'wpgeo' );
			wp_enqueue_script( 'wpgeotooltip' );
			if ( is_admin() ) {
				 wp_enqueue_script( 'wpgeo-admin-post' );
			}
			//wp_enqueue_script( 'jquerywpgeo' );
			
			return '';
		}
	}
	
	/**
	 * Get Google Maps Locale
	 * See http://code.google.com/apis/maps/faq.html#languagesupport for link to updated languages codes.
	 *
	 * @author Alain Messin, tweaked by Ben :)
	 *
	 * @param string $before Before output.
	 * @param string $after After output.
	 * @return string Google locale.
	 */
	function get_googlemaps_locale( $before = '', $after = '' ) {
		$l = get_locale();
		if ( ! empty( $l ) ) {

			// WordPress locale is xx_XX, some codes are known
			// by google with - in place of _ , so replace
			$l = str_replace( '_', '-', $l );
			
			// Known Google codes known
			$codes = array(
				'en-AU',
				'en-GB',
				'pt-BR',
				'pt-PT',
				'zh-CN',
				'zh-TW'
			);
			
			// Other codes known by googlemaps are 2 characters codes
			if ( ! in_array( $l, $codes ) ) {
				$l = substr( $l, 0, 2 );
			}
		}
		
		// Apply filter - why not ;)
		$l = apply_filters( 'wp_geo_locale', $l );
		
		if ( ! empty( $l ) ) {
			$l = $before . $l . $after;
		}
		return $l;
	}
	
	/**
	 * Map Scripts Init
	 * Output Javascripts to display maps.
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 * @param int $zoom Zoom.
	 * @param bool $panel_open Admin panel open?
	 * @param bool $hide_marker Hide marker?
	 * @return string HTML content.
	 */
	function mapScriptsInit( $latitude, $longitude, $zoom = 5, $panel_open = false, $hide_marker = false ) {
		global $wpgeo, $post;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$maptype = empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];	
		
		// Centre on London
		if ( ! wpgeo_is_valid_geo_coord( $latitude, $longitude ) ) {
			$latitude    = $wp_geo_options['default_map_latitude'];
			$longitude   = $wp_geo_options['default_map_longitude'];
			$zoom        = $wp_geo_options['default_map_zoom'];
			$panel_open  = true;
			$hide_marker = true;
		}
		$mapcentre = array( $latitude, $longitude );
		
		if ( is_numeric( $post->ID ) && $post->ID > 0 ) {
			$settings = get_post_meta( $post->ID, WPGEO_MAP_SETTINGS_META, true );
			if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
				$zoom = $settings['zoom'];
			}
			if ( ! empty( $settings['type'] ) ) {
				$maptype = $settings['type'];
			}
			if ( ! empty( $settings['centre'] ) ) {
				$new_mapcentre = explode( ',', $settings['centre'] );
				if ( wpgeo_is_valid_geo_coord( $new_mapcentre[0], $new_mapcentre[1] ) ) {
					$mapcentre = $new_mapcentre;
				}
			}
		}
		
		// Vars
		$google_maps_api_key = $wpgeo->get_google_api_key();
		$panel_open ? $panel_open = 'jQuery(\'#wpgeolocationdiv.postbox h3\').click();' : $panel_open = '';
		$hide_marker ? $hide_marker = 'marker.hide();' : $hide_marker = '';
		
		// Script
		$wpgeo->includeGoogleMapsJavaScriptAPI();
		$html_content = '
			<script type="text/javascript">
			//<![CDATA[
			
			function init_wp_geo_map_admin() {
				if (GBrowserIsCompatible() && document.getElementById("wp_geo_map")) {
					map = new GMap2(document.getElementById("wp_geo_map"));
					var center = new GLatLng(' . $mapcentre[0] . ', ' . $mapcentre[1] . ');
					var point = new GLatLng(' . $latitude . ', ' . $longitude . ');
					map.setCenter(center, ' . $zoom . ');
					map.addMapType(G_PHYSICAL_MAP);
					
					var zoom_setting = document.getElementById("wpgeo_map_settings_zoom");
					zoom_setting.value = ' . $zoom . ';
					
					// Map Controls
					' . WPGeo_API_GMap2::render_map_control( 'map', 'GLargeMapControl3D' ) . '
					' . WPGeo_API_GMap2::render_map_control( 'map', 'GMapTypeControl' ) . '
					//map.setUIToDefault();
					
					map.setMapType(' . $maptype . ');
					var type_setting = document.getElementById("wpgeo_map_settings_type");
					type_setting.value = wpgeo_getMapTypeContentFromUrlArg(map.getCurrentMapType().getUrlArg());
					
					GEvent.addListener(map, "click", function(overlay, latlng) {
						var latField = document.getElementById("wp_geo_latitude");
						var lngField = document.getElementById("wp_geo_longitude");
						latField.value = latlng.lat();
						lngField.value = latlng.lng();
						marker.setPoint(latlng);
						marker.show();
					});
					
					GEvent.addListener(map, "maptypechanged", function() {
						var type_setting = document.getElementById("wpgeo_map_settings_type");
						type_setting.value = wpgeo_getMapTypeContentFromUrlArg(map.getCurrentMapType().getUrlArg());
					});
					
					GEvent.addListener(map, "zoomend", function(oldLevel, newLevel) {
						var zoom_setting = document.getElementById("wpgeo_map_settings_zoom");
						zoom_setting.value = newLevel;
					});
					
					GEvent.addListener(map, "moveend", function() {
						var center = this.getCenter();
						var centre_setting = document.getElementById("wpgeo_map_settings_centre");
						centre_setting.value = center.lat() + "," + center.lng();
					});
					
					marker = new GMarker(point, {draggable: true});
					
					GEvent.addListener(marker, "dragstart", function() {
						map.closeInfoWindow();
					});
					
					GEvent.addListener(marker, "dragend", function() {
						var coords = marker.getLatLng();
						var latField = document.getElementById("wp_geo_latitude");
						var lngField = document.getElementById("wp_geo_longitude");
						latField.value = coords.lat();
						lngField.value = coords.lng();
					});
					
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', 'map' ) . '
					' . WPGeo_API_GMap2::render_map_overlay( 'map', 'marker' ) . '
					
					' . $panel_open . '
					
					var latField = document.getElementById("wp_geo_latitude");
					var lngField = document.getElementById("wp_geo_longitude");
					
					' . $hide_marker . '
					
				}
			}
			
			jQuery(window).load( init_wp_geo_map_admin );
			jQuery(window).unload( GUnload );
			
			//]]>
			</script>';
		
		return $html_content;
	}
	
	/**
	 * Get The Excerpt
	 * Output Map placeholders on excerpts if set to automatically.
	 *
	 * @param string $content HTML content.
	 * @return string HTML content.
	 */
	function get_the_excerpt( $content = '' ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		if ( $wp_geo_options['show_maps_on_excerpts'] == 'Y' ) {
			return $this->the_content( $content );
		}
		return $content;
	}
	
	/**
	 * The Content
	 * Output Map placeholders in the content area if set to automatically.
	 *
	 * @param string $content HTML content.
	 * @return string HTML content.
	 */
	function the_content( $content = '' ) {
		global $wpgeo, $post, $wpdb;
		
		$new_content = '';
		if ( $wpgeo->show_maps() && ! is_feed() ) {
			$wp_geo_options = get_option( 'wp_geo_options' );
			
			// Get the post
			$id = $post->ID;
		
			// Get latitude and longitude
			$latitude  = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
			$longitude = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
			
			// Need a map?
			if ( wpgeo_is_valid_geo_coord( $latitude, $longitude ) ) {
				$new_content .= '<div class="wp_geo_map" id="wp_geo_map_' . $id . '" style="width:' . $wp_geo_options['default_map_width'] . '; height:' . $wp_geo_options['default_map_height'] . ';"></div>';
				$new_content = apply_filters( 'wpgeo_the_content_map', $new_content );
			}
			
			// Add map to content
			$show_post_map = apply_filters( 'wpgeo_show_post_map', $wp_geo_options['show_post_map'], $id );
			
			// Show at top/bottom of post
			if ( $show_post_map == 'TOP' ) {
				$content = $new_content . $content;
			} elseif ( $show_post_map == 'BOTTOM' ) {
				$content = $content . $new_content;
			}
		}
		return $content;
	}
	
	/**
	 * Admin Menu
	 * Adds WP Geo settings page menu item.
	 */
	function admin_menu() {
		global $wpgeo;
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( __( 'WP Geo Options', 'wp-geo' ), __( 'WP Geo', 'wp-geo' ), 'manage_options', 'wp-geo', array( $wpgeo, 'options_page' ) );
		}
	}
	
	/**
	 * Widget Is Active?
	 */
	function widget_is_active() {
		$widgets = array(
			'wpgeo_recent_locations_widget',
			'wpgeo_category_map_widget',
			'wpgeo_contextual_map_widget'
		);
		foreach ( $widgets as $widget ) {
			if ( is_active_widget( false, false, $widget, true ) ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Show Maps
	 * Checks the current page/scenario and wether maps should be shown.
	 */
	function show_maps() {
		global $post, $post_ID, $pagenow;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Check if domain is correct
		if ( ! wpgeo_check_domain() )
			return false;
		
		// Widget active
		// if ( is_active_widget( array( 'WPGeo_Widget', 'map_widget' ) ) )
		//	return true;
		
		// Check settings
		if ( is_home() && $wp_geo_options['show_maps_on_home'] == 'Y' )
			return true;
		if ( is_single() ) {
			if ( function_exists( 'get_post_type' ) && function_exists( 'get_post_type_object' ) && function_exists( 'post_type_supports' ) ) {
				$post_type = get_post_type( $post->ID );
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type == 'post' ) {
					return true;
				} elseif ( $wp_geo_options['show_maps_on_customposttypes'][$post_type] == 'Y' ) {
					return true;
				} elseif ( ! $post_type_object->show_ui ) {
					return post_type_supports( $post_type, 'wpgeo' );
				}
			} elseif ( $wp_geo_options['show_maps_on_posts'] == 'Y' ) {
				return true;
			}
		}
		if ( is_page() && $wp_geo_options['show_maps_on_pages'] == 'Y' )				return true;
		if ( is_date() && $wp_geo_options['show_maps_in_datearchives'] == 'Y' )			return true;
		if ( is_category() && $wp_geo_options['show_maps_in_categoryarchives'] == 'Y' )	return true;
		if ( is_tag() && $wp_geo_options['show_maps_in_tagarchives'] == 'Y' )			return true;
		if ( is_tax() && $wp_geo_options['show_maps_in_taxarchives'] == 'Y' )			return true;
		if ( is_author() && $wp_geo_options['show_maps_in_authorarchives'] == 'Y' )		return true;
		if ( is_search() && $wp_geo_options['show_maps_in_searchresults'] == 'Y' )		return true;
		if ( is_feed() && $wp_geo_options['add_geo_information_to_rss'] == 'Y' )		return true;

		// Activate maps in admin...
		if ( is_admin() ) {
			// If editing a post or page...
			if ( is_numeric( $post_ID ) && $post_ID > 0 )
				return true;
			// If writing a new post or page...
			if ( $pagenow == 'post-new.php' || $pagenow == 'page-new.php' )
				return true;
		}
		
		// Do Action
		if ( $this->show_maps_external )
			return true;
		
		return false;
	}
	
	/**
	 * Options Checkbox HTML
	 *
	 * @param string $id Field ID.
	 * @param string $val Field value.
	 * @param string $checked Checked value.
	 * @param bool $disabled (optional) Is disabled?
	 * @return string Checkbox HTML.
	 */
	function options_checkbox( $name, $val, $checked, $disabled = false, $id = '' ) {
		if ( empty( $id ) )
			$id = $name;
		return '<input name="' . $name . '" type="checkbox" id="' . $id . '" value="' . $val . '" ' . checked( $val, $checked, false ) . ' ' . disabled( true, $disabled, false ) . '/>';
	}
	
	/**
	 * Options Page
	 */
	function options_page() {
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Process option updates
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {
		/*
			$wp_geo_options['google_api_key']        = $_POST['google_api_key'];
			$wp_geo_options['google_map_type']       = $_POST['google_map_type'];
			$wp_geo_options['show_post_map']         = $_POST['show_post_map'];
			$wp_geo_options['default_map_latitude']  = empty( $_POST['default_map_latitude'] ) ? $wpgeo->default_map_latitude : $_POST['default_map_latitude'];
			$wp_geo_options['default_map_longitude'] = empty( $_POST['default_map_longitude'] ) ? $wpgeo->default_map_longitude : $_POST['default_map_longitude'];
			$wp_geo_options['default_map_width']     = wpgeo_css_dimension( $_POST['default_map_width'] );
			$wp_geo_options['default_map_height']    = wpgeo_css_dimension( $_POST['default_map_height'] );
			$wp_geo_options['default_map_zoom']      = $_POST['default_map_zoom'];
			
			$wp_geo_options['default_map_control']     = $_POST['default_map_control'];
			$wp_geo_options['show_map_type_normal']    = isset( $_POST['show_map_type_normal'] ) && $_POST['show_map_type_normal'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_map_type_satellite'] = isset( $_POST['show_map_type_satellite'] ) && $_POST['show_map_type_satellite'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_map_type_hybrid']    = isset( $_POST['show_map_type_hybrid'] ) && $_POST['show_map_type_hybrid'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_map_type_physical']  = isset( $_POST['show_map_type_physical'] ) && $_POST['show_map_type_physical'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_map_scale']          = isset( $_POST['show_map_scale'] ) && $_POST['show_map_scale'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_map_overview']       = isset( $_POST['show_map_overview'] ) && $_POST['show_map_overview'] == 'Y' ? 'Y' : 'N';
			
			$wp_geo_options['save_post_zoom']         = isset( $_POST['save_post_zoom'] ) && $_POST['save_post_zoom'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['save_post_map_type']     = isset( $_POST['save_post_map_type'] ) && $_POST['save_post_map_type'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['save_post_centre_point'] = isset( $_POST['save_post_centre_point'] ) && $_POST['save_post_centre_point'] == 'Y' ? 'Y' : 'N';
			
			$wp_geo_options['show_polylines']  = isset( $_POST['show_polylines'] ) && $_POST['show_polylines'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['polyline_colour'] = $_POST['polyline_colour'];
			
			$wp_geo_options['show_maps_on_home']             = isset( $_POST['show_maps_on_home'] ) && $_POST['show_maps_on_home'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_on_pages']            = isset( $_POST['show_maps_on_pages'] ) && $_POST['show_maps_on_pages'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_on_posts']            = isset( $_POST['show_maps_on_posts'] ) && $_POST['show_maps_on_posts'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_datearchives']     = isset( $_POST['show_maps_in_datearchives'] ) && $_POST['show_maps_in_datearchives'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_categoryarchives'] = isset( $_POST['show_maps_in_categoryarchives'] ) && $_POST['show_maps_in_categoryarchives'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_tagarchives']      = isset( $_POST['show_maps_in_tagarchives'] ) && $_POST['show_maps_in_tagarchives'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_taxarchives']      = isset( $_POST['show_maps_in_taxarchives'] ) && $_POST['show_maps_in_taxarchives'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_authorarchives']   = isset( $_POST['show_maps_in_authorarchives'] ) && $_POST['show_maps_in_authorarchives'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_in_searchresults']    = isset( $_POST['show_maps_in_searchresults'] ) && $_POST['show_maps_in_searchresults'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_on_excerpts']         = isset( $_POST['show_maps_on_excerpts'] ) && $_POST['show_maps_on_excerpts'] == 'Y' ? 'Y' : 'N';
			$wp_geo_options['show_maps_on_customposttypes']  = array();
			
			if ( isset( $_POST['show_maps_on_customposttypes'] ) && is_array( $_POST['show_maps_on_customposttypes'] ) ) {
				foreach ( $_POST['show_maps_on_customposttypes'] as $key => $val ) {
					$wp_geo_options['show_maps_on_customposttypes'][$key] = $val == 'Y' ? 'Y' : 'N';
				}
			}
			
			$wp_geo_options['add_geo_information_to_rss'] = $_POST['add_geo_information_to_rss'] == 'Y' ? 'Y' : 'N';
			
			update_option( 'wp_geo_options', $wp_geo_options );
			echo '<div class="updated"><p>' . __( 'WP Geo settings updated', 'wp-geo' ) . '</p></div>';
			*/
		}

		// Markers
		$markers = array();
		$markers['large'] = $this->markers->get_marker_by_id( 'large' );
		$markers['small'] = $this->markers->get_marker_by_id( 'small' );
		$markers['dot']   = $this->markers->get_marker_by_id( 'dot' );
		
		// Write the form
		echo '<div class="wrap">
			<div id="icon-options-wpgeo" class="icon32" style="background: url(' . WPGEO_URL . 'img/logo/icon32.png) 2px 1px no-repeat;"><br></div>
			<h2>' . __( 'WP Geo Settings', 'wp-geo' ) . '</h2>
			<form action="options.php" method="post">';
		include( WPGEO_DIR . 'admin/donate-links.php' );
		
        if ( ! $wpgeo->markers->marker_folder_exists() ) {
            echo '<div class="error"><p>' . sprintf( __( "Unable to create the markers folder %s.<br />Please create it and copy the marker images to it from %s</p>", 'wp-geo' ), str_replace( ABSPATH, '', $wpgeo->markers->upload_dir ) . '/wp-geo/markers/', str_replace( ABSPATH, '', WPGEO_DIR ) . 'img/markers' ) . '</div>';
        }
		if ( ! $this->checkGoogleAPIKey() ) {
			echo '<div class="error"><p>' . sprintf( __( "Before you can use WP Geo you must acquire a %s for your blog - the plugin will not function without it!", 'wp-geo' ), '<a href="https://developers.google.com/maps/documentation/javascript/v2/introduction#Obtaining_Key" target="_blank">' . __( 'Google API Key', 'wp-geo' ) . '</a>' ) . '</p></div>';
		}
		
		do_settings_sections( 'wp_geo_options' );
		settings_fields( 'wp_geo_options' );
		echo '<p class="submit">
					<input type="submit" name="submit" value="' . __( 'Save Changes', 'wp-geo' ) . '" class="button-primary" />
				</p>
			</form>';
		echo '
				<h2 style="margin-top:30px;">' . __( 'Marker Settings', 'wp-geo' ) . '</h2>'
				. __( '<p>Custom marker images are automatically created in your WordPress uploads folder and used by WP Geo.<br />A copy of these images will remain in the WP Geo folder in case you need to revert to them at any time.<br />You may edit these marker icons if you wish - they must be PNG files. Each marker consist of a marker image and a shadow image. If you do not wish to show a marker shadow you should use a transparent PNG for the shadow file.</p><p>Currently you must update these images manually and the anchor point must be the same - looking to provide more control in future versions.</p>', 'wp-geo' ) . '
				' . $wpgeo->markers->get_admin_display();
		echo '<h2 style="margin-top:30px;">' . __( 'Documentation', 'wp-geo' ) . '</h2>'
			. __( '<p>If you set the Show Post Map setting to &quot;Manual&quot;, you can use the Shortcode <code>[wp_geo_map]</code> in a post to display a map (if a location has been set for the post). You can only include the Shortcode once within a post. If you select another Show Post Map option then the Shortcode will be ignored and the map will be positioned automatically.</p>', 'wp-geo' )
			. '<h2 style="margin-top:30px;">' . __( 'Feedback', 'wp-geo' ) . '</h2>'
			. sprintf( __( "<p>If you experience any problems or bugs with the plugin, or want to suggest an improvement, please visit the <a %s>WP Geo Google Code page</a> to log your issue. If you would like to feedback or comment on the plugin please visit the <a %s>WP Geo plugin</a> page.</p>", 'wp-geo' ), 'href="http://code.google.com/p/wp-geo/issues/list"', 'href="http://www.benhuson.co.uk/wordpress-plugins/wp-geo/"' )
			. sprintf( __( "<p>If you like WP Geo and would like to make a donation, please do so on the <a %s>WP Geo website</a>. Your contributions help to ensure that I can dedicate more time to the support and development of the plugin.</p>", 'wp-geo' ), 'href="http://www.wpgeo.com/" target="_blank"' ) . '
		</div>';
	}
	
	/**
	 * Select Map Control
	 * Map control array or menu.
	 *
	 * @param string $return (optional) Array or menu type.
	 * @param string $selected (optional) Selected value.
	 * @return array|string Array or menu HTML.
	 */
	function selectMapControl( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'name' => 'default_map_control',
			'id'   => 'default_map_control'
		) );
		$map_type_array = array(
			'GLargeMapControl3D'  => __( 'Large 3D pan/zoom control', 'wp-geo' ),
			'GLargeMapControl'    => __( 'Large pan/zoom control', 'wp-geo' ),
			'GSmallMapControl'    => __( 'Smaller pan/zoom control', 'wp-geo' ),
			'GSmallZoomControl3D' => __( 'Small 3D zoom control (no panning controls)', 'wp-geo' ),
			'GSmallZoomControl'   => __( 'Small zoom control (no panning controls)', 'wp-geo' ),
			''                    => __( 'No pan/zoom controls', 'wp-geo' )
		);
		
		// Menu?
		if ( $return = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$menu .= '<option value="' . $key . '"' . selected( $selected, $key, false ) . '>' . $val . '</option>';
			}
			$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
			return $menu;
		}
		
		return $map_type_array;
	}
	
	/**
	 * Select Map Zoom
	 * Map zoom array or menu.
	 *
	 * @param string $return (optional) Array or menu type.
	 * @param string $selected (optional) Selected value.
	 * @param array $args (optional) Args.
	 * @return array|string Array or menu HTML.
	 */
	function selectMapZoom( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'return'   => null,
			'selected' => null,
			'name'     => 'default_map_zoom',
			'id'       => 'default_map_zoom'
		) );
		
		// Deprecated compatibility
		if ( $args['return'] == null ) 
			$args['return'] = $return;
		if ( $args['selected'] == null ) 
			$args['selected'] = $selected;
		
		// Array
		$map_type_array = array(
			'0'  => '0 - ' . __( 'Zoomed Out', 'wp-geo' ), 
			'1'  => '1', 
			'2'  => '2', 
			'3'  => '3', 
			'4'  => '4', 
			'5'  => '5', 
			'6'  => '6', 
			'7'  => '7', 
			'8'  => '8', 
			'9'  => '9', 
			'10' => '10', 
			'11' => '11', 
			'12' => '12', 
			'13' => '13', 
			'14' => '14', 
			'15' => '15', 
			'16' => '16', 
			'17' => '17', 
			'18' => '18', 
			'19' => '19 - ' . __( 'Zoomed In', 'wp-geo' ), 
		);
		
		// Menu?
		if ( $return = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$menu .= '<option value="' . $key . '"' . selected( $args['selected'], $key, false ) . '>' . $val . '</option>';
			}
			$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
			return $menu;
		}
		
		return $map_type_array;
	}
	
	/**
	 * Google Map Types
	 * Map type array or menu.
	 *
	 * @param string $return (optional) Array or menu type.
	 * @param string $selected (optional) Selected value.
	 * @param array $args (optional) Args.
	 * @return array|string Array or menu HTML.
	 */
	function google_map_types( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'return'   => null,
			'selected' => null,
			'name'     => 'google_map_type',
			'id'       => 'google_map_type'
		) );
		
		// Deprecated compatibility
		if ( $args['return'] == null ) 
			$args['return'] = $return;
		if ( $args['selected'] == null ) 
			$args['selected'] = $selected;
		
		// Array
		$map_type_array = array(
			'G_NORMAL_MAP' 		=> __( 'Normal', 'wp-geo' ), 
			'G_SATELLITE_MAP' 	=> __( 'Satellite (photographic map)', 'wp-geo' ), 
			'G_HYBRID_MAP' 		=> __( 'Hybrid (photographic map with normal features)', 'wp-geo' ),
			'G_PHYSICAL_MAP' 	=> __( 'Physical (terrain map)', 'wp-geo' )
		);
		
		// Menu?
		if ( $args['return'] = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$menu .= '<option value="' . $key . '"' . selected( $args['selected'], $key, false ) . '>' . $val . '</option>';
			}
			$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
			return $menu;
		}
		
		return $map_type_array;
	}
	
	/**
	 * Post Map Menu
	 * Map position array or menu.
	 *
	 * @param string $return (optional) Array or menu type.
	 * @param string $selected (optional) Selected value.
	 * @return array|string Array or menu HTML.
	 */
	function post_map_menu( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'name' => 'show_post_map',
			'id'   => 'show_post_map'
		) );
		$map_type_array = array(
			'TOP'    => __( 'At top of post', 'wp-geo' ), 
			'BOTTOM' => __( 'At bottom of post', 'wp-geo' ), 
			'HIDE'   => __( 'Manually', 'wp-geo' )
		);
		
		// Menu?
		if ( $return = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$menu .= '<option value="' . $key . '"' . selected( $selected, $key, false ) . '>' . $val . '</option>';
			}
			$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
			return $menu;
		}
		
		return $map_type_array;
	}
	
	/**
	 * After Plugin Row
	 * This function can be used to insert text after the WP Geo plugin row on the plugins page.
	 * Useful if you need to tell people something important before they upgrade.
	 *
	 * @param string $plugin Plugin reference.
	 */
	function after_plugin_row( $plugin ) {
		if ( 'wp-geo/wp-geo.php' == $plugin && ! empty( $this->plugin_message ) ) {
			//echo '<td colspan="5" class="plugin-update" style="line-height:1.2em;">' . $this->plugin_message . '</td>';
			return;
		}
	}
	
	/**
	 * Get WP Geo Posts
	 *
	 * @todo Use same parameters as query_posts by default.
	 *
	 * @param array $args Arguments.
	 * @return array Points.
	 */
	function get_wpgeo_posts( $args = null ) {
		global $customFields;
		
		$default_args = array(
			'numberposts' => 5
		);
		$arguments = wp_parse_args( $args, $default_args );
		extract( $arguments, EXTR_SKIP );
		
		$customFields = "'" . WPGEO_LONGITUDE_META . "', '" . WPGEO_LATITUDE_META . "'";
		
		$custom_posts = new WP_Query();
		add_filter( 'posts_join', array( $this, 'get_custom_field_posts_join' ) );
		add_filter( 'posts_groupby', array( $this, 'get_custom_field_posts_group' ) );
		$custom_posts->query( 'showposts=' . $numberposts );
		remove_filter( 'posts_join', array( $this, 'get_custom_field_posts_join' ) );
		remove_filter( 'posts_groupby', array( $this, 'get_custom_field_posts_group' ) );
		
		$points = array();
		while ( $custom_posts->have_posts() ) {
			$custom_posts->the_post();
			$id   = get_the_ID();
			$long = get_post_custom_values( WPGEO_LONGITUDE_META );
			$lat  = get_post_custom_values( WPGEO_LATITUDE_META );
			$points[] = array(
				'id'   => $id,
				'long' => $long,
				'lat'  => $lat
			);
		}
		return $points;
	}
	
	/**
	 * Get Custom Field Posts Join
	 * Join custom fields on to results.
	 *
	 * @todo Use $wpdb->prepare();
	 *
	 * @param string $join JOIN statement.
	 * @return string SQL.
	 */
	function get_custom_field_posts_join( $join ) {
		global $wpdb, $customFields;
		return $join . " JOIN $wpdb->postmeta postmeta ON (postmeta.post_id = $wpdb->posts.ID and postmeta.meta_key in ($customFields))";
	}
	
	/**
	 * Get Custom Field Posts Group
	 * Group by post id.
	 *
	 * @param string $group GROUP BY statement.
	 * @return string SQL.
	 */
	function get_custom_field_posts_group( $group ) {
		global $wpdb;
		$group .= " $wpdb->posts.ID ";
		return $group;
	}
	
	/* =============== Admin Edit Pages =============== */
	
	/**
	 * Add Custom Meta Boxes
	 *
	 * @todo Check if should be added to pages/posts.
	 */
	function add_custom_boxes() {
		global $post;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		add_meta_box( 'wpgeo_location', __( 'WP Geo Location', 'wpgeo' ), array( $this, 'wpgeo_location_inner_custom_box' ), 'post', 'advanced' );
		add_meta_box( 'wpgeo_location', __( 'WP Geo Location', 'wpgeo' ), array( $this, 'wpgeo_location_inner_custom_box' ), 'page', 'advanced' );
		
		// Support for custom post types
		if ( function_exists( 'get_post_types' ) && function_exists( 'post_type_supports' ) ) {
			$post_types = get_post_types();
			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );
				if ( post_type_supports( $post_type, 'wpgeo' ) ) {
					add_meta_box( 'wpgeo_location', __( 'WP Geo Location', 'wpgeo' ), array( $this, 'wpgeo_location_inner_custom_box' ), $post_type, 'advanced' );
				}
			}
		}
	}
	
	/**
	 * WP Geo Location Inner Custom Box
	 */
	function wpgeo_location_inner_custom_box() {
		global $post;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		$search    = '';
		$latitude  = get_post_meta( $post->ID, WPGEO_LATITUDE_META, true );
		$longitude = get_post_meta( $post->ID, WPGEO_LONGITUDE_META, true );
		$title     = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
		$marker    = get_post_meta( $post->ID, WPGEO_MARKER_META, true );
		$settings  = get_post_meta( $post->ID, WPGEO_MAP_SETTINGS_META, true );
		
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
		
		if ( isset( $settings['zoom'] ) && ! empty( $settings['zoom'] ) ) {
			$wpgeo_map_settings_zoom = $settings['zoom'];
			$wpgeo_map_settings_zoom_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_zoom'] == 'Y' ) {
			$wpgeo_map_settings_zoom = $wp_geo_options['save_post_zoom'];
			$wpgeo_map_settings_zoom_checked = checked( true, true, false );
		}
		if ( isset( $settings['type'] ) && ! empty( $settings['type'] ) ) {
			$wpgeo_map_settings_type = $settings['type'];
			$wpgeo_map_settings_type_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_zoom'] == 'Y' ) {
			$wpgeo_map_settings_type = $wp_geo_options['save_post_zoom'];
			$wpgeo_map_settings_type_checked = checked( true, true, false );
		}
		if ( isset( $settings['centre'] ) && ! empty( $settings['centre'] ) ) {
			$wpgeo_map_settings_centre = $settings['centre'];
			$wpgeo_map_settings_centre_checked = checked( true, true, false );
		} elseif ( $wp_geo_options['save_post_centre_point'] == 'Y' ) {
			$wpgeo_map_settings_centre = $wp_geo_options['save_post_centre_point'];
			$wpgeo_map_settings_centre_checked = checked( true, true, false );
		}
		
		// Use nonce for verification
		echo '<input type="hidden" name="wpgeo_location_noncename" id="wpgeo_location_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table cellpadding="3" cellspacing="5" class="form-table">
			<tr>
				<th scope="row">' . __( 'Search for location', 'wp-geo' ) . '<br /><span style="font-weight:normal;">(' . __( 'town, postcode or address', 'wp-geo' ) . ')</span></th>
				<td><input name="wp_geo_search" type="text" size="45" id="wp_geo_search" value="' . $search . '" />
					<input type="hidden" name="wp_geo_base_country_code" id="wp_geo_base_country_code" value="' . apply_filters( 'wpgeo_base_country_code', '' ) . '" />
					<span class="submit"><input type="button" id="wp_geo_search_button" name="wp_geo_search_button" value="' . __( 'Search', 'wp-geo' ) . '" /></span></td>
			</tr>
			<tr>
				<td colspan="2">
				<div id="wp_geo_map" style="height:300px; width:100%; padding:0px; margin:0px;">
					' . __( 'Loading Google map, please wait...', 'wp-geo' ) . '
				</div>
				</td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Latitude', 'wp-geo' ) . ', ' . __( 'Longitude', 'wp-geo' ) . '</th>
				<td><input name="wp_geo_latitude" type="text" size="25" id="wp_geo_latitude" value="' . $latitude . '" /><br />
					<input name="wp_geo_longitude" type="text" size="25" id="wp_geo_longitude" value="' . $longitude . '" /><br />
					<a href="#" class="wpgeo-clear-location-fields">' . __( 'clear location', 'wp-geo' ) . '</a> | <a href="#" class="wpgeo-centre-location">' . __( 'centre location', 'wp-geo' ) . '</a>
				</td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Marker Title', 'wp-geo' ) . ' <small>(' . __( 'optional', 'wp-geo' ) . ')</small></th>
				<td><input name="wp_geo_title" type="text" size="25" style="width:100%;" id="wp_geo_title" value="' . $title . '" /></td>
			</tr>
			<tr>
				<th scope="row">' . __( 'Marker Image', 'wp-geo' ) . '</th>
				<td>' . $this->markers->dropdown_markers( $markers_menu ) . '</td>
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
	
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['wpgeo_location_noncename'] ) || ( isset( $_POST['wpgeo_location_noncename'] ) && ! wp_verify_nonce( $_POST['wpgeo_location_noncename'], plugin_basename( __FILE__ ) ) ) ) {
			return $post_id;
		}
		
		// Authenticate user
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} elseif ( 'post' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		} elseif ( function_exists( 'get_post_type_object' ) ) {
			$post_type = get_post_type_object( $_POST['post_type'] );
			// @todo Should this be "edit_" . $post_type->capability_type
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
				return $post_id;
		}
		
		$mydata = array();
		
		// Find and save the location data
		if ( isset( $_POST['wp_geo_latitude'] ) && isset( $_POST['wp_geo_longitude'] ) ) {
			
			// Only delete post meta if isset (to avoid deletion in bulk/quick edit mode)
			delete_post_meta( $post_id, WPGEO_LATITUDE_META );
			delete_post_meta( $post_id, WPGEO_LONGITUDE_META );
			
			if ( wpgeo_is_valid_geo_coord( $_POST['wp_geo_latitude'], $_POST['wp_geo_longitude'] ) ) {
				add_post_meta( $post_id, WPGEO_LATITUDE_META, $_POST['wp_geo_latitude'] );
				add_post_meta( $post_id, WPGEO_LONGITUDE_META, $_POST['wp_geo_longitude'] );
				$mydata[WPGEO_LATITUDE_META]  = $_POST['wp_geo_latitude'];
				$mydata[WPGEO_LONGITUDE_META] = $_POST['wp_geo_longitude'];
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
			$settings['type'] = $_POST['wpgeo_map_settings_type'];
		}
		if ( isset( $_POST['wpgeo_map_settings_centre'] ) && ! empty( $_POST['wpgeo_map_settings_centre'] ) ) {
			$settings['centre'] = $_POST['wpgeo_map_settings_centre'];
		}
		add_post_meta( $post_id, WPGEO_MAP_SETTINGS_META, $settings );
		$mydata[WPGEO_MAP_SETTINGS_META] = $settings;
		
		return $mydata;
	}
	
	/**
	 * WP Footer
	 */
	function wp_footer() {
		$js = $this->maps2->get_maps_javascript();
		if ( ! empty( $js ) ) {
			echo '<script type="text/javascript">
				<!--
				' . $js . '
				-->
				</script>
				';
		}
	}
	
}

?>