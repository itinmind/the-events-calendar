<?php
class Tribe__Events__RSS {
	public function hook() {
		add_filter( 'get_post_time', array( $this, 'event_date_to_pub_date' ), 10, 3 );
	}

	/**
	 * Convert the post_date_gmt to the event date for feeds
	 *
	 * @todo consider moving into a class dedicated to RSS feed integration
	 *
	 * @param string $time the post_date
	 * @param string $d    the date format to return
	 * @param bool   $gmt  whether this is a gmt time
	 *
	 * @return int|string
	 */
	public function event_date_to_pub_date( $time, $d, $gmt ) {
		global $post;

		if ( is_object( $post ) && $post->post_type == Tribe__Events__Main::POSTTYPE && is_feed() && $gmt ) {

			//WordPress always outputs a pubDate set to 00:00 (UTC) so account for that when returning the Event Start Date and Time
			$zone = get_option( 'timezone_string', false );

			if ( $zone ) {
				$zone = new DateTimeZone( $zone );
			} else {
				$zone = new DateTimeZone( 'UTC' );
			}

			$time = new DateTime( tribe_get_start_date( $post->ID, false, $d ), $zone );
			$time->setTimezone( new DateTimeZone( 'UTC' ) );
			$time = $time->format( $d );
		}

		return $time;
	}
}