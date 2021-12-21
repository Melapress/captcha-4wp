<?php
	// Plugin adverts sidebar
	require_once 'sidebar.php';
?>
<div class="c4wp-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'Contact Us', 'advanced-nocaptcha-recaptcha' ); ?></h2>
	</div>
	<?php if ( function_exists( 'c4wp_fs' ) ) : ?>	
		<style type="text/css">
			.fs-secure-notice {
				position: relative !important;
				top: 0 !important;
				left: 0 !important;
			}
			.fs-full-size-wrapper {
				margin: 10px 20px 0 2px !important;
			}
		</style>
		<?php
		$freemius_id = c4wp_fs()->get_id();
		$vars = array( 'id' => $freemius_id );
		echo fs_get_template( 'contact.php', $vars );
		?>
	<?php else :?>

	<?php endif; ?>
</div>
