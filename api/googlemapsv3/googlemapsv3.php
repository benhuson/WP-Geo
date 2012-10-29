<?php

/**
 * WP Geo API: Google Maps v3
 */
class WPGeo_API_GoogleMapsV3 {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV3() {
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_filter( 'wpgeo_decode_api_string', array( $this, 'wpgeo_decode_api_string' ), 10, 3 );
	}
	
	/**
	 * API String
	 */
	function wpgeo_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'g_physical_map'  : return 'google.maps.MapTypeId.TERRAIN';
				case 'g_satellite_map' : return 'google.maps.MapTypeId.SATELLITE';
				case 'g_hybrid_map'    : return 'google.maps.MapTypeId.HYBRID';
				case 'g_normal_map'    :
				default                : return 'google.maps.MapTypeId.ROADMAP';
			}
		}
		return $string;
	}
	
	/**
	 * Decode API String
	 */
	function wpgeo_decode_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'google.maps.maptypeid.terrain' :
				case 'terrain' :
					return 'G_PHYSICAL_MAP';
				case 'google.maps.maptypeid.satellite' :
				case 'satellite' :
					return 'G_SATELLITE_MAP';
				case 'google.maps.maptypeid.hybrid' :
				case 'hybrid' :
					return 'G_HYBRID_MAP';
				case 'google.maps.maptypeid.roadmap' :
				case 'roadmap' :
					return 'G_NORMAL_MAP';
			}
		}
		return $string;
	}
	
}

?>