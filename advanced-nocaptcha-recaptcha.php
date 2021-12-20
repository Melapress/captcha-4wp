<?php
/*
Plugin Name: Captcha 4WP
Plugin URI: https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/
Description: Show noCaptcha or invisible captcha in Comment Form, bbPress, BuddyPress, WooCommerce, CF7, Login, Register, Lost Password, Reset Password. Also can implement in any other form easily.
Version: 6.1.7
Author: WP White Security
Author URI: https://www.wpwhitesecurity.com/
Text Domain: advanced-nocaptcha-recaptcha
License: GPLv2 or later
WC tested up to: 5.6.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once ABSPATH . '/wp-admin/includes/plugin.php';

class C4WP {

	private static $instance;

	private function __construct() {
		if ( function_exists( 'anr_get_option' ) ) {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			deactivate_plugins( 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php' );
			return;
		}
		$this->constants();
		$this->includes();
		$this->actions();
		// $this->filters();
	}

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function constants() {
		define( 'ANR_PLUGIN_VERSION', '6.1.7' );
		define( 'ANR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'ANR_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		define( 'ANR_PLUGIN_FILE', __FILE__ );
	}

	private function includes() {
		require_once ANR_PLUGIN_DIR . 'functions.php';
	}

	private function actions() {
		add_action( 'after_setup_theme', 'anr_include_require_files' );
		add_action( 'init', 'anr_translation' );
		add_action( 'login_enqueue_scripts', 'anr_login_enqueue_scripts' );
	}
} //END Class

ANR::init();