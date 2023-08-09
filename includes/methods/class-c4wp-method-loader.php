<?php
/**
 * Handling loading and basic tasks for working with each CAPTCHA method.
 *
 * @package C4WP
 */

namespace C4WP\Methods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions as C4WP_Functions;

if ( ! class_exists( 'C4WP_Method_Loader' ) ) {

	/**
	 * Main class.
	 */
	class C4WP_Method_Loader {

        /**
         * A neat array of each method and its class.
         */
        public static $methods = array(
			'captcha' => 'C4WP\\Methods\\Captcha',
		);

        /**
         * Init applicable classes.
         */
		public static function init() {
            // Silence, as further premium methods are initiated via the extension loader.
            self::$methods = apply_filters( 'c4wp_available_methods', self::$methods );
        }

        /**
         * Get the form field for the desirect method.
         *
         * @param string $current_method
         * @param integer $current_number_of_captchas
         * @return void
         */
        public static function get_form_field( $current_method = 'captcha', $current_number_of_captchas = 0 ) {
            if ( isset( self::$methods[$current_method] ) && method_exists( self::$methods[$current_method], 'form_field' ) ) {
                return self::$methods[$current_method]::form_field( $current_number_of_captchas );
            }

            return false;
        }

        /**
         * Get footer scripts for desired method.
         *
         * @param string $current_method
         * @return void
         */
        public static function get_footer_scripts( $current_method = 'captcha' ) {
            if ( isset( self::$methods[$current_method] ) && method_exists( self::$methods[$current_method], 'footer_scripts' ) ) {
                return self::$methods[$current_method]::footer_scripts();
            }

            return false;
        }

        /**
         * Get currenty selected method.
         *
         * @param boolean $return_alias
         * @param boolean $return_class
         * @return void
         */
        public static function get_currently_selected_method( $return_alias = true, $return_class = false ) {
            $active_method   = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
            $captcha_methods = array(
                'v2_checkbox',
                'v2_invisible',
                'v3'
            );

            if ( in_array( $active_method, $captcha_methods, true ) ) {
                return ( $return_class ) ? self::$methods['captcha'] : 'captcha';
            } else {
                return ( isset( self::$methods[$active_method] ) && $return_class ) ? self::$methods[$active_method] : $active_method;
            }
        }

        /**
         * Verify using method specific logic.
         *
         * @param string $current_method
         * @param boolean $response
         * @return void
         */
        public static function method_verify( $current_method = 'captcha', $response = false ) {
            if ( empty( $current_method ) ) {
                return true;
            }

            if ( method_exists( self::$methods[$current_method], 'verify' ) ) {
                return self::$methods[$current_method]::verify( $response );
            } 

            return false;
        }

        /**
         * Simple checker to see if the currently active method is actually present and available right now.
         *
         * @return boolean
         */
        public static function is_active_method_available() {
            $active_method   = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
            $captcha_methods = array(
                'v2_checkbox',
                'v2_invisible',
                'v3'
            );

            if ( in_array( $active_method, $captcha_methods, true ) ) {
                $active_method = 'captcha';
            }
            
            if ( ! isset( self::$methods[$active_method] ) ) {
                return false;
            }
            
            return true;
        }

        /**
         * Admin notice to alert that the currently active method is not available, and they should update.
         *
         * @return void
         */
        public static function method_unavailable_notice() {
            $admin_url = ( function_exists( 'c4wp_same_settings_for_all_sites' ) || ! function_exists( 'c4wp_same_settings_for_all_sites' ) && is_multisite() ) ? network_admin_url( 'admin.php?page=c4wp-admin-captcha-account' ) : admin_url( 'admin.php?page=c4wp-admin-captcha-account' );
            $markup = '<div id="captcha_keys_notice" class="notice notice-info"><p>';
            $markup .= sprintf(
                    'The active CAPTCHA method is not currently available for your license. Please update your settings accordingly or activate a %1$s license to continue protecting your websites forms. %2$s',
                    '<strong>' . esc_html__( 'Business or Enterprise', 'advanced-nocaptcha-recaptcha' ) . '</strong>',
                    '</br></br><a href="' . esc_url(  $admin_url ) . '" class="button button-primary">' . esc_html__( 'Update my license', 'advanced-nocaptcha-recaptcha' ) . '</a>',
                    '<a href="#" class="button button-secondary">' . esc_html__( 'Close and update settings', 'advanced-nocaptcha-recaptcha' ) . '</a>'
                );
            $markup .= '</p></div>';

            echo $markup;
        }
    }
}