				/* @v2-invisible-js:start */
				var c4wp_onloadCallback = function() {
				    for (var i = 0; i < document.forms.length; i++) {
				        var form = document.forms[i];
				        var captcha_div = form.querySelector('.c4wp_captcha_field_div:not(.rendered)');

				        if (null === captcha_div) {
				            continue;
				        }
				        if (!(captcha_div.offsetWidth || captcha_div.offsetHeight || captcha_div.getClientRects().length)) {
				            continue;
				        }

				        captcha_div.innerHTML = '';

				        (function(form) {
				            var c4wp_captcha = grecaptcha.render(captcha_div, {
				                'sitekey': c4wpConfig.site_key,
				                'size': 'invisible',
				                'theme': c4wpConfig.theme,
				                'badge': c4wpConfig.badge,
				                'callback': function(token) {
				                    var woo_checkout = form.classList.contains('woocommerce-checkout');
				                    var woo_login = form.getElementsByClassName('woocommerce-form-login__submit');
				                    var woo_register = form.getElementsByClassName('woocommerce-form-register__submit');
				                    var is_commentform = form.getAttribute('id');
				                    if (woo_checkout) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        if (typeof jQuery !== 'undefined') {
				                            jQuery('.woocommerce-checkout').submit();
				                        } else {
				                            form.submit();
				                        }
				                    } else if (woo_login.length) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        form['login'].click();
				                    } else if (woo_register.length) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        form['register'].click();
				                    } else if ('commentform' === is_commentform) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        form['submit'].click();
				                    } else if (form.classList.contains('ac-form')) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        jQuery(form).find('[name="ac_form_submit"]').click();
				                    } else if (form.id == 'create-group-form') {
				                        // Buddypress group.
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        jQuery(form).find('#group-creation-create').click();
				                    } else if (form.id == 'signup-form' && form.classList.contains('signup-form')) {
				                        // Buddyboss.
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        jQuery(form).find('[type="submit"]').click();
				                        return true;
				                    } else if (form.classList.contains('frm-fluent-form')) {
				                        ;
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        jQuery(form).find('[type="submit"]').click();
				                        return true;

				                    } else if (form.parentElement.classList.contains('nf-form-layout')) {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        jQuery(form).find('[type="submit"]').click();
				                        return true;
				                    } else if (typeof jQuery !== 'undefined' && jQuery('input[id*="c4wp-wc-checkout"]').length && token) {
				                        // WC block checkout.
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

				                        jQuery(form).find('.wc-block-components-checkout-place-order-button:not(.c4wp-submit)').click();
				                    } else if (form.id == '#setupform') {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        form.querySelector('.submit .submit').click();
				                        return;
				                    } else if (form.classList.contains('elementor-form')) {
				                        // Needs priming early below.
				                        return false;
				                    } else {
				                        form.setAttribute('data-captcha-valid', 'yes');
				                        form.submit();
				                    }

				                    // Apply relevant accessibility attributes to response.
				                    var responseTextareas = document.querySelectorAll(".g-recaptcha-response");
				                    responseTextareas.forEach(function(textarea) {
				                        textarea.setAttribute("aria-hidden", "true");
				                        textarea.setAttribute("aria-label", "do not use");
				                        textarea.setAttribute("aria-readonly", "true");
				                    });
				                },
				                'expired-callback': function() {
				                    grecaptcha.reset(c4wp_captcha);
				                }
				            });

				            // WC block checkout clone btn.
				            var wcblock_submit = form.querySelector('.wc-block-components-checkout-place-order-button');
				            if (null !== wcblock_submit) {
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
				                    grecaptcha.execute(c4wp_captcha).then(function(data) {
				                        form.classList.add('c4wp-primed');
				                    });
				                });
				            }

				            var elementor_submit = form.querySelector('.elementor-button[type="submit"]');
				            if (null !== elementor_submit) {

				                grecaptcha.execute(c4wp_captcha).then(function(data) {
				                    var responseElem = form.querySelector('.g-recaptcha-response');
				                    responseElem.setAttribute('value', data);
				                    form.classList.add('c4wp-primed');
				                });
				            }

				            captcha_div.classList.add('rendered');


				            eval(c4wpConfig.additional_js);
				            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped


				            form.onsubmit = function(e) {
				                if ('yes' === form.getAttribute('data-captcha-valid')) {
				                    return true;
				                }

				                e.preventDefault();
				                grecaptcha.execute(c4wp_captcha);
				                return false;
				            };

				            if (typeof jQuery !== 'undefined') {
				                // Remove and append badge on WP login screen.
				                jQuery('.login form.shake .grecaptcha-badge').appendTo('body');
				                //, .ninja-forms-field[type="submit"]
				                jQuery('body').on('click', 'form:not(.c4wp-primed) .ff-btn-submit,form:not(.c4wp-primed) .everest-forms-submit-button', function(e) {
				                    e.preventDefault();
				                    grecaptcha.execute(c4wp_captcha).then(function(data) {
				                        var responseElem = form.querySelector('.g-recaptcha-response');
				                        responseElem.setAttribute('value', data);
				                        form.classList.add('c4wp-primed');
				                    });
				                });
				            }
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
				/* @v2-invisible-js:end */