<?php

class C4WP_Settings {

	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_init', [ $this, 'settings_save' ], 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( C4WP_PLUGIN_FILE ), [ $this, 'add_settings_link' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );	
		
		$use_network_hooks = is_multisite();

		if ( $use_network_hooks ) {
			add_action( 'network_admin_menu', [ $this, 'network_menu_page' ] );
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( C4WP_PLUGIN_FILE ), [ $this, 'add_settings_link' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'menu_page' ] );			
		}

	}

	function admin_enqueue_scripts() {
		wp_register_script( 'c4wp-admin', C4WP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), C4WP_PLUGIN_VERSION, false );
	}

	function admin_init() {
		register_setting( 'c4wp_admin_options', 'c4wp_admin_options', array( $this, 'options_sanitize' ) );

		$current_tab = 'c4wp-admin-captcha';
		if ( ! empty( $_GET['page'] ) ) {
			$current_tab = $_GET['page'];
		}

		foreach ( $this->get_sections( $current_tab ) as $section_id => $section ) {
			add_settings_section( $section_id, $section['section_title'], ! empty( $section['section_callback'] ) ? $section['section_callback'] : null, 'c4wp_admin_options' );
		}
		foreach ( $this->get_fields() as $field_id => $field ) {
			add_settings_field( $field['id'], $field['label'], ! empty( $field['callback'] ) ? $field['callback'] : array( $this, 'callback' ), 'c4wp_admin_options', $field['section_id'], $field );
		}
	}

	function get_sections( $section_we_want = 'c4wp-admin-captcha' ) {
		$captcha_sections = array(
			'google_keys' => array(
				'section_title'    => '',
				'section_callback' => function() {
					$settings_url = function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ? network_admin_url( 'admin.php?page=c4wp-admin-settings' ) : admin_url( 'admin.php?page=c4wp-admin-settings' );
					echo '<span style="margin-top: 10px; display: block;">';
					printf(
						/* translators: link to the settings page with text "Settings page" */
						esc_html__( 'In this page you can configure the type of reCAPTCHA that you want to use on your website. Once you configure your CAPTCHA, head to the %s to configure where the CAPTCHA should be used, whitelist IP addresses and configure other settings.', 'advanced-nocaptcha-recaptcha' ),
						'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings page', 'advanced-nocaptcha-recaptcha' ) . '</a>'
					);
					echo '</span>';
				},
			),
		);

		$settings_sections = array(
			'forms' => array(
				'section_title'    => '',
				'section_callback' => function() {
					echo '<span style="margin-top: 10px; display: block;">';
					esc_html_e( 'In this page you can configure where on your website you want to add the CAPTCHA check. You can also configure several other settings, such as whitelisting IP addresses, excluding logged in users from CAPTCHA checks and more.', 'advanced-nocaptcha-recaptcha' );
					echo '</span>';
				},
			),
			'other' => array(
				'section_title' => '',
			),
		);

		$sections = ( $section_we_want == 'c4wp-admin-captcha' ) ? $captcha_sections : $settings_sections;
		return apply_filters( 'c4wp_settings_sections', $sections );
	}


	function get_fields() {
		$score_values = array();
		for ( $i = 0.0; $i <= 1; $i += 0.1 ) {
			$score_values[ "$i" ] = number_format_i18n( $i, 1 );
		}
		$score_description = sprintf(
			/* translators: expression "very restrictive" in bold */
			esc_html__( 'Any value above 0.5 is %s.', 'advanced-nocaptcha-recaptcha' ),
			'<strong>' . __( 'very restrictive', 'advanced-nocaptcha-recaptcha' ) . '</strong>'
		);
		$score_description .= ' ' . esc_html__( 'This means that you might end up locked out from your website. Therefore test this on a staging website website beforehand.', 'advanced-nocaptcha-recaptcha' );

		$role_select_html = '<select multiple="multiple" id="hide_from_roles" name="c4wp_admin_options[hide_from_roles]" display:none;width:100>%">';

		$forms_preamble_desc = esc_html__( 'You can add a CAPTCHA check to the below list of pages on WordPress.', 'advanced-nocaptcha-recaptcha' );
		$lang_selector_desc  = esc_html__( 'Use the setting below to select the language of the text used in the CAPTCHA text.', 'advanced-nocaptcha-recaptcha' );

		if ( ! c4wp_is_premium_version() ) {
			$forms_preamble_desc .= sprintf(
				__( 'To add CAPTCHA checks to WooCommerce, Contact Form 7, BuddyPress and other forms created by third party plugins you need to %s', 'advanced-nocaptcha-recaptcha' ),
				'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( '#' ) . '">' . esc_html__( 'upgrade to Premium', 'advanced-nocaptcha-recaptcha' ) . '</a>'
			);
			$lang_selector_desc  .= esc_html__( ' In the Premium edition you can configure the plugin to automatically detect the language settings of the visitor\'s and use that language.', 'advanced-nocaptcha-recaptcha' );
		}

		$comment_form_label = esc_html__( 'Comments form', 'advanced-nocaptcha-recaptcha' );

		if ( defined( 'JETPACK__VERSION' ) ) {
			$comment_form_label .= ' ' . esc_html__( '(Incompatible with Jetpack comments)', 'advanced-nocaptcha-recaptcha' ) . '';
		}

		$fields = array(
			'captcha_version_title'  => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Select the type of reCAPTCHA you want to use', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content',
			),
			'captcha_version'        => array(
				'label'      => esc_html__( 'reCAPTCHA version', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'radio',
				'class'      => 'regular',
				'std'        => 'v2_checkbox',
				'options'    => array(
					'v2_checkbox'  => esc_html__( 'Version 2 (Users have to check the "I’m not a robot” checkbox)', 'advanced-nocaptcha-recaptcha' ),
					'v2_invisible' => esc_html__( 'Version 2 (No user interaction needed, however, if traffic is suspicious, users are asked to solve a CAPTCHA)', 'advanced-nocaptcha-recaptcha' ),
					'v3'           => esc_html__( 'Version 3 (verify request with a score without user interaction)', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'site_key_title'         => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Specify the Site & Secret key', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content',
			),
			'site_key_subtitle'      => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<p class="description c4wp-desc" style="position: absolute;">%1$s</p>',
					sprintf(
						esc_html__( 'To communicate with Google and utilize the reCAPTCHA service you need to get a Site Key and Secret Key. You can obtain these keys for free by registering for your Google reCAPTCHA. Refer to %s if you need help with the process.', 'advanced-nocaptcha-recaptcha' ),
						'<a href="' . esc_url( 'https://www.wpwhitesecurity.com/support/kb/get-google-recaptcha-keys/' ) . '" target="_blank">' . esc_html__( 'how to get the Google reCAPTCHA keys', 'advanced-nocaptcha-recaptcha' ) . '</a>'
					)
				),
				'class'      => 'wrap-around-content',
			),
			'site_key'               => array(
				'label'      => esc_html__( 'Site Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => true,
			),
			'secret_key'             => array(
				'label'      => esc_html__( 'Secret Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => true,
			),
			'score_title'            => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Configure the below optional settings to fine-tune the reCAPTCHA to your requirements.', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content',
			),
			'score'                  => array(
				'label'      => esc_html__( 'Captcha Score', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
				'std'        => '0.5',
				'options'    => $score_values,
				'desc'       => esc_html__( 'Use this setting to specify sensitivity of the CAPTCHA check. The closer to 1 the more sensitive the CAPTCHA check will be, which also means more traffic will be marked as spam. This option is only available for reCAPTCHA v3.', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),
			'v3_script_load'         => array(
				'label'      => esc_html__( 'Load CAPTCHA v3 scripts on:', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
				'std'        => 'all_pages',
				'options'    => array(
					'all_pages'  => esc_html__( 'All Pages', 'advanced-nocaptcha-recaptcha' ),
					'form_pages' => esc_html__( 'Form Pages', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => esc_html__( 'By default CAPTCHA only loads on the pages where it is required, mainly forms. However, for V3 you can configure it to load on all pages so it has a better context of the traffic and works more efficiently. The CAPTCHA test will never interrupt users on non-form pages.', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),
			'language_handling'      => array(
				'label'      => esc_html__( 'CAPTCHA language', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'radio',
				'class'      => 'regular remove-space-below remove-radio-br',
				'std'        => 'manually_choose',
				'options'    => array(
					'manually_choose' => esc_html__( 'Select a language', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => $lang_selector_desc . '</br>',
			),
			'language'               => array(
				'label'      => '',
				'section_id' => 'google_keys',
				'type'       => 'select',
				'std'        => 'en',
				'class'      => 'regular lang_select',
				'options'    => array(
					'ar'     => esc_html__( 'Arabic', 'advanced-nocaptcha-recaptcha' ),
					'bg'     => esc_html__( 'Bulgarian', 'advanced-nocaptcha-recaptcha' ),
					'ca'     => esc_html__( 'Catalan', 'advanced-nocaptcha-recaptcha' ),
					'zh-CN'  => esc_html__( 'Chinese (Simplified)', 'advanced-nocaptcha-recaptcha' ),
					'zh-CN'  => esc_html__( 'Chinese (Traditional)', 'advanced-nocaptcha-recaptcha' ),
					'hr'     => esc_html__( 'Croatian', 'advanced-nocaptcha-recaptcha' ),
					'cs'     => esc_html__( 'Czech', 'advanced-nocaptcha-recaptcha' ),
					'da'     => esc_html__( 'Danish', 'advanced-nocaptcha-recaptcha' ),
					'nl'     => esc_html__( 'Dutch', 'advanced-nocaptcha-recaptcha' ),
					'en-GB'  => esc_html__( 'English (UK)', 'advanced-nocaptcha-recaptcha' ),
					'en'     => esc_html__( 'English (US)', 'advanced-nocaptcha-recaptcha' ),
					'fil'    => esc_html__( 'Filipino', 'advanced-nocaptcha-recaptcha' ),
					'fi'     => esc_html__( 'Finnish', 'advanced-nocaptcha-recaptcha' ),
					'fr'     => esc_html__( 'French', 'advanced-nocaptcha-recaptcha' ),
					'fr-CA'  => esc_html__( 'French (Canadian)', 'advanced-nocaptcha-recaptcha' ),
					'de'     => esc_html__( 'German', 'advanced-nocaptcha-recaptcha' ),
					'de-AT'  => esc_html__( 'German (Austria)', 'advanced-nocaptcha-recaptcha' ),
					'de-CH'  => esc_html__( 'German (Switzerland)', 'advanced-nocaptcha-recaptcha' ),
					'el'     => esc_html__( 'Greek', 'advanced-nocaptcha-recaptcha' ),
					'iw'     => esc_html__( 'Hebrew', 'advanced-nocaptcha-recaptcha' ),
					'hi'     => esc_html__( 'Hindi', 'advanced-nocaptcha-recaptcha' ),
					'hu'     => esc_html__( 'Hungarain', 'advanced-nocaptcha-recaptcha' ),
					'id'     => esc_html__( 'Indonesian', 'advanced-nocaptcha-recaptcha' ),
					'it'     => esc_html__( 'Italian', 'advanced-nocaptcha-recaptcha' ),
					'ja'     => esc_html__( 'Japanese', 'advanced-nocaptcha-recaptcha' ),
					'ko'     => esc_html__( 'Korean', 'advanced-nocaptcha-recaptcha' ),
					'lv'     => esc_html__( 'Latvian', 'advanced-nocaptcha-recaptcha' ),
					'lt'     => esc_html__( 'Lithuanian', 'advanced-nocaptcha-recaptcha' ),
					'no'     => esc_html__( 'Norwegian', 'advanced-nocaptcha-recaptcha' ),
					'fa'     => esc_html__( 'Persian', 'advanced-nocaptcha-recaptcha' ),
					'pl'     => esc_html__( 'Polish', 'advanced-nocaptcha-recaptcha' ),
					'pt'     => esc_html__( 'Portuguese', 'advanced-nocaptcha-recaptcha' ),
					'pt-BR'  => esc_html__( 'Portuguese (Brazil)', 'advanced-nocaptcha-recaptcha' ),
					'pt-PT'  => esc_html__( 'Portuguese (Portugal)', 'advanced-nocaptcha-recaptcha' ),
					'ro'     => esc_html__( 'Romanian', 'advanced-nocaptcha-recaptcha' ),
					'ru'     => esc_html__( 'Russian', 'advanced-nocaptcha-recaptcha' ),
					'sr'     => esc_html__( 'Serbian', 'advanced-nocaptcha-recaptcha' ),
					'sk'     => esc_html__( 'Slovak', 'advanced-nocaptcha-recaptcha' ),
					'sl'     => esc_html__( 'Slovenian', 'advanced-nocaptcha-recaptcha' ),
					'es'     => esc_html__( 'Spanish', 'advanced-nocaptcha-recaptcha' ),
					'es-419' => esc_html__( 'Spanish (Latin America)', 'advanced-nocaptcha-recaptcha' ),
					'sv'     => esc_html__( 'Swedish', 'advanced-nocaptcha-recaptcha' ),
					'th'     => esc_html__( 'Thai', 'advanced-nocaptcha-recaptcha' ),
					'tr'     => esc_html__( 'Turkish', 'advanced-nocaptcha-recaptcha' ),
					'uk'     => esc_html__( 'Ukrainian', 'advanced-nocaptcha-recaptcha' ),
					'vi'     => esc_html__( 'Vietnamese', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'error_message'          => array(
				'label'      => esc_html__( 'Error message', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'std'        => esc_html__( 'Please solve the CAPTCHA to proceed', 'advanced-nocaptcha-recaptcha' ),
				'desc'       => esc_html__( 'Specify the message you want to show users who do not complete the CAPTCHA.', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),
			'theme'                  => array(
				'label'      => esc_html__( 'Theme', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_checkbox c4wp-show-field-for-v2_invisible',
				'std'        => 'light',
				'options'    => array(
					'light' => esc_html__( 'Light', 'advanced-nocaptcha-recaptcha' ),
					'dark'  => esc_html__( 'Dark', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'size'                   => array(
				'label'      => esc_html__( 'Size', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_checkbox',
				'std'        => 'normal',
				'options'    => array(
					'normal'  => esc_html__( 'Normal', 'advanced-nocaptcha-recaptcha' ),
					'compact' => esc_html__( 'Compact', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'badge'                  => array(
				'label'      => esc_html__( 'Badge', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_invisible',
				'std'        => 'bottomright',
				'options'    => array(
					'bottomright' => esc_html__( 'Bottom Right', 'advanced-nocaptcha-recaptcha' ),
					'bottomleft'  => esc_html__( 'Bottom Left', 'advanced-nocaptcha-recaptcha' ),
					'inline'      => esc_html__( 'Inline', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => esc_html__( 'Badge shows for invisible captcha', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),
			'recaptcha_domain'       => array(
				'label'      => esc_html__( 'reCAPTCHA domain', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => c4wp_recaptcha_domain(),
				'options'    => array(
					'google.com'    => 'google.com',
					'google.net'    => 'google.net',
					'recaptcha.net' => 'recaptcha.net',
				),
				'desc'       => esc_html__( 'Use this setting to change the domain if Google is not accessible or blocked.', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),
			'remove_css'             => array(
				'label'      => esc_html__( 'Remove CSS', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'checkbox',
				'class'      => 'checkbox toggleable disabled c4wp-show-field-for-v2_checkbox',
				'cb_label'   => esc_html__( "Remove this plugin's css from login page?", 'advanced-nocaptcha-recaptcha' ),
				'desc'       => __( 'This css increase login page width to adjust with Captcha width.', 'advanced-nocaptcha-recaptcha' ) . '</br>',
			),

			// Settings.
			'enabled_forms_title'    => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Select where on your website you want to add the CAPTCHA check', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'enabled_forms_subtitle' => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'label'      => sprintf(
					'<p class="description c4wp-desc" style="position: absolute;">%1$s</p>',
					$forms_preamble_desc
				),
				'class'      => 'wrap-around-content',
			),
			'enabled_forms'          => array(
				'label'      => esc_html__( 'WordPress pages', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
				'class'      => 'checkbox',
				'options'    => array(
					'login'          => esc_html__( 'Login form', 'advanced-nocaptcha-recaptcha' ),
					'registration'   => esc_html__( 'Registration form', 'advanced-nocaptcha-recaptcha' ),
					'reset_password' => esc_html__( 'Reset password form', 'advanced-nocaptcha-recaptcha' ),
					'lost_password'  => esc_html__( 'Lost password form', 'advanced-nocaptcha-recaptcha' ),
					'comment'        => $comment_form_label,
				),
			),
		);
		if ( ! c4wp_is_premium_version() ) :

			$features_url                  = function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ? network_admin_url( 'admin.php?page=c4wp-admin-upgrade' ) : admin_url( 'admin.php?page=c4wp-admin-upgrade' );
			$premium_area['premium_title'] = array(
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => 'premium-title-wrapper h-140',
				'label'      => sprintf(
					'<span class="premium-title"><strong>Upgrade to Premium to:</strong><p>Add spam protection to block spam bots and allow real humans to easily interact with your WordPress website by adding CAPTCHA to any form on your website, including out of the box support for forms on third party plugins such as:</p><p><ul style="list-style: disc; padding-left: 17px; font-weight: 400;"><li>%5$s</li><li>%6$s</li><li>%7$s</li><li>%8$s</li></ul></p><a href="%3$s" class="premium-link" target="_blank">%1$s</a> <a href="%4$s" class="premium-link-not-btn">%2$s</a></span>',
					esc_html__( 'Upgrade to Premium', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'Find out more', 'advanced-nocaptcha-recaptcha' ),
					esc_url( 'https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/pricing/?utm_source=plugin&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+premium+button' ),
					esc_url( $features_url ),
					esc_html__( 'Checkout and login pages on WooCommerce stores', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'Contact Form 7, MailChimp 4 WordPress forms', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'BuddyPress and bbPress', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'And others', 'advanced-nocaptcha-recaptcha' )
				),
			);

			$fields = self::push_at_to_associative_array( $fields, 'failed_login_cron_schedule', $premium_area );
		endif;

		$fields = apply_filters( 'c4wp_settings_fields', $fields );

		foreach ( $fields as $field_id => $field ) {
			$fields[ $field_id ] = wp_parse_args(
				$field,
				array(
					'id'             => $field_id,
					'label'          => '',
					'cb_label'       => '',
					'cb_label_after' => '',
					'type'           => 'text',
					'class'          => 'regular-text',
					'section_id'     => '',
					'desc'           => '',
					'std'            => '',
					'min_val'        => '',
					'max_val'        => '',
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

		$value = c4wp_get_option( $field['id'], $field['std'] );

		if ( ! empty( $field['desc'] ) ) {
			printf( '<p class="description mb-10">%s</p>', $field['desc'] );
		}

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
			case 'submit':
				printf(
					'<input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" value="%6$s"%7$s />',
					esc_attr( $field['type'] ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib
				);
				break;
			case 'number':
				printf(
					'<input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" min="%8$s" max="%9$s" value="%6$s"%7$s />',
					esc_attr( $field['type'] ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib,
					esc_attr( $field['min_val'] ),
					esc_attr( $field['max_val'] )
				);
				break;
			case 'number-inline':
				printf(
					'%8$s <input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" min="%8$s" max="%9$s" value="%6$s" %7$s /> %9$s',
					esc_attr( 'number' ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib,
					esc_attr( $field['cb_label'] ),
					esc_attr( $field['cb_label_after'] ),
					esc_attr( $field['min_val'] ),
					esc_attr( $field['max_val'] )
				);
				break;
			case 'textarea':
					printf(
						'<textarea id="c4wp_admin_options_%1$s" class="%2$s" name="c4wp_admin_options[%3$s]" placeholder="%4$s" %5$s >%6$s</textarea>',
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] ),
						esc_attr( $field['id'] ),
						isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
						$attrib,
						esc_textarea( $value )
					);
				break;
			case 'checkbox':
				printf( '<input type="hidden" name="c4wp_admin_options[%s]" value="" />', esc_attr( $field['id'] ) );
				printf(
					'<label><input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" value="%5$s"%6$s /> %7$s</label>',
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
				printf( '<input type="hidden" name="c4wp_admin_options[%s][]" value="" />', esc_attr( $field['id'] ) );
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<label><input type="%1$s" id="c4wp_admin_options_%2$s_%5$s" class="%3$s" name="c4wp_admin_options[%4$s][]" value="%5$s"%6$s /> %7$s</label><br>',
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
					'<select id="c4wp_admin_options_%1$s" class="%2$s" name="c4wp_admin_options[%1$s]">',
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
			case 'radio':
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<input type="radio" id="%1$s" name="c4wp_admin_options[%4$s]" value="%1$s" class="%5$s" %2$s><label for="%1$s">%3$s</label><br><br>',
						esc_attr( $key ),
						checked( $value, $key, false ),
						esc_attr( $label ),
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] )
					);
				}
				break;

			default:
				printf( esc_html__( 'No hook defined for %s', 'advanced-nocaptcha-recaptcha' ), esc_html( $field['type'] ) );
				break;
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
			case 'number-inline':
				if ( isset( $field['id'] ) && 'failed_login_cron_schedule' == $field['id'] ) {
					if ( absint( $value ) > 10 ) {
						$value = 10;
					} elseif ( absint( $value ) < 1 ) {
						$value = 1;
					}
				}
				if ( isset( $field['id'] ) && 'failed_login_allow' == $field['id'] ) {
					if ( absint( $value ) > 20 ) {
						$value = 10;
					} elseif ( absint( $value ) < 1 ) {
						$value = 1;
					}
				}
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
				foreach ( $sanitized as $key => $p_value ) {
					if ( ! array_key_exists( $p_value, $field['options'] ) ) {
						unset( $sanitized[ $key ] );
					}
				}
				break;
			case 'select':
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					$sanitized = isset( $field['std'] ) ? $field['std'] : '';
				}
				break;
			default:
				$sanitized = apply_filters( 'c4wp_settings_field_sanitize_filter_' . $field['type'], $value, $field );
				break;
		}

		return apply_filters( 'c4wp_settings_field_sanitize_filter', $sanitized, $field, $value );
	}

	function menu_page() {
		$icon_url = C4WP_PLUGIN_URL . 'assets/img/20x20-icon.png';
		add_menu_page( esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA 4WP', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-captcha', array( $this, 'admin_settings' ), $icon_url, 99 );
		$hook_captcha_submenu  = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-captcha', array( $this, 'admin_settings' ), 0 );
		$hook_settings_submenu = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'CAPTCHA 4WP Settings', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Settings & Placements', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-settings', array( $this, 'admin_settings' ), 1 );
		$hook_help_submenu     = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-help', array( $this, 'admin_settings' ), 5 );

		if ( ! function_exists( 'c4wp_fs' ) || function_exists( 'c4wp_fs' ) && c4wp_fs()->is_not_paying() ) {
			$hook_upgrade_submenu = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-upgrade', array( $this, 'admin_settings' ), 2 );
			add_action( "load-$hook_upgrade_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		}

		add_action( "load-$hook_captcha_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		add_action( "load-$hook_help_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		add_action( "load-$hook_settings_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
	}

	function network_menu_page() {
		add_menu_page( esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA 4WP', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-captcha', array( $this, 'admin_settings' ), '', 99 );
		$hook_captcha_submenu  = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-captcha', array( $this, 'admin_settings' ), 0 );
		$hook_settings_submenu = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'CAPTCHA 4WP Settings', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Settings & Placements', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-settings', array( $this, 'admin_settings' ), 1 );
		$hook_help_submenu     = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-help', array( $this, 'admin_settings' ), 5 );

		if ( ! function_exists( 'c4wp_fs' ) || function_exists( 'c4wp_fs' ) && c4wp_fs()->is_not_paying() ) {
			$hook_upgrade_submenu = add_submenu_page( 'c4wp-admin-captcha', esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-upgrade', array( $this, 'admin_settings' ), 2 );
			add_action( "load-$hook_upgrade_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		}

		add_action( "load-$hook_captcha_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		add_action( "load-$hook_help_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
		add_action( "load-$hook_settings_submenu", array( $this, 'c4wp_admin_page_enqueue_scripts' ) );
	}

	function c4wp_admin_page_enqueue_scripts() {
		wp_enqueue_style( 'c4wp-admin', C4WP_PLUGIN_URL . 'assets/css/admin.css' );

	}

	function settings_save() {
		if ( current_user_can( 'manage_options' ) && isset( $_POST['c4wp_admin_options'] ) && isset( $_POST['action'] ) && 'update' === $_POST['action'] && isset( $_GET['page'] ) && 'c4wp-admin-settings' === $_GET['page'] ||
		current_user_can( 'manage_options' ) && isset( $_POST['c4wp_admin_options'] ) && isset( $_POST['action'] ) && 'update' === $_POST['action'] && isset( $_GET['page'] ) && 'c4wp-admin-captcha' === $_GET['page'] ) {
			check_admin_referer( 'c4wp_admin_options-options' );

			$value = wp_unslash( $_POST['c4wp_admin_options'] );
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			$value = $this->validate_and_set_notices( $value );

			c4wp_update_option( $value );

			wp_safe_redirect( add_query_arg( 'updated', true ) );
			exit;
		}
	}

	function admin_settings() {
		wp_enqueue_script( 'c4wp-admin' );
		wp_localize_script(
			'c4wp-admin',
			'anrScripts',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'ipWarning' => esc_html__( 'Please supply a valid IP', 'advanced-nocaptcha-recaptcha' ),
			)
		);

		$current_tab = 'c4wp-admin-captcha';
		if ( ! empty( $_GET['page'] ) ) {
			$current_tab = $_GET['page'];
		}
		?>
		<div class="wrap fs-section">
			<!-- Plugin settings go here -->
			<div id="c4wp-help">
				<h1>
				<?php
				if ( 'c4wp-admin-captcha' == $current_tab ) {
					_e( 'Captcha Configuration', 'advanced-nocaptcha-recaptcha' );
				} elseif ( 'c4wp-admin-settings' == $current_tab ) {
					_e( 'CAPTCHA Placements', 'advanced-nocaptcha-recaptcha' );
				}
				?>
				</h1>
				<div id="post-body" class="metabox-holder columns-2 c4wp-settings">
					<div id="post-body-content">
						<div id="tab_container">
							<?php
							if ( 'c4wp-admin-captcha' == $current_tab || 'c4wp-admin-settings' == $current_tab ) {
								$this->settings_form();
							} elseif ( 'c4wp-admin-help' == $current_tab ) {
								$this->display_help_page();
							} elseif ( 'c4wp-admin-upgrade' == $current_tab ) {
								$this->display_upgrade_page();
							}
							?>
						</div><!-- #tab_container-->
					</div><!-- #post-body-content-->
					<div id="postbox-container-1" class="postbox-container">
						<?php echo $this->c4wp_admin_sidebar(); ?>
					</div><!-- #postbox-container-1 -->
				</div><!-- #post-body -->
				<br class="clear" />
			</div><!-- #poststuff -->
		</div>
		<style>
		.disabled, .disabled .disabled {
			opacity: 0.6;
			pointer-events: none;
		}
		</style>
		<?php
	}

	function settings_form() {

		?>
			<?php $this->c4wp_settings_notice(); ?>
			<form method="post" action="">
				
				<?php
				settings_fields( 'c4wp_admin_options' );
				do_settings_sections( 'c4wp_admin_options' );
				do_action( 'c4wp_admin_setting_form' );
				submit_button();
				?>
			</form>
		<?php
	}

	/**
	 * Creates and displays notices upon successful saving of settings or errors if needed.
	 */
	function c4wp_settings_notice() {
		$errors = get_transient( 'c4wp_admin_options_errors' );
		$notice = '';
		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $error ) {
			if ( 'empty_site_key' == $error ) {
				$notice .= '<div class="notice notice-error"><p>' . esc_html__( 'The site key that you have entered is invalid. Please try again.', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
			if ( 'empty_secret_key' == $error ) {
				$notice .= '<div class="notice notice-error"><p>' . esc_html__( 'The secret key that you have entered is invalid. Please try again.', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
			if ( 'success' == $error ) {
				$context = esc_html__( 'Captcha settings', 'advanced-nocaptcha-recaptcha' );
				if ( isset( $_REQUEST['page'] ) && 'c4wp-admin-captcha' == $_REQUEST['page'] ) {
					$context = esc_html__( 'Captcha configuration', 'advanced-nocaptcha-recaptcha' );
				}
				$notice .= '<div class="notice notice-success"><p>' . $context . esc_html__( ' updated', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
		}

		delete_transient( 'c4wp_admin_options_errors' );

		echo $notice;
	}

	function c4wp_admin_sidebar() {
			$return = '';
		if ( ! c4wp_is_premium_version() ) :
			$icon_url = C4WP_PLUGIN_URL . 'assets/img/c4wp-logo-full.png';
			$return  .= sprintf(
				'<div class="postbox">
					<h3 class="hndle" style="text-align: center;">
						<img src="' . esc_url( $icon_url ) . '" style="max-width: 200px; display: inline-block; margin: 10px 0 15px;">
						<span>' . esc_html__( 'Upgrade to Premium for:', 'advanced-nocaptcha-recaptcha' ) . '</span>
					</h3>
					<div class="inside">
						<div>
							<ul class="c4wp-pro-features-ul">
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Use the language that your website viewers understand', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Spam protection for your WooCommerce stores', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Specify where to put the CAPTCHA test on WooCommerce checkout page', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'One-click Contact Form 7 forms spam protection', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'One-click spam protection for Mailchimp for WordPress forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'CAPTCHA tests & spam protection for BuddyPress, bbPress & other third party plugins', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Add CAPTCHA to any type of form, even PHP forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Boost login security, add CAPTCHA tests only failed logins', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA for logged in users', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA for specific IP addresses', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA from specific URLs', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'No Ads!', 'advanced-nocaptcha-recaptcha' ) . '</li>
							</ul>
							<p style="text-align: center; margin: auto"><a class="premium-link" href="%1$s" target="_blank">' . esc_html__( 'Upgrade to Premium', 'advanced-nocaptcha-recaptcha' ) . '</a> <a class="premium-link-not-btn" href="%2$s" target="_blank">' . esc_html__( 'Get a FREE 7-day trial', 'advanced-nocaptcha-recaptcha' ) . '</a></p>
						</div>
					</div>
				</div>',
				esc_url( 'https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/pricing/?utm_source=plugin&utm_medium=banner&utm_campaign=C4WP&utm_content=plugin+premium+button' ),
				function_exists( 'c4wp_fs' ) ? c4wp_fs()->get_upgrade_url() : 'https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/plugin-trial/?utm_source=plugin&utm_medium=banner&utm_campaign=C4WP&utm_content=get+trial'
			);
		endif;
		return $return;
	}

	/**
	 * Validate targetted options and remove from the array which is about to be saved if invalid.
	 * Also handles error/success notices based on result.
	 *
	 * @param  array $value
	 * @return array
	 *
	 * @since 7.0.0
	 */
	function validate_and_set_notices( $value ) {
		$errors = array();

		if ( isset( $value['site_key'] ) && isset( $value['secret_key'] ) ) {
			$site_key   = $value['site_key'];
			$secret_key = $value['secret_key'];
			$allowed    = array( '-', '_' );

			if ( empty( $site_key ) || ! ctype_alnum( str_replace( $allowed, '', $site_key ) ) ) {
				$errors[] = 'empty_site_key';
				unset( $value['site_key'] );
			}
			if ( empty( $secret_key ) || ! ctype_alnum( str_replace( $allowed, '', $secret_key ) ) ) {
				$errors[] = 'empty_secret_key';
				unset( $value['secret_key'] );
			}
		}

		// Let user know how it went.
		if ( $errors ) {
			set_transient( 'c4wp_admin_options_errors', $errors, 30 );
		} else {
			set_transient( 'c4wp_admin_options_errors', array( 'success' ), 30 );
		}

		return $value;
	}

	function add_settings_link( $links ) {
		// add settings link in plugins page
		$settings_link = '<a href="' . c4wp_settings_page_url() . '">' . esc_html__( 'Settings', 'advanced-nocaptcha-recaptcha' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	function display_help_page() {
		require_once 'templates/help/index.php';
	}

	function display_upgrade_page() {
		require_once 'templates/upgrade/index.php';
	}

	static function push_at_to_associative_array( $array, $key, $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys, true );
		$pos   = false === $index ? count( $array ) : $index + 1;

		$array = array_slice( $array, 0, $pos, true ) + $new + array_slice( $array, $pos, count( $array ) - 1, true );
		return $array;
	}


} //END CLASS

add_action( 'wp_loaded', array( C4WP_Settings::init(), 'actions_filters' ) );
