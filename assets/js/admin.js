function createCaptchaScripts( $captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false ) {
	if ( $captcha_version == 'v2_checkbox' || $captcha_version == 'v2_invisble' ) {
		var s = document.createElement("script");
		s.type = "text/javascript";
		s.src = "https://www.google.com/recaptcha/api.js?render=onload";
		s.id = 'gscripts';		
		jQuery("head").append(s);
	} else if ( $sitekey ) {
		var s = document.createElement("script");
		s.type = "text/javascript";
		s.src = "https://www.google.com/recaptcha/api.js?render=" + $sitekey;
		s.id = 'gscripts';		
		jQuery("head").append(s);
	}
}

function createRenderArea( $captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false  ) {

	if ( $is_fallback ) {
		jQuery( '#render-settings-placeholder-fallback' ).css( 'height', 'auto' );
		if ( $sitekey ) {
			if ( $captcha_version == 'v2_checkbox' ) {
				jQuery( '#render-settings-placeholder-fallback' ).html( '<div class="g-recaptcha" id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '" style="position: absolute; left: 220px;"></div>' ).css( 'height', '78px' );		
			} else if ( $captcha_version == 'v2_invisble' ) {
				jQuery( '#render-settings-placeholder-fallback' ).html( '<div class="g-recaptcha" id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '" data-size="invisible"></div>' );
			} else {
				jQuery( '#render-settings-placeholder-fallback' ).html( '<div id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '"></div>' );
				setTimeout(function() { 
					if ( ! jQuery( 'body .grecaptcha-badge' ).length ) {
						jQuery( '#c4wp-testrender-fb *' ).remove();
						jQuery( '#c4wp-testrender-fb' ).append( '<strong style="color: red" id="warning">Invalid site key</strong>' );
						jQuery( '#warning' ).attr( 'data-key-invalid', true );
					}
				}, 500);
				setTimeout(function() { 
					if ( jQuery( 'body .grecaptcha-badge' ).length ) {
						jQuery(  'body .grecaptcha-badge' ).detach().appendTo( '#c4wp-testrender-fb' )
					}
				}, 600);
			}
		}
	} else {
		jQuery( '.g-recaptcha, #gscripts, #c4wp-testrender, #warning, #render-wrapper' ).remove();
		jQuery('.grecaptcha-badge').parent().remove();
		jQuery( '#render-settings-placeholder' ).css( 'height', 'auto' );
		if ( $sitekey ) {
			if ( $captcha_version == 'v2_checkbox' ) {
				jQuery( '#render-settings-placeholder' ).html( '<div class="g-recaptcha" id="c4wp-testrender" data-sitekey="' + $sitekey + '" style="position: absolute; left: 220px;"></div>' ).css( 'height', '78px' );		
			} else if ( $captcha_version == 'v2_invisble' ) {
				jQuery( '#render-settings-placeholder' ).html( '<div class="g-recaptcha" id="c4wp-testrender" data-sitekey="' + $sitekey + '" data-size="invisible"></div>' );
			} else {
				jQuery( '#render-settings-placeholder' ).html( '<div id="c4wp-testrender" data-sitekey="' + $sitekey + '"></div>' );
				setTimeout(function() { 
					if ( ! jQuery( 'body .grecaptcha-badge' ).length ) {
						jQuery( '#c4wp-testrender *' ).remove();
						jQuery( '#c4wp-testrender' ).append( '<strong style="color: red" id="warning">Invalid site key</strong>' );
						jQuery( '#warning' ).attr( 'data-key-invalid', true );
					}
				}, 500);
				setTimeout(function() { 
					if ( jQuery( 'body .grecaptcha-badge' ).length ) {
						jQuery(  'body .grecaptcha-badge' ).detach().appendTo( '#c4wp-testrender' )
					}
				}, 600);
			}
		}
	}

}
function c4wpConfirm(dialogText, okFunc, cancelFunc, dialogTitle) {
	jQuery('<div style="padding: 10px; max-width: 500px; word-wrap: break-word;">' + dialogText + '</div>').dialog({
	  draggable: false,
	  modal: true,
	  dialogClass: "c4wp-alert",
	  resizable: false,
	  width: 'auto',
	  title: dialogTitle || 'Confirm',
	  minHeight: 75,
	  buttons: {
		OK: function () {
		  if (typeof (okFunc) == 'function') {
			setTimeout(okFunc, 50);
		  }
		  jQuery(this).dialog('destroy');
		},
		Cancel: function () {
		  if (typeof (cancelFunc) == 'function') {
			setTimeout(cancelFunc, 50);
		  }
		  jQuery(this).dialog('destroy');
		}
	  }
	});
}


function testSiteKeys( $captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false ) {
	createCaptchaScripts( $captcha_version, $sitekey, $is_fallback );
	createRenderArea( $captcha_version, $sitekey, $is_fallback );
}

function c4wp_admin_show_hide_failure_fields(){
	var selected_value = jQuery( '.c4wp-wizard-panel select[name="c4wp_admin_options[failure_action]"] option:selected' ).val();
	jQuery( '.c4wp-wizard-panel .toggleable' ).slideUp(300).addClass( 'disabled' );
	jQuery( '.c4wp-wizard-panel .toggletext' ).slideUp(300).addClass( 'disabled' );
	jQuery( '.c4wp-wizard-panel .c4wp-show-field-for-'+ selected_value ).slideDown(300).removeClass( 'disabled' );
	jQuery( '.c4wp-wizard-panel .toggleable' ).parent().slideUp(300).addClass( 'disabled' );
	jQuery( '.c4wp-wizard-panel .c4wp-show-field-for-'+ selected_value ).parent().slideDown(300).removeClass( 'disabled' );

	if ( jQuery( '#c4wp-setup-wizard-v3-fallback' ).hasClass( 'active' ) && 'nothing' == selected_value ) {
		jQuery( '.show-wizard [data-check-inputs]' ).attr( 'data-check-inputs', '' );
	} else {
		jQuery( '.show-wizard [data-check-inputs]' ).attr( 'data-check-inputs', '#c4wp_admin_options_failure_v2_site_key, #c4wp_admin_options_failure_v2_secret_key' );
	}

	if ( jQuery('#c4wp_admin_options_failed_login_enable').length ) {
		if ( document.getElementById('c4wp_admin_options_failed_login_enable').checked ) {
			jQuery( '.failed-captcha-count-input' ).removeClass( 'disabled' );
		} else {
			jQuery( '.failed-captcha-count-input' ).addClass( 'disabled' );
		}
	}
}

jQuery(document).ready(function( $ ){

	testSiteKeys( $('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), jQuery( '#c4wp_admin_options_site_key' ).attr( 'value' ) );

	$('input[name="c4wp_admin_options[site_key]"]').keyup(function(){
		testSiteKeys( $('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), $(this).val() );
	});

	$('input[name="c4wp_admin_options[failure_v2_site_key]"]').keyup(function(){
		testSiteKeys( 'v2_checkbox', $(this).val(), true );
	});
	
	$('.form-table').on( "change", '[name="c4wp_admin_options[captcha_version]"]', function(e) {	
		testSiteKeys( this.value, $('input[name="c4wp_admin_options[site_key]"]').val() );	
		c4wp_admin_show_hide_fields();
	});

	$( 'body' ).on( "change", 'select[name="c4wp_admin_options[failure_action]"]', function(e) {
		c4wp_admin_show_hide_failure_fields();
	});

	c4wp_admin_show_hide_failure_fields();

	jQuery( 'body' ).on( 'click', '[name="c4wp_admin_options[captcha_version]"]', function ( e ) {
		var radio = $(this);


		if ( ! jQuery( '#c4wp-admin-wrap' ).hasClass( 'captcha_keys_required' ) ) {
			e.preventDefault();
			c4wpConfirm( anrScripts.switchingWarning, function () {
				$(radio).prop('checked', true);
				$( '#c4wp_admin_options_site_key, #c4wp_admin_options_secret_key' ).attr( 'value', '' ).val( '' );
				testSiteKeys( $('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), jQuery( '#c4wp_admin_options_site_key' ).attr( 'value' ) );
				c4wp_admin_show_hide_fields();
				return true;
			}, function () {
				return false;
			},
			anrScripts.switchingWarningTitle		
			);
		}

	});

	$( 'body' ).on( "change", 'input#c4wp_admin_options_failed_login_enable', function(e) {
		c4wp_admin_show_hide_failure_fields();
	});
	
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
				return '<li>' + v + ' <span id="removeip" class="dashicons dashicons-no-alt" data-value="'+ v +'"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function buildWhitelistListURLs() {
		if ( $( '#c4wp_admin_options_whitelisted_urls' ).val() ) {
			var text = $('#c4wp_admin_options_whitelisted_urls').val();
			var output = text.split(',');
			$( '#whitelist-urls-userfacing' ).html('<ul>' + $.map(output, function(v) { 
				return '<li>' + v + ' <span id="removeurl" class="dashicons dashicons-no-alt" data-value="'+ v +'"></span></li>';
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

	jQuery('.captcha_keys_required .checkbox[id*="enabled_forms"]').change(function() {
       jQuery( '#captcha_keys_notice' ).slideDown( 500 );  
    });

	jQuery( 'body' ).on( 'click', '#captcha_keys_notice .button-secondary', function ( e ) {
		e.preventDefault();
		jQuery( '#captcha_keys_notice' ).slideUp( 500 );  
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

	if ( jQuery('#whitelist_ips_input').length ) {
		// Ensure only numbers and periods can be used.
		const ele = document.getElementById('whitelist_ips_input');
		ele.addEventListener('keypress', function (e) {
			const key = e.which || e.keyCode;
			if (key != 46 && (key < 48 || key > 57)) {
				e.preventDefault();
			}
		});
	}

	if ( jQuery('#c4wp_admin_options_failed_login_cron_schedule').length ) {
		// Ensure only numbers and periods can be used.
		const ele = document.getElementById('c4wp_admin_options_failed_login_cron_schedule');
		ele.addEventListener('keypress', function (e) {
			const key = e.which || e.keyCode;
			if ((key < 48 || key > 57)) {
				e.preventDefault();
			}
		});
	}


	jQuery( 'body' ).on( 'click', 'a#launch-c4wp-wizard', function ( e ) {
		e.preventDefault();

		showWizard();
	});

	function showWizard( goToIntro = false ) {
		if ( goToIntro ) {
			jQuery( '#c4wp-setup-wizard-intro' ).addClass( 'active' );
		} else {
			jQuery( '#c4wp-setup-wizard-intro' ).remove();
			jQuery( '#c4wp-setup-wizard-version-select' ).addClass( 'active' );
		}
		setTimeout(function() { 
			jQuery( '#c4wp-setup-wizard' ).addClass( 'show-wizard' );
		}, 100);
	}

	function validateURL(string) {
		try {
			const newUrl = new URL(string);
			return newUrl.protocol === 'http:' || newUrl.protocol === 'https:';
		  } catch (err) {
			return false;
		  }
	  }

	jQuery( 'body' ).on( 'click', '#c4wp-close-wizard, a[href="#c4wp-cancel-wizard"]', function ( e ) {
		e.preventDefault();
		jQuery( '#c4wp-setup-wizard' ).removeClass( 'show-wizard' );
	});

	jQuery( 'body' ).on( 'click', '#reset-c4wp-config', function ( e ) {
		e.preventDefault();
		c4wpConfirm(
			anrScripts.removeConfigWarning,
			function () {
				c4wp_reset_captcha_config();
				return true;
			},
			function () {
				return false;
			},
			anrScripts.removeConfigWarningTitle
		);
	});

	jQuery( 'body' ).on( 'click', 'a[data-wizard-goto]', function ( e ) {
		e.preventDefault();
		var targetDiv = jQuery( this ).attr( 'href' );
		var inputsToCheck = jQuery( this ).attr( 'data-check-inputs' );
		var current_fallback = jQuery( '#c4wp_admin_options_failure_action option:selected' ).val();

		if ( ! jQuery( '#c4wp-setup-wizard-v3-fallback' ).hasClass( 'active' ) ) {
			if ( targetDiv == '#c4wp-setup-wizard-additional-settings' ) {
				var currVal = jQuery('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
				if ( currVal == 'v3' ) {
					var targetDiv = '#c4wp-setup-wizard-v3-fallback';
				}
				c4wp_admin_show_hide_failure_fields();
			}	
		} else {
			if ( 'redirect' == current_fallback ) {
				if ( ! jQuery( '#c4wp_admin_options_failure_redirect' ).val() || ! validateURL( jQuery( '#c4wp_admin_options_failure_redirect' ).val() ) ) {
					jQuery( '#c4wp_admin_options_failure_redirect' ).css( 'border', '1px solid red' );
					return true;
				} else {
					jQuery( '#c4wp_admin_options_failure_redirect' ).css( 'border', '1px solid #8c8f94' );
					jQuery( this ).parent().removeClass('active').slideUp(200);
					jQuery( targetDiv ).addClass('active').slideDown(200);
				}
			} else if ( 'nothing' == current_fallback ) {
				jQuery( this ).parent().removeClass('active').slideUp(200);
				jQuery( targetDiv ).addClass('active').slideDown(200);
			}
		}

		if ( targetDiv == '#c4wp-setup-wizard-site-keys' ) {
			var currVal = jQuery('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
			if ( typeof currVal == 'undefined' ) {
				jQuery('input[name="c4wp_admin_options[captcha_version]"]').css( 'border', '1px solid red' );
				return true;
			} else {
				jQuery('input[name="c4wp_admin_options[captcha_version]"]').css( 'border', '1px solid #8c8f94' );
			}
		}

		if ( inputsToCheck || jQuery( 'body [data-key-invalid]' ).length ) {
			if ( ! jQuery( inputsToCheck ).val() || jQuery( 'body [data-key-invalid]' ).length ) {
				jQuery( inputsToCheck ).css( 'border', '1px solid red' );
			} else {
				jQuery( this ).parent().removeClass('active').slideUp(200);
				jQuery( targetDiv ).addClass('active').slideDown(200);
			}
		} else {
			jQuery( this ).parent().removeClass('active').slideUp(200);
			jQuery( targetDiv ).addClass('active').slideDown(200);
		}

		if ( jQuery( '#c4wp-setup-wizard-site-keys' ).hasClass( 'active' ) ) {
			jQuery( '#c4wp-setup-wizard-site-keys .button-primary[data-check-inputs]' ).attr( 'data-check-inputs', '#c4wp_admin_options_site_key, #c4wp_admin_options_secret_key' );			
		}
	});

	jQuery( 'body' ).on( 'click', 'a[href="#finish"]', function ( e ) {
		e.preventDefault();
		jQuery( '#c4wp-setup-wizard' ).removeClass( 'show-wizard' );
		jQuery( '#c4wp-admin-wrap form #submit' ).trigger('click');
	});
	
	function c4wp_reset_captcha_config() {
		var nonce      = jQuery( '#reset-c4wp-config' ).attr( 'data-nonce' );
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_reset_captcha_config',
				nonce: nonce,
			},
			success: function ( result ) {
				location.reload();
			}
		});
	}

	if ( jQuery( '#c4wp-admin-wrap' ).hasClass( 'show_wizard_on_load' ) ) {
		showWizard( true );
	}
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