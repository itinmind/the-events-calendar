<?php


class Tribe__Events__Asset__PHP_Date_Formatter extends Tribe__Events__Asset__Abstract_Asset {

	public function handle() {

		$path = Tribe__Events__Views__Base_View::getMinFile( $this->vendor_url . 'php-date-formatter/js/php-date-formatter.js', true );

		$script_handle = $this->prefix . '-php-date-formatter';
		wp_enqueue_script( $script_handle, $path, 'jquery', '1.3.4' );
		Tribe__Events__Views__Base_View::add_vendor_script( $script_handle );
	}
}