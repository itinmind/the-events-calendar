<?php

/**
 * Generates special-case rewrite rules for some of the core views that
 * ship with The Events Calendar.
 *
 * Examples:
 *
 *     /events/2018-04/       # shorter alternative to /events/month/2018-04/
 *     /events/2018-07-01/    # shorter alternative to /events/day/2018-07-01/
 */
class Tribe__Events__Views__Extra_Rewrite_Rules {
	public function __construct() {
		add_action( 'tribe_events_rewrite_rules_day', array( $this, 'day_view' ) );
		add_action( 'tribe_events_rewrite_rules_month', array( $this, 'month_view' ) );
	}

	public function day_view( Tribe__Events__Rewrite $rewrite ) {
		$rewrite->generate_rules( array(
			array(
				array( '{{ today }}' ),
				array( 'eventDisplay' => 'day' ),
			),
			array(
				array( '{{ today }}', '{{ featured }}' ),
				array( 'eventDisplay' => 'day', 'featured' => true ),
			),
			array(
				array( '(\d{4}-\d{2}-\d{2})' ),
				array( 'eventDisplay' => 'day', 'eventDate' => '%1' ),
			),
			array(
				array( '(\d{4}-\d{2}-\d{2})', '{{ featured }}' ),
				array( 'eventDisplay' => 'day', 'eventDate' => '%1', 'featured' => true ),
			),
		) );
	}

	public function month_view( Tribe__Events__Rewrite $rewrite ) {
		$rewrite->generate_rules( array(
			array(
				array( '(\d{4}-\d{2})' ),
				array( 'eventDisplay' => 'month', 'eventDate' => '%1' )
			),
			array(
				array( '(\d{4}-\d{2})', '{{ featured }}' ),
				array( 'eventDisplay' => 'month', 'eventDate' => '%1', 'featured' => true )
			),
		) );
	}
}