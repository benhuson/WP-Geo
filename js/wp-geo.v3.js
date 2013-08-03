
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

(function($){
	// Enable visual refresh
	google.maps.visualRefresh = true;

	/**
	 * Render all the maps on the page by reading through the DOM
	 */
	function wpgeo_render_maps_dom() {

		$('.wpgeo_icon').each(function() {
			var icon = $(this).data();
			console.log('icon prepare', icon);
			icon = wpgeo_createIcon(icon.width, icon.height, icon.anchorx, icon.anchory, icon.image, icon.imageshadow);
			// Stock the created icon in the DOM of the element
			console.log('icon prepare', icon);
			$(this).data('icon', icon);
		});

		$('.wpgeo_map').each(function() {
			var map = $(this);
			var mapData = map.data();

			var markers = [];
			$('.wpgeo_marker', map).each(function(i, marker) {
				var data = $(marker).data();
				// Get the icon stocked in the DOM
				console.log('icon to use', data.icon);
				data.icon = $('.'+data.icon).data('icon');
				console.log('icon used', data.icon);
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

	google.maps.event.addDomListener(window, "load", wpgeo_render_maps_dom);	
})(jQuery);
