<?php

/**
 * WP Geo API: Google Maps v2
 */
class WPGeo_API_GoogleMapsV2 {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV2() {
		add_action( 'wpgeo_api_googlemapsv2_js', array( $this, 'wpgeo_js' ) );
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