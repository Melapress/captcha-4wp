jQuery(document).ready(function( $ ){

	// Tidy desc areas.	
	function tidySettingsDescs() {
        jQuery( '.premium-title-wrapper th' ).each(function(index, value) {
			var height = jQuery( this ).height();
			jQuery( this ).parent().css( 'height', height + 40 );
		});
		setTimeout(function() { 				
			jQuery( '.wrap-around-content' ).each(function(index, value) {
				var height = jQuery( this ).find('.c4wp-desc:first' ).height() - 20;
				jQuery( this ).find( '.c4wp-desc' ).parent().css( 'height', height );

				var height = jQuery( this ).find( 'strong:first' ).outerHeight() - 20;
                if ( height < 18 ) {
                    height = 18;
                }
				jQuery( this ).find( 'strong:first' ).parent().css( 'height', height );
			});
		}, 50);
	}
		
	function c4wp_admin_show_hide_fields(){
		var selected_value = $( '[name="c4wp_admin_options[captcha_version]"]:checked' ).val();
		$( '.toggleable' ).addClass( 'disabled' );
		$( '.c4wp-show-field-for-'+ selected_value ).removeClass( 'disabled' );
	}
	
	/**
	 * Handles checking and unchecking of role settings within the admin.
	 */
	function toggleRoleOptions() {
		var selected_value = $( '[name="c4wp_admin_options[loggedin_hide_selection]"]:checked' ).val();
		if ( selected_value == 'loggedin_hide_for_roles' ) {
			$( '.loggedin_hide.disabled' ).not( 'tr' ).removeClass( 'disabled' );
		} else {
			$( '.loggedin_hide' ).not( 'tr' ).addClass( 'disabled' );
		}
		
		var selected_lang_value = $( '[name="c4wp_admin_options[language_handling]"]:checked' ).val();
		if ( 'auto_detect' == selected_lang_value ) {
			$( 'select.lang_select' ).addClass( 'disabled' );
		} else {			
			$( 'select.lang_select' ).removeClass( 'disabled' );
		}	
	}

	/**
	 * Builds a nice list/interface for whitelisted IPs based on the value of the relevant textarea.
	 */
	 function buildWhitelistList() {
		if ( $( '#c4wp_admin_options_whitelisted_ips' ).val() ) {
			var text = $('#c4wp_admin_options_whitelisted_ips').val();
			var output = text.split(',');
			$( '#whitelist-ips-userfacing' ).html('<ul>' + $.map(output, function(v) { 
				return '<li>' + v + ' <span id="removeip" class="dashicons dashicons-no-alt" style="color: red" data-value="'+ v +'"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function buildWhitelistListURLs() {
		if ( $( '#c4wp_admin_options_whitelisted_urls' ).val() ) {
			var text = $('#c4wp_admin_options_whitelisted_urls').val();
			var output = text.split(',');
			$( '#whitelist-urls-userfacing' ).html('<ul>' + $.map(output, function(v) { 
				return '<li>' + v + ' <span id="removeurl" class="dashicons dashicons-no-alt" style="color: red" data-value="'+ v +'"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function moveLangPicker() {
		$( '.lang_select' ).appendTo( '#manually_choose + label' );
	}

	toggleRoleOptions();
	buildWhitelistList();
	buildWhitelistListURLs();
	moveLangPicker();
	tidySettingsDescs();

	jQuery(window).on('resize', function(){
		tidySettingsDescs();
	});

	// Toggle options on/off based on current captcha version.
	if( $( '[name="c4wp_admin_options[captcha_version]"]' ).length ){
		c4wp_admin_show_hide_fields();
	}	
	$('.form-table').on( "change", '[name="c4wp_admin_options[captcha_version]"]', function(e) {
		c4wp_admin_show_hide_fields();
	});	

	// Toggle checkboxes and incompatible settings when changed in admin.
	$('.form-table').on( "change", '#c4wp_admin_options_loggedin_hide_for_roles', function(e) {
		if ( $( this ).is(':checked') ) {
			$( '#c4wp_admin_options_loggedin_hide' ).prop( "checked", false );
		} else {
			$( '.loggedin_hide.disabled' ).removeClass( 'disabled' );
		}
	});
	$('.form-table').on( "change", '#c4wp_admin_options_loggedin_hide', function(e) {
		if ( $( this ).is(':checked') ) {
			$( '#c4wp_admin_options_loggedin_hide_for_roles' ).prop( "checked", false );
		} else {
			$( '.loggedin_hide' ).addClass( 'disabled' );
		}
	});
	$('.form-table').on( "change", '#c4wp_admin_options_auto_detect_lang', function(e) {
		if ( $( this ).is(':checked') ) {
			$( '#c4wp_admin_options_language' ).closest( 'tr' ).addClass( 'disabled' );
		} else {			
			$( '#c4wp_admin_options_language' ).closest( 'tr' ).removeClass( 'disabled' );
		}
	});

	// Toggle "hide for these roles" options based on input in admin.
	$('.form-table').on( "change", '[name="c4wp_admin_options[loggedin_hide_selection]"], [name="c4wp_admin_options[language_handling]"]', function(e) {
		toggleRoleOptions();
	});

	// Append newly added ips to neat list.
	$('.form-table').on( "change", '#c4wp_admin_options_whitelisted_ips', function(e) {
		buildWhitelistList()
	});

	// Append newly added URLs to neat list.
	$('.form-table').on( "change", '#c4wp_admin_options_whitelisted_urls', function(e) {
		buildWhitelistListURLs()
	});

	// Add new URL to whitelist.
	jQuery( 'body' ).on( 'click', 'a#add-url', function ( e ) {
		var newIP = $( '#whitelist_urls_input' ).val();
		e.preventDefault();

		if ( ! $( '#c4wp_admin_options_whitelisted_urls' ).val() ) {
			$( '#c4wp_admin_options_whitelisted_urls' ).append( newIP ).trigger("change");;
		} else {
			$( '#c4wp_admin_options_whitelisted_urls' ).append( ',' + newIP ).trigger("change");;
		}
		$( '#whitelist_urls_input' ).val( '' );
	});
	
	// Remove an IP from the list.
	jQuery( 'body' ).on( 'click', 'span#removeip', function ( e ) {
		var removingIP = $( this ).attr( 'data-value' );
		var textareaValue = $( '#c4wp_admin_options_whitelisted_ips' ).val();

		if( $( '#c4wp_admin_options_whitelisted_ips'  ).val().indexOf( ',' + removingIP ) > -1) {
			var newValue = textareaValue.replace( ',' + removingIP, '' );
		} else {
			var newValue = textareaValue.replace( removingIP, '' );
		}
		$( '#c4wp_admin_options_whitelisted_ips' ).val( newValue );
		$( this ).parent().remove();
	});

	jQuery( 'body' ).on( 'click', 'span#removeurl', function ( e ) {
		var removingIP = $( this ).attr( 'data-value' );
		var textareaValue = $( '#c4wp_admin_options_whitelisted_urls' ).val();

		if( $( '#c4wp_admin_options_whitelisted_urls'  ).val().indexOf( ',' + removingIP ) > -1) {
			var newValue = textareaValue.replace( ',' + removingIP, '' );
		} else {
			var newValue = textareaValue.replace( removingIP, '' );
		}
		$( '#c4wp_admin_options_whitelisted_urls' ).val( newValue );
		$( this ).parent().remove();
	});

	jQuery( 'body' ).on( 'click', 'input.disabled', function ( e ) {
		e.preventDefault();
	});
});



// Handle dismissal of admin notice
jQuery(function() {	
	/**
	 * Checks if the supplied value is valid.
	 */
	 function isIpAddressValid(ipAddress) {
		if (ipAddress == null || ipAddress == "")
			return false;
		var ip = '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}' +
			'(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$';
		if (ipAddress.match(ip) != null)
			return true;
	}

	jQuery( 'body' ).on( 'click', 'a[href="#dismiss-captcha-notice"]', function ( e ) {
		e.preventDefault();
		var ourButton  = jQuery( this );
		var nonce      = ourButton.attr( 'data-nonce' );
		
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_nocaptcha_plugin_notice_ignore',
				nonce: nonce,
			},
			success: function ( result ) {
				jQuery( '#network-captcha-notice' ).slideUp();
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

	// Add new IP to whitelist, if its oK.
	jQuery( 'body' ).on( 'click', 'a#add-ip', function ( e ) {
		e.preventDefault();
		var newIP = jQuery( '#whitelist_ips_input' ).val();

		if ( ! isIpAddressValid( newIP ) ) {
				alert( anrScripts.ipWarning );
			return;
		}

		if ( ! jQuery( '#c4wp_admin_options_whitelisted_ips' ).val() ) {
			jQuery( '#c4wp_admin_options_whitelisted_ips' ).append( newIP ).trigger("change");;
		} else {
			jQuery( '#c4wp_admin_options_whitelisted_ips' ).append( ',' + newIP ).trigger("change");;
		}
		jQuery( '#whitelist_ips_input' ).val( '' );
	});
});

/**
 * Onclick event handler to implement user's choice to either
 * opt in or out of freemius.
 *
 * @param {string} element - Current element.
 */
	function c4wp_freemius_opt_in( element ) {
	var nonce  = jQuery( '#c4wp-freemius-opt-nonce' ).val(); // Nonce.
	var choice = jQuery( element ).data( 'opt' ); // Choice.

	jQuery.ajax( {
		type: 'POST',
		url: ajaxurl,
		async: true,
		data: {
			action: 'c4wp_freemius_opt_in',
			opt_nonce: nonce,
			choice: choice
		},
		success: function( data ) {
			location.reload();
		},
		error: function( xhr, textStatus, error ) {
			console.log( xhr.statusText );
			console.log( textStatus );
			console.log( error );
		}
	} );
}