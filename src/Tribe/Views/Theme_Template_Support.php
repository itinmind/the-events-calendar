<?php
/**
 * Supports the use of theme-provided templates in place of the Default
 * Events Template.
 */
class Tribe__Events__Views__Theme_Template_Support {
	/**
	 * The selected custom/theme template, if any.
	 *
	 * @var string
	 */
	protected $template = '';

	/**
	 * Indicates if the wp_head action has completed.
	 *
	 * (In practice, this will be late on during the wp_head action, at
	 * priority 999.)
	 *
	 * @var bool
	 */
	protected $wp_head_complete = false;

	public function hook() {
		add_action( 'wp_head', array( $this, 'maybe_spoof_query' ), 100 );
		add_action( 'wp_head', array( $this, 'wp_head_finished' ), 999 );
	}

	/**
	 * Setup query spoofing, if required.
	 *
	 * This allows us to inject event content into regular theme templates by
	 * making sure we enter the loop by always having some posts in the global
	 * $wp_query object.
	 */
	public function maybe_spoof_query() {
		global $post,
		       $wp_query;

		// Bail for single posts where the password is still required, and for feeds
		if ( is_single() && post_password_required() || is_feed() ) {
			return;
		}

		// Bail unless this is the main events query (and the default events template is not in use)
		if ( ! $wp_query->is_main_query() || ! tribe_is_event_query() || tribe_get_option( 'tribeEventsTemplate', 'default' ) == '' ) {
			return;
		}

		$post                 = $this->spoofed_post();
		$wp_query->posts[]    = $post;
		$wp_query->post_count = count( $wp_query->posts );

		$wp_query->spoofed = true;
		$wp_query->rewind_posts();

		// Prevent unnecessary database queries that might be triggered when theme/template functions run in relation to the spoofed post
		wp_cache_set( $post->ID, $post, 'posts' );
		wp_cache_set( $post->ID, array( true ), 'post_meta' );
	}

	/**
	 * Returns an object with basically the same properties as a WP_Post object.
	 *
	 * @return object
	 */
	protected function spoofed_post() {
		return (object) array(
			'ID'                    => 0,
			'post_status'           => 'draft',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		);
	}

	/**
	 * Spoof the global post (automatically unhooks itself to help reduce the chance
	 * of it firing multiple times accidentally).
	 */
	public function spoof_the_post() {
		$GLOBALS['post'] = $this->spoofed_post();
		remove_action( 'the_post', array( $this, 'spoof_the_post' ) );
	}

	/**
	 * Restore the original query after spoofing it (if it was indeed spoofed).
	 */
	public function restore_query() {
		global $wp_query;

		// If the query hasn't been spoofed we need take no action
		if ( ! isset( $wp_query->spoofed ) || ! $wp_query->spoofed ) {
			return;
		}

		// Remove the spoof post and fix the post count
		array_pop( $wp_query->posts );
		$wp_query->post_count = count( $wp_query->posts );

		// If we have other posts besides the spoof, rewind and reset
		if ( $wp_query->post_count > 0 ) {
			$wp_query->rewind_posts();
			wp_reset_postdata();
		}

		// If there are no other posts, unset the $post property
		if ( 0 === $wp_query->post_count ) {
			$wp_query->current_post = -1;
			unset( $wp_query->post );
		}

		// Don't do this again
		unset( $wp_query->spoofed );
	}

	/**
	 * Determines the appropriate template file to be loaded if a custom/theme template
	 * is currently set as the default template for event views.
	 */
	public function setup_template() {
		if ( ! is_single() || ! post_password_required() ) {
			add_action( 'loop_start', array( $this, 'override_loop' ) );
		}

		$template = locate_template( tribe_get_option( 'tribeEventsTemplate', 'default' ) == 'default'
			? 'page.php'
			: tribe_get_option( 'tribeEventsTemplate', 'default' ) );

		if ( $template == '' ) {
			$template = get_index_template();
		}

		$this->template = $template;
	}

	/**
	 * This is where the magic happens where we run some ninja code that hooks the query to resolve to an events template.
	 *
	 * @param WP_Query $query
	 */
	public function override_loop( $query ) {
		do_action( 'tribe_events_filter_the_page_title' );

		if ( ! $query->is_main_query() || ! $this->wp_head_complete ) {
			return;
		}

		add_action( 'the_post', array( tribe( 'tec.views.theme-template-support' ), 'spoof_the_post' ) );
		add_filter( 'the_content', array( $this, 'inject_view' ) );
		add_filter( 'comments_template', array( $this, 'allow_comments' ) );
		remove_action( 'loop_start', array( $this, 'override_loop' ) );
	}

	/**
	 * Loads the contents into the page template
	 *
	 * @return string Page content
	 */
	public function inject_view() {
		remove_filter( 'the_content', array( $this, 'inject_view' ) );
		$this->restore_query();

		ob_start();

		tribe_events_before_html();
		tribe_get_view();
		tribe_events_after_html();

		$contents = ob_get_clean();

		// make sure the loop ends after our template is included
		if ( ! is_404() ) {
			$this->end_query();
		}

		return $contents;
	}

	/**
	 * Allow or strip the comments according to the current showComments setting.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function allow_comments( $template ) {
		remove_filter( 'comments_template', array( $this, 'allow_comments' ) );

		if ( ! is_single() || tribe_is_showing_all() || ( tribe_get_option( 'showComments', false ) === false ) ) {
			return Tribe__Events__Main::instance()->pluginPath . 'src/admin-views/no-comments.php';
		}

		return $template;
	}

	/**
	 * Determine when wp_head has been triggered.
	 */
	public function wp_head_finished() {
		$this->wp_head_complete = true;
	}

	/**
	 * Query is complete: stop the loop from repeating.
	 */
	protected function end_query() {
		global $wp_query;
		$wp_query->current_post = -1;
		$wp_query->post_count   = 0;
	}

	public function get_template() {
		return $this->template;
	}
}