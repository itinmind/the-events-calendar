<?php
/**
 * Fix issues where themes display the_title() before the main loop starts.
 *
 * With some themes the title of single events can be displayed twice and, more crucially, it may result in the
 * event views such as month view prominently displaying the title of the most recent event post (which may
 * not even be included in the view output).
 *
 * There's no bulletproof solution to this problem, but for affected themes a preventative measure can be turned
 * on by adding the following to wp-config.php:
 *
 *     define( 'TRIBE_MODIFY_GLOBAL_TITLE', true );
 *
 * Note: this reverses the situation in version 3.2, when this behaviour was enabled by default. In response to
 * feedback it will now be disabled by default and will need to be turned on by adding the above line.
 */
class Tribe__Events__Views__Global_Post_Titles {
	/**
	 * If the global post title has to be modified the original is stored here.
	 *
	 * @var bool|string
	 */
	protected $original_post_title = false;

	public function hook() {
		add_action( 'tribe_tec_template_chooser', array( $this, 'maybe_modify_global_post_title' ) );
	}

	/**
	 * Test to see if the modification is needed and set it up if so.
	 *
	 * @see issues #24294, #23260
	 */
	public function maybe_modify_global_post_title() {
		global $post;

		// We will only interfere with event queries, where a post is set and this behaviour is enabled
		if ( ! tribe_is_event_query() || ! defined( 'TRIBE_MODIFY_GLOBAL_TITLE' ) || ! TRIBE_MODIFY_GLOBAL_TITLE ) {
			return;
		}

		if ( ! isset( $post ) || ! $post instanceof WP_Post ) {
			return;
		}

		// Wait until late in the wp_title|document_title_parts hook to actually make a change - this should allow single event titles
		// to be used within the title element itself
		add_filter( 'document_title_parts', array( $this, 'modify_global_post_title' ), 1000 );
		add_filter( 'wp_title', array( $this, 'modify_global_post_title' ), 1000 );
	}

	/**
	 * Actually modifies the global $post object's title property, setting it to an empty string.
	 *
	 * This is expected to be called late on during the wp_title action, but does not in fact alter the string
	 * it is passed.
	 *
	 * @see Tribe__Events__Views__Global_Post_Titles::maybe_modify_global_post_title()
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public function modify_global_post_title( $title = '' ) {
		global $post;

		// Set the title to an empty string (but record the original)
		$this->original_post_title = $post->post_title;
		$post->post_title          = apply_filters( 'tribe_set_global_post_title', '' );

		// Restore as soon as we're ready to display one of our own views
		add_action( 'tribe_pre_get_view', array( $this, 'restore_global_post_title' ) );

		// Now return the title unmodified
		return $title;
	}


	/**
	 * Restores the global $post title if it has previously been modified.
	 *
	 * @see Tribe__Events__Views__Global_Post_Titles::modify_global_post_title().
	 */
	public function restore_global_post_title() {
		global $post;
		$post->post_title = $this->original_post_title;
		remove_action( 'tribe_pre_get_view', array( $this, 'restore_global_post_title' ) );
	}
}