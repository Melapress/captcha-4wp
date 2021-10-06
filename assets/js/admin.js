jQuery(document).ready(function( $ ){		
	function anr_admin_show_hide_fields(){
		var selected_value = $('#anr_admin_options_captcha_version').val();
		$( '.hidden' ).hide();
		$( '.anr-show-field-for-'+ selected_value ).show('slow');
	}
	if( $('#anr_admin_options_captcha_version').length ){
		anr_admin_show_hide_fields();
	}
	
	$('.form-table').on( "change", "#anr_admin_options_captcha_version", function(e) {
		anr_admin_show_hide_fields();
	});

});

// Handle dismissal of admin notice
jQuery(function() {
	jQuery( 'body' ).on( 'click', 'a[href="#dismiss-captcha-notice"]', function ( e ) {
		e.preventDefault();
		var ourButton  = jQuery( this );
		var nonce      = ourButton.attr( 'data-nonce' );
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'adv_nocaptcha_plugin_notice_ignore',
				nonce: nonce,
			},
			success: function ( result ) {
				jQuery( '#adv-captcha-notice' ).slideUp();
			}
		});
	});
});