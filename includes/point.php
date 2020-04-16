<?php

/**
 * Point Class
 */
class WPGeo_Point {

	var $coord = null;
	var $args  = null;
	var $icon  = 'large';
	var $title = '';
	var $link  = '';

	/**
	 * Constructor
	 *
	 * @param  object  $coord  WPGeo_Coord object.
	 * @param  array   $args   Point arguments.
	 */
	function __construct( $coord, $args = null ) {
		$args = wp_parse_args( $args, array(
			'icon'  => 'large',
			'title' => '',
			'link'  => ''
		) );
		$this->coord = $coord;
		$this->args  = $args;
		$this->icon  = $args['icon'];
		$this->title = $args['title'];
		$this->link  = $args['link'];
	}

	/**
	 * Get Coord
	 *
	 * @return  object  WPGeo_Coord.
	 */
	function get_coord() {
		return $this->coord;
	}

	/**
	 * Get Arg
	 *
	 * @return  mixed  Argument value.
	 */
	function get_arg( $key ) {
		if ( is_array( $this->args ) && isset( $this->args[$key] ) ) {
			return $this->args[$key];
		}
		return null;
	}

	/**
	 * Get Icon
	 *
	 * @return  string  Icon string.
	 */
	function get_icon() {
		return $this->icon;
	}

	/**
	 * Get Title
	 *
	 * @return  string  Title string.
	 */
	function get_title() {
		return $this->title;
	}

	/**
	 * Get Link
	 *
	 * @return  string  URL.
	 */
	function get_link() {
		return $this->link;
	}

}
