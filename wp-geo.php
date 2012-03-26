<?php



/*
Plugin Name: WP Geo
Plugin URI: http://www.wpgeo.com/
Description: Adds geocoding to WordPress.
Version: 3.2.6.4
Author: Ben Huson
Author URI: http://www.benhuson.co.uk/
Minimum WordPress Version Required: 2.9
Tested up to: 3.3.1
*/



// Pre-2.6 compatibility
if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

// WP Geo plugin directory and url paths. props Alain (alm)
define( 'WPGEO_SUBDIR', '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) );
define( 'WPGEO_URL', plugins_url( WPGEO_SUBDIR ) );
define( 'WPGEO_DIR', ABSPATH . 'wp-content/plugins' . WPGEO_SUBDIR );

// Constants
define( 'WPGEO_LATITUDE_META',     '_wp_geo_latitude' );
define( 'WPGEO_LONGITUDE_META',    '_wp_geo_longitude' );
define( 'WPGEO_TITLE_META',        '_wp_geo_title' );
define( 'WPGEO_MARKER_META',       '_wp_geo_marker' );
define( 'WPGEO_MAP_SETTINGS_META', '_wp_geo_map_settings' );



// Language
load_plugin_textdomain( 'wp-geo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );



// Includes
include_once( WPGEO_DIR . 'includes/wp-geo.php' );
include_once( WPGEO_DIR . 'includes/query.php' );
include_once( WPGEO_DIR . 'includes/marker.php' );
include_once( WPGEO_DIR . 'includes/markers.php' );
include_once( WPGEO_DIR . 'includes/maps.php' );
include_once( WPGEO_DIR . 'includes/functions.php' );
include_once( WPGEO_DIR . 'includes/templates.php' );
include_once( WPGEO_DIR . 'includes/shortcodes.php' );
include_once( WPGEO_DIR . 'includes/feeds.php' );
include_once( WPGEO_DIR . 'includes/display.php' );
include_once( WPGEO_DIR . 'widgets/contextual-map.php' );
include_once( WPGEO_DIR . 'widgets/recent-locations.php' );



// Init.
global $wpgeo;
$wpgeo = new WPGeo();



// Activation Hook
register_activation_hook( __FILE__, array( $wpgeo, 'register_activation' ) );



// Action Hooks
add_action( 'init', array( $wpgeo, 'init' ) );
add_action( 'init', array( $wpgeo, 'init_later' ), 10000 );
add_action( 'wp_enqueue_scripts', array( $wpgeo, 'includeGoogleMapsJavaScriptAPI' ) );
add_action( 'admin_enqueue_scripts', array( $wpgeo, 'includeGoogleMapsJavaScriptAPI' ) );
add_action( 'wp_head', array( $wpgeo, 'wp_head' ) );
add_action( 'wp_footer', array( $wpgeo, 'wp_footer' ) );
add_action( 'admin_init', array( $wpgeo, 'admin_init' ) );
add_action( 'admin_head', array( $wpgeo, 'admin_head' ) );
add_action( 'admin_menu', array( $wpgeo, 'admin_menu' ) );
add_action( 'after_plugin_row', array( $wpgeo, 'after_plugin_row' ) );
add_action( 'admin_notices', array( $wpgeo, 'version_upgrade_msg' ) );



// Filters
add_filter( 'the_content', array( $wpgeo, 'the_content' ) );
add_filter( 'get_the_excerpt', array( $wpgeo, 'get_the_excerpt' ) );
add_filter( 'post_limits', array( $wpgeo, 'post_limits' ) );
add_filter( 'posts_join', array( $wpgeo, 'posts_join' ) );
add_filter( 'posts_where', array( $wpgeo, 'posts_where' ) );



?>