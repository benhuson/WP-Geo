<?php

/**
 * Is Valid Geo Coord
 *
 * @todo This function can be deprecated.
 *
 * @param   float  $lat   Latitude.
 * @param   float  $long  Longitude.
 * @return  bool
 */
function wpgeo_is_valid_geo_coord( $lat, $lng ) {
	$coord = new WPGeo_Coord( $lat, $lng );
	return $coord->is_valid_coord();
}

/**
 * CSS Dimension
 * If numeric assumes pixels and adds 'px', otherwise treated as string.
 *
 * @param   string|int  $str  Dimension.
 * @return  string            Dimension as string.
 */
function wpgeo_css_dimension( $str = false ) {
	if ( is_numeric( $str ) ) {
		$str .= 'px';
	}
	return $str;
}

/**
 * Check Domain
 * This function checks that the domain name of the page matches the blog site url.
 * If it doesn't match we can prevent maps from showing as the Google API Key will not be valid.
 * This prevent warnings if the site is accessed through Google cache.
 *
 * @return  boolean
 */
function wpgeo_check_domain() {
	$http = is_ssl() ? 'https' : 'http';
	$host = $http . '://' . rtrim( $_SERVER["HTTP_HOST"], '/' );

	// Blog might not be in site root so strip to domain
	$blog = preg_replace( "/(http:\/\/[^\/]*).*/", "$1", get_bloginfo( 'url' ) );

	// Strip both boths to non-SSL to compare
	$host = str_replace( 'https:', 'http:', $host );
	$blog = str_replace( 'https:', 'http:', $blog );

	$match = $host == $blog ? true : false;
	return $match;
}

/**
 * Check Version
 * Check if WP Geo version is greater or equal to parameters.
 *
 * @param   string  $version  Version number in the form 2.1.3.a.
 * @return  boolean
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
 * @param   numeric  $version  Database version number.
 * @return  boolean
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
 * @param   array         $args  Array of arguments.
 * @return  array|string         Array or HTML select menu.
 */
function wpgeo_show_polylines_options( $args = null ) {
	$args = wp_parse_args( $args, array(
		'id'       => 'show_polylines',
		'name'     => 'show_polylines',
		'return'   => 'array',
		'selected' => null
	) );

	$menu_options = array(
		''	=> __( 'Default', 'wp-geo' ),
		'Y'	=> __( 'Show Polylines', 'wp-geo' ),
		'N'	=> __( 'Hide Polylines', 'wp-geo' )
	);

	if ( $args['return'] = 'menu' ) {
		return wpgeo_select( $args['name'], $menu_options, $args['selected'] );
	}
	return $menu_options;
}

/**
 * Checkbox HTML
 *
 * @param   string  $name      Field ID.
 * @param   string  $val       Field value.
 * @param   string  $checked   Checked value.
 * @param   bool    $disabled  (optional) Is disabled?
 * @param   int     $id        (optional) Field ID. Defaults to $name.
 * @return  string             Checkbox HTML.
 */
function wpgeo_checkbox( $name, $val, $checked, $disabled = false, $id = '' ) {
	if ( empty( $id ) ) {
		$id = $name;
	}
	return '<input name="' . esc_attr( $name ) . '" type="checkbox" id="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '"' . checked( $val, $checked, false ) . disabled( true, $disabled, false ) . ' />';
}

/**
 * Select HTML
 *
 * @param   string  $name      Field ID.
 * @param   string  $options   Option values.
 * @param   string  $selected  (optional) Select value.
 * @param   bool    $disabled  (optional) Is disabled?
 * @param   int     $id        (optional) Field ID. Defaults to $name.
 * @return  string             Select HTML.
 */
function wpgeo_select( $name, $options, $selected = '', $disabled = false, $id = '' ) {
	if ( empty( $id ) ) {
		$id = $name;
	}
	$options_html = '';
	foreach ( $options as $value => $label ) {
		$options_html .= '<option value="' . esc_attr( $value ) . '"' . selected( $selected, $value, false ) . '>' . $label . '</option>';
	}
	return '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '"' . disabled( true, $disabled, false ) . '>' . $options_html . '</select>';
}
