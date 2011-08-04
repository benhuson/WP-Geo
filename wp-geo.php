<?php



/*
Plugin Name: WP Geo
Plugin URI: http://www.wpgeo.com/
Description: Adds geocoding to WordPress.
Version: 3.2.5
Author: Ben Huson
Author URI: http://www.benhuson.co.uk/
Minimum WordPress Version Required: 2.9
Tested up to: 3.1.3
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



// Constants
define( 'WPGEO_LATITUDE_META',     '_wp_geo_latitude' );
define( 'WPGEO_LONGITUDE_META',    '_wp_geo_longitude' );
define( 'WPGEO_TITLE_META',        '_wp_geo_title' );
define( 'WPGEO_MARKER_META',       '_wp_geo_marker' );
define( 'WPGEO_MAP_SETTINGS_META', '_wp_geo_map_settings' );



// Language
load_plugin_textdomain( 'wp-geo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );



// Includes
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/wp-geo.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/query.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/marker.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/markers.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/maps.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/functions.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/templates.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/shortcodes.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/feeds.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/includes/display.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/widgets/contextual-map.php' );
include_once( WP_PLUGIN_DIR . '/wp-geo/widgets/recent-locations.php' );



// Admin Includes
if ( is_admin() ) {
	include_once( WP_PLUGIN_DIR . '/wp-geo/admin/editor.php' );
	include_once( WP_PLUGIN_DIR . '/wp-geo/admin/dashboard.php' );
	include_once( WP_PLUGIN_DIR . '/wp-geo/admin/settings.php' );
}



// Init.
global $wpgeo;
$wpgeo = new WPGeo();



// Activation Hook
register_activation_hook( __FILE__, array( $wpgeo, 'register_activation' ) );



// Action Hooks
add_action( 'init', array( $wpgeo, 'init' ) );
add_action( 'init', array( $wpgeo, 'init_later' ), 10000 );
add_action( 'wp_print_scripts', array( $wpgeo, 'includeGoogleMapsJavaScriptAPI' ) );
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