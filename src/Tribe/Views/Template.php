<?php
class Tribe__Events__Views__Template {
	protected $path = '';
	protected $requested_template = '';
	protected $args = array();

	public static function locate( $template, array $args = array() ) {
		$template = new self( $template, $args );
		return $template->get_path();
	}

	public static function display( $template, array $args = array() ) {
		$template = new self( $template, $args );
		$template->render();
	}

	public function __construct( $template, array $args = array() ) {
		$this->requested_template = $template;
		$this->args = $args;
		$this->locate_template();
	}

	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme,
	 * 2) parent template, 3) plugin resources. will look in the events/
	 * directory in a theme and the views/ directory in the plugin
	 * folder.
	 */
	public function locate_template() {
		$template    = $this->requested_template;
		$plugin_path = isset( $this->args['plugin_path'] ) ? $this->args['plugin_path'] : '';

		// Append .php to file name if required
		if ( substr( $template, - 4 ) != '.php' ) {
			$template .= '.php';
		}

		/**
		 * Sets the template base path.
		 *
		 * @param array $plugin_path
		 */
		$template_base_paths = (array) apply_filters( 'tribe_events_template_paths', (array) Tribe__Events__Main::instance()->pluginPath );

		// Backwards compatibility (if $plugin_path arg is used)
		if ( $plugin_path && ! in_array( $plugin_path, $template_base_paths ) ) {
			array_unshift( $template_base_paths, $plugin_path );
		}

		$file = false;

		/* potential scenarios:

		- the user has no template overrides
			-> we can just look in our plugin dirs, for the specific path requested, don't need to worry about the namespace
		- the user created template overrides without the namespace, which reference non-overrides without the namespace and, their own other overrides without the namespace
			-> we need to look in their theme for the specific path requested
			-> if not found, we need to look in our plugin views for the file by adding the namespace
		- the user has template overrides using the namespace
			-> we should look in the theme dir, then the plugin dir for the specific path requested, don't need to worry about the namespace

		*/

		// check if there are overrides at all
		if ( locate_template( array( 'tribe-events/' ) ) ) {
			$overrides_exist = true;
		} else {
			$overrides_exist = false;
		}

		if ( $overrides_exist ) {
			// check the theme for specific file requested
			$file = locate_template( array( 'tribe-events/' . $template ), false, false );
			if ( ! $file ) {
				// if not found, it could be our plugin requesting the file with the namespace,
				// so check the theme for the path without the namespace
				$files = array();
				foreach ( array_keys( $template_base_paths ) as $namespace ) {
					if ( ! empty( $namespace ) && ! is_numeric( $namespace ) ) {
						$files[] = 'tribe-events' . str_replace( $namespace, '', $template );
					}
				}
				$file = locate_template( $files, false, false );
				if ( $file ) {
					_deprecated_function( sprintf( esc_html__( 'Template overrides should be moved to the correct subdirectory: %s', 'the-events-calendar' ), str_replace( get_stylesheet_directory() . '/tribe-events/', '', $file ) ), '3.2', $template );
				}
			} else {
				$file = apply_filters( 'tribe_events_template', $file, $template );
			}
		}

		// if the theme file wasn't found, check our plugins views dirs
		if ( ! $file ) {

			foreach ( $template_base_paths as $template_base_path ) {

				// make sure directories are trailingslashed
				$template_base_path = ! empty( $template_base_path ) ? trailingslashit( $template_base_path ) : $template_base_path;

				$file = $template_base_path . 'src/views/' . $template;

				$file = apply_filters( 'tribe_events_template', $file, $template );

				// return the first one found
				if ( file_exists( $file ) ) {
					break;
				} else {
					$file = false;
				}
			}
		}

		// file wasn't found anywhere in the theme or in our plugin at the specifically requested path,
		// and there are overrides, so look in our plugin for the file with the namespace added
		// since it might be an old override requesting the file without the namespace
		if ( ! $file && $overrides_exist ) {
			foreach ( $template_base_paths as $_namespace => $template_base_path ) {

				// make sure directories are trailingslashed
				$template_base_path = ! empty( $template_base_path ) ? trailingslashit( $template_base_path ) : $template_base_path;
				$_namespace         = ! empty( $_namespace ) ? trailingslashit( $_namespace ) : $_namespace;

				$file = $template_base_path . 'src/views/' . $_namespace . $template;

				$file = apply_filters( 'tribe_events_template', $file, $template );

				// return the first one found
				if ( file_exists( $file ) ) {
					_deprecated_function( sprintf( esc_html__( 'Template overrides should be moved to the correct subdirectory: tribe_get_template_part(\'%s\')', 'the-events-calendar' ), $template ), '3.2', 'tribe_get_template_part(\'' . $_namespace . $template . '\')' );
					break;
				}
			}
		}

		$this->path = apply_filters( 'tribe_events_template_' . $template, $file );
	}

	public function get_path() {
		return $this->path;
	}

	public function render() {
		include $this->path;
	}
}