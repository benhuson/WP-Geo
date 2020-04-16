<?php

/**
 * Coord Class
 */
class WPGeo_Coord {
	
	var $latitude  = null;
	var $longitude = null;
	
	/**
	 * Constructor
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 */
	function __construct( $latitude, $longitude ) {
		$this->latitude  = $latitude;
		$this->longitude = $longitude;
		if ( ! $this->is_valid_coord() && ! empty($this->latitude) && ! empty($this->longitude)) {
			$this->latitude  = $this->sanitize_latlng( $this->latitude );
			$this->longitude = $this->sanitize_latlng( $this->longitude );
		}
	}

	/**
	 * Is Valid Geo Coord
	 *
	 * @param float $lat Latitude.
	 * @param float $long Longitude.
	 * @return bool
	 */
	function is_valid_coord() {
		if ( is_numeric( $this->latitude ) && is_numeric( $this->longitude ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sanitize Lat/Lng
	 * Ensures the latitude or longitude is a floating number and that the decimal
	 * point is a full stop rather than a comma created by floatval() in some locales.
	 *
	 * @param number $n Latitude or Longitude.
	 * @return number
	 */
	function sanitize_latlng( $n ) {
		$n = floatval( $n );
		if ( defined( 'DECIMAL_POINT' ) ) {
			$pt = nl_langinfo( DECIMAL_POINT );
			$n = str_replace( $pt, '.', $n );
		}
		return $n;
	}

	/**
	 * Get Longitude
	 *
	 * @return float
	 */
	function latitude() {
		return $this->latitude;
	}

	/**
	 * Get Longitude
	 *
	 * @return float
	 */
	function longitude() {
		return $this->longitude;
	}
	
	/**
	 * Get Delimited
	 * Returns the latitude and longitude as a string.
	 * By default the values are delimited by a comma.
	 *
	 * @return string Delimited coordinate string.
	 */
	function get_delimited( $delimiter = ',' ) {
		return $this->latitude . $delimiter . $this->longitude; 
	}
	
}
