<?php
/**
 * Handling loading and basic tasks for working with each CAPTCHA method.
 *
 * @package C4WP
 * @since 7.6.0
 */

declare(strict_types=1);

namespace C4WP\Methods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions;

if ( ! class_exists( '\C4WP\Methods\C4WP_Method_Loader' ) ) {

	/**
	 * Main class.
	 *
	 * @since 7.6.0
	 */
	class C4WP_Method_Loader {

		/**
		 * A neat array of each method and its class.
		 *
		 * @var array - Array of methods.
		 *
		 * @since 7.6.0
		 */
		public static $methods = array(
			'captcha' => 'C4WP\\Methods\\Captcha',
		);

		/**
		 * Init applicable classes.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function init() {
			// Silence, as further premium methods are initiated via the extension loader.
			self::$methods = apply_filters( 'c4wp_available_methods', self::$methods );
		}

		/**
		 * Get the form field for the desired method.
		 *
		 * @param string  $current_method - Method in use.
		 * @param integer $current_number_of_captchas - Current captcha count.
		 *
		 * @return string|bool - Return field markup or nothing.
		 *
		 * @since 7.6.0
		 */
		public static function get_form_field( $current_method = 'captcha', $current_number_of_captchas = 0 ) {
			if ( isset( self::$methods[ $current_method ] ) && method_exists( self::$methods[ $current_method ], 'form_field' ) ) {
				return self::$methods[ $current_method ]::form_field( $current_number_of_captchas );
			}

			return false;
		}

		/**
		 * Get footer scripts for desired method.
		 *
		 * @param string $current_method - Method in use.
		 *
		 * @return string|bool - Return field markup or nothing.
		 *
		 * @since 7.6.0
		 */
		public static function get_footer_scripts( $current_method = 'captcha' ) {
			if ( isset( self::$methods[ $current_method ] ) && method_exists( self::$methods[ $current_method ], 'footer_scripts' ) ) {
				return self::$methods[ $current_method ]::footer_scripts();
			}

			return false;
		}

		/**
		 * Get currently selected method.
		 *
		 * @param boolean $return_alias - Return alias.
		 * @param boolean $return_class - Return class.
		 *
		 * @return string|bool - Return field markup or nothing.
		 *
		 * @since 7.6.0
		 */
		public static function get_currently_selected_method( $return_alias = true, $return_class = false ) {
			$active_method = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );

			/* @dev:start */
			$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );
			// Testing only.
			$test_method = isset( $_GET['override-captcha-version'] ) && ! empty( $_GET['override-captcha-version'] ) ? trim( sanitize_key( wp_unslash( $_GET['override-captcha-version'] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $dev_mode && $test_method ) {
				$active_method = $test_method;
			}
			/* @dev:end */

			$captcha_methods = array(
				'v2_checkbox',
				'v2_invisible',
				'v3',
			);

			if ( in_array( $active_method, $captcha_methods, true ) ) {
				return ( $return_class ) ? self::$methods['captcha'] : 'captcha';
			} else {
				return ( isset( self::$methods[ $active_method ] ) && $return_class ) ? self::$methods[ $active_method ] : $active_method;
			}
		}

		/**
		 * Verify using method specific logic.
		 *
		 * @param string  $current_method - Method to use.
		 * @param boolean $response - Response to verify.
		 * @param boolean $is_fallback_challenge - Is a fallback challenge.
		 *
		 * @return bool - Did verify or not.
		 *
		 * @since 7.6.0
		 */
		public static function method_verify( $current_method = 'captcha', $response = false, $is_fallback_challenge = false ) {
			if ( empty( $current_method ) ) {
				return true;
			}

			if ( method_exists( self::$methods[ $current_method ], 'verify' ) ) {
				return self::$methods[ $current_method ]::verify( $response, $is_fallback_challenge );
			}

			return false;
		}

		/**
		 * Simple checker to see if the currently active method is actually present and available right now.
		 *
		 * @return boolean
		 *
		 * @since 7.6.0
		 */
		public static function is_active_method_available() {
			$active_method   = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
			$captcha_methods = array(
				'v2_checkbox',
				'v2_invisible',
				'v3',
			);

			if ( in_array( $active_method, $captcha_methods, true ) ) {
				$active_method = 'captcha';
			}

			if ( ! isset( self::$methods[ $active_method ] ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Admin notice to alert that the currently active method is not available, and they should update.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function method_unavailable_notice() {
			$admin_url = add_query_arg( array( 'page' => 'c4wp-admin-captcha-account' ), network_admin_url( 'admin.php' ) );
			$markup    = '<div id="captcha_keys_notice" class="notice notice-info"><p>';
			$markup   .= sprintf(
				'The configured CAPTCHA method is only available in the Premium edition. Please download and activate the Premium plugin if you have a license already, if not, you need to purchase a %1$s.',
				'</br></br><a href="' . esc_url( $admin_url ) . '" class="button button-primary">' . esc_html__( 'Premium license', 'advanced-nocaptcha-recaptcha' ) . '</a>',
			);
			$markup   .= '</p></div>';
			echo wp_kses( $markup, C4WP_Functions::c4wp_allowed_kses_args() );
		}
	}
}
