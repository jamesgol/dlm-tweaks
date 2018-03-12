<?php

namespace DLM_Tweaks;

/**
 * Force DownloadMonitor WordPress plugin to handle tags + exclude_tags attributes the same was as Category is
 * Duplicates behavior of https://github.com/download-monitor/download-monitor/pull/488
 * without requiring patching the code.  This will be unnecessary if the PR gets committed
 */
class Shortcodes {

	private $tag = null;

	private $exclude_tag = null;

	private $post_name = null;

	public function __construct() {
		add_filter( 'pre_do_shortcode_tag', array( $this, 'catch_attrs' ), 10, 4 );
	}

	public function catch_attrs( $bool, $tag, $attr, $m ) {
		if ( 'downloads' === $tag ) {
			$add_tag_filter = false;
			if ( isset( $attr['tag'] ) ) {
				$this->tag = $attr['tag'];
				$add_tag_filter = true;
			}

			if ( isset( $attr['exclude_tag'] ) ) {
				$this->exclude_tag = $attr['exclude_tag'];
				$add_tag_filter = true;
			}
			if ( $add_tag_filter ) {
				add_filter( 'dlm_shortcode_downloads_args', array( $this, 'tag_filters' ) );
			}

		} elseif ( 'download' === $tag ) {
			// This can get removed if https://github.com/download-monitor/download-monitor/pull/486/files is ever committed
			if ( isset( $attr['name'] ) ) {
				$this->post_name = $attr['name'];
				add_filter( 'dlm_shortcode_download_id', array( $this, 'download_by_name' ) );
			}
		}

		return $bool;
	}

	public function download_by_name( $id ) {
		if ( empty( $id ) && null !== $this->post_name ) {
			$posts = get_posts( array( 'name' => $this->post_name, 'post_type' => 'dlm_download', 'numberposts' => 1 ) );
			if ( !empty( $posts ) ) {
				$id = $posts[0]->ID;
			}
		}
		remove_filter( 'dlm_shortcode_download_id', array( $this, 'download_by_name' ) );
		$this->post_name = null;

		return $id;
	}

	public function tag_filters( $args ) {
		// First unset any tags in the existing query
		if ( isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) {
			foreach ( $args['tax_query'] as $key => $value ) {
				if ( isset( $value['taxonomy'] ) && 'dlm_download_tag' === $value['taxonomy'] ) {
					unset( $args['tax_query'][ $key ] );
				}
			}
		}


		if ( null !== $this->tag ) {
			$args['tax_query'] = array_merge( $args['tax_query'], $this->format_tags( 'dlm_download_tag', $this->tag ) );
		}

		if ( null !== $this->exclude_tag ) {
			$args['tax_query'] = array_merge( $args['tax_query'], $this->format_tags( 'dlm_download_tag', $this->exclude_tag ) );
		}

		// Return to a clean slate
		$this->exclude_tag = null;
		$this->tag = null;
		remove_filter( 'dlm_shortcode_downloads_args', array( $this, 'tag_filters' ) );

		return $args;
	}

	/**
	 * Format taxonomy filter for query
	 *
	 * @param string $tax Taxonomy name to be used
	 * @param string $terms Comma or plus delimited list of terms
	 * @param array $args Arguments to be appended to each query
	 *
	 * @return array
	 */
	public function format_tags( $tax, $terms, $args = array() ) {
		$tax_query = array();

		if ( preg_match( '/\+/', $terms ) ) {

			// Taxonomy with AND

			// string to array
			$terms = array_filter( explode( '+', $terms ) );

			// check if explode had results
			if ( ! empty( $terms ) ) {

				foreach ( $terms as $term ) {
					$tax_query[] = array_merge( array(
						'taxonomy'         => $tax,
						'field'            => 'slug',
						'terms'            => $term,
					), $args );
				}
			}

		} else {

			// Taxonomy with OR

			// string to array
			$terms = array_filter( explode( ',', $terms ) );

			// check if explode had results
			if ( ! empty( $terms ) ) {

				$tax_query[] = array_merge( array(
					'taxonomy'         => $tax,
					'field'            => 'slug',
					'terms'            => $terms,
				), $args );
			}
		}
		return $tax_query;
	}
}