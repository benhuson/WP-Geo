<?php

/**
 * WP Geo Admin
 */
class WPGeo_Admin {
	
	function WPGeo_Admin() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	
	/**
	 * Admin Notices
	 * Shows error message if WP Geo was unable to create the markers folder
	 * and if no Google API Key has been entered.
	 */
	function admin_notices(){
		global $wpgeo, $current_screen;
		if ( $current_screen->id == 'settings_page_wp-geo' ) {
			// Marker image folder message
			if ( ! $wpgeo->markers->marker_folder_exists() ) {
				echo '<div class="error"><p>' . sprintf( __( "Unable to create the markers folder %s.<br />Please create it and copy the marker images to it from %s</p>", 'wp-geo' ), str_replace( ABSPATH, '', $wpgeo->markers->upload_dir ) . '/wp-geo/markers/', str_replace( ABSPATH, '', WPGEO_DIR ) . 'img/markers' ) . '</div>';
			}
			// Google API Key message
			if ( ! $wpgeo->checkGoogleAPIKey() ) {
				echo '<div class="error"><p>' . sprintf( __( "Before you can use WP Geo you must acquire a %s for your blog - the plugin will not function without it!", 'wp-geo' ), '<a href="https://developers.google.com/maps/documentation/javascript/v2/introduction#Obtaining_Key" target="_blank">' . __( 'Google API Key', 'wp-geo' ) . '</a>' ) . '</p></div>';
			}
		}
		// Version upgrade message
		if ( in_array( $current_screen->id, array( 'settings_page_wp-geo', 'widgets' ) ) ) {
			$wp_geo_show_version_msg = get_option( 'wp_geo_show_version_msg' );
			if ( current_user_can( 'manage_options' ) && $wp_geo_show_version_msg == 'Y' ) {
				echo '<div id="wpgeo_version_message" class="error below-h2" style="margin:5px 15px 2px 0px;">
						<p>' . __( 'WP Geo has been updated to use the WordPress widgets API. You will need to re-add your widgets.', 'wp-geo' ) . ' <a href="' . wp_nonce_url( add_query_arg( 'wpgeo_action', 'dismiss-update-msg', $_SERVER['PHP_SELF'] ), 'wpgeo_dismiss_update_msg' ) . '">' . __( 'Dismiss', 'wp-geo' ) . '</a></p>
					</div>';
			}
		}
	}
	
}

?>