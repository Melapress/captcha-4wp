<?php
	// Plugin adverts sidebar
	require_once 'sidebar.php';
?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'System information', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<form method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wsal-sysinfo"><?php echo esc_html( c4wp_get_sysinfo() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="ppmwp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'ppmwp-download-sysinfo', false ); ?>
		</p>
	</form>
</div>
