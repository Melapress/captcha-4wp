<?php
/**
 * Main enforcement logic.
 *
 * @package C4WP
 */

namespace C4WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use C4WP\C4WP_Functions as C4WP_Functions;
use C4WP\Methods\C4WP_Method_Loader as C4WP_Method_Loader;
use C4WP\C4WP_Hide_Captcha as C4WP_Hide_Captcha;

if ( ! class_exists( 'C4WP_Captcha_Class' ) ) {

	/**
	 * Main class.
	 */
	class C4WP_Captcha_Class {

		/**
		 * Class instance.
		 *
		 * @var C4WP_Captcha_Class
		 */
		private static $instance;

		/**
		 * Counter for number of captchas found within an page.
		 *
		 * @var integer
		 */
		private static $captcha_count = 0;

		/**
		 * Class initiator.
		 *
		 * @return $instance - C4WP_Pro instance.
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
		 */
		public static function actions_filters() {
			if ( C4WP_Functions::c4wp_is_form_enabled( 'login' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				add_action( 'login_form', array( __CLASS__, 'login_form_field' ), 99 );
				add_filter( 'login_form_middle', array( __CLASS__, 'login_form_return' ), 99 );
				add_action( 'um_after_login_fields', array( __CLASS__, 'login_form_field' ), 99 );
				add_filter( 'authenticate', array( __CLASS__, 'login_verify' ), 999, 3 );
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
					add_filter( 'pre_comment_approved', array( __CLASS__, 'comment_verify_490' ), 99 );
				} else {
					add_filter( 'preprocess_comment', array( __CLASS__, 'comment_verify' ) );
				}
			}

			add_action( 'wp_ajax_c4wp_ajax_verify', array( __CLASS__, 'c4wp_ajax_verify' ), 10, 1 );
			add_action( 'wp_ajax_nopriv_c4wp_ajax_verify', array( __CLASS__, 'c4wp_ajax_verify' ), 10, 1 );
			add_action( 'wp_ajax_c4wp_nocaptcha_plugin_notice_ignore', array( 'C4WP_Settings', 'c4wp_nocaptcha_plugin_notice_ignore' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

			add_action( 'wp_footer', array( __CLASS__, 'footer_script' ), 99999 );
			add_action( 'login_footer', array( __CLASS__, 'footer_script' ), 99999 );


			add_action( 'wp_ajax_c4wp_validate_secret_key', array( __CLASS__, 'c4wp_validate_secret_key' ), 10, 1 );
			add_action( 'wp_ajax_nopriv_c4wp_validate_secret_key', array( __CLASS__, 'c4wp_validate_secret_key' ), 10, 1 );

		}

		/**
		 * Add settings page scripts.
		 *
		 * @return void
		 */
		public static function admin_enqueue_scripts() {
			wp_enqueue_script( 'c4wp-notices', C4WP_PLUGIN_URL . 'assets/js/notices.js', array( 'jquery' ), C4WP_PLUGIN_VERSION, false );
		}

		/**
		 * A neat wrapper to provide a response verification via AJAX call.
		 */
		public static function c4wp_ajax_verify() {
			$method     = C4WP_Method_Loader::get_currently_selected_method( true, true );
			$url        = apply_filters( 'c4wp_google_verify_url', sprintf( $method::get_verify_url() ) );
			$remoteip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$secret_key = trim( C4WP_Functions::c4wp_get_option( 'secret_key' ) );
			$verify     = false;

			// Grab POSTed data.
			$posted   = filter_input_array( INPUT_POST );
			$nonce    = sanitize_text_field( $posted[ 'nonce' ] );
			$response = sanitize_text_field( $posted[ 'response' ] );

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

		public static function c4wp_validate_secret_key() {
			$verify     = false;

			// Grab POSTed data.
			$posted   = filter_input_array( INPUT_POST );
			$nonce    = sanitize_text_field( $posted[ 'nonce' ] );
			$response = sanitize_text_field( $posted[ 'response' ] );
			$secret   = sanitize_text_field( $posted[ 'secret' ] );
			$method   = sanitize_text_field( $posted[ 'method' ] );
			$remoteip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			// Check nonce.
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'c4wp_validate_secret_key_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
			}

			$url = '';

			$captcha_methods = array(
                'v2_checkbox',
                'v2_invisible',
                'v3'
            );

            if ( in_array( $method, $captcha_methods, true ) ) {
				$method = 'captcha';
			}

			if ( method_exists( C4WP_Method_Loader::$methods[$method], 'get_verify_url' ) ) {
                $url = C4WP_Method_Loader::$methods[$method]::get_verify_url();
            };

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
		 * @return $code - Markup
		 */
		public static function c4wp_ajax_verification_scripts() {
			$ajax_url    = admin_url( 'admin-ajax.php' );
			$flag_markup = '<input id="c4wp_ajax_flag" type="hidden" name="c4wp_ajax_flag" value="c4wp_ajax_flag">';

			$fail_action         = C4WP_Functions::c4wp_get_option( 'failure_action', 'nothing' );
			$redirect            = C4WP_Functions::c4wp_get_option( 'failure_redirect' );
			$failure_v2_site_key = C4WP_Functions::c4wp_get_option( 'failure_v2_site_key' );

			$code = "

			var parentElem = captcha_div.parentElement;

			if ( ! form.classList.contains( 'c4wp_verify_underway' ) && captcha_div.parentElement.getAttribute( 'data-c4wp-use-ajax' ) == 'true' ) {
				form.classList.add('c4wp_verify_underway' );
				const flagMarkup =  '" . $flag_markup . "';
				var flagMarkupDiv = document.createElement('div');
				flagMarkupDiv.innerHTML = flagMarkup.trim();

				form.appendChild( flagMarkupDiv );
	
				var nonce = captcha_div.parentElement.getAttribute( 'data-nonce' );

				var post_data = {
					'action'   : 'c4wp_ajax_verify',
					'nonce'    : nonce,
					'response' : data
				};

				var formData = new FormData();

				formData.append( 'action', 'c4wp_ajax_verify' );
				formData.append( 'nonce', nonce );
				formData.append( 'response', data );
				
				fetch( '" . $ajax_url . "', {
					method: 'POST',
					body: formData,
				} ) // wrapped
					.then( 
						res => res.json()
					)
					.then( data => {
						if ( data['success'] ) {
							form.classList.add( 'c4wp_verified' );
							if ( typeof jQuery !== 'undefined' && form_type == 'wc_checkout' ) {
								form.classList.add( 'c4wp_v2_fallback_active' );
								jQuery( '.woocommerce-checkout' ).submit();
								return true;
							} else if ( typeof jQuery !== 'undefined' && form_type == 'wc_login' ) {		
								jQuery( '.woocommerce-form-login__submit' ).trigger('click');
								return true;
							} else if ( typeof jQuery !== 'undefined' && form_type == 'wc_reg' ) {		
								jQuery( '.woocommerce-form-register__submit' ).trigger('click');
								return true;
							} else if ( typeof jQuery !== 'undefined' && form_type == 'bp_comment' ) {
								return true;
							} else if ( typeof jQuery !== 'undefined' && form_type == 'bp_group' ) {							
								jQuery( '#group-creation-create' ).trigger('click');
							} else if ( typeof jQuery !== 'undefined' && form_type == 'bp_signup' ) {								
								jQuery( '#submit' ).trigger('click');
							} else {
								if ( typeof form.submit === 'function' ) {
									form.submit();
								} else {
									HTMLFormElement.prototype.submit.call(form);
								}
							}
						} else {
							";

			if ( 'redirect' === $fail_action ) {
				$code .= "
									window.location.href = '" . $redirect . "';
								";
			}

			if ( 'v2_checkbox' === $fail_action ) {
				$code .= "
									captcha_div.innerHTML = '';
									form.classList.add( 'c4wp_v2_fallback_active' );
									flagMarkupDiv.firstChild.setAttribute( 'name', 'c4wp_v2_fallback' );

									var c4wp_captcha = grecaptcha.render( captcha_div,{
										'sitekey' : '" . $failure_v2_site_key . "',							
											'expired-callback' : function(){
												grecaptcha.reset( c4wp_captcha );
											}
									}); 
								";
			}

							$code .= '						
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
			';

			return $code;
		}

		/**
		 * Return our error message, for use with WP_Error etc.
		 *
		 * @param boolean $mgs - Current message.
		 * @return string $message - Our message.
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
		 */
		public static function total_captcha() {
			return self::$captcha_count;
		}

		/**
		 * Create and return captcha field markup.
		 *
		 * @return string $field - Field HTML Markup.
		 */
		public static function captcha_form_field() {
			self::$captcha_count++;
			$field = C4WP_Method_Loader::get_form_field( C4WP_Method_Loader::get_currently_selected_method( true, false ), self::total_captcha() );
			return $field;
		}

		/**
		 * Prints plugin scripts to the footer when needed.
		 *
		 * @return void
		 */
		public static function footer_script() {
			static $included = false;

			$number  = self::total_captcha();
			$version = C4WP_Functions::c4wp_get_option( 'captcha_version', 'v2_checkbox' );

			$number = ( function_exists( 'is_buddypress' ) && is_buddypress() && 'v2_invisble' !== $version ) ? 1 : $number;

			if ( ! $number && ( 'v3' !== $version || 'all_pages' !== C4WP_Functions::c4wp_get_option( 'v3_script_load', 'all_pages' ) ) ) {
				return;
			}

			if ( $included ) {
				return;
			}

			// Ensure JS is not embedded if veiwing from within the WP customizer or widgets admin areas.
			if ( ! self::check_should_js_embed() ) {
				return;
			}


			$included = true;

			C4WP_Method_Loader::get_footer_scripts( C4WP_Method_Loader::get_currently_selected_method( true, false ) );

		}


		/**
		 * Echo form field markup.
		 *
		 * @return void
		 */
		public static function form_field() {
			echo self::form_field_return(); // phpcs:ignore
		}

		/**
		 * Return form field markup.
		 *
		 * @param string $return - Orignal markup.
		 * @return string $return - Markup with our field added.
		 */
		public static function form_field_return( $return = '' ) {

			$field_markup = apply_filters( 'c4wp_form_field_markup', self::captcha_form_field() ); 

			return $return . $field_markup;
		}

		/**
		 * Displays the login form field if applicable.
		 *
		 * @return void
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
		 * @return string $field - Field markup.
		 */
		public function login_form_return( $field = '' ) {
			if ( $this->show_login_captcha() ) {
				$field = $this->form_field_return( $field );
			}
			return $field;
		}

		/**
		 * Determine if a captcha should be shown for a given login attempt.
		 *
		 * @return bool *show_captcha - Wether to show or now.
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
		 * @return void
		 */
		public function ms_form_field( $errors ) {
			$err_messages = $errors->get_error_message( 'c4wp_error' );
			if ( $err_messages ) {
				echo '<p class="error">' . esc_attr( $errmsg ) . '</p>';
			}
			$this->form_field();
		}

		/**
		 * Main verification function. Return if a submission is allowed or not.
		 *
		 * @param boolean $response - Current response.
		 * @return bool - Was request valid?
		 */
		public static function verify( $response = false ) {
			$verify = C4WP_Method_Loader::method_verify( C4WP_Method_Loader::get_currently_selected_method( true, false ), $response );
			return $verify;
		}

		/**
		 * Verify a login attempt.
		 *
		 * @param WP_User $user - User object.
		 * @param string  $username - Login username.
		 * @param string  $password - Login password.
		 * @return WP_User|WP_Error - Always return the user, WP Error otherwise.
		 */
		public static function login_verify( $user, $username = '', $password = '' ) {
			global $wpdb;
			if ( ! $username ) {
				return $user;
			}

			// Bail if a rest request.
			if ( self::is_rest_request() ) {
				return $user;
			}


			$show_captcha = self::show_login_captcha();

			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:ignore
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
		 */
		public static function is_rest_request() {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST || isset( $_GET['rest_route'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/', 0 ) === 0 ) {
				return true;
			}

			global $wp_rewrite;
			if ( null === $wp_rewrite ) {
				$wp_rewrite = new \WP_Rewrite(); // phpcs:ignore
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
		 * @return WP_Error - Error array with ours added, if applicable.
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
		 * @return array $result - Error array with ours added, if applicable.
		 */
		public static function ms_form_field_verify( $result ) {
			if ( isset( $_POST['stage'] ) && 'validate-user-signup' === $_POST['stage'] && ! $this->verify() ) { // phpcs:ignore
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'error_message' ) );
			}

			return $result;
		}

		/**
		 * Verify a new user signup on a WPMU form.
		 *
		 * @param array $result - Error array.
		 * @return array $result - Error array with ours added, if applicable.
		 */
		public static function ms_blog_verify( $result ) {
			if ( ! $this->verify() ) {
				$result['errors']->add( 'c4wp_error', C4WP_Functions::c4wp_get_option( 'error_message' ) );
			}

			return $result;
		}

		/**
		 * Verify lost password form submissin.
		 *
		 * @param WP_Error $result - Current errors.
		 * @param int      $user_id - User ID.
		 * @return WP_Error - Error array with ours added, if applicable.
		 */
		public static function lostpassword_verify( $result, $user_id ) {

			// Allow admins to send reset links.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) {
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
		 * @return WP_Error|WP_User - User object or error.
		 */
		public static function reset_password_verify( $errors, $user ) {
			// Allow admins to send reset links.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) {
				return $errors;
			}

			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:ignore
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
		 * @return array - New comment data.
		 */
		public static function comment_verify( $commentdata ) {
			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:ignore
				if ( ! self::verify() ) {
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
		 * @returnb ool $approved - Our approval.
		 */
		public static function comment_verify_490( $approved ) {

			if ( ! isset( $_POST['c4wp_ajax_flag'] ) ) { // phpcs:ignore
				if ( ! self::verify() ) {
					return new \WP_Error( 'c4wp_error', self::add_error_to_mgs(), 403 );
				}
			}
			return $approved;
		}

		/**
		 * Checks if the current page load is actually an iframe found in the new customizer/widgets areas within WP 5.8+.
		 *
		 * @return bool
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
		 * @return string
		 */
		public static function determine_captcha_language() {
			$language = trim( C4WP_Functions::c4wp_get_option( 'language' ) );
			$auto_detect = C4WP_Functions::c4wp_get_option( 'language_handling' );

			$lang = '';
			if ( 'manually_choose' === $auto_detect ) {
				$lang = '&hl=' . $language;
			} else {
				$lang = '&hl=' . get_bloginfo( 'language' );
			}

			$lang = '&hl=' . $language;
			return $lang;
		}
	} //END CLASS
} //ENDIF


