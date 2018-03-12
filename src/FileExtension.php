<?php

namespace DLM_Tweaks;

class FileExtension {

	private $endpoint;

	private $ep_value;

	public function __construct() {
		$this->endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';

		// Download Monitor hooks 'parse_request' at 0, so we need to get in first
		add_action( 'parse_request', array( $this, 'parse_request' ), -1 );
	}

	public function parse_request() {
		global $wp, $wpdb;

		if ( isset( $_GET, $_GET['v'] ) ) {
			// If they are manually specifying a download version ID let it through without manipulation
			return;
		}
		if ( isset( $_GET, $_GET[ $this->endpoint ] ) ) {
			$path_parts = pathinfo( $_GET[ $this->endpoint ] );
			// @TODO Handle _GET passed
		} elseif ( isset( $wp->query_vars, $wp->query_vars[ $this->endpoint ] )  ) {
			$q = $wp->query_vars[ $this->endpoint ];
			$path_parts = pathinfo( $q );
			if ( isset( $path_parts['extension'] ) ) {
				// If an extension is specified
				$wp->query_vars[ $this->endpoint . '_type' ] = $path_parts['extension'];
				$wp->query_vars[ $this->endpoint ] = $path_parts['filename'];
			} else {
				// Set pdf as the default type
				$wp->query_vars[ $this->endpoint . '_type' ] = 'pdf';
			}
		}

		// Get ID of download
		$raw_id = sanitize_title( stripslashes( $wp->query_vars[ $this->endpoint ] ) );

		// Find real ID
		switch ( $this->ep_value ) {
			case 'slug' :
				$download_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download';", $raw_id ) ) );
				break;
			default :
				$download_id = absint( $raw_id );
				break;
		}

		$download = null;

		if ( $download_id > 0 ) {
			try {
				$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
			}catch (\Exception $e) {
				// download not found
			}

		}

		$best = $this->find_best_version( $download );

		if ( $best ) {
			$_GET[ 'v' ] = $best->get_id();
		}

	}

	public function find_best_version( $download ) {
		global $wp;

		$matched_type = array();
		$best_version_number = null;
		$best_version = null;

		// @TODO If exact Version ID requested don't try to second guess the user
		if ( isset( $wp->query_vars[ $this->endpoint . '_type' ] ) ) {
			$type = $wp->query_vars[ $this->endpoint . '_type' ];
			foreach ( $download->get_versions() as $id => $version ) {
				if ( $type === $version->get_filetype() ) {
					$matched_type[ $id ] = $version;
					$version_number = $version->get_version_number();
					if ( null === $best_version_number || version_compare( $version_number, $best_version_number ) ) {
						$best_version_number = $version_number;
						$best_version = $version;
					}
				}
			}
		}
		return $best_version;
	}


}