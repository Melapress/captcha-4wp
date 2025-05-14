<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * CAPTCHA 4WP
 *
 * @copyright Copyright (C) 2013-2025, Melapress - support@captcha4wp.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: CAPTCHA 4WP
 * Version:     7.6.0
 * Plugin URI:  https://captcha4wp.com/
 * Description: Easily add Google reCAPTCHA to WordPress forms. Upgrade to Premium and gain access to additional features, including hCaptcha and CloudFlare Turnstile integration, CAPTCHA one-click form integration with plugins such as WooCommerce, Contact Form 7, and WP Forms, and many other features.
 * Author:      Melapress
 * Author URI:  https://captcha4wp.com/
 * Text Domain: advanced-nocaptcha-recaptcha
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.5
 * WC tested up to: 6.3.0
 * Requires PHP: 7.4
 * Network: true
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';

/**
 * Main C4WP Class.
 */
class C4WP {

	/**
	 * Class instance.
	 *
	 * @var C4WP instance.
	 *
	 * @since 7.0.0
	 */
	private static $instance;

	/**
	 * Class constructor.
	 *
	 * @since 7.0.0
	 */
	private function __construct() {
		$this->constants();
		$this->includes();
		$this->actions();
	}

	/**
	 * Class initiator.
	 *
	 * @return $instance - C4WP instance.
	 *
	 * @since 7.0.0
	 */
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @return void
	 *
	 * @since 7.0.0
	 */
	private function constants() {
		if ( ! defined( 'C4WP_VERSION' ) ) {
			define( 'C4WP_VERSION', '7.6.0' );
		}
		if ( ! defined( 'C4WP_PLUGIN_DIR' ) ) {
			define( 'C4WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'C4WP_PLUGIN_URL' ) ) {
			define( 'C4WP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		}
		if ( ! defined( 'C4WP_PLUGIN_FILE' ) ) {
			define( 'C4WP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'C4WP_TABLE_PREFIX' ) ) {
			define( 'C4WP_TABLE_PREFIX', 'c4wp_' );
		}
		if ( ! defined( 'C4WP_PREFIX' ) ) {
			define( 'C4WP_PREFIX', 'c4wp_' );
		}
		if ( ! defined( 'C4WP_BASE_NAME' ) ) {
			define( 'C4WP_BASE_NAME', plugin_basename( __FILE__ ) );
		}

		register_uninstall_hook( C4WP_PLUGIN_FILE, 'c4wp_uninstall' );
	}

	/**
	 * Include functions and pro extensions.
	 *
	 * @return void
	 *
	 * @since 7.0.0
	 */
	private function includes() {
		if ( file_exists( C4WP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once C4WP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		add_action( 'wp_loaded', array( 'C4WP_Settings', 'actions_filters' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Captcha_Class', 'actions_filters' ), -9 );
		add_action( 'init', array( 'C4WP\\PluginUpdatedNotice', 'init' ) );


		add_action( 'init', array( 'C4WP\\Methods\\C4WP_Method_Loader', 'init' ), 0 );

		// Hide all unrelated to the plugin notices on the plugin admin pages.
		add_action( 'admin_print_scripts', array( 'C4WP\C4WP_Functions', 'hide_unrelated_notices' ) );
	}

	/**
	 * Add plugin actions.
	 *
	 * @return void
	 *
	 * @since 7.0.0
	 */
	private function actions() {
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'c4wp_translation' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'actions' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'c4wp_plugin_update' ), -15 );
		add_action( 'login_enqueue_scripts', array( 'C4WP\\C4WP_Functions', 'c4wp_login_enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . C4WP_BASE_NAME, array( 'C4WP\\C4WP_Functions', 'add_plugin_shortcuts' ), 999, 1 );

	}
}
// END Class.


	add_action( 'plugins_loaded', array( 'C4WP', 'init' ) );


require_once 'includes/include-functions.php';


/* @free:start */
register_activation_hook( __FILE__, 'c4wp_redirect_after_activation' );
register_activation_hook( __FILE__, 'c4wp_free_on_plugin_activation' );
/* @free:end */
