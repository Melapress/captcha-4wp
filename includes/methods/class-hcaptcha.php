<?php
/**
 * HCaptcha CAPTCHA method.
 *
 * @package C4WP
 * @since 7.6.0
 */

declare(strict_types=1);

namespace C4WP\Methods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions;
use C4WP\C4WP_Captcha_Class;

if ( ! class_exists( '\C4WP\Methods\C4WP_HCaptcha' ) ) {

	/**
	 * Main class.
	 *
	 * @since 7.6.0
	 */
	class HCaptcha {

		/**
		 * This methods main URL.
		 *
		 * @var string
		 *
		 * @since 7.6.0
		 */
		public static $verify_url = 'https://api.hcaptcha.com/siteverify';

		/**
		 * Add any applicable actions.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function init() {
			if ( C4WP_Functions::c4wp_is_premium_version() ) {
				add_filter( 'c4wp_settings_fields', array( __CLASS__, 'add_settings_field' ) );
			}
			C4WP_Method_Loader::$methods['hcaptcha'] = 'C4WP\\Methods\\HCaptcha';
		}

		/**
		 * Add method specific settings to fields.
		 *
		 * @param  array $fields - Current settings fields.
		 *
		 * @return array $fields - Appended settings.
		 *
		 * @since 7.6.0
		 */
		public static function add_settings_field( $fields ) {
			$versions                             = $fields['captcha_version']['options'];
			$versions['hcaptcha']                 = esc_html__( 'hCaptcha (Users have to check the "I’m not a robot” checkbox)', 'advanced-nocaptcha-recaptcha' );
			$fields['captcha_version']['options'] = $versions;
			return $fields;
		}

		/**
		 * Create a method specific form field.
		 *
		 * @param integer $captcha_count - Current number of CAPTCHAs on page.
		 *
		 * @return string $field - Field markup.
		 *
		 * @since 7.6.0
		 */
		public static function form_field( $captcha_count = 0 ) {
			$verify_nonce = wp_create_nonce( 'c4wp_verify_nonce' );
			$field        = '<div class="c4wp_captcha_field" style="margin-bottom: 10px; margin-top: 15px;"><div id="c4wp_captcha_field_' . esc_attr( $captcha_count ) . '" class="c4wp_captcha_field_div" data-nonce="' . esc_attr( $verify_nonce ) . '">';
			$field       .= '</div></div>';

			return $field;
		}

		/**
		 * Main verification function. Decides if submission was ok or not.
		 *
		 * @param boolean $response - Response to verify.
		 * @param boolean $is_fallback_challenge - Is this a fallback response.
		 *
		 * @return bool - Did verify or not.
		 *
		 * @since 7.6.0
		 */
		public static function verify( $response = false, $is_fallback_challenge = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static $last_verify        = null;
			static $last_response      = null;
			static $duplicate_response = false;

			$remoteip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$secret_key = trim( C4WP_Functions::c4wp_get_secret_key() );
			$verify     = false;

			// $_POSTs ignored for phpcs as nonce not required.
			if ( false === $response ) {
				$response = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,
				if ( empty( $response ) ) {
					$response = empty( $response ) && isset( $_POST['response'] ) ? sanitize_text_field( wp_unslash( $_POST['response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,
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

			$is_ajax_verification = ( isset( $_POST['action'] ) && 'c4wp_ajax_verify' === $_POST['action'] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing,

			// Bail if we have nothing to work with.
			if ( empty( $response ) && ! isset( $_POST['h-captcha-response'] ) && ! $is_ajax_verification ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,
				$return = ( 'proceed' === C4WP_Functions::c4wp_get_option( 'pass_on_no_captcha_found', 'proceed' ) ) ? true : false;
				return $return;
			}

			if ( ! $response || ! $remoteip ) {
				return $verify;
			}

			if ( null !== $last_verify ) {
				return $last_verify;
			}

			$url = apply_filters( 'c4wp_hcaptcha_verify_url', self::$verify_url );

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
				if ( isset( $result['score'] ) && ! empty( $result['score'] ) ) {
					$verify = $result['score'] >= C4WP_Functions::c4wp_get_option( 'score', '0.5' );
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

			/* @dev:start */
			$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );

			if ( $dev_mode ) {
				$store                  = array();
				$store['verify_post']   = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing,
				$store['verify_result'] = $result;
				$store['verify_return'] = $verify;
				C4WP_Functions::c4wp_log_verify_result( $store );

				if ( 'return_false' === C4WP_Functions::c4wp_get_option( 'override_result' ) ) {
					return false;
				} elseif ( 'return_true' === C4WP_Functions::c4wp_get_option( 'override_result' ) ) {
					return true;
				}
			}
			/* @dev:end */

			return $verify;
		}

		/**
		 * Method specific footer scripts.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function footer_scripts() {
			// PHPCS ignored as this method uses inline scripts, which it does not like.
			// phpcs:disable
			?>
			<script id="c4wp-inline-js" type="text/javascript">
				/* @hcaptcha-js:start */
				var c4wp_onloadCallback = function() {
					for ( var i = 0; i < document.forms.length; i++ ) {
						let form           = document.forms[i];
						let captcha_div    = form.querySelector( '.c4wp_captcha_field_div:not(.rendered)' );						
						let foundSubmitBtn = null;
						<?php if ( C4WP_Functions::c4wp_get_option( 'disable_submit', false ) ) { ?>
							foundSubmitBtn = form.querySelector( '[type=submit]' );
						<?php } ?>

						if ( null === captcha_div )
							continue;						
						captcha_div.innerHTML = '';

						if ( null != foundSubmitBtn ) {
							foundSubmitBtn.classList.add( 'disabled' );
							foundSubmitBtn.setAttribute( 'disabled', 'disabled' );
						}
						
						( function( form ) {
							var c4wp_captcha = grecaptcha.render( captcha_div,{
								'sitekey' : '<?php echo esc_js( trim( C4WP_Functions::c4wp_get_site_key() ) ); ?>',
								'size'  : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'size', 'normal' ) ); ?>',
								'theme' : '<?php echo esc_js( C4WP_Functions::c4wp_get_option( 'theme', 'light' ) ); ?>',
								'expired-callback' : function(){
									grecaptcha.reset( c4wp_captcha );
								},
								'callback' : function( token ){
									if ( null != foundSubmitBtn ) {
										foundSubmitBtn.classList.remove( 'disabled' );
										foundSubmitBtn.removeAttribute( 'disabled' );
									}
									if ( typeof jQuery !== 'undefined' && jQuery( 'input[id*="c4wp-wc-checkout"]' ).length ) {
										let input = document.querySelector('input[id*="c4wp-wc-checkout"]'); 
										let lastValue = input.value;
										input.value = token;
										let event = new Event('input', { bubbles: true });
										event.simulated = true;
										let tracker = input._valueTracker;
										if (tracker) {
											tracker.setValue( lastValue );
										}
										input.dispatchEvent(event)
									}
								}
							});
							captcha_div.classList.add( 'rendered' );
							<?php
								$additional_js = apply_filters( 'c4wp_captcha_callback_additional_js', false );
								echo $additional_js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						})(form);
					}
				};

				window.addEventListener("load", (event) => {
					if ( typeof jQuery !== 'undefined' && jQuery( 'input[id*="c4wp-wc-checkout"]' ).length ) {
						var element = document.createElement('div');
						var html = '<?php echo wp_kses( self::form_field(), C4WP_Functions::c4wp_allowed_kses_args() ); ?>';
						element.innerHTML = html;
						jQuery( '[class*="c4wp-wc-checkout"]' ).append( element );
						jQuery( '[class*="c4wp-wc-checkout"]' ).find('*').off();
						c4wp_onloadCallback();
					}
				});
				/* @hcaptcha-js:end */
			</script>
			<?php
			$lang       = C4WP_Captcha_Class::determine_captcha_language();
			$google_url = apply_filters( 'c4wp_hcaptcha_script_api_src', sprintf( 'https://js.hcaptcha.com/1/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang ), $lang );
			?>

			<script id="c4wp-recaptcha-js" src="<?php echo esc_url( $google_url ); ?>" async defer></script>
			<?php
			// phpcs:enable
		}

		/**
		 * Get this methods verification URL.
		 *
		 * @return string - The url.
		 *
		 * @since 7.6.0
		 */
		public static function get_verify_url() {
			return self::$verify_url;
		}

		/**
		 * Get the script URL for this method.
		 *
		 * @return string - The URL.
		 *
		 * @since 7.6.0
		 */
		public static function get_provider_script_url() {
			$lang       = C4WP_Captcha_Class::determine_captcha_language();
			$google_url = apply_filters( 'c4wp_hcaptcha_script_api_src', sprintf( 'https://js.hcaptcha.com/1/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang ), $lang );

			return $google_url;
		}
	}
}
