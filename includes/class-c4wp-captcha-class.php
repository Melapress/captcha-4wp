<?php
/**
 * Main enforcement logic.
 *
 * @package C4WP
 * @since 7.6.0
 */

declare(strict_types=1);

namespace C4WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions;
use C4WP\Methods\C4WP_Method_Loader;
use C4WP\C4WP_Hide_Captcha;
use C4WP\Geo_Blocking;

if ( ! class_exists( '\C4WP\C4WP_Captcha_Class' ) ) {

	/**
	 * Main class.
	 *
	 * @since 7.6.0
	 */
	class C4WP_Captcha_Class {

		/**
		 * Class instance.
		 *
		 * @var C4WP_Captcha_Class
		 *
		 * @since 7.6.0
		 */
		private static $instance;

		/**
		 * Counter for number of captchas found within an page.
		 *
		 * @var integer
		 *
		 * @since 7.6.0
		 */
		private static $captcha_count = 0;

		/**
		 * Class initiator.
		 *
		 * @return C4WP_Pro $instance - C4WP_Pro instance.
		 *
		 * @since 7.6.0
		 */
		public static function init() {
			if ( ! self::$instance instanceof self ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Setup actions and filters used in our plugin.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function actions_filters() {
			$test_loading = false;

			if ( C4WP_Functions::c4wp_is_form_enabled( 'login' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				add_action( 'login_form', array( __CLASS__, 'login_form_field' ), 99 );
				add_filter( 'login_form_middle', array( __CLASS__, 'login_form_return' ), 99 );
				add_action( 'um_after_login_fields', array( __CLASS__, 'login_form_field' ), 99 );
				add_filter( 'authenticate', array( __CLASS__, 'login_verify' ), 999, 2 );
			}

			if ( C4WP_Functions::c4wp_is_form_enabled( 'registration' ) ) {
				add_action( 'register_form', array( __CLASS__, 'form_field' ), 99 );
				add_filter( 'registration_errors', array( __CLASS__, 'registration_verify' ), 10, 3 );
			}

			if ( C4WP_Functions::c4wp_is_form_enabled( 'ms_user_signup' ) && is_multisite() ) {
				add_action( 'signup_extra_fields', array( __CLASS__, 'ms_form_field' ), 99 );
				add_filter( 'wpmu_validate_user_signup', array( __CLASS__, 'ms_form_field_verify' ) );
				add_action( 'signup_blogform', array( __CLASS__, 'ms_form_field' ), 99 );
				add_filter( 'wpmu_validate_blog_signup', array( __CLASS__, 'ms_blog_verify' ) );
			}

			if ( C4WP_Functions::c4wp_is_form_enabled( 'lost_password' ) ) {
				add_action( 'lostpassword_form', array( __CLASS__, 'form_field' ), 99 );
				add_action( 'lostpassword_post', array( __CLASS__, 'lostpassword_verify' ), 10, 2 );
			}

			if ( C4WP_Functions::c4wp_is_form_enabled( 'reset_password' ) ) {
				add_action( 'resetpass_form', array( __CLASS__, 'form_field' ), 99 );
				add_filter( 'validate_password_reset', array( __CLASS__, 'reset_password_verify' ), 10, 2 );
			}

			if ( C4WP_Functions::c4wp_is_form_enabled( 'comment' ) && ( ! is_admin() || ! current_user_can( 'moderate_comments' ) ) ) {
				if ( ! is_user_logged_in() ) {
					add_action( 'comment_form_after_fields', array( __CLASS__, 'form_field' ), 99 );
				} else {
					add_filter( 'comment_form_field_comment', array( __CLASS__, 'form_field_return' ), 99 );
				}

				if ( version_compare( get_bloginfo( 'version' ), '4.9.0', '>=' ) ) {
					add_filter( 'pre_comment_approved', array( __CLASS__, 'comment_verify' ), 99 );
				} else {
					add_filter( 'preprocess_comment', array( __CLASS__, 'comment_verify_old' ) );
				}
			}

			add_action( 'wp_ajax_c4wp_ajax_verify', array( __CLASS__, 'c4wp_ajax_verify' ), 10, 1 );
			add_action( 'wp_ajax_nopriv_c4wp_ajax_verify', array( __CLASS__, 'c4wp_ajax_verify' ), 10, 1 );
			add_action( 'wp_ajax_c4wp_nocaptcha_plugin_notice_ignore', array( 'C4WP_Settings', 'c4wp_nocaptcha_plugin_notice_ignore' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

			/* @dev:start */
			$dev_mode     = apply_filters( 'c4wp_enable_dev_mode', false );
			$test_loading = isset( $_GET['override-load-scripts'] ) && $dev_mode ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			/* @dev:end */

			if ( 'inline' !== C4WP_Functions::c4wp_get_option( 'inline_or_file', 'inline' ) || $test_loading ) {
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_captcha_scripts' ) );
				add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_captcha_scripts' ) );
			} else {
				add_action( 'wp_footer', array( __CLASS__, 'footer_script' ), 99999 );
				add_action( 'login_footer', array( __CLASS__, 'footer_script' ), 99999 );
			}

			add_action( 'wp_head', array( __CLASS__, 'header_css' ), 99999 );
			add_action( 'login_footer', array( __CLASS__, 'header_css' ), 99999 );

			add_action( 'wp_ajax_c4wp_validate_secret_key', array( __CLASS__, 'c4wp_validate_secret_key' ), 10, 1 );
			add_action( 'wp_ajax_nopriv_c4wp_validate_secret_key', array( __CLASS__, 'c4wp_validate_secret_key' ), 10, 1 );
		}

		/**
		 * Add settings page scripts.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function admin_enqueue_scripts() {
			wp_enqueue_script( 'c4wp-notices', C4WP_PLUGIN_URL . 'assets/js/notices.js', array( 'jquery' ), C4WP_VERSION, false );
		}

		/**
		 * Enqueue captcha scripts.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function enqueue_captcha_scripts() {
			$number          = self::total_captcha();
			$captcha_version = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
			$number          = self::total_captcha();

			$number = ( function_exists( '\is_buddypress' ) && \is_buddypress() && 'v2_invisible' !== $captcha_version ) ? 1 : $number;

			// Ensure JS is not embedded if viewing from within the WP customizer or widgets admin areas.
			if ( ! self::check_should_js_embed() ) {
				return;
			}


			$loading_args = array(
				'in_footer' => ( 'footer' === C4WP_Functions::c4wp_get_option( 'file_header_or_footer', 'footer' ) ) ? true : false,
				'strategy'  => 'defer',
			);

			/* @dev:start */

			// Testing.
			$dev_mode    = apply_filters( 'c4wp_enable_dev_mode', false );
			$test_method = isset( $_GET['override-captcha-version'] ) && ! empty( $_GET['override-captcha-version'] ) ? sanitize_text_field( wp_unslash( $_GET['override-captcha-version'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			if ( $dev_mode && $test_method ) {
				$captcha_version = $test_method;
				if ( isset( $_GET['override-load-scripts'] ) && 'header' === trim( sanitize_key( wp_unslash( $_GET['override-load-scripts'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
					$loading_args['in_footer'] = false;
				}
			}
			/* @dev:start */

			$method     = C4WP_Method_Loader::get_currently_selected_method( true, true );
			$google_url = false;

			if ( method_exists( $method, 'get_provider_script_url' ) ) {
				$google_url = $method::get_provider_script_url();

				wp_enqueue_script( 'c4wp-method-provider', $google_url, array( 'jquery' ), C4WP_VERSION, $loading_args );
				wp_enqueue_script( 'c4wp-method', C4WP_PLUGIN_URL . 'includes/methods/js/c4wp-' . $captcha_version . '.js', array( 'c4wp-method-provider' ), C4WP_VERSION, $loading_args );

				$ajax_url = network_admin_url( 'admin-ajax.php' );


				wp_localize_script(
					'c4wp-method',
					'c4wpConfig',
					array(
						'ajax_url'            => $ajax_url,
						'captcha_version'     => $captcha_version,
						'disable_submit'      => C4WP_Functions::c4wp_get_option( 'disable_submit', false ),
						'site_key'            => esc_js( trim( C4WP_Functions::c4wp_get_site_key() ) ),
						'size'                => esc_js( trim( C4WP_Functions::c4wp_get_option( 'size', 'normal' ) ) ),
						'theme'               => esc_js( trim( C4WP_Functions::c4wp_get_option( 'theme', 'light' ) ) ),
						'badge'               => esc_js( trim( C4WP_Functions::c4wp_get_option( 'badge', 'bottomright' ) ) ),
						'failure_action'      => esc_js( trim( C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' ) ) ),
						'additional_js'       => apply_filters( 'c4wp_captcha_callback_additional_js', '' ),
						'fallback_js'         => self::c4wp_ajax_verification_scripts(),
						'flag_markup'         => '<input id="c4wp_ajax_flag" type="hidden" name="c4wp_ajax_flag" value="c4wp_ajax_flag">',
						'field_markup'        => C4WP_Method_Loader::get_form_field( C4WP_Method_Loader::get_currently_selected_method( true, false ), self::total_captcha() ),
						'redirect'            => C4WP_Functions::c4wp_get_option( 'failure_redirect' ),
						'failure_v2_site_key' => C4WP_Functions::c4wp_get_option( 'failure_v2_site_key' ),
					)
				);
			}
		}

		/**
		 * A neat wrapper to provide a response verification via AJAX call.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_ajax_verify() {
			$method     = C4WP_Method_Loader::get_currently_selected_method( true, true );
			$url        = apply_filters( 'c4wp_google_verify_url', sprintf( $method::get_verify_url() ) );
			$remoteip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$secret_key = trim( C4WP_Functions::c4wp_get_secret_key() );
			$verify     = false;

			// Grab POSTed data.
			$posted   = filter_input_array( INPUT_POST );
			$nonce    = sanitize_text_field( $posted['nonce'] );
			$response = sanitize_text_field( $posted['response'] );

			// Check nonce.
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'c4wp_verify_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
			}

			$verify = self::verify();

			if ( ! $verify ) {
				wp_send_json_error();
			} else {
				wp_send_json_success();
			}
		}

		/**
		 * Validate a secret key for use in response.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_validate_secret_key() {
			$verify = false;

			// Grab POSTed data.
			$posted   = filter_input_array( INPUT_POST );
			$nonce    = sanitize_text_field( $posted['nonce'] );
			$response = sanitize_text_field( $posted['response'] );
			$secret   = sanitize_text_field( $posted['secret'] );
			$method   = sanitize_text_field( $posted['method'] );
			$remoteip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			// Check nonce.
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'c4wp_validate_secret_key_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
			}

			$url = '';

			$captcha_methods = array(
				'v2_checkbox',
				'v2_invisible',
				'v3',
			);

			if ( in_array( $method, $captcha_methods, true ) ) {
				$method = 'captcha';
			}

			if ( method_exists( C4WP_Method_Loader::$methods[ $method ], 'get_verify_url' ) ) {
				$url = C4WP_Method_Loader::$methods[ $method ]::get_verify_url();
			}

			// make a POST request to the Google reCAPTCHA Server.
			$request = wp_remote_post(
				$url,
				array(
					'timeout' => 10,
					'body'    => array(
						'secret'   => $secret,
						'response' => $response,
						'remoteip' => $remoteip,
					),
				)
			);

			// get the request response body.
			$request_body = wp_remote_retrieve_body( $request );

			if ( ! $request_body ) {
				$verify = false;
			}

			$result = json_decode( $request_body, true );

			if ( isset( $result['success'] ) && true === $result['success'] ) {
				$verify = true;
			}

			if ( ! $verify ) {
				wp_send_json_error();
			} else {
				wp_send_json_success();
			}
		}

		/**
		 * Additional JS for getting a response via AJAX for further work.
		 *
		 * @return string $code - Markup
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_ajax_verification_scripts() {
			ob_start();
			$ajax_url = admin_url( 'admin-ajax.php' );
			?>
			<script type="text/javascript">
			/* @v3-fallback-js:start */
			if ( typeof captcha_div == 'undefined' && form.classList.contains( 'wc-block-checkout__form' ) ) {
				var captcha_div = form.querySelector( '#additional-information-c4wp-c4wp-wc-checkout' );
			}

			if ( ! captcha_div && form.classList.contains( 'wc-block-checkout__form' ) ) {
				var captcha_div = form.querySelector( '#order-c4wp-c4wp-wc-checkout' );
			}

			if ( typeof captcha_div == 'undefined' ) {
				var captcha_div = form.querySelector( '.c4wp_captcha_field_div' );
			}

			var parentElem = captcha_div.parentElement;

			if ( ( form.classList.contains( 'c4wp-primed' ) ) || ( ! form.classList.contains( 'c4wp_verify_underway' ) && captcha_div.parentElement.getAttribute( 'data-c4wp-use-ajax' ) == 'true' ) ) {

				form.classList.add('c4wp_verify_underway' );
				const flagMarkup =  '<input id="c4wp_ajax_flag" type="hidden" name="c4wp_ajax_flag" value="c4wp_ajax_flag">';
				var flagMarkupDiv = document.createElement('div');
				flagMarkupDiv.innerHTML = flagMarkup.trim();

				form.appendChild( flagMarkupDiv );
	
				var nonce = captcha_div.parentElement.getAttribute( 'data-nonce' );

				var formData = new FormData();

				formData.append( 'action', 'c4wp_ajax_verify' );
				formData.append( 'nonce', nonce );
				formData.append( 'response', data );
				
				fetch( '<?php echo esc_url( $ajax_url ); ?>', {
					method: 'POST',
					body: formData,
				} ) // wrapped
					.then( 
						res => res.json()
					)
					.then( data => {
						if ( data['success'] ) {
							form.classList.add( 'c4wp_verified' );
							// Submit as usual.
							if ( foundSubmitBtn ) {
								foundSubmitBtn.click();
							} else if ( form.classList.contains( 'wc-block-checkout__form' ) ) {
								jQuery( form ).find( '.wc-block-components-checkout-place-order-button:not(.c4wp-submit)' ).click(); 
							} else {								
								if ( typeof form.submit === 'function' ) {
									form.submit();
								} else {
									HTMLFormElement.prototype.submit.call(form);
								}
							}

						} else {
							//jQuery( '.nf-form-cont' ).trigger( 'nfFormReady' );

							if ( 'redirect' === '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' ) ); ?>' ) {
								window.location.href = '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'failure_redirect' ) ); ?>';
							}

							if ( 'v2_checkbox' === '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' ) ); ?>' ) {
								if ( form.classList.contains( 'wc-block-checkout__form' ) ) {
									captcha_div = captcha_div.parentElement;
								}

								captcha_div.innerHTML = '';
								form.classList.add( 'c4wp_v2_fallback_active' );
								flagMarkupDiv.firstChild.setAttribute( 'name', 'c4wp_v2_fallback' );

								var c4wp_captcha = grecaptcha.render( captcha_div,{
									'sitekey' : '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'failure_v2_site_key' ) ); ?>',		
									'size'  : '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'size', 'normal' ) ); ?>',
									'theme' : '<?php echo esc_attr( C4WP_Functions::c4wp_get_option( 'theme', 'normal' ) ); ?>',				
									'expired-callback' : function(){
										grecaptcha.reset( c4wp_captcha );
									}
								}); 
								jQuery( '.ninja-forms-field.c4wp-submit' ).prop( 'disabled', false );
							}

							if ( form.classList.contains( 'wc-block-checkout__form' ) ) {
								return true;
							}

							if ( form.parentElement.classList.contains( 'nf-form-layout' ) ) {
								jQuery( '.ninja-forms-field.c4wp-submit' ).prop( 'disabled', false );
								return false;
							}

							// Prevent further submission
							event.preventDefault();
							return false;
						}
					} )
					.catch( err => console.error( err ) );

				// Prevent further submission
				event.preventDefault();
				return false;
			}
			/* @v3-fallback-js:end */
			</script>
			<?php
			$output = ob_get_clean();
			// I dont actually want the tags, so remove them.
			$output = str_replace( array( '<script type="text/javascript">', '</script>' ), '', $output );
			return wp_json_encode( $output );
		}

		/**
		 * Return our error message, for use with WP_Error etc.
		 *
		 * @param boolean $mgs - Current message.
		 *
		 * @return string $message - Our message.
		 *
		 * @since 7.6.0
		 */
		public static function add_error_to_mgs( $mgs = false ) {
			if ( false === $mgs ) {
				$mgs = C4WP_Functions::c4wp_get_option( 'error_message', '' );
			}
			if ( ! $mgs ) {
				$mgs = __( 'Please solve Captcha correctly', 'advanced-nocaptcha-recaptcha' );
			}
			$message = '<strong>' . __( 'ERROR', 'advanced-nocaptcha-recaptcha' ) . '</strong>: ' . $mgs;
			return apply_filters( 'c4wp_error_message', $message, $mgs );
		}

		/**
		 * Return number of captchas found in a page.
		 *
		 * @return int $captcha_count - Captcha count.
		 *
		 * @since 7.6.0
		 */
		public static function total_captcha() {
			return self::$captcha_count;
		}

		/**
		 * Create and return captcha field markup.
		 *
		 * @return string $field - Field HTML Markup.
		 *
		 * @since 7.6.0
		 */
		public static function captcha_form_field() {
			++self::$captcha_count;
			$field = C4WP_Method_Loader::get_form_field( C4WP_Method_Loader::get_currently_selected_method( true, false ), self::total_captcha() );
			return $field;
		}

		/**
		 * Prints plugin scripts to the footer when needed.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function footer_script() {
			static $included = false;

			$number  = self::total_captcha();
			$version = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );

			$number         = ( function_exists( '\is_buddypress' ) && \is_buddypress() ) ? 1 : $number;
			$is_wc_checkout = ( function_exists( '\is_checkout' ) && \is_checkout() && ! \is_wc_endpoint_url() ) ? true : false;

			$post_id = get_the_ID(); // Set post ID var.

			if ( class_exists( '\Elementor\Plugin' ) ) {
				if ( \Elementor\Plugin::instance()->db->is_built_with_elementor( $post_id ) ) {
					$number = 1;
				}
			}

			if ( ! $is_wc_checkout ) {
				if ( ! $number && ( 'v3' !== $version || 'all_pages' !== C4WP_Functions::c4wp_get_option( 'v3_script_load', 'all_pages' ) ) ) {
					return;
				}
			}

			if ( $included ) {
				return;
			}

			// Ensure JS is not embedded if viewing from within the WP customizer or widgets admin areas.
			if ( ! self::check_should_js_embed() ) {
				return;
			}


			$included = true;
			echo '<!-- CAPTCHA added with CAPTCHA 4WP plugin. More information: https://captcha4wp.com -->';
			C4WP_Method_Loader::get_footer_scripts( C4WP_Method_Loader::get_currently_selected_method( true, false ) );
			echo '<!-- / CAPTCHA by CAPTCHA 4WP plugin -->';
		}

		/**
		 * Place any optional styling in the doc header.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function header_css() {
			$is_wc_checkout = ( function_exists( '\is_checkout' ) && \is_checkout() && ! \is_wc_endpoint_url() ) ? true : false;
			$version        = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );
			$position       = C4WP_Functions::c4wp_get_option( 'wc_checkout_position', 'default' );
			$badge_position = C4WP_Functions::c4wp_get_option( 'badge_v3', 'bottomright' );

			if ( $is_wc_checkout ) {
				?>
				<style type="text/css" id="c4wp-wc-block-checkout-css">
					label[for="additional-information-c4wp/c4wp-wc-checkout"], input[id="additional-information-c4wp/c4wp-wc-checkout"], label[for="additional-information-c4wp/c4wp-wc-checkout-geo"], input[id="additional-information-c4wp/c4wp-wc-checkout-geo"], 
					label[for="additional-information-c4wp-c4wp-wc-checkout"], input[id="additional-information-c4wp-c4wp-wc-checkout"], label[for="additional-information-c4wp-c4wp-wc-checkout-geo"], input[id="additional-information-c4wp-c4wp-wc-checkout-geo"], 
					.wc-block-components-address-form__c4wp-c4wp-wc-checkout input, label[for="order-c4wp-c4wp-wc-checkout"] {
						display: none;
					}
				</style>
				<?php
			}
			if ( 'default' === $position ) {
				?>
				<style type="text/css" id="c4wp-checkout-css">
					.woocommerce-checkout .c4wp_captcha_field {
						margin-bottom: 10px;
						margin-top: 15px;
						position: relative;
						display: inline-block;
					}
				</style>
				<?php
			}
			if ( 'v2_invisible' === $version ) {
				?>
				<style type="text/css" id="c4wp-lp-form-css">
					.login-action-lostpassword.login form.shake {
						animation: none;
						animation-iteration-count: 0;
						transform: none !important;
					}
				</style>
				<?php
			}
			if ( 'v3' === $version && 'bottomleft' === $badge_position ) {
				?>
				<style type="text/css" id="c4wp-v3-badge-css">
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
			}
			if ( 'v3' === $version ) {
				?>
				<style type="text/css" id="c4wp-v3-lp-form-css">
					.login #login, .login #lostpasswordform {
						min-width: 350px !important;
					}
					.wpforms-field-c4wp iframe {
						width: 100% !important;
					}
				</style>
				<?php
			}
			if ( 'hcaptcha' === $version ) {
				?>
				<style type="text/css" id="c4wp-wc-hcaptcha-css">
					#order_review + .c4wp_captcha_field {
						position: relative;
						left: 15px;
						display: inline-block;
					}
				</style>
				<?php
			}
			?>
			<style type="text/css" id="c4wp-v3-lp-form-css">
				.login #login, .login #lostpasswordform {
					min-width: 350px !important;
				}
				.wpforms-field-c4wp iframe {
					width: 100% !important;
				}
			</style>
			<?php
		}

		/**
		 * Echo form field markup.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function form_field() {
			echo wp_kses( self::form_field_return(), C4WP_Functions::c4wp_allowed_kses_args() );
		}

		/**
		 * Return form field markup.
		 *
		 * @param string $return_markup - Original markup.
		 *
		 * @return string $return_markup - Markup with our field added.
		 *
		 * @since 7.6.0
		 */
		public static function form_field_return( $return_markup = '' ) {
			$field_markup  = '<!-- CAPTCHA added with CAPTCHA 4WP plugin. More information: https://captcha4wp.com -->';
			$field_markup .= apply_filters( 'c4wp_form_field_markup', self::captcha_form_field() );
			$field_markup .= '<!-- / CAPTCHA by CAPTCHA 4WP plugin -->';

			return $return_markup . $field_markup;
		}

		/**
		 * Displays the login form field if applicable.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function login_form_field() {
			if ( self::show_login_captcha() ) {
				self::form_field();
			}
		}

		/**
		 * Returns the login form field.
		 *
		 * @param string $field - Original markup.
		 *
		 * @return string $field - Field markup.
		 *
		 * @since 7.6.0
		 */
		public static function login_form_return( $field = '' ) {
			if ( self::show_login_captcha() ) {
				$field = self::form_field_return( $field );
			}
			return $field;
		}

		/**
		 * Determine if a captcha should be shown for a given login attempt.
		 *
		 * @return bool *show_captcha - Wether to show or now.
		 *
		 * @since 7.6.0
		 */
		public static function show_login_captcha() {
			$show_captcha = true;
			$ip           = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : false;
			$show_captcha = apply_filters( 'c4wp_login_captcha_filter', $show_captcha, $ip );

			return $show_captcha;
		}

		/**
		 * Add login form field to multisite form.
		 *
		 * @param WP_Error $errors - Error messages.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function ms_form_field( $errors ) {
			$err_messages = $errors->get_error_message( 'c4wp_error' );
			if ( $err_messages ) {
				echo '<p class="error">' . esc_attr( C4WP_Functions::c4wp_get_option( 'error_message' ) ) . '</p>';
			}
			$geo_err_messages = $errors->get_error_message( 'c4wp_geo_error' );
			if ( $geo_err_messages ) {
				echo '<p class="error">' . esc_attr( C4WP_Functions::c4wp_get_option( 'geo_blocked_message' ) ) . '</p>';
			}
			self::form_field();
		}

		/**
		 * Main verification function. Return if a submission is allowed or not.
		 *
		 * @param boolean $response - Current response.
		 * @param boolean $is_fallback_challenge - Is currently a failsafe.
		 *
		 * @return bool - Was request valid?
		 *
		 * @since 7.6.0
		 */
		public static function verify( $response = false, $is_fallback_challenge = false ) {
			$verify = C4WP_Method_Loader::method_verify( C4WP_Method_Loader::get_currently_selected_method( true, false ), $response, $is_fallback_challenge );

			if ( isset( $_POST['c4wp_geo_blocking_enabled'] ) && class_exists( 'C4WP\\Geo_Blocking' ) && isset( $_SERVER['REMOTE_ADDR'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$fails_additional_verify = apply_filters( 'c4wp_post_method_verify', false, $verify, Geo_Blocking::sanitize_incoming_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( $fails_additional_verify ) {
					return false;
				}
			}

			return $verify;
		}

		/**
		 * Verify a login attempt.
		 *
		 * @param WP_User $user - User object.
		 * @param string  $username - Login username.
		 *
		 * @return WP_User|WP_Error - Always return the user, WP Error otherwise.
		 *
		 * @since 7.6.0
		 */
		public static function login_verify( $user, $username = '' ) {
			global $wpdb;
			if ( ! $username ) {
				return $user;
			}

			// Bail if a rest request.
			if ( self::is_rest_request() ) {
				return $user;
			}


			// Do not authenticate WC login if form is not enabled.
			if ( isset( $_POST['woocommerce-login-nonce'] ) && ! C4WP_Functions::c4wp_is_form_enabled( 'wc_login' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $user;
			}

			$show_captcha = self::show_login_captcha();

			// Ignore nonce check as we only use as flag.
			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:disable
				if ( $show_captcha && ! self::verify() ) {
					return new \WP_Error( 'c4wp_error', self::add_error_to_mgs() );
				}
			}

			return $user;
		}

		/**
		 * Checks if the current authentication request is RESTy or a custom URL where it should not load.
		 *
		 * @return boolean - Was a rest request?
		 * 
		 * @since 7.6.0
		 */
		public static function is_rest_request() {
			if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( isset( $_GET['rest_route'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/', 0 ) === 0 ) ) {
				return true;
			}

			global $wp_rewrite;
			if ( null === $wp_rewrite ) {
				$wp_rewrite = new \WP_Rewrite();
			}

			$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
			$current_url = wp_parse_url( add_query_arg( array() ) );

			// If no path to check, return.
			if ( ! isset( $current_url['path'] ) || ! isset( $rest_url['path'] ) ) {
				return apply_filters( 'c4wp_is_rest_request_no_path_found', false );
			}

			$is_rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;

			return $is_rest;
		}

		/**
		 * Verify a registration attempt.
		 *
		 * @param WP_Error $errors - Current error array.
		 * @param string   $sanitized_user_login - Current user login.
		 * @param string   $user_email - User email address.
		 * 
		 * @return WP_Error - Error array with ours added, if applicable.
		 * 
		 * @since 7.6.0
		 */
		public static function registration_verify( $errors, $sanitized_user_login, $user_email ) {
			if ( ! self::verify() ) {
				$errors->add( 'c4wp_error', self::add_error_to_mgs() );
			}
			return $errors;
		}

		/**
		 * Verify a new user signup on a multisite network.
		 *
		 * @param array $result - Error array.
		 * 
		 * @return array $result - Error array with ours added, if applicable.
		 * 
		 * @since 7.6.0
		 */
		public static function ms_form_field_verify( $result ) {
			if ( isset( $_POST['c4wp_geo_blocking_enabled'] ) && class_exists( 'C4WP\\Geo_Blocking' ) && \C4WP\Geo_Blocking::should_submission_be_geo_blocked() ) {
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'geo_blocked_message' ) );
			} elseif ( isset( $_POST['stage'] ) && 'validate-user-signup' === $_POST['stage'] && ! self::verify() ) {
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'error_message' ) );
			}

			return $result;
		}

		/**
		 * Verify a new user signup on a WPMU form.
		 *
		 * @param array $result - Error array.
		 * 
		 * @return array $result - Error array with ours added, if applicable.
		 * 
		 * @since 7.6.0
		 */
		public static function ms_blog_verify( $result ) {
			if ( isset( $_POST['c4wp_geo_blocking_enabled'] ) && class_exists( 'C4WP\\Geo_Blocking' ) && \C4WP\Geo_Blocking::should_submission_be_geo_blocked() ) {
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'geo_blocked_message' ) );
			} elseif ( ! self::verify() ) {
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'error_message' ) );
			}

			return $result;
		}

		/**
		 * Verify lost password form submission.
		 *
		 * @param WP_Error $result - Current errors.
		 * @param int      $user_id - User ID.
		 * 
		 * @return WP_Error - Error array with ours added, if applicable.
		 */
		public static function lostpassword_verify( $result, $user_id ) {

			// Allow admins to send reset links.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) {
				return $result;
			}

			// Do not authenticate WC login if form is not enabled.
			if ( isset( $_POST['wc_reset_password'] ) && isset( $_POST['woocommerce-lost-password-nonce'] ) && ! C4WP_Functions::c4wp_is_form_enabled( 'wc_lost_password' ) ) {
				return $result;
			}

			if ( ! self::verify() ) {
				$result->add( 'c4wp_error', self::add_error_to_mgs() );
			}

			return $result;
		}

		/**
		 * Verify password reset submissions.
		 *
		 * @param WP_Error $errors - Current errors.
		 * @param WP_User  $user - User object.
		 * 
		 * @return WP_Error|WP_User - User object or error.
		 * 
		 * @since 7.6.0
		 */
		public static function reset_password_verify( $errors, $user ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			// Allow admins to send reset links. Ignore PHPCS as we only use as flag.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $errors;
			}

			// Ignore nonce check as we dont process form data.
			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:disable
				if ( ! self::verify() ) {
					$errors->add( 'c4wp_error', self::add_error_to_mgs() );
				}
			}

			return $errors;
		}

		/**
		 * Verify comment submissions.
		 *
		 * @param array $commentdata - Submitted data.
		 * 
		 * @return array - New comment data.
		 * 
		 * @since 7.6.0
		 */
		public static function comment_verify_old( $commentdata ) {
			$auto_detect = C4WP_Functions::c4wp_get_option( 'language_handling' );

			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) {
				$verify = self::verify();

				if ( class_exists( 'C4WP\\Geo_Blocking' ) && isset( $_SERVER['REMOTE_ADDR'] ) ) {
					$fails_additional_verify = apply_filters( 'c4wp_post_method_verify', true, $verify, Geo_Blocking::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), 'comment_verify' );

					if ( $fails_additional_verify ) {
						$mgs = C4WP_Functions::c4wp_get_option( 'comment_blocked_message', '' );
						wp_die(
							'<p>' . wp_kses_post( $mgs ) . '</p>',
							esc_html__( 'Comment Submission Failure' ),
							array(
								'response'  => 403,
								'back_link' => true,
							)
						);
					}
				}

				if ( ! $verify ) {
					wp_die(
						'<p>' . wp_kses_post( self::add_error_to_mgs() ) . '</p>',
						esc_html__( 'Comment Submission Failure' ),
						array(
							'response'  => 403,
							'back_link' => true,
						)
					);
				}
			}

			return $commentdata;
		}

		/**
		 * Very comment in WP 4.9 and older.
		 *
		 * @param  bool $approved - Approval currently.
		 * 
		 * @return bool $approved - Our approval.
		 * 
		 * @since 7.6.0
		 */
		public static function comment_verify( $approved ) {
			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) {
				$verify = C4WP_Method_Loader::method_verify( C4WP_Method_Loader::get_currently_selected_method( true, false ), false, false );

				if ( ! $verify ) {
					if ( class_exists( 'C4WP\\C4WP_Hide_Captcha' ) ) {
						if ( is_user_logged_in() && C4WP_Hide_Captcha::c4wp_hide_for_logged_in_user_or_role() ) {
							return $approved;
						}
					}
					return new \WP_Error( 'c4wp_error', self::add_error_to_mgs(), 403 );
				}
			}
			return $approved;
		}

		/**
		 * Checks if the current page load is actually an iframe found in the new customizer/widgets areas within WP 5.8+.
		 *
		 * @return bool - Should embed or not?
		 * 
		 * @since 7.6.0
		 */
		public static function check_should_js_embed() {
			// Ensure we dont load inside an iframe/preview.
			if ( isset( $_GET['legacy-widget-preview'] ) || isset( $_GET['customize_messenger_channel'] ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Auto detects language if applicable, otherwise returns the users desired language from the settings.
		 *
		 * @return string - desired language.
		 * 
		 * @since 7.6.0
		 */
		public static function determine_captcha_language() {
			$language = trim( C4WP_Functions::c4wp_get_option( 'language' ) );

			$language = apply_filters( 'c4wp_captcha_language_filter', $language );

			/* @free:start */
			$lang = '&hl=' . $language;
			/* @free:end */
			return $lang;
		}
	}
}
