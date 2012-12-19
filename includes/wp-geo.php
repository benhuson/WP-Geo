<?php

/**
 * WP Geo class
 * The main WP Geo class - this is where it all happens.
 */
class WPGeo {
	
	// Version Information
	var $version    = '3.2.7.1';
	var $db_version = 1;
	
	var $api;
	var $admin;
	var $markers;
	var $show_maps_external = false;
	var $maps;
	var $maps2;
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
			//update_option( 'wp_geo_show_version_msg', 'Y' );
			update_option( 'wp_geo_version', $this->version );
		}
		
		// API
		$wp_geo_options = get_option( 'wp_geo_options' );
		if ( ( is_admin() && 'googlemapsv3' == $wp_geo_options['admin_api'] ) || ( ! is_admin() && 'googlemapsv3' == $wp_geo_options['public_api'] ) ) {
			include_once( WPGEO_DIR . 'api/googlemapsv3/googlemapsv3.php' );
			$this->api = new WPGeo_API_GoogleMapsV3();
		} else {
			include_once( WPGEO_DIR . 'api/googlemapsv2/googlemapsv2.php' );
			$this->api = new WPGeo_API_GoogleMapsV2();
		}
		
		$this->maps    = array();
		$this->maps2   = new WPGeo_Maps();
		$this->markers = new WPGeo_Markers();
		$this->feeds   = new WPGeo_Feeds();
		
		// Action Hooks
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'init_later' ), 10000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'includeGoogleMapsJavaScriptAPI' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
		add_action( 'wp_footer', array( $this, 'wp_footer' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Filters
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'get_the_excerpt', array( $this, 'get_the_excerpt' ) );
		add_filter( 'post_limits', array( $this, 'post_limits' ) );
		add_filter( 'posts_join', array( $this, 'posts_join' ) );
		add_filter( 'posts_where', array( $this, 'posts_where' ) );
		add_filter( 'option_wp_geo_options', array( $this, 'option_wp_geo_options' ) );
		
		// Admin
		if ( is_admin() ) {
			include_once( WPGEO_DIR . 'admin/admin.php' );
			$this->admin = new WPGeo_Admin();
		}
	}
	
	/**
	 * Filter 'wp_geo_options' value to ensure all defaults are set.
	 *
	 * @param array $option Option values.
	 * @return array Default values.
	 */
	function option_wp_geo_options( $option ) {
		return wp_parse_args( $option, $this->default_option_values() );
	}
	
	/**
	 * Default Option Values
	 */
	function default_option_values() {
		return array(
			'public_api'                    => 'googlemapsv2',
			'admin_api'                     => 'googlemapsv2',
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
	}
	
	/**
	 * Register Activation
	 * Runs when the plugin is activated - creates options etc.
	 */
	function register_activation() {
		$wpgeo = new WPGeo();
		
		$options = $this->default_option_values();
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
				if ( 'googlemapsv3' == $wp_geo_options['public_api'] ) {
					
					// Google Maps v3
					$html_content = '
						<script type="text/javascript">
						//<![CDATA[
						
						var map = null; ' . $js_map_inits . '
						var marker = null; ' . $js_marker_inits . '
						
						function init_wp_geo_map() {
							' . $js_map_writes . '
						}
						google.maps.event.addDomListener(window, "load", init_wp_geo_map);
						//]]>
						</script>';
				} else {
					
					// Google Maps v2
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
				}
				
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
	 * Include Google Maps JavaScript API
	 * Queue JavaScripts required by WP Geo.
	 *
	 * @todo Maps API changes.
	 */
	function includeGoogleMapsJavaScriptAPI() {
		global $wpgeo;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$http = is_ssl() ? 'https' : 'http';
		
		wp_register_script( 'wpgeo_tooltip', WPGEO_URL . 'js/tooltip.js', array( 'jquery' ), $this->version );
		if ( 'googlemapsv3' == $wp_geo_options['admin_api'] ) {
			wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.v3.js', array( 'jquery', 'wpgeo_tooltip' ), $this->version );
		} else {
			wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.js', array( 'jquery', 'wpgeo_tooltip' ), $this->version );
		}
		wp_register_script( 'wpgeo_admin_post', WPGEO_URL . 'js/admin-post.js', array( 'jquery', 'wpgeo' ), $this->version );
		
		// Select API to use...
		if ( ( $wpgeo->show_maps() || $wpgeo->widget_is_active() ) ) {
			if ( ( is_admin() && 'googlemapsv3' == $wp_geo_options['admin_api'] ) || ( ! is_admin() && 'googlemapsv3' == $wp_geo_options['public_api'] ) ) {
				
				// Google Maps v3
				if ( $wpgeo->checkGoogleAPIKey() ) {
					$googlemaps_js = add_query_arg( array(
						'region' => $wpgeo->get_googlemaps_locale(),
						'key'    => $wpgeo->get_google_api_key(),
						'sensor' => 'false'
					), $http . '://maps.googleapis.com/maps/api/js' );
					
					wp_register_script( 'googlemaps3', $googlemaps_js, false, $this->version );
					wp_register_script( 'wpgeo_admin_post_googlemaps3', WPGEO_URL . 'api/googlemapsv3/js/admin-post.js', array( 'jquery', 'wpgeo_admin_post', 'googlemaps3' ), $this->version );
					
					wp_enqueue_script( 'wpgeo' );
					wp_enqueue_script( 'googlemaps3' );
					if ( is_admin() ) {
						 wp_enqueue_script( 'wpgeo_admin_post_googlemaps3' );
					}
				}
			} else {
				
				// Google Maps v2
				if ( $wpgeo->checkGoogleAPIKey() ) {
					$googlemaps_js = add_query_arg( array(
						'v'      => 2,
						'hl'     => $wpgeo->get_googlemaps_locale(),
						'key'    => $wpgeo->get_google_api_key(),
						'sensor' => 'false'
					), $http . '://maps.google.com/maps?file=api' );
					
					wp_register_script( 'googlemaps2', $googlemaps_js, false, $this->version );
					wp_register_script( 'wpgeo_admin_post_googlemaps2', WPGEO_URL . 'api/googlemapsv2/js/admin-post.js', array( 'jquery', 'wpgeo_admin_post', 'googlemaps2' ), $this->version );
					
					wp_enqueue_script( 'wpgeo' );
					wp_enqueue_script( 'googlemaps2' );
					if ( is_admin() ) {
						 wp_enqueue_script( 'wpgeo_admin_post_googlemaps2' );
					}
				}
			}
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
	 * API String
	 */
	function api_string( $text, $context ) {
		return apply_filters( 'wpgeo_api_string', $text, $text, $context );
	}
	
	/**
	 * Decode API String
	 */
	function decode_api_string( $text, $context ) {
		return apply_filters( 'wpgeo_decode_api_string', $text, $text, $context );
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
				if ( count( $new_mapcentre ) > 1 && wpgeo_is_valid_geo_coord( $new_mapcentre[0], $new_mapcentre[1] ) ) {
					$mapcentre = $new_mapcentre;
				}
			}
		}
		
		// Vars
		$google_maps_api_key = $wpgeo->get_google_api_key();
		$panel_open = ! $hide_marker || $panel_open ? '.removeClass("closed")' : '';
		
		// Script
		// @todo Maps API needs changing
		$wpgeo->includeGoogleMapsJavaScriptAPI();
		return '
			<script type="text/javascript">
			//<![CDATA[
			var WPGeo_Admin = {
				api        : "' . $wp_geo_options['admin_api'] . '",
				map        : null,
				marker     : null,
				zoom       : ' . $zoom . ',
				mapCentreX : ' . $mapcentre[0] . ',
				mapCentreY : ' . $mapcentre[1] . ',
				mapType    : ' . $this->api_string( $maptype, 'maptype' ) . ',
				latitude   : ' . $latitude . ',
				longitude  : ' . $longitude . ',
				hideMarker : ' . absint( $hide_marker ) . '
			};
			jQuery(document).ready(function($) {
				$("#wpgeo_location")' . $panel_open . '.bind("WPGeo_adminPostMapReady", function(e){
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', 'WPGeo_Admin.map' ) . '
				});
			});
			//]]>
			</script>';
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
				$new_content .= '<div class="wp_geo_map" id="wp_geo_map_' . $id . '" style="width:' . wpgeo_css_dimension( $wp_geo_options['default_map_width'] ) . '; height:' . wpgeo_css_dimension( $wp_geo_options['default_map_height'] ) . ';"></div>';
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
			return $this->show_maps_filter( true );
		if ( is_single() ) {
			if ( function_exists( 'get_post_type' ) && function_exists( 'get_post_type_object' ) && function_exists( 'post_type_supports' ) ) {
				$post_type = get_post_type( $post->ID );
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type == 'post' ) {
					return $this->show_maps_filter( true );
				} elseif ( $wp_geo_options['show_maps_on_customposttypes'][$post_type] == 'Y' ) {
					return $this->show_maps_filter( true );
				} elseif ( ! $post_type_object->show_ui ) {
					return $this->show_maps_filter( post_type_supports( $post_type, 'wpgeo' ) );
				}
			} elseif ( $wp_geo_options['show_maps_on_posts'] == 'Y' ) {
				return $this->show_maps_filter( true );
			}
		}
		if ( is_page() && $wp_geo_options['show_maps_on_pages'] == 'Y' )                return $this->show_maps_filter( true );
		if ( is_date() && $wp_geo_options['show_maps_in_datearchives'] == 'Y' )         return $this->show_maps_filter( true );
		if ( is_category() && $wp_geo_options['show_maps_in_categoryarchives'] == 'Y' ) return $this->show_maps_filter( true );
		if ( is_tag() && $wp_geo_options['show_maps_in_tagarchives'] == 'Y' )           return $this->show_maps_filter( true );
		if ( is_tax() && $wp_geo_options['show_maps_in_taxarchives'] == 'Y' )           return $this->show_maps_filter( true );
		if ( is_author() && $wp_geo_options['show_maps_in_authorarchives'] == 'Y' )     return $this->show_maps_filter( true );
		if ( is_search() && $wp_geo_options['show_maps_in_searchresults'] == 'Y' )      return $this->show_maps_filter( true );
		if ( is_feed() && $wp_geo_options['add_geo_information_to_rss'] == 'Y' )        return $this->show_maps_filter( true );
		if ( is_post_type_archive() && post_type_supports( get_post_type(), 'wpgeo' ) && $wp_geo_options['show_maps_on_home'] == 'Y' )
			return $this->show_maps_filter( true );
		
		// Activate maps in admin...
		if ( is_admin() ) {
			// If editing a post or page...
			if ( is_numeric( $post_ID ) && $post_ID > 0 )
				return $this->show_maps_filter( true );
			// If writing a new post or page...
			if ( $pagenow == 'post-new.php' || $pagenow == 'page-new.php' )
				return $this->show_maps_filter( true );
		}
		
		// Do Action
		if ( $this->show_maps_external )
			return $this->show_maps_filter( true );
		
		return $this->show_maps_filter( false );
	}
	
	/**
	 * Show Maps Filter
	 * Allows show maps value to be overridden.
	 *
	 * @param bool $show_maps Show Maps?
	 * @return bool
	 */
	function show_maps_filter( $show_maps = false ) {
		return apply_filters( 'wpgeo_show_maps', $show_maps );
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