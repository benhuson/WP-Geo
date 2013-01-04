<?php

/**
 * Is Valid Geo Coord
 *
 * @todo This function can be deprecated.
 *
 * @param float $lat Latitude.
 * @param float $long Longitude.
 * @return bool
 */
function wpgeo_is_valid_geo_coord( $lat, $lng ) {
	$coord = new WPGeo_Coord( $lat, $lng );
	return $coord->is_valid_coord();
}

/**
 * CSS Dimension
 * If numeric assumes pixels and adds 'px', otherwise treated as string.
 *
 * @param string|int $str Dimension.
 * @return string  Dimension as string.
 */
function wpgeo_css_dimension( $str = false ) {
	if ( is_numeric( $str ) ) {
		$str .= 'px';
	}
	return $str;
}

/**
 * Check Domain
 * This function checks that the domainname of the page matches the blog site url.
 * If it doesn't match we can prevent maps from showing as the Google API Key will not be valid.
 * This prevent warnings if the site is accessed through Google cache.
 *
 * @return boolean
 */
function wpgeo_check_domain() {
	$host = 'http://' . rtrim( $_SERVER["HTTP_HOST"], '/' );
	
	// Blog might not be in site root so strip to domain
	$blog = preg_replace( "/(http:\/\/[^\/]*).*/", "$1", get_bloginfo( 'url' ) );
	
	$match = $host == $blog ? true : false;
	return $match;
}

/**
 * Check Version
 * Check if WP Geo version is greater or equal to parameters.
 *
 * @param string $version Version number in the form 2.1.3.a.
 * @return boolean
 */
function wpgeo_check_version( $version ) {
	global $wpgeo;
	
	if ( version_compare( $version, $wpgeo->version, '>=' ) ) {
		return true;
	}
	return false;
}

/**
 * Check DB Version
 * Check if WP Geo database version is greater or equal to parameter.
 *
 * @param numeric $version Database version number.
 * @return boolean
 */
function wpgeo_check_db_version( $version ) {
	global $wpgeo;
	
	if ( $version >= $wpgeo->db_version ) {
		return true;
	}
	return false;
}

/**
 * Show Polylines Options
 * Polylines options menu for the map.
 *
 * @param array $args Array of arguments.
 * @return array|string Array or HTML select menu.
 */
function wpgeo_show_polylines_options( $args = null ) {
	$args = wp_parse_args( $args, array(
		'id'       => 'show_polylines',
		'name'     => 'show_polylines',
		'return'   => 'array',
		'selected' => null
	) );
	
	// Menu Options
	$map_type_array = array(
		''	=> __( 'Default', 'wp-geo' ),
		'Y'	=> __( 'Show Polylines', 'wp-geo' ),
		'N'	=> __( 'Hide Polylines', 'wp-geo' )
	);
	
	// Menu?
	if ( $args['return'] = 'menu' ) {
		$menu = '';
		foreach ( $map_type_array as $key => $val ) {
			$menu .= '<option value="' . $key . '" ' . selected( $args['selected'], $key, false ) . '>' . $val . '</option>';
		}
		$menu = '<select name="' . $args['name'] . '" id="' . $args['id'] . '">' . $menu. '</select>';
		return $menu;
	}
	
	return $map_type_array;
}

?>