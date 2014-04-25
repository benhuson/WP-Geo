<?php

/**
 * WP Geo API: Google Maps v3
 */
class WPGeo_API_GoogleMapsV3 extends WPGeo_API {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV3() {
		add_action( 'wpgeo_register_scripts', array( $this, 'wpgeo_register_scripts' ) );
		add_action( 'wpgeo_enqueue_scripts', array( $this, 'wpgeo_enqueue_scripts' ) );
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_filter( 'wpgeo_decode_api_string', array( $this, 'wpgeo_decode_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv3_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_googlemapsv3_markericon', array( $this, 'wpgeo_api_googlemapsv3_markericon' ), 10, 2 );
		add_filter( 'wpgeo_check_google_api_key', array( $this, 'check_google_api_key' ) );
	}

	/**
	 * Register WP Geo Scripts
	 *
	 * @uses  WPGeo:$version
	 * @uses  WPGeo:get_googlemaps_locale()
	 * @uses  WPGeo:get_google_api_key()
	 */
	function wpgeo_register_scripts() {
		global $wpgeo;
		wp_register_script( 'googlemaps3', $this->get_googlemaps3_script_url(), false, $wpgeo->version );
		wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.v3.js', array( 'jquery', 'wpgeo_tooltip' ), $wpgeo->version );
		wp_register_script( 'wpgeo_admin_post_googlemaps3', WPGEO_URL . 'api/googlemapsv3/js/admin-post.js', array( 'jquery', 'wpgeo_admin_post', 'googlemaps3' ), $wpgeo->version );
	}

	/**
	 * Check Google API Key
	 *
	 * Always return true as Google Maps API v3 does not require the API key.
	 *
	 * @param   bool  $bool  Is an API key set?
	 * @return  bool
	 */
	public function check_google_api_key( $bool ) {
		return true;
	}

	/**
	 * Get Google Maps v3 Script URL
	 *
	 * @return  string  Google Maps API v3 URL.
	 */
	function get_googlemaps3_script_url() {
		global $wpgeo;
		$googlemaps_js_args = array(
			'language' => $wpgeo->get_googlemaps_locale(),
			'sensor'   => 'false'
		);
		$api_key = $wpgeo->get_google_api_key();
		if ( ! empty( $api_key ) ) {
			$googlemaps_js_args['key'] = $api_key;
		}
		return add_query_arg( $googlemaps_js_args, '//maps.googleapis.com/maps/api/js' );
	}

	/**
	 * Enqueue WP Geo Scripts
	 */
	function wpgeo_enqueue_scripts() {
		wp_enqueue_script( 'wpgeo' );
		wp_enqueue_script( 'googlemaps3' );
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'post' == $screen->base ) {
				wp_enqueue_script( 'wpgeo_admin_post_googlemaps3' );
			}
		}
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
			$coord     = $map->points[$i]->get_coord();
			$post      = $map->points[$i]->get_arg( 'post' );
			$post_icon = $map->points[$i]->get_icon();
			$link      = $map->points[$i]->get_link();
			$title     = $map->points[$i]->get_title();
			$icon = 'wpgeo_icon_' . $post_icon;
			if ( ! is_null( $post ) ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $post_icon, $post, 'widget' );
			}
			$markers .= 'var marker_' . $i . '_' . $map->get_js_id() . ' = new google.maps.Marker({ position:new google.maps.LatLng(' . $coord->get_delimited() . '), map:' . $map->get_js_id() . ', icon: ' . $icon . ' });' . "\n";
			if ( ! empty( $link ) ) {
				$markers .= 'google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "click", function() {
						window.location.href = "' . $link . '";
					});
					';
			}
			if ( ! empty( $title ) ) {
				$markers .= '
					var tooltip_' . $i . '_' . $map->get_js_id() . ' = new Tooltip(marker_' . $i . '_' . $map->get_js_id() . ', \'' . esc_js( $title ) . '\');
					google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "mouseover", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.show();
					});
					google.maps.event.addListener(marker_' . $i . '_' . $map->get_js_id() . ', "mouseout", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.hide();
					});
					';
			}
			$markers .= 'bounds.extend(new google.maps.LatLng(' . $coord->get_delimited() . '));' . "\n";
		}
		return $markers;
	}
	
	function get_polylines_js( $map ) {
		$polylines = '';
		if ( count( $map->polylines ) > 0 ) {
			$count = 1;
			foreach ( $map->polylines as $polyline ) {
				$polyline_js_3_coords = array();
				$coords = $polyline->get_coords();
				foreach ( $coords as $c ) {
					$polyline_js_3_coords[] = 'new google.maps.LatLng(' . $c->get_delimited() . ')';
				}
				$polylines = 'var polyline_' . $count . '_' . $map->get_js_id() . ' = new google.maps.Polyline({
						path          : [' . implode( ',', $polyline_js_3_coords ) . '],
						strokeColor   : "' . $polyline->get_color() . '",
						strokeOpacity : ' . $polyline->get_opacity() . ',
						strokeWeight  : ' . $polyline->get_thickness() . ',
						geodesic      : ' . $polyline->get_geodesic() . ',
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
				$map_types = $map->get_map_types();
				$map_types[] = $map->get_map_type();
				foreach ( $map_types as $key => $type ) {
					$map_types[$key] = apply_filters( 'wpgeo_api_string', 'google.maps.MapTypeId.ROADMAP', $type, 'maptype' );
				}
				$map_types = array_unique( $map_types );
				$map_type_control = count( $map_types ) > 1 ? 1 : 0;
				echo '
					if (document.getElementById("' . $map->get_dom_id() . '")) {
						var bounds = new google.maps.LatLngBounds();
						var mapOptions = {
							center             : new google.maps.LatLng(' . $center_coord->get_delimited() . '),
							zoom               : ' . $map->get_map_zoom() . ',
							mapTypeId          : ' . apply_filters( 'wpgeo_api_string', 'google.maps.MapTypeId.ROADMAP', $map->get_map_type(), 'maptype' ) . ',
							mapTypeControl     : ' . $map_type_control . ',
							mapTypeControlOptions : {
								mapTypeIds : [' . implode( ', ', $map_types ) . ']
							},
							streetViewControl  : ' . (int) $map->show_control( 'streetview' ) . ',
							scaleControl       : ' . (int) $map->show_control( 'scale' ) . ',
							overviewMapControl : ' . (int) $map->show_control( 'overview' ) . ',
							overviewMapControlOptions : {
								opened : ' . (int) $map->show_control( 'overview' ) . '
							},
							panControl         : ' . (int) $map->show_control( 'pan' ) . ',
							zoomControl        : ' . (int) $map->show_control( 'zoom' ) . ',
							zoomControlOptions : {
								' . $this->zoom_control_options_js( $map->mapcontrol ) . '
							},
							scrollwheel        : false
						};
						' . $map->get_js_id() . ' = new google.maps.Map(document.getElementById("' . $map->get_dom_id() . '"), mapOptions);
						
						// Add the markers and polylines
						' . $this->get_markers_js( $map ) . '
						' . $this->get_polylines_js( $map ) . '
						';
					if ( count( $map->points ) > 1 ) {
						echo '
						// Adjust Zoom
						google.maps.event.addListenerOnce(' . $map->get_js_id() . ', "bounds_changed", function() {
							var oldZoom = ' . $map->get_js_id() . '.getZoom();
							if ( ' . $map->get_map_zoom() . ' < oldZoom ) {
								' . $map->get_js_id() . '.setZoom(' . $map->get_map_zoom() . ');
							}
						});';
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

	/**
	 * Zoom Control Options JS
	 */
	function zoom_control_options_js( $mapcontrol ) {
		if ( in_array( $mapcontrol, array( 'GSmallMapControl', 'GSmallZoomControl3D', 'GSmallZoomControl' ) ) ) {
			return 'style: google.maps.ZoomControlStyle.SMALL';
		}
		return '';
	}

}
