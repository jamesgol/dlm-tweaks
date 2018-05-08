<?php

namespace DLM_Tweaks;

class ModifyPermalink {

	private $endpoint;

	private $ep_value;

	public function __construct() {
		$this->endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';

		add_filter( 'dlm_cpt_dlm_download_args', array( $this, 'download_cpt_args' ), 10, 1 );
	}

	function download_cpt_args( $args ) {
		if ( 'slug' === $this->ep_value ) {
			// Allow modifying slug in editor
			$args['publicly_queryable'] = true;
			$args['public'] = true;
			$args['rewrite'] = array('slug' => $this->endpoint );
		}
		return $args;
	}

}