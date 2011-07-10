


/**
* WP Geo for jQuery
* @author Ben Huson, ben@thewhiteroom.net based on functions by Marco Alionso Ramirez, marco@onemarco.com
* @version 1.0
* Google Maps interface for WP Geo WordPress Plugin
*/



// When DOM ready...
//jQuery(document).ready();

String.prototype.trim = function () {
    return this.replace(/^\s*/, "").replace(/\s*$/, "");
}


// When window loaded...
jQuery(window).load(
	function()
	{
		
		// If Google maps supported...
		if (GBrowserIsCompatible())
		{
		
			// Each Map...
			jQuery("div.wp_geo_map").each(
				function(i)
				{
				
					// If has map data...
					if (jQuery(this).find("ul.wp-geo-mapdata").length > 0)
					{
						var table_cell = "ul.wp-geo-mapdata li";
						var w = jQuery(this).find(table_cell + ".wp-geo-width").text();
						var h = jQuery(this).find(table_cell + ".wp-geo-height").text().toString();
						var type = jQuery(this).find(table_cell + ".wp-geo-type").text().toString();
						var zoom = parseInt(jQuery(this).find(table_cell + ".wp-geo-zoom").text());
						var controls = jQuery(this).find(table_cell + ".wp-geo-controls").text();
						var controltypes = jQuery(this).find(table_cell + ".wp-geo-controltypes").text();
						var scale = jQuery(this).find(table_cell + ".wp-geo-scale").text().toString();
						var overview = jQuery(this).find(table_cell + ".wp-geo-overview").text().toString();
					}
					
					// Setup map div
    				var map = jQuery(this).find("div.wp_geo_map_display");
					map.width(w);
					map.height(h);
    				
    				// Create map
					var g_map = new GMap2(map[0]);
					
					var bounds = new GLatLngBounds();
					
					var center = new GLatLng(38.897661, -77.036564);
					g_map.setCenter(center, zoom);
					
					
					// If has points data...
					var longitude;
					var latitude;
					var marker = new Array();
					var point_counter = 1;
					if (jQuery(this).find("ul.wp-geo-mapdata li.wp-geo-marker").length > 0)
					{
						jQuery(this).find("ul.wp-geo-mapdata li.wp-geo-marker").each(
							function(i)
							{
							
								latlong = jQuery(this).find("em").text().split(",");
								latitude = parseFloat(latlong[0].trim());
								longitude = parseFloat(latlong[1].trim());
								
								marker[point_counter] = new wpgeo_createMarker2(g_map, new GLatLng(latitude, longitude), wpgeo_icon_large, 'Barack\'s Home Sweet Home', 'http://wordpressdev/?p=15');
								bounds.extend(new GLatLng(latitude, longitude));
								point_counter++;
								
							}
						);
					}
					
					// Map type
					g_map.addMapType(eval(type));
					g_map.setMapType(eval(type));
					
					var mapTypeControl = new GMapTypeControl();
					g_map.addControl(new GSmallMapControl());
					g_map.addControl(mapTypeControl);
					
					
					bounds.extend(new GLatLng(38.897661, -77.036564));
					
					//g_map.setCenter(marker_0.getLatLng());
					g_map.setZoom(g_map.getBoundsZoomLevel(bounds));
					g_map.setCenter(bounds.getCenter());
					
					// Scale?
					if (scale == 'Y')
						g_map.addControl(new GScaleControl());
					
					// Map Overview?
					if (overview == 'Y')
						g_map.addControl(new GOverviewMapControl());
					
				}
			);
		
		}
		
	}
	
	
	
);
