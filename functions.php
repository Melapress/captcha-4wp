<?php
// Add update hook.
add_action( 'init', 'c4wp_plugin_update', -15 );

function c4wp_plugin_update() {
	$prev_version = c4wp_get_option( 'version', '3.1' );
	if ( version_compare( $prev_version, C4WP_PLUGIN_VERSION, '!=' ) ) {
		do_action( 'c4wp_plugin_update', $prev_version );
		c4wp_update_option( 'version', C4WP_PLUGIN_VERSION );
	}
}

// Plugin update actions.
add_action( 'c4wp_plugin_update', 'c4wp_plugin_update_32', 10 );
add_action( 'c4wp_plugin_update', 'c4wp_plugin_update_51', 20 );
add_action( 'c4wp_plugin_update', 'c4wp_plugin_update_70', 30 );
add_action( 'c4wp_plugin_update', 'c4wp_plugin_update_706', 30 );
add_action( 'c4wp_plugin_update', 'c4wp_plugin_update_7061', 40 );

function c4wp_plugin_update_32( $prev_version ) {
	if ( version_compare( $prev_version, '3.2', '<' ) ) {
		if ( function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ) {
			$options = get_site_option( 'c4wp_admin_options' );
		} else {
			$options = get_option( 'c4wp_admin_options' );
		}
		if ( ! $options || ! is_array( $options ) ) {
			return;
		}
		$options['error_message'] = str_replace( esc_html__( '<strong>ERROR</strong>: ', 'advanced-nocaptcha-recaptcha' ), '', c4wp_get_option( 'error_message' ) );

		$enabled_forms = [];
		if ( ! empty( $options['login'] ) ) {
			$enabled_forms[] = 'login';
		}
		if ( ! empty( $options['registration'] ) ) {
			$enabled_forms[] = 'registration';
		}
		if ( ! empty( $options['ms_user_signup'] ) ) {
			$enabled_forms[] = 'ms_user_signup';
		}
		if ( ! empty( $options['lost_password'] ) ) {
			$enabled_forms[] = 'lost_password';
		}
		if ( ! empty( $options['reset_password'] ) ) {
			$enabled_forms[] = 'reset_password';
		}
		if ( ! empty( $options['comment'] ) ) {
			$enabled_forms[] = 'comment';
		}
		if ( ! empty( $options['bb_new'] ) ) {
			$enabled_forms[] = 'bbp_new';
		}
		if ( ! empty( $options['bb_reply'] ) ) {
			$enabled_forms[] = 'bbp_reply';
		}
		if ( ! empty( $options['wc_checkout'] ) ) {
			$enabled_forms[] = 'wc_checkout';
		}
		$options['enabled_forms'] = $enabled_forms;

		unset( $options['login'], $options['registration'], $options['ms_user_signup'], $options['lost_password'], $options['reset_password'], $options['comment'], $options['bb_new'], $options['bb_reply'], $options['wc_checkout'] );

		c4wp_update_option( $options );
	}
}

function c4wp_plugin_update_51( $prev_version ) {
	if ( version_compare( $prev_version, '5.1', '<' ) ) {
		$options = [];
		if ( 'invisible' === c4wp_get_option( 'size' ) ) {
			$options['size']            = 'normal';
			$options['captcha_version'] = 'v2_invisible';
		}

		c4wp_update_option( $options );
	}
}

function c4wp_plugin_update_70( $prev_version ) {
	if ( version_compare( $prev_version, '7.0.6.1', '<' ) ) {
		if ( is_multisite() ) {
            if ( ! get_site_option( 'c4wp_70_upgrade_complete' ) ) {
                $original_options = get_site_option( 'anr_admin_options' );
                update_site_option( 'c4wp_admin_options', $original_options );
                update_site_option( 'c4wp_70_upgrade_complete', true );
                delete_site_option( 'anr_admin_options' );
            }
		} else {
            if ( ! get_option( 'c4wp_70_upgrade_complete' ) ) {
                $original_options = get_option( 'anr_admin_options' );
                update_option( 'c4wp_admin_options', $original_options );
                update_option( 'c4wp_70_upgrade_complete', true );
                delete_option( 'anr_admin_options' );
            }
		}
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = %s", [ 'anr-post' ] ) );
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
	}
}

function c4wp_plugin_update_706( $prev_version ) {
	if ( version_compare( $prev_version, '7.0.6', '<' ) ) {
		delete_transient( 'c4wp_config_file_hash' );
	}
}

/**
 * Update langiage if auto-detect was enabled.
 *
 * @param  string $prev_version
 * @return void
 */
function c4wp_plugin_update_7061( $prev_version ) {
    $current_lang = c4wp_get_option( 'language' );
	if ( version_compare( $prev_version, '7.0.6.1', '<' ) && empty( $current_lang ) ) {
        c4wp_update_option( 'language', 'en' );
	}
}

/**
 * Handle getting options for our plugin.
 */
function c4wp_get_option( $option, $default = '', $section = 'c4wp_admin_options' ) {

	$get_site_options = is_multisite();

	if ( $get_site_options ) {
		$options = get_site_option( $section );
	} else {
		$options = get_option( $section );
	}

	if ( isset( $options[ $option ] ) ) {
		$value      = $options[ $option ];
		$is_default = false;
	} else {
		$value      = $default;
		$is_default = true;
	}
	return apply_filters( 'c4wp_get_option', $value, $option, $default, $is_default );
}

/**
 * Handle updating option for our plugin.
 */
function c4wp_update_option( $options, $value = '', $section = 'c4wp_admin_options' ) {

	if ( $options && ! is_array( $options ) ) {
		$options = array(
			$options => $value,
		);
	}
	if ( ! is_array( $options ) ) {
		return false;
	}

	$update_site_options = is_multisite();

	if ( $update_site_options ) {
		update_site_option( $section, wp_parse_args( $options, get_site_option( $section ) ) );
	} else {
		update_option( $section, wp_parse_args( $options, get_option( $section ) ) );		
	}

	return true;
}

/**
 * Checks if a specific form is enabled within the plugins settings.
 */
function c4wp_is_form_enabled( $form ) {
	if ( ! $form ) {
		return false;
	}
	$enabled_forms = array_merge( c4wp_get_option( 'enabled_forms', array() ), c4wp_get_option( 'enabled_forms_wc', array() ), c4wp_get_option( 'enabled_forms_bp', array() ), c4wp_get_option( 'enabled_forms_bbp', array() ) );
	
	if ( ! is_array( $enabled_forms ) ) {
		return false;
	}
	return in_array( $form, $enabled_forms, true );
}

function c4wp_translation() {
	// SETUP TEXT DOMAIN FOR TRANSLATIONS
	load_plugin_textdomain( 'advanced-nocaptcha-recaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function c4wp_login_enqueue_scripts() {

	if ( ! c4wp_get_option( 'remove_css' ) && 'normal' === c4wp_get_option( 'size', 'normal' ) && 'v2_checkbox' === c4wp_get_option( 'captcha_version', 'v2_checkbox' ) ) {
		wp_enqueue_style( 'c4wp-login-style', C4WP_PLUGIN_URL . 'assets/css/style.css' );
	}
}

function c4wp_include_require_files() {
	$fep_files = array(
		'main' => 'anr-captcha-class.php',
	);
	if ( is_admin() ) {
		$fep_files['settings'] = 'admin/settings.php';
	}

	$fep_files = apply_filters( 'c4wp_include_files', $fep_files );

	foreach ( $fep_files as $fep_file ) {
		require_once $fep_file;
	}
}
add_action( 'wp_footer', 'c4wp_wp_footer', 99999 );
add_action( 'login_footer', 'c4wp_wp_footer', 99999 );

function c4wp_wp_footer() {
	c4wp_captcha_class::init()->footer_script();
}

add_action( 'c4wp_captcha_form_field', function() { c4wp_captcha_form_field( true ); } );
add_shortcode( 'c4wp-captcha', 'c4wp_captcha_form_field' );

// Old versions for back-compat.
add_action( 'anr_captcha_form_field', function() { c4wp_captcha_form_field( true ); } );
add_shortcode( 'anr-captcha', 'c4wp_captcha_form_field' );

function c4wp_captcha_form_field( $echo = false ) {
	if ( $echo ) {
		c4wp_captcha_class::init()->form_field();
	} else {
		return c4wp_captcha_class::init()->form_field_return();
	}

}

function anr_verify_captcha( $response = false ) {
	return c4wp_captcha_class::init()->verify( $response );
}

function c4wp_verify_captcha( $response = false ) {
	return c4wp_captcha_class::init()->verify( $response );
}

add_filter( 'shake_error_codes', 'c4wp_add_shake_error_codes' );

function c4wp_add_shake_error_codes( $shake_error_codes ) {
	$shake_error_codes[] = 'c4wp_error';

	return $shake_error_codes;
}

function c4wp_fs_uninstall_cleanup() {
	global $wpdb;

	$post_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'c4wp-post' LIMIT 1" );

	if ( $post_id ) {
		// There may have too many post meta. delete them first in one query.
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d", $post_id ) );
		
		wp_delete_post( $post_id, true );
	}
}

function c4wp_fs_support_forum_url( $wp_org_support_forum_url ) {
	return 'https://www.wpwhitesecurity.com/contact/';
}

function c4wp_recaptcha_domain(){
	$domain = c4wp_get_option( 'recaptcha_domain', 'google.com' );
	return apply_filters( 'c4wp_recaptcha_domain', $domain );
}


function c4wp_settings_page_url( $tab = false ){
	$url = ( function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() || ! function_exists( 'c4wp_same_settings_for_all_sites' ) ) ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
	return $url;
}

function hide_freemius_submenu_items( $is_visible, $submenu_id ) {
	if ( 'contact' === $submenu_id ) {
		$is_visible = false;
	}
	return $is_visible;
}

function c4wp_get_sysinfo() {
	// System info.
	global $wpdb;

	$sysinfo = '### System Info → Begin ###' . "\n\n";

	// Start with the basics...
	$sysinfo .= '-- Site Info --' . "\n\n";
	$sysinfo .= 'Site URL (WP Address):    ' . site_url() . "\n";
	$sysinfo .= 'Home URL (Site Address):  ' . home_url() . "\n";
	$sysinfo .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	// Get theme info.
	$theme_data   = wp_get_theme();
	$theme        = $theme_data->Name . ' ' . $theme_data->Version;
	$parent_theme = $theme_data->Template;
	if ( ! empty( $parent_theme ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
	}

	// Language information.
	$locale = get_locale();

	// WordPress configuration.
	$sysinfo .= "\n" . '-- WordPress Configuration --' . "\n\n";
	$sysinfo .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$sysinfo .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
	$sysinfo .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$sysinfo .= 'Active Theme:             ' . $theme . "\n";
	if ( $parent_theme !== $theme ) {
		$sysinfo .= 'Parent Theme:             ' . $parent_theme . "\n";
	}
	$sysinfo .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'.
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$front_page_id = (int) get_option( 'page_on_front' );
		$blog_page_id  = (int) get_option( 'page_for_posts' );

		$sysinfo .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$sysinfo .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$sysinfo .= 'ABSPATH:                  ' . ABSPATH . "\n";
	$sysinfo .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$sysinfo .= 'WP Memory Limit:          ' . WP_MEMORY_LIMIT . "\n";

	// Get plugins that have an update.
	$updates = get_plugin_updates();

	// Must-use plugins.
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if ( count( $muplugins ) > 0 ) {
		$sysinfo .= "\n" . '-- Must-Use Plugins --' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$sysinfo .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}
	}

	// WordPress active plugins.
	$sysinfo .= "\n" . '-- WordPress Active Plugins --' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	// WordPress inactive plugins.
	$sysinfo .= "\n" . '-- WordPress Inactive Plugins --' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	if ( is_multisite() ) {
		// WordPress Multisite active plugins.
		$sysinfo .= "\n" . '-- Network Active Plugins --' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin   = get_plugin_data( $plugin_path );
			$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}
	}

	// Server configuration.
	$server_software = filter_input( INPUT_SERVER, 'SERVER_SOFTWARE', FILTER_SANITIZE_STRING );
	$sysinfo        .= "\n" . '-- Webserver Configuration --' . "\n\n";
	$sysinfo        .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$sysinfo        .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";

	if ( isset( $server_software ) ) {
		$sysinfo .= 'Webserver Info:           ' . $server_software . "\n";
	} else {
		$sysinfo .= 'Webserver Info:           Global $_SERVER array is not set.' . "\n";
	}

	// PHP configs.
	$sysinfo .= "\n" . '-- PHP Configuration --' . "\n\n";
	$sysinfo .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$sysinfo .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$sysinfo .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$sysinfo .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$sysinfo .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$sysinfo .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$sysinfo .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	$sysinfo .= "\n" . '-- Captcha4WP Settings  --' . "\n\n";

	$c4wp_options = get_option( 'c4wp_admin_options' );

	if ( ! empty( $c4wp_options ) ) {
		foreach ( $c4wp_options as $option => $value) {
			$sysinfo .= 'Option: ' . $option . "\n";
			$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n";
		}
	}
	
	$sysinfo .= "\n" . '### System Info → End ###' . "\n\n";

	return $sysinfo;
}

/**
 * Determines if an install is premium/paying.
 *
 * @return void
 */
function c4wp_is_premium_version() {
	return ( class_exists( 'C4WP_Pro' ) && ! c4wp_fs()->is_not_paying() ) ? true : false;
}
