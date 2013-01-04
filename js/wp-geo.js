
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
	var icon = new GIcon();
	icon.image = image;
	icon.iconSize = new GSize(width, height);
	icon.iconAnchor = new GPoint(anchorX, anchorY);
	icon.shadow = transparent;
	return icon;
}

/**
 * Create a marker for the map
 */
function wpgeo_createMarker(latlng, icon, title, link) {
	var tooltip;
	var marker = new GMarker(latlng, icon);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title)
	}
	
	marker.latlng = latlng;
	marker.tooltip = tooltip;
	marker.title = title;
	marker.link = link;
	
	if (tooltip) {
		GEvent.addListener(marker, "mouseover", wpgeo_markerOverHandler);
		GEvent.addListener(marker, "mouseout", wpgeo_markerOutHandler);
	}
	if (link) {
		GEvent.addListener(marker, "click", wpgeo_markerClickHandler);
	}
	
	map.addOverlay(marker);
	bounds.extend(marker.getPoint());
	return marker;
}

/**
 * Create a marker for the map
 */
function wpgeoCreateMapMarker(map, latlng, icon, title, link) {
	var tooltip;
	var marker = new GMarker(latlng, icon);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title)
	}
	
	marker.latlng = latlng;
	marker.tooltip = tooltip;
	marker.title = title;
	marker.link = link;
	
	if (tooltip) {
		GEvent.addListener(marker, "mouseover", wpgeo_markerOverHandler);
		GEvent.addListener(marker, "mouseout", wpgeo_markerOutHandler);
	}
	if (link) {
		GEvent.addListener(marker, "click", wpgeo_markerClickHandler);
	}
	
	map.addOverlay(marker);
	bounds.extend(marker.getPoint());
	return marker;
}

/**
 * Create a marker for the map
 */
function wpgeo_createMarker2(map, latlng, icon, title, link) {
	var tooltip;
	var marker = new GMarker(latlng, icon);
	
	// Create a custom tooltip
	if (title) {
		tooltip = new Tooltip(marker, title)
	}
	
	marker.latlng = latlng;
	marker.tooltip = tooltip;
	marker.title = title;
	marker.link = link;
	
	if (tooltip) {
		GEvent.addListener(marker, "mouseover", wpgeo_markerOverHandler);
		GEvent.addListener(marker, "mouseout", wpgeo_markerOutHandler);
	}
	if (link) {
		GEvent.addListener(marker, "click", wpgeo_markerClickHandler);
	}
	
	map.addOverlay(marker);
	return marker;
}

/**
 * Create the polygonal lines between markers
 */
function wpgeo_createPolyline(coords, color, thickness, alpha) {
	var polyOptions = { clickable:true, geodesic:true };
	var polyline = new GPolyline(coords, color, thickness, alpha, polyOptions);
	return polyline;
}



/**
 * Handles the roll over event for a marker
 */
function wpgeo_markerOverHandler() {
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

