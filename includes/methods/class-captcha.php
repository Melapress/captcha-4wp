<?php
/**
 * Google recaprcha CAPTCHA method.
 *
 * @package C4WP
 */

namespace C4WP\Methods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions as C4WP_Functions;
use C4WP\C4WP_Captcha_Class as C4WP_Captcha_Class;

if ( ! class_exists( 'C4WP_Captcha' ) ) {

	/**
	 * Main class.
	 */
	class Captcha {

		public static $verify_url = 'https://www.google.com/recaptcha/api/siteverify ';

		/**
		 * Add any applicable actions.
		 *
		 * @return void
		 */
		public static function init() {
			// Nothing to see here.
		}

		/**
		 * Create a method specific form field.
		 *
		 * @param integer $captcha_count - Current number of CAPTCHAs on page.
		 * @return string $field - Field markup.
		 */
		public static function form_field( $captcha_count = 0 ) {
			$site_key     = trim( C4WP_Functions::c4wp_get_option( 'site_key' ) );
			$number       = $captcha_count;
			$version      = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
			$verify_nonce = wp_create_nonce( 'c4wp_verify_nonce' );
			$fail_action  = C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' );

			$use_ajax = '';
			if ( 'v2_checkbox' === $fail_action || 'redirect' === $fail_action ) {
				$use_ajax = 'data-c4wp-use-ajax="true"';
				if ( 'v2_checkbox' === $fail_action ) {
					$key       = C4WP_Functions::c4wp_get_option( 'failure_v2_site_key' );
					$use_ajax .= ' data-c4wp-v2-site-key="' . $key . '"';
				}
				if ( 'redirect' === $fail_action ) {
					$redirect  = C4WP_Functions::c4wp_get_option( 'failure_v2_site_key' );
					$use_ajax .= ' data-c4wp-failure-redirect="' . $redirect . '"';
				}
			}

			$field = '<div class="c4wp_captcha_field" style="margin-bottom: 10px;" data-nonce="' . esc_attr( $verify_nonce ) . '" ' . $use_ajax . '><div id="c4wp_captcha_field_' . esc_attr( $number ) . '" class="c4wp_captcha_field_div">';
			if ( 'v3' === $version ) {
				$field .= '<input type="hidden" name="g-recaptcha-response" class="c4wp_response" aria-label="do not use" aria-readonly="true" value=""/>';
			}
			$field .= '</div></div>';

			return $field;
		}

		/**
		 * Method specific verification
		 *
		 * @param boolean $response - Current response.
		 * @return boolean $response - Actual response from method provider.
		 */
		public static function verify( $response = false ) {
			static $last_verify        = null;
			static $last_response      = null;
			static $duplicate_response = false;
			
			$remoteip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$secret_key = isset( $_POST['c4wp_v2_fallback'] ) ? trim( C4WP_Functions::c4wp_get_option( 'failure_v2_secret_key' ) ) : trim( C4WP_Functions::c4wp_get_option( 'secret_key' ) ); // phpcs:ignore
			$verify     = false;

			if ( false === $response ) {
				$response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : ''; // phpcs:ignore
				if ( empty( $response ) ) {
					$response = isset( $_POST['response'] ) ? sanitize_text_field( wp_unslash( $_POST['response'] ) ) : ''; // phpcs:ignore
				}
			}

			// Store what we send to google in case we need to verify if its a duplicated request.
			$last_response = $response;
			if ( $response === $last_response ) {
				$duplicate_response = true;
			}

			$pre_check = apply_filters( 'c4wp_verify_captcha_pre', null, $response );

			if ( null !== $pre_check ) {
				return $pre_check;
			}

			// if $secret_key is not set.
			if ( ! $secret_key ) {
				return true;
			}

			$is_ajax_verification = ( isset( $_POST['action'] ) && 'c4wp_ajax_verify' == $_POST['action'] ) ? true : false;

			// Bail if we have nothign to work with.
			if ( empty( $response ) && ! isset( $_POST['c4wp_v2_fallback'] ) && ! isset( $_POST['g-recaptcha-response'] ) && ! $is_ajax_verification ) { // phpcs:ignore
				return true;
			}

			if ( ! $response && ! isset( $_POST['c4wp_v2_fallback'] ) || ! $remoteip ) {
				return $verify;
			}

			if ( null !== $last_verify ) {
				return $last_verify;
			}

			$url = apply_filters( 'c4wp_google_verify_url', sprintf( self::$verify_url, C4WP_Functions::c4wp_recaptcha_domain() ) );

			// make a POST request to the Google reCAPTCHA Server.
			$request = wp_remote_post(
				$url,
				array(
					'timeout' => 10,
					'body'    => array(
						'secret'   => $secret_key,
						'response' => $response,
						'remoteip' => $remoteip,
					),
				)
			);

			// get the request response body.
			$request_body = wp_remote_retrieve_body( $request );
			if ( ! $request_body ) {
				return $verify;
			}			

			$result = json_decode( $request_body, true );

			if ( isset( $result['success'] ) && true === $result['success'] ) {
				if ( 'v3' === C4WP_Functions::c4wp_get_option( 'captcha_version' ) ) {
					$score  = isset( $result['score'] ) ? $result['score'] : true;
					$verify = C4WP_Functions::c4wp_get_option( 'score', '0.5' ) <= $score;
				} else {
					$verify = true;
				}
			}

			$verify      = apply_filters( 'c4wp_verify_captcha', $verify, $result, $response );
			$last_verify = $verify;

			// If we know this is a duplicated request, pass verification.
			if ( isset( $result['error-codes'] ) && 'timeout-or-duplicate' === $result['error-codes'][0] && $duplicate_response ) {
				$verify = true;
			}

			$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );

			if ( $dev_mode ) {
				$store                  = array();
				$store['verify_post']   = $_POST; // phpcs:ignore
				$store['verify_result'] = $result;
				$store['verify_return'] = $verify;
				C4WP_Functions::c4wp_log_verify_result( $store );

				if ( 'return_false' === C4WP_Functions::c4wp_get_option( 'override_result' ) ) {
					return false;
				} elseif ( 'return_true' === C4WP_Functions::c4wp_get_option( 'override_result' ) ) {
					return true;
				}
			}

			return $verify;
		}

		/**
		 * Method specific footer scripts.
		 *
		 * @return void
		 */
		public static function footer_scripts() {
			$version = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
			if ( 'v2_checkbox' === $version ) {
				self::v2_checkbox_script();
			} elseif ( 'v2_invisible' === $version ) {
				self::v2_invisible_script();
			} elseif ( 'v3' === $version ) {
				self::v3_script();
			}
		}

		/**
		 * V2 checkbox inline script.
		 *
		 * @return void
		 */
		public static function v2_checkbox_script() {
			?>
			<script id="c4wp-inline-js" type="text/javascript">
				var c4wp_onloadCallback = function() {
					for ( var i = 0; i < document.forms.length; i++ ) {
						let form = document.forms[i];
						let captcha_div = form.querySelector( '.c4wp_captcha_field_div:not(.rendered)' );
						if ( null === captcha_div )
							continue;						
						captcha_div.innerHTML = '';
						( function( form ) {
							var c4wp_captcha = grecaptcha.render( captcha_div,{
								'sitekey' : '<?php echo esc_js( trim( C4WP_Functions::c4wp_get_option( 'site_key' ) ) ); ?>',
								'size'  : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'size', 'normal' ) ); ?>',
								'theme' : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'theme', 'light' ) ); ?>',
								'expired-callback' : function(){
									grecaptcha.reset( c4wp_captcha );
								}
							});
							captcha_div.classList.add( 'rendered' );
							<?php
								$additonal_js = apply_filters( 'c4wp_captcha_callback_additonal_js', '' );
								echo $additonal_js; // phpcs:ignore
							?>
						})(form);
					}
				};
			
			</script>
			<?php
			$lang       = C4WP_Captcha_Class::determine_captcha_language();
			$google_url = apply_filters( 'c4wp_v2_checkbox_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang, C4WP_Functions::c4wp_recaptcha_domain() ), $lang );
			?>

			<script id="c4wp-recaptcha-js" src="<?php echo esc_url( $google_url ); ?>"
				async defer>
			</script>
			<?php
		}

		/**
		 * V2 invisible inline script.
		 *
		 * @return void
		 */
		public static function v2_invisible_script() {
			?>
			<script id="c4wp-inline-js" type="text/javascript">
				var c4wp_onloadCallback = function() {
					for ( var i = 0; i < document.forms.length; i++ ) {
						var form = document.forms[i];
						var captcha_div = form.querySelector( '.c4wp_captcha_field_div:not(.rendered)' );
						if ( null === captcha_div ) {
							continue;
						}
						if ( !( captcha_div.offsetWidth || captcha_div.offsetHeight || captcha_div.getClientRects().length ) ) {
							continue;
						}
						captcha_div.innerHTML = '';

						( function( form ) {
							var c4wp_captcha = grecaptcha.render( captcha_div,{
								'sitekey' : '<?php echo esc_js( trim( C4WP_Functions::c4wp_get_option( 'site_key' ) ) ); ?>',
								'size'  : 'invisible',
								'theme' : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'theme', 'light' ) ); ?>',
								'badge' : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'badge', 'bottomright' ) ); ?>',
								'callback' : function ( token ) {
									var woo_checkout = form.classList.contains( 'woocommerce-checkout' );
									var woo_login    = form.getElementsByClassName( 'woocommerce-form-login__submit' );
									var woo_register = form.getElementsByClassName( 'woocommerce-form-register__submit' );
									var is_commentform = form.getAttribute('id');
									if ( woo_checkout ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										if ( typeof jQuery !== 'undefined' ) {
											jQuery( '.woocommerce-checkout' ).submit();
										} else {
											form.submit();
										}
									} else if ( woo_login.length ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										form['login'].click();
									} else if ( woo_register.length ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										form['register'].click();
									} else if ( 'commentform' === is_commentform ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										form['submit'].click();
									} else if ( form.classList.contains( 'ac-form' ) ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										jQuery( form ).find( '[name="ac_form_submit"]' ).click(); 
									} else if ( form.id == 'create-group-form' ) {
										form.setAttribute( 'data-captcha-valid', 'yes');
										jQuery( form ).find( '#group-creation-create' ).click(); 
									} else {
										form.setAttribute( 'data-captcha-valid', 'yes');
										form.submit();
									}

									// Apply relevent accessibility attributes to response.
									var responseTextareas = document.querySelectorAll(".g-recaptcha-response");
									responseTextareas.forEach(function(textarea) {
										textarea.setAttribute("aria-hidden", "true");
										textarea.setAttribute("aria-label", "do not use");
										textarea.setAttribute("aria-readonly", "true");
									});
								},
								'expired-callback' : function(){
									grecaptcha.reset( c4wp_captcha );
								}
							});

							captcha_div.classList.add( 'rendered' );

							<?php
								$additonal_js = apply_filters( 'c4wp_captcha_callback_additonal_js', false );
								echo $additonal_js; // phpcs:ignore
							?>
							form.onsubmit = function( e ){
								if ( 'yes' === form.getAttribute( 'data-captcha-valid' ) ) {
									return true;
								}

								e.preventDefault();
								grecaptcha.execute( c4wp_captcha );
								return false;
							};

							if ( typeof jQuery !== 'undefined' ) {
								// Remove and append badge on WP login screen.
								jQuery( '.login form.shake .grecaptcha-badge' ).appendTo( 'body' );
							}
						})(form);
					}
				};
			</script>
			<?php
			$lang       = C4WP_Captcha_Class::determine_captcha_language();
			$google_url = apply_filters( 'c4wp_v2_invisible_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang, C4WP_Functions::c4wp_recaptcha_domain() ), $lang );
			?>
			<script id="c4wp-recaptcha-js" src="<?php echo esc_url( $google_url ); ?>"
				async defer>
			</script>
			<?php
		}

		/**
		 * V3 inline scripts.
		 *
		 * @return void
		 */
		public static function v3_script() {

			$site_key = trim( C4WP_Functions::c4wp_get_option( 'site_key' ) );
			$lang     = C4WP_Captcha_Class::determine_captcha_language();
			$position = C4WP_Functions::c4wp_get_option( 'badge_v3', 'bottomright' );

			$google_url = apply_filters( 'c4wp_v3_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?render=' . $site_key . $lang, C4WP_Functions::c4wp_recaptcha_domain() ), $site_key, $lang );
			$asyng_tag  = ( 'yes' === C4WP_Functions::c4wp_get_option( 'v3_script_async', 'no' ) ) ? 'async' : '';
			?>
			<script <?php echo esc_url( $asyng_tag ); ?> id="c4wp-recaptcha-js" src="<?php echo esc_url( $google_url ); ?>"></script>
			<script id="c4wp-inline-js" type="text/javascript">
				
				( function( grecaptcha ) {

					let c4wp_onloadCallback = function() {
						for ( var i = 0; i < document.forms.length; i++ ) {

							let form = document.forms[i];
							let captcha_div = form.querySelector( '.c4wp_captcha_field_div:not(.rendered)' );
							let jetpack_sso = form.querySelector( '#jetpack-sso-wrap' );

							if ( null === captcha_div ) {
								continue;
							}
							if ( !( captcha_div.offsetWidth || captcha_div.offsetHeight || captcha_div.getClientRects().length ) ) {					    	
								if ( jetpack_sso == null && jetpack_sso.length == 0 && ! form.classList.contains( 'woocommerce-form-login' ) ) {
									continue;
								}
							}
							
							var woo_register = form.getElementsByClassName( 'woocommerce-form-register__submit' );
							var woo_ppc      = form.querySelector('#ppc-button-ppcp-gateway');
							
							if ( woo_ppc != null &&  woo_ppc.length ) {
								woo_ppc.addEventListener( 'click', function ( event ) {
									if ( form.classList.contains( 'c4wp_verify_underway' ) ) {
										return true;
									} else {
										logSubmit( event, 'wc_login', form );
									}
								});
							} else if ( woo_register != null && woo_register.length ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );
									form.classList.add( 'c4wp_v3_init' );
								});

								if ( captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') == 'true' ) {
									form.addEventListener( 'submit', function( event ) {
										if ( form.classList.contains( 'c4wp_v2_fallback_active' ) ) {
											return true;
										} else {
											logSubmit( event, 'wc_reg', form );
										}
									});
								}
							}
							// is WC Checkout?
							else if ( form.classList.contains( 'checkout' ) ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
									form.classList.add( 'c4wp_v3_init' );
								});
								
								if ( typeof jQuery !== 'undefined' && jQuery( captcha_div ).parent().attr( 'data-c4wp-use-ajax' ) == 'true' ) {
									jQuery( 'form.checkout' ).on( 'checkout_place_order', function( event ) {
										if ( jQuery( form ).hasClass( 'c4wp_v2_fallback_active' ) ) {
											return true;
										} else {
											logSubmit( event, 'wc_checkout', form );
											return false;
										}
									});
								}
							// is WC Login?
							} else if ( form.classList.contains( 'woocommerce-form-login' )  ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
								});

								if ( captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') == 'true' ) {
									const searchElement = form.querySelector( '.woocommerce-form-login__submit' );
									searchElement.addEventListener( 'click', function ( event ) {
										if ( form.classList.contains( 'c4wp_verify_underway' ) ) {
											return true;
										} else {
											logSubmit( event, 'wc_login', form );
										}
									});
								}

							} else if ( form.classList.contains( 'lost_reset_password' ) ) {
								const searchElement = form.querySelector( '.lost_reset_password button[type="submit"]' );
								searchElement.addEventListener( 'click', function ( event ) {
									if ( form.classList.contains( 'c4wp_verify_underway' ) ) {
										return true;
									} else {
										logSubmit( event, 'wc_reset_pass', form );
									}
								});

							// is CF7?
							} else if ( form.classList.contains( 'wpcf7-form' ) ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
								});
								if ( captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') == 'true' ) {
									const searchElement = form.querySelector( '.wpcf7-submit' );
									searchElement.addEventListener( 'click', function ( event ) {
										logSubmit( event, 'cf7', form );
									});
								}
							} else if ( form.getAttribute('id') == 'resetpassform' ) {
								const searchElement = document.querySelector( '#wp-submit' );
								searchElement.addEventListener( 'click', function ( event ) {
									// We take over the submit event, so fill this hiddne field.
									const pass1 = document.querySelector( '#pass1' );
									const pass2 = document.querySelector( '#pass2' );
									pass2.setAttribute( 'value', pass1.value );	
									logSubmit( event, 'reset_pw_form', form );
								});
							} else if ( form.getAttribute('id') == 'signup-form' && form.parentElement.parentElement.getAttribute('id') == 'buddypress' || form.getAttribute('id') == 'create-group-form' ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
								});

								if ( captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') == 'true' ) {
									form.addEventListener( 'submit', function ( event ) {
										if ( form.classList.contains( 'c4wp_verify_underway' ) ) {
											return true;
										} else {
											if ( form.getAttribute('id') == 'create-group-form' ) {
												logSubmit( event, 'bp_group', form );
											} else {
												logSubmit( event, 'bp_signup', form );
											}
										}
									});	
								}

							} else if ( form.parentElement.classList.contains( 'gform_wrapper' ) ) {
								// Execute early to ensure response is populated.
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
								});

								var GFsearchElement = form.querySelector( 'input[type=submit]' );

								GFsearchElement.addEventListener( 'click', function ( event ) {	
									logSubmit( event, 'gf', form );
								});

							} else {
								if ( captcha_div.parentElement.getAttribute('data-c4wp-use-ajax') != 'true' ) {
									// Execute early to ensure response is populated.
									grecaptcha.execute(
										'<?php echo esc_js( $site_key ); ?>',
									).then( function( data ) {
										var responseElem = form.querySelector( '.c4wp_response' );
										responseElem.setAttribute( 'value', data );	
									});
								} else {
									if ( form.classList.contains( 'ac-form' ) ) {
										jQuery( 'body' ).on( 'click', '.verify-captcha', function ( e ) {											
											if ( form.classList.contains( 'c4wp_verify_underway' ) ) {
												return true;
											} else {
												event.preventDefault();
												if ( form.classList.contains( 'bp_comment' ) ) {
													logSubmit( event, 'bp_comment', form );
												} else {
													logSubmit( event, 'other', form );
												}
											}
										});
									}
									// Anything else.
									form.addEventListener( 'submit', function ( event ) {
										logSubmit( event, 'other', form );
									});	
								}						
							}

							function logSubmit( event, form_type = '', form ) {
								if ( ! form.classList.contains( 'c4wp_v2_fallback_active' ) && ! form.classList.contains( 'c4wp_verified' ) ) {
									event.preventDefault();
									
									try {
										grecaptcha.execute(
											'<?php echo esc_js( $site_key ); ?>',
										).then( function( data ) {	
											var responseElem = form.querySelector( '.c4wp_response' );
											if ( responseElem == null ) {
												var responseElem = document.querySelector( '.c4wp_response' );
											}
											
											responseElem.setAttribute( 'value', data );	

											<?php
											if ( 'nothing' !== C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' ) ) {
												echo C4WP_Captcha_Class::c4wp_ajax_verification_scripts(); // phpcs:ignore 
											}
											?>

											if ( typeof form.submit === 'function' ) {
												form.submit();
											} else {
												HTMLFormElement.prototype.submit.call(form);
											}

											return true;
										});
									} catch (e) {

									}
								} else {
									if ( typeof form.submit === 'function' ) {
										form.submit();
									} else {
										HTMLFormElement.prototype.submit.call(form);
									}
									return true;
								}
							};
						}
					};

					grecaptcha.ready( c4wp_onloadCallback );

					jQuery( 'body' ).on( 'click', '.acomment-reply.bp-primary-action', function ( e ) {
						c4wp_onloadCallback();
					});	

					//token is valid for 2 minutes, So get new token every after 1 minutes 50 seconds
					setInterval(c4wp_onloadCallback, 110000);

				} )( grecaptcha );
			</script>
			<?php
			if ( 'bottomleft' === $position ) :
				?>
			<style type="text/css">
				.grecaptcha-badge {
					width: 70px !important;
					overflow: hidden !important;
					transition: all 0.3s ease !important;
					left: 4px !important;
				}
				.grecaptcha-badge:hover {
					width: 256px !important;
				}
			</style>
				<?php
			endif;
		}

		public static function get_verify_url() {
			return self::$verify_url;
		}
	}
}
