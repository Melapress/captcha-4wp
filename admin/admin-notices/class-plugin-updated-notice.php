<?php
/**
 * Plugin settings.
 *
 * @package C4WP
 * @since 7.0.0
 */

declare(strict_types=1);

namespace C4WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Settings class.
 */
class PluginUpdatedNotice {

	/**
	 * Lets set things up
	 *
	 * @return void
	 *
	 * @since 7.6.0
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'plugin_new_owner_banner' ) );
		add_action( 'network_admin_notices', array( __CLASS__, 'plugin_new_owner_banner' ) );
		add_action( 'admin_init', array( __CLASS__, 'on_plugin_update' ), 10 );
		// add_action( 'admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ) );
		// add_action( 'network_admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ) );
		add_action( 'wp_ajax_c4wp_dismiss_update_notice', array( __CLASS__, 'dismiss_update_notice' ) );	
		add_action( 'wp_ajax_c4wp_dismiss_owner_notice', array( __CLASS__, 'dismiss_owner_notice' ) );	
	}

	/**
	 * Simple checker for admin facing notices.
	 *
	 * @return int - Count.
	 *
	 * @since 2.0.0
	 */
	public static function get_current_notices_count() {
		$count = 0;

		// if ( get_site_option( C4WP_PREFIX . 'update_notice_needed', false ) ) {
		// 	++$count;
		// }

		return $count;
	}

	/**
	 * Show notice to recently updated plugin.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function plugin_was_updated_banner() {
		$show_update_notice = get_site_option( C4WP_PREFIX . 'update_notice_needed', false );
		$screen             = get_current_screen();

		$pages_for_banner = array(
			'toplevel_page_c4wp-admin-captcha',
			'toplevel_page_c4wp-admin-captcha-network',
			'captcha-4wp_page_c4wp-admin-geo-blocking',
			'captcha-4wp_page_c4wp-admin-geo-blocking-network',
			'captcha-4wp_page_c4wp-admin-forms',
			'captcha-4wp_page_c4wp-admin-forms-network',
			'captcha-4wp_page_c4wp-admin-settings',
			'captcha-4wp_page_c4wp-admin-settings-network',
			'captcha-4wp_page_c4wp-admin-help',
			'captcha-4wp_page_c4wp-admin-help-network',
		);

		if ( in_array( $screen->base, $pages_for_banner, true ) && $show_update_notice ) {
			?>
			<!-- Copy START -->
			<div class="c4wp-plugin-update">
				<div class="c4wp-plugin-update-content">
					<h2 class="c4wp-plugin-update-title"><?php esc_html_e( 'Captcha 4WP has been updated to version', 'advanced-nocaptcha-recaptcha' ); ?> <?php echo esc_attr( C4WP_VERSION ); ?>.</h2>
					<p class="c4wp-plugin-update-text">
						<?php esc_html_e( 'You are now running the latest version of CAPTCHA 4WP. To see what\'s been included in this update, refer to the plugin\'s release notes and change log where we list all new features, updates, and bug fixes.', 'advanced-nocaptcha-recaptcha' ); ?>							
					</p>
					<a href="https://captcha4wp.com/releases/?utm_source=plugin&utm_medium=banner&utm_campaign=c4wp" target="_blank" class="c4wp-cta-link"><?php esc_html_e( 'Read the release notes', 'advanced-nocaptcha-recaptcha' ); ?></a>
				</div>
				<button aria-label="Close button" class="c4wp-plugin-update-close" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( C4WP_PREFIX . 'dismiss_update_notice_nonce' ) ); ?>"></button>
			</div>
			<!-- Copy END -->
			
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function( $ ) {
				jQuery( 'body' ).on( 'click', '.c4wp-plugin-update-close', function ( e ) {
					var nonce  = jQuery( '.c4wp-plugin-update [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );
					
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						async: true,
						data: {
							action: 'c4wp_dismiss_update_notice',
							nonce : nonce,
						},
						success: function ( result ) {		
							jQuery( '.c4wp-plugin-update' ).slideUp( 300 );
						}
					});
				});
			});
			//]]>
			</script>
			<?php
		}

		if ( ( in_array( $screen->base, $pages_for_banner, true ) && $show_update_notice ) ) {
			?>
			<style type="text/css">
				/* Melapress brand font 'Quicksand' — There maybe be a preferable way to add this but this seemed the most discrete. */
				@font-face {
					font-family: 'Quicksand';
					src: url('<?php echo \esc_url( C4WP_PLUGIN_URL ); ?>assets/fonts/Quicksand-VariableFont_wght.woff2') format('woff2');
					font-weight: 100 900; /* This indicates that the variable font supports weights from 100 to 900 */
					font-style: normal;
				}
				
				.c4wp-plugin-update, .c4wp-plugin-data-migration {
					background-color: #50284E;
					border-radius: 7px;
					color: #fff;
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 1.66rem;
					position: relative;
					overflow: hidden;
					transition: all 0.2s ease-in-out;
					margin-top: 20px;
					margin-right: 20px;
				}			

				.c4wp-plugin-update-content {
					max-width: 45%;
				}
				
				.c4wp-plugin-update-title {
					margin: 0;
					font-size: 20px;
					font-weight: bold;
					font-family: Quicksand, sans-serif;
					line-height: 1.44rem;
					color: #fff;
				}
				
				.c4wp-plugin-update-text {
					margin: .25rem 0 0;
					font-size: 0.875rem;
					line-height: 1.3125rem;
				}
				
				.c4wp-plugin-update-text a:link {
					color: #FF8977;
				}
				
				.c4wp-cta-link {
					border-radius: 0.25rem;
					background: #FF8977;
					color: #0000EE;
					font-weight: bold;
					text-decoration: none;
					font-size: 0.875rem;
					padding: 0.675rem 1.3rem .7rem 1.3rem;
					transition: all 0.2s ease-in-out;
					display: inline-block;
					margin: .5rem auto;
				}
				
				.c4wp-cta-link:hover {
					background: #0000EE;
					color: #FF8977;
				}
				
				.c4wp-plugin-update-close {
					background-image: url(<?php echo esc_url( C4WP_PLUGIN_URL ) . 'assets/img/close-icon-rev.svg'; ?>); /* Path to your close icon */
					background-size: cover;
					width: 18px;
					height: 18px;
					border: none;
					cursor: pointer;
					position: absolute;
					top: 20px;
					right: 20px;
					background-color: transparent;
				}
				
				.c4wp-plugin-update::before {
					content: '';
					background-image: url(<?php echo esc_url( C4WP_PLUGIN_URL ) . 'assets/img/c4wp-updated-bg.png'; ?>); /* Background image only displayed on desktop */
					background-size: 100%;
					background-repeat: no-repeat;
					background-position: 100% 51%;
					position: absolute;
					top: 0;
					right: 0;
					bottom: 0;
					left: 0;
					z-index: 0;
				}
				
				.c4wp-plugin-update-content, .c4wp-plugin-update-close {
					z-index: 1;
				}
				
				@media (max-width: 1200px) {
					.c4wp-plugin-update::before {
						display: none;
					}
				
					.c4wp-plugin-update-content {
						max-width: 100%;
					}
				}

				.c4wp-plugin-data-migration {
					background-color: #D9E4FD;						
				}

				.c4wp-plugin-data-migration * {
					color: #1A3060;
				}

				.c4wp-plugin-data-migration .c4wp-plugin-update-content {
					min-height: 80px;
				}
					
				#spinning-wrapper {
					position: absolute;
					right: -20px;
					height: 300px;
					width: 300px;
				}

				#spinning-wrapper .dashicons {
					height: 300px;
					height: 300px;
					font-size: 300px;
				}

				#spinning-wrapper  * {
					color: #8AAAF1 !important;
				}

				#spinning-wrapper.active {
					-webkit-animation: spin 4s infinite linear;
				}

				@-webkit-keyframes spin {
					0%  {-webkit-transform: rotate(0deg);}
					100% {-webkit-transform: rotate(360deg);}   
				}
			</style>
			<?php
		}
	}

	/**
	 * Redirects user to admin on plugin update.
	 *
	 * @return void
	 *
	 * @since 7.6.0
	 */
	public static function on_plugin_update() {
		if ( get_site_option( C4WP_PREFIX . 'update_redirection_needed', false ) ) {
			delete_site_option( C4WP_PREFIX . 'update_redirection_needed' );
			update_site_option( C4WP_PREFIX . 'update_notice_needed', true );
			$args = array(
				'page' => 'c4wp-admin-captcha',
			);
			$url  = add_query_arg( $args, network_admin_url( 'admin.php' ) );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Handle notice dismissal.
	 *
	 * @since 7.6.0
	 *
	 * @return void
	 */
	public static function dismiss_update_notice() {
		// Grab POSTed data.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : false;
		// Check nonce.
		if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, C4WP_PREFIX . 'dismiss_update_notice_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
		}

		delete_site_option( C4WP_PREFIX . 'update_notice_needed' );

		wp_send_json_success( esc_html__( 'Complete.', 'advanced-nocaptcha-recaptcha' ) );
	}

	/**
	 * Show notice to alert of new ownership.
	 *
	 * @return void
	 *
	 * @since 7.6.0
	 */
	public static function plugin_new_owner_banner() {
		$screen			    = get_current_screen();
		$show_update_notice = get_site_option( C4WP_PREFIX . 'owner_notice_dismissed', false );

		$pages_for_banner = array(
			'toplevel_page_c4wp-admin-captcha',
			'toplevel_page_c4wp-admin-captcha-network',
			'captcha-4wp_page_c4wp-admin-geo-blocking',
			'captcha-4wp_page_c4wp-admin-geo-blocking-network',
			'captcha-4wp_page_c4wp-admin-forms',
			'captcha-4wp_page_c4wp-admin-forms-network',
			'captcha-4wp_page_c4wp-admin-settings',
			'captcha-4wp_page_c4wp-admin-settings-network',
			'captcha-4wp_page_c4wp-admin-help',
			'captcha-4wp_page_c4wp-admin-help-network',
		);

		if ( in_array( $screen->base, $pages_for_banner, true ) && ! $show_update_notice ) {
			?>
			<!-- Copy START -->
			<div class="c4wp-plugin-update new-owner">
				<div class="c4wp-plugin-update-content">
					<h2 class="c4wp-plugin-update-title"><?php esc_html_e( 'CAPTCHA 4WP has been acquired by WPKube', 'advanced-nocaptcha-recaptcha' ); ?>.</h2>
					<p class="c4wp-plugin-update-text">
						<?php esc_html_e( 'As part of our ongoing efforts to focus more on our core plugins, we\'ve decided to sell CAPTCHA 4WP to WPKube. Devesh and his team are well-known for their expertise in WordPress, and we\'re confident that CAPTCHA 4 WP will continue to thrive under their leadership.', 'advanced-nocaptcha-recaptcha' ); ?>							
					</p>
					<a href="https://melapress.com/captcha-4-wp-plugin-acquired-by-wpkube/?utm_source=plugin&utm_medium=acquired-banner&utm_campaign=c4wp" target="_blank" class="c4wp-cta-link"><?php esc_html_e( 'Read the announcement', 'advanced-nocaptcha-recaptcha' ); ?></a>
				</div>
				<button aria-label="Close button" class="c4wp-owner-notice-close" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( C4WP_PREFIX . 'dismiss_owner_notice_nonce' ) ); ?>"></button>
			</div>
			<!-- Copy END -->
			
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function( $ ) {
				jQuery( 'body' ).on( 'click', '.c4wp-owner-notice-close', function ( e ) {
					var nonce  = jQuery( '.new-owner [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );					
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						async: true,
						data: {
							action: 'c4wp_dismiss_owner_notice',
							nonce : nonce,
						},
						success: function ( result ) {		
							jQuery( '.c4wp-plugin-update.new-owner' ).slideUp( 300 );
						}
					});
				});
			});
			//]]>
			</script>
			<?php
		}

		if ( ( in_array( $screen->base, $pages_for_banner, true ) ) ) {
			?>
			<style type="text/css">
				/* Melapress brand font 'Quicksand' — There maybe be a preferable way to add this but this seemed the most discrete. */
				@font-face {
					font-family: 'Quicksand';
					src: url('<?php echo \esc_url( C4WP_PLUGIN_URL ); ?>assets/fonts/Quicksand-VariableFont_wght.woff2') format('woff2');
					font-weight: 100 900; /* This indicates that the variable font supports weights from 100 to 900 */
					font-style: normal;
				}
				
				.c4wp-plugin-update.new-owner {
					background-color: #824780 !important;
					border-radius: 7px;
					color: #fff;
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 1.66rem;
					position: relative;
					overflow: hidden;
					transition: all 0.2s ease-in-out;
					margin-top: 20px;
					margin-right: 20px;
				}			

				.new-owner .c4wp-plugin-update-content {
					max-width: 100% !important;
				}
				
				.c4wp-plugin-update-title {
					margin: 0;
					font-size: 20px;
					font-weight: bold;
					font-family: Quicksand, sans-serif;
					line-height: 1.44rem;
					color: #fff;
				}
				
				.c4wp-plugin-update-text {
					margin: .25rem 0 0;
					font-size: 0.875rem;
					line-height: 1.3125rem;
				}
				
				.c4wp-plugin-update-text a:link {
					color: #FF8977;
				}
				
				.c4wp-cta-link {
					border-radius: 0.25rem;
					background: #FF8977;
					color: #0000EE;
					font-weight: bold;
					text-decoration: none;
					font-size: 0.875rem;
					padding: 0.675rem 1.3rem .7rem 1.3rem;
					transition: all 0.2s ease-in-out;
					display: inline-block;
					margin: .5rem auto;
				}
				
				.c4wp-cta-link:hover {
					background: #0000EE;
					color: #FF8977;
				}
				
				.c4wp-owner-notice-close {
					background-image: url(<?php echo esc_url( C4WP_PLUGIN_URL ) . 'assets/img/close-icon-rev.svg'; ?>); /* Path to your close icon */
					background-size: cover;
					width: 18px;
					height: 18px;
					border: none;
					cursor: pointer;
					position: absolute;
					top: 20px;
					right: 20px;
					background-color: transparent;
				}
				
				.c4wp-plugin-update.new-owner::before {
					content: '';
					display: none !important;
					background-image: none !important;
					background-size: 100%;
					background-repeat: no-repeat;
					background-position: 100% 51%;
					position: absolute;
					top: 0;
					right: 0;
					bottom: 0;
					left: 0;
					z-index: 0;
				}
				
				.c4wp-plugin-update-content, .c4wp-plugin-update-close {
					z-index: 1;
				}
				
				@media (max-width: 1200px) {
					.c4wp-plugin-update::before {
						display: none;
					}
				
					.c4wp-plugin-update-content {
						max-width: 100%;
					}
				}

				.c4wp-plugin-data-migration {
					background-color: #D9E4FD;						
				}

				.c4wp-plugin-data-migration * {
					color: #1A3060;
				}

				.c4wp-plugin-data-migration .c4wp-plugin-update-content {
					min-height: 80px;
				}
					
				#spinning-wrapper {
					position: absolute;
					right: -20px;
					height: 300px;
					width: 300px;
				}

				#spinning-wrapper .dashicons {
					height: 300px;
					height: 300px;
					font-size: 300px;
				}

				#spinning-wrapper  * {
					color: #8AAAF1 !important;
				}

				#spinning-wrapper.active {
					-webkit-animation: spin 4s infinite linear;
				}

				@-webkit-keyframes spin {
					0%  {-webkit-transform: rotate(0deg);}
					100% {-webkit-transform: rotate(360deg);}   
				}
			</style>
			<?php
		}
	}

	/**
	 * Handle notice dismissal.
	 *
	 * @since 7.6.0
	 *
	 * @return void
	 */
	public static function dismiss_owner_notice() {
		// Grab POSTed data.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : false;
		// Check nonce.
		if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, C4WP_PREFIX . 'dismiss_owner_notice_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
		}

		update_site_option( C4WP_PREFIX . 'owner_notice_dismissed', true );

		wp_send_json_success( esc_html__( 'Complete.', 'advanced-nocaptcha-recaptcha' ) );
	}
}