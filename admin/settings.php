<?php

/**
 * WP Geo Settings
 */
class WPGeo_Settings {
	
	/**
	 * Constructor
	 */
	function WPGeo_Settings() {
		$this->filter_plugin_action_links();
	}
	
	/**
	 * Filter Plugin Action Links
	 */
	function filter_plugin_action_links() {
		add_filter( 'plugin_action_links', array( $this, 'wpgeo_filter_plugin_action_links' ), 10, 2 );
	}
	
	/**
	 * Based on the Sociable plugin, this adds a 'Settings' option
	 * to the entry on the WP Plugins page.
	 *
	 * @param array $links The array of links displayed by the plugins page
	 * @param string $file The current plugin being filtered.
	 * @return array Array of links.
	 */
	function wpgeo_filter_plugin_action_links( $links, $file ) {
		if ( $file == 'wp-geo/wp-geo.php' ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=wp-geo/includes/wp-geo.php' ) . '">' . __( 'Settings', 'wp-geo' ) . '</a>';
			if ( ! in_array( $settings_link, $links ) )
				array_unshift( $links, $settings_link );
		}
		return $links;
	}
	
}

?>