
(function($){
	
	/**
	 * Google API v2
	 */
	function wpgeo_init_admin_post_map() {
		
		$(document).ready(function($) {
			
			// If maps compatible...
			if (GBrowserIsCompatible() && document.getElementById(WPGeo_Admin.map_dom_id)) {
			
				// Define map
				var center = new GLatLng(WPGeo_Admin.mapCentreX, WPGeo_Admin.mapCentreY);
				var point = new GLatLng(WPGeo_Admin.latitude, WPGeo_Admin.longitude);
				
				// Marker
				WPGeo_Admin.marker = new GMarker(point, {draggable: true});
				
				// Map
				WPGeo_Admin.map = new GMap2(document.getElementById(WPGeo_Admin.map_dom_id));
				WPGeo_Admin.map.setCenter(center, WPGeo_Admin.zoom);
				WPGeo_Admin.map.addMapType(G_PHYSICAL_MAP);
				WPGeo_Admin.map.addControl(new GLargeMapControl3D());
				WPGeo_Admin.map.addControl(new GMapTypeControl());
				WPGeo_Admin.map.setMapType(WPGeo_Admin.mapType);
				WPGeo_Admin.map.addOverlay(WPGeo_Admin.marker);
			
				// Update location field
				$("#wpgeo_location").bind("WPGeo_updateLatLngField", function(e) {
					if (e.lat == '' || e.lng == '') {
						WPGeo_Admin.marker.hide();
					} else {
						var point = new GLatLng(e.lat, e.lng);
						WPGeo_Admin.map.setCenter(point);
						WPGeo_Admin.marker.setPoint(point);
						WPGeo_Admin.marker.show();
					}
				});
				
				// Click on map
				GEvent.addListener(WPGeo_Admin.map, "click", function(overlay, latlng) {
					$("#wpgeo_location").trigger({
						type   : 'WPGeo_updateMarkerLatLng',
						latLng : latlng,
						lat    : latlng.lat(),
						lng    : latlng.lng()
					});
					WPGeo_Admin.marker.setPoint(latlng);
					WPGeo_Admin.marker.show();
				});
				
				// Update map type
				GEvent.addListener(WPGeo_Admin.map, "maptypechanged", function() {
					$("#wpgeo_location").trigger({
						type    : "WPGeo_updateMapType",
						mapType : wpgeo_getMapTypeContentFromUrlArg(WPGeo_Admin.map.getCurrentMapType().getUrlArg())
					});
				});
				
				// Update zoom
				GEvent.addListener(WPGeo_Admin.map, "zoomend", function(oldLevel, newLevel) {
					$("#wpgeo_location").trigger({
						type : "WPGeo_updateMapZoom",
						zoom : newLevel
					});
				});
				
				// Update center
				GEvent.addListener(WPGeo_Admin.map, "moveend", function() {
					$("#wpgeo_location").trigger({
						type   : 'WPGeo_updateMapCenter',
						latLng : WPGeo_Admin.map.getCenter(),
						lat    : WPGeo_Admin.map.getCenter().lat(),
						lng    : WPGeo_Admin.map.getCenter().lng(),
					});
					var centre_setting = document.getElementById("wpgeo_map_settings_centre");
					centre_setting.value = WPGeo_Admin.map.getCenter().lat() + "," + WPGeo_Admin.map.getCenter().lng();
				});
				
				// Marker Drag start
				GEvent.addListener(WPGeo_Admin.marker, "dragstart", function() {
					WPGeo_Admin.map.closeInfoWindow();
				});
				
				// Update marker location after drag
				GEvent.addListener(WPGeo_Admin.marker, "dragend", function() {
					$("#wpgeo_location").trigger({
						type   : 'WPGeo_updateMarkerLatLng',
						latLng : WPGeo_Admin.marker.getLatLng(),
						lat    : WPGeo_Admin.marker.getLatLng().lat(),
						lng    : WPGeo_Admin.marker.getLatLng().lng(),
					});
				});
			
				// Hide marker?
				$("#wpgeo_location").bind("WPGeo_hideMarker", function(e) {
					WPGeo_Admin.marker.hide();
				});
				
				// Move to center marker
				$("#wpgeo_location").bind("WPGeo_centerLocation", function(e) {
					WPGeo_Admin.map.setCenter(WPGeo_Admin.marker.getLatLng());
				});
				
				// Search Location
				$("#wpgeo_location").bind("WPGeo_searchLocation", function(e) {
					var geocoder = new GClientGeocoder();
					
					// Set default base country for search
					if (e.base_country_code != undefined && e.base_country_code != '') {
						geocoder.setBaseCountryCode(e.base_country_code);
					}
		
					if (geocoder) {
						geocoder.getLatLng(
							e.address,
							function(point) {
								if (!point) {
									alert(e.address + " not found");
								} else {
									WPGeo_Admin.map.setCenter(point);
									WPGeo_Admin.marker.setPoint(point);
									WPGeo_Admin.marker.show();
									$("#wpgeo_location").trigger({
										type   : 'WPGeo_updateMarkerLatLng',
										latLng : point,
										lat    : point.lat(),
										lng    : point.lng(),
									});
								}
							}
						);
					}
				});
				
				// Hide Marker
				if (WPGeo_Admin.hideMarker) {
					WPGeo_Admin.marker.hide();
				}
				
			}
		});
	}
	$(window).load(wpgeo_init_admin_post_map);
	$(window).unload(GUnload);

	/**
	 * Get the Google Map type from a URL parameter.
	 *
	 * @param   (string) Map type key from URL.
	 * @return  (string) Map type constant.
	 */
	function wpgeo_getMapTypeContentFromUrlArg( arg ) {
		if ( arg == G_NORMAL_MAP.getUrlArg() ) {
			return "G_NORMAL_MAP";
		} else if ( arg == G_SATELLITE_MAP.getUrlArg() ) {
			return "G_SATELLITE_MAP";
		} else if ( arg == G_HYBRID_MAP.getUrlArg() ) {
			return "G_HYBRID_MAP";
		} else if ( arg == G_PHYSICAL_MAP.getUrlArg() ) {
			return "G_PHYSICAL_MAP";
		} else if ( arg == G_MAPMAKER_NORMAL_MAP.getUrlArg() ) {
			return "G_MAPMAKER_NORMAL_MAP";
		} else if ( arg == G_MAPMAKER_HYBRID_MAP.getUrlArg() ) {
			return "G_MAPMAKER_HYBRID_MAP";
		} else if ( arg == G_MOON_ELEVATION_MAP.getUrlArg() ) {
			return "G_MOON_ELEVATION_MAP";
		} else if ( arg == G_MOON_VISIBLE_MAP.getUrlArg() ) {
			return "G_MOON_VISIBLE_MAP";
		} else if ( arg == G_MARS_ELEVATION_MAP.getUrlArg() ) {
			return "G_MARS_ELEVATION_MAP";
		} else if ( arg == G_MARS_VISIBLE_MAP.getUrlArg() ) {
			return "G_MARS_VISIBLE_MAP";
		} else if ( arg == G_MARS_INFRARED_MAP.getUrlArg() ) {
			return "G_MARS_INFRARED_MAP";
		} else if ( arg == G_SKY_VISIBLE_MAP.getUrlArg() ) {
			return "G_SKY_VISIBLE_MAP";
		} else if ( arg == G_SATELLITE_3D_MAP.getUrlArg() ) {
			return "G_SATELLITE_3D_MAP";
		} else if ( arg == G_DEFAULT_MAP_TYPES.getUrlArg() ) {
			return "G_DEFAULT_MAP_TYPES";
		} else if ( arg == G_MAPMAKER_MAP_TYPES.getUrlArg() ) {
			return "G_MAPMAKER_MAP_TYPES";
		} else if ( arg == G_MOON_MAP_TYPES.getUrlArg() ) {
			return "G_MOON_MAP_TYPES";
		} else if ( arg == G_MARS_MAP_TYPES.getUrlArg() ) {
			return "G_MARS_MAP_TYPES";
		} else if ( arg == G_SKY_MAP_TYPES.getUrlArg() ) {
			return "G_SKY_MAP_TYPES";
		}
		return "";
	}
	
})(jQuery);





