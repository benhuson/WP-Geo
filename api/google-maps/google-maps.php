<?php

class WPGeo_API_GoogleMaps {
	
	/**
	 * Constructor
	 */
	function WPGeo_API_GoogleMaps() {
		add_filter( 'wpgeo_map', array( $this, 'wpgeo_map' ), 5, 2 );
		add_filter( 'wpgeo_map_link', array( $this, 'wpgeo_map_link' ), 5, 2 );
		add_filter( 'wpgeo_marker_javascript', array( $this, 'wpgeo_marker_javascript' ), 5, 2 );
	}
	
	/**
	 * Map
	 */
	function wpgeo_map( $map, $args ) {
		$wp_geo_options = get_option( 'wp_geo_options' );
		$args = wp_parse_args( $args, array(
			'id'      => 'wpgeo-map',
			'classes' => array( 'wpgeo-map' ),
			'styles'  => array(),
			'width'   => $wp_geo_options['default_map_width'],
			'height'  => $wp_geo_options['default_map_height'],
			'content' => ''
		) );
		return '<div id="' . $args['id'] . '" class="' . implode( ' ', $args['classes'] ) . '" style="width:' . $args['width'] . '; height:' . $args['height'] . '; ' . implode( '; ', $args['styles'] ) . '">' . $args['content'] . '</div>';
	}
	
	/**
	 * Map Link
	 */
	function wpgeo_map_link( $url, $args ) {
		$q = 'q=' . $args['latitude'] . ',' . $args['longitude'];
		$z = $args['zoom'] ? '&z=' . $args['zoom'] : '';
		return 'http://maps.google.co.uk/maps?' . $q . $z;
	}
	
	/**
	 * Marker JavaScript
	 */
	function wpgeo_marker_javascript( $js, $marker ) {
		return "wpgeo_createIcon(" . $marker->width . ", " . $marker->height . ", " . $marker->anchorX . ", " . $marker->anchorY . ", '" . $marker->image . "', '" . $marker->shadow . "')";
	}
	
}

global $WPGeo_API_GoogleMaps;
$WPGeo_API_GoogleMaps = new WPGeo_API_GoogleMaps();

?>