<?php

/**
 * WP Geo Dashboard
 * Display the WP Geo Blog RSS feed in the dashboard.
 */
if ( ! class_exists( 'WPGeo_Dashboard' ) ) {
	
	class WPGeo_Dashboard {
		
		/**
		 * Constructor
		 */
		function WPGeo_Dashboard() {
			add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
			add_filter( 'wp_dashboard_widgets', array( $this, 'add_widget' ) );
		}
		
		/**
		 * Register the dashboard widget
		 */
		function register_widget() {
			wp_add_dashboard_widget( 'wpgeo_dashboard', 'WP Geo',
				array( $this, 'widget' ),
				array(
					'all_link'  => 'http://www.wpgeo.com/',
					'feed_link' => 'http://www.wpgeo.com/feed/'
				)
			);
		}
		
		/**
		 * Add the dashboard widget
		 *
		 * @param array $widgets Array of widgets.
		 * @return array Widgets.
		 */
		function add_widget( $widgets ) {
			global $wp_registered_widgets;
			
			if ( ! isset( $wp_registered_widgets['wpgeo_dashboard'] ) ) {
				return $widgets;
			}
			array_splice( $widgets, sizeof( $widgets ) - 1, 0, 'wpgeo_dashboard' );
			
			return $widgets;
		}
		
		/**
		 * Display the dashboard widget
		 *
		 * @param array $args Args.
		 */
		function widget( $args = null ) {
			
			// Validate Args
			$defaults = array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
				'widget_name'   => ''
			);
			extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

			echo $before_widget . $before_title . $widget_name . $after_title;
			echo '<div style="background-image:url(' . plugins_url( WPGEO_SUBDIR . 'img/logo/wp-geo.png' ) . '); background-repeat:no-repeat; background-position:right top; padding-right:80px;">';
			
			$feed = fetch_feed( 'http://feeds2.feedburner.com/wpgeo' );
			
			if ( is_wp_error( $feed ) || ! $feed->get_item_quantity() ) {
				echo '<p>' . __( 'No recent updates.', 'wp-geo' ) . '</p>';
				return;
			}
			
			$items = $feed->get_items( 0, 2 );
			
			foreach ( $items as $item ) {
				$url         = esc_url( $item->get_link() );
				$title       = esc_html( $item->get_title() );
				$date        = esc_html( strip_tags( $item->get_date() ) );
				$description = esc_html( strip_tags( @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) ) ) );
				echo '<div style="margin-bottom:20px;">';
				echo '<p style="margin-bottom:5px;"><a style="font-size: 1.2em; font-weight:bold;" href="' . $url  . '" title="' . $title . '">' . $title . '</a></p>';
				echo '<p style="color: #aaa; margin-top:5px;">' . date( 'l, jS F Y', strtotime( $date ) ) . '</p>';
				echo '<p>' . $description .'</p>';
				echo '</div>';
			}
			
			echo '<p><a href="http://www.wpgeo.com/">' . __( 'View all WP Geo news...', 'wp-geo' ) . '</a></p>';
			echo '</div>';
			echo $after_widget;
		}
		
	}
	
	global $wpgeo_dashboard;
	$wpgeo_dashboard = new WPGeo_Dashboard();
	
}
