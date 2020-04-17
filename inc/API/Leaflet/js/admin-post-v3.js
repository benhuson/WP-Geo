
(function($){

	function wpgeo_init_admin_post_map() {

		$(document).ready(function($) {

			// Define map
			WPGeo_Admin.map = L.map(document.getElementById(WPGeo_Admin.map_dom_id), {
				scrollwheel    : false,
				zoom           : parseInt(WPGeo_Admin.zoom, 10),
				zoomControl    : true,
				center         : [WPGeo_Admin.mapCentreX, WPGeo_Admin.mapCentreY]
			});

			L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
				attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors"
			}).addTo( WPGeo_Admin.map );

			// Define marker
			WPGeo_Admin.marker = L.marker([WPGeo_Admin.latitude, WPGeo_Admin.longitude], {
				draggable : true,
				opacity   : WPGeo_Admin.hideMarker ? 0 : 1
			}).addTo(WPGeo_Admin.map);

			// Update marker location
			$("#wpgeo_location").bind("WPGeo_updateMarkerLatLng", function(e) {
				WPGeo_Admin.marker.setLatLng(e.latLng);
				WPGeo_Admin.marker.setOpacity(1);
			});

			// Update location field
			$("#wpgeo_location").bind("WPGeo_updateLatLngField", function(e) {
				if ( e.lat == '' || e.lng == '' ) {
					WPGeo_Admin.marker.setOpacity(0);
				} else {
					var latLng = L.latLng(e.lat, e.lng);
					WPGeo_Admin.map.setView(latLng);
					WPGeo_Admin.marker.setLatLng(latLng);
					WPGeo_Admin.marker.setOpacity(1);
					$("#wpgeo_location").trigger({
						type   : 'WPGeo_updateMapCenter',
						latLng : latLng,
						lat    : latLng.lat,
						lng    : latLng.lng
					});
				}
			});

			// Click on map
			WPGeo_Admin.map.on("click", function(e) {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMarkerLatLng',
					latLng : e.latlng,
					lat    : e.latlng.lat,
					lng    : e.latlng.lng
				});
			});

			// Update zoom
			WPGeo_Admin.map.on("zoomlevelschange", function() {
				$("#wpgeo_location").trigger({
					type : "WPGeo_updateMapZoom",
					zoom : WPGeo_Admin.map.getZoom()
				});
			});

			// Update center
			WPGeo_Admin.map.on("moveend", function() {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMapCenter',
					latLng : WPGeo_Admin.map.getCenter(),
					lat    : WPGeo_Admin.map.getCenter().lat,
					lng    : WPGeo_Admin.map.getCenter().lng,
				});
			});

			// Update marker location after drag
			WPGeo_Admin.marker.on("dragend", function(event) {
				$("#wpgeo_location").trigger({
					type   : 'WPGeo_updateMarkerLatLng',
					latLng : WPGeo_Admin.marker.getLatLng(),
					lat    : WPGeo_Admin.marker.getLatLng().lat,
					lng    : WPGeo_Admin.marker.getLatLng().lng,
				});
			});

			// Hide marker?
			$("#wpgeo_location").bind("WPGeo_hideMarker", function(e){
				WPGeo_Admin.marker.setOpacity(0);
			});

			// Move to center marker
			$("#wpgeo_location").bind("WPGeo_centerLocation", function(e){
				WPGeo_Admin.map.setView(WPGeo_Admin.marker.getLatLng());
			});

			// Search Location
			$("#wpgeo_location").bind("WPGeo_searchLocation", function(e) {
				var queryURL = 'https://nominatim.openstreetmap.org/search?format=geojson&countrycodes=' + e.base_country_code + '&q=' + e.address;
				$.getJSON( queryURL, function( data ) {
					if (typeof data.features !== "undefined" && data.features.length) {
						var latLng = L.latLng(data.features[0].geometry.coordinates[1], data.features[0].geometry.coordinates[0]);
						WPGeo_Admin.map.setView(latLng);
						WPGeo_Admin.marker.setLatLng(latLng);
						WPGeo_Admin.marker.setOpacity(1);
						$("#wpgeo_location").trigger({
							type   : 'WPGeo_updateMarkerLatLng',
							latLng : latLng,
							lat    : latLng.lat,
							lng    : latLng.lng
						});
						$("#wpgeo_location").trigger({
							type   : 'WPGeo_updateMapCenter',
							latLng : latLng,
							lat    : latLng.lat,
							lng    : latLng.lng
						});
					} else {
						alert(e.address + " not found");
					}
				});
			});

			// Map ready, do other stuff if needed
			$("#wpgeo_location").trigger("WPGeo_adminPostMapReady");

			$( window ).on( 'load', function() {
				WPGeo_Admin.map.invalidateSize();
			} );

		});
	}

	wpgeo_init_admin_post_map();

})(jQuery);
