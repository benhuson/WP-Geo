<?php

/**
 * WP Geo API: Google Maps v2
 */
class WPGeo_API_GoogleMapsV2 extends WPGeo_API {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMapsV2() {
		add_action( 'wpgeo_register_scripts', array( $this, 'wpgeo_register_scripts' ) );
		add_action( 'wpgeo_enqueue_scripts', array( $this, 'wpgeo_enqueue_scripts' ) );
		add_filter( 'wpgeo_api_string', array( $this, 'wpgeo_api_string' ), 10, 3 );
		add_action( 'wpgeo_api_googlemapsv2_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_googlemapsv2_markericon', array( $this, 'wpgeo_api_googlemapsv2_markericon' ), 10, 2 );
		add_action( 'wpgeo_widget_form_fields', array( $this, 'display_widget_api_key_message' ), 1 );
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
		$googlemaps_js = add_query_arg( array(
			'v'      => 2,
			'hl'     => $wpgeo->get_googlemaps_locale(),
			'key'    => $wpgeo->get_google_api_key(),
			'sensor' => 'false'
		), '//maps.google.com/maps?file=api' );
		wp_register_script( 'googlemaps2', $googlemaps_js, false, $wpgeo->version );
		wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.js', array( 'jquery', 'wpgeo_tooltip' ), $wpgeo->version );
		wp_register_script( 'wpgeo_admin_post_googlemaps2', WPGEO_URL . 'api/googlemapsv2/js/admin-post.js', array( 'jquery', 'wpgeo_admin_post', 'googlemaps2' ), $wpgeo->version );
	}

	/**
	 * Enqueue WP Geo Scripts
	 */
	function wpgeo_enqueue_scripts() {
		wp_enqueue_script( 'wpgeo' );
		wp_enqueue_script( 'googlemaps2' );
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'post' == $screen->base ) {
				wp_enqueue_script( 'wpgeo_admin_post_googlemaps2' );
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
			$post_icon = $map->points[$i]->get_icon();
			$post = $map->points[$i]->get_arg( 'post' );
			$coord = $map->points[$i]->get_coord();
			$icon = 'wpgeo_icon_' . $post_icon;
			if ( ! is_null( $post ) ) {
				$icon = 'wpgeo_icon_' . apply_filters( 'wpgeo_marker_icon', $post_icon, $post, 'widget' );
			}
			$markers .= 'var marker_' . $i . ' = wpgeoCreateMapMarker(' . $map->get_js_id() . ', new GLatLng(' . $coord->get_delimited() . '), ' . $icon . ', "' . addslashes( __( $map->points[$i]->get_title() ) ) . '", "' . $map->points[$i]->get_link() . '");' . "\n";
		}
		return $markers;
	}
	
	function get_polylines_js( $map ) {
		$polylines = '';
		if ( count( $map->polylines ) > 0 ) {
			foreach ( $map->polylines as $polyline ) {
				$coords = array();
				$polyline_coords = $polyline->get_coords();
				foreach ( $polyline_coords as $coord ) {
					$coords[] = 'new GLatLng(' . $coord->get_delimited() . ')';
				}
				$options = array();
				if ( $polyline->is_geodesic() ) {
					$options[] = 'geodesic:true';
				}
				$polylines = $map->get_js_id() . '.addOverlay(new GPolyline([' . implode( ',', $coords ) . '],"' . $polyline->get_color() . '",' . $polyline->get_thickness() . ',' . $polyline->get_opacity() . ',{' . implode( ',', $options ) . '}));';
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
					' . $map->get_js_id() . '.setZoom(' . $map->get_map_zoom() . ');
					bounds = new GLatLngBounds();
					
					// Add the markers and polylines
					' . $this->get_markers_js( $map ) . '
					' . $this->get_polylines_js( $map );
				if ( count( $map->points ) > 1 ) {
					echo '
						// Center the map to show all markers
						var center = bounds.getCenter();
						var zoom = ' . $map->get_js_id() . '.getBoundsZoomLevel(bounds)
						if (zoom > ' . $map->get_map_zoom() . ') {
							zoom = ' . $map->get_map_zoom() . ';
						}
						' . $map->get_js_id() . '.setCenter(center, zoom);';
				}
				echo '
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

	/**
	 * Display Widget API Key Message
	 *
	 * @param  object  $widget  Instance of WPGeo_Widget or superclass.
	 */
	public function display_widget_api_key_message( $widget ) {
		global $wpgeo;
		if ( ! $wpgeo->checkGoogleAPIKey() ) {
			echo '<p class="wp_geo_error">' . __( 'WP Geo is not currently active as you have not entered a Google Maps API v2 Key', 'wp-geo') . '. <a href="' . admin_url( '/options-general.php?page=wp-geo/includes/wp-geo.php' ) . '">' . __( 'Please update your WP Geo settings', 'wp-geo' ) . '</a>.</p>';
		}
	}

}
