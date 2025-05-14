<?php
/**
 * Base functions and back-compar items.
 *
 * @package C4WP
 */

declare(strict_types=1);

/* @free:start */
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
		$admin_url = add_query_arg( array( 'page' => 'c4wp-admin-captcha' ), network_admin_url( 'admin.php' ) );
		wp_safe_redirect( esc_url( $admin_url ) );
		exit();
	}
}
/* @free:end */

/**
 * Declare compatibility with WC HPOS.
 *
 * @return void
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', C4WP_PLUGIN_FILE, true );
		}
	}
);

/**
 * Uninstall the plugin
 *
 * @return void
 */
if ( ! function_exists( 'c4wp_uninstall' ) ) {

	/**
	 * Uninstaller for plugin data, if desired in settings.
	 *
	 * @return void
	 *
	 * @since 7.6.0
	 */
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
				// Ignore as is valid use.
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
				// Ignore as is valid use.
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

			// Remove specific Freemius entry.
			delete_site_option( 'fs_c4wp' );

			$table_name = $wpdb->prefix . 'c4wp_failed_login_tracking';

			// We ignore the query as its valid use as well as the statement given its a custom table.
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

			if ( $get_site_options ) {
				$options = delete_site_option( 'c4wp_admin_options' );
			} else {
				$options = delete_option( 'c4wp_admin_options' );
			}
		}
	}
}

/**
 * Backfill function for users with C4WP integrated into a custom form.
 *
 * @return bool - Result.
 */
if ( ! function_exists( 'c4wp_verify_captcha' ) ) {
	/**
	 * Verify a given response.
	 *
	 * @param string $response - String to verify.
	 *
	 * @return bool - Did verify result.
	 *
	 * @since 7.6.0
	 */
	function c4wp_verify_captcha( $response = '' ) {
		if ( class_exists( 'C4WP\C4WP_Functions' ) ) {
			return C4WP\C4WP_Functions::c4wp_verify_captcha( $response );
		}
		return false;
	}
}

if ( ! function_exists( 'anr_verify_captcha' ) ) {
	/**
	 * Verify a given response. Exists for very old users.
	 *
	 * @param string $response - String to verify.
	 *
	 * @return bool - Did verify result.
	 *
	 * @since 7.6.0
	 */
	function anr_verify_captcha( $response = '' ) {
		if ( class_exists( 'C4WP\C4WP_Functions' ) ) {
			return C4WP\C4WP_Functions::c4wp_verify_captcha( $response );
		}
		return false;
	}
}


/* @free:start */
if ( ! function_exists( 'c4wp_free_on_plugin_activation' ) ) {
	/**
	 * Handle swapping of premiums and free editions bu disabling premium edition.
	 *
	 * @return void
	 *
	 * @since 7.6.0
	 */
	function c4wp_free_on_plugin_activation() {
		if ( is_plugin_active( 'advanced-nocaptcha-and-invisible-captcha-pro/advanced-nocaptcha-and-invisible-captcha-pro.php' ) ) {
			deactivate_plugins( 'advanced-nocaptcha-and-invisible-captcha-pro/advanced-nocaptcha-and-invisible-captcha-pro.php' );
		}
	}
}
/* @free:end */
