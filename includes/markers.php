<?php



/**
 * @package    WP Geo
 * @subpackage Markers Class
 * @author     Ben Huson <ben@thewhiteroom.net>
 */



class WPGeo_Markers {
	
	
	
	/**
	 * Properties
	 */
	var $marker_image_dir = '/uploads/wp-geo/markers/';
	var $markers;
	
	
	
	/**
	 * @method       Constructor
	 * @description  Initialise the class.
	 */
	function WPGeo_Markers() {
		
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
	 * @method       Get Image URL
	 * @description  Get the URL of the image.
	 */
	function get_image_url( $img ) {
		
		$plugin_url = WP_PLUGIN_URL . '/wp-geo/img/markers/';
		$upload_url = WP_CONTENT_URL . $this->marker_image_dir;
		$upload_dir = WP_CONTENT_DIR . $this->marker_image_dir;
		
		if ( file_exists( $upload_dir . $img ) ) {
			return $upload_url . $img;
		}
		
		return $plugin_url . $img;
		
	}
	
	
	
	/**
	 * @method       Add extra markers
	 * @description  Allow plugins and themes to add markers.
	 */
	function add_extra_markers() {
		
		// Allow plugins and themes to add markers
		$this->markers = apply_filters( 'wpgeo_markers', $this->markers );
		
	}
	
	
	
	/**
	 * @method       Get Marker by ID
	 * @description  Retur s marker object.
	 */
	function get_marker_by_id( $marker_id ) {
		
		foreach ( $this->markers as $m ) {
			if ( $m->id == $marker_id ) {
				return $m;
			}
		}
		
	}
	
	
	
	/**
	 * @method       Marker Folder Exists
	 * @description  Checks that the marker images folder has been created.
	 * @return       (boolean)
	 */
	function marker_folder_exists() {
		
		if ( is_dir( WP_CONTENT_DIR . '/uploads/wp-geo/markers' ) ) {
			return true;
		}
		return false;
		
	}
	
	
	
	/**
	 * @method       Register Activation
	 * @description  When the plugin is activated, created all the required folder
	 *               and move the marker images there.
	 */
	function register_activation() {
		
		// New Marker Folders
		clearstatcache();
		$old_umask = umask( 0 );
		
		if ( is_writable( WP_CONTENT_DIR ) && ( !is_dir( WP_CONTENT_DIR . '/uploads/wp-geo' ) || !is_dir( WP_CONTENT_DIR . '/uploads/wp-geo/markers' ) ) ) {
			mkdir( WP_CONTENT_DIR . '/uploads/wp-geo' );
			mkdir( WP_CONTENT_DIR . '/uploads/wp-geo/markers' );
		}
		
		// Marker Folders
		$old_marker_image_dir = WP_CONTENT_DIR . '/plugins/wp-geo/img/markers/';
		$new_marker_image_dir = WP_CONTENT_DIR . $this->marker_image_dir;
		
		// Marker Files
		if ( is_dir( $new_marker_image_dir ) ) {
			$this->moveFileOrDelete( $old_marker_image_dir . 'dot-marker.png', $new_marker_image_dir . 'dot-marker.png' );
			$this->moveFileOrDelete( $old_marker_image_dir . 'dot-marker-shadow.png', $new_marker_image_dir . 'dot-marker-shadow.png' );
			$this->moveFileOrDelete( $old_marker_image_dir . 'large-marker.png', $new_marker_image_dir . 'large-marker.png' );
			$this->moveFileOrDelete( $old_marker_image_dir . 'large-marker-shadow.png', $new_marker_image_dir . 'large-marker-shadow.png' );
			$this->moveFileOrDelete( $old_marker_image_dir . 'small-marker.png', $new_marker_image_dir . 'small-marker.png' );
			$this->moveFileOrDelete( $old_marker_image_dir . 'small-marker-shadow.png', $new_marker_image_dir . 'small-marker-shadow.png' );
		}
		
		// Reset default permissions
		umask( $old_umask );
		
	}
	
	
	
	/**
	 * @method       Move File or Delete
	 * @description  Move a file, or replace it if one already exists.
	 */
	function moveFileOrDelete( $old_file, $new_file ) {
		
		if ( !file_exists( $new_file ) ) {
			$ok = copy( $old_file, $new_file );
			if ( $ok ) {
				// Moved OK...
			}
		}
		
	}
	
	
	
	/**
	 * @method       WP Head
	 * @description  Output HTML header.
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
	 * @method       Get Admin Display
	 * @description  Output marker HTML for the admin.
	 */
	function get_admin_display() {
		
		$html = '';
		
		foreach ( $this->markers as $m ) {
			$html .= $m->get_admin_display();
		}
		
		return '<table class="form-table">' . $html . '</table>';
		
	}
	
	
	
	/**
	 * @method       Dropdown Markers
	 * @description  Output marker select menu.
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
		$name = esc_attr( $r['name'] );
		
		if ( empty( $r['id'] ) )
			$r['id'] = $r['name'];
		
		$output = '<select name="' . $name . '" id="' . $id . '">';
		if ( !empty( $r['show_option_none'] ) )
			$output .= '<option value="' . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . '</option>';
		foreach ( $this->markers as $marker ) {
			$selected = '';
			if ( $r['selected'] == $marker->id )
				$selected = ' selected="selected"';
			$output .= '<option value="' . esc_attr( $marker->id ) . '"' . $selected . '>' . $marker->name . '</option>';
		}
		$output .= '</select>';
		
		if ( $echo )
			echo $output;
		
		return $output;
		
	}
	
	
	
}



?>