<?php

/**
 * WP Geo API: Google Maps v3
 */
class WPGeo_API_GoogleMapsV3 extends WPGeo_API {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV3() {
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_filter( 'wpgeo_decode_api_string', array( $this, 'wpgeo_decode_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv3_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_googlemapsv3_markericon', array( $this, 'wpgeo_api_googlemapsv3_markericon' ), 10, 2 );
	}
	
	/**
	 * Marker Icon
	 *
	 * @param string $value Marker icon JavaScript.
	 * @param object $marker WPGeo_Marker.
	 * @return string Marker icon.
	 */
	function wpgeo_api_googlemapsv3_markericon( $value, $marker ) {
		return $marker;
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

	function wpgeo_js( $maps ) {
		if ( ! is_array( $maps ) ) {
			$maps = array( $maps );
		}
		if ( count( $maps ) > 0 ) {
			$filters = array();
			foreach ( $maps as $map ) {
				$filters[] = apply_filters( 'wpgeo_map_js_preoverlays', '', $map->get_js_id() );
			}
			$filters = array_filter($filters);
			if(!empty($filters)) {
				echo '
					<script type="text/javascript">
					//<![CDATA[
					function wpgeo_render_map_filters() {
						';
					echo join("\n", $filters);
				echo '
					}
					google.maps.event.addDomListener(window, "load", wpgeo_render_map_filters);
					//]]>
				</script>';
			}
		}
	}
	
}
