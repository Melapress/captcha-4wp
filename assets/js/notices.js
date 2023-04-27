// Handle dismissal of admin notice
jQuery(function() {	

	jQuery( 'body' ).on( 'click', 'a[href="#dismiss-captcha-notice"], a[href="#c4wp-cancel-v3-failover-notice"]', function ( e ) {
		e.preventDefault();
		let ourButton  = jQuery( this );
		var nonce      = ourButton.attr( 'data-nonce' );
		var type       = ourButton.attr( 'data-notice-type' );
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_nocaptcha_plugin_notice_ignore',
				nonce: nonce,
				notice_type: type,
			},
			success: function ( result ) {
				jQuery( ourButton ).closest( '.notice' ).slideUp();
			}
		});
	});

	jQuery( 'body' ).on( 'click', 'a[href="#dismiss-upgrade-captcha-notice"]', function ( e ) {
		e.preventDefault();
		var ourButton  = jQuery( this );
		var nonce      = ourButton.attr( 'data-nonce' );
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_nocaptcha_upgrade_plugin_notice_ignore',
				nonce: nonce,
			},
			success: function ( result ) {
				jQuery( '#adv-captcha-notice' ).slideUp();
			}
		});
	});
});

