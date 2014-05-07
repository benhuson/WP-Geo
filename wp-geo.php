<?php

/*
Plugin Name: WP Geo
Plugin URI: http://www.wpgeo.com/
Description: Adds location maps to your posts, pages and custom post types.
Version: 3.3.8
Author: Ben Huson
Author URI: http://www.benhuson.co.uk/
Minimum WordPress Version Required: 3.5
Tested up to: 3.9
*/

// WP Geo plugin directory and url paths. props Alain (alm)
define( 'WPGEO_SUBDIR', '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) );
define( 'WPGEO_URL', plugins_url( WPGEO_SUBDIR ) );
define( 'WPGEO_DIR', plugin_dir_path( __FILE__ ) );

// Constants
if ( ! defined( 'WPGEO_LATITUDE_META' ) ) {
	define( 'WPGEO_LATITUDE_META', '_wp_geo_latitude' );
}
if ( ! defined( 'WPGEO_LONGITUDE_META' ) ) {
	define( 'WPGEO_LONGITUDE_META', '_wp_geo_longitude' );
}
define( 'WPGEO_TITLE_META',        '_wp_geo_title' );
define( 'WPGEO_MARKER_META',       '_wp_geo_marker' );
define( 'WPGEO_MAP_SETTINGS_META', '_wp_geo_map_settings' );

// Language
load_plugin_textdomain( 'wp-geo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// Includes
include_once( WPGEO_DIR . 'includes/wp-geo.php' );
include_once( WPGEO_DIR . 'includes/query.php' );
include_once( WPGEO_DIR . 'includes/api.php' );
include_once( WPGEO_DIR . 'includes/marker.php' );
include_once( WPGEO_DIR . 'includes/markers.php' );
include_once( WPGEO_DIR . 'includes/maps.php' );
include_once( WPGEO_DIR . 'includes/functions.php' );
include_once( WPGEO_DIR . 'includes/templates.php' );
include_once( WPGEO_DIR . 'includes/shortcodes.php' );
include_once( WPGEO_DIR . 'includes/feeds.php' );
include_once( WPGEO_DIR . 'widgets/wpgeo-widget.php' );
include_once( WPGEO_DIR . 'widgets/contextual-map.php' );
include_once( WPGEO_DIR . 'widgets/category-map.php' );
include_once( WPGEO_DIR . 'widgets/recent-locations.php' );

// Init.
global $wpgeo;
$wpgeo = new WPGeo();
