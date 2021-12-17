<?php

/*
Plugin Name: CAPTCHA 4WP (Premium)
Plugin URI: https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/
Description: Show noCaptcha or invisible captcha in Comment Form, bbPress, BuddyPress, WooCommerce, CF7, Login, Register, Lost Password, Reset Password. Also can implement in any other form easily.
Version: 6.1.7
Author: WP White Security
Author URI: https://www.wpwhitesecurity.com/
Text Domain: advanced-nocaptcha-recaptcha
License: GPLv2 or later
WC tested up to: 5.6.0
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';
class C4WP_Pro
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
        function c4wp_fs()
        {
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
                require_once dirname( __FILE__ ) . '/freemius/start.php';
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
                        'affiliation' => false,
                        'network' => $for_network,
                    ),
                    'is_live'        => true,
                    'anonymous_mode' => $is_anonymous,
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
    add_action( 'plugins_loaded', array( 'C4WP_Pro', 'init' ) );
}
