<?php
class Tribe__Events__Views__List_View extends Tribe__Events__Views__Base_View {
	/**
	 * List view ajax handler
	 *
	 * @todo this method is disused and Tribe__Events__Views__Loader::ajax_response() should ultimately replace it
	 *
	 */
	public function ajax_response() {

		Tribe__Events__Query::init();

		$tribe_paged = ( ! empty( $_POST['tribe_paged'] ) ) ? intval( $_POST['tribe_paged'] ) : 1;
		$post_status = array( 'publish' );
		if ( is_user_logged_in() ) {
			$post_status[] = 'private';
		}

		$args = array(
			'eventDisplay' => 'list',
			'post_type'    => Tribe__Events__Main::POSTTYPE,
			'post_status'  => $post_status,
			'paged'        => $tribe_paged,
		);

		// check & set display
		if ( isset( $_POST['tribe_event_display'] ) ) {
			if ( $_POST['tribe_event_display'] == 'past' ) {
				$args['eventDisplay'] = 'past';
				$args['order'] = 'DESC';
			} elseif ( 'all' == $_POST['tribe_event_display'] ) {
				$args['eventDisplay'] = 'all';
			}
		}

		// check & set event category
		if ( isset( $_POST['tribe_event_category'] ) ) {
			$args[ Tribe__Events__Main::TAXONOMY ] = $_POST['tribe_event_category'];
		}

		$args = apply_filters( 'tribe_events_listview_ajax_get_event_args', $args, $_POST );

		$query = tribe_get_events( $args, true );

		// $hash is used to detect whether the primary arguments in the query have changed (i.e. due to a filter bar request)
		// if they have, we want to go back to page 1
		$hash = $query->query_vars;

		$hash['paged']      = null;
		$hash['start_date'] = null;
		$hash['end_date']   = null;
		$hash_str           = md5( maybe_serialize( $hash ) );

		if ( ! empty( $_POST['hash'] ) && $hash_str !== $_POST['hash'] ) {
			$tribe_paged   = 1;
			$args['paged'] = 1;
			$query         = Tribe__Events__Query::getEvents( $args, true );
		}


		$response = array(
			'html'        => '',
			'success'     => true,
			'max_pages'   => $query->max_num_pages,
			'hash'        => $hash_str,
			'tribe_paged' => $tribe_paged,
			'total_count' => $query->found_posts,
			'view'        => 'list',
		);

		global $wp_query, $post, $paged;
		$wp_query = $query;
		if ( ! empty( $query->posts ) ) {
			$post = $query->posts[0];
		}

		$paged = $tribe_paged;

		Tribe__Events__Main::instance()->displaying = apply_filters( 'tribe_events_listview_ajax_event_display', 'list', $args );

		if ( ! empty( $_POST['tribe_event_display'] ) && $_POST['tribe_event_display'] == 'past' ){
			$response['view'] = 'past';
		}

		ob_start();
		tribe_get_view( 'list/content' );
		$response['html'] .= ob_get_clean();

		apply_filters( 'tribe_events_ajax_response', $response );

		header( 'Content-type: application/json' );
		echo json_encode( $response );

		tribe_exit();
	}
}