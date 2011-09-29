<?php



/**
 * @package     WP Geo
 * @subpackage  Admin > Editor Class
 * @author      Ben Huson <ben@thewhiteroom.net>
 */



class WPGeo_Editor {
	
	
	
	/**
	 * @method       Constructor
	 * @description  Initialise the class.
	 */
	
	function WPGeo_Editor() {
	}
	
	
	
	/**
	 * @method       Add Buttons
	 * @description  This function add buttons to the Rich Editor.
	 */
	
	function add_buttons() {
	
		// Don't bother doing this stuff if the current user lacks permissions
		if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) )
			return;
		
		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true' ) {
			add_filter( 'mce_buttons', array( $this, 'register_map_button' ) );
			add_filter( 'mce_external_plugins', array( $this, 'add_map_plugin' ) );
		}
	
	}
	
	
	
	/**
	 * @method       Register Map Button
	 * @description  This function add the WP Geo map button to the editor.
	 * @parameter    $buttons = Array of editor buttons
	 * @return       (array) Array of buttons
	 */
	
	function register_map_button( $buttons ) {
	
		array_push( $buttons, 'separator', 'wpgeomap' );
		return $buttons;
	
	}
	
	
	
	/**
	 * @method       Load TinyMCE WP Geo Plugin
	 * @description  This function add the WP Geo map button to the editor.
	 * @parameter    $plugin_array = Array of TinyMCE plugins
	 * @return       (array) Array of plugins
	 */
	
	function add_map_plugin( $plugin_array ) {
	
		$plugin_array['wpgeomap'] = WPGEO_URL . 'js/tinymce/plugins/wpgeomap/editor_plugin.js';
		return $plugin_array;
	
	}
	
	

}



?>