


/**
* ----- WP Geo Admin Post -----
* JavaScript for the WP Go Google Maps interface
* when editing posts and pages.
*/



var map = null;
var geocoder = null;
var marker = null;



function wpgeo_updatedLatLngFields() {

	var latitude  = jQuery("input#wp_geo_latitude").val();
	var longitude = jQuery("input#wp_geo_longitude").val();
	
	if ( latitude == '' || longitude == '' ) {
		marker.hide();
	} else {
		var point = new GLatLng(latitude, longitude);
		map.setCenter(point);
		marker.setPoint(point);
		marker.show();
	}
	
}



jQuery(document).ready(function() {
	
	
	
	// Latitude field updated
	jQuery("#wp_geo_latitude").keyup(function() {
		wpgeo_updatedLatLngFields();
	});
	
	
	
	// Longitude field updated
	jQuery("#wp_geo_longitude").keyup(function() {
		wpgeo_updatedLatLngFields();
	});
	
	
	
	// Clear location fields
	jQuery("#wpgeo_location a.wpgeo-clear-location-fields").click(function(e) {
		
		jQuery("input#wp_geo_search").val('');
		jQuery("input#wp_geo_latitude").val('');
		jQuery("input#wp_geo_longitude").val('');
		marker.hide();
		
		return false;
		
	});
	
	
	
	// Centre Location
	jQuery("#wpgeo_location a.wpgeo-centre-location").click(function(e) {
		
		map.setCenter(marker.getLatLng());
		
		return false;
		
	});
	
	
	
	// Location search
	jQuery("#wpgeo_location #wp_geo_search_button").click(function(e) {
		
		var latitude  = jQuery("input#wp_geo_latitude").val();
		var longitude = jQuery("input#wp_geo_longitude").val();
		var address = jQuery("input#wp_geo_search").val();
		
		var geocoder = new GClientGeocoder();
		
		// Set default base country for search
		if ( jQuery("input#wp_geo_base_country_code").length > 0 ) {
			var base_country_code = jQuery("input#wp_geo_base_country_code").val();
			geocoder.setBaseCountryCode(base_country_code);
		}
		
		if ( geocoder ) {
			geocoder.getLatLng(
				address,
				function(point) {
					if ( !point ) {
						alert(address + " not found");
					} else {
						map.setCenter(point);
						marker.setPoint(point);
						marker.show();
						jQuery("input#wp_geo_latitude").val(point.lat());
						jQuery("input#wp_geo_longitude").val(point.lng());
					}
				}
			);
		}
		
		return false;
		
	});
	
	
	
	// Prevent enter from submitting post
	jQuery(window).keydown(function(event){
		if (jQuery("#wpgeo_location input:focus").length > 0) {
			if (event.keyCode == 13) {
				event.preventDefault();
				return false;
			}
		}
	});
	
	
	
});