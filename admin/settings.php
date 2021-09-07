<?php

class ANR_Settings {

	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'settings_save' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( ANR_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		if ( anr_same_settings_for_all_sites() ) {
			add_action( 'network_admin_menu', array( $this, 'network_menu_page' ) );
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( ANR_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'menu_page' ) );
		}

		// Admin notices, temporary.
		add_action('admin_notices', [ $this, 'adv_nocaptcha_plugin_notice' ] );
		add_action('admin_init', [ $this, 'adv_nocaptcha_plugin_notice_ignore'] );
	}

	function adv_nocaptcha_plugin_notice() {			
		global $current_user;		
		$user_id = $current_user->ID;
		$current_screen = get_current_screen();

		$logo_img_src = ANR_PLUGIN_URL . 'assets/img/hero-logo.png';

		// General notice in dashboard.
		if ( current_user_can( 'manage_options' ) && ! get_user_meta( $user_id, 'nocaptcha_plugin_notice_ignore') && $current_screen->base !== 'settings_page_anr-admin-settings' ) {	
			echo '<div class="updated notice" style="position: relative; display: flex;"><img style="width: 100px; height: 100px; margin-top: 9px; margin-right: 5px;" src="' . esc_url( $logo_img_src ) . '"><div><h3 style="position: relative; top: 7px; font-size: 1.7em;">Exciting news!</h3><p style="margin-top: 15px;">'. __('Finally! The Advanced noCaptcha & invisible Captcha plugin is getting an update') .' <a href="https://www.wpwhitesecurity.com/advanced-nocaptcha-recaptcha-joins-wp-white-security/" target="blank">Learn more.</a></p></div>  <a style="position: absolute; right: 15px; top: 15px;" href="?adv-nocaptcha-ignore-notice" target="blank">Dismiss.</a></p></div>';	
		}		

		// General notice in dashboard.
		if ( current_user_can( 'manage_options' ) && $current_screen->base === 'settings_page_anr-admin-settings' ) {	
			echo '<div class="updated notice" style="position: relative; display: flex; margin-top: 15px;"><img style="width: 100px; height: 100px; margin-top: 9px; margin-right: 5px;" src="' . esc_url( $logo_img_src ) . '"><div><h3 style="position: relative; top: 7px; font-size: 1.7em;">Great news!</h3><p style="margin-top: 15px;">'. __('This plugin joins WP White Security and it will soon be getting a well deserved update. ') .' <a href="https://www.wpwhitesecurity.com/advanced-nocaptcha-recaptcha-joins-wp-white-security/" target="blank">Read our announcement for more details.</a></p><p style="margin-top: -7px; margin-bottom: 15px;">Thank you for using this plugin.</p></div></p></div>';	
		}		
	}
		
	function adv_nocaptcha_plugin_notice_ignore() {		
		global $current_user;		
		$user_id = $current_user->ID;		
		if ( isset( $_GET['adv-nocaptcha-ignore-notice'] ) ) {			
			add_user_meta( $user_id, 'nocaptcha_plugin_notice_ignore', 'true', true );			
		}		
	}
	
	function admin_enqueue_scripts() {
		wp_register_script( 'anr-admin', ANR_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), ANR_PLUGIN_VERSION, true );
	}

	function admin_init() {
		register_setting( 'anr_admin_options', 'anr_admin_options', array( $this, 'options_sanitize' ) );
		foreach ( $this->get_sections() as $section_id => $section ) {
			add_settings_section( $section_id, $section['section_title'], ! empty( $section['section_callback'] ) ? $section['section_callback'] : null, 'anr_admin_options' );
		}
		foreach ( $this->get_fields() as $field_id => $field ) {
			add_settings_field( $field['id'], $field['label'], ! empty( $field['callback'] ) ? $field['callback'] : array( $this, 'callback' ), 'anr_admin_options', $field['section_id'], $field );
		}
	}

	function get_sections() {
		$sections = array(
			'google_keys' => array(
				'section_title'    => __( 'Google Keys', 'advanced-nocaptcha-recaptcha' ),
				'section_callback' => function() {
					printf( __( 'Get reCaptcha keys from <a href="%s" target="_blank" rel="noopener noreferrer">Google</a>. Make sure to get keys for your selected captcha type. <a href="%s" target="_blank" rel="noopener noreferrer">How to get google reCAPTCHA keys?</a>.', 'advanced-nocaptcha-recaptcha' ), 'https://www.google.com/recaptcha/admin', 'https://www.shamimsplugins.com/docs/advanced-nocaptcha-recaptcha/getting-started-advanced-nocaptcha-recaptcha/how-to-get-google-recaptcha-keys/' );
				},
			),
			'forms'       => array(
				'section_title' => __( 'Forms', 'advanced-nocaptcha-recaptcha' ),
			),
			'other'       => array(
				'section_title' => __( 'Other Settings', 'advanced-nocaptcha-recaptcha' ),
			),
		);
		return apply_filters( 'anr_settings_sections', $sections );
	}

	function get_fields() {
		$score_values = [];
		for ( $i = 0.0; $i <= 1; $i += 0.1 ) {
			$score_values[ "$i" ] = number_format_i18n( $i, 1 );
		}
		$fields = array(
			'captcha_version'            => array(
				'label'      => __( 'reCAPTCHA type', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => 'v2_checkbox',
				'options'    => array(
					'v2_checkbox'  => __( 'V2 "I\'m not a robot"', 'advanced-nocaptcha-recaptcha' ),
					'v2_invisible' => __( 'V2 Invisible', 'advanced-nocaptcha-recaptcha' ),
					'v3'           => __( 'V3', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => __( 'Select your reCAPTCHA type. Make sure to use site key and secret key for your selected type.', 'advanced-nocaptcha-recaptcha' ),
			),
			'site_key'           => array(
				'label'      => __( 'Site Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
			),
			'secret_key'         => array(
				'label'      => __( 'Secret Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
			),
			'enabled_forms'      => array(
				'label'      => __( 'Enabled Forms', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
				'class'      => 'checkbox',
				'options'    => array(
					'login'          => __( 'Login Form', 'advanced-nocaptcha-recaptcha' ),
					'registration'   => __( 'Registration Form', 'advanced-nocaptcha-recaptcha' ),
					'ms_user_signup' => __( 'Multisite User Signup Form', 'advanced-nocaptcha-recaptcha' ),
					'lost_password'  => __( 'Lost Password Form', 'advanced-nocaptcha-recaptcha' ),
					'reset_password' => __( 'Reset Password Form', 'advanced-nocaptcha-recaptcha' ),
					'comment'        => __( 'Comment Form', 'advanced-nocaptcha-recaptcha' ),
					'bbp_new'         => __( 'bbPress New topic', 'advanced-nocaptcha-recaptcha' ),
					'bbp_reply'       => __( 'bbPress reply to topic', 'advanced-nocaptcha-recaptcha' ),
					'bp_register'       => __( 'BuddyPress register', 'advanced-nocaptcha-recaptcha' ),
					'wc_checkout'    => __( 'WooCommerce Checkout', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => sprintf( __( 'For "Contact Form 7" you need to follow <a target="_blank" rel="noopener noreferrer" href="%1$s">this instruction</a>. For other forms see <a href="%2$s">this instruction</a>', 'advanced-nocaptcha-recaptcha' ), esc_url( 'https://www.shamimsplugins.com/docs/advanced-nocaptcha-recaptcha/getting-started-advanced-nocaptcha-recaptcha/implement-in-contact-form-7/' ), esc_url( anr_settings_page_url( 'instruction' ) ) ),
			),
			'error_message'      => array(
				'label'      => __( 'Error Message', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'std'        => __( 'Please solve Captcha correctly', 'advanced-nocaptcha-recaptcha' ),
			),
			'language'           => array(
				'label'      => __( 'Captcha Language', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular',
				'options'    => array(
					''       => __( 'Auto Detect', 'advanced-nocaptcha-recaptcha' ),
					'ar'     => __( 'Arabic', 'advanced-nocaptcha-recaptcha' ),
					'bg'     => __( 'Bulgarian', 'advanced-nocaptcha-recaptcha' ),
					'ca'     => __( 'Catalan', 'advanced-nocaptcha-recaptcha' ),
					'zh-CN'  => __( 'Chinese (Simplified)', 'advanced-nocaptcha-recaptcha' ),
					'zh-CN'  => __( 'Chinese (Traditional)', 'advanced-nocaptcha-recaptcha' ),
					'hr'     => __( 'Croatian', 'advanced-nocaptcha-recaptcha' ),
					'cs'     => __( 'Czech', 'advanced-nocaptcha-recaptcha' ),
					'da'     => __( 'Danish', 'advanced-nocaptcha-recaptcha' ),
					'nl'     => __( 'Dutch', 'advanced-nocaptcha-recaptcha' ),
					'en-GB'  => __( 'English (UK)', 'advanced-nocaptcha-recaptcha' ),
					'en'     => __( 'English (US)', 'advanced-nocaptcha-recaptcha' ),
					'fil'    => __( 'Filipino', 'advanced-nocaptcha-recaptcha' ),
					'fi'     => __( 'Finnish', 'advanced-nocaptcha-recaptcha' ),
					'fr'     => __( 'French', 'advanced-nocaptcha-recaptcha' ),
					'fr-CA'  => __( 'French (Canadian)', 'advanced-nocaptcha-recaptcha' ),
					'de'     => __( 'German', 'advanced-nocaptcha-recaptcha' ),
					'de-AT'  => __( 'German (Austria)', 'advanced-nocaptcha-recaptcha' ),
					'de-CH'  => __( 'German (Switzerland)', 'advanced-nocaptcha-recaptcha' ),
					'el'     => __( 'Greek', 'advanced-nocaptcha-recaptcha' ),
					'iw'     => __( 'Hebrew', 'advanced-nocaptcha-recaptcha' ),
					'hi'     => __( 'Hindi', 'advanced-nocaptcha-recaptcha' ),
					'hu'     => __( 'Hungarain', 'advanced-nocaptcha-recaptcha' ),
					'id'     => __( 'Indonesian', 'advanced-nocaptcha-recaptcha' ),
					'it'     => __( 'Italian', 'advanced-nocaptcha-recaptcha' ),
					'ja'     => __( 'Japanese', 'advanced-nocaptcha-recaptcha' ),
					'ko'     => __( 'Korean', 'advanced-nocaptcha-recaptcha' ),
					'lv'     => __( 'Latvian', 'advanced-nocaptcha-recaptcha' ),
					'lt'     => __( 'Lithuanian', 'advanced-nocaptcha-recaptcha' ),
					'no'     => __( 'Norwegian', 'advanced-nocaptcha-recaptcha' ),
					'fa'     => __( 'Persian', 'advanced-nocaptcha-recaptcha' ),
					'pl'     => __( 'Polish', 'advanced-nocaptcha-recaptcha' ),
					'pt'     => __( 'Portuguese', 'advanced-nocaptcha-recaptcha' ),
					'pt-BR'  => __( 'Portuguese (Brazil)', 'advanced-nocaptcha-recaptcha' ),
					'pt-PT'  => __( 'Portuguese (Portugal)', 'advanced-nocaptcha-recaptcha' ),
					'ro'     => __( 'Romanian', 'advanced-nocaptcha-recaptcha' ),
					'ru'     => __( 'Russian', 'advanced-nocaptcha-recaptcha' ),
					'sr'     => __( 'Serbian', 'advanced-nocaptcha-recaptcha' ),
					'sk'     => __( 'Slovak', 'advanced-nocaptcha-recaptcha' ),
					'sl'     => __( 'Slovenian', 'advanced-nocaptcha-recaptcha' ),
					'es'     => __( 'Spanish', 'advanced-nocaptcha-recaptcha' ),
					'es-419' => __( 'Spanish (Latin America)', 'advanced-nocaptcha-recaptcha' ),
					'sv'     => __( 'Swedish', 'advanced-nocaptcha-recaptcha' ),
					'th'     => __( 'Thai', 'advanced-nocaptcha-recaptcha' ),
					'tr'     => __( 'Turkish', 'advanced-nocaptcha-recaptcha' ),
					'uk'     => __( 'Ukrainian', 'advanced-nocaptcha-recaptcha' ),
					'vi'     => __( 'Vietnamese', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'theme'              => array(
				'label'      => __( 'Theme', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden anr-show-field-for-v2_checkbox anr-show-field-for-v2_invisible',
				'std'        => 'light',
				'options'    => array(
					'light' => __( 'Light', 'advanced-nocaptcha-recaptcha' ),
					'dark'  => __( 'Dark', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'size'               => array(
				'label'      => __( 'Size', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden anr-show-field-for-v2_checkbox',
				'std'        => 'normal',
				'options'    => array(
					'normal'    => __( 'Normal', 'advanced-nocaptcha-recaptcha' ),
					'compact'   => __( 'Compact', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'badge'              => array(
				'label'      => __( 'Badge', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden anr-show-field-for-v2_invisible',
				'std'        => 'bottomright',
				'options'    => array(
					'bottomright' => __( 'Bottom Right', 'advanced-nocaptcha-recaptcha' ),
					'bottomleft'  => __( 'Bottom Left', 'advanced-nocaptcha-recaptcha' ),
					'inline'      => __( 'Inline', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => __( 'Badge shows for invisible captcha', 'advanced-nocaptcha-recaptcha' ),
			),
			'failed_login_allow' => array(
				'label'             => __( 'Failed login Captcha', 'advanced-nocaptcha-recaptcha' ),
				'section_id'        => 'other',
				'std'               => 0,
				'type'              => 'number',
				'class'             => 'regular-number',
				'sanitize_callback' => 'absint',
				'desc'              => __( 'Show login Captcha after how many failed attempts? 0 = show always', 'advanced-nocaptcha-recaptcha' ),
			),
			'v3_script_load'     => array(
				'label'      => __( 'v3 Script Load', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden anr-show-field-for-v3',
				'std'        => 'all_pages',
				'options'    => array(
					'all_pages'  => __( 'All Pages', 'advanced-nocaptcha-recaptcha' ),
					'form_pages' => __( 'Form Pages', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => __( 'Loading in All Pages help google for analytics', 'advanced-nocaptcha-recaptcha' ),
			),
			'score'              => array(
				'label'      => __( 'Captcha Score', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden anr-show-field-for-v3',
				'std'        => '0.5',
				'options'    => $score_values,
				'desc'       => __( 'Higher means more sensitive', 'advanced-nocaptcha-recaptcha' ),
			),
			'whitelisted_ips'              => array(
				'label'      => __( 'Whitelisted IPs', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'textarea',
				'class'      => 'regular',
				'desc'       => __( 'One per line', 'advanced-nocaptcha-recaptcha' ),
			),
			'recaptcha_domain'      => array(
				'label'      => __( 'Recaptcha Domain', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => anr_recaptcha_domain(),
				'options'    => array(
					'google.com'   => 'google.com',
					'google.net'   => 'google.net',
					'recaptcha.net' => 'recaptcha.net',
				),
				'desc'        => __( 'If any one is blocked in your country select other one.', 'advanced-nocaptcha-recaptcha' ),
			),
			'loggedin_hide'      => array(
				'label'      => __( 'Logged in Hide', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'cb_label'   => __( 'Hide Captcha for logged in users?', 'advanced-nocaptcha-recaptcha' ),
			),
			'remove_css'         => array(
				'label'      => __( 'Remove CSS', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'checkbox',
				'class'      => 'checkbox hidden anr-show-field-for-v2_checkbox',
				'cb_label'   => __( "Remove this plugin's css from login page?", 'advanced-nocaptcha-recaptcha' ),
				'desc'       => __( 'This css increase login page width to adjust with Captcha width.', 'advanced-nocaptcha-recaptcha' ),
			),
			'no_js'              => array(
				'label'      => __( 'No JS Captcha', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'checkbox',
				'class'      => 'checkbox hidden anr-show-field-for-v2_checkbox',
				'cb_label'   => __( 'Show captcha if javascript disabled?', 'advanced-nocaptcha-recaptcha' ),
				'desc'       => __( 'If JavaScript is a requirement for your site, we advise that you do NOT check this.', 'advanced-nocaptcha-recaptcha' ),
			),
		);
		if ( ! class_exists( 'ANR_Pro' ) ) :
			$fields['pro_notice'] = array(
				'section_id' => 'forms',
				'type'       => 'html',
				'std'        => sprintf( '<div class="notice notice-success inline">
					<p>To support development of "Advanced noCaptcha & invisible Captcha" plugin please purchase PRO version. <a class="button button-secondary" href="%1$s">' . __( 'View Details', 'advanced-nocaptcha-recaptcha' ) . '</a></p>
				</div>', function_exists( 'anr_fs' ) ? anr_fs()->get_upgrade_url() : 'https://www.shamimsplugins.com/products/advanced-nocaptcha-and-invisible-captcha-pro/' ),
			);
		endif;

		$fields = apply_filters( 'anr_settings_fields', $fields );

		foreach ( $fields as $field_id => $field ) {
			$fields[ $field_id ] = wp_parse_args(
				$field, array(
					'id'         => $field_id,
					'label'      => '',
					'cb_label'   => '',
					'type'       => 'text',
					'class'      => 'regular-text',
					'section_id' => '',
					'desc'       => '',
					'std'        => '',
				)
			);
		}
		
		return $fields;
	}

	function callback( $field ) {
		$attrib = '';
		if ( ! empty( $field['required'] ) ) {
			$attrib .= ' required = "required"';
		}
		if ( ! empty( $field['readonly'] ) ) {
			$attrib .= ' readonly = "readonly"';
		}
		if ( ! empty( $field['disabled'] ) ) {
			$attrib .= ' disabled = "disabled"';
		}
		if ( ! empty( $field['minlength'] ) ) {
			$attrib .= ' minlength = "' . absint( $field['minlength'] ) . '"';
		}
		if ( ! empty( $field['maxlength'] ) ) {
			$attrib .= ' maxlength = "' . absint( $field['maxlength'] ) . '"';
		}

		$value = anr_get_option( $field['id'], $field['std'] );

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
			case 'number':
			case 'hidden':
			case 'submit':
				printf(
					'<input type="%1$s" id="anr_admin_options_%2$s" class="%3$s" name="anr_admin_options[%4$s]" placeholder="%5$s" value="%6$s"%7$s />',
					esc_attr( $field['type'] ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib
				);
				break;
			case 'textarea':
					printf( '<textarea id="anr_admin_options_%1$s" class="%2$s" name="anr_admin_options[%3$s]" placeholder="%4$s" %5$s >%6$s</textarea>',
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] ),
						esc_attr( $field['id'] ),
						isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
						$attrib,
						esc_textarea( $value )
					);
					break;
			case 'checkbox':
				printf( '<input type="hidden" name="anr_admin_options[%s]" value="" />', esc_attr( $field['id'] ) );
				printf(
					'<label><input type="%1$s" id="anr_admin_options_%2$s" class="%3$s" name="anr_admin_options[%4$s]" value="%5$s"%6$s /> %7$s</label>',
					'checkbox',
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					'1',
					checked( $value, '1', false ),
					esc_attr( $field['cb_label'] )
				);
				break;
			case 'multicheck':
				printf( '<input type="hidden" name="anr_admin_options[%s][]" value="" />', esc_attr( $field['id'] ) );
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<label><input type="%1$s" id="anr_admin_options_%2$s_%5$s" class="%3$s" name="anr_admin_options[%4$s][]" value="%5$s"%6$s /> %7$s</label><br>',
						'checkbox',
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] ),
						esc_attr( $field['id'] ),
						esc_attr( $key ),
						checked( in_array( $key, (array) $value ), true, false ),
						esc_attr( $label )
					);
				}
				break;
			case 'select':
				printf(
					'<select id="anr_admin_options_%1$s" class="%2$s" name="anr_admin_options[%1$s]">',
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] )
				);
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $key ),
						selected( $value, $key, false ),
						esc_attr( $label )
					);
				}
				printf( '</select>' );
				break;
			case 'html':
				echo $field['std'];
				break;

			default:
				printf( __( 'No hook defined for %s', 'advanced-nocaptcha-recaptcha' ), esc_html( $field['type'] ) );
				break;
		}
		if ( ! empty( $field['desc'] ) ) {
			printf( '<p class="description">%s</p>', $field['desc'] );
		}
	}

	function options_sanitize( $value ) {
		if ( ! $value || ! is_array( $value ) ) {
			return $value;
		}
		$fields = $this->get_fields();

		foreach ( $value as $option_slug => $option_value ) {
			if ( isset( $fields[ $option_slug ] ) && ! empty( $fields[ $option_slug ]['sanitize_callback'] ) ) {
				$value[ $option_slug ] = call_user_func( $fields[ $option_slug ]['sanitize_callback'], $option_value );
			} elseif ( isset( $fields[ $option_slug ] ) ) {
				$value[ $option_slug ] = $this->posted_value_sanitize( $option_value, $fields[ $option_slug ] );
			}
		}
		return $value;
	}

	function posted_value_sanitize( $value, $field ) {
		$sanitized = $value;
			switch ( $field['type'] ) {
				case 'text':
				case 'hidden':
				$sanitized = sanitize_text_field( trim( $value ) );
				break;
			case 'url':
				$sanitized = esc_url( $value );
				break;
			case 'number':
				$sanitized = absint( $value );
				break;
			case 'textarea':
			case 'wp_editor':
			case 'teeny':
				$sanitized = wp_kses_post( $value );
				break;
			case 'checkbox':
				$sanitized = absint( $value );
				break;
			case 'multicheck':
				$sanitized = is_array( $value ) ? array_filter( $value ) : array();
				foreach( $sanitized as $key => $p_value ) {
					if ( ! array_key_exists( $p_value, $field['options'] ) ) {
						unset( $sanitized[ $key ] );
					}
				}
				break;
			case 'select':
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					$sanitized = isset($field['std'])? $field['std'] : '';
				}
				break;
			default:
				$sanitized = apply_filters( 'anr_settings_field_sanitize_filter_' . $field['type'], $value, $field );
				break;
		}
		return apply_filters( 'anr_settings_field_sanitize_filter', $sanitized, $field, $value );
	}

	function menu_page() {
		add_options_page( __( 'Advanced noCaptcha & invisible captcha Settings', 'advanced-nocaptcha-recaptcha' ), __( 'Advanced noCaptcha & invisible captcha', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'anr-admin-settings', array( $this, 'admin_settings' ) );
		//add_submenu_page( 'anr-non-exist-menu', 'Advanced noCaptcha reCaptcha - ' . __( 'Instruction', 'advanced-nocaptcha-recaptcha' ), __( 'Instruction', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'anr-instruction', array( $this, 'instruction_page' ) );

	}

	function network_menu_page() {
		add_submenu_page( 'settings.php', __( 'Advanced noCaptcha & invisible captcha Settings', 'advanced-nocaptcha-recaptcha' ), __( 'Advanced noCaptcha & invisible captcha', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'anr-admin-settings', array( $this, 'admin_settings' ) );
		//add_submenu_page( 'anr-non-exist-menu', 'Advanced noCaptcha reCaptcha - ' . __( 'Instruction', 'advanced-nocaptcha-recaptcha' ), __( 'Instruction', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'anr-instruction', array( $this, 'instruction_page' ) );

	}
	
	function settings_save() {
		if ( current_user_can( 'manage_options' ) && isset( $_POST['anr_admin_options'] ) && isset( $_POST['action'] ) && 'update' === $_POST['action'] && isset( $_GET['page'] ) && 'anr-admin-settings' === $_GET['page'] ) {
			check_admin_referer( 'anr_admin_options-options' );

			$value = wp_unslash( $_POST['anr_admin_options'] );
			if ( ! is_array( $value ) ) {
				$value = [];
			}
			anr_update_option( $value );
			
			wp_safe_redirect( add_query_arg( 'updated', true ) );
			exit;
		}
	}

	function get_tabs(){
		return [
			'settings' => __( 'Settings', 'advanced-nocaptcha-recaptcha' ),
			'instruction' => __( 'Instruction', 'advanced-nocaptcha-recaptcha' ),
		];
	}

	function admin_settings() {
		wp_enqueue_script( 'anr-admin' );
		$current_tab = 'settings';
		if( !empty( $_GET['tab'] ) ){
			$current_tab = $_GET['tab'];
		}
		?>
		<div class="wrap fs-section">
			<h2 class="nav-tab-wrapper">
			<?php
			foreach( $this->get_tabs() as $k => $v ){
				$active = '';
				if( $k === $current_tab ){
					$active = ' nav-tab-active';
				}
				printf( '<a href="%s" class="nav-tab fs-tab%s">%s</a>', anr_settings_page_url( $k ), $active, $v );
			}
			?>
			</h2>
			<!-- Plugin settings go here -->
			<div id="poststuff">
				<h2><?php _e( 'Advanced noCaptcha & invisible captcha Settings', 'advanced-nocaptcha-recaptcha' ); ?></h2>
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div id="tab_container">
							<?php
							if( 'settings' == $current_tab ){
								$this->settings_form();
							} elseif( 'instruction' == $current_tab ){
								$this->instruction_page();
							}
							?>
						</div><!-- #tab_container-->
					</div><!-- #post-body-content-->
					<div id="postbox-container-1" class="postbox-container">
						<?php echo $this->anr_admin_sidebar(); ?>
					</div><!-- #postbox-container-1 -->
				</div><!-- #post-body -->
				<br class="clear" />
			</div><!-- #poststuff -->
		</div>
		<?php
	}

	function settings_form(){
		?>
			<?php settings_errors( 'anr_admin_options' ); ?>
			<form method="post" action="">
				<?php
				settings_fields( 'anr_admin_options' );
				do_settings_sections( 'anr_admin_options' );
				do_action( 'anr_admin_setting_form' );
				submit_button();
				?>
			</form>
		<?php
	}
	function instruction_page(){
		?>
			<div><?php printf( __( 'Get your site key and secret key from <a href="%s" target="_blank" rel="noopener noreferrer">GOOGLE</a> if you do not have already. <a href="%s" target="_blank" rel="noopener noreferrer">How to get google reCAPTCHA keys?</a>.', 'advanced-nocaptcha-recaptcha' ), esc_url( 'https://www.google.com/recaptcha/admin' ), 'https://www.shamimsplugins.com/docs/advanced-nocaptcha-recaptcha/getting-started-advanced-nocaptcha-recaptcha/how-to-get-google-recaptcha-keys/' ); ?></div>
			<div><?php printf( __( 'Goto %s page of this plugin and set up as you need. and ENJOY...', 'advanced-nocaptcha-recaptcha' ), '<a href="' . esc_url( anr_settings_page_url() ) . '">' . esc_html__( 'Settings', 'advanced-nocaptcha-recaptcha' ) . '</a>' ); ?></div>

			<h3><?php _e( 'Implement noCaptcha in Contact Form 7', 'advanced-nocaptcha-recaptcha' ); ?></h3>
			<div><?php printf( __( 'To show noCaptcha use %s', 'advanced-nocaptcha-recaptcha' ), '<code>[anr_nocaptcha g-recaptcha-response]</code>' ); ?></div>
			<div><?php printf( __( 'Full details can be found in <a target="_blank" rel="noopener noreferrer" href="%1$s">this instruction</a>', 'advanced-nocaptcha-recaptcha' ), esc_url( 'https://www.shamimsplugins.com/docs/advanced-nocaptcha-recaptcha/getting-started-advanced-nocaptcha-recaptcha/implement-in-contact-form-7/' ) ) ?></div>

			<h3><?php _e( 'Implement noCaptcha in WooCommerce', 'advanced-nocaptcha-recaptcha' ); ?></h3>
			<div><?php _e( 'If Login Form, Registration Form, Lost Password Form, Reset Password Form is selected in SETTINGS page of this plugin they will show and verify Captcha in WooCommerce respective forms also.', 'advanced-nocaptcha-recaptcha' ); ?></div>
			
			<h3><?php _e( 'If you want to implement noCaptcha in any other custom form', 'advanced-nocaptcha-recaptcha' ); ?></h3>
			<div><?php printf( __( 'To show noCaptcha in a form use %1$s OR %2$s', 'advanced-nocaptcha-recaptcha' ), "<code>do_action( 'anr_captcha_form_field' )</code>", '<code>[anr-captcha]</code>' ); ?></div>
			<div><?php printf( __( 'To verify use %s. It will return true on success otherwise false.', 'advanced-nocaptcha-recaptcha' ), '<code>anr_verify_captcha()</code>' ); ?></div>
			<div><?php printf( __( 'For paid support pleasse visit <a href="%s" target="_blank" rel="noopener noreferrer">Advanced noCaptcha reCaptcha</a>', 'advanced-nocaptcha-recaptcha' ), esc_url( 'https://www.shamimsplugins.com/hire/' ) ); ?></div>
						
		<?php
	}

	function anr_admin_sidebar() {
			$return = '';
			if ( ! class_exists( 'ANR_Pro' ) ) :
			$return .= '<style>ul.anr-pro-features-ul li.dashicons-yes-alt:before {color:green;}</style>';
			$return .= sprintf( '<div class="postbox">
				<h3 class="hndle" style="text-align: center;">
					<span>' . __( 'PRO Features', 'advanced-nocaptcha-recaptcha' ) . '</span>
				</h3>
				<div class="inside">
					<div>
						<ul class="anr-pro-features-ul">
							<li class="dashicons-before dashicons-yes-alt"> ' . __( 'WooCommerce forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . __( 'Contact Form 7 forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . __( 'bbPress New topic form', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . __( 'bbPress reply to topic form', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . __( 'BuddyPress register form', 'advanced-nocaptcha-recaptcha' ) . '</li>
						</ul>
						<p style="text-align: center; margin: auto"><a class="button button-secondary" href="%1$s">' . __( 'View Details', 'advanced-nocaptcha-recaptcha' ) . '</a></p>
					</div>
				</div>
			</div>', function_exists( 'anr_fs' ) ? anr_fs()->get_upgrade_url() : 'https://www.shamimsplugins.com/products/advanced-nocaptcha-and-invisible-captcha-pro/' );
		endif;
		return $return;
	}

	function add_settings_link( $links ) {
		// add settings link in plugins page
		$settings_link = '<a href="' . anr_settings_page_url() . '">' . __( 'Settings', 'advanced-nocaptcha-recaptcha' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}


} //END CLASS

add_action( 'wp_loaded', array( ANR_Settings::init(), 'actions_filters' ) );
