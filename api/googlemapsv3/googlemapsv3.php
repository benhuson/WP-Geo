<?php

/**
 * WP Geo API: Google Maps v3
 */
class WPGeo_API_GoogleMapsV3 extends WPGeo_API {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV3() {
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_filter( 'wpgeo_decode_api_string', array( $this, 'wpgeo_decode_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv3_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_googlemapsv3_markericon', array( $this, 'wpgeo_api_googlemapsv3_markericon' ), 10, 2 );
	}
	
	/**
	 * Marker Icon
	 *
	 * @param string $value Marker icon JavaScript.
	 * @param object $marker WPGeo_Marker.
	 * @return string Marker icon.
	 */
	function wpgeo_api_googlemapsv3_markericon( $value, $marker ) {
		$value = "wpgeo_createIcon(" . $marker->width . ", " . $marker->height . ", " . $marker->anchorX . ", " . $marker->anchorY . ", '" . $marker->image . "', '" . $marker->shadow . "')";
		return $value;
	}
	
	/**
	 * API String
	 */
	function wpgeo_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'g_physical_map'  : return 'google.maps.MapTypeId.TERRAIN';
				case 'g_satellite_map' : return 'google.maps.MapTypeId.SATELLITE';
				case 'g_hybrid_map'    : return 'google.maps.MapTypeId.HYBRID';
				case 'g_normal_map'    :
				default                : return 'google.maps.MapTypeId.ROADMAP';
			}
		}
		return $string;
	}
	
	/**
	 * Decode API String
	 */
	function wpgeo_decode_api_string( $string, $key, $context ) {
		if ( 'maptype' == $context ) {
			switch ( strtolower( $key ) ) {
				case 'google.maps.maptypeid.terrain' :
				case 'terrain' :
					return 'G_PHYSICAL_MAP';
				case 'google.maps.maptypeid.satellite' :
				case 'satellite' :
					return 'G_SATELLITE_MAP';
				case 'google.maps.maptypeid.hybrid' :
				case 'hybrid' :
					return 'G_HYBRID_MAP';
				case 'google.maps.maptypeid.roadmap' :
				case 'roadmap' :
					return 'G_NORMAL_MAP';
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
			$markers .= 'var marker_' . $i . '_' . $map->get_js_id() . ' = new google.maps.Marker({ position:new google.maps.LatLng(' . $map->points[$i]->coord->get_delimited() . '), map:' . $map->get_js_id() . ', icon: ' . $icon . ' });' . "\n";
			if ( ! empty( $map->points[$i]->link ) ) {
				$markers .= 'google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "click", function() {
						window.location.href = "' . $map->points[$i]->link . '";
					});
					';
			}
			if ( ! empty( $map->points[$i]->title ) ) {
				$markers .= '
					var tooltip_' . $i . '_' . $map->get_js_id() . ' = new Tooltip(marker_' . $i . '_' . $map->get_js_id() . ', \'' . esc_js( $map->points[$i]->title ) . '\');
					google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "mouseover", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.show();
					});
					google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "mouseout", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.hide();
					});
					';
			}
			$markers .= 'bounds.extend(new google.maps.LatLng(' . $map->points[$i]->coord->get_delimited() . '));' . "\n";
		}
		return $markers;
	}
	
	function get_polylines_js( $map ) {
		$polylines = '';
		if ( count( $map->polylines ) > 0 ) {
			$count = 1;
			foreach ( $map->polylines as $polyline ) {
				$polyline_js_3_coords = array();
				foreach ( $polyline->coords as $c ) {
					$polyline_js_3_coords[] = 'new google.maps.LatLng(' . $c->get_delimited() . ')';
				}
				$polylines = 'var polyline_' . $count . '_' . $map->get_js_id() . ' = new google.maps.Polyline({
						path          : [' . implode( ',', $polyline_js_3_coords ) . '],
						strokeColor   : "' . $polyline->color . '",
						strokeOpacity : ' . $polyline->opacity . ',
						strokeWeight  : ' . $polyline->thickness . ',
						geodesic      : ' . $polyline->geodesic . ',
						map           : ' . $map->get_js_id() . '
					});';
				$count++;
			}
		}
		return $polylines;
	}
	
	function get_feeds_js( $map ) {
		$feeds = '';
		if ( count( $map->feeds ) > 0 ) {
			$count = 1;
			foreach ( $map->feeds as $feed ) {
				$feeds .= '
					var kmlLayer_' . $count . ' = new google.maps.KmlLayer({
						url : "' . $feed . '",
						map : ' . $map->get_js_id() . '
					});';
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
				//<![CDATA[
				function wpgeo_render_maps() {
					';
			foreach ( $maps as $map ) {
				$center_coord = $map->get_map_centre();
				echo '
					if (document.getElementById("' . $map->get_dom_id() . '")) {
						var bounds = new google.maps.LatLngBounds();
						var mapOptions = {
							center            : new google.maps.LatLng(' . $center_coord->get_delimited() . '),
							zoom              : ' . $map->get_map_zoom() . ',
							mapTypeId         : ' . apply_filters( 'wpgeo_api_string', 'google.maps.MapTypeId.ROADMAP', $map->get_map_type(), 'maptype' ) . ',
							mapTypeControl    : false, // @todo
							streetViewControl : false // @todo
						};
						' . $map->get_js_id() . ' = new google.maps.Map(document.getElementById("' . $map->get_dom_id() . '"), mapOptions);
						
						// Add the markers and polylines
						' . $this->get_markers_js( $map ) . '
						' . $this->get_polylines_js( $map ) . '
						';
					if ( count( $map->points ) > 1 ) {
						echo $map->get_js_id() . '.fitBounds(bounds);';
					}
					echo '
						' . apply_filters( 'wpgeo_map_js_preoverlays', '', $map->get_js_id() ) . '
						' . $this->get_feeds_js( $map ) . '
						';
				echo '
					}
					';
			}
			echo '
				}
				google.maps.event.addDomListener(window, "load", wpgeo_render_maps);
				//]]>
				</script>';
		}
	}
	
}
