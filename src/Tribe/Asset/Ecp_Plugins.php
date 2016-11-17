<?php


class Tribe__Events__Asset__Ecp_Plugins extends Tribe__Events__Asset__Abstract_Asset {

	public function handle() {
		$deps = array_merge( $this->deps, array( 'jquery' ) );
		$path = Tribe__Events__Views__Base_View::getMinFile( tribe_events_resource_url( 'jquery-ecp-plugins.js' ), true );
		wp_enqueue_script( $this->prefix . '-ecp-plugins', $path, $deps, $this->filter_js_version() );
	}
}
