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
		add_filter( 'dlm_download_get_the_download_link', array( $this, 'download_link' ), 10, 2 );
		add_filter( 'dlm_download_get_versions', array( $this, 'get_versions' ), 10, 2 );
		add_filter( 'dlm_version_require_exact', '__return_true' );
	}

	public function get_versions( $versions, $download ) {
		if ( isset( $_GET, $_GET['version'] ) ) {
			// If the user is requesting a specific version, only focus on those
			foreach ( $versions as $id => $version ) {
				if ( $version->get_version() !== $_GET['version'] ) {
	//				unset( $versions[ $id ] );
				}
			}

		}
		return $versions;
	}

	public function default_extension() {
		return apply_filters( 'dlm_tweaks_default_extension', 'pdf' );
	}

	public function extension_preference() {
		$preference = array( 'pdf', 'docx','doc' );
		return apply_filter( 'dlm_tweaks_extension_preference', $preference );
	}

	private function ext_sort( $a, $b ) {
		// If either is a preferred extension, sort that way
//		if ( in_array( $a, $this->extension_preference() ) )

		// Otherwise alphabetical
		return strcasecmp( $a, $b );
	}

	public function download_link( $link, $version ) {
		//return apply_filters( 'dlm_download_get_the_download_link', esc_url_raw( $link ), $this, $this->get_version() );
		$slug = $version->get_slug();
		$ext = $version->get_version()->get_filetype();
		$link = str_replace( "/$slug/", "/$slug.$ext/", $link );
		return $link;
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
				$wp->query_vars[ $this->endpoint . '_type' ] = $this->default_extension();
			}
		}

		// Get ID of download
		$raw_id = sanitize_title( stripslashes( $wp->query_vars[ $this->endpoint ] ) );

		if ( empty( $raw_id ) ) {
			return;
		}

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

				$best = $this->find_best_version( $download );

				if ( $best ) {
					$_GET[ 'v' ] = $best->get_id();
				}

			}catch (\Exception $e) {
				// download not found
			}

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
					if ( isset( $_GET, $_GET['version'] ) ) {
						if ( $version_number === $_GET['version'] ) {
							// If this is the version number requested, quickly return it
							return $version;
						}
					}
					elseif ( null === $best_version_number || version_compare( $version_number, $best_version_number ) ) {
						$best_version_number = $version_number;
						$best_version = $version;
					}
				}
			}
		}
		return $best_version;
	}


}