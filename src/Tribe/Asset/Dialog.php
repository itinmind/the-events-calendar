<?php


	class Tribe__Events__Asset__Dialog extends Tribe__Events__Asset__Abstract_Asset {

		public function handle() {
			wp_enqueue_script( 'jquery-ui-dialog' );
			Tribe__Events__Views__Base_View::add_vendor_script( 'jquery-ui-dialog' );
		}
	}
