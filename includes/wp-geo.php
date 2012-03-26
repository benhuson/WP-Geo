<?php



/**
 * @class        WP Geo class
 * @description  The main WP Geo class - this is where it all happens.
 * @author       Ben Huson <ben@thewhiteroom.net>
 */

class WPGeo {
	
	
	
	/**
	 * Properties
	 */
	
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
	
	var $default_map_latitude = '51.492526418807465';
	var $default_map_longitude = '-0.15754222869873047';
	
	
	
	/**
	 * @method       Constructor
	 * @description  Initialise the class.
	 */
	
	function WPGeo() {
		
		// Version
		$wp_geo_version = get_option( 'wp_geo_version' );
		if ( empty( $wp_geo_version ) || version_compare( $wp_geo_version, $this->version, '<' ) ) {
			update_option( 'wp_geo_show_version_msg', 'Y' );
			update_option( 'wp_geo_version', $this->version );
		}
		
		$this->maps = array();
		$this->maps2 = new WPGeo_Maps();
		$this->markers = new WPGeo_Markers();
		$this->feeds = new WPGeo_Feeds();
		
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
	 * @method       Register Activation
	 * @description  Runs when the plugin is activated - creates options etc.
	 */
	
	function register_activation() {
		
		$wpgeo = new WPGeo();
		
		$options = array(
			'google_api_key' => '', 
			'google_map_type' => 'G_NORMAL_MAP', 
			'show_post_map' => 'TOP', 
			'default_map_latitude' => '51.492526418807465',
			'default_map_longitude' => '-0.15754222869873047',
			'default_map_width' => '100%', 
			'default_map_height' => '300px',
			'default_map_zoom' => '5',
			'default_map_control' => 'GLargeMapControl3D',
			'show_map_type_normal' => 'Y',
			'show_map_type_satellite' => 'Y',
			'show_map_type_hybrid' => 'Y',
			'show_map_type_physical' => 'Y',
			'show_map_scale' => 'N',
			'show_map_overview' => 'N',
			'save_post_zoom' => 'N',
			'save_post_map_type' => 'N',
			'save_post_centre_point' => 'N',
			'show_polylines' => 'Y',
			'polyline_colour' => '#FFFFFF',
			'show_maps_on_home' => 'Y',
			'show_maps_on_pages' => 'Y',
			'show_maps_on_posts' => 'Y',
			'show_maps_in_datearchives' => 'Y',
			'show_maps_in_categoryarchives' => 'Y',
			'show_maps_in_tagarchives' => 'Y',
			'show_maps_in_taxarchives' => 'Y',
			'show_maps_in_authorarchives' => 'Y',
			'show_maps_in_searchresults' => 'N',
			'show_maps_on_excerpts' => 'N',
			'add_geo_information_to_rss' => 'Y'
		);
		add_option('wp_geo_options', $options);
		$wp_geo_options = get_option('wp_geo_options');
		foreach ( $options as $key => $val ) {
			if ( !isset($wp_geo_options[$key]) ) {
				$wp_geo_options[$key] = $options[$key];
			} elseif ( empty( $wp_geo_options[$key] ) && in_array( $key, array( 'default_map_latitude', 'default_map_longitude' ) ) ) {
				$wp_geo_options[$key] = $options[$key];
			}
		}
		update_option('wp_geo_options', $wp_geo_options);
		
		// Files
		$wpgeo->markers->register_activation();
		
	}
	
	
	
	/**
	 * @method       Is WP Geo Feed?
	 * @description  Detects whether this is a WP Geo feed.
	 * @return        (boolean)
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
	 * @method       Post Limits
	 * @description  Removes limit on WP Geo feed to show all posts.
	 * @param        $limit = Current limit
	 * @return       (int) Limit
	 */
	
	function post_limits( $limit ) {
	
		global $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			if ( isset($_GET['limit']) && is_numeric($_GET['limit']) ) {
				return 'LIMIT 0, ' . $_GET['limit'];
			}
		}
		return $limit;
		
	}
	
	
	
	/**
	 * @method       Posts Join
	 * @description  Joins the post meta tables onto the results of the posts table.
	 * @param        $join = Current JOIN statement
	 * @return       (string) JOIN string
	 */
	
	function posts_join( $join ) {
	
		global $wpdb, $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			$join .= " LEFT JOIN $wpdb->postmeta ON (" . $wpdb->posts . ".ID = $wpdb->postmeta.post_id)";
		}
		return $join;
		
	}
	
	
	
	/**
	 * @method       Posts Where
	 * @description  Adds extra WHERE clause to the posts results to only include posts with longitude and latitude.
	 * @param        $where = Current WHERE statement
	 * @return       (string) WHERE string
	 */
	
	function posts_where( $where ) {
	
		global $wpdb, $wpgeo;
		
		if ( $wpgeo->is_wpgeo_feed() ) {
			$where .= " AND ($wpdb->postmeta.meta_key = '" . WPGEO_LATITUDE_META . "' OR $wpdb->postmeta.meta_key = '" . WPGEO_LONGITUDE_META . "')";
		}
		return $where;
	
	}
	
	
	
	/**
	 * @method       Check Google API Key
	 * @description  Check that a Google API Key has been entered.
	 * @return       (boolean)
	 */
	
	function checkGoogleAPIKey() {
		
		global $wpgeo;
		
		$wp_geo_options = get_option('wp_geo_options');
		$api_key = $wpgeo->get_google_api_key();
		
		if ( empty($api_key ) || !isset($api_key) ) {
			return false;
		}
		return true;
		
	}
	
	
	
	/**
	 * @method       Get Google API Key
	 * @description  Gets the Google API Key. Passes it through a filter so it can be overriden by another plugin.
	 * @return       (string) API Key
	 */
	
	function get_google_api_key() {
		
		$wp_geo_options = get_option('wp_geo_options');
		return apply_filters( 'wpgeo_google_api_key', $wp_geo_options['google_api_key'] );
		
	}
	
	
	
	/**
	 * @method       Category Map
	 * @description  Outputs the HTML for a category map.
	 * @param        $args = Arguments
	 */
	
	function categoryMap( $args = '' ) {
		
		global $posts;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		$showmap = false;
		
		// Extract args
		$allowed_args = array(
			'width' => null,
			'height' => null
		);
		$args = wp_parse_args($args, $allowed_args);
		
		for ( $i = 0; $i < count($posts); $i++ ) {
			$post = $posts[$i];
			$latitude = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
			$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
			
			if ( is_numeric($latitude) && is_numeric($longitude) ) {
				$showmap = true;
			}
			
		}
		
		$map_width = $wp_geo_options['default_map_width'];
		$map_height = $wp_geo_options['default_map_height'];
		
		if ( $args['width'] != null) {
			$map_width = $args['width'];
			if ( is_numeric($map_width) ) {
				$map_width = $map_width . 'px';
			}
		}
		if ( $args['height'] != null) {
			$map_height = $args['height'];
			if ( is_numeric($map_height) ) {
				$map_height = $map_height . 'px';
			}
		}
		
		if ( $showmap && !is_feed() && $this->checkGoogleAPIKey() ) {
			echo '<div class="wp_geo_map" id="wp_geo_map_visible" style="width:' . $map_width . '; height:' . $map_height . ';"></div>';
		}
		
	}
	
	
	
	/**
	 * @method       Meta Tags
	 * @description  Outputs geo-related meta tags.
	 */
	
	function meta_tags() {
		
		if ( is_single() ) {
			
			global $post;
			
			$lat =  get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
			$long =  get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
			$title =  get_post_meta($post->ID, WPGEO_TITLE_META, true);
			$nl = "\n";
			
			if ( is_numeric($lat) && is_numeric($long) ) {
				echo '<meta name="geo.position" content="' . $lat . ';' . $long . '" />' . $nl; // Geo-Tag: Latitude and longitude
				//echo '<meta name="geo.region" content="DE-BY" />' . $nl;                      // Geo-Tag: Country code (ISO 3166-1) and regional code (ISO 3166-2)
				//echo '<meta name="geo.placename" content="MÙnchen" />' . $nl;                 // Geo-Tag: City or the nearest town
				if ( !empty($title) ) {
					echo '<meta name="DC.title" content="' . $title . '" />' . $nl;             // Dublin Core Meta Tag Title (used by some geo databases)
				}
				echo '<meta name="ICBM" content="' . $lat . ', ' . $long . '" />' . $nl;        // ICBM Tag (prior existing equivalent to the geo.position)
			}
		}
		
	}
	
	
	
	/**
	 * @method       WP Head
	 * @description  Outputs HTML and JavaScript to the header.
	 */
	
	function wp_head() {
		
		global $wpgeo;
		
		$js_map_inits = '';
		$js_marker_inits = '';
		
		$this->meta_tags();
		
		// WP Geo Default Settings
		$wp_geo_options = get_option('wp_geo_options');
		
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
		
		// CSS
		echo '<link rel="stylesheet" href="' . WPGEO_URL . 'css/wp-geo.css" type="text/css" />';
		
		if ( $wpgeo->show_maps() || $wpgeo->widget_is_active() ) {
		
			global $posts;
			
			$this->markers->wp_head();
			
			$wp_geo_options = get_option('wp_geo_options');
			$maptype = empty($wp_geo_options['google_map_type']) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];
			$mapzoom = $wp_geo_options['default_map_zoom'];
			
			// Coords to show on map?
			$coords = array();
			for ( $i = 0; $i < count($posts); $i++ ) {
			
				$post      = $posts[$i];
				$latitude  = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
				$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
				$title     = get_wpgeo_title( $post->ID );
				$marker    = get_post_meta($post->ID, WPGEO_MARKER_META, true);
				$settings  = get_post_meta($post->ID, WPGEO_MAP_SETTINGS_META, true);
				
				$mymaptype = $maptype;
				if ( isset($settings['type']) && !empty($settings['type']) ) {
					$mymaptype = $settings['type'];
				}
				$mymapzoom = $mapzoom;
				if ( isset($settings['zoom']) && is_numeric($settings['zoom']) ) {
					$mymapzoom = $settings['zoom'];
				}
				
				if ( is_numeric($latitude) && is_numeric($longitude) ) {
					$push = array(
						'id' => $post->ID,
						'latitude' => $latitude,
						'longitude' => $longitude,
						'title' => $title,
						'link' => get_permalink($post->ID),
						'post' => $post
					);
					array_push($coords, $push);
					
					// ----------- Start - Create maps for visible posts and pages -----------
					
					$map = new WPGeo_Map($post->ID);										// Create map
					
					// Add point
					$marker_large = empty( $marker ) ? 'large' : $marker;
					$icon = apply_filters( 'wpgeo_marker_icon', $marker_large, $post, 'post' );
					$map->addPoint($latitude, $longitude, $icon, $title, get_permalink($post->ID));
					
					$map->setMapZoom($mymapzoom);										// Set zoom
					$map->setMapType($mymaptype);										// Set map type
					
					if ( !empty( $settings['centre'] ) ) {
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
						$map->addMapType('G_PHYSICAL_MAP');								// Show PHYSICAL map?
					if ( $wp_geo_options['show_map_type_normal'] == 'Y' )
						$map->addMapType('G_NORMAL_MAP');								// Show NORMAL map?
					if ( $wp_geo_options['show_map_type_satellite'] == 'Y' )
						$map->addMapType('G_SATELLITE_MAP');							// Show SATELLITE map?
					if ( $wp_geo_options['show_map_type_hybrid'] == 'Y' )
						$map->addMapType('G_HYBRID_MAP');								// Show HYBRID map?
					
					if ( $wp_geo_options['show_map_scale'] == 'Y' )
						$map->showMapScale(true);										// Show map scale
					if ( $wp_geo_options['show_map_overview'] == 'Y' )
						$map->showMapOverview(true);									// Show map overview
					
					$map->setMapControl($wp_geo_options['default_map_control']);		// Set map control
					array_push($this->maps, $map);										// Add map to maps array
					
					// ----------- End - Create maps for visible posts and pages -----------
					
				}
				
			}
			
			// Need a map?
			if ( count($coords) > 0 ) {
			
				// ----------- Start - Create map for visible posts and pages -----------
				
				$map = new WPGeo_Map('visible');
				$map->show_polyline = true;
				
				// Add points
				for ( $j = 0; $j < count($coords); $j++ ) {
					$marker_small = empty( $marker ) ? 'small' : $marker;
					$icon = apply_filters( 'wpgeo_marker_icon', $marker_small, $coords[$j]['post'], 'multiple' );
					$map->addPoint($coords[$j]['latitude'], $coords[$j]['longitude'], $icon, $coords[$j]['title'], $coords[$j]['link']);
				}
				
				$map->setMapZoom($mapzoom);										// Set zoom
				$map->setMapType($maptype);										// Set map type
				
				if ( $wp_geo_options['show_map_type_physical'] == 'Y' )			// Show PHYSICAL map?
					$map->addMapType('G_PHYSICAL_MAP');
				if ( $wp_geo_options['show_map_type_normal'] == 'Y' )			// Show NORMAL map?
					$map->addMapType('G_NORMAL_MAP');
				if ( $wp_geo_options['show_map_type_satellite'] == 'Y' )			// Show SATELLITE map?
					$map->addMapType('G_SATELLITE_MAP');
				if ( $wp_geo_options['show_map_type_hybrid'] == 'Y' )			// Show HYBRID map?
					$map->addMapType('G_HYBRID_MAP');
				
				if ( $wp_geo_options['show_map_scale'] == 'Y' )
					$map->showMapScale(true);									// Show map scale
				if ( $wp_geo_options['show_map_overview'] == 'Y' )
					$map->showMapOverview(true);								// Show map overview
					
				$map->setMapControl($wp_geo_options['default_map_control']);	// Set map control
				array_push($this->maps, $map);									// Add map to maps array
				
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
				
				function init_wp_geo_map()
				{
					if (GBrowserIsCompatible())
					{
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
	
			// Check if plugin head needed
			// Check for Google API key
			// Write Javascripts and CSS
		
		}
		
	}
	
	
	
	/**
	 * @method       Init
	 * @description  Runs actions on init if Google API Key exists.
	 */
	
	function init() {
	
		// Only show admin things if Google API Key valid
		if ( $this->checkGoogleAPIKey() ) {
		
			// Use the admin_menu action to define the custom boxes
			add_action('admin_menu', array($this, 'add_custom_boxes'));
			
			// Use the save_post action to do something with the data entered
			add_action('save_post', array($this, 'wpgeo_location_save_postdata'));
			
			// Do an action for plugins to detect wether WP Geo is ready
			do_action( 'wpgeo_init', $this );
			
		}
		
	}
	
	
	
	/**
	 * @method       wp
	 * @description  Runs after WordPress is ready.
	 */
	
	function init_later() {
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Support for custom post types
		// Don't add support if on the WP settings page though
		if ( !is_admin() || !isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && $_GET['page'] != 'wp-geo/includes/wp-geo.php' ) ) {
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
	 * @method       Admin Init
	 * @description  Runs actions required in the admin.
	 */
	
	function admin_init() {
		include_once( WPGEO_DIR . 'admin/editor.php' );
		include_once( WPGEO_DIR . 'admin/dashboard.php' );
		include_once( WPGEO_DIR . 'admin/settings.php' );
		
		// Register Settings
		if ( function_exists('register_setting') ) {
			register_setting('wp-geo-options', 'wp_geo_options', '');
		}
		
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
		
		// Show Settings Link
		$this->settings = new WPGeo_Settings();
		
	}
	
	
	
	/**
	 * @method       Admin Head
	 * @description  Outputs HTML to the header in the admin.
	 */
	
	function admin_head() {
	
		global $wpgeo, $post_ID;
		
		echo '<link rel="stylesheet" href="' . WPGEO_URL . 'css/wp-geo.css" type="text/css" />';
		
		// Only load if on a post or page
		if ( $wpgeo->show_maps() ) {
			
			// Get post location
			$latitude = get_post_meta($post_ID, WPGEO_LATITUDE_META, true);
			$longitude = get_post_meta($post_ID, WPGEO_LONGITUDE_META, true);
			$default_latitude = $latitude;
			$default_longitude = $longitude;
			$default_zoom = 13;
			
			$panel_open = false;
			$hide_marker = false;
			
			if ( !$wpgeo->show_maps_external ) {
				echo $wpgeo->mapScriptsInit($default_latitude, $default_longitude, $default_zoom, $panel_open, $hide_marker);
			}
		
		}
		
	}
	
	
	
	/**
	 * @method       Include Google Maps JavaScript API
	 * @description  Queue JavaScripts required by WP Geo.
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
		
		if ( ($wpgeo->show_maps() || $wpgeo->widget_is_active()) && $wpgeo->checkGoogleAPIKey() ) {
			
			// Set Locale
			$locale = $wpgeo->get_googlemaps_locale('&hl=');
			
			wp_register_script('googlemaps', 'http://maps.google.com/maps?file=api&v=2' . $locale . '&key=' . $wpgeo->get_google_api_key() . '&sensor=false', false, '2');
			wp_register_script('wpgeo', WPGEO_URL . 'js/wp-geo.js', array('googlemaps', 'wpgeotooltip'), '1.0');
			wp_register_script('wpgeo-admin-post', WPGEO_URL . 'js/admin-post.js', array('jquery', 'googlemaps'), '1.0');
			wp_register_script('wpgeotooltip', WPGEO_URL . 'js/tooltip.js', array('googlemaps', 'jquery'), '1.0');
			//wp_register_script('jquerywpgeo', WPGEO_URL . 'js/jquery.wp-geo.js', array('jquery', 'googlemaps'), '1.0');
			
			wp_enqueue_script('jquery');
			wp_enqueue_script('googlemaps');
			wp_enqueue_script('wpgeo');
			wp_enqueue_script('wpgeotooltip');
			if ( is_admin() ) {
				 wp_enqueue_script('wpgeo-admin-post');
			}
			//wp_enqueue_script('jquerywpgeo');
			
			return '';
		}
		
	}
	
	
	
	/**
	 * @method       Get Google Maps Locale
	 * @description  See http://code.google.com/apis/maps/faq.html#languagesupport for link to updated languages codes.
	 * @author       Alain Messin, tweaked by Ben :)
	 * @param        $before = Before output
	 * @param        $after = After output
	 * @return       (string) Google locale
	 */
	
	function get_googlemaps_locale( $before = '', $after = '' ) {
		
		$l = get_locale();
		
		if ( !empty($l) ) {

			// WordPress locale is xx_XX, some codes are known by google with - in place of _ , so replace
			$l = str_replace('_', '-', $l);
			
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
			if ( !in_array($l, $codes) ) {
				$l = substr($l, 0, 2);
			}
		
		}
		
		// Apply filter - why not ;)
		$l = apply_filters('wp_geo_locale', $l);
		
		if ( !empty($l) ) {
			$l = $before . $l . $after;
		}
		
		return $l;
		
	}
	
	
	
	/**
	 * @method       Map Scripts Init
	 * @description  Output Javascripts to display maps.
	 * @param        $latitude = Latitude
	 * @param        $longitude = Longitude
	 * @param        $zoom = Zoom
	 * @param        $panel_open = Admin panel open?
	 * @param        $hide_marker = Hide marker?
	 * @return       (string) HTML content
	 */
	
	function mapScriptsInit( $latitude, $longitude, $zoom = 5, $panel_open = false, $hide_marker = false ) {
		
		global $wpgeo, $post;
		
		$wp_geo_options = get_option('wp_geo_options');
		$maptype = empty($wp_geo_options['google_map_type']) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];	
		
		// Centre on London
		if ( !is_numeric($latitude) || !is_numeric($longitude) ) {
			$latitude = $wp_geo_options['default_map_latitude'];
			$longitude = $wp_geo_options['default_map_longitude'];
			$zoom = $wp_geo_options['default_map_zoom']; // Default 5;
			$panel_open = true;
			$hide_marker = true;
		}
		
		$mapcentre = array( $latitude, $longitude );
		
		if ( is_numeric($post->ID) && $post->ID > 0 ) {
			$settings = get_post_meta($post->ID, WPGEO_MAP_SETTINGS_META, true);
			if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
				$zoom = $settings['zoom'];
			}
			if ( !empty($settings['type']) ) {
				$maptype = $settings['type'];
			}
			if ( !empty($settings['centre']) ) {
				$new_mapcentre = explode( ',', $settings['centre'] );
				if ( is_numeric( $new_mapcentre[0] ) && is_numeric( $new_mapcentre[1] ) ) {
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
			
			function init_wp_geo_map_admin()
			{
				if (GBrowserIsCompatible() && document.getElementById("wp_geo_map"))
				{
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
	 * @method       Get The Excerpt
	 * @description  Output Map placeholders on excerpts if set to automatically.
	 * @param        $content = HTML content
	 * @return       (string) HTML content
	 */
	
	function get_the_excerpt( $content = '' ) {
		
		$wp_geo_options = get_option('wp_geo_options');
		if ( $wp_geo_options['show_maps_on_excerpts'] == 'Y' ) {
			return $this->the_content( $content );
		}
		
		return $content;
		
	}
	
	
	
	/**
	 * @method       The Content
	 * @description  Output Map placeholders in the content area if set to automatically.
	 * @param        $content = HTML content
	 * @return       (string) HTML content
	 */
	
	function the_content( $content = '' ) {
	
		global $wpgeo, $posts, $post, $wpdb;
		
		$new_content = '';
		
		if ( $wpgeo->show_maps() && !is_feed() ) {
			
			$wp_geo_options = get_option('wp_geo_options');
			
			// Get the post
			$id = $post->ID;
		
			// Get latitude and longitude
			$latitude = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
			$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
			
			// Need a map?
			if ( is_numeric($latitude) && is_numeric($longitude) ) {
				
				$new_content .= '<div class="wp_geo_map" id="wp_geo_map_' . $id . '" style="width:' . $wp_geo_options['default_map_width'] . '; height:' . $wp_geo_options['default_map_height'] . ';"></div>';
			
				// Run HTML through filter
				$new_content = apply_filters( 'wpgeo_the_content_map', $new_content );
				
			}
			
			// Add map to content
			$show_post_map = apply_filters( 'wpgeo_show_post_map', $wp_geo_options['show_post_map'], $id );
			
			if ( $show_post_map == 'TOP' ) {
				// Show at top of post
				$content = $new_content . $content;
			} elseif ( $show_post_map == 'BOTTOM' ) {
				// Show at bottom of post
				$content = $content . $new_content;
			}
		
		}
		
		return $content;
		
	}

	
	
	/**
	 * @method       Admin Menu
	 * @description  Adds WP Geo settings page menu item.
	 */
	
	function admin_menu() {
		
		global $wpgeo;
		
		if ( function_exists('add_options_page') ) {
			add_options_page( 'WP Geo Options', 'WP Geo', 'manage_options', __FILE__, array( $wpgeo, 'options_page' ) );
		}
		
	}

	
	
	/**
	 * @method       Widget Is Active
	 * @description  Checks if the WP Geo widget is active.
	 */
	
	function widget_is_active() {
		$widgets = array(
			'wpgeo_recent_locations_widget',
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
	 * @method       Show Maps
	 * @description  Checks the current page/scenario and wether maps should be shown.
	 */
	
	function show_maps() {
	
		global $post, $post_ID, $pagenow;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		// Check if domain is correct
		if ( !wpgeo_check_domain() ) {
			return false;
		}
		
		// Widget active
		// if (is_active_widget(array('WPGeo_Widget', 'map_widget'))) return true;
		
		// Check settings
		if ( is_home() && $wp_geo_options['show_maps_on_home'] == 'Y' )					return true;
		if ( is_single() ) {
			if ( function_exists( 'get_post_type' ) && function_exists( 'get_post_type_object' ) && function_exists( 'post_type_supports' ) ) {
				$post_type = get_post_type( $post->ID );
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type == 'post' ) {
					return true;
				} elseif ( $wp_geo_options['show_maps_on_customposttypes'][$post_type] == 'Y' ) {
					return true;
				} elseif ( !$post_type_object->show_ui ) {
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
			if ( is_numeric($post_ID) && $post_ID > 0 ) {
				return true;
			}
			// If writing a new post or page...
			if ( $pagenow == 'post-new.php' || $pagenow == 'page-new.php' ) {
				return true;
			}
		}
		
		// Do Action
		if ( $this->show_maps_external ) {
			return true;
		}
		
		return false;
		
	}
	
	
	
	/**
	 * @method       Options Checkbox
	 * @description  Return the checkbox HTML.
	 * @param        $id = Field ID
	 * @param        $val = Field value
	 * @param        $checked = Checked value
	 * @return       (string) Checkbox HTML
	 */
	
	function options_checkbox( $id, $val, $checked, $disabled = false ) {
	
		$is_checked = '';
		if ( $val == $checked ) {
			$is_checked = 'checked="checked" ';
		}
		
		$is_disabled = '';
		if ( $val == $disabled ) {
			$is_disabled = 'disabled="disabled" ';
		}
		
		return '<input name="' . $id . '" type="checkbox" id="' . $id . '" value="' . $val . '" ' . $is_checked . $is_disabled . '/>';
	
	}
	
	
	
	/**
	 * @method       Options Page
	 * @description  Outputs the options page.
	 */
	
	function options_page() {
		
		global $wpgeo;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		// Process option updates
		if ( isset($_POST['action']) && $_POST['action'] == 'update' ) {
		
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
			
			update_option('wp_geo_options', $wp_geo_options);
			echo '<div class="updated"><p>' . __('WP Geo settings updated', 'wp-geo') . '</p></div>';
			
		}

		// Markers
		$markers = array();
		$markers['large'] = $this->markers->get_marker_by_id( 'large' );
		$markers['small'] = $this->markers->get_marker_by_id( 'small' );
		$markers['dot']   = $this->markers->get_marker_by_id( 'dot' );
		
		
		// Write the form
		echo '
		<div class="wrap">
			<h2>' . __('WP Geo Settings', 'wp-geo') . '</h2>
			<form method="post">
				<img style="float:right; padding:0 20px 0 0; margin:0 0 20px 20px;" src="' . WPGEO_URL . 'img/logo/wp-geo.png" />';
		include( WPGEO_DIR . 'admin/donate-links.php' );	
		echo '<h3>' . __('General Settings', 'wp-geo') . '</h3>
				<p>'
				. sprintf(__("For more information and documentation about this plugin please visit the <a %s>WP Geo Plugin</a> home page.", 'wp-geo'), 'href="http://www.benhuson.co.uk/wordpress-plugins/wp-geo/"') . '<br />'
				. sprintf(__("If you experience any problems/bugs with the plugin, please <a %s>log it here</a>.", 'wp-geo'), 'href="http://code.google.com/p/wp-geo/issues/list"') . 
				'</p>';
        if ( !$wpgeo->markers->marker_folder_exists() ) {
            echo '<div class="error"><p>' . sprintf( __( "Unable to create the markers folder %s.<br />Please create it and copy the marker images to it from %s</p>", 'wp-geo' ), str_replace( ABSPATH, '', $wpgeo->markers->upload_dir ) . '/wp-geo/markers/', str_replace( ABSPATH, '' ,WP_PLUGIN_DIR ) . WPGEO_SUBDIR . 'img/markers' ) . '</div>';
        }
		if ( !$this->checkGoogleAPIKey() ) {
			echo '<div class="error"><p>Before you can use Wp Geo you must acquire a <a href="http://code.google.com/apis/maps/documentation/javascript/v2/introduction.html#Obtaining_Key">Google API Key</a> for your blog - the plugin will not function without it!</p></div>';
		}
		echo '<table class="form-table">
					<tr valign="top">
						<th scope="row">' . __('Google API Key', 'wp-geo') . '</th>
						<td><input name="google_api_key" type="text" id="google_api_key" value="' . $wp_geo_options['google_api_key'] . '" size="50" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Map Type', 'wp-geo') . '</th>
						<td>' . $wpgeo->google_map_types('menu', $wp_geo_options['google_map_type']) . '</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Show Post Map', 'wp-geo') . '</th>
						<td>' . $wpgeo->post_map_menu('menu', $wp_geo_options['show_post_map']) . '<br />
							' . $wpgeo->options_checkbox('show_maps_on_excerpts', 'Y', $wp_geo_options['show_maps_on_excerpts']) . ' ' . __('Show on excerpts', 'wp-geo') . '
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Map Location', 'wp-geo') . '</th>
						<td>
							<label for="default_map_latitude" style="width:70px; display:inline-block;">Latitude</label> <input name="default_map_latitude" type="text" id="default_map_latitude" value="' . $wp_geo_options['default_map_latitude'] . '" size="25" /><br />
							<label for="default_map_longitude" style="width:70px; display:inline-block;">Longitude</label> <input name="default_map_longitude" type="text" id="default_map_longitude" value="' . $wp_geo_options['default_map_longitude'] . '" size="25" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Map Width', 'wp-geo') . '</th>
						<td><input name="default_map_width" type="text" id="default_map_width" value="' . $wp_geo_options['default_map_width'] . '" size="10" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Map Height', 'wp-geo') . '</th>
						<td><input name="default_map_height" type="text" id="default_map_height" value="' . $wp_geo_options['default_map_height'] . '" size="10" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Map Zoom', 'wp-geo') . '</th>
						<td>' . $wpgeo->selectMapZoom('menu', $wp_geo_options['default_map_zoom']) . '</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Map Controls', 'wp-geo') . '</th>
						<td>
							' . $wpgeo->selectMapControl('menu', $wp_geo_options['default_map_control']). '<br />
							<p style="margin:1em 0 0 0;"><strong>' . __('Map Type Controls', 'wp-geo') . '</strong></p>
							<p style="margin:0;">' . __('You must select at least 2 map types for the control to show.', 'wp-geo') . '</p>
							' . $wpgeo->options_checkbox('show_map_type_normal', 'Y', $wp_geo_options['show_map_type_normal']) . ' ' . __('Normal map', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_map_type_satellite', 'Y', $wp_geo_options['show_map_type_satellite']) . ' ' . __('Satellite (photographic map)', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_map_type_hybrid', 'Y', $wp_geo_options['show_map_type_hybrid']) . ' ' . __('Hybrid (photographic map with normal features)', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_map_type_physical', 'Y', $wp_geo_options['show_map_type_physical']) . ' ' . __('Physical (terrain map)', 'wp-geo') . '<br />
							<p style="margin:1em 0 0 0;"><strong>' . __('Other Controls', 'wp-geo') . '</strong></p>
							' . $wpgeo->options_checkbox('show_map_scale', 'Y', $wp_geo_options['show_map_scale']) . ' ' . __('Show map scale', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_map_overview', 'Y', $wp_geo_options['show_map_overview']) . ' ' . __('Show collapsible overview map (in the corner of the map)', 'wp-geo') . '
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Default Post Options', 'wp-geo') . '</th>
						<td>
							<p style="margin:0;">
							' . $wpgeo->options_checkbox('save_post_zoom', 'Y', $wp_geo_options['save_post_zoom']) . ' ' . __('Save custom map zoom for this post', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('save_post_map_type', 'Y', $wp_geo_options['save_post_map_type']) . ' ' . __('Save custom map type for this post', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('save_post_centre_point', 'Y', $wp_geo_options['save_post_centre_point']) . ' ' . __('Save map centre point for this post', 'wp-geo') . '
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Polylines', 'wp-geo') . '</th>
						<td>' . $wpgeo->options_checkbox('show_polylines', 'Y', $wp_geo_options['show_polylines']) . ' ' . __('Show polylines (to connect multiple points on a single map)', 'wp-geo') . '</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Polyline Colour', 'wp-geo') . '</th>
						<td><input name="polyline_colour" type="text" id="polyline_colour" value="' . $wp_geo_options['polyline_colour'] . '" size="7" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Show Maps On', 'wp-geo') . '</th>
						<td>
							' . $wpgeo->options_checkbox('show_maps_on_pages', 'Y', $wp_geo_options['show_maps_on_pages']) . ' ' . __('Pages', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_on_posts', 'Y', $wp_geo_options['show_maps_on_posts']) . ' ' . __('Posts (single posts)', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_on_home', 'Y', $wp_geo_options['show_maps_on_home']) . ' ' . __('Posts home page', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_datearchives', 'Y', $wp_geo_options['show_maps_in_datearchives']) . ' ' . __('Posts in date archives', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_categoryarchives', 'Y', $wp_geo_options['show_maps_in_categoryarchives']) . ' ' . __('Posts in category archives', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_tagarchives', 'Y', $wp_geo_options['show_maps_in_tagarchives']) . ' ' . __('Posts in tag archives', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_taxarchives', 'Y', $wp_geo_options['show_maps_in_taxarchives']) . ' ' . __('Posts in taxonomy archives', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_authorarchives', 'Y', $wp_geo_options['show_maps_in_authorarchives']) . ' ' . __('Posts in author archives', 'wp-geo') . '<br />
							' . $wpgeo->options_checkbox('show_maps_in_searchresults', 'Y', $wp_geo_options['show_maps_in_searchresults']) . ' ' . __('Search Results', 'wp-geo') . '<br />';
		if ( function_exists( 'get_post_types' ) && function_exists( 'post_type_supports' ) ) {
			$custom_post_type_checkboxes = '';
			$post_types = get_post_types( array(), 'objects' );
			foreach ( $post_types as $post_type ) {
				if ( $post_type->name == 'post' || $post_type->name == 'page' ) {
					continue;
				}
				if ( $post_type->show_ui ) {
					$custom_post_type_checkbox_value = isset( $wp_geo_options['show_maps_on_customposttypes'][$post_type->query_var] ) ? $wp_geo_options['show_maps_on_customposttypes'][$post_type->query_var] : '';
					$custom_post_type_disabled = false;
					if ( post_type_supports( $post_type->query_var, 'wpgeo' ) ) {
						$custom_post_type_checkbox_value = 'Y';
						$custom_post_type_disabled = true;
					}
					$custom_post_type_checkboxes .= $wpgeo->options_checkbox( 'show_maps_on_customposttypes[' . $post_type->query_var . ']', 'Y', $custom_post_type_checkbox_value, $custom_post_type_disabled ) . ' ' . __( $post_type->label, 'wp-geo' ) . '<br />';
				} elseif ( post_type_supports( $post_type->query_var, 'wpgeo' )) {
					$custom_post_type_checkboxes .= $wpgeo->options_checkbox( 'show_maps_on_customposttypes[' . $post_type->query_var . ']', 'Y', 'Y', true ) . ' ' . __( $post_type->label, 'wp-geo' ) . '<br />';
				
				}
			}
			if ( !empty( $custom_post_type_checkboxes ) ) {
				$custom_post_type_checkboxes = '<strong>Custom Post Types</strong><br />' . $custom_post_type_checkboxes;
			}
			echo $custom_post_type_checkboxes;
		}
		echo '
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">' . __('Feeds', 'wp-geo') . '</th>
						<td>' . $wpgeo->options_checkbox('add_geo_information_to_rss', 'Y', $wp_geo_options['add_geo_information_to_rss']) . ' ' . __('Add geographic information', 'wp-geo') . '</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="Submit" value="' . __('Save Changes', 'wp-geo') . '" />
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="option_fields" value="google_api_key,google_map_type,show_post_map" />
				</p>
				<h2 style="margin-top:30px;">' . __('Marker Settings', 'wp-geo') . '</h2>'
				. __('<p>Custom marker images are automatically created in your WordPress uploads folder and used by WP Geo.<br />A copy of these images will remain in the WP Geo folder in case you need to revert to them at any time.<br />You may edit these marker icons if you wish - they must be PNG files. Each marker consist of a marker image and a shadow image. If you do not wish to show a marker shadow you should use a transparent PNG for the shadow file.</p><p>Currently you must update these images manually and the anchor point must be the same - looking to provide more control in future versions.</p>', 'wp-geo') . '
				' . $wpgeo->markers->get_admin_display();
		if ( function_exists('register_setting') && function_exists('settings_fields') ) {
			settings_fields('wp-geo-options'); 
		}	
		echo '</form>
			<h2 style="margin-top:30px;">' . __('Documentation', 'wp-geo') . '</h2>'
			. __('<p>If you set the Show Post Map setting to &quot;Manual&quot;, you can use the Shortcode <code>[wp_geo_map]</code> in a post to display a map (if a location has been set for the post). You can only include the Shortcode once within a post. If you select another Show Post Map option then the Shortcode will be ignored and the map will be positioned automatically.</p>', 'wp-geo')
			. '<h2 style="margin-top:30px;">' . __('Feedback', 'wp-geo') . '</h2>'
			. sprintf(__("<p>If you experience any problems or bugs with the plugin, or want to suggest an improvement, please visit the <a %s>WP Geo Google Code page</a> to log your issue. If you would like to feedback or comment on the plugin please visit the <a %s>WP Geo plugin</a> page.</p>", 'wp-geo'), 'href="http://code.google.com/p/wp-geo/issues/list"', 'href="http://www.benhuson.co.uk/wordpress-plugins/wp-geo/"')
			. sprintf(__("<p>If you like WP Geo and would like to make a donation, please do so on the <a %s>WP Geo website</a>. Your contributions help to ensure that I can dedicate more time to the support and development of the plugin.</p>", 'wp-geo'), 'href="http://www.wpgeo.com/" target="_blank"') . '
		</div>';
		
	}
	
	
	
	/**
	 * @method       Select Map Control
	 * @description  Map control array or menu.
	 * @param        $return = Array or menu type
	 * @param        $selected = Selected value
	 * @return       (array or string) Array or menu HTML
	 */
	
	function selectMapControl( $return = 'array', $selected = '' ) {
		
		// Array
		$map_type_array = array(
			'GLargeMapControl3D'  => __('Large 3D pan/zoom control', 'wp-geo'), 
			'GLargeMapControl'    => __('Large pan/zoom control', 'wp-geo'), 
			'GSmallMapControl'    => __('Smaller pan/zoom control', 'wp-geo'), 
			'GSmallZoomControl3D' => __('Small 3D zoom control (no panning controls)', 'wp-geo'), 
			'GSmallZoomControl'   => __('Small zoom control (no panning controls)', 'wp-geo'), 
			''                    => __('No pan/zoom controls', 'wp-geo')
		);
		
		// Menu?
		if ( $return = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$is_selected = $selected == $key ? ' selected="selected"' : '';
				$menu .= '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
			}
			$menu = '<select name="default_map_control" id="default_map_control">' . $menu. '</select>';
			return $menu;
		}
		
		// Default return
		return $map_type_array;
		
	}
	
	
	
	/**
	 * @method       Select Map Zoom
	 * @description  Map zoom array or menu.
	 * @param        $return = Array or menu type
	 * @param        $selected = Selected value
	 * @return       (array or string) Array or menu HTML
	 */
	
	function selectMapZoom( $return = 'array', $selected = '', $args = null ) {
		
		// Defaults
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
			'0' 	=> '0 - ' . __('Zoomed Out', 'wp-geo'), 
			'1' 	=> '1', 
			'2' 	=> '2', 
			'3' 	=> '3', 
			'4' 	=> '4', 
			'5' 	=> '5', 
			'6' 	=> '6', 
			'7' 	=> '7', 
			'8' 	=> '8', 
			'9' 	=> '9', 
			'10' 	=> '10', 
			'11' 	=> '11', 
			'12' 	=> '12', 
			'13' 	=> '13', 
			'14' 	=> '14', 
			'15' 	=> '15', 
			'16' 	=> '16', 
			'17' 	=> '17', 
			'18' 	=> '18', 
			'19' 	=> '19 - ' . __('Zoomed In', 'wp-geo'), 
		);
		
		// Menu?
		if ( $return = 'menu' ) {
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
	 * @method       Google Map Types
	 * @description  Map type array or menu.
	 * @param        $return = Array or menu type
	 * @param        $selected = Selected value
	 * @return       (array or string) Array or menu HTML
	 */
	
	function google_map_types( $return = 'array', $selected = '', $args = null ) {
		
		// Defaults
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
			'G_NORMAL_MAP' 		=> __('Normal', 'wp-geo'), 
			'G_SATELLITE_MAP' 	=> __('Satellite (photographic map)', 'wp-geo'), 
			'G_HYBRID_MAP' 		=> __('Hybrid (photographic map with normal features)', 'wp-geo'),
			'G_PHYSICAL_MAP' 	=> __('Physical (terrain map)', 'wp-geo')
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
	 * @method       Post Map Menu
	 * @description  Map position array or menu.
	 * @param        $return = Array or menu type
	 * @param        $selected = Selected value
	 * @return       (array or string) Array or menu HTML
	 */
	
	function post_map_menu( $return = 'array', $selected = '' ) {
		
		// Array
		$map_type_array = array(
			'TOP' 		=> __('At top of post', 'wp-geo'), 
			'BOTTOM' 	=> __('At bottom of post', 'wp-geo'), 
			'HIDE' 		=> __('Manually', 'wp-geo')
		);
		
		// Menu?
		if ( $return = 'menu' ) {
			$menu = '';
			foreach ( $map_type_array as $key => $val ) {
				$is_selected = $selected == $key ? ' selected="selected"' : '';
				$menu .= '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
			}
			$menu = '<select name="show_post_map" id="show_post_map">' . $menu. '</select>';
			return $menu;
		}
		
		// Default return
		return $map_type_array;
		
	}
	
	
	
	/**
	 * @method       After Plugin Row
	 * @description  This function can be used to insert text after the WP Geo plugin row on the plugins page.
	 *               Useful if you need to tell people something important before they upgrade.
	 * @param        $plugin = Plugin reference
	 */
	
	function after_plugin_row( $plugin ) {
		
		if ( 'wp-geo/wp-geo.php' == $plugin && !empty($this->plugin_message) ) {
			//echo '<td colspan="5" class="plugin-update" style="line-height:1.2em;">' . $this->plugin_message . '</td>';
			return;
		}
		
	}
	
	
	
	/**
	 * @method       Get WP Geo Posts
	 * @description  This function can be used to insert text after the WP Geo plugin row on the plugins page.
	 *               Useful if you need to tell people something important before they upgrade.
	 * @param        $args = Arguments
	 * @return       (array) of points
	 */
	
	function get_wpgeo_posts( $args = 'numberposts=5' ) {
		
		global $customFields;
		
		$default_args = array('numberposts' => 5);
		$arguments = wp_parse_args($args, $default_args);
		extract($arguments, EXTR_SKIP);
		
		$customFields = "'" . WPGEO_LONGITUDE_META . "', '" . WPGEO_LATITUDE_META . "'";
		$custom_posts = new WP_Query();
		
		add_filter('posts_join', array($this, 'get_custom_field_posts_join'));
		add_filter('posts_groupby', array($this, 'get_custom_field_posts_group'));
		
		$custom_posts->query('showposts=' . $numberposts); // Uses same parameters as query_posts
		
		remove_filter('posts_join', array($this, 'get_custom_field_posts_join'));
		remove_filter('posts_groupby', array($this, 'get_custom_field_posts_group'));
		
		$points = array();
		
		while ( $custom_posts->have_posts() ) {
			$custom_posts->the_post();
			$id   = get_the_ID();
			$long = get_post_custom_values( WPGEO_LONGITUDE_META );
			$lat  = get_post_custom_values( WPGEO_LATITUDE_META );
			$points[] = array('id' => $id, 'long' => $long, 'lat' => $lat);
		}
		
		return $points;
		
	}
	
	
	
	/**
	 * @method       Get Custom Field Posts Join
	 * @description  Join custom fields on to results.
	 * @param        $join = JOIN statement
	 * @return       (string) SQL
	 */
	
	function get_custom_field_posts_join( $join ) {
	
		global $wpdb, $customFields;
		return $join . " JOIN $wpdb->postmeta postmeta ON (postmeta.post_id = $wpdb->posts.ID and postmeta.meta_key in ($customFields))";
	
	}
	
	
	
	/**
	 * @method       Get Custom Field Posts Group
	 * @description  Group by post id.
	 * @param        $group = GROUP BY statement
	 * @return       (string) SQL
	 */
	
	function get_custom_field_posts_group( $group ) {
	
		global $wpdb;
		$group .= " $wpdb->posts.ID ";
		return $group;
		
	}
	
	
	
	/* =============== Admin Edit Pages =============== */
	
	
	
	/**
	 * @method       Add Custom Boxes
	 * @description  Adds a custom section to the "advanced" Post and Page edit screens using the admin_menu hook.
	 */
	
	function add_custom_boxes() {
		
		global $post;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		if ( function_exists( 'add_meta_box') ) {
			add_meta_box('wpgeo_location', __('WP Geo Location', 'wpgeo'), array($this, 'wpgeo_location_inner_custom_box'), 'post', 'advanced');
			add_meta_box('wpgeo_location', __('WP Geo Location', 'wpgeo'), array($this, 'wpgeo_location_inner_custom_box'), 'page', 'advanced');
			
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
			
		} else {
			add_action('dbx_post_advanced', array($this, 'wpgeo_location_old_custom_box'));
			add_action('dbx_page_advanced', array($this, 'wpgeo_location_old_custom_box'));
		}
		
	}
	
	
	
	/**
	 * @method       WP Geo Location Inner Custom Box
	 * @description  Prints the inner fields for the custom post/page section.
	 */
	
	function wpgeo_location_inner_custom_box() {
		
		global $post;
		
		$wp_geo_options = get_option('wp_geo_options');
		
		$search    = '';
		$latitude  = get_post_meta($post->ID, WPGEO_LATITUDE_META, true);
		$longitude = get_post_meta($post->ID, WPGEO_LONGITUDE_META, true);
		$title     = get_post_meta($post->ID, WPGEO_TITLE_META, true);
		$marker    = get_post_meta($post->ID, WPGEO_MARKER_META, true);
		$settings  = get_post_meta($post->ID, WPGEO_MAP_SETTINGS_META, true);
		
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
		
		if ( isset($settings['zoom']) && !empty($settings['zoom']) ) {
			$wpgeo_map_settings_zoom = $settings['zoom'];
			$wpgeo_map_settings_zoom_checked = ' checked="checked"';
		} elseif ( $wp_geo_options['save_post_zoom'] == 'Y' ) {
			$wpgeo_map_settings_zoom = $wp_geo_options['save_post_zoom'];
			$wpgeo_map_settings_zoom_checked = ' checked="checked"';
		}
		if ( isset($settings['type']) && !empty($settings['type']) ) {
			$wpgeo_map_settings_type = $settings['type'];
			$wpgeo_map_settings_type_checked = ' checked="checked"';
		} elseif ( $wp_geo_options['save_post_zoom'] == 'Y' ) {
			$wpgeo_map_settings_type = $wp_geo_options['save_post_zoom'];
			$wpgeo_map_settings_type_checked = ' checked="checked"';
		}
		if ( isset($settings['centre']) && !empty($settings['centre']) ) {
			$wpgeo_map_settings_centre = $settings['centre'];
			$wpgeo_map_settings_centre_checked = ' checked="checked"';
		} elseif ( $wp_geo_options['save_post_centre_point'] == 'Y' ) {
			$wpgeo_map_settings_centre = $wp_geo_options['save_post_centre_point'];
			$wpgeo_map_settings_centre_checked = ' checked="checked"';
		}
		
		// Use nonce for verification
		echo '<input type="hidden" name="wpgeo_location_noncename" id="wpgeo_location_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';
		
		// The actual fields for data entry
		echo '<table cellpadding="3" cellspacing="5" class="form-table">
			<tr>
				<th scope="row">' . __('Search for location', 'wp-geo') . '<br /><span style="font-weight:normal;">(' . __('town, postcode or address', 'wp-geo') . ')</span></th>
				<td><input name="wp_geo_search" type="text" size="45" id="wp_geo_search" value="' . $search . '" />
					<input type="hidden" name="wp_geo_base_country_code" id="wp_geo_base_country_code" value="' . apply_filters( 'wpgeo_base_country_code', '' ) . '" />
					<span class="submit"><input type="button" id="wp_geo_search_button" name="wp_geo_search_button" value="' . __('Search', 'wp-geo') . '" /></span></td>
			</tr>
			<tr>
				<td colspan="2">
				<div id="wp_geo_map" style="height:300px; width:100%; padding:0px; margin:0px;">
					' . __('Loading Google map, please wait...', 'wp-geo') . '
				</div>
				</td>
			</tr>
			<tr>
				<th scope="row">' . __('Latitude', 'wp-geo') . ', ' . __('Longitude', 'wp-geo') . '</th>
				<td><input name="wp_geo_latitude" type="text" size="25" id="wp_geo_latitude" value="' . $latitude . '" /><br />
					<input name="wp_geo_longitude" type="text" size="25" id="wp_geo_longitude" value="' . $longitude . '" /><br />
					<a href="#" class="wpgeo-clear-location-fields">' . __('clear location', 'wp-geo') . '</a> | <a href="#" class="wpgeo-centre-location">' . __('centre location', 'wp-geo') . '</a>
				</td>
			</tr>
			<tr>
				<th scope="row">' . __('Marker Title', 'wp-geo') . ' <small>(' . __('optional', 'wp-geo') . ')</small></th>
				<td><input name="wp_geo_title" type="text" size="25" style="width:100%;" id="wp_geo_title" value="' . $title . '" /></td>
			</tr>
			<tr>
				<th scope="row">' . __('Marker Image', 'wp-geo') . '</th>
				<td>' . $this->markers->dropdown_markers( $markers_menu ) . '</td>
			</tr>
			<tr>
				<th scope="row">' . __('Map Settings', 'wp-geo') . '</th>
				<td>
					<label for="wpgeo_map_settings_zoom"><input type="checkbox" name="wpgeo_map_settings_zoom" id="wpgeo_map_settings_zoom" value="' . $wpgeo_map_settings_zoom . '"' . $wpgeo_map_settings_zoom_checked . ' /> ' . __('Save custom map zoom for this post', 'wp-geo') . '</label><br />
					<label for="wpgeo_map_settings_type"><input type="checkbox" name="wpgeo_map_settings_type" id="wpgeo_map_settings_type" value="' . $wpgeo_map_settings_type . '"' . $wpgeo_map_settings_type_checked . ' /> ' . __('Save custom map type for this post', 'wp-geo') . '</label><br />
					<label for="wpgeo_map_settings_centre"><input type="checkbox" name="wpgeo_map_settings_centre" id="wpgeo_map_settings_centre" value="' . $wpgeo_map_settings_centre . '"' . $wpgeo_map_settings_centre_checked . ' /> ' . __('Save map centre point for this post', 'wp-geo') . '</label>
				</td>
			</tr>
			' . apply_filters( 'wpgeo_edit_post_map_fields', '', $post->ID ) . '
		</table>';
		
	}
	
	
	
	/**
	 * @method       WP Geo Location Old Custom Box
	 * @description  Prints the edit form for pre-WordPress 2.5 post/page.
	 */
	
	function wpgeo_location_old_custom_box() {
	
		echo '<div class="dbx-b-ox-wrapper">' . "\n";
		echo '<fieldset id="wpgeo_location_fieldsetid" class="dbx-box">' . "\n";
		echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' . __('WP Geo Location', 'wpgeo') . "</h3></div>";   
		echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';
		
		// output editing form
		wpgeo_location_inner_custom_box();
		
		echo "</div></div></fieldset></div>\n";
		
	}
	
	
	
	/**
	 * @method       WP Geo Location Save post data
	 * @description  When the post is saved, saves our custom data.
	 * @param        $post_id = Post ID
	 */
	
	function wpgeo_location_save_postdata( $post_id ) {
	
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset( $_POST['wpgeo_location_noncename'] ) || ( isset( $_POST['wpgeo_location_noncename'] ) && !wp_verify_nonce( $_POST['wpgeo_location_noncename'], plugin_basename( __FILE__ ) ) ) ) {
			return $post_id;
		}
		
		// Authenticate user
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can('edit_page', $post_id) )
				return $post_id;
		} elseif ( 'post' == $_POST['post_type'] ) {
			if ( !current_user_can('edit_post', $post_id) )
				return $post_id;
		} elseif ( function_exists( 'get_post_type_object' ) ) {
			$post_type = get_post_type_object( $_POST['post_type'] );
			// Should this be "edit_" . $post_type->capability_type
			if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
				return $post_id;
		}
		
		$mydata = array();
		
		// Find and save the location data
		if ( isset($_POST['wp_geo_latitude']) && isset($_POST['wp_geo_longitude']) ) {
			
			// Only delete post meta if isset (to avoid deletion in bulk/quick edit mode)
			delete_post_meta($post_id, WPGEO_LATITUDE_META);
			delete_post_meta($post_id, WPGEO_LONGITUDE_META);
			
			if ( is_numeric($_POST['wp_geo_latitude']) && is_numeric($_POST['wp_geo_longitude']) ) {
				
				add_post_meta($post_id, WPGEO_LATITUDE_META, $_POST['wp_geo_latitude']);
				add_post_meta($post_id, WPGEO_LONGITUDE_META, $_POST['wp_geo_longitude']);
				
				$mydata[WPGEO_LATITUDE_META]  = $_POST['wp_geo_latitude'];
				$mydata[WPGEO_LONGITUDE_META] = $_POST['wp_geo_longitude'];
				
			}
			
		}
		
		// Find and save the title data
		if ( isset($_POST['wp_geo_title']) ) {
			
			delete_post_meta($post_id, WPGEO_TITLE_META);
			
			if ( !empty($_POST['wp_geo_title']) ) {
				add_post_meta($post_id, WPGEO_TITLE_META, $_POST['wp_geo_title']);
				$mydata[WPGEO_TITLE_META]  = $_POST['wp_geo_title'];
			}
			
		}
		
		// Find and save the marker data
		if ( isset( $_POST['wp_geo_marker'] ) ) {
			if ( !empty($_POST['wp_geo_marker']) ) {
				update_post_meta( $post_id, WPGEO_MARKER_META, $_POST['wp_geo_marker'] );
				$mydata[WPGEO_MARKER_META] = $_POST['wp_geo_marker'];
			} else {
				delete_post_meta( $post_id, WPGEO_MARKER_META );
			}
		}
		
		// Find and save the settings data
		delete_post_meta($post_id, WPGEO_MAP_SETTINGS_META);
		
		$settings = array();
		if ( isset($_POST['wpgeo_map_settings_zoom']) && !empty($_POST['wpgeo_map_settings_zoom']) ) {
			$settings['zoom'] = $_POST['wpgeo_map_settings_zoom'];
		}
		if ( isset($_POST['wpgeo_map_settings_type']) && !empty($_POST['wpgeo_map_settings_type']) ) {
			$settings['type'] = $_POST['wpgeo_map_settings_type'];
		}
		if ( isset($_POST['wpgeo_map_settings_centre']) && !empty($_POST['wpgeo_map_settings_centre']) ) {
			$settings['centre'] = $_POST['wpgeo_map_settings_centre'];
		}
		
		add_post_meta($post_id, WPGEO_MAP_SETTINGS_META, $settings);
		$mydata[WPGEO_MAP_SETTINGS_META] = $settings;
		
		return $mydata;
	
	}
	
	
	
	function wp_footer() {
		
		$js = $this->maps2->get_maps_javascript();
		
		if ( !empty( $js ) ) {
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