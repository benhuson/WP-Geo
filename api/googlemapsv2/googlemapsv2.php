<?php

/**
 * WP Geo API: Google Maps v2
 */
class WPGeo_API_GoogleMapsV2 {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV2() {
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv2_js', array( $this, 'wpgeo_js' ) );
	}
	
	/**
	 * API String
	 */
	function wpgeo_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'g_physical_map'  : return 'G_PHYSICAL_MAP';
				case 'g_satellite_map' : return 'G_SATELLITE_MAP';
				case 'g_hybrid_map'    : return 'G_HYBRID_MAP';
				case 'g_normal_map'    :
				default                : return 'G_NORMAL_MAP';
			}
		}
		return $string;
	}
	
	function wpgeo_js( $maps ) {
		echo '
			<script type="text/javascript">
			
			function renderWPGeo() {
				if (GBrowserIsCompatible()) {
				';
		foreach ( $maps as $map ) {
			echo '
				map_' . $map->id . ' = new GMap2(document.getElementById("' . $map->get_dom_id() . '"));
				map_' . $map->id . '.setCenter(new GLatLng(41.875696,-87.624207), 3);
				';
			if ( count( $map->feeds ) > 0 ) {
				echo '
					kmlLayer = new GGeoXml("' . $map->feeds[0] . '");
					GEvent.addListener(kmlLayer, "load", function() {
						kmlLayer.gotoDefaultViewport(map_' . $map->id . ');
					});
					map_' . $map->id . '.addOverlay(kmlLayer);
					';
			}
		}
		echo '}
			}
		
			if (document.all&&window.attachEvent) { // IE-Win
				window.attachEvent("onload", function () { renderWPGeo(); });
				window.attachEvent("onunload", GUnload);
			} else if (window.addEventListener) { // Others
				window.addEventListener("load", function () { renderWPGeo(); }, false);
				window.addEventListener("unload", GUnload, false);
			}
			
			</script>
			';
	}
	
}

?>