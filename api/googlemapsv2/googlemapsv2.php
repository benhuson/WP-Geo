<?php

/**
 * WP Geo API: Google Maps v2
 */
class WPGeo_API_GoogleMapsV2 extends WPGeo_API {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV2() {
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv2_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_googlemapsv2_markericon', array( $this, 'wpgeo_api_googlemapsv2_markericon' ), 10, 2 );
	}
	
	/**
	 * Marker Icon
	 *
	 * @param string $value Marker icon JavaScript.
	 * @param object $marker WPGeo_Marker.
	 * @return string Marker icon.
	 */
	function wpgeo_api_googlemapsv2_markericon( $value, $marker ) {
		$value = "wpgeo_createIcon(" . $marker->width . ", " . $marker->height . ", " . $marker->anchorX . ", " . $marker->anchorY . ", '" . $marker->image . "', '" . $marker->shadow . "')";
		return $value;
	}
	
	/**
	 * API String
	 */
	function wpgeo_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'g_physical_map'  : return 'G_PHYSICAL_MAP';
				case 'g_satellite_map' : return 'G_SATELLITE_MAP';
				case 'g_hybrid_map'    : return 'G_HYBRID_MAP';
				case 'g_normal_map'    :
				default                : return 'G_NORMAL_MAP';
			}
		}
		return $string;
	}
	
	function get_markers_js( $map ) {
		$markers = '';
		for ( $i = 0; $i < count( $map->points ); $i++ ) {
			$post_icon = isset( $map->points[$i]->icon ) ? $map->points[$i]->icon : 'small';
			$icon = 'wpgeo_icon_' . $post_icon;
			if ( isset( $map->points[$i]->args['post'] ) ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $post_icon, $map->points[$i]->args['post'], 'widget' );
			}
			$markers .= 'var marker_' . $i . ' = wpgeoCreateMapMarker(' . $map->get_js_id() . ', new GLatLng(' . $map->points[$i]->coord->get_delimited() . '), ' . $icon . ', "' . addslashes( __( $map->points[$i]->title ) ) . '", "' . $map->points[$i]->link . '");' . "\n";
		}
		return $markers;
	}
	
	function get_polylines_js( $map ) {
		$polylines = '';
		if ( count( $map->polylines ) > 0 ) {
			foreach ( $map->polylines as $polyline ) {
				$coords = array();
				foreach ( $polyline->coords as $coord ) {
					$coords[] = 'new GLatLng(' . $coord->get_delimited() . ')';
				}
				$options = array();
				if ( $polyline->geodesic ) {
					$options[] = 'geodesic:true';
				}
				$polylines = $map->get_js_id() . '.addOverlay(new GPolyline([' . implode( ',', $coords ) . '],"' . $polyline->color . '",' . $polyline->thickness . ',' . $polyline->opacity . ',{' . implode( ',', $options ) . '}));';
			}
		}
		return $polylines;
	}
	
	function get_feeds_js( $map ) {
		$feeds = '';
		if ( count( $map->feeds ) > 0 ) {
			$count = 1;
			foreach ( $map->feeds as $feed ) {
				$feeds = '
					kmlLayer_' . $count . ' = new GGeoXml("' . $feed . '");
					GEvent.addListener(kmlLayer_' . $count . ', "load", function() {
						kmlLayer_' . $count . '.gotoDefaultViewport(' . $map->get_js_id() . ');
					});
					' . $map->get_js_id() . '.addOverlay(kmlLayer_' . $count . ');
					';
				$count++;
			}
		}
		return $feeds;
	}
	
	function wpgeo_js( $maps ) {
		if ( ! is_array( $maps ) ) {
			$maps = array( $maps );
		}
		if ( count( $maps ) > 0 ) {
			echo '
				<script type="text/javascript">
				
				function renderWPGeo() {
					if (GBrowserIsCompatible()) {
					';
			foreach ( $maps as $map ) {
				$center_coord = $map->get_map_centre();
				echo '
					' . $map->get_js_id() . ' = new GMap2(document.getElementById("' . $map->get_dom_id() . '"));
					' . $map->get_js_id() . '.addControl(new GSmallZoomControl3D()); // @todo
					' . $map->get_js_id() . '.setCenter(new GLatLng(' . $center_coord->get_delimited() . '), 0);
					' . $map->get_js_id() . '.setMapType(' . $map->get_map_type() . ');
					bounds = new GLatLngBounds();
					
					// Add the markers and polylines
					' . $this->get_markers_js( $map ) . '
					' . $this->get_polylines_js( $map ) . '
		
					// Center the map to show all markers
					var center = bounds.getCenter();
					var zoom = ' . $map->get_js_id() . '.getBoundsZoomLevel(bounds)
					if (zoom > ' . $map->get_map_zoom() . ') {
						zoom = ' . $map->get_map_zoom() . ';
					}
					' . $map->get_js_id() . '.setCenter(center, zoom);
					
					' . apply_filters( 'wpgeo_map_js_preoverlays', '', $map->get_js_id() ) . '
					' . $this->get_feeds_js( $map ) . '
					';
			}
			echo '}
				}
			
				if (document.all&&window.attachEvent) { // IE-Win
					window.attachEvent("onload", function () { renderWPGeo(); });
					window.attachEvent("onunload", GUnload);
				} else if (window.addEventListener) { // Others
					window.addEventListener("load", function () { renderWPGeo(); }, false);
					window.addEventListener("unload", GUnload, false);
				}
				
				</script>
				';
		}
	}
	
}
