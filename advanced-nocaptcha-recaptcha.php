<?php

/*
* Plugin Name: CAPTCHA 4WP
* Plugin URI: https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/
* Description: Easily show any type of Captcha check (such as noCaptcha or invisible Captcha) on any form on your website, including login pages, comments and password reset forms, and also forms by third party plugins such as Contact Form 7, BuddyPress & WooCommerce.
* Version: 7.0.0
* Author: WP White Security
* Author URI: https://www.wpwhitesecurity.com/
* Text Domain: advanced-nocaptcha-recaptcha
* License: GPLv2 or later
* WC tested up to: 5.6.0
* Network: true
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
    }
    
    private function actions()
    {
        add_action( 'after_setup_theme', 'c4wp_include_require_files' );
        add_action( 'init', 'c4wp_translation' );
        add_action( 'login_enqueue_scripts', 'c4wp_login_enqueue_scripts' );
    }

    

}
//END Class
    
    // ... Your plugin's main file logic ...
    add_action( 'plugins_loaded', array( 'C4WP', 'init' ) );

/* @free:start */
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
/* @free:end */