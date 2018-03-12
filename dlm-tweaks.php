<?php
/**
 * Plugin Name: Download Monitor Tweaks
 * Description: Simple tweaks for Download Monitor
 * Version: 0.01
 * Author: James Golovich
 * Requires at least: 4.0
 * Tested up to: 4.9
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DLM_TWEAKS_VERSION', 0.01 );

require_once 'vendor/autoload.php';

DLM_Tweaks();

function DLM_Tweaks() {
	return DLM_Tweaks::instance();
}

class DLM_Tweaks {
	private static $instance;

	private $fileextension;

	private $shortcodes;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'setup' ) );
	}

	public function setup() {
		$this->fileextension = new DLM_Tweaks\FileExtension;
		$this->shortcodes = new DLM_Tweaks\Shortcodes;
	}

	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new Self;
		}
		return self::$instance;
	}
}