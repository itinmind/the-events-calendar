<?php
class Tribe__Events__Views__Body_Classes {
	public function hook() {
		add_filter( 'body_class', array( $this, 'theme_class' ) );
		add_filter( 'body_class', array( $this, 'template_class' ) );
		add_filter( 'body_class', array( $this, 'live_filter_class' ) );
		add_filter( 'body_class', array( $this, 'view_classes' ) );
		add_filter( 'tribe_events_views_template_selection', array( $this, 'singular_class_fix' ) );
	}

	/**
	 * Add a CSS class representing the theme to the <body> element.
	 *
	 * @param array $classes
	 *
	 * @return array $classes
	 */
	public function theme_class( $classes ) {
		$child_theme  = get_option( 'stylesheet' );
		$parent_theme = get_option( 'template' );

		// if the 2 options are the same, then there is no child theme
		if ( $child_theme == $parent_theme ) {
			$child_theme = false;
		}

		if ( $child_theme ) {
			$theme_classes = "tribe-theme-parent-$parent_theme tribe-theme-child-$child_theme";
		} else {
			$theme_classes = "tribe-theme-$parent_theme";
		}

		$classes[] = $theme_classes;

		return $classes;
	}

	/**
	 * Add a CSS class representing the current events template to the <body> element.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function template_class( $classes ) {
		$template_filename = basename( Tribe__Events__Templates::get_current_template() );

		if ( $template_filename == 'default-template.php' ) {
			$classes[] = 'tribe-events-page-template';
		} else {
			$classes[] = 'page-template-' . sanitize_title( $template_filename );
		}

		return $classes;
	}

	/**
	 * Add a CSS class indicating the live filter updates are enabled to the <body> element.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function live_filter_class( $classes ) {
		if (
			is_admin()
			|| ! tribe_get_option( 'liveFiltersUpdate', true )
			|| get_query_var( 'post_type' ) !== Tribe__Events__Main::POSTTYPE
		) {
			return $classes;
		}

		$classes[] = 'tribe-filter-live';
		return $classes;
	}

	/**
	 * Applies a fix relating to the singular body class.
	 *
	 * This was originally implemented from within Tribe__Events__Templates and
	 * was a fix for a historic formatting issue.
	 *
	 * @see https://central.tri.be/issues/15461
	 *
	 * @param string $selected_template
	 *
	 * @return string
	 */
	public function singular_class_fix( $selected_template ) {
		if ( tribe_get_option( 'tribeEventsTemplate', 'default' ) == '' ) {
			return $selected_template;
		}

		// remove singular body class if sidebar-page.php
		if ( $selected_template === get_stylesheet_directory() . '/sidebar-page.php' ) {
			add_filter( 'body_class', array( $this, 'remove_singular_class' ) );
		} else {
			add_filter( 'body_class', array( $this, 'add_singular_class' ) );
		}

		return $selected_template;
	}

	/**
	 * Add the "singular" body class
	 *
	 * @param array $classes List of classes to filter
	 *
	 * @return array
	 */
	public function add_singular_class( $classes ) {
		$classes[] = 'singular';
		return $classes;
	}

	/**
	 * Remove "singular" from available body class
	 *
	 * @param array $classes List of classes to filter
	 *
	 * @return mixed
	 */
	public function remove_singular_class( $classes ) {
		$key = array_search( 'singular', $classes );

		if ( $key ) {
			unset( $classes[ $key ] );
		}

		return $classes;
	}

	public function view_classes( $classes ) {
		/**
		 * @var Tribe__Events__Views__Base_View $view
		 */
		if ( ! $view = tribe( 'tec.views.loader' )->get_selected_view() ) {
			return $classes;
		}

		// View-specific class
		$classes[] = $view->get_property( 'body_class' );

		// Apply the appropriate category class if needed
		if ( is_tax( Tribe__Events__Main::TAXONOMY ) ) {
			$classes[] = 'events-category';
			$category  = get_term_by( 'name', single_cat_title( '', false ), Tribe__Events__Main::TAXONOMY );
			$classes[] = 'events-category-' . $category->slug;
		}

		// Archive class
		if ( ! is_single() || tribe_is_showing_all() ) {
			$single_id = array_search( 'single-tribe_events', $classes );
			if ( ! empty( $single_id ) ) {
				$classes[ $single_id ] = 'events-list';
			}
			$classes[] = 'events-archive';
		}

		// Add selected style to body class for add-on styling
		$style_option = tribe_get_option( 'stylesheetOption', 'tribe' );

		switch ( $style_option ) {
			case 'skeleton':
				$classes[] = 'tribe-events-style-skeleton'; // Skeleton styles
				break;
			case 'full':
				$classes[] = 'tribe-events-style-full'; // Full styles
				break;
			default: // tribe styles is the default so add full and theme (tribe)
				$classes[] = 'tribe-events-style-full';
				$classes[] = 'tribe-events-style-theme';
				break;
		}

		return $classes;
	}
}