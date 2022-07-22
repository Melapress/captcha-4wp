<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName, WordPress.WP.EnqueuedResources.NonEnqueuedScript, WordPress.Security.NonceVerification.Missing, WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification.Recommended, WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! class_exists( 'C4wp_Captcha_Class' ) ) {

	/**
	 * Main class.
	 */
	class C4wp_Captcha_Class {

		/**
		 * Class instance.
		 *
		 * @var C4wp_Captcha_Class
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
		public function actions_filters() {
			if ( c4wp_is_form_enabled( 'login' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				add_action( 'login_form', array( $this, 'login_form_field' ), 99 );
				add_filter( 'login_form_middle', array( $this, 'login_form_return' ), 99 );
				add_action( 'um_after_login_fields', array( $this, 'login_form_field' ), 99 );
				add_filter( 'authenticate', array( $this, 'login_verify' ), 999, 3 );
			}

			if ( c4wp_is_form_enabled( 'registration' ) ) {
				add_action( 'register_form', array( $this, 'form_field' ), 99 );
				add_filter( 'registration_errors', array( $this, 'registration_verify' ), 10, 3 );
			}

			if ( c4wp_is_form_enabled( 'ms_user_signup' ) && is_multisite() ) {
				add_action( 'signup_extra_fields', array( $this, 'ms_form_field' ), 99 );
				add_filter( 'wpmu_validate_user_signup', array( $this, 'ms_form_field_verify' ) );
				add_action( 'signup_blogform', array( $this, 'ms_form_field' ), 99 );
				add_filter( 'wpmu_validate_blog_signup', array( $this, 'ms_blog_verify' ) );
			}

			if ( c4wp_is_form_enabled( 'lost_password' ) ) {
				add_action( 'lostpassword_form', array( $this, 'form_field' ), 99 );
				add_action( 'lostpassword_post', array( $this, 'lostpassword_verify' ), 10, 2 );
			}

			if ( c4wp_is_form_enabled( 'reset_password' ) ) {
				add_action( 'resetpass_form', array( $this, 'form_field' ), 99 );
				add_filter( 'validate_password_reset', array( $this, 'reset_password_verify' ), 10, 2 );
			}

			if ( c4wp_is_form_enabled( 'comment' ) && ( ! is_admin() || ! current_user_can( 'moderate_comments' ) ) ) {
				if ( ! is_user_logged_in() ) {
					add_action( 'comment_form_after_fields', array( $this, 'form_field' ), 99 );
				} else {
					add_filter( 'comment_form_field_comment', array( $this, 'form_field_return' ), 99 );
				}
				if ( version_compare( get_bloginfo( 'version' ), '4.9.0', '>=' ) ) {
					add_filter( 'pre_comment_approved', array( $this, 'comment_verify_490' ), 99 );
				} else {
					add_filter( 'preprocess_comment', array( $this, 'comment_verify' ) );
				}
			}
		}

		/**
		 * Return our error message, for use with WP_Error etc.
		 *
		 * @param boolean $mgs - Current message.
		 * @return string $message - Our message.
		 */
		public function add_error_to_mgs( $mgs = false ) {
			if ( false === $mgs ) {
				$mgs = c4wp_get_option( 'error_message', '' );
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
		public function total_captcha() {
			return self::$captcha_count;
		}

		/**
		 * Create and return captcha field markup.
		 *
		 * @return string $field - Field HTML Markup.
		 */
		public function captcha_form_field() {
			self::$captcha_count++;
			$site_key = trim( c4wp_get_option( 'site_key' ) );
			$number   = $this->total_captcha();
			$version  = c4wp_get_option( 'captcha_version', 'v2_checkbox' );

			$field = '<div class="c4wp_captcha_field" style="margin-bottom: 10px;"><div id="c4wp_captcha_field_' . $number . '" class="c4wp_captcha_field_div">';
			if ( 'v3' === $version ) {
				$field .= '<input type="hidden" name="g-recaptcha-response" aria-label="do not use" aria-readonly="true" value=""/>';
			}
			$field .= '</div></div>';
			return $field;
		}

		/**
		 * Prints plugin scripts to the footer when needed.
		 *
		 * @return void
		 */
		public function footer_script() {
			static $included = false;

			$number  = $this->total_captcha();
			$version = c4wp_get_option( 'captcha_version', 'v2_checkbox' );

			if ( ! $number && ( 'v3' !== $version || 'all_pages' !== c4wp_get_option( 'v3_script_load', 'all_pages' ) ) ) {
				return;
			}

			if ( $included ) {
				return;
			}

			// Ensure JS is not embedded if veiwing from within the WP customizer or widgets admin areas.
			if ( ! $this->check_should_js_embed() ) {
				return;
			}

			$included = true;

			if ( 'v2_checkbox' === $version ) {
				$this->v2_checkbox_script();
			} elseif ( 'v2_invisible' === $version ) {
				$this->v2_invisible_script();
			} elseif ( 'v3' === $version ) {
				$this->v3_script();
			}
		}

		/**
		 * V2 checkboc inline script.
		 *
		 * @return void
		 */
		public function v2_checkbox_script() {
			?>
			<script type="text/javascript">
				var c4wp_onloadCallback = function() {
					for ( var i = 0; i < document.forms.length; i++ ) {
						var form = document.forms[i];
						var captcha_div = form.querySelector( '.c4wp_captcha_field_div:not(.rendered)' );
						if ( null === captcha_div )
							continue;						
						captcha_div.innerHTML = '';
						( function( form ) {
							var c4wp_captcha = grecaptcha.render( captcha_div,{
								'sitekey' : '<?php echo esc_js( trim( c4wp_get_option( 'site_key' ) ) ); ?>',
								'size'  : '<?php echo esc_js( c4wp_get_option( 'size', 'normal' ) ); ?>',
								'theme' : '<?php echo esc_js( c4wp_get_option( 'theme', 'light' ) ); ?>'
							});
							captcha_div.classList.add( 'rendered' );
							<?php
								$additonal_js = apply_filters( 'c4wp_captcha_callback_additonal_js', '' );
								echo $additonal_js;
							?>
						})(form);
					}
				};
			
			</script>
			<?php
			$lang       = $this->determine_captcha_language();
			$google_url = apply_filters( 'c4wp_v2_checkbox_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang, c4wp_recaptcha_domain() ), $lang );
			?>
			<script src="<?php echo esc_url( $google_url ); ?>"
				async defer>
			</script>
			<?php
		}

		/**
		 * V2 invisible inline script.
		 *
		 * @return void
		 */
		public function v2_invisible_script() {
			?>
			<script type="text/javascript">
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
								'sitekey' : '<?php echo esc_js( trim( c4wp_get_option( 'site_key' ) ) ); ?>',
								'size'  : 'invisible',
								'theme' : '<?php echo esc_js( c4wp_get_option( 'theme', 'light' ) ); ?>',
								'badge' : '<?php echo esc_js( c4wp_get_option( 'badge', 'bottomright' ) ); ?>',
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
								echo $additonal_js;
							?>
							form.onsubmit = function( e ){
								if ( 'yes' === form.getAttribute( 'data-captcha-valid' ) ) {
									return true;
								}

								e.preventDefault();
								grecaptcha.execute( c4wp_captcha );
								return false;
							};
							// Remove and append badge on WP login screen.
							jQuery( '.login form.shake .grecaptcha-badge' ).appendTo( 'body' );
						})(form);
					}
				};
			</script>
			<?php
			$lang       = $this->determine_captcha_language();
			$google_url = apply_filters( 'c4wp_v2_invisible_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?onload=c4wp_onloadCallback&render=explicit' . $lang, c4wp_recaptcha_domain() ), $lang );
			?>
			<script src="<?php echo esc_url( $google_url ); ?>"
				async defer>
			</script>
			<?php
		}

		/**
		 * V3 inline scripts.
		 *
		 * @return void
		 */
		public function v3_script() {

			$site_key = trim( c4wp_get_option( 'site_key' ) );
			$lang     = $this->determine_captcha_language();
			$position = c4wp_get_option( 'badge_v3', 'bottomright' );

			$google_url = apply_filters( 'c4wp_v3_script_api_src', sprintf( 'https://www.%s/recaptcha/api.js?render=' . $site_key . $lang, c4wp_recaptcha_domain() ), $site_key, $lang );
			?>
			<script src="<?php echo esc_url( $google_url ); ?>"></script>
			<script type="text/javascript">
				( function( grecaptcha ) {

					var c4wp_onloadCallback = function() {
						grecaptcha.execute(
							'<?php echo esc_js( $site_key ); ?>',
							{ action: 'advanced_nocaptcha_recaptcha' }
						).then( function( token ) {
							for ( var i = 0; i < document.forms.length; i++ ) {
								var form = document.forms[i];
								var captcha = form.querySelector( 'input[name="g-recaptcha-response"]' );
								if ( null === captcha )
									continue;

								captcha.value = token;
							}
							// Apply relevent accessibility attributes to response.
							var responseTextareas = document.querySelectorAll(".g-recaptcha-response");
							responseTextareas.forEach(function(textarea) {
								textarea.setAttribute("aria-hidden", "true");
								textarea.setAttribute("aria-label", "do not use");
								textarea.setAttribute("aria-readonly", "true");
							});
						});
					};

					grecaptcha.ready( c4wp_onloadCallback );

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

		/**
		 * Echo form field markup.
		 *
		 * @return void
		 */
		public function form_field() {
			echo $this->form_field_return();
		}

		/**
		 * Return form field markup.
		 *
		 * @param string $return - Orignal markup.
		 * @return string $return - Markup with our field added.
		 */
		public function form_field_return( $return = '' ) {
			return $return . $this->captcha_form_field();
		}

		/**
		 * Displays the login form field if applicable.
		 *
		 * @return void
		 */
		public function login_form_field() {
			if ( $this->show_login_captcha() ) {
				$this->form_field();
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
		public function show_login_captcha() {
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
		public function verify( $response = false ) {
			static $last_verify        = null;
			static $last_response      = null;
			static $duplicate_response = false;

			$remoteip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';


			$secret_key = trim( c4wp_get_option( 'secret_key' ) );
			$verify     = false;


			if ( false === $response ) {
				$response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';
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

			if ( ! $response || ! $remoteip ) {
				return $verify;
			}

			if ( null !== $last_verify ) {
				return $last_verify;
			}

			$url = apply_filters( 'c4wp_google_verify_url', sprintf( 'https://www.%s/recaptcha/api/siteverify', c4wp_recaptcha_domain() ) );

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
				if ( 'v3' === c4wp_get_option( 'captcha_version' ) ) {
					$score  = isset( $result['score'] ) ? $result['score'] : 0;
					$action = isset( $result['action'] ) ? $result['action'] : '';
					$verify = c4wp_get_option( 'score', '0.5' ) <= $score && 'advanced_nocaptcha_recaptcha' === $action;
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
		public function login_verify( $user, $username = '', $password = '' ) {
			global $wpdb;
			if ( ! $username ) {
				return $user;
			}

			// Bail if a rest request.
			if ( $this->is_rest_request() ) {
				return $user;
			}


			$show_captcha = $this->show_login_captcha();

			if ( $show_captcha && ! $this->verify() ) {
				// Bail if we have nothign to work with.
				if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
					return $user;
				}
				return new WP_Error( 'c4wp_error', $this->add_error_to_mgs() );
			}

			return $user;
		}

		/**
		 * Checks if the current authentication request is RESTy or a custom URL where it should not load.
		 *
		 * @return boolean - Was a rest request?
		 */
		public function is_rest_request() {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST || isset( $_GET['rest_route'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/', 0 ) === 0 ) {
				return true;
			}

			global $wp_rewrite;
			if ( null === $wp_rewrite ) {
				$wp_rewrite = new WP_Rewrite();
			}

			$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
			$current_url = wp_parse_url( add_query_arg( array() ) );
			$is_rest     = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;

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
		public function registration_verify( $errors, $sanitized_user_login, $user_email ) {
			if ( ! $this->verify() ) {
				// Bail if we have nothign to work with.
				if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
					return $errors;
				}
				$errors->add( 'c4wp_error', $this->add_error_to_mgs() );
			}
			return $errors;
		}

		/**
		 * Verify a new user signup on a multisite network.
		 *
		 * @param array $result - Error array.
		 * @return array $result - Error array with ours added, if applicable.
		 */
		public function ms_form_field_verify( $result ) {
			if ( isset( $_POST['stage'] ) && 'validate-user-signup' === $_POST['stage'] && ! $this->verify() ) {
				$result['errors']->add( 'c4wp_error', c4wp_get_option( 'error_message' ) );
			}

			return $result;
		}

		/**
		 * Verify a new user signup on a WPMU form.
		 *
		 * @param array $result - Error array.
		 * @return array $result - Error array with ours added, if applicable.
		 */
		public function ms_blog_verify( $result ) {
			if ( ! $this->verify() ) {
				$result['errors']->add( 'c4wp_error', c4wp_get_option( 'error_message' ) );
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
		public function lostpassword_verify( $result, $user_id ) {

			// Allow admins to send reset links.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) {
				return $result;
			}

			if ( ! $this->verify() ) {
				// Bail if we have nothign to work with.
				if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
					return $result;
				}
				$result->add( 'c4wp_error', $this->add_error_to_mgs() );
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
		public function reset_password_verify( $errors, $user ) {
			// Allow admins to send reset links.
			if ( current_user_can( 'manage_options' ) && isset( $_REQUEST['action'] ) && in_array( wp_unslash( $_REQUEST['action'] ), array( 'resetpassword', 'send-password-reset' ), true ) ) {
				return $errors;
			}

			if ( ! $this->verify() ) {
				// Bail if we have nothign to work with.
				if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
					return $user;
				}
				$errors->add( 'c4wp_error', $this->add_error_to_mgs() );
			}

			return $errors;
		}

		/**
		 * Verify comment submissions.
		 *
		 * @param array $commentdata - Submitted data.
		 * @return array - New comment data.
		 */
		public function comment_verify( $commentdata ) {
			if ( ! $this->verify() ) {
				wp_die(
					'<p>' . $this->add_error_to_mgs() . '</p>',
					esc_html__( 'Comment Submission Failure' ),
					array(
						'response'  => 403,
						'back_link' => true,
					)
				);
			}

			return $commentdata;
		}

		/**
		 * Very comment in WP 4.9 and older.
		 *
		 * @param  bool $approved - Approval currently.
		 * @returnb ool $approved - Our approval.
		 */
		public function comment_verify_490( $approved ) {
			if ( ! $this->verify() ) {
				return new WP_Error( 'c4wp_error', $this->add_error_to_mgs(), 403 );
			}
			return $approved;
		}

		/**
		 * Checks if the current page load is actually an iframe found in the new customizer/widgets areas within WP 5.8+.
		 *
		 * @return bool
		 */
		public function check_should_js_embed() {
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
		public function determine_captcha_language() {
			$language = trim( c4wp_get_option( 'language' ) );
			$auto_detect = c4wp_get_option( 'language_handling' );

			$lang = '';
			if ( 'manually_choose' === $auto_detect ) {
				$lang = '&hl=' . $language;
			} else {
				$lang = '&hl=' . get_bloginfo( 'language' );
			}

			/*
			 */
			$lang = '&hl=' . $language;
			return $lang;
		}
	} //END CLASS
} //ENDIF

add_action( 'init', array( C4wp_Captcha_Class::init(), 'actions_filters' ), -9 );

