<?php
class Tribe__Events__Views__Loader {
	protected $template = '';
	protected $selected_view;

	public function hook() {
		$init_view_action = tribe_is_ajax_view_request() ? 'admin_init' : 'template_redirect';
		add_action( $init_view_action, array( $this, 'setup_requested_view' ) );
		add_filter( 'template_include', array( $this, 'template_chooser' ) );
	}

	/**
	 * Selects and prepares the requested event view for display.
	 */
	public function setup_requested_view() {
		/**
		 * @var Tribe__Events__Views
		 */
		$view_manager = tribe( 'tec.views' );

		/**
		 * @var Tribe__Events__Views__Base_View $this->selected_view
		 */
		if ( ! $this->selected_view = $view_manager->get_view() ) {
			return;
		}

		if ( ! $this->selected_view || ! $this->selected_view->is_enabled() ) {
			return;
		}

		// Setup ajax responses when required
		if ( Tribe__Main::instance()->doing_ajax() ) {
			$ajax_hook = $this->selected_view->get_property( 'ajax_hook' );
			add_action( 'wp_ajax_' . $ajax_hook, array( $this, 'ajax_response' ) );
			add_action( 'wp_ajax_nopriv_' . $ajax_hook, array( $this, 'ajax_response' ) );
		}
	}

	/**
	 * Indicates if an event view was found and selected in relation to the current
	 * request.
	 *
	 * @return bool
	 */
	public function has_selected_view() {
		return is_object( $this->selected_view );
	}

	/**
	 * Returns the view object selcted to serve the current request, if any, or else
	 * null.
	 *
	 * @return Tribe__Views__Base_View|null
	 */
	public function get_selected_view() {
		return $this->selected_view;
	}

	/**
	 * Pick the correct template to include
	 *
	 * @param string $template Path to template
	 *
	 * @return string Path to template
	 */
	public function template_chooser( $template ) {
		$displaying = Tribe__Events__Main::instance()->displaying;

		/**
		 * Fires when The Events Calendar's template chooser runs.
		 *
		 * @param string $template
		 */
		do_action( 'tribe_tec_template_chooser', $template );

		// Bail if this is not an events query
		if ( ! tribe_is_event_query() ) {
			return $template;
		}

		// Return the 404 template if this is for a single post that could not be found
		if ( is_single() && is_404() ) {
			return get_404_template();
		}

		// Return the 404 template for non-single views that are not enabled, with the exception
		// of day view (allowing month overflow links to work correctly)
		if ( ! is_single() && ! tribe_events_is_view_enabled( $displaying ) && 'day' !== $displaying ) {
			return get_404_template();
		}

		/**
		 * Provides an opportunity to override the template selection.
		 *
		 * @param string $template
		 */
		$this->template = apply_filters( 'tribe_events_views_template_selection', $this->select_template() );

		return $this->template;
	}

	/**
	 * Selects the most appropriate template to service the current request.
	 *
	 * This generally reflects the current value of the Events Template setting
	 * found in the Events > Settings > Display screen.
	 *
	 * @return string
	 */
	protected function select_template() {
		// If this is an oembed, override the wrapping template and use the embed template
		if ( Tribe__Views::is_embed() ) {
			return Tribe__Events__Views__Template::locate( 'embed' );
		}

		// If the Default Events Template is in use selection is straightforward
		if ( tribe_get_option( 'tribeEventsTemplate', 'default' ) == '' ) {
			return Tribe__Events__Views__Template::locate( 'default-template' );
		}

		// Otherwise we need to return the appropriate custom theme template
		$theme_template = tribe( 'tec.views.theme-template-support' );
		$theme_template->setup_template();
		return $theme_template->get_template();
	}

	/**
	 * Renders the view output in an ajax context then kills execution.
	 */
	public function ajax_response() {
		$response = '';

		if ( $this->selected_view ) {
			$this->setup_query_for_ajax_response();
			$query = $this->selected_view->get_query();

			$response = array (
				'html'        => $this->selected_view->get_output(),
				'success'     => true,
				'view'        =>  $this->selected_view->get_slug(),
				'max_pages'   => $query->max_num_pages,
				'total_count' => $query->total_count,
				'hash'        => md5( $query->query_vars ),
			);

			/**
			 * Provides a final opportunity to adapt and modify the ajax
			 * view response in array form.
			 *
			 * @param array $response
			 */
			$response = (array) apply_filters( 'tribe_events_ajax_response', $response );

			header( 'Content-type: application/json' );
			$response = json_encode( $response );
		}

		/**
		 * Provides a final opportunity to adapt and modify the ajax
		 * view response.
		 *
		 * @param array $response
		 */
		tribe_exit( apply_filters( 'tribe_events_ajax_response_raw', $response ) );
	}

	/**
	 * Builds a WP_Query object that can be used to populate a view requested
	 * via ajax.
	 *
	 * @todo refer to Tribe__Events__Views__List_View::ajax_response() for other aspects to implement
	 */
	protected function setup_query_for_ajax_response() {
		global $wp_query;

		// Setup basic query args
		$args = array(
			'eventDisplay' => $this->selected_view->get_slug(),
			'post_type'    => Tribe__Events__Main::POSTTYPE,
			'post_status'  => is_user_logged_in() ? array( 'publish', 'private' ) : array( 'private' ),
			'paged'        => ! empty( $_POST['tribe_paged'] ) ? intval( $_POST['tribe_paged'] ) : 1,
		);

		// Support past event requests
		if ( isset( $_POST['tribe_event_display'] ) && 'past' === $_POST['tribe_event_display'] ) {
			$args[ 'eventDisplay' ] = 'past';
			$args[ 'order' ] = 'DESC';
		}

		// Support 'all' event requests - @todo consider moving across to ECP
		if ( 'all' === $_POST[ 'tribe_event_display' ] ) {
			$args[ 'eventDisplay' ] = 'all';
		}

		// Support category requests
		if ( isset( $_POST['tribe_event_category'] ) ) {
			$args[ Tribe__Events__Main::TAXONOMY ] = $_POST['tribe_event_category'];
		}

		// Add the eventdate if provided
		if ( isset( $_POST['eventDate'] ) ) {
			$args['eventDate'] = $_POST['eventDate'];
		}

		// Setting/checking the hash is used to detect if the primary arguments have changed (example: if Filter
		// Bar is active and the filter options have changed)
		$query = tribe_get_events( $args, true );
		$hash  = md5( maybe_serialize( $query->query_vars ) );

		// Go back to page one if the hash has changed
		if ( ! empty( $_POST['hash'] ) && $hash !== $_POST['hash'] ) {
			$args['paged'] = 1;
		}

		/**
		 * Dictates the arguments of the query used to populate event views
		 * during ajax requests.
		 *
		 * @param array $args
		 * @param Tribe__Events__Views__Base_View $view
		 */
		$args = (array) apply_filters( 'tribe_events_views_ajax_query_args', $args, $this->selected_view );

		Tribe__Events__Query::init();
		$wp_query = new WP_Query( $args );

		$this->selected_view->set_data( array(
			'query' => $wp_query
		) );
	}
}