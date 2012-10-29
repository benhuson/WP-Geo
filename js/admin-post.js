
(function($){
	
	/**
	 * Post Admin
	 */
	 $(document).ready(function() {
		
		/**
		 * Events
		 */
		 
		// Centre location
		$("#wpgeo_location a.wpgeo-centre-location").click(function(e) {
			$('#wpgeo_location').trigger('WPGeo_centerLocation');
			e.preventDefault();
		});
	
		// Clear location fields
		$("#wpgeo_location a.wpgeo-clear-location-fields").click(function(e) {
			$('#wpgeo_location').trigger('WPGeo_clearLocationFields');
			e.preventDefault();
		});
		
		// Location search
		$("#wpgeo_location #wp_geo_search_button").click(function(e) {
			$(this).closest('#wpgeo_location').trigger({
				type              : 'WPGeo_searchLocation',
				address           : $("input#wp_geo_search").val(),
				base_country_code : $("input#wp_geo_base_country_code").val()
			});
			e.preventDefault();
		});
	
		// Latitude field updated
		$("#wp_geo_latitude").keyup(function() {
			$("#wpgeo_location").trigger({
				type : 'WPGeo_updateLatLngField',
				lat  : $("input#wp_geo_latitude").val(),
				lng  : $("input#wp_geo_longitude").val(),
			});
		});
		
		// Longitude field updated
		$("#wp_geo_longitude").keyup(function() {
			$("#wpgeo_location").trigger({
				type : 'WPGeo_updateLatLngField',
				lat  : $("input#wp_geo_latitude").val(),
				lng  : $("input#wp_geo_longitude").val(),
			});
		});
		
		// Prevent search <enter> from submitting post
		$(window).keydown(function(e) {
			if ($("#wpgeo_location input:focus").length > 0) {
				if (e.keyCode == 13) {
					e.preventDefault();
					return false;
				}
			}
		});
		
		/**
		 * Event Handlers
		 */
		
		// Update lat/lng fields
		$("#wpgeo_location").bind("WPGeo_updateMarkerLatLng", function(e) {
			$("#wp_geo_latitude").val(e.lat);
			$("#wp_geo_longitude").val(e.lng);
		});
		
		// Update zoom
		$("#wpgeo_location").bind("WPGeo_updateMapZoom", function(e) {
			$("#wpgeo_map_settings_zoom").val(e.zoom);
		});
		
		// Update map type
		$("#wpgeo_location").bind("WPGeo_updateMapType", function(e) {
			$("#wpgeo_map_settings_type").val(e.mapType);
		});
		
		// Update center
		$("#wpgeo_location").bind("WPGeo_updateMapCenter", function(e) {
			$("#wpgeo_map_settings_centre").val(e.latLng.lat() + "," + e.latLng.lng());
		});
		
		// Clear location fields handler
		$('#wpgeo_location').bind('WPGeo_clearLocationFields', function(e) {
			$(this).find("input#wp_geo_search").val('');
			$(this).find("input#wp_geo_latitude").val('');
			$(this).find("input#wp_geo_longitude").val('');
			$('#wpgeo_location').trigger('WPGeo_hideMarker');
		});
	 	
	 });
	 
})(jQuery);
