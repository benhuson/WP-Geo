<?php

/**
 * WP Geo class
 * The main WP Geo class - this is where it all happens.
 */
class WPGeo {
	
	// Version Information
	var $version    = '3.3.8';
	var $db_version = 1;
	
	var $api;
	var $admin;
	var $wpgeo_query;
	var $markers;
	var $show_maps_external = false;
	var $maps;
	var $feeds;
	
	var $default_map_latitude  = '51.492526418807465';
	var $default_map_longitude = '-0.15754222869873047';
	
	/**
	 * Constructor
	 */
	function WPGeo() {
		
		// API
		$wp_geo_options = get_option( 'wp_geo_options' );
		if ( 'googlemapsv3' == $this->get_api_string() ) {
			include_once( WPGEO_DIR . 'api/googlemapsv3/googlemapsv3.php' );
			$this->api = new WPGeo_API_GoogleMapsV3();
		} elseif ( 'googlemapsv2' == $this->get_api_string() ) {
			include_once( WPGEO_DIR . 'api/googlemapsv2/googlemapsv2.php' );
			$this->api = new WPGeo_API_GoogleMapsV2();
		} else {
			$this->api = new WPGeo_API();
		}

		$this->wpgeo_query = new WPGeo_Query();
		$this->maps        = new WPGeo_Maps();
		$this->markers     = new WPGeo_Markers();
		$this->feeds       = new WPGeo_Feeds();
		
		// Action Hooks
		add_action( 'plugins_loaded', array( $this, '_maybe_upgrade' ), 5 );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'init_later' ), 10000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( $this, 'meta_tags' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
		add_action( 'wp_footer', array( $this, 'wp_footer' ) );
		add_action( 'admin_footer', array( $this, 'wp_footer' ) );
		
		// Filters
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'get_the_excerpt', array( $this, 'get_the_excerpt' ) );
		add_filter( 'option_wp_geo_options', array( $this, 'option_wp_geo_options' ) );
		add_filter( 'clean_url', array( $this, 'clean_googleapis_url' ), 99, 3 );

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
			'public_api'                    => 'googlemapsv3',
			'admin_api'                     => 'googlemapsv3',
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
			'show_streetview_control'       => 'N',
			'save_post_zoom'                => 'N',
			'save_post_map_type'            => 'N',
			'save_post_centre_point'        => 'N',
			'show_polylines'                => 'Y',
			'polyline_colour'               => '#FFFFFF',
			'supported_post_types'          => array( 'post', 'page' ),
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
	 * Maybe Upgrade
	 * Checks on each admin page load wether the plugin upgrade routine should
	 * be run to create options etc.
	 */
	function _maybe_upgrade() {
		$wp_geo_version = get_option( 'wp_geo_version', 0 );
		if ( empty( $wp_geo_version ) || version_compare( $wp_geo_version, $this->version, '<' ) ) {
			update_option( 'wp_geo_show_version_msg', 'Y' );
			update_option( 'wp_geo_version', $this->version );
			
			// Update Options
			$default_options = $this->default_option_values();
			$options = get_option( 'wp_geo_options', $default_options );
			$options = wp_parse_args( $options, $default_options );
			update_option( 'wp_geo_options', $options );
			
			// Files
			$this->markers->register_activation();
		}
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
	 * Check Google API Key
	 *
	 * Check that a Google API Key has been entered.
	 * Can be overridden by the filter if it is not required.
	 *
	 * @return  boolean
	 */
	function checkGoogleAPIKey() {
		$api_key = $this->get_google_api_key();
		return apply_filters( 'wpgeo_check_google_api_key', ! empty( $api_key ) );
	}

	/**
	 * Get Google API Key
	 *
	 * Gets the Google API Key. Passes it through a filter so it can be overriden by API or another plugin.
	 *
	 * @return  string  API Key.
	 */
	function get_google_api_key() {
		$wp_geo_options = get_option( 'wp_geo_options' );
		if ( ! isset( $wp_geo_options['google_api_key'] ) ) {
			$wp_geo_options['google_api_key'] = '';
		}
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
			$coord = get_wpgeo_post_coord( $post->ID );
			if ( $coord->is_valid_coord() ) {
				$showmap = true;
			}
		}
		
		if ( $showmap && ! is_feed() && $this->checkGoogleAPIKey() ) {
		
			$map = new WPGeo_Map( 'visible' );
			echo $map->get_map_html( array(
				'classes' => array( 'wp_geo_map' ),
				'styles'  => array(
					'width'  => $args['width'],
					'height' => $args['height']
				)
			) );
			
		}
	}

	/**
	 * Meta Tags
	 * Outputs geo-related meta tags.
	 */
	function meta_tags() {
		global $post;

		if ( is_single() ) {
			$coord = get_wpgeo_post_coord( $post->ID );
			if ( $coord->is_valid_coord() ) {

				// Would make sense to look these up automatically from Google
				//echo '<meta name="geo.region" content="DE-BY" />';                                // Geo-Tag: Country code (ISO 3166-1) and regional code (ISO 3166-2)
				//echo '<meta name="geo.placename" content="MÙnchen" />';                           // Geo-Tag: City or the nearest town
				echo '<meta name="geo.position" content="' . $coord->get_delimited( ';' ) . '" />'; // Geo-Tag: Latitude and longitude
				echo '<meta name="ICBM" content="' . $coord->get_delimited() . '" />';              // ICBM Tag (prior existing equivalent to the geo.position)

				// Dublin Core Meta Title Tag
				// Some geo databases extract the web-page's title out of the DC.title tag
				$title = get_post_meta( $post->ID, WPGEO_TITLE_META, true );
				if ( ! empty( $title ) ) {
					echo '<meta name="DC.title" content="' . esc_attr( $title ) . '" />';
				}
			}
		}
	}

	/**
	 * WP Head
	 * Outputs HTML and JavaScript to the header.
	 */
	function wp_head() {
		global $wpgeo;

		$wp_geo_options = get_option( 'wp_geo_options' );

		echo '
			<script type="text/javascript">
			//<![CDATA[

			// WP Geo default settings
			var wpgeo_w = \'' . $wp_geo_options['default_map_width'] . '\';
			var wpgeo_h = \'' . $wp_geo_options['default_map_height'] . '\';
			var wpgeo_type = \'' . $wp_geo_options['google_map_type'] . '\';
			var wpgeo_zoom = ' . $wp_geo_options['default_map_zoom'] . ';
			var wpgeo_controls = \'' . $wp_geo_options['default_map_control'] . '\';
			var wpgeo_controltypes = \'' . implode( ',', $this->control_type_option_strings( $wp_geo_options ) ) . '\';
			var wpgeo_scale = \'' . $wp_geo_options['show_map_scale'] . '\';
			var wpgeo_overview = \'' . $wp_geo_options['show_map_overview'] . '\';

			//]]>
			</script>
			';

		if ( $wpgeo->show_maps() || $wpgeo->widget_is_active() ) {
			$this->markers->wp_head();
		}
	}

	/**
	 * Control Type Option Strings
	 *
	 * @param   array $options WP Geo options.
	 * @return  array Control type strings.
	 */
	function control_type_option_strings( $options ) {
		global $wpgeo;

		$map_type_options = $wpgeo->api->map_type_options();
		$controltypes = array();
		foreach ( $map_type_options as $key => $val ) {
			if ( $options[$val] == 'Y' ) {
				$controltypes[] = $key;
			}
		}
		return $controltypes;
	}
	
	/**
	 * Post Types Supports
	 *
	 * Checks if a post type supports WP Geo.
	 * Checks the admin settings add post types added with add_post_type_support().
	 *
	 * @param   string  $post_type  Post type.
	 * @return  boolean
	 */
	function post_type_supports( $post_type ) {
		$options = get_option( 'wp_geo_options' );
		if ( post_type_supports( $post_type, 'wpgeo' ) || in_array( $post_type, $options['supported_post_types'] ) ) {
			return true;
		}
		return false;
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
	 * Clean Google APIs URL
	 *
	 * Replace '&#038;' with '&'
	 * It's not standard but Google doesn;t seem to like '&#038;'
	 */
	function clean_googleapis_url( $url, $original_url, $_context ) {
		if ( strstr( $url, 'googleapis.com' ) !== false ) {
			$url = str_replace( '&#038;', '&', $url );
		}
		return $url;
	}

	/**
	 * Include Google Maps JavaScript API
	 *
	 * Legacy function. Please use WPGeo::enqueue_scripts();
	 *
	 * @todo  Deprecate function.
	 */
	function includeGoogleMapsJavaScriptAPI() {
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue Scripts
	 *
	 * Register required WP Geo styles and scripts.
	 *
	 * @uses  WPGeo:$version
	 * @uses  WPGeo:show_maps()
	 * @uses  WPGeo:widget_is_active()
	 * @uses  WPGeo:checkGoogleAPIKey()
	 * @uses  do_action()  Calls 'wpgeo_register_scripts'.
	 * @uses  do_action()  Calls 'wpgeo_enqueue_scripts'.
	 */
	function enqueue_scripts() {
		global $wpgeo;

		// Styles
		wp_register_style( 'wpgeo', WPGEO_URL . 'css/wp-geo.css', null, $this->version );

		// Scripts
		wp_register_script( 'wpgeo_tooltip', WPGEO_URL . 'js/tooltip.js', array( 'jquery' ), $this->version );
		wp_register_script( 'wpgeo_admin_post', WPGEO_URL . 'js/admin-post.js', array( 'jquery', 'wpgeo' ), $this->version );
		do_action( 'wpgeo_register_scripts' );

		// Enqueue styles and scripts
		wp_enqueue_style( 'wpgeo' );
		if ( ( $this->show_maps() || $this->widget_is_active() ) && $this->checkGoogleAPIKey() ) {
			do_action( 'wpgeo_enqueue_scripts' );
		}
	}

	/**
	 * Get Google Maps Locale
	 * See http://code.google.com/apis/maps/faq.html#languagesupport for link to updated languages codes.
	 * https://spreadsheets.google.com/pub?key=p9pdwsai2hDMsLkXsoM05KQ&gid=1
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
	 * @param object $coord WPGeo_Coord.
	 * @param int $zoom Zoom.
	 * @param bool $panel_open Admin panel open?
	 * @param bool $hide_marker Hide marker?
	 * @return string HTML content.
	 */
	function mapScriptsInit( $coord, $zoom = 5, $panel_open = false, $hide_marker = false ) {
		global $wpgeo, $post;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		$maptype = empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];
		
		// Centre on London
		if ( ! $coord->is_valid_coord() ) {
			$coord       = new WPGeo_Coord( $wp_geo_options['default_map_latitude'], $wp_geo_options['default_map_longitude'] );
			$zoom        = $wp_geo_options['default_map_zoom'];
			$panel_open  = true;
			$hide_marker = true;
		}
		$map_center_coord = new WPGeo_Coord( $coord->latitude(), $coord->longitude() );
		
		if ( isset( $post ) && is_numeric( $post->ID ) && $post->ID > 0 ) {
			$settings = WPGeo::get_post_map_settings( $post->ID );
			if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
				$zoom = $settings['zoom'];
			}
			if ( ! empty( $settings['type'] ) ) {
				$maptype = $settings['type'];
			}
			if ( ! empty( $settings['centre'] ) ) {
				$map_center = explode( ',', $settings['centre'] );
				if ( count( $map_center ) == 2 ) {
					$new_mapcentre_coord = new WPGeo_Coord( $map_center[0], $map_center[1] );
					if ( $new_mapcentre_coord->is_valid_coord() ) {
						$map_center_coord = $new_mapcentre_coord;
					}
				}
			}
		}
		
		// Vars
		$panel_open = ! $hide_marker || $panel_open ? '.removeClass("closed")' : '';
		
		$wpgeo_admin_vars = array(
			'api'        => $this->get_api_string(),
			'map_dom_id' => $this->admin->map->get_dom_id(),
			'map'        => null,
			'marker'     => null,
			'zoom'       => intval( $zoom ),
			'mapCentreX' => $map_center_coord->latitude(),
			'mapCentreY' => $map_center_coord->longitude(),
			'latitude'   => $coord->latitude(),
			'longitude'  => $coord->longitude(),
			'hideMarker' => absint( $hide_marker )
		);
		
		// Script
		// @todo Maps API needs changing
		return '
			<script type="text/javascript">
			var WPGeo_Admin = ' . json_encode( $wpgeo_admin_vars ) . ';
			WPGeo_Admin.mapType = ' . $this->api_string( $maptype, 'maptype' ) . ';
			
			jQuery(document).ready(function($) {
				$("#wpgeo_location")' . $panel_open . '.bind("WPGeo_adminPostMapReady", function(e){
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', 'WPGeo_Admin.map' ) . '
				});
			});
			</script>
			';
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
	 * Get Post Map Settings
	 *
	 * @since  3.3.2
	 *
	 * @param   int   $post_id  Post ID.
	 * @return  array           Post map settings array.
	 */
	static function get_post_map_settings( $post_id ) {
		$settings = wp_parse_args( get_post_meta( $post_id, WPGEO_MAP_SETTINGS_META, true ), array(
			'zoom'   => '',
			'type'   => '',
			'centre' => ''
		) );
		return $settings;
	}

	/**
	 * Get API String
	 */
	function get_api_string( $str = '%s' ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		if ( is_admin() ) {
			if ( empty( $wp_geo_options['admin_api'] ) ) {
				$wp_geo_options['admin_api'] = 'googlemapsv3';
			}
			$str = sprintf( $str, $wp_geo_options['admin_api'] );
		} else {
			if ( empty( $wp_geo_options['public_api'] ) ) {
				$wp_geo_options['public_api'] = 'googlemapsv3';
			}
			$str = sprintf( $str, $wp_geo_options['public_api'] );
		}
		return $str;
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
			$maptype = empty( $wp_geo_options['google_map_type'] ) ? 'G_NORMAL_MAP' : $wp_geo_options['google_map_type'];
			$mapzoom = $wp_geo_options['default_map_zoom'];

			$coord    = get_wpgeo_post_coord( $post->ID );
			$title    = get_wpgeo_title( $post->ID );
			$marker   = get_post_meta( $post->ID, WPGEO_MARKER_META, true );
			$settings = WPGeo::get_post_map_settings( $post->ID );

			$mymaptype = $maptype;
			if ( isset( $settings['type'] ) && ! empty( $settings['type'] ) ) {
				$mymaptype = $settings['type'];
			}
			$mymapzoom = $mapzoom;
			if ( isset( $settings['zoom'] ) && is_numeric( $settings['zoom'] ) ) {
				$mymapzoom = $settings['zoom'];
			}

			// Need a map?
			if ( $coord->is_valid_coord() ) {
				$map = new WPGeo_Map( $post->ID );

				// Add point
				$marker_large = empty( $marker ) ? 'large' : $marker;
				$icon = apply_filters( 'wpgeo_marker_icon', $marker_large, $post, 'post' );
				$map->add_point( $coord, array(
					'icon'  => $icon,
					'title' => $title,
					'link'  => get_permalink( $post->ID )
				) );
				$map->setMapZoom( $mymapzoom );
				$map->setMapType( $mymaptype );
	
				if ( ! empty( $settings['centre'] ) ) {
					$centre = explode( ',', $settings['centre'] );
					if ( is_array( $centre ) && count( $centre ) == 2 ) {
						$map->setMapCentre( $centre[0], $centre[1] );
					} else {
						$map->setMapCentre( $coord->latitude(), $coord->longitude() );
					}
				} else {
					$map->setMapCentre( $coord->latitude(), $coord->longitude() );
				}

				$map_types = $this->control_type_option_strings( $wp_geo_options );
				foreach ( $map_types as $map_type ) {
					$map->addMapType( $map_type );
				}

				if ( $wp_geo_options['show_map_scale'] == 'Y' ) {
					$map->showMapScale( true );
				}
				if ( $wp_geo_options['show_map_overview'] == 'Y' ) {
					$map->showMapOverview( true );
				}
				if ( $wp_geo_options['show_streetview_control'] == 'Y' ) {
					$map->show_streetview_control( true );
				}
				
				$map->setMapControl( $wp_geo_options['default_map_control'] );

				$wpgeo->maps->add_map( $map );

				$new_content .= $map->get_map_html( array(
					'classes' => array( 'wp_geo_map' ),
					'styles'  => array(
						'width'  => $wp_geo_options['default_map_width'],
						'height' => $wp_geo_options['default_map_height']
					)
				) );
				$new_content = apply_filters( 'wpgeo_the_content_map', $new_content );
			}
			
			// Add map to content
			$show_post_map = apply_filters( 'wpgeo_show_post_map', $wp_geo_options['show_post_map'], $post->ID );
			
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
		global $wpgeo, $post, $post_ID, $pagenow;
		
		$wp_geo_options = get_option( 'wp_geo_options' );
		
		// Check if domain is correct
		if ( ! wpgeo_check_domain() ) {
			return false;
		}
		
		// Widget active
		// if ( is_active_widget( array( 'WPGeo_Widget', 'map_widget' ) ) ) {
		//	return true;
		// }
		
		// Check settings
		if ( is_home() && $wp_geo_options['show_maps_on_home'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_single() ) {
			$post_type = get_post_type( $post->ID );
			if ( $post_type == 'post' ) {
				return $this->show_maps_filter( true );
			} elseif ( isset( $wp_geo_options['show_maps_on_customposttypes'][$post_type] ) && $wp_geo_options['show_maps_on_customposttypes'][$post_type] == 'Y' ) {
				return $this->show_maps_filter( true );
			} elseif ( $wpgeo->post_type_supports( $post_type ) ) {
				return true;
			}
		}
		if ( is_page() && $wp_geo_options['show_maps_on_pages'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_date() && $wp_geo_options['show_maps_in_datearchives'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_category() && $wp_geo_options['show_maps_in_categoryarchives'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_tag() && $wp_geo_options['show_maps_in_tagarchives'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_tax() && $wp_geo_options['show_maps_in_taxarchives'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_author() && $wp_geo_options['show_maps_in_authorarchives'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_search() && $wp_geo_options['show_maps_in_searchresults'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_feed() && $wp_geo_options['add_geo_information_to_rss'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		if ( is_post_type_archive() && $wpgeo->post_type_supports( get_post_type() ) && $wp_geo_options['show_maps_on_home'] == 'Y' ) {
			return $this->show_maps_filter( true );
		}
		
		// Activate maps in admin...
		if ( is_admin() ) {
			// If editing a post or page...
			if ( is_numeric( $post_ID ) && $post_ID > 0 ) {
				return $this->show_maps_filter( true );
			}
			// If writing a new post or page...
			if ( $pagenow == 'post-new.php' || $pagenow == 'page-new.php' ) {
				return $this->show_maps_filter( true );
			}
		}
		
		// Do Action
		if ( $this->show_maps_external ) {
			return $this->show_maps_filter( true );
		}
		
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
	 * @todo Deprecate this function.
	 *
	 * @param string $id Field ID.
	 * @param string $val Field value.
	 * @param string $checked Checked value.
	 * @param bool $disabled (optional) Is disabled?
	 * @return string Checkbox HTML.
	 */
	function options_checkbox( $name, $val, $checked, $disabled = false, $id = '' ) {
		return wpgeo_checkbox( $name, $val, $checked, $disabled, $id );
	}

	/**
	 * Select Map Control
	 * Map control array or menu.
	 *
	 * @param   string  $return    (optional) Array or menu type.
	 * @param   string  $selected  (optional) Selected value.
	 * @return  array|string       Array or menu HTML.
	 */
	function selectMapControl( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'name' => 'default_map_control',
			'id'   => 'default_map_control'
		) );
		$menu_options = $this->api->map_controls();

		if ( $return = 'menu' ) {
			return wpgeo_select( $args['name'], $menu_options, $selected, false, $args['id'] );
		}
		return $menu_options;
	}

	/**
	 * Select Map Zoom
	 * Map zoom array or menu.
	 *
	 * @param   string  $return    (optional) Array or menu type.
	 * @param   string  $selected  (optional) Selected value.
	 * @param   array   $args      (optional) Args.
	 * @return  array|string       Array or menu HTML.
	 */
	function selectMapZoom( $return = 'array', $selected = '', $args = null ) {
		$args = wp_parse_args( (array)$args, array(
			'return'   => null,
			'selected' => null,
			'name'     => 'default_map_zoom',
			'id'       => 'default_map_zoom'
		) );

		// Deprecated compatibility
		if ( $args['return'] == null ) {
			$args['return'] = $return;
		}
		if ( $args['selected'] == null ) {
			$args['selected'] = $selected;
		}

		$menu_options = $this->api->zoom_values();

		if ( $return = 'menu' ) {
			return wpgeo_select( $args['name'], $menu_options, $args['selected'] );
		}
		return $menu_options;
	}

	/**
	 * Google Map Types
	 * Map type array or menu.
	 *
	 * @param   string  $return    (optional) Array or menu type.
	 * @param   string  $selected  (optional) Selected value.
	 * @param   array   $args      (optional) Args.
	 * @return  array|string       Array or menu HTML.
	 */
	function google_map_types( $return = 'array', $selected = '', $args = null ) {
		global $wpgeo;

		$args = wp_parse_args( (array)$args, array(
			'return'   => null,
			'selected' => null,
			'name'     => 'google_map_type',
			'id'       => 'google_map_type'
		) );

		// Deprecated compatibility
		if ( $args['return'] == null ) {
			$args['return'] = $return;
		}
		if ( $args['selected'] == null ) {
			$args['selected'] = $selected;
		}

		$menu_options = $wpgeo->api->map_types();

		if ( $args['return'] = 'menu' ) {
			return wpgeo_select( $args['name'], $menu_options, $args['selected'], false, $args['id'] );
		}
		return $menu_options;
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
		add_filter( 'posts_join', array( $this->wpgeo_query, 'get_custom_field_posts_join' ) );
		add_filter( 'posts_groupby', array( $this->wpgeo_query, 'get_custom_field_posts_group' ) );
		$custom_posts->query( 'showposts=' . $numberposts );
		remove_filter( 'posts_join', array( $this->wpgeo_query, 'get_custom_field_posts_join' ) );
		remove_filter( 'posts_groupby', array( $this->wpgeo_query, 'get_custom_field_posts_group' ) );
		
		$points = array();
		while ( $custom_posts->have_posts() ) {
			$custom_posts->the_post();
			$id = get_the_ID();
			$coord = get_wpgeo_post_coord( $id );
			if ( $coord->is_valid_coord() ) {
				$points[] = array(
					'id'   => $id,
					'lat'  => $coord->latitude(),
					'long' => $coord->longitude()
				);
			}
		}
		return $points;
	}

	/**
	 * WP Footer
	 */
	function wp_footer() {
		do_action( $this->get_api_string( 'wpgeo_api_%s_js' ), $this->maps->maps );
	}
	
}
