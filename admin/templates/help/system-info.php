<?php
/**
 * System info area markup.
 *
 * @package C4WP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin adverts sidebar.
require_once 'sidebar.php';

?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'System information', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<form method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wsal-sysinfo"><?php echo esc_html( C4WP\C4WP_Functions::c4wp_get_sysinfo() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="ppmwp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'ppmwp-download-sysinfo', false ); ?>
		</p>
	</form>
	<script>

		function download(filename, text) {
			// Create temporary element.
			var element = document.createElement('a');
			element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
			element.setAttribute('download', filename);

			// Set the element to not display.
			element.style.display = 'none';
			document.body.appendChild(element);

			// Simlate click on the element.
			element.click();

			// Remove temporary element.
			document.body.removeChild(element);
		}
		jQuery( document ).ready( function() {
			var download_btn = jQuery( '#ppmwp-download-sysinfo' );
			download_btn.click( function( event ) {
				event.preventDefault();
				download( 'c4wp-system-info.txt', jQuery( '#system-info-textarea' ).val() );
			} );
		} );
		</script>
</div>
