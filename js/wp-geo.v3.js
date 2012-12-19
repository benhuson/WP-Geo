
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
	
	var markerOptions = new google.maps.MarkerOptions();
	markerOptions.icon = icon;
	markerOptions.position = latlng;
	markerOptions.title = title;
	
	var marker = new google.maps.Marker(markerOptions);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title)
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
	
	marker.setMap(map);
	bounds.extend(marker.getPosition());
	return marker;
}

/**
 * Create a marker for the map
 */
function wpgeo_createMarker2(map, latlng, icon, title, link) {
	var tooltip;
	
	var markerOptions = new google.maps.MarkerOptions();
	markerOptions.icon = icon;
	markerOptions.position = latlng;
	markerOptions.title = title;
	
	var marker = new google.maps.Marker(markerOptions);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title)
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
	
	marker.setMap(map);
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
	// Check API
	if (!(this.isInfoWindowOpen) && !(this.isHidden())) {
		this.tooltip.show();
	}
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

