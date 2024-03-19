function createCaptchaScripts($captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false) {

	if ($captcha_version == 'v2_checkbox' || $captcha_version == 'v2_invisible') {

		if (jQuery('#cf-script').length) {
			jQuery('#cf-script').remove();
		}
		if (jQuery('#hcaptcha-render').length) {
			jQuery('#hcaptcha-render').remove();
		}
		if (jQuery('#cloudflare-render').length) {
			jQuery('#cloudflare-render').remove();
		}

		var s = document.createElement("script");
		s.type = "text/javascript";
		s.src = "https://www.google.com/recaptcha/api.js?render=onload";
		s.id = 'google-script';
		jQuery("head").append(s);

	} else if ($captcha_version == 'cloudflare') {

		if (jQuery('#google-script').length) {
			jQuery('#google-script').remove();
		}
		if (jQuery('#cf-script').length) {
			jQuery('#cf-script').remove();
		}
		if (jQuery('#hcaptcha-render').length) {
			jQuery('#hcaptcha-render').remove();
		}

		var s = document.createElement("script");
		s.type = "text/javascript";
		s.src = "https://challenges.cloudflare.com/turnstile/v0/api.js";
		s.id = 'cf-script';
		jQuery("head").append(s);

	} else if ($captcha_version == 'hcaptcha') {

		if (jQuery('#google-script').length) {
			jQuery('#google-script').remove();
		}
		if (jQuery('#cf-script').length) {
			jQuery('#cf-script').remove();
		}

		if (!jQuery('#hcaptcha-script').length) {
			var s = document.createElement("script");
			s.type = "text/javascript";
			s.src = "https://js.hcaptcha.com/1/api.js?render=onload";
			s.id = 'hcaptcha-script';
			jQuery("head").append(s);
		} else {
			hcaptcha.render('hcaptcha-render');
		}

	} else if ($sitekey) {
		var s = document.createElement("script");
		s.type = "text/javascript";
		s.src = "https://www.google.com/recaptcha/api.js?render=" + $sitekey;
		s.id = 'google-script';
		jQuery("head").append(s);
	}
}

function createRenderArea($captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false) {
	if ($is_fallback) {
		jQuery('#render-settings-placeholder-fallback').css('height', 'auto');
		if ($sitekey) {
			if ($captcha_version == 'v2_checkbox') {
				jQuery('#render-settings-placeholder-fallback').html('<div class="g-recaptcha" id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '" style="position: absolute; left: 220px;"></div>').css('height', '78px');
			} else if ($captcha_version == 'v2_invisible') {
				jQuery('#render-settings-placeholder-fallback').html('<div class="g-recaptcha" id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '" data-size="invisible"></div>');
			} else {
				jQuery('#render-settings-placeholder-fallback').html('<div id="c4wp-testrender-fb" data-sitekey="' + $sitekey + '"></div>');
				setTimeout(function() {
					if (!jQuery('body .grecaptcha-badge').length) {
						jQuery('#c4wp-testrender-fb *').remove();
						jQuery('#c4wp-testrender-fb').append('<strong style="color: red" id="warning">Invalid site key</strong>');
						jQuery('#warning').attr('data-key-invalid', true);
					}
				}, 500);
				setTimeout(function() {
					if (jQuery('body .grecaptcha-badge').length) {
						jQuery('body .grecaptcha-badge').detach().appendTo('#c4wp-testrender-fb')
					}
				}, 600);
			}
		}
	} else {
		if ($captcha_version == 'cloudflare') {
			jQuery('#render-settings-placeholder').css('height', 'auto');
			jQuery('#render-settings-placeholder').html('<div class="g-recaptcha cf-turnstile" id="cloudflare-render" data-sitekey="' + $sitekey + '" style="position: absolute; left: 220px;"></div>').css('height', '78px');
		} else if ($captcha_version == 'hcaptcha') {
			jQuery('#render-settings-placeholder').css('height', 'auto');
			jQuery('#render-settings-placeholder').html('<div class="h-captcha" id="hcaptcha-render" data-sitekey="' + $sitekey + '"></div>').css('height', '78px');
		} else {
			jQuery('.g-recaptcha, #gscripts, #c4wp-testrender, #warning, #render-wrapper').remove();
			jQuery('#render-settings-placeholder').html('');
			jQuery('.grecaptcha-badge').parent().remove();
			jQuery('#render-settings-placeholder').css('height', 'auto');
			if ($sitekey) {
				if ($captcha_version == 'v2_checkbox') {
					jQuery('#render-settings-placeholder').html('<div class="g-recaptcha" id="c4wp-testrender" data-sitekey="' + $sitekey + '" style="position: absolute; left: 220px;"></div>').css('height', '78px');
				} else if ($captcha_version == 'v2_invisible') {
					jQuery('#render-settings-placeholder').html('<div class="g-recaptcha" id="c4wp-testrender" data-sitekey="' + $sitekey + '" data-size="invisible"></div>');
				} else {
					jQuery('#render-settings-placeholder').html('<div id="c4wp-testrender" data-sitekey="' + $sitekey + '"></div>');
					setTimeout(function() {
						if (!jQuery('body .grecaptcha-badge').length) {
							jQuery('#c4wp-testrender *').remove();
							jQuery('#c4wp-testrender').append('<strong style="color: red" id="warning">Invalid site key</strong>');
							jQuery('#warning').attr('data-key-invalid', true);
						}
					}, 500);
					setTimeout(function() {
						if (jQuery('body .grecaptcha-badge').length) {
							jQuery('body .grecaptcha-badge').detach().appendTo('#c4wp-testrender')
						}
					}, 600);
				}
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
			OK: function() {
				if (typeof(okFunc) == 'function') {
					setTimeout(okFunc, 50);
				}
				jQuery(this).dialog('destroy');
			},
			Cancel: function() {
				if (typeof(cancelFunc) == 'function') {
					setTimeout(cancelFunc, 50);
				}
				jQuery(this).dialog('destroy');
			}
		}
	});
}


function testSiteKeys($captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false) {

	if ($sitekey.length < 5) {
		return;
	}

	if ($captcha_version == 'v2_checkbox' || $captcha_version == 'v2_invisible' || $captcha_version == 'v3') {
		createRenderArea($captcha_version, $sitekey, $is_fallback);
		createCaptchaScripts($captcha_version, $sitekey, $is_fallback);
	}
	if ($captcha_version == 'cloudflare') {
		createRenderArea($captcha_version, $sitekey, $is_fallback);
		createCaptchaScripts($captcha_version, $sitekey, $is_fallback);
		if (typeof turnstile != 'undefined') {
			turnstile.render('#cloudflare-render');
		}

	}
	if ($captcha_version == 'hcaptcha') {
		createRenderArea('hcaptcha', $sitekey, $is_fallback);
		createCaptchaScripts('hcaptcha', $sitekey, $is_fallback);
	}
}

function testSecretKeys($captcha_version = 'v2_checkbox', $sitekey = false, $is_fallback = false) {
	var currResponse = false;

	if ($captcha_version == 'hcaptcha') {
		var currResponse = jQuery(document).find('[data-hcaptcha-response]').attr('data-hcaptcha-response');
	} else if ($captcha_version == 'v3') {
		var currResponse = jQuery(document).find('.g-recaptcha-response').attr('data-response');
	} else if ($captcha_version == 'v2_invisible') {
		var currResponse = jQuery(document).find('.g-recaptcha-response').val();
	} else if ($captcha_version == 'cloudflare') {
		var currResponse = jQuery(document).find('input[name="cf-turnstile-response"]').attr('value');
	} else {
		var currResponse = jQuery(document).find('.g-recaptcha-response').val();
	}

	if (!currResponse) {
		return;
	}

	var formData = new FormData();
	formData.append('action', 'c4wp_validate_secret_key');
	formData.append('nonce', anrScripts.validate_secret_keys_nonce);
	formData.append('secret', jQuery('input[name="c4wp_admin_options[secret_key]"]').val());
	formData.append('response', currResponse);
	formData.append('method', $captcha_version);

	fetch(anrScripts.ajax_url, {
			method: 'POST',
			body: formData,
		}) // wrapped
		.then(
			res => res.json()
		)
		.then(data => {
			if (data['success']) {
				jQuery('input[name="c4wp_admin_options[secret_key]"]').css('border', '2px solid green');
				jQuery('#secret_key_validation_feedback').html('<span data-secret-validated style="color:green">Secret key validated</span>');

				setTimeout(function() {
					if ($captcha_version == 'v3') {
						jQuery('a[href="#c4wp-setup-wizard-v3-fallback"]').click();
					} else {
						jQuery('a[href="#c4wp-setup-wizard-additional-settings"]').click();
					}
				}, 500);
			} else {
				jQuery('input[name="c4wp_admin_options[secret_key]"]').css('border', '2px solid red');
				jQuery('#secret_key_validation_feedback').html('<span data-secret-not-valid style="color:red">Secret key invalid</span>');
			}
		})
		.catch(err => console.error(err));
}

function c4wp_admin_show_hide_failure_fields() {
	var selected_value = jQuery('.c4wp-wizard-panel select[name="c4wp_admin_options[failure_action]"] option:selected').val();
	jQuery('.c4wp-wizard-panel .toggleable').slideUp(300).addClass('disabled');
	jQuery('.c4wp-wizard-panel .toggletext').slideUp(300).addClass('disabled');
	jQuery('.c4wp-wizard-panel .c4wp-show-field-for-' + selected_value).slideDown(300).removeClass('disabled');
	jQuery('.c4wp-wizard-panel .toggleable').parent().slideUp(300).addClass('disabled');
	jQuery('.c4wp-wizard-panel .c4wp-show-field-for-' + selected_value).parent().slideDown(300).removeClass('disabled');

	if (jQuery('#c4wp-setup-wizard-v3-fallback').hasClass('active') && 'nothing' == selected_value) {
		jQuery('.show-wizard [data-check-inputs]').attr('data-check-inputs', '');
	} else {
		jQuery('.show-wizard [data-check-inputs]').attr('data-check-inputs', '#c4wp_admin_options_failure_v2_site_key, #c4wp_admin_options_failure_v2_secret_key');
	}
}

function c4wp_update_help_texts() {
	var currentMethod = $('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
	if (currentMethod == 'hcaptcha') {
		jQuery('.wizard_key_intro_text').html(anrScripts.hcaptcha_wizard_intro_text);
	} else if (currentMethod == 'cloudflare') {
		jQuery('.wizard_key_intro_text').html(anrScripts.cloudflare_wizard_intro_text);
	} else if (currentMethod == 'v2_checkbox') {
		jQuery('.wizard_key_intro_text').html(anrScripts.v2_checkbox_wizard_intro_text);
	} else {
		jQuery('.wizard_key_intro_text').html(anrScripts.recaptcha_wizard_intro_text);
	}
}

jQuery(document).ready(function($) {
	testSiteKeys($('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), jQuery('#c4wp_admin_options_site_key').attr('value'));

	$('input[name="c4wp_admin_options[site_key]"]').keyup(function() {
		testSiteKeys($('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), $(this).val());
	});

	$('input[name="c4wp_admin_options[secret_key]"]').keyup(function() {
		//testSecretKeys( $('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), $(this).val() );
	});

	$('input[name="c4wp_admin_options[failure_v2_site_key]"]').keyup(function() {
		testSiteKeys('v2_checkbox', $(this).val(), true);
	});

	$('.form-table').on("change", '[name="c4wp_admin_options[captcha_version]"]', function(e) {
		testSiteKeys(this.value, $('input[name="c4wp_admin_options[site_key]"]').val());
		c4wp_admin_show_hide_fields();
	});

	$('body').on("change", 'select[name="c4wp_admin_options[failure_action]"]', function(e) {
		c4wp_admin_show_hide_failure_fields();
	});

	jQuery('body').on('click', '[name="c4wp_admin_options[captcha_version]"]', function(e) {
		var radio = $(this);

		if (!jQuery('#c4wp-admin-wrap').hasClass('captcha_keys_required')) {
			e.preventDefault();
			c4wpConfirm(anrScripts.switchingWarning, function() {
					$(radio).prop('checked', true);
					$('#c4wp_admin_options_site_key, #c4wp_admin_options_secret_key').attr('value', '').val('');
					testSiteKeys($('input[name="c4wp_admin_options[captcha_version]"]:checked').val(), jQuery('#c4wp_admin_options_site_key').attr('value'));
					c4wp_admin_show_hide_fields();
					jQuery('#render-settings-placeholder *').remove();
					return true;
				}, function() {
					return false;
				},
				anrScripts.switchingWarningTitle
			);
		}

		var currentMethod = $('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
		if (currentMethod == 'hcaptcha') {
			jQuery('.wizard_key_intro_text').html(anrScripts.hcaptcha_wizard_intro_text);
		} else if (currentMethod == 'cloudflare') {
			jQuery('.wizard_key_intro_text').html(anrScripts.cloudflare_wizard_intro_text);
		} else {
			jQuery('.wizard_key_intro_text').html(anrScripts.recaptcha_wizard_intro_text);
		}

	});

	jQuery('body').on('click', 'a[href="#key-validation-step-2"]', function(e) {
		var currentMethod = $('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
		var warning = '<span id="key-warning" style="color: red">Please fulfill the captcha challenge to proceed</span>';
		var currResponse = false;
		var warningBit = jQuery(this);

		if (jQuery('body [data-key-invalid]').length) {
			e.preventDefault();
			return true;
		}

		if ('hcaptcha' == currentMethod) {
			var currResponse = jQuery(document).find('[data-hcaptcha-response]').attr('data-hcaptcha-response');
		} else if ('v3' == currentMethod) {
			grecaptcha.execute(
				$('input[name="c4wp_admin_options[site_key]"]').val(),
			).then(function(data) {
				jQuery(document).find('.g-recaptcha-response').attr('data-response', data);
			});
			var currResponse = jQuery(document).find('.g-recaptcha-response').attr('data-response');
		} else if ('v2_invisible' == currentMethod) {
			grecaptcha.execute();
			var currResponse = jQuery(document).find('.g-recaptcha-response').val();
		} else if ('cloudflare' == currentMethod) {
			var currResponse = jQuery(document).find('input[name="cf-turnstile-response"]').attr('value');
		} else {
			var currResponse = jQuery(document).find('.g-recaptcha-response').val();
		}

		// Pause to allow for response.
		if ('v2_invisible' == currentMethod || 'v3' == currentMethod) {
			if (!currResponse) {
				setTimeout(function() {
					var currResponse = jQuery(document).find('.g-recaptcha-response').attr('data-response');
					if (!currResponse) {
						var currResponse = jQuery(document).find('.g-recaptcha-response').val()
					}
					if (!currResponse) {
						if (!jQuery('#key-warning').length) {
							jQuery(warning).appendTo(warningBit.prev());
						}
						e.preventDefault();
						return;
					} else {
						jQuery('#key-validation-step-1').slideUp(300);
						jQuery('#key-validation-step-2').slideDown(300);
					}
				}, 1000);
			} else {
				jQuery('#key-validation-step-1').slideUp(300);
				jQuery('#key-validation-step-2').slideDown(300);
			}

		} else {
			if (!currResponse) {
				if (!jQuery('#key-warning').length) {
					jQuery(warning).appendTo(warningBit.prev());
				}
				e.preventDefault();
				return;
			} else {
				jQuery('#key-validation-step-1').slideUp(300);
				jQuery('#key-validation-step-2').slideDown(300);
			}
		}


	});

	jQuery('body').on('click', 'a[href="#key-validation-step-1"]', function(e) {
		jQuery('#key-validation-step-2').slideUp(300);
		jQuery('#key-validation-step-1').slideDown(300);
	});

	jQuery('body').on('click', 'a[href="#c4wp-setup-wizard-validate-secret-and-proceed"]', function(e) {
		var currentMethod = $('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
		testSecretKeys(currentMethod, $('input[name="c4wp_admin_options[secret_key]"]').val());
	});

	// Tidy desc areas.	
	function tidySettingsDescs() {
		jQuery('.premium-title-wrapper th').each(function(index, value) {
			var height = jQuery(this).height();
			jQuery(this).parent().css('height', height + 40);
		});
		setTimeout(function() {
			jQuery('.wrap-around-content').each(function(index, value) {
				var height = jQuery(this).find('.c4wp-desc:first').height() - 20;
				jQuery(this).find('.c4wp-desc').parent().css('height', height);

				var height = jQuery(this).find('strong:first').outerHeight() - 20;
				if (height < 18) {
					height = 18;
				}
				jQuery(this).find('strong:first').parent().css('height', height);
			});
		}, 50);

		if (anrScripts.captcha_version == 'v3') {
			jQuery('.c4wp-show-field-for-v3').removeClass('disabled');
		}

	}

	function c4wp_admin_show_hide_fields() {

		var selected_value = $('[name="c4wp_admin_options[captcha_version]"]:checked').val();
		$('.toggleable').addClass('disabled');
		$('.c4wp-show-field-for-' + selected_value).removeClass('disabled');

		if (selected_value != 'cloudflare') {
			$('#c4wp_admin_options_language').removeClass('disabled');
			$('#c4wp_admin_options_language_cloudflare').remove();
		}

		if (selected_value == 'v3' || selected_value == 'v2_checkbox' || selected_value == 'v2_invisible') {
			$('.c4wp-google-only-setting').removeClass('disabled');
		}

		var fb_selected_value = jQuery('.c4wp-wizard-panel select[name="c4wp_admin_options[failure_action]"] option:selected').val();
		if (fb_selected_value == 'v2_checkbox') {
			$('.c4wp-show-field-for-' + fb_selected_value).removeClass('disabled');
		}
	}

	/**
	 * Handles checking and unchecking of role settings within the admin.
	 */
	function toggleRoleOptions() {
		var selected_value = $('[name="c4wp_admin_options[loggedin_hide_selection]"]:checked').val();
		if (selected_value == 'loggedin_hide_for_roles') {
			$('.loggedin_hide.disabled').not('tr').removeClass('disabled');
		} else {
			$('.loggedin_hide').not('tr').addClass('disabled');
		}

		var selected_lang_value = $('[name="c4wp_admin_options[language_handling]"]:checked').val();
		if ('auto_detect' == selected_lang_value) {
			$('select.lang_select').addClass('disabled');
		} else {
			$('select.lang_select').removeClass('disabled');
		}
	}

	/**
	 * Builds a nice list/interface for whitelisted IPs based on the value of the relevant textarea.
	 */
	function buildWhitelistList() {
		if ($('#c4wp_admin_options_whitelisted_ips').val()) {
			var text = $('#c4wp_admin_options_whitelisted_ips').val();
			var output = text.split(',');
			$('#whitelist-ips-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="removeip" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function buildWhitelistListURLs() {
		if ($('#c4wp_admin_options_whitelisted_urls').val()) {
			var text = $('#c4wp_admin_options_whitelisted_urls').val();
			var output = text.split(',');
			$('#whitelist-urls-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="removeurl" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function buildDeniedCountries() {
		if ($('#c4wp_admin_options_denied_countries').val()) {
			var text = $('#c4wp_admin_options_denied_countries').val();
			var output = text.split(',');
			$('#denied-countries-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-denied-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
		if ($('#c4wp_admin_options_comment_rule_countries').val()) {
			var text = $('#c4wp_admin_options_comment_rule_countries').val();
			var output = text.split(',');
			$('#comment_denied-countries-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-comment_denied-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function buildAllowedCountries() {
		if ($('#c4wp_admin_options_allowed_countries').val()) {
			var text = $('#c4wp_admin_options_allowed_countries').val();
			var output = text.split(',');
			$('#allowed-countries-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-allowed-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
		if ($('#c4wp_admin_options_comment_allowed_countries').val()) {
			var text = $('#c4wp_admin_options_comment_allowed_countries').val();
			var output = text.split(',');
			$('#comment_allowed-countries-userfacing').html('<ul>' + $.map(output, function(v) {
				return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-comment_allowed-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
			}).join('') + '</ul>');
		}
	}

	function moveLangPicker() {
		$('.lang_select').appendTo('#manually_choose + label');
	}

	toggleRoleOptions();
	buildWhitelistList();
	buildWhitelistListURLs();
	moveLangPicker();
	tidySettingsDescs();

	buildDeniedCountries();
	buildAllowedCountries();

	jQuery(window).on('resize', function() {
		tidySettingsDescs();
	});

	// Once more, for good measure.
	setTimeout(function() {
		tidySettingsDescs();
	}, 500);

	// Toggle options on/off based on current captcha version.
	if ($('[name="c4wp_admin_options[captcha_version]"]').length) {
		c4wp_admin_show_hide_fields();
	}
	$('.form-table').on("change", '[name="c4wp_admin_options[captcha_version]"]', function(e) {
		c4wp_admin_show_hide_fields();
	});

	// Toggle checkboxes and incompatible settings when changed in admin.
	$('.form-table').on("change", '#c4wp_admin_options_loggedin_hide_for_roles', function(e) {
		if ($(this).is(':checked')) {
			$('#c4wp_admin_options_loggedin_hide').prop("checked", false);
		} else {
			$('.loggedin_hide.disabled').removeClass('disabled');
		}
	});
	$('.form-table').on("change", '#c4wp_admin_options_loggedin_hide', function(e) {
		if ($(this).is(':checked')) {
			$('#c4wp_admin_options_loggedin_hide_for_roles').prop("checked", false);
		} else {
			$('.loggedin_hide').addClass('disabled');
		}
	});
	$('.form-table').on("change", '#c4wp_admin_options_auto_detect_lang', function(e) {
		if ($(this).is(':checked')) {
			$('#c4wp_admin_options_language').closest('tr').addClass('disabled');
		} else {
			$('#c4wp_admin_options_language').closest('tr').removeClass('disabled');
		}
	});

	// Toggle "hide for these roles" options based on input in admin.
	$('.form-table').on("change", '[name="c4wp_admin_options[loggedin_hide_selection]"], [name="c4wp_admin_options[language_handling]"]', function(e) {
		toggleRoleOptions();
	});

	// Append newly added ips to neat list.
	$('.form-table').on("change", '#c4wp_admin_options_whitelisted_ips', function(e) {
		buildWhitelistList()
	});

	// Append newly added URLs to neat list.
	$('.form-table').on("change", '#c4wp_admin_options_whitelisted_urls', function(e) {
		buildWhitelistListURLs()
	});

	$('.form-table').on("change", '#c4wp_admin_options_denied_countries, #c4wp_admin_options_comment_rule_countries', function(e) {
		buildDeniedCountries()
	});

	$('.form-table').on("change", '#c4wp_admin_options_allowed_countries, #c4wp_admin_options_comment_allowed_countries', function(e) {
		buildAllowedCountries()
	});

	// Add new URL to whitelist.
	jQuery('body').on('click', 'a#add-url', function(e) {
		var newIP = $('#whitelist_urls_input').val();
		e.preventDefault();

		if (isUrlValid(newIP)) {
			$('#whitelist_urls_input').css('border', '1px solid red');
			return;
		} else {
			$('#whitelist_urls_input').css('border', '1px solid #8c8f94');
		}

		if (!$('#c4wp_admin_options_whitelisted_urls').val()) {
			$('#c4wp_admin_options_whitelisted_urls').append(newIP).trigger("change");;
		} else {
			$('#c4wp_admin_options_whitelisted_urls').append(',' + newIP).trigger("change");;
		}
		$('#whitelist_urls_input').val('');
	});

	// Remove an IP from the list.
	jQuery('body').on('click', 'span#removeip', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#c4wp_admin_options_whitelisted_ips').val();

		if ($('#c4wp_admin_options_whitelisted_ips').val().indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		$('#c4wp_admin_options_whitelisted_ips').val(newValue);
		$(this).parent().remove();
	});

	jQuery('body').on('click', 'span#removeurl', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#c4wp_admin_options_whitelisted_urls').val();

		if ($('#c4wp_admin_options_whitelisted_urls').val().indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		$('#c4wp_admin_options_whitelisted_urls').val(newValue);
		$(this).parent().remove();
	});

	jQuery('body').on('click', 'input.disabled', function(e) {
		e.preventDefault();
	});

	jQuery('.captcha_keys_required .checkbox[id*="enabled_forms"]').change(function() {
		jQuery('#captcha_keys_notice').slideDown(500);
	});

	jQuery('body').on('click', '#captcha_keys_notice .button-secondary', function(e) {
		e.preventDefault();
		jQuery('#captcha_keys_notice').slideUp(500);
	});

	// jQuery( 'tr th:empty' ).parent( 'tr' ).remove();


	if (jQuery('.c4wp-settings-tab-wrapper').length) {
		if (window.location.href.indexOf("hide-captcha-settings") > -1) {
			jQuery('a[href="?page=c4wp-admin-settings&tab=login-settings"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=general-settings"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=hide-captcha-settings"]').addClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=integrations"]').removeClass('nav-tab-active');
			jQuery('.sub-section-hide_captcha').addClass('not-hidden');
		} else if (window.location.href.indexOf("general-settings") > -1) {
			jQuery('a[href="?page=c4wp-admin-settings&tab=login-settings"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=general-settings"]').addClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=integrations"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=hide-captcha-settings"]').removeClass('nav-tab-active');
			jQuery('.sub-section-general_settings').addClass('not-hidden');
		} else if (window.location.href.indexOf("integrations") > -1) {
			jQuery('a[href="?page=c4wp-admin-settings&tab=login-settings"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=integrations"]').removeClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=integrations"]').addClass('nav-tab-active');
			jQuery('a[href="?page=c4wp-admin-settings&tab=hide-captcha-settings"]').removeClass('nav-tab-active');
			jQuery('.sub-section-integrations').addClass('not-hidden');
		} else {
			if ( jQuery('.sub-section-logins').length ) {
				jQuery('.sub-section-logins:not(:first)').addClass('not-hidden');
			} else {
				jQuery('.sub-section-general_settings').addClass('not-hidden');
			}			
		}
		setTimeout(() => {
			tidySettingsDescs();
			console.log('1');
		}, 500);
	} else {
		jQuery('.sub-section-general_settings').addClass('not-hidden');
	}

	jQuery('body').on("change", '#c4wp_admin_options_comment_handling', function(e) {
		var currentState = jQuery('#c4wp_admin_options_comment_handling').find(":selected").val();
		if ('deny_to_error' != currentState) {
			jQuery('#c4wp_admin_options_comment_blocked_message').closest('tr').addClass('disabled');
		} else {
			jQuery('#c4wp_admin_options_comment_blocked_message').closest('tr').removeClass('disabled');
		}
	});

	if (window.location.href.indexOf("comment-form-settings") > -1) {
		var currentState = jQuery('#c4wp_admin_options_comment_handling').find(":selected").val();
		if ('deny_to_spam' != currentState) {
			jQuery('#c4wp_admin_options_comment_blocked_message').closest('tr').fadeOut();
		}
		jQuery('a[href="?page=c4wp-admin-forms&tab=comment-form-settings"]').addClass('nav-tab-active');
		jQuery('.sub-section-comment-form-settings:not(.remain-hidden)').fadeIn(200);
		hideShowCommentCountryInputs();
	} else {
		jQuery('a[href="?page=c4wp-admin-forms&tab=forms-placements"]').addClass('nav-tab-active');
		jQuery('.sub-section-forms-placements').fadeIn(200);
		setTimeout(() => {
			hideShowCountryInputs();
		}, 200);
	}

	jQuery('body').on('click', 'span#remove-denied-country', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#c4wp_admin_options_denied_countries').text();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');

		$('#c4wp_admin_options_denied_countries').text(newValue);
		$(this).parent().remove();
	});

	jQuery('body').on('click', 'span#remove-allowed-country', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#c4wp_admin_options_allowed_countries').text();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');

		$('#c4wp_admin_options_allowed_countries').text(newValue);
		$(this).parent().remove();
	});

	jQuery('body').on('click', 'span#remove-comment_denied-country', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#c4wp_admin_options_comment_rule_countries').text();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');

		$('#c4wp_admin_options_comment_rule_countries').text(newValue);
		$(this).parent().remove();
	});

	jQuery('body').on('click', 'span#remove-comment_allowed-country', function(e) {
		var removingIP = $(this).attr('data-value');
		var textareaValue = $('#comment_allowed_countries_input').text();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');

		$('#c4wp_admin_options_comment_allowed_countries').text(newValue);
		$(this).parent().remove();
	});

	jQuery('#allowed_countries_input').closest('tr').removeClass('not-hidden');
	jQuery('#denied_countries_input, #c4wp_admin_options_denied_countries').closest('tr').removeClass('not-hidden');


	jQuery('body').on("change", '#c4wp_admin_options_denied_countries_method', function(e) {
		hideShowCountryInputs();
	});

	jQuery('body').on("change", '#c4wp_admin_options_comment_rule_countries_method', function(e) {
		hideShowCommentCountryInputs();
	});

	jQuery('#denied_countries_input').keypress(function(e) {
		var regex = new RegExp("^[a-zA-Z]+$");
		var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
		var len = jQuery('#denied_countries_input').val().length;
		jQuery('#denied_countries_input').val(jQuery('#denied_countries_input').val().toUpperCase());
		if (regex.test(str) && len < 2) {
			return true;
		} else {
			e.preventDefault();
			return false;
		}
	});
	jQuery('#allowed_countries_input').keypress(function(e) {
		var regex = new RegExp("^[a-zA-Z]+$");
		var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
		var len = jQuery('#allowed_countries_input').val().length;
		jQuery('#allowed_countries_input').val(jQuery('#allowed_countries_input').val().toUpperCase());
		if (regex.test(str) && len < 2) {
			return true;
		} else {
			e.preventDefault();
			return false;
		}
	});
	jQuery('#comment_rule_countries_input').keypress(function(e) {
		var regex = new RegExp("^[a-zA-Z]+$");
		var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
		var len = jQuery('#comment_rule_countries_input').val().length;
		jQuery('#comment_rule_countries_input').val(jQuery('#comment_rule_countries_input').val().toUpperCase());
		if (regex.test(str) && len < 2) {
			return true;
		} else {
			e.preventDefault();
			return false;
		}
	});
	jQuery('#comment_allowed_countries_input').keypress(function(e) {
		var regex = new RegExp("^[a-zA-Z]+$");
		var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
		var len = jQuery('#comment_allowed_countries_input').val().length;
		jQuery('#comment_allowed_countries_input').val(jQuery('#comment_allowed_countries_input').val().toUpperCase());
		if (regex.test(str) && len < 2) {
			return true;
		} else {
			e.preventDefault();
			return false;
		}
	});

	jQuery('#c4wp_admin_options_iplocate_api_key').keypress(function(e) {
		var regex = new RegExp("^[a-z0-9]+$");
		var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
		var len = jQuery('#c4wp_admin_options_iplocate_api_key').val().length;
		if (regex.test(str) && len < 32) {
			return true;
		} else {
			e.preventDefault();
			return false;
		}
	});
});

function hideShowCountryInputs() {
	var currentValue = jQuery('#c4wp_admin_options_denied_countries_method').find(":selected").val();
	jQuery('#denied_countries_input, #allowed_countries_input, #c4wp_admin_options_denied_countries, #c4wp_admin_options_allowed_countries').closest('tr').fadeOut(0);

	if (currentValue == 'allow_only') {
		jQuery('#allowed_countries_input').closest('tr').fadeIn();
	} else {
		jQuery('#denied_countries_input').closest('tr').fadeIn();
	}
}

function hideShowCommentCountryInputs() {
	var currentValue = jQuery('#c4wp_admin_options_comment_rule_countries_method').find(":selected").val();
	if (currentValue == 'deny_to_error' || currentValue == 'allow_only') {
		jQuery('#c4wp_admin_options_comment_blocked_message').closest('tr').fadeIn(0);
	} else {
		jQuery('#c4wp_admin_options_comment_blocked_message').closest('tr').fadeOut(0);
	}
}

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

	function getCodesList(justReturnCodes = false) {
		var availableCodes = {
			'Afghanistan': 'AF',
			'Åland Islands': 'AX',
			'Albania': 'AL',
			'Algeria': 'DZ',
			'American Samoa': 'AS',
			'Andorra': 'AD',
			'Angola': 'AO',
			'Anguilla': 'AI',
			'Antarctica': 'AQ',
			'Antigua and Barbuda': 'AG',
			'Argentina': 'AR',
			'Armenia': 'AM',
			'Aruba': 'AW',
			'Australia': 'AU',
			'Austria': 'AT',
			'Azerbaijan': 'AZ',
			'Bahamas': 'BS',
			'Bahrain': 'BH',
			'Bangladesh': 'BD',
			'Barbados': 'BB',
			'Belarus': 'BY',
			'Belgium': 'BE',
			'Belize': 'BZ',
			'Benin': 'BJ',
			'Bermuda': 'BM',
			'Bhutan': 'BT',
			'Bolivia, Plurinational State of': 'BO',
			'Bonaire, Sint Eustatius and Saba': 'BQ',
			'Bosnia and Herzegovina': 'BA',
			'Botswana': 'BW',
			'Bouvet Island': 'BV',
			'Brazil': 'BR',
			'British Indian Ocean Territory': 'IO',
			'Brunei Darussalam': 'BN',
			'Bulgaria': 'BG',
			'Burkina Faso': 'BF',
			'Burundi': 'BI',
			'Cambodia': 'KH',
			'Cameroon': 'CM',
			'Canada': 'CA',
			'Cape Verde': 'CV',
			'Cayman Islands': 'KY',
			'Central African Republic': 'CF',
			'Chad': 'TD',
			'Chile': 'CL',
			'China': 'CN',
			'Christmas Island': 'CX',
			'Cocos (Keeling) Islands': 'CC',
			'Colombia': 'CO',
			'Comoros': 'KM',
			'Congo': 'CG',
			'Congo, the Democratic Republic of the': 'CD',
			'Cook Islands': 'CK',
			'Costa Rica': 'CR',
			'Côte d Ivoire': 'CI',
			'Croatia': 'HR',
			'Cuba': 'CU',
			'Curaçao': 'CW',
			'Cyprus': 'CY',
			'Czech Republic': 'CZ',
			'Denmark': 'DK',
			'Djibouti': 'DJ',
			'Dominica': 'DM',
			'Dominican Republic': 'DO',
			'Ecuador': 'EC',
			'Egypt': 'EG',
			'El Salvador': 'SV',
			'Equatorial Guinea': 'GQ',
			'Eritrea': 'ER',
			'Estonia': 'EE',
			'Ethiopia': 'ET',
			'Falkland Islands (Malvinas)': 'FK',
			'Faroe Islands': 'FO',
			'Fiji': 'FJ',
			'Finland': 'FI',
			'France': 'FR',
			'French Guiana': 'GF',
			'French Polynesia': 'PF',
			'French Southern Territories': 'TF',
			'Gabon': 'GA',
			'Gambia': 'GM',
			'Georgia': 'GE',
			'Germany': 'DE',
			'Ghana': 'GH',
			'Gibraltar': 'GI',
			'Greece': 'GR',
			'Greenland': 'GL',
			'Grenada': 'GD',
			'Guadeloupe': 'GP',
			'Guam': 'GU',
			'Guatemala': 'GT',
			'Guernsey': 'GG',
			'Guinea': 'GN',
			'Guinea-Bissau': 'GW',
			'Guyana': 'GY',
			'Haiti': 'HT',
			'Heard Island and McDonald Islands': 'HM',
			'Holy See (Vatican City State)': 'VA',
			'Honduras': 'HN',
			'Hong Kong': 'HK',
			'Hungary': 'HU',
			'Iceland': 'IS',
			'India': 'IN',
			'Indonesia': 'ID',
			'Iran, Islamic Republic of': 'IR',
			'Iraq': 'IQ',
			'Ireland': 'IE',
			'Isle of Man': 'IM',
			'Israel': 'IL',
			'Italy': 'IT',
			'Jamaica': 'JM',
			'Japan': 'JP',
			'Jersey': 'JE',
			'Jordan': 'JO',
			'Kazakhstan': 'KZ',
			'Kenya': 'KE',
			'Kiribati': 'KI',
			'Korea, Democratic Peoples Republic of': 'KP',
			'Korea, Republic of': 'KR',
			'Kuwait': 'KW',
			'Kyrgyzstan': 'KG',
			'Lao Peoples Democratic Republic': 'LA',
			'Latvia': 'LV',
			'Lebanon': 'LB',
			'Lesotho': 'LS',
			'Liberia': 'LR',
			'Libya': 'LY',
			'Liechtenstein': 'LI',
			'Lithuania': 'LT',
			'Luxembourg': 'LU',
			'Macao': 'MO',
			'Macedonia, the Former Yugoslav Republic of': 'MK',
			'Madagascar': 'MG',
			'Malawi': 'MW',
			'Malaysia': 'MY',
			'Maldives': 'MV',
			'Mali': 'ML',
			'Malta': 'MT',
			'Marshall Islands': 'MH',
			'Martinique': 'MQ',
			'Mauritania': 'MR',
			'Mauritius': 'MU',
			'Mayotte': 'YT',
			'Mexico': 'MX',
			'Micronesia, Federated States of': 'FM',
			'Moldova, Republic of': 'MD',
			'Monaco': 'MC',
			'Mongolia': 'MN',
			'Montenegro': 'ME',
			'Montserrat': 'MS',
			'Morocco': 'MA',
			'Mozambique': 'MZ',
			'Myanmar': 'MM',
			'Namibia': 'NA',
			'Nauru': 'NR',
			'Nepal': 'NP',
			'Netherlands': 'NL',
			'New Caledonia': 'NC',
			'New Zealand': 'NZ',
			'Nicaragua': 'NI',
			'Niger': 'NE',
			'Nigeria': 'NG',
			'Niue': 'NU',
			'Norfolk Island': 'NF',
			'Northern Mariana Islands': 'MP',
			'Norway': 'NO',
			'Oman': 'OM',
			'Pakistan': 'PK',
			'Palau': 'PW',
			'Palestine, State of': 'PS',
			'Panama': 'PA',
			'Papua New Guinea': 'PG',
			'Paraguay': 'PY',
			'Peru': 'PE',
			'Philippines': 'PH',
			'Pitcairn': 'PN',
			'Poland': 'PL',
			'Portugal': 'PT',
			'Puerto Rico': 'PR',
			'Qatar': 'QA',
			'Réunion': 'RE',
			'Romania': 'RO',
			'Russian Federation': 'RU',
			'Rwanda': 'RW',
			'Saint Barthélemy': 'BL',
			'Saint Helena, Ascension and Tristan da Cunha': 'SH',
			'Saint Kitts and Nevis': 'KN',
			'Saint Lucia': 'LC',
			'Saint Martin (French part)': 'MF',
			'Saint Pierre and Miquelon': 'PM',
			'Saint Vincent and the Grenadines': 'VC',
			'Samoa': 'WS',
			'San Marino': 'SM',
			'Sao Tome and Principe': 'ST',
			'Saudi Arabia': 'SA',
			'Senegal': 'SN',
			'Serbia': 'RS',
			'Seychelles': 'SC',
			'Sierra Leone': 'SL',
			'Singapore': 'SG',
			'Sint Maarten (Dutch part)': 'SX',
			'Slovakia': 'SK',
			'Slovenia': 'SI',
			'Solomon Islands': 'SB',
			'Somalia': 'SO',
			'South Africa': 'ZA',
			'South Georgia and the South Sandwich Islands': 'GS',
			'South Sudan': 'SS',
			'Spain': 'ES',
			'Sri Lanka': 'LK',
			'Sudan': 'SD',
			'Suriname': 'SR',
			'Svalbard and Jan Mayen': 'SJ',
			'Swaziland': 'SZ',
			'Sweden': 'SE',
			'Switzerland': 'CH',
			'Syrian Arab Republic': 'SY',
			'Taiwan, Province of China': 'TW',
			'Tajikistan': 'TJ',
			'Tanzania, United Republic of': 'TZ',
			'Thailand': 'TH',
			'Timor-Leste': 'TL',
			'Togo': 'TG',
			'Tokelau': 'TK',
			'Tonga': 'TO',
			'Trinidad and Tobago': 'TT',
			'Tunisia': 'TN',
			'Turkey': 'TR',
			'Turkmenistan': 'TM',
			'Turks and Caicos Islands': 'TC',
			'Tuvalu': 'TV',
			'Uganda': 'UG',
			'Ukraine': 'UA',
			'United Arab Emirates': 'AE',
			'United Kingdom': 'GB',
			'United States': 'US',
			'United States Minor Outlying Islands': 'UM',
			'Uruguay': 'UY',
			'Uzbekistan': 'UZ',
			'Vanuatu': 'VU',
			'Venezuela, Bolivarian Republic of': 'VE',
			'Viet Nam': 'VN',
			'Virgin Islands, British': 'VG',
			'Virgin Islands, U.S.': 'VI',
			'Wallis and Futuna': 'WF',
			'Western Sahara': 'EH',
			'Yemen': 'YE',
			'Zambia': 'ZM',
			'Zimbabwe': 'ZW',
		};

		if (justReturnCodes) {
			var list = getCodesList();
			var justCodes = [];

			jQuery.each(list, function(key, value) {
				justCodes.push(value);
			});

			availableCodes = justCodes;
		}

		return availableCodes;
	};

	function isIpAddressValidIPv6(str) {
		// Regular expression to check if string is a IPv6 address
		const regexExp = /(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))/gi;

		return regexExp.test(str);
	}

	jQuery('body').on('click', 'a[href="#dismiss-captcha-notice"], a[href="#c4wp-cancel-v3-failover-notice"]', function(e) {
		e.preventDefault();
		let ourButton = jQuery(this);
		var nonce = ourButton.attr('data-nonce');
		var type = ourButton.attr('data-notice-type');

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_nocaptcha_plugin_notice_ignore',
				nonce: nonce,
				notice_type: type,
			},
			success: function(result) {
				jQuery(ourButton).closest('.notice').slideUp();
			}
		});
	});

	jQuery('body').on('click', 'a[href="#dismiss-upgrade-captcha-notice"]', function(e) {
		e.preventDefault();
		var ourButton = jQuery(this);
		var nonce = ourButton.attr('data-nonce');

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_nocaptcha_upgrade_plugin_notice_ignore',
				nonce: nonce,
			},
			success: function(result) {
				jQuery('#adv-captcha-notice').slideUp();
			}
		});
	});

	// Add new IP to whitelist, if its oK.
	jQuery('body').on('click', 'a#add-ip', function(e) {
		e.preventDefault();
		var newIP = jQuery('#whitelist_ips_input').val();

		if (!isIpAddressValid(newIP) && !isIpAddressValidIPv6(newIP)) {
			alert(anrScripts.ipWarning);
			return;
		}

		if (!jQuery('#c4wp_admin_options_whitelisted_ips').val()) {
			jQuery('#c4wp_admin_options_whitelisted_ips').append(newIP).trigger("change");;
		} else {
			jQuery('#c4wp_admin_options_whitelisted_ips').append(',' + newIP).trigger("change");;
		}
		jQuery('#whitelist_ips_input').val('');
	});

	jQuery('body').on('click', 'a#add-denied-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#denied_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#c4wp_admin_options_denied_countries').text();

		if (currentVal.indexOf(newIP) != -1) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if (!jQuery('#c4wp_admin_options_denied_countries').val()) {
			jQuery('#c4wp_admin_options_denied_countries').append(newIP).trigger("change");;
		} else {
			jQuery('#c4wp_admin_options_denied_countries').append(',' + newIP).trigger("change");;
		}
		jQuery('#denied_countries_input').val('');
	});

	jQuery('body').on('click', 'a#add-comment_denied-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#comment_rule_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#c4wp_admin_options_comment_rule_countries').text();

		if (currentVal.indexOf(newIP) != -1) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-comment_denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if (!jQuery('#c4wp_admin_options_comment_rule_countries').val()) {
			jQuery('#c4wp_admin_options_comment_rule_countries').append(newIP).trigger("change");;
		} else {
			jQuery('#c4wp_admin_options_comment_rule_countries').append(',' + newIP).trigger("change");;
		}
		jQuery('#comment_rule_countries_input').val('');
	});


	jQuery('body').on('click', 'a#add-comment_allowed-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#comment_allowed_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#c4wp_admin_options_comment_allowed_countries').text();

		if (currentVal.indexOf(newIP) != -1) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-comment_allowed-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if (!jQuery('#c4wp_admin_options_comment_allowed_countries').val()) {
			jQuery('#c4wp_admin_options_comment_allowed_countries').append(newIP).trigger("change");;
		} else {
			jQuery('#c4wp_admin_options_comment_allowed_countries').append(',' + newIP).trigger("change");;
		}
		jQuery('#comment_allowed_countries_input').val('');
	});

	jQuery('body').on('click', 'a#add-allowed-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#allowed_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#c4wp_admin_options_allowed_countries').text();

		if (currentVal.indexOf(newIP) != -1) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-allowed-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if (!jQuery('#c4wp_admin_options_allowed_countries').val()) {
			jQuery('#c4wp_admin_options_allowed_countries').append(newIP).trigger("change");;
		} else {
			jQuery('#c4wp_admin_options_allowed_countries').append(',' + newIP).trigger("change");;
		}
		jQuery('#allowed_countries_input').val('');
	});

	jQuery('body').on('click', 'a#launch-c4wp-wizard', function(e) {
		e.preventDefault();
		showWizard();
	});

	if (jQuery('#whitelist_ips_input').length) {
		// Ensure only numbers and periods can be used.
		const ele = document.getElementById('whitelist_ips_input');
		ele.addEventListener('keypress', function(e) {
			const key = e.which || e.keyCode;
			if (key != 46 && (key < 48 || key > 57)) {
				e.preventDefault();
			}
		});
	}

	if (jQuery('#c4wp_admin_options_failed_login_cron_schedule').length) {
		// Ensure only numbers and periods can be used.
		const ele = document.getElementById('c4wp_admin_options_failed_login_cron_schedule');
		ele.addEventListener('keypress', function(e) {
			const key = e.which || e.keyCode;
			if ((key < 48 || key > 57)) {
				e.preventDefault();
			}
		});
	}

	if (jQuery('#c4wp_admin_options_failed_login_enable').length) {
		if (document.getElementById('c4wp_admin_options_failed_login_enable').checked) {
			jQuery('.failed-captcha-count-input').removeClass('disabled');
		} else {
			jQuery('.failed-captcha-count-input').addClass('disabled');
		}
	}

	jQuery('body').on("change", 'input#c4wp_admin_options_failed_login_enable', function(e) {
		if (jQuery('#c4wp_admin_options_failed_login_enable').length) {
			if (document.getElementById('c4wp_admin_options_failed_login_enable').checked) {
				jQuery('.failed-captcha-count-input').removeClass('disabled');
			} else {
				jQuery('.failed-captcha-count-input').addClass('disabled');
			}
		}
	});

	function showWizard(goToIntro = false) {
		if (goToIntro) {
			jQuery('#c4wp-setup-wizard-intro').addClass('active');
		} else {
			jQuery('#c4wp-setup-wizard-intro').remove();
			jQuery('#c4wp-setup-wizard-version-select').addClass('active');
		}
		setTimeout(function() {
			jQuery('#c4wp-setup-wizard').addClass('show-wizard');
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

	function removeDuplicates(str) {
		const strArr = str.split(",");
		const uniqueArray = [...new Set(strArr)];
		return uniqueArray.join();
	}

	jQuery('body').on('click', '#c4wp-close-wizard, a[href="#c4wp-cancel-wizard"]', function(e) {
		e.preventDefault();
		jQuery('#c4wp-setup-wizard').removeClass('show-wizard');
	});

	jQuery('body').on('click', '#reset-c4wp-config', function(e) {
		e.preventDefault();
		c4wpConfirm(
			anrScripts.removeConfigWarning,
			function() {
				c4wp_reset_captcha_config();
				return true;
			},
			function() {
				return false;
			},
			anrScripts.removeConfigWarningTitle
		);
	});

	jQuery('body').on('click', 'a[data-wizard-goto]', function(e) {
		e.preventDefault();
		var targetDiv = jQuery(this).attr('href');
		var inputsToCheck = jQuery(this).attr('data-check-inputs');
		var current_fallback = jQuery('#c4wp_admin_options_failure_action option:selected').val();

		if (!jQuery('#c4wp-setup-wizard-v3-fallback').hasClass('active')) {
			if (targetDiv == '#c4wp-setup-wizard-additional-settings') {
				var currVal = jQuery('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
				if (currVal == 'v3') {
					var targetDiv = '#c4wp-setup-wizard-v3-fallback';
				}
				c4wp_admin_show_hide_failure_fields();
			}
		} else {
			if ('redirect' == current_fallback) {
				if (!jQuery('#c4wp_admin_options_failure_redirect').val() || !validateURL(jQuery('#c4wp_admin_options_failure_redirect').val())) {
					jQuery('#c4wp_admin_options_failure_redirect').css('border', '1px solid red');
					return true;
				} else {
					jQuery('#c4wp_admin_options_failure_redirect').css('border', '1px solid #8c8f94');
					jQuery(this).parent().removeClass('active').slideUp(200);
					jQuery(targetDiv).addClass('active').slideDown(200);
				}
			} else if ('nothing' == current_fallback) {
				jQuery(this).parent().removeClass('active').slideUp(200);
				jQuery(targetDiv).addClass('active').slideDown(200);
			}
		}

		if (targetDiv == '#c4wp-setup-wizard-site-keys') {
			var currVal = jQuery('input[name="c4wp_admin_options[captcha_version]"]:checked').val();
			if (typeof currVal == 'undefined') {
				jQuery('input[name="c4wp_admin_options[captcha_version]"]').css('border', '1px solid red');
				return true;
			} else {
				jQuery('input[name="c4wp_admin_options[captcha_version]"]').css('border', '1px solid #8c8f94');
			}
			if (currVal == 'hcaptcha') {
				jQuery('.wizard_key_intro_text').html(anrScripts.hcaptcha_wizard_intro_text);
			} else if (currVal == 'cloudflare') {
				jQuery('.wizard_key_intro_text').html(anrScripts.cloudflare_wizard_intro_text);
			} else {
				jQuery('.wizard_key_intro_text').html(anrScripts.recaptcha_wizard_intro_text);
			}
		}

		if (inputsToCheck || jQuery('body [data-key-invalid]').length) {
			if (!jQuery(inputsToCheck).val() || jQuery('body [data-key-invalid]').length) {
				jQuery(inputsToCheck).css('border', '1px solid red');
			} else {
				jQuery(this).parent().removeClass('active').slideUp(200);
				jQuery(targetDiv).addClass('active').slideDown(200);
			}
		} else {
			if (jQuery(this).parent().attr('id') == 'key-validation-step-1') {
				jQuery(this).parent().parent().parent().removeClass('active');
				jQuery(this).parent().addClass('hidden');
			} else {
				jQuery(this).parent().removeClass('active').slideUp(200);
			}
			jQuery(targetDiv).addClass('active').slideDown(200);
		}

		if (jQuery('#c4wp-setup-wizard-site-keys').hasClass('active')) {
			if (targetDiv == '#c4wp-setup-wizard-site-keys') {
				jQuery('#key-validation-step-1').removeClass('hidden');
				if (jQuery('#key-validation-step-1').is(":hidden")) {
					jQuery('#key-validation-step-1').slideDown(200);
				}
			}
			jQuery('#c4wp-setup-wizard-site-keys .button-primary[data-check-inputs]').attr('data-check-inputs', '#c4wp_admin_options_site_key, #c4wp_admin_options_secret_key');
		}

		if (jQuery('#c4wp-setup-wizard-v3-fallback').hasClass('active')) {
			c4wp_admin_show_hide_failure_fields();
		}

	});

	jQuery('body').on('click', 'a[href="#finish"]', function(e) {
		e.preventDefault();
		jQuery('#c4wp-setup-wizard').removeClass('show-wizard');
		jQuery('#c4wp-admin-wrap form #submit').trigger('click');
	});

	function c4wp_reset_captcha_config() {
		var nonce = jQuery('#reset-c4wp-config').attr('data-nonce');
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'c4wp_reset_captcha_config',
				nonce: nonce,
			},
			success: function(result) {
				location.reload();
			}
		});
	}

	if (jQuery('#c4wp-admin-wrap').hasClass('show_wizard_on_load')) {
		showWizard(true);
	}

});

/**
 * Onclick event handler to implement user's choice to either
 * opt in or out of freemius.
 *
 * @param {string} element - Current element.
 */
function c4wp_freemius_opt_in(element) {
	var nonce = jQuery('#c4wp-freemius-opt-nonce').val(); // Nonce.
	var choice = jQuery(element).data('opt'); // Choice.

	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		async: true,
		data: {
			action: 'c4wp_freemius_opt_in',
			opt_nonce: nonce,
			choice: choice
		},
		success: function(data) {
			location.reload();
		},
		error: function(xhr, textStatus, error) {
			console.log(xhr.statusText);
			console.log(textStatus);
			console.log(error);
		}
	});
}

function isUrlValid(url) {
	return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
}