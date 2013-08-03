
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
		mapTypeId         : eval(mapData.typeid),
		mapTypeControl    : eval(mapData.typecontrol), 
		streetViewControl : eval(mapData.streetviewcontrol),
		overviewMapControl: eval(mapData.overview),
		overviewMapControlOptions:{opened:true}
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
	 * Render a specific map by reading the DOM of its container
	 * @param map container of the map
	 */
	window.wpgeo_build_map = function(map) {
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
	}

	/**
	 * Render a map with Google Maps Static API
	 *
	 * When trigger is provided, triggers the display of a map using the api provided at api_uri.
	 *
	 * @param map container of the map
	 * @api_uri URI of the API to use when the trigger event is triggered
	 * @trigger Name of the event of the container that triggers the new display of the map or "none"
	 */
	window.wpgeo_render_static_map = function(map, api_uri, trigger) {
		// We recompute the dimensions because Google can't bear dimension in percent
		var src = map.data('staticmap').replace('@width@', map.width()).replace('@height@', map.height());

		map.css({
			width		   : map.width(),
			height		   : map.height(),
			'background-image' : 'url('+src+')'
		});

		// Only use static maps if trigger is "none"
		if(trigger != 'none') {
			map.css('cursor', 'pointer').one(trigger, function() {
				// Load the API if needed and then build and render the map
				wpgeo_load_api(api_uri).done(function() {
					map.css('background-image', 'none');
					wpgeo_build_map(map);
				});
			});
		}
	}

	// We create a deferred object in order to chain calls when the API is loaded
	var api_loaded = $.Deferred();
	/**
	 * Indicate that the API is initialized via the api_loader object
	 */
	window.wpgeo_api_loaded = function() {
		// Enable visual refresh
		google.maps.visualRefresh = true;

		// Create wpgeo_icons for use in markers later
		$('.wpgeo_icon').each(function() {
			var icon = $(this).data();
			icon = wpgeo_createIcon(icon.width, icon.height, icon.anchorx, icon.anchory, icon.image, icon.imageshadow);
			// Stock the created icon in the DOM of the element
			$(this).data('icon', icon);
		});

		// Indicate that the API is initialized
		api_loaded.resolve();
	}

	/**
	 * Load the Google Maps API
	 */
	window.wpgeo_load_api = function(api_uri) {
		// Unique id in order to prevent the Google Maps API script to be loaded twice
		var id = 'googlemaps3';
		if (document.getElementById(id)) return api_loaded.resolve();

		// Create the script
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.src = api_uri + "&callback=wpgeo_api_loaded";
		script.id = id;
		script.async = true;

		// Insertion of the script with the others
		var fjs = document.getElementsByTagName('script')[0];
		fjs.parentNode.insertBefore(script, fjs);

		return api_loaded;
	}


	$(window).load(function() {
		// Get the conf stocked in the data of a div
		var conf = $('.wpgeo_conf').first().data();

		// Prepare maps according to the trigger
		switch(conf.trigger) {
			case 'load':
				// Trigger the building and rendering of all maps on load 
				wpgeo_load_api(conf.apiuri).done(function() {
					$('.wpgeo_map').each(function() {
						wpgeo_build_map($(this));
					});
				});
				break;
			default:
				// Use static map API, and render a real map when a specific is triggered on the div (except if trigger = none)
				$('.wpgeo_map').each(function() {
					wpgeo_render_static_map($(this), conf.apiuri, conf.trigger);
				});
				break;
		}
	});
})(jQuery);
