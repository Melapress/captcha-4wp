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
		public static function verify( $response = false, $is_fallback_challenge = false ) {
			static $last_verify        = null;
			static $last_response      = null;
			static $duplicate_response = false;
			
			$remoteip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$secret_key = isset( $_POST['c4wp_v2_fallback'] ) || $is_fallback_challenge ? trim( C4WP_Functions::c4wp_get_option( 'failure_v2_secret_key' ) ) : trim( C4WP_Functions::c4wp_get_option( 'secret_key' ) ); // phpcs:ignore
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
				$return = ( 'proceed' == C4WP_Functions::c4wp_get_option( 'pass_on_no_captcha_found', 'proceed' ) ) ? true : false;
				return $return;
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
			} else {
				if ( 'v3' === C4WP_Functions::c4wp_get_option( 'captcha_version' ) ) {
					$secret_key = trim( C4WP_Functions::c4wp_get_option( 'secret_key' ) );
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
						$score  = isset( $result['score'] ) ? $result['score'] : true;
						$verify = C4WP_Functions::c4wp_get_option( 'score', '0.5' ) <= $score;
					}
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
						let foundSubmitBtn = null;
						<?php if ( C4WP_Functions::c4wp_get_option( 'disable_submit', false ) ) { ?>
							foundSubmitBtn = form.querySelector( '[type=submit]' );
						<?php } ?>
						
						if ( null === captcha_div ) {
							continue;	
						}					

						captcha_div.innerHTML = '';

						if ( null != foundSubmitBtn ) {
							foundSubmitBtn.classList.add( 'disabled' );
							foundSubmitBtn.setAttribute( 'disabled', 'disabled' );

							if ( form.classList.contains( 'woocommerce-checkout' ) ) {
								setTimeout( function(){ 
									foundSubmitBtn = form.querySelector( '#place_order' );
									foundSubmitBtn.classList.add( 'disabled' );
									foundSubmitBtn.setAttribute( 'disabled', 'disabled' );
								}, 2500 );
							}
						}

						( function( form ) {
							var c4wp_captcha = grecaptcha.render( captcha_div,{
								'sitekey' : '<?php echo esc_js( trim( C4WP_Functions::c4wp_get_option( 'site_key' ) ) ); ?>',
								'size'  : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'size', 'normal' ) ); ?>',
								'theme' : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'theme', 'light' ) ); ?>',
								'expired-callback' : function(){
									grecaptcha.reset( c4wp_captcha );
								},
								'callback' : function(){
									if ( null != foundSubmitBtn ) {
										foundSubmitBtn.classList.remove( 'disabled' );
										foundSubmitBtn.removeAttribute( 'disabled' );
									}
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
										// Buddypress group.
										form.setAttribute( 'data-captcha-valid', 'yes');
										jQuery( form ).find( '#group-creation-create' ).click(); 
									} else if ( form.id == 'signup-form' && form.classList.contains( 'signup-form' ) ) {
										// Buddyboss.
										form.setAttribute( 'data-captcha-valid', 'yes');
										jQuery( form ).find( '[type="submit"]' ).click(); 
										return true;
									} else if ( form.classList.contains( 'frm-fluent-form' ) ) {;
										form.setAttribute( 'data-captcha-valid', 'yes');
										jQuery( form ).find( '[type="submit"]' ).click(); 
										return true;

									}  else if ( form.parentElement.classList.contains( 'nf-form-layout' ) ) {
										return true;
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
								//, .ninja-forms-field[type="submit"]
								jQuery( 'body' ).on( 'click', 'form:not(.c4wp-primed) .ff-btn-submit,form:not(.c4wp-primed) .everest-forms-submit-button', function ( e ) {
									e.preventDefault();
									grecaptcha.execute( c4wp_captcha ).then( function( data ) {
										var responseElem = form.querySelector( '.g-recaptcha-response' );
										responseElem.setAttribute( 'value', data );	
										form.classList.add( 'c4wp-primed' );
									});	
								});
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
			<style>
				.login-action-lostpassword.login form.shake {
					animation: none;
					animation-iteration-count: 0;
					transform: none !important;
				}
			</style>
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

							if ( null === captcha_div || form.id == 'create-group-form' ) {								
								continue;
							}
							if ( !( captcha_div.offsetWidth || captcha_div.offsetHeight || captcha_div.getClientRects().length ) ) {					    	
								if ( jetpack_sso == null && ! form.classList.contains( 'woocommerce-form-login' ) ) {
									continue;
								}
							}

							let alreadyCloned = form.querySelector( '.c4wp-submit' );
							if ( null != alreadyCloned ) {
								continue;
							}

							let foundSubmitBtn = form.querySelector( '#signup-form [type=submit], [type=submit]:not(.nf-element):not(#group-creation-create):not([name="signup_submit"]):not([name="ac_form_submit"]):not(.verify-captcha)' );
							let cloned = false;
							let clone  = false;

							// Submit button found, clone it.
							if ( foundSubmitBtn ) {
								clone = foundSubmitBtn.cloneNode(true);
								clone.classList.add( 'c4wp-submit' );
								clone.removeAttribute( 'onclick' );
								clone.removeAttribute( 'onkeypress' );
								if ( foundSubmitBtn.parentElement.form === null ) {
									foundSubmitBtn.parentElement.prepend(clone);
								} else {
									foundSubmitBtn.parentElement.insertBefore( clone, foundSubmitBtn );
								}
								foundSubmitBtn.style.display = "none";
								cloned = true;
							}
							
							// Clone created, listen to its click.
							if ( cloned ) {
								clone.addEventListener( 'click', function ( event ) {
									logSubmit( event, 'cloned', form, foundSubmitBtn );
								});
							// No clone, execture and watch for form submission.
							} else {
								grecaptcha.execute(
									'<?php echo esc_js( $site_key ); ?>',
								).then( function( data ) {
									var responseElem = form.querySelector( '.c4wp_response' );
									responseElem.setAttribute( 'value', data );	
								});

								// Anything else.
								form.addEventListener( 'submit', function ( event ) {
									logSubmit( event, 'other', form );
								});	
							}

							function logSubmit( event, form_type = '', form, foundSubmitBtn ) {

								// Standard v3 check.
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

											// Submit as usual.
											if ( foundSubmitBtn ) {
												foundSubmitBtn.click();
											} else {
												
												if ( typeof form.submit === 'function' ) {
													form.submit();
												} else {
													HTMLFormElement.prototype.submit.call(form);
												}
											}

											return true;
										});
									} catch (e) {
										// Slience.
									}
								// V2 fallback.
								} else {
									if ( form.classList.contains( 'wpforms-form' ) || form.classList.contains( 'frm-fluent-form' )) {
										return true;
									}
									
									// Submit as usual.
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

					if ( typeof jQuery !== 'undefined' ) {
						jQuery( 'body' ).on( 'click', '.acomment-reply.bp-primary-action', function ( e ) {
							c4wp_onloadCallback();
						});	
					}

					//token is valid for 2 minutes, So get new token every after 1 minutes 50 seconds
					setInterval(c4wp_onloadCallback, 110000);

					<?php
						$additonal_js = apply_filters( 'c4wp_captcha_callback_additonal_js', false );
						echo $additonal_js; // phpcs:ignore
					?>

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
			?>
			<style type="text/css">
				.login #login, .login #lostpasswordform {
					min-width: 350px !important;
				}
			</style>
			<?php
		}

		public static function get_verify_url() {
			return self::$verify_url;
		}
	}
}
