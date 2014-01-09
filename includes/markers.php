<?php

/**
 * WP Geo Markers
 */
class WPGeo_Markers {

	var $upload_dir       = '';
	var $wpgeo_upload_dir = '';
	var $marker_image_url = '';
	var $marker_image_dir = '';
	var $markers;

	/**
	 * Constructor
	 */
	function WPGeo_Markers() {

		// Marker directories abstraction. props Alain (alm)
		$upl = wp_upload_dir();
		$this->upload_dir       = $upl['basedir'];
		$this->wpgeo_upload_dir = $upl['basedir'] . '/wp-geo/';
		$this->marker_image_dir = $upl['basedir'] . '/wp-geo/markers/';
		$this->marker_image_url = $upl['baseurl'] . '/wp-geo/markers/';

		$this->markers = array();

		// Large Marker
		$this->markers[] = new WPGeo_Marker(
			'large',
			__( 'Default Large Marker', 'wp-geo' ),
			__( 'This is the default marker used to indicate a location on most maps.', 'wp-geo' ),
			20, 34, 10, 34,
			$this->get_image_url( 'large-marker.png' ),
			$this->get_image_url( 'large-marker-shadow.png' )
		);

		// Small Marker
		$this->markers[] = new WPGeo_Marker(
			'small',
			__( 'Default Small Marker', 'wp-geo' ),
			__( 'This is the default marker used for the WP Geo sidebar widget.', 'wp-geo' ),
			10, 17, 5, 17,
			$this->get_image_url( 'small-marker.png' ),
			$this->get_image_url( 'small-marker-shadow.png' )
		);

		// Dot Marker
		$this->markers[] = new WPGeo_Marker(
			'dot',
			__( 'Default Dot Marker', 'wp-geo' ),
			__( 'This marker image is not currently used but it is anticipated that it will be used to indicate less important locations in a future versions of WP Geo.', 'wp-geo' ),
			8, 8, 3, 6,
			$this->get_image_url( 'dot-marker.png' ),
			$this->get_image_url( 'dot-marker-shadow.png' )
		);
	}

	/**
	 * Get Image URL
	 *
	 * @param   string  $img  Image file name.
	 * @return  string        Image URL.
	 */
	function get_image_url( $img ) {
		if ( file_exists( $this->marker_image_dir . $img ) ) {
			return $this->marker_image_url . $img;
		}
		return WPGEO_URL . 'img/markers/' . $img;
	}

	/**
	 * Add extra markers
	 * Allows plugins and themes to add markers.
	 */
	function add_extra_markers() {
		$this->markers = apply_filters( 'wpgeo_markers', $this->markers );
	}

	/**
	 * Get Marker Object by ID
	 *
	 * @param   string  $marker_id  Marker ID.
	 * @return  object              Marker.
	 */
	function get_marker_by_id( $marker_id ) {
		foreach ( $this->markers as $m ) {
			if ( $m->id == $marker_id ) {
				return $m;
			}
		}
	}

	/**
	 * Marker Folder Exists
	 * Checks that the marker images folder has been created.
	 *
	 * @return  boolean
	 */
	function marker_folder_exists() {
		if ( is_dir( $this->marker_image_dir ) ) {
			return true;
		}

		// Make dirs and register for site because we may be in multisite.
		// Then retry. props Alain (alm)
		$this->register_activation();
		return ( is_dir( $this->marker_image_dir ) ) ? true : false;
	}

	/**
	 * Register Activation
	 * When the plugin is activated, created all the required folder
	 * and move the marker images there.
	 */
	function register_activation() {

		// Create Marker Folders?
		clearstatcache();
		$old_umask = umask( 0 );
		if ( is_writable( ABSPATH . 'wp-content' ) && ( ! is_dir( $this->wpgeo_upload_dir ) || ! is_dir( $this->marker_image_dir ) ) ) {
			mkdir( $this->wpgeo_upload_dir );
			mkdir( $this->marker_image_dir );
		}

		// Marker Folders
		$old_marker_image_dir = WPGEO_DIR . 'img/markers/';
		$new_marker_image_dir = $this->marker_image_dir;

		// Marker Files
		if ( is_dir( $new_marker_image_dir ) ) {
			$this->_move_file_or_replace( $old_marker_image_dir . 'dot-marker.png', $new_marker_image_dir . 'dot-marker.png' );
			$this->_move_file_or_replace( $old_marker_image_dir . 'dot-marker-shadow.png', $new_marker_image_dir . 'dot-marker-shadow.png' );
			$this->_move_file_or_replace( $old_marker_image_dir . 'large-marker.png', $new_marker_image_dir . 'large-marker.png' );
			$this->_move_file_or_replace( $old_marker_image_dir . 'large-marker-shadow.png', $new_marker_image_dir . 'large-marker-shadow.png' );
			$this->_move_file_or_replace( $old_marker_image_dir . 'small-marker.png', $new_marker_image_dir . 'small-marker.png' );
			$this->_move_file_or_replace( $old_marker_image_dir . 'small-marker-shadow.png', $new_marker_image_dir . 'small-marker-shadow.png' );
		}

		// Reset default permissions
		umask( $old_umask );
	}

	/**
	 * Move File or Replace
	 * Move a file, or replace it if one already exists.
	 *
	 * @param  string  $old_file  Old file path.
	 * @param  string  $new_file  New file path.
	 */
	function _move_file_or_replace( $old_file, $new_file ) {
		if ( ! file_exists( $new_file ) ) {
			$ok = copy( $old_file, $new_file );
			if ( $ok ) {
				// Moved OK...
			}
		}
	}

	/**
	 * WP Head
	 * Output HTML header.
	 *
	 * @todo Once all map JS in footer, this can be moved there also.
	 */
	function wp_head() {
		$js = '';
		foreach ( $this->markers as $m ) {
			$js .= $m->get_javascript();
		}

		echo '
			<script type="text/javascript">
			//<![CDATA[
			// ----- WP Geo Marker Icons -----
			' . $js . '
			//]]>
			</script>
			';
	}

	/**
	 * Get Admin Display
	 * Output marker HTML for the admin.
	 *
	 * @return  string  Table HTML.
	 */
	function get_admin_display() {
		$html = '';
		foreach ( $this->markers as $m ) {
			$html .= $m->get_admin_display();
		}
		return '<table class="form-table">' . $html . '</table>';
	}

	/**
	 * Dropdown Markers
	 * Output marker select menu.
	 *
	 * @param   array  $args  Args.
	 * @return  string        Dropdown HTML.
	 */
	function dropdown_markers( $args ) {
		$defaults = array(
			'selected'          => '',
			'echo'              => 1,
			'name'              => 'marker_id',
			'id'                => '',
			'show_option_none'  => '',
			'option_none_value' => ''
		);
		$r = wp_parse_args( $args, $defaults );

		$output = '';
		$name = $r['name'];

		if ( empty( $r['id'] ) ) {
			$r['id'] = $r['name'];
		}
		$id = $r['id'];

		$options = array();
		if ( ! empty( $r['show_option_none'] ) ) {
			$options[$r['option_none_value']] = $r['show_option_none'];
		}
		foreach ( $this->markers as $marker ) {
			$options[$marker->id] = $marker->name;
		}

		$output .= wpgeo_select( $name, $options, $r['selected'], false, $id );

		if ( $r['echo'] ) {
			echo $output;
		}
		return $output;
	}

}

