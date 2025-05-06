				/* @v2-checkbox-js:start */
				var c4wp_onloadCallback = function() {
				    for (var i = 0; i < document.forms.length; i++) {
				        let form = document.forms[i];

				        let captcha_div = form.querySelector('.c4wp_captcha_field_div:not(.rendered)');
				        let foundSubmitBtn = null;
				        if (c4wpConfig.disable_submit == 1) {
				            foundSubmitBtn = form.querySelector('[type=submit]');
				        }

				        if (null === captcha_div) {
				            continue;
				        }

				        captcha_div.innerHTML = '';

				        if (null != foundSubmitBtn) {
				            foundSubmitBtn.classList.add('disabled');
				            foundSubmitBtn.setAttribute('disabled', 'disabled');

				            if (form.classList.contains('woocommerce-checkout')) {
				                setTimeout(function() {
				                    foundSubmitBtn = form.querySelector('#place_order');
				                    foundSubmitBtn.classList.add('disabled');
				                    foundSubmitBtn.setAttribute('disabled', 'disabled');
				                }, 2500);
				            }
				        }

				        (function(form) {
				            var c4wp_captcha = grecaptcha.render(captcha_div, {
				                'sitekey': c4wpConfig.site_key,
				                'size': c4wpConfig.size,
				                'theme': c4wpConfig.theme,
				                'expired-callback': function() {
				                    grecaptcha.reset(c4wp_captcha);
				                },
				                'callback': function(token) {
				                    if (null != foundSubmitBtn) {
				                        foundSubmitBtn.classList.remove('disabled');
				                        foundSubmitBtn.removeAttribute('disabled');
				                    }
				                    if (typeof jQuery !== 'undefined' && jQuery('input[id*="c4wp-wc-checkout"]').length) {
				                        let input = document.querySelector('input[id*="c4wp-wc-checkout"]');
				                        let lastValue = input.value;
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
				                }
				            });
				            captcha_div.classList.add('rendered');

				            eval(c4wpConfig.additional_js);
				            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				        })(form);
				    }
				};

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
				/* @v2-checkbox-js:end */