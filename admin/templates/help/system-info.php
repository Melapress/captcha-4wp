<?php
/**
 * System info area markup.
 *
 * @package C4WP
 * @since 7.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'System information', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<form method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="c4wp-sysinfo"><?php echo esc_html( C4WP\C4WP_Functions::c4wp_get_sysinfo() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="c4wp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'c4wp-download-sysinfo', false ); ?>
		</p>
	</form>
	<script type="text/javascript">

		function download(filename, text) {
			// Create temporary element.
			var element = document.createElement('a');
			element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
			element.setAttribute('download', filename);

			// Set the element to not display.
			element.style.display = 'none';
			document.body.appendChild(element);

			// Simulate click on the element.
			element.click();

			// Remove temporary element.
			document.body.removeChild(element);
		}
		jQuery( document ).ready( function() {
			var download_btn = jQuery( '#c4wp-download-sysinfo' );
			download_btn.click( function( event ) {
				event.preventDefault();
				download( 'c4wp-system-info.txt', jQuery( '#system-info-textarea' ).val() );
			} );
		} );
		</script>
</div>
