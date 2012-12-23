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
				map = new GMap2(document.getElementById("' . $map->get_dom_id() . '"));
				map.setCenter(new GLatLng(41.875696,-87.624207), 3);
				geoXml = new GGeoXml("' . $map->feed[0] . '");
				GEvent.addListener(geoXml, "load", function() {
					geoXml.gotoDefaultViewport(map);
				});
				' . WPGeo_API_GMap2::render_map_overlay( 'map', 'geoXml' ) . '
				';
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