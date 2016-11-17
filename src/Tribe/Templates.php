<?php
/**
 * Templating functionality for Tribe Events Calendar
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Tribe__Events__Templates' ) ) {

	/**
	 * Handle views and template files.
	 */
	class Tribe__Events__Templates extends Tribe__Views {

		/**
		 * @var bool Is wp_head complete?
		 */
		public static $wpHeadComplete = false;

		/**
		 * @var bool Is this the main loop?
		 */
		public static $isMainLoop = false;

		/**
		 * The template name currently being used
		 */
		protected static $template = false;

		public static function getTemplateHierarchy( $template, $args = array() ) {
			return Tribe__Events__Views__Template::locate( $template, $args );
		}

		/**
		 * Include the class for the current view
		 *
		 * @param bool $class
		 *
		 **/
		public static function instantiate_template_class( $class = false ) {
			if ( tribe_is_event_query() || tribe_is_ajax_view_request() ) {
				if ( ! $class ) {
					$class = self::get_current_template_class();
				}
				if ( class_exists( $class ) ) {
					new $class;
				}
			}
		}

		/**
		 * @return string
		 */
		public static function get_current_template() {
			return self::$template;
		}

		/**
		 * Get the correct internal page template
		 *
		 * @return string Template path
		 */
		public static function get_current_page_template() {
			$template = '';

			// list view
			if ( tribe_is_list_view() ) {
				$template = Tribe__Events__Views__Template::locate( 'list', array( 'disable_view_check' => true ) );
			}

			// month view
			if ( tribe_is_month() ) {
				$template = Tribe__Events__Views__Template::locate( 'month', array( 'disable_view_check' => true ) );
			}

			// day view
			if ( tribe_is_day() ) {
				$template = Tribe__Events__Views__Template::locate( 'day' );
			}

			if ( Tribe__Views::is_embed() ) {
				$template = Tribe__Events__Views__Template::locate( 'embed' );
			}

			// single event view
			if (
				is_singular( Tribe__Events__Main::POSTTYPE )
				&& ! tribe_is_showing_all()
				&& ! Tribe__Views::is_embed()
			) {
				$template = Tribe__Events__Views__Template::locate( 'single-event', array( 'disable_view_check' => true ) );
			}

			// apply filters
			return apply_filters( 'tribe_events_current_view_template', $template );
		}

		/**
		 * Get the correct internal page template
		 *
		 * @return string Template class
		 */
		public static function get_current_template_class() {
			$class = '';

			// list view
			if ( tribe_is_list_view() || tribe_is_showing_all() || tribe_is_ajax_view_request( 'list' ) ) {
				$class = 'Tribe__Events__Template__List';
			}
			// month view
			elseif ( tribe_is_month() || tribe_is_ajax_view_request( 'month' ) ) {
				$class = 'Tribe__Events__Views__Month_View';
			}
			// day view
			elseif ( tribe_is_day() || tribe_is_ajax_view_request( 'day' ) ) {
				$class = 'Tribe__Events__Template__Day';
			}
			elseif ( Tribe__Templates::is_embed() ) {
				$class = 'Tribe__Events__Template__Embed';
			}
			// single event view
			elseif ( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
				$class = 'Tribe__Events__Template__Single_Event';
			}

			// apply filters
			return apply_filters( 'tribe_events_current_template_class', $class );
		}

		/**
		 * Checks where we are are and determines if we should show events in the main loop
		 *
		 * @param WP_Query $query
		 *
		 * @return WP_Query
		 */
		public static function showInLoops( $query ) {
			if ( ! is_admin() && tribe_get_option( 'showInLoops' ) && ( $query->is_home() || $query->is_tag ) && empty( $query->query_vars['post_type'] ) && false == $query->query_vars['suppress_filters'] ) {

				// @todo remove
				// 3.3 know-how for main query check
				if ( self::is_main_loop( $query ) ) {
					self::$isMainLoop = true;
					$post_types       = array( 'post', Tribe__Events__Main::POSTTYPE );
					$query->set( 'post_type', $post_types );
				}
			}

			return $query;
		}
	}
}