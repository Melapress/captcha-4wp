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
