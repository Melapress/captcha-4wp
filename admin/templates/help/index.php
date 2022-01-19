<div class="wrap help-wrap">
	<div class="nav-tab-wrapper">
		<?php
			// Get current tab
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'help';
		?>
		<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab<?php echo 'help' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'advanced-nocaptcha-recaptcha' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'system-info' ) ); ?>" class="nav-tab<?php echo 'system-info' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Info', 'advanced-nocaptcha-recaptcha' ); ?></a>
	</div>
	<div class="c4wp-help-section nav-tabs">
		<?php
			// Require page content. Default help.php
			require_once $current_tab . '.php';
		?>
	</div>
</div>
