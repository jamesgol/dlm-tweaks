<?php
/**
 * Plugin Name: Download Monitor Tweaks
 * Description: Simple tweaks for Download Monitor
 * Version: 1.00
 * Author: James Golovich
 * Requires at least: 4.0
 * Tested up to: 4.9
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DLM_TWEAKS_VERSION', 0.01 );

function DLM_Tweaks() {
	return DLM_Tweaks::instance();
}

class DLM_Tweaks {
	private static $instance;

	private $fileextension;

	private $shortcodes;
	
	private $modify_permalink;

	public function __construct() {
			add_action('plugins_loaded', array($this, 'setup'));
	}

	public function setup() {
		if ( defined( 'DLM_VERSION' ) ) {

			require_once 'src/FileExtension.php';
			$this->fileextension = new DLM_Tweaks\FileExtension;
			require_once 'src/Shortcodes.php';
			$this->shortcodes = new DLM_Tweaks\Shortcodes;
			require_once 'src/ModifyPermalink.php';
			$this->modify_permalink = new DLM_Tweaks\ModifyPermalink;
		}
	}

	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
}
