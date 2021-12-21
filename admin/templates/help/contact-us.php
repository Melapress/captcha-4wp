<?php
	// Plugin adverts sidebar
	require_once 'sidebar.php';
?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'Contact Us', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<?php 
	/* @free:start */
	?>
		<p><?php esc_html_e( 'Having trouble with our plugin? Feel free to get in touch via our contact form. We will be more than happy to assist you in anyway we can.', 'advanced-nocaptcha-recaptcha' ); ?></</p>
		<div class="btn">
			<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/contact/' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free support forum', 'advanced-nocaptcha-recaptcha' ); ?></a>
		</div>
	<?php
	/* @free:end */
	?>
</div>
