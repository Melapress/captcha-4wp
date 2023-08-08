<?php
/**
 * Help tabs.
 *
 * @package C4WP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="our-wordpress-plugins side-bar">
	<h3><?php esc_html_e( 'Other plugins developed by us:', 'advanced-nocaptcha-recaptcha' ); ?></h3>
	<ul>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( C4WP_PLUGIN_URL . 'assets/img/wp-security-audit-log-img.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Keep a log of users and under the hood site activity.', 'advanced-nocaptcha-recaptcha' ); ?></p>
					<div class="cta-btn">
						<a href="https://melapress.com/wordpress-activity-log/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=c4wp" target="_blank"><?php esc_html_e( 'LEARN MORE', 'advanced-nocaptcha-recaptcha' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( C4WP_PLUGIN_URL . 'assets/img/wp-2fa.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Add an extra layer of security to your login pages with 2FA & require your users to use it.', 'advanced-nocaptcha-recaptcha' ); ?></p>
					<div class="cta-btn">
						<a href="https://melapress.com/wordpress-2fa/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=c4wp" target="_blank"><?php esc_html_e( 'LEARN MORE', 'advanced-nocaptcha-recaptcha' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( C4WP_PLUGIN_URL . 'assets/img/website-file-changes-monitor.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Automatically identify unauthorized file changes on your WordPress site.', 'advanced-nocaptcha-recaptcha' ); ?></p>
					<div class="cta-btn">
						<a href="https://wordpress.org/plugins/website-file-changes-monitor/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=c4wp" target="_blank"><?php esc_html_e( 'LEARN MORE', 'advanced-nocaptcha-recaptcha' ); ?></a>
					</div>
				</div>
			</div>
		</li>
	</ul>
</div>
