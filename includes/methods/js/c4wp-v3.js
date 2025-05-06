				/* @v3-js:start */
				let c4wp_onloadCallback = function() {
				    for (var i = 0; i < document.forms.length; i++) {
				        let form = document.forms[i];
				        let captcha_div = form.querySelector('.c4wp_captcha_field_div:not(.rendered)');
				        let jetpack_sso = form.querySelector('#jetpack-sso-wrap');
				        var wcblock_submit = form.querySelector('.wc-block-components-checkout-place-order-button');
				        var has_wc_submit = null !== wcblock_submit;

				        if (null === captcha_div && !has_wc_submit || form.id == 'create-group-form') {
				            if (!form.parentElement.classList.contains('nf-form-layout')) {
				                continue;
				            }

				        }
				        if (!has_wc_submit) {
				            if (!(captcha_div.offsetWidth || captcha_div.offsetHeight || captcha_div.getClientRects().length)) {
				                if (jetpack_sso == null && !form.classList.contains('woocommerce-form-login')) {
				                    continue;
				                }
				            }
				        }

				        let alreadyCloned = form.querySelector('.c4wp-submit');
				        if (null != alreadyCloned) {
				            continue;
				        }

				        let foundSubmitBtn = form.querySelector('#signup-form [type=submit], [type=submit]:not(#group-creation-create):not([name="signup_submit"]):not([name="ac_form_submit"]):not(.verify-captcha)');
				        let cloned = false;
				        let clone = false;

				        // Submit button found, clone it.
				        if (foundSubmitBtn) {
				            clone = foundSubmitBtn.cloneNode(true);
				            clone.classList.add('c4wp-submit');
				            clone.removeAttribute('onclick');
				            clone.removeAttribute('onkeypress');
				            if (foundSubmitBtn.parentElement.form === null) {
				                foundSubmitBtn.parentElement.prepend(clone);
				            } else {
				                foundSubmitBtn.parentElement.insertBefore(clone, foundSubmitBtn);
				            }
				            foundSubmitBtn.style.display = "none";
				            captcha_div = form.querySelector('.c4wp_captcha_field_div');
				            cloned = true;
				        }

				        // WC block checkout clone btn.
				        if (has_wc_submit && !form.classList.contains('c4wp-primed')) {
				            clone = wcblock_submit.cloneNode(true);
				            clone.classList.add('c4wp-submit');
				            clone.classList.add('c4wp-clone');
				            clone.removeAttribute('onclick');
				            clone.removeAttribute('onkeypress');
				            if (wcblock_submit.parentElement.form === null) {
				                wcblock_submit.parentElement.prepend(clone);
				            } else {
				                wcblock_submit.parentElement.insertBefore(clone, wcblock_submit);
				            }
				            wcblock_submit.style.display = "none";

				            clone.addEventListener('click', function(e) {
				                if (form.classList.contains('c4wp_v2_fallback_active')) {
				                    jQuery(form).find('.wc-block-components-checkout-place-order-button:not(.c4wp-submit)').click();
				                    return true;
				                } else {
				                    grecaptcha.execute(c4wpConfig.site_key, ).then(function(data) {
				                        form.classList.add('c4wp-primed');
				                    });
				                }

				            });
				            foundSubmitBtn = wcblock_submit;
				            cloned = true;
				        }

				        // Clone created, listen to its click.
				        if (cloned) {
				            clone.addEventListener('click', function(event) {
				                logSubmit(event, 'cloned', form, foundSubmitBtn);
				            });
				            // No clone, execute and watch for form submission.
				        } else {
				            grecaptcha.execute(
				                c4wpConfig.site_key,
				            ).then(function(data) {
				                var responseElem = form.querySelector('.c4wp_response');
				                if (responseElem == null) {
				                    var responseElem = document.querySelector('.c4wp_response');
				                }
				                if (responseElem != null) {
				                    responseElem.setAttribute('value', data);
				                }
				            });

				            // Anything else.
				            form.addEventListener('submit', function(event) {
				                logSubmit(event, 'other', form);
				            });
				        }

				        function logSubmit(event, form_type = '', form, foundSubmitBtn) {
				            // Standard v3 check.
				            if (!form.classList.contains('c4wp_v2_fallback_active') && !form.classList.contains('c4wp_verified')) {
				                event.preventDefault();
				                try {
				                    grecaptcha.execute(
				                        c4wpConfig.site_key,
				                    ).then(function(data) {
				                        var responseElem = form.querySelector('.c4wp_response');
				                        if (responseElem == null) {
				                            var responseElem = document.querySelector('.c4wp_response');
				                        }

				                        responseElem.setAttribute('value', data);

				                        if (form.classList.contains('wc-block-checkout__form')) {
				                            // WC block checkout.
				                            let input = document.querySelector('input[id*="c4wp-wc-checkout"]');
				                            let lastValue = input.value;
				                            var token = data;
				                            input.value = token;
				                            let event = new Event('input', {
				                                bubbles: true
				                            });
				                            event.simulated = true;
				                            let tracker = input._valueTracker;
				                            if (tracker) {
				                                tracker.setValue(lastValue);
				                            }
				                            input.dispatchEvent(event)
				                        }

				                        if ('nothing' !== c4wpConfig.failure_action) {
				                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				                            /* @v3-fallback-js:start */
				                            if (typeof captcha_div == 'undefined' && form.classList.contains('wc-block-checkout__form')) {
				                                var captcha_div = form.querySelector('#additional-information-c4wp-c4wp-wc-checkout');
				                            }

				                            if (typeof captcha_div == 'undefined') {
				                                var captcha_div = form.querySelector('.c4wp_captcha_field_div');
				                            }

				                            var parentElem = captcha_div.parentElement;

				                            if ((form.classList.contains('c4wp-primed')) || (!form.classList.contains('c4wp_verify_underway') && captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') == 'true')) {

				                                form.classList.add('c4wp_verify_underway');
				                                const flagMarkup = '<input id="c4wp_ajax_flag" type="hidden" name="c4wp_ajax_flag" value="c4wp_ajax_flag">';
				                                var flagMarkupDiv = document.createElement('div');
				                                flagMarkupDiv.innerHTML = flagMarkup.trim();

				                                form.appendChild(flagMarkupDiv);

				                                var nonce = captcha_div.parentElement.getAttribute('data-nonce');

				                                var formData = new FormData();

				                                formData.append('action', 'c4wp_ajax_verify');
				                                formData.append('nonce', nonce);
				                                formData.append('response', data);

				                                fetch(c4wpConfig.ajax_url, {
				                                        method: 'POST',
				                                        body: formData,
				                                    }) // wrapped
				                                    .then(
				                                        res => res.json()
				                                    )
				                                    .then(data => {
				                                        if (data['success']) {
				                                            form.classList.add('c4wp_verified');
				                                            // Submit as usual.
				                                            if (foundSubmitBtn) {
				                                                foundSubmitBtn.click();
				                                            } else if (form.classList.contains('wc-block-checkout__form')) {
				                                                jQuery(form).find('.wc-block-components-checkout-place-order-button:not(.c4wp-submit)').click();
				                                            } else {
				                                                if (typeof form.submit === 'function') {
				                                                    form.submit();
				                                                } else {
				                                                    HTMLFormElement.prototype.submit.call(form);
				                                                }
				                                            }

				                                        } else {
				                                            //jQuery( '.nf-form-cont' ).trigger( 'nfFormReady' );

				                                            if ('redirect' === c4wpConfig.failure_action) {
				                                                window.location.href = c4wpConfig.failure_redirect;
				                                            }

				                                            if ('v2_checkbox' === c4wpConfig.failure_action) {
				                                                if (form.classList.contains('wc-block-checkout__form')) {
				                                                    captcha_div = captcha_div.parentElement;
				                                                }

				                                                captcha_div.innerHTML = '';
				                                                form.classList.add('c4wp_v2_fallback_active');
				                                                flagMarkupDiv.firstChild.setAttribute('name', 'c4wp_v2_fallback');

				                                                var c4wp_captcha = grecaptcha.render(captcha_div, {
				                                                    'sitekey': c4wpConfig.failure_v2_site_key,
				                                                    'size': c4wpConfig.size,
				                                                    'theme': c4wpConfig.theme,
				                                                    'expired-callback': function() {
				                                                        grecaptcha.reset(c4wp_captcha);
				                                                    }
				                                                });
				                                            }

				                                            if (form.classList.contains('wc-block-checkout__form')) {
				                                                return true;
				                                            }

				                                            if (form.parentElement.classList.contains('nf-form-layout')) {
				                                                return false;
				                                            }

				                                            // Prevent further submission
				                                            event.preventDefault();
				                                            return false;
				                                        }
				                                    })
				                                    .catch(err => console.error(err));

				                                // Prevent further submission
				                                event.preventDefault();
				                                return false;
				                            }
				                            /* @v3-fallback-js:end */
				                        }


				                        // Submit as usual.
				                        if (foundSubmitBtn) {
				                            foundSubmitBtn.click();
				                        } else if (form.classList.contains('wc-block-checkout__form')) {
				                            jQuery(form).find('.wc-block-components-checkout-place-order-button:not(.c4wp-submit)').click();
				                        } else {

				                            if (typeof form.submit === 'function') {
				                                form.submit();
				                            } else {
				                                HTMLFormElement.prototype.submit.call(form);
				                            }
				                        }

				                        return true;
				                    });
				                } catch (e) {
				                    // Silence.
				                }
				                // V2 fallback.
				            } else {
				                if (form.classList.contains('wpforms-form') || form.classList.contains('frm-fluent-form') || form.classList.contains('woocommerce-checkout')) {
				                    return true;
				                }

				                if (form.parentElement.classList.contains('nf-form-layout')) {
				                    return false;
				                }

				                if (form.classList.contains('wc-block-checkout__form')) {
				                    return;
				                }

				                // Submit as usual.
				                if (typeof form.submit === 'function') {
				                    form.submit();
				                } else {
				                    HTMLFormElement.prototype.submit.call(form);
				                }

				                return true;
				            }
				        };
				    }
				};

				grecaptcha.ready(c4wp_onloadCallback);

				if (typeof jQuery !== 'undefined') {
				    jQuery('body').on('click', '.acomment-reply.bp-primary-action', function(e) {
				        c4wp_onloadCallback();
				    });
				}

				//token is valid for 2 minutes, So get new token every after 1 minutes 50 seconds
				setInterval(c4wp_onloadCallback, 110000);


				eval(c4wpConfig.additional_js); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped


				window.addEventListener("load", (event) => {
				    if (typeof jQuery !== 'undefined' && jQuery('input[id*="c4wp-wc-checkout"]').length) {
				        var element = document.createElement('div');
				        var html = c4wpConfig.field_markup;
				        element.innerHTML = html;
				        jQuery('[class*="c4wp-wc-checkout"]').append(element);
				        jQuery('[class*="c4wp-wc-checkout"]').find('*').off();
				        c4wp_onloadCallback();
				    }
				});
				/* @v3-js:end */