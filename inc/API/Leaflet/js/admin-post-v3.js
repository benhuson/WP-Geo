
(function($){
	
	/**
	 * Google API v3
	 */
	function wpgeo_init_admin_post_map() {
		
		$(document).ready(function($) {
			
			// Define map
			WPGeo_Admin.map = new google.maps.Map(document.getElementById(WPGeo_Admin.map_dom_id), {
				scrollwheel    : false,
				zoom           : parseInt(WPGeo_Admin.zoom, 10),
				center         : new google.maps.LatLng(WPGeo_Admin.mapCentreX, WPGeo_Admin.mapCentreY),
				mapTypeId      : WPGeo_Admin.mapType,
				zoomControl    : true,
				mapTypeControl : true
			});
			
			// Define marker
			WPGeo_Admin.marker = new google.maps.Marker({
				position  : new google.maps.LatLng(WPGeo_Admin.latitude, WPGeo_Admin.longitude),
				map       : WPGeo_Admin.map,
				draggable : true,
				visible   : WPGeo_Admin.hideMarker == 0
			});
			
			// Update marker location
			$("#wpgeo_location").bind("WPGeo_updateMarkerLatLng", function(e) {
				WPGeo_Admin.marker.setPosition(e.latLng);
				WPGeo_Admin.marker.setVisible(true);
			});
			
			// Update location field
			$("#wpgeo_location").bind("WPGeo_updateLatLngField", function(e) {
				if ( e.lat == '' || e.lng == '' ) {
					WPGeo_Admin.marker.setVisible(false);
				} else {
					var latLng = new google.maps.LatLng(e.lat, e.lng);
					WPGeo_Admin.map.setCenter(latLng);
					WPGeo_Admin.marker.setPosition(latLng);
					WPGeo_Admin.marker.setVisible(true);
					$("#wpgeo_location").trigger({
						type   : 'WPGeo_updateMapCenter',
						latLng : latLng,
						lat    : latLng.lat(),
						lng    : latLng.lng()
					});
				}
			});
			
			// Click on map
			google.maps.event.addListener(WPGeo_Admin.map, "click", function(e) {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMarkerLatLng',
					latLng : e.latLng,
					lat    : e.latLng.lat(),
					lng    : e.latLng.lng()
				});
			});
			
			// Update map type
			google.maps.event.addListener(WPGeo_Admin.map, "maptypeid_changed", function() {
				var type = WPGeo_Admin.map.getMapTypeId();
				switch (type) {
					case "terrain":
						type = "G_PHYSICAL_MAP";
						break;
					case "roadmap":
						type = "G_NORMAL_MAP";
						break;
					case "satellite":
						type = "G_SATELLITE_MAP";
						break;
					case "hybrid":
						type = "G_HYBRID_MAP";
						break;
				}
				$("#wpgeo_location").trigger({
					type    : "WPGeo_updateMapType",
					mapType : type
				});
			});
			
			// Update zoom
			google.maps.event.addListener(WPGeo_Admin.map, "zoom_changed", function() {
				$("#wpgeo_location").trigger({
					type : "WPGeo_updateMapZoom",
					zoom : WPGeo_Admin.map.getZoom()
				});
			});
			
			// Update center
			google.maps.event.addListener(WPGeo_Admin.map, "dragend", function() {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMapCenter',
					latLng : WPGeo_Admin.map.getCenter(),
					lat    : WPGeo_Admin.map.getCenter().lat(),
					lng    : WPGeo_Admin.map.getCenter().lng(),
				});
			});
			
			// Update marker location after drag
			google.maps.event.addListener(WPGeo_Admin.marker, "dragend", function(event) {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMarkerLatLng',
					latLng : WPGeo_Admin.marker.getPosition(),
					lat    : WPGeo_Admin.marker.getPosition().lat(),
					lng    : WPGeo_Admin.marker.getPosition().lng(),
				});
			});
			
			// Hide marker?
			$("#wpgeo_location").bind("WPGeo_hideMarker", function(e){
				WPGeo_Admin.marker.setVisible(false);
			});
			
			// Move to center marker
			$("#wpgeo_location").bind("WPGeo_centerLocation", function(e){
				WPGeo_Admin.map.setCenter(WPGeo_Admin.marker.getPosition());
			});
			
			// Search Location
			$("#wpgeo_location").bind("WPGeo_searchLocation", function(e){
				var geocoder = new google.maps.Geocoder();
				if ( geocoder ) {
					geocoder.geocode({
						address : e.address,
						region  : e.base_country_code
					}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							WPGeo_Admin.map.setCenter(results[0].geometry.location);
							WPGeo_Admin.marker.setPosition(results[0].geometry.location);
							WPGeo_Admin.marker.setVisible(true);
							$("#wpgeo_location").trigger({
								type   : 'WPGeo_updateMarkerLatLng',
								latLng : results[0].geometry.location,
								lat    : results[0].geometry.location.lat(),
								lng    : results[0].geometry.location.lng()
							});
						} else {
							alert(e.address + " not found");
						}
					});
				}
			});

			// Map ready, do other stuff if needed
			$("#wpgeo_location").trigger("WPGeo_adminPostMapReady");
		});
	}
	google.maps.event.addDomListener(window, "load", wpgeo_init_admin_post_map);

})(jQuery);
