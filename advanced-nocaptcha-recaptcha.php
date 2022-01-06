<?php

/**
 * CAPTCHA 4WP (Premium)
 *
 * @copyright Copyright (C) 2013-2022, WP White Security - support@wpwhitesecurity.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: CAPTCHA 4WP
 * Version:     7.0.0
 * Plugin URI:  https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/
 * Description: Easily add any type of Captcha check (such as noCaptcha or invisible Captcha) on any website form, including login pages, comments and password reset forms, and also forms by third party plugins such as Contact Form 7, WooCommerce & BuddyPress.
 * Author:      WP White Security
 * Author URI:  https://www.wpwhitesecurity.com/
 * Text Domain: advanced-nocaptcha-recaptcha
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.0
 * WC tested up to: 5.6.0
 * Requires PHP: 7.0
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

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';
class C4WP
{
    private static  $instance ;
    private function __construct()
    {
        
        if ( is_plugin_active( 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php' ) ) {
            deactivate_plugins( 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php' );
            return;
        }
        
        $this->constants();
        $this->includes();
        $this->actions();
        // $this->filters();
    }
    
    public static function init()
    {
        if ( !self::$instance instanceof self ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function constants()
    {
        define( 'C4WP_PLUGIN_VERSION', '6.1.7' );
        define( 'C4WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'C4WP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
        define( 'C4WP_PLUGIN_FILE', __FILE__ );
        define( 'C4WP_TABLE_PREFIX', 'c4wp_' );
     }
    
    private function includes()
    {
        require_once C4WP_PLUGIN_DIR . 'functions.php';
        if ( file_exists( C4WP_PLUGIN_DIR . 'extensions/pro-extensions.php' ) ) {
            require_once C4WP_PLUGIN_DIR . 'extensions/pro-extensions.php';
            
            // Extensions.
            require_once C4WP_PLUGIN_DIR . 'extensions/failed-logins.php';
            $failed_logins = new C4WP_Failed_Logins_Captcha;
            $failed_logins->init();

            require_once C4WP_PLUGIN_DIR . 'extensions/third-party/contact-form-7.php';
            $cf7 = new C4WP_CF7_Captcha;
            $cf7->init();

            require_once C4WP_PLUGIN_DIR . 'extensions/third-party/woocommerce.php';
            $wc = new C4WP_WooCommerce_Captcha;
            $wc->init();

            require_once C4WP_PLUGIN_DIR . 'extensions/third-party/bbpress.php';
            $bbpress = new C4WP_BBPress_Captcha;
            $bbpress->init();

            require_once C4WP_PLUGIN_DIR . 'extensions/third-party/buddypress.php';
            $buddypress = new C4WP_BuddyPress_Captcha;
            $buddypress->init();

            require_once C4WP_PLUGIN_DIR . 'extensions/third-party/mailchimp-4wp.php';
            $mc4wp = new C4WP_Mailchimp4WP_Captcha;
            $mc4wp->init();
        }
    }
    
    private function actions()
    {
        add_action( 'after_setup_theme', 'c4wp_include_require_files' );
        add_action( 'init', 'c4wp_translation' );
        add_action( 'login_enqueue_scripts', 'c4wp_login_enqueue_scripts' );
        //cleanup after uninstall
        c4wp_fs()->add_action( 'after_uninstall', 'c4wp_fs_uninstall_cleanup' );
        //Support fourm link in admin dashboard sidebar
        c4wp_fs()->add_filter( 'support_forum_url', 'c4wp_fs_support_forum_url' );

        c4wp_fs()->add_action( 'is_submenu_visible', 'hide_freemius_submenu_items', 10, 2 );
    }

    

}
//END Class
if ( function_exists( 'c4wp_fs' ) ) {
    c4wp_fs()->set_basename( true, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'c4wp_fs' ) ) {
        // Create a helper function for easy SDK access.
        function c4wp_fs() {
            global $c4wp_fs ;
            $for_network = is_plugin_active_for_network( plugin_basename( __FILE__ ) );

            $freemius_state = get_option( 'c4wp_freemius_state', 'anonymous' );
		    $is_anonymous   = ( 'anonymous' === $freemius_state || 'skipped' === $freemius_state );
            
            if ( ! isset( $c4wp_fs ) ) {
                // Activate multisite network integration.
                if ( $for_network && !defined( 'WP_FS__PRODUCT_5860_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_5860_MULTISITE', true );
                }

                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, [
                    'third-party',
                    'wordpress-sdk',
                    'start.php'
                ] );

                $c4wp_fs = fs_dynamic_init( array(
                    'id'             => '5860',
                    'slug'           => 'advanced-nocaptcha-recaptcha',
                    'premium_slug'   => 'advanced-nocaptcha-and-invisible-captcha-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_8758a9fa397c3760defbec41e2e35',
                    'is_premium'     => true,
                    'premium_suffix' => 'PRO',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'        => 'c4wp-admin-captcha',
                        'contact'     => false,
                        'support'     => false,
                        'pricing'     => false,
                        'network'     => $for_network,
                    ),
                    'is_live'        => true,
                ) );

            }
            
            return $c4wp_fs;
        }
        
        // Init Freemius.
        c4wp_fs();
        // Signal that SDK was initiated.
        do_action( 'c4wp_fs_loaded' );
    }
    
    // ... Your plugin's main file logic ...
    add_action( 'plugins_loaded', array( 'C4WP', 'init' ) );
}

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
        delete_option(' c4wp_redirect_after_activation' );
        exit( wp_redirect( c4wp_settings_page_url() ) );
    }
}