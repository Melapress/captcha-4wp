<?php
	// Plugin adverts sidebar
	require_once 'sidebar.php';
	$admin_url          = ( function_exists( 'c4wp_same_settings_for_all_sites' ) ) ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
	$settings_admin_url = ( function_exists( 'c4wp_same_settings_for_all_sites' ) ) ? network_admin_url( 'admin.php?page=c4wp-admin-settings' ) : admin_url( 'admin.php?page=c4wp-admin-settings' );
?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2 style="margin-top: 15px; padding-left: 0;"><?php esc_html_e( 'Getting Started', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'Adding CAPTCHA checks on your website with CAPTCHA 4WP is really easy. All you need to do is:', 'c4wp-wpp' ); ?></p>
	<ol>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.google.com/recaptcha/admin#list' ), esc_html__( 'Configure the CAPTCHA & Get the API keys', 'advanced-nocaptcha-recaptcha' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( $admin_url ), esc_html__( 'Configure the CAPTCHA and add specify the keys in the plugin', 'advanced-nocaptcha-recaptcha' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( $settings_admin_url ), esc_html__( 'Configure on which pages you want to add the CAPTCHA test', 'advanced-nocaptcha-recaptcha' ) ); ?></li>
	</ol>
	<p>
		<?php esc_html_e( 'It should only take you a few minutes to get started. Should you encounter any problems or require assistance, you can use any of the following options:', 'advanced-nocaptcha-recaptcha' ); ?>
	</p>
	<!-- End -->
	<br>

	<!-- Plugin support -->
	<div class="title">
		<h2 style="padding-left: 0;"><?php esc_html_e( 'Plugin Support', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'You can post your question on our support forum or send us an email for 1 to 1 support. Email support is provided to both free and premium plugin users.', 'advanced-nocaptcha-recaptcha' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/advanced-nocaptcha-recaptcha/' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free support forum', 'advanced-nocaptcha-recaptcha' ); ?></a>
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/submit-ticket/?utm_source=plugin&utm_medium=referral&utm_campaign=C4WP&utm_content=free+support+email' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free email support', 'advanced-nocaptcha-recaptcha' ); ?></a>
	</div>
	<!-- End -->

	<br>
	<!-- Plugin documentation -->
	<div class="title">
		<h2 style="padding-left: 0;"><?php esc_html_e( 'Plugin Documentation', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'For more technical information about the WP Activity Log plugin please visit the plugin’s knowledge base. Refer to the list of WordPress security events for a complete list of Events and IDs that the plugin uses to keep a log of all the changes in the WordPress activity log.', 'advanced-nocaptcha-recaptcha' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/kb/?utm_source=plugin&utm_medium=referral&utm_campaign=C4WP&utm_content=knowledge+base' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Knowledge Base', 'advanced-nocaptcha-recaptcha' ); ?></a>
	</div>
	<!-- End -->
	<br>
	<!-- Plugin documentation -->
	<div class="title">
		<h2 style="padding-left: 0;"><?php esc_html_e( 'Rate CAPTCHA 4WP', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'We work really hard to deliver a plugin that enables you to add CAPTCHA checks and tests on your WordPress website to protect it against spam bots and other automated malicious attacks. It takes thousands of man-hours every year and an endless amount of dedication to research, develop and maintain the free edition of CAPTCHA 4WP. If you like what you see, and find CAPTCHA 4WP useful we ask you nothing more than to please rate our plugin. We appreciate every star!', 'advanced-nocaptcha-recaptcha' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/advanced-nocaptcha-recaptcha/reviews/?filter=5' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Rate plugin', 'advanced-nocaptcha-recaptcha' ); ?></a>
	</div>
	<!-- End -->

</div>
<style>
	#postbox-container-1 {
		display: none;
	}
</style>