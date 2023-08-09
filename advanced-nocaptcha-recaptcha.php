<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

/**
 * CAPTCHA 4WP
 *
 * @copyright Copyright (C) 2013-2023, Melapress - support@melapress.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: CAPTCHA 4WP
 * Version:     7.3.0
 * Plugin URI:  https://melapress.com/wordpress-plugins/captcha-plugin-wordpress/
 * Description: Easily add any type of CAPTCHA (such as noCaptcha or invisible Captcha) on any website form, including login pages, comments and password reset forms, and also forms by third party plugins such as Contact Form 7, WooCommerce & BuddyPress.
 * Author:      Melapress
 * Author URI:  https://melapress.com/
 * Text Domain: advanced-nocaptcha-recaptcha
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.0
 * WC tested up to: 6.3.0
 * Requires PHP: 7.2
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
	 */
	private static $instance;

	/**
	 * Class constructor.
	 */
	private function __construct() {


		if ( is_plugin_active( 'advanced-nocaptcha-and-invisible-captcha-pro/advanced-nocaptcha-and-invisible-captcha-pro.php' ) ) {
			deactivate_plugins( 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php' );
			return;
		}

		$this->constants();
		$this->includes();
		$this->actions();
	}

	/**
	 * Class initiator.
	 *
	 * @return $instance - C4WP instance.
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
	 */
	private function constants() {
		define( 'C4WP_PLUGIN_VERSION', '7.3.0' );
		define( 'C4WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'C4WP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		define( 'C4WP_PLUGIN_FILE', __FILE__ );
		define( 'C4WP_TABLE_PREFIX', 'c4wp_' );
		register_uninstall_hook( C4WP_PLUGIN_FILE, 'c4wp_uninstall' );
	}

	/**
	 * Include functions and pro extensions.
	 *
	 * @return void
	 */
	private function includes() {

		if ( file_exists( C4WP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once C4WP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		add_action( 'wp_loaded', array( 'C4WP_Settings', 'actions_filters' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Captcha_Class', 'actions_filters' ), -9 );


		add_action( 'init', array( 'C4WP\\Methods\\C4WP_Method_Loader', 'init' ), 0 );

		
	}

	/**
	 * Add plugin actions.
	 *
	 * @return void
	 */
	private function actions() {
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'c4wp_translation' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'actions' ) );
		add_action( 'init', array( 'C4WP\\C4WP_Functions', 'c4wp_plugin_update' ), -15 );
		add_action( 'login_enqueue_scripts', array( 'C4WP\\C4WP_Functions', 'c4wp_login_enqueue_scripts' ) );

	}
}
// END Class.

	// ... Your plugin's main file logic ...
	add_action( 'plugins_loaded', array( 'C4WP', 'init' ) );

register_activation_hook( __FILE__, 'c4wp_redirect_after_activation' );

/**
 * Setup admin for redirection upon activation.
 *
 * @return void
 */
function c4wp_redirect_after_activation() {
	add_option( 'c4wp_redirect_after_activation', true );
}

add_action( 'admin_init', 'c4wp_activation_redirect' );

/**
 * Redirect users to the plugins settings page upon activation.
 *
 * @return void
 */
function c4wp_activation_redirect() {
	if ( is_admin() && get_option( 'c4wp_redirect_after_activation', false ) ) {
		delete_option( 'c4wp_redirect_after_activation' );
		$admin_url = ( function_exists( 'c4wp_same_settings_for_all_sites' ) || ! function_exists( 'c4wp_same_settings_for_all_sites' ) && is_multisite() ) ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
		exit( wp_safe_redirect( esc_url( $admin_url ) ) ); // phpcs:ignore
	}
}

/**
 * Declare compatibility with WC HPOS.
 *
 * @return void
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Uninstall the plugin
 *
 * @return void
 */
if ( ! function_exists( 'c4wp_uninstall' ) ) {

	function c4wp_uninstall() {

		$get_site_options = is_multisite();
		if ( $get_site_options ) {
			$options = get_site_option( 'c4wp_admin_options' );
		} else {
			$options = get_option( 'c4wp_admin_options' );
		}
	
		if ( isset( $options['delete_data_enable'] ) && $options['delete_data_enable'] ) {
			if ( $get_site_options ) {
				$network_id = get_current_network_id();
				global $wpdb;
				$wpdb->query(
					$wpdb->prepare(
						"
						DELETE FROM $wpdb->sitemeta
						WHERE meta_key LIKE %s
						AND site_id = %d
						",
						array(
							'%c4wp%',
							$network_id,
						)
					)
				);
			} else {
				global $wpdb;
				$wpdb->query(
					$wpdb->prepare(
						"
						DELETE FROM $wpdb->options
						WHERE option_name LIKE %s
						",
						array(
							'%c4wp%',
						)
					)
				);
			}
	
			// Remove wsal specific Freemius entry.
			delete_site_option( 'fs_c4wpp' );
			
			$table_name = $wpdb->prefix . 'c4wp_failed_login_tracking';
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore
		}
		
	}
}