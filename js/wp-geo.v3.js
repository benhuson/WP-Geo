
/**
* WP Geo Javascript
* @author Ben Huson, ben@thewhiteroom.net based on functions by Marco Alionso Ramirez, marco@onemarco.com
* @version 1.0
* Google Maps interface for WP Geo WordPress Plugin
*/

/**
 * Create a custom marker icon for the map
 */
function wpgeo_createIcon(width, height, anchorX, anchorY, image, transparent) {
	var icon = {};
	icon.url = image;
	icon.size = new google.maps.Size(width, height);
	icon.anchor = new google.maps.Point(anchorX, anchorY);
	//icon.shadow = transparent;
	return icon;
}

/**
 * Create a marker for the map
 */
function wpgeo_createMarker(latlng, icon, title, link) {
	var tooltip;
	
	var markerOptions = {};
	markerOptions.icon = icon;
	markerOptions.position = latlng;
	markerOptions.title = title;
	
	var marker = new google.maps.Marker(markerOptions);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title);
	}
	
	marker.tooltip = tooltip;
	marker.link = link;
	
	if (tooltip) {
		google.maps.event.addListener(marker, 'mouseover', wpgeo_markerOverHandler);
		google.maps.event.addListener(marker, 'mouseout', wpgeo_markerOutHandler);
	}
	
	if (link) {
		google.maps.event.addListener(marker, 'click', wpgeo_markerClickHandler);
	}
	
	return marker;
}

/**
 * Create the polygonal lines between markers
 */
function wpgeo_createPolyline(coords, color, thickness, alpha) {
	var polyOptions = {
		clickable     : true,
		geodesic      : true,
		strokeColor   : color,
		strokeWeight  : thickness,
		strokeOpacity : alpha,
		path          : coords
	};
	var polyline = new google.maps.Polyline(polyOptions);
	return polyline;
}


/**
 * Handles the roll over event for a marker
 */
function wpgeo_markerOverHandler() {
	this.tooltip.show();
}

/**
 * Handles the roll out event for a marker
 */
function wpgeo_markerOutHandler() {
	this.tooltip.hide();
}

/**
 * Handles the click event for a marker
 */
function wpgeo_markerClickHandler() {
	window.location.href= this.link;
}


/**
 * Render a map with its markers, polylines and feeds
 */
function wpgeo_render_map(mapElement, mapData, markers, polylines, feeds) {
	var mapOptions = {
		center            : new google.maps.LatLng(mapData.lat, mapData.lng),
		zoom              : mapData.zoom,
		mapTypeId         : eval(mapData.typeId),
		mapTypeControl    : mapData.typeControl, 
		streetViewControl : mapData.streetViewControl
	};

	var map = new google.maps.Map(mapElement, mapOptions);

	// Add the markers
	var bounds = new google.maps.LatLngBounds();
	for(var i= 0; i < markers.length; i++) {
		var markerData = markers[i];
		var marker = wpgeo_createMarker(new google.maps.LatLng(markerData.lat, markerData.lng), markerData.icon, markerData.title, markerData.link);
		marker.setMap(map);
		bounds.extend(marker.getPosition());
	}
	if(markers.length > 1) {
		map.fitBounds(bounds);
	}
	
	// Add the polylines
	for(var i = 0; i < polylines.length; i++) {
		var polyData = polylines[i];

		var path = [];
		for(var j = 0; j < polyData.path.length; j++) {
			var coord = polyData.path[j];
			path.push(new google.maps.LatLng(coord.lat, coord.lng));
		}

		var polyline = wpgeo_createPolyline(path, polyData.color, polyData.thickness, polyData.opacity, polyData.geodesic);
		polyline.setMap(map);
	}

	// Add the feeds
	for(var i= 0; i < feeds.length; i++) {
		var feedData = feeds[i];
		var kmlLayer =  new google.maps.KmlLayer({
			url : feedData.url,
			map : map
		});
	}
}

// jQuery wrapper
(function($){
	/**
	 * Render all the maps on the page by reading through the DOM
	 */
	window.wpgeo_render_maps_dom = function() {
		// Enable visual refresh
		google.maps.visualRefresh = true;


		$('.wpgeo_icon').each(function() {
			var icon = $(this).data();
			icon = wpgeo_createIcon(icon.width, icon.height, icon.anchorx, icon.anchory, icon.image, icon.imageshadow);
			// Stock the created icon in the DOM of the element
			$(this).data('icon', icon);
		});

		$('.wpgeo_map').each(function() {
			var map = $(this);
			var mapData = map.data();

			var markers = [];
			$('.wpgeo_marker', map).each(function(i, marker) {
				var data = $(marker).data();
				// Get the icon stocked in the DOM
				data.icon = $('.'+data.icon).data('icon');
				markers.push(data);
			}); 

			var polylines = [];
			$('.wpgeo_polyline', map).each(function(i, polyline) {
				polyline = $(polyline);
				var data = polyline.data();
				data.path = [];
				$('.wp_geo_coords', polyline).each(function(i, coord) {
					data.path.push($(coord).data());
				});
				polylines.push(data);
			}); 

			var feeds = [];
			$('.wpgeo_feed', map).each(function(i, feed) {
				feeds.push($(feed).data());
			}); 
			
			wpgeo_render_map(map[0], mapData, markers, polylines, feeds);
		});
	}


	$(window).load(function() {
		var api_uri = $('.wpgeo_api_uri').first().data('uri');
		if(api_uri) {
			// Unique id in order to prevent the Google Maps API script to be loaded twice
			var id = 'googlemaps3';
			if (document.getElementById(id)) return;

			var script = document.createElement("script");
			script.type = "text/javascript";
			script.src = api_uri + "&callback=wpgeo_render_maps_dom";
			script.id = id;
			script.async = true;

			// Insertion of the script with the others
			var fjs = document.getElementsByTagName('script')[0];
			fjs.parentNode.insertBefore(script, fjs);
		}
	});

})(jQuery);
