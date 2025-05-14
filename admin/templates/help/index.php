<?php
/**
 * Help tabs.
 *
 * @package C4WP
 * @since 7.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="wrap help-wrap">
	<div class="nav-tab-wrapper">
		<?php
			// Get current tab, it can only be help or system info, ignore anything else.
			$current_tab = ( isset( $_GET['tab'] ) && 'system-info' === $_GET['tab'] ) ? esc_html( sanitize_key( wp_unslash( $_GET['tab'] ) ) ) : 'help'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab<?php echo 'help' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'advanced-nocaptcha-recaptcha' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'system-info' ) ); ?>" class="nav-tab<?php echo 'system-info' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Info', 'advanced-nocaptcha-recaptcha' ); ?></a>
	</div>
	<div class="c4wp-help-section nav-tabs">
		<?php
		// Require page content. Default help.php. Only allow one or the other, nothing else.
		if ( 'system-info' === $current_tab || 'help' === $current_tab ) {
			require_once trailingslashit( __DIR__ ) . $current_tab . '.php';
		}
		?>
	</div>
</div>
