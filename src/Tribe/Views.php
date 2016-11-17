<?php
class Tribe__Events__Views {
	/**
	 * Container for our registered views.
	 *
	 * @var array
	 */
	protected $registered_views = array();

	/**
	 * Default view properties.
	 *
	 * @var array
	 */
	protected $default_properties = array(
		'autogenerate_rewrite_rules' => true,
		'rewrite_slug' => '',
		'is_single' => false,
	);

	public function __construct() {
		tribe_singleton( 'tec.views.loader', new Tribe__Events__Views__Loader );
		tribe_singleton( 'tec.views.global-post-titles', new Tribe__Events__Views__Global_Post_Titles );
		tribe_singleton( 'tec.views.theme-template-support', new Tribe__Events__Views__Theme_Template_Support );
		tribe_singleton( 'tec.views.body-classes', new Tribe__Events__Views__Body_Classes );

		tribe( 'tec.views.loader' )->hook();
		tribe( 'tec.views.global-post-titles' )->hook();
		tribe( 'tec.views.theme-template-support' )->hook();
		tribe( 'tec.views.body-classes' )->hook();
	}

	/**
	 * Registers a view.
	 *
	 * @param string        $slug
	 * @param string        $title
	 * @param string|object $implementation
	 * @param array         $properties
	 */
	public function register( $slug, $title, $implementation, array $properties = array() ) {
		$view = array_merge( $properties, $this->default_properties );

		$view['title'] = $title;
		$view['implementation'] = $implementation;

		if ( empty( $view['rewrite_slug'] ) ) {
			$view['rewrite_slug'] = $slug;
		}

		$this->registered_views[ $slug ] = $view;

		/**
		 * Fires immediately after a view has been registered.
		 *
		 * Listening for this event may be useful in cases where you wish to
		 * overwrite a view with a custom implementation.
		 *
		 * @param string $slug
		 * @param array  $properties
		 */
		do_action( 'tribe_events_view_registered', $slug, $properties );
	}

	/**
	 * Returns a list of currently registered views.
	 *
	 * @return array
	 */
	public function get_registered_views() {
		return $this->registered_views;
	}

	/**
	 * Returns the specified view object or boolean false if it has not been
	 * registered.
	 *
	 * If a view slug is not provided it will default to the most appropriate
	 * view, if any are suitable.
	 *
	 * @param string|null $slug
	 *
	 * @return bool|Tribe__Events__Views__Base_View
	 */
	public function get_view( $slug = null ) {
		if ( null === $slug ) {
			$slug = $this->get_current_view_slug();
		}

		if ( ! isset( $this->registered_views[ $slug ] ) ) {
			return false;
		}

		$implementation = $this->registered_views[ $slug ]['implementation'];

		if ( is_object( $implementation ) ) {
			return $implementation;
		}

		if ( class_exists( $implementation ) ) {
			return new $implementation( $slug );
		}

		return false;
	}

	/**
	 * Returns the definition of the specified view object or boolean false if it has
	 * not been registered.
	 *
	 * @param string $slug
	 *
	 * @return bool|array
	 */
	public function get_view_definition( $slug ) {
		return isset( $this->registered_views[ $slug ] ) ? $this->registered_views[ $slug ] : false;
	}

	/**
	 * Indicates if the specified view has been registered.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function is_registered( $slug ) {
		return isset( $this->registered_views[ $slug ] );
	}

	/**
	 * Indicates if the specified view has been enabled (it must also have been
	 * registered for this to be true).
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function is_enabled( $slug ) {
		return in_array( $slug, tribe_get_option( 'tribeEnableViews' ) );
	}

	/**
	 * Attempts to enable the specified view.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function enable( $slug ) {
		if ( ! $this->is_registered( $slug ) ) {
			return false;
		}

		if ( $this->is_enabled( $slug ) ) {
			return false;
		}

		$views = (array) tribe_get_option( 'tribeEnableViews' );
		$views[] = $slug;

		return tribe_update_option( 'tribeEnableViews', $views );
	}

	/**
	 * Returns a list of the currently enabled views.
	 *
	 * @return array
	 */
	public function get_enabled_views() {
		$enabled_views = array();

		foreach ( $this->registered_views as $slug => $view ) {
			if ( $this->is_enabled( $slug ) ) {
				$enabled_views[ $slug ] = $view;
			}
		}

		return $enabled_views;
	}

	/**
	 * Returns the default view, providing a fallback if the default is no longer availble.
	 *
	 * This can be useful is for instance a view added by another plugin (such as PRO) is
	 * stored as the default but can no longer be generated due to the plugin being deactivated.
	 *
	 * @return string
	 */
	public function get_default_view_slug() {
		// Compare the stored default view option to the list of available views
		$default         = Tribe__Settings_Manager::instance()->get_option( 'viewOption', 'month' );
		$available_views = (array) apply_filters( 'tribe-events-bar-views', array(), false );

		foreach ( $available_views as $view ) {
			if ( $default === $view['displaying'] ) {
				return $default;
			}
		}

		// If the stored option is no longer available, pick the first available one instead
		$first_view = array_shift( $available_views );
		$view       = $first_view['displaying'];

		// Update the saved option
		Tribe__Settings_Manager::instance()->set_option( 'viewOption', $view );

		return $view;
	}

	/**
	 * Returns the slug of the currently requested view (in terms of the global
	 * $wp_query object).
	 *
	 * The requested view must be enabled and availalbe, or else null will be returned.
	 *
	 * @return string|null
	 */
	public function get_current_view_slug() {
		$requested_view = $this->get_current_view_from_global_query();

		if ( ! $requested_view ) {
			$requested_view = $this->get_current_view_from_ajax_request();
		}

		if ( $requested_view && $this->is_enabled( $requested_view ) ) {
			return $requested_view;
		}

		$default_view = $this->get_default_view_slug();

		if ( $this->is_enabled( $default_view ) ) {
			return $default_view;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function get_current_view_from_global_query() {
		global $wp_query;

		if ( empty( $wp_query->tribe_is_event_query ) ) {
			return null;
		}

		return $wp_query->get( 'eventDisplay' );
	}

	/**
	 * @return string|null
	 */
	public function get_current_view_from_ajax_request() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return null;
		}

		// If specified, this param takes priority (to enable view switching)
		if ( ! empty( $_POST['tribe_event_display'] ) ) {
			return $_POST['tribe_event_display'];
		}

		// Otherwise, inspect the action parameter to see if the ajax hook is known
		if ( empty( $_POST['action'] ) ) {
			return null;
		}

		foreach ( $this->registered_views as $slug => $properties ) {
			if ( isset( $properties['ajax_hook'] ) && $_POST['action'] === $properties['ajax_hook'] ) {
				return $slug;
			}
		}

		return null;
	}
}