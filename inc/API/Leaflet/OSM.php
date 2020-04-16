<?php

/**
 * @package     WP Geo
 * @subpackage  API \ Leaflet \ OSM
 */

namespace WP_Geo\API\Leaflet;

use WP_Geo\API\WPGeo_API;

if ( ! defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly

class OSM extends WPGeo_API {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'wpgeo_register_scripts', array( $this, 'wpgeo_register_scripts' ) );
		add_action( 'wpgeo_enqueue_scripts', array( $this, 'wpgeo_enqueue_scripts' ) );
		add_action( 'wpgeo_api_leaflet_js', array( $this, 'wpgeo_js' ) );
		add_filter( 'wpgeo_api_leaflet_markericon', array( $this, 'wpgeo_api_leaflet_markericon' ), 10, 2 );
		add_filter( 'wpgeo_check_google_api_key', array( $this, 'check_google_api_key' ) );

	}

	/**
	 * Register WP Geo Scripts
	 *
	 * @uses  WPGeo:$version
	 * @uses  WPGeo:get_googlemaps_locale()
	 * @uses  WPGeo:get_google_api_key()
	 *
	 * @internal  Private. Called via the `wpgeo_register_scripts` action.
	 */
	public function wpgeo_register_scripts() {

		global $wpgeo;

		wp_register_style( 'leaflet', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.css', false, $wpgeo->version );

		wp_register_script( 'leaflet', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.js', false, $wpgeo->version );
		wp_register_script( 'wpgeo', WPGEO_URL . 'js/wp-geo.v3.js', array( 'jquery', 'wpgeo_tooltip' ), $wpgeo->version );
		wp_register_script( 'wpgeo_admin_post_leaflet', WPGEO_URL . 'inc/API/Leaflet/js/admin-post-v3.js', array( 'jquery', 'wpgeo_admin_post', 'leaflet' ), $wpgeo->version );

	}

	/**
	 * Check Google API Key
	 *
	 * Always return true as Leaflet does not require the API key.
	 *
	 * @param   bool  $bool  Is an API key set?
	 * @return  bool
	 *
	 * @internal  Private. Called via the `wpgeo_check_google_api_key` filter.
	 */
	public function check_google_api_key( $bool ) {

		return true;

	}

	/**
	 * Enqueue WP Geo Scripts
	 *
	 * @internal  Private. Called via the `wpgeo_enqueue_scripts` action.
	 */
	public function wpgeo_enqueue_scripts() {

		global $wpgeo;

		wp_enqueue_style( 'leaflet' );

		wp_enqueue_script( 'wpgeo' );
		wp_enqueue_script( 'leaflet' );

		if ( is_admin() ) {
			if ( $wpgeo->admin->show_on_admin_screen() ) {
				$screen = get_current_screen();
				if ( 'post' == $screen->base ) {
					wp_enqueue_script( 'wpgeo_admin_post_leaflet' );
				}
			}
		}

	}

	/**
	 * Marker Icon
	 *
	 * @param   string  $value   Marker icon JavaScript.
	 * @param   object  $marker  WPGeo_Marker.
	 * @return  string           Marker icon.
	 *
	 * @internal  Private. Called via the `wpgeo_api_leaflet_markericon` filter.
	 */
	public function wpgeo_api_leaflet_markericon( $value, $marker ) {

		return 'L.icon({
			iconUrl: "' . $marker->image . '",
			shadowUrl: "' . $marker->shadow . '",
			iconSize:     [' . $marker->width . ', ' . $marker->height . '], // size of the icon
			shadowSize:   [' . $marker->width . ', ' . $marker->height . '], // size of the shadow
			iconAnchor:   [' . $marker->anchorX . ', ' . $marker->anchorY . '], // point of the icon which will correspond to marker location
			shadowAnchor: [' . $marker->anchorX . ', ' . $marker->anchorY . '],  // the same for the shadow
			popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
		});';

	}

	/**
	 * Get Markers JS
	 *
	 * @param   WPGeo_Map  Map object.
	 * @return  string     Markers JS.
	 */
	public function get_markers_js( $map ) {

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

			$markers .= 'var marker_' . $i . '_' . $map->get_js_id() . ' = L.marker([' . $coord->get_delimited() . '], {icon: ' . $icon . '}).addTo(' . $map->get_js_id() . ');' . "\n";

			if ( ! empty( $link ) ) {
				$markers .= 'marker_' . $i . '_' . $map->get_js_id() . '.on("click", function() {
						window.location.href = "' . $link . '";
					});
					';
			}
			if ( ! empty( $title ) ) {
				$markers .= '
					var tooltip_' . $i . '_' . $map->get_js_id() . ' = new Tooltip(marker_' . $i . '_' . $map->get_js_id() . ', \'' . esc_js( $title ) . '\');
					marker_' . $i . '_' . $map->get_js_id() . '.on("mouseover", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.show();
					});
					marker_' . $i . '_' . $map->get_js_id() . '.on("mouseout", function() {
						tooltip_' . $i . '_' . $map->get_js_id() . '.hide();
					});
					';
			}
			$markers .= 'bounds.extend(L.latLng(' . $coord->get_delimited() . '));' . "\n";
		}

		return $markers;

	}

	/**
	 * Get Polylines JS
	 *
	 * @param   WPGeo_Map  Map object.
	 * @return  string     Polylines JS.
	 */
	public function get_polylines_js( $map ) {

		$polylines = '';

		if ( count( $map->polylines ) > 0 ) {
			$count = 1;
			foreach ( $map->polylines as $polyline ) {
				$polyline_js_3_coords = array();
				$coords = $polyline->get_coords();
				foreach ( $coords as $c ) {
					$polyline_js_3_coords[] = '[' . $c->get_delimited() . ']';
				}
				$polylines = 'var polyline_' . $count . '_' . $map->get_js_id() . ' = L.polyline([' . implode( ',', $polyline_js_3_coords ) . '], {
						color   : "' . $polyline->get_color() . '",
						opacity : ' . $polyline->get_opacity() . ',
						weight  : ' . $polyline->get_thickness() . '
					}).addTo(' . $map->get_js_id() . ');';
				$count++;
			}
		}

		return $polylines;

	}

	/**
	 * Get Feeds JS
	 *
	 * @param   WPGeo_Map  Map object.
	 * @return  string     Feeds JS.
	 */
	public function get_feeds_js( $map ) {

		// Not yet supported

		return '';

	}

	/**
	 * Maps JS
	 *
	 * @param  array   Map objects.
	 *
	 * @internal  Private. Called via the `wpgeo_api_leaflet_js` action.
	 */
	public function wpgeo_js( $maps ) {

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

						var bounds = L.latLngBounds();
						var mapOptions = {
							center          : [' . $center_coord->get_delimited() . '],
							zoom            : ' . $map->get_map_zoom() . ',
							zoomControl     : ' . (int) $map->show_control( 'zoom' ) . ',
							scrollWheelZoom : false
						};

						var ' . $map->get_js_id() . ' = L.map("' . $map->get_dom_id() . '", mapOptions);

						L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
							attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors"
						}).addTo(' . $map->get_js_id() . ');

						// Add the markers and polylines
						' . $this->get_markers_js( $map ) . '
						' . $this->get_polylines_js( $map ) . '
						';
				if ( count( $map->points ) > 1 ) {
					echo '
					// Adjust Zoom
					' . $map->get_js_id() . '.on("moveend", function(e) {
						var oldZoom = ' . $map->get_js_id() . '.getZoom();
						if ( ' . $map->get_map_zoom() . ' < oldZoom ) {
							' . $map->get_js_id() . '.setZoom(' . $map->get_map_zoom() . ');
						}
					});';
					echo $map->get_js_id() . '.fitBounds(bounds);';
				}
				echo '
					' . apply_filters( 'wpgeo_map_leaflet_js_preoverlays', '', $map->get_js_id() ) . '
					' . $this->get_feeds_js( $map ) . '
					';
				echo '
					}
					';
			}
			echo '
				}
				wpgeo_render_maps();
				//]]>
				</script>';
		}

	}

}
