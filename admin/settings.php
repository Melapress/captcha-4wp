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

			add_action( 'admin_menu', [ $this, 'menu_page' ] );
	}
	
	function admin_enqueue_scripts() {
		wp_register_script( 'c4wp-admin', C4WP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), C4WP_PLUGIN_VERSION, false );
	}

	function admin_init() {
		register_setting( 'c4wp_admin_options', 'c4wp_admin_options', [ $this, 'options_sanitize' ] );

		$current_tab = 'c4wp-admin-captcha';
		if( ! empty( $_GET['page'] ) ) {
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
		$captcha_sections = [
			'google_keys' => [
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
			],
		];

		$settings_sections = [
			'forms'       => [
				'section_title'    => '',
				'section_callback' => function() {
					echo '<span style="margin-top: 10px; display: block;">';
					esc_html_e( 'In this page you can configure where on your website you want to add the CAPTCHA check. You can also configure several other settings, such as whitelisting IP addresses, excluding logged in users from CAPTCHA checks and more.', 'advanced-nocaptcha-recaptcha' );
					echo '</span>';
				},
			],
			'other'       => [
				'section_title' => '',
			],
		];

		$sections = ( $section_we_want == 'c4wp-admin-captcha' ) ? $captcha_sections : $settings_sections;
		return apply_filters( 'c4wp_settings_sections', $sections );
	}

	
	function get_fields() {
		$score_values = [];
		for ( $i = 0.0; $i <= 1; $i += 0.1 ) {
			$score_values[ "$i" ] = number_format_i18n( $i, 1 );
		}
		$score_description = sprintf(
			/* translators: expression "very restrictive" in bold */
			esc_html__( 'Any value above 0.5 is %s.', 'advanced-nocaptcha-recaptcha' ),
			'<strong>'.__( 'very restrictive', 'advanced-nocaptcha-recaptcha' ) . '</strong>'
		);		
		$score_description .= ' ' . esc_html__( 'This means that you might end up locked out from your website. Therefore test this on a staging website website beforehand.', 'advanced-nocaptcha-recaptcha' );

		$role_select_html =  '<select multiple="multiple" id="hide_from_roles" name="c4wp_admin_options[hide_from_roles]" display:none;width:100>%">';
		global $wp_roles;
		$allRoles      = $wp_roles->role_names;

		$forms_preamble_desc = esc_html__( 'You can add a CAPTCHA check to the above list of pages on WordPress.', 'advanced-nocaptcha-recaptcha' );
		
		if ( ! c4wp_is_premium_version() ) {
			$forms_preamble_desc .= sprintf( __( 'To add CAPTCHA checks to WooCommerce, Contact Form 7, BuddyPress and other forms created by third party plugins you need to %s', 'advanced-nocaptcha-recaptcha' ),
				'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( '#' ) . '">' . esc_html__( 'upgrade to Premium', 'advanced-nocaptcha-recaptcha' ) . '</a>'
			);
		}

		$premium_setting_class = ( c4wp_is_premium_version() ) ? 'premium-only' : 'disabled upgrade-required';
		
		$fields = array(
			'captcha_version_title' => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Select the type of reCAPTCHA you want to use', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'captcha_version' => array(
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
				'desc'       => esc_html__( 'For more details and a highlight of the differences between the different reCAPTCHA versions, refer to [XXX].', 'advanced-nocaptcha-recaptcha' ),
			),
			'site_key_title' => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Specify the Site & Secret key', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'site_key' => array(
				'label'      => esc_html__( 'Site Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => true,
			),
			'secret_key' => array(
				'label'      => esc_html__( 'Secret Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'desc'       => sprintf(
					'</br> ' . esc_html__( 'To communicate with Google and utilize the reCAPTCHA service you need to get a Site Key and Secret Key. You can obtain these keys for free by registering for your Google reCAPTCHA. Refer to %s if you need help with the process.', 'advanced-nocaptcha-recaptcha' ),					
					'<a href="' . esc_url( 'https://www.wpwhitesecurity.com/support/kb/get-google-recaptcha-keys/' ) . '" target="_blank">' . esc_html__( 'how to get the Google reCAPTCHA keys', 'advanced-nocaptcha-recaptcha' ) . '</a>'			
				),
				'required'   => true,
			),
			'score_title'            => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Configure the below optional settings to fine-tune the reCAPTCHA to your requirements.', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'score'              => array(
				'label'      => esc_html__( 'Captcha Score', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
				'std'        => '0.5',
				'options'    => $score_values,
				'desc'       => '</br>'. esc_html__( 'Use this setting to specify sensitivity of the CAPTCHA check. The closer to 1 the more sensitive the CAPTCHA check will be, which also means more traffic will be marked as spam. This option is only available for reCAPTCHA v3.', 'advanced-nocaptcha-recaptcha' ),			
			),
			'v3_script_load'     => array(
				'label'      => esc_html__( 'Load CAPTCHA v3 scripts on:', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
				'std'        => 'all_pages',
				'options'    => array(
					'all_pages'  => esc_html__( 'All Pages', 'advanced-nocaptcha-recaptcha' ),
					'form_pages' => esc_html__( 'Form Pages', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => '</br>'. esc_html__( 'By default CAPTCHA only loads on the pages where it is required, mainly forms. However, for V3 you can configure it to load on all pages so it has a better context of the traffic and works more efficiently. The CAPTCHA test will never interrupt users on non-form pages.', 'advanced-nocaptcha-recaptcha' ),
			),
			'language'           => array(
				'label'      => esc_html__( 'CAPTCHA language', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'std'        => 'en',
				'class'      => 'regular',
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
			'error_message'      => array(
				'label'      => esc_html__( 'Error message', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'std'        => esc_html__( 'Please solve the CAPTCHA to proceed', 'advanced-nocaptcha-recaptcha' ),
				'desc'       =>  '</br>'. esc_html__( 'Specify the message you want to show users who do not complete the CAPTCHA.', 'advanced-nocaptcha-recaptcha' ),
			),
			'theme'              => array(
				'label'      => esc_html__( 'Theme', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_checkbox c4wp-show-field-for-v2_invisible', // 
				'std'        => 'light',
				'options'    => array(
					'light' => esc_html__( 'Light', 'advanced-nocaptcha-recaptcha' ),
					'dark'  => esc_html__( 'Dark', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'size'               => array(
				'label'      => esc_html__( 'Size', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_checkbox', //  
				'std'        => 'normal',
				'options'    => array(
					'normal'    => esc_html__( 'Normal', 'advanced-nocaptcha-recaptcha' ),
					'compact'   => esc_html__( 'Compact', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'badge'              => array(
				'label'      => esc_html__( 'Badge', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v2_invisible', //  
				'std'        => 'bottomright',
				'options'    => array(
					'bottomright' => esc_html__( 'Bottom Right', 'advanced-nocaptcha-recaptcha' ),
					'bottomleft'  => esc_html__( 'Bottom Left', 'advanced-nocaptcha-recaptcha' ),
					'inline'      => esc_html__( 'Inline', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       =>  '</br>'. esc_html__( 'Badge shows for invisible captcha', 'advanced-nocaptcha-recaptcha' ),
			),
			'recaptcha_domain'      => array(
				'label'      => esc_html__( 'reCAPTCHA domain', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => c4wp_recaptcha_domain(),
				'options'    => array(
					'google.com'   => 'google.com',
					'google.net'   => 'google.net',
					'recaptcha.net' => 'recaptcha.net',
				),
				'desc'        =>  '</br>'. esc_html__( 'Use this setting to change the domain if Google is not accessible or blocked.', 'advanced-nocaptcha-recaptcha' ),
			),			
			'remove_css'         => array(
				'label'      => esc_html__( 'Remove CSS', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'checkbox',
				'class'      => 'checkbox toggleable disabled c4wp-show-field-for-v2_checkbox',
				'cb_label'   => esc_html__( "Remove this plugin's css from login page?", 'advanced-nocaptcha-recaptcha' ),
				'desc'       => '</br>'.__( 'This css increase login page width to adjust with Captcha width.', 'advanced-nocaptcha-recaptcha' ),
			),

			// Settings.
			'enabled_forms_title'            => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Select where on your website you want to add the CAPTCHA check', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'enabled_forms'      => array(
				'label'      => esc_html__( 'Add CAPTCHA check on these pages', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
				'class'      => 'checkbox',
				'options'    => array(
					'login'          => esc_html__( 'Login form', 'advanced-nocaptcha-recaptcha' ),
					'registration'   => esc_html__( 'Registration form', 'advanced-nocaptcha-recaptcha' ),
					'reset_password' => esc_html__( 'Reset password form', 'advanced-nocaptcha-recaptcha' ),
					'lost_password'  => esc_html__( 'Lost password form', 'advanced-nocaptcha-recaptcha' ),
					'comment'        => esc_html__( 'Comments form', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => '</br>'. $forms_preamble_desc,
			),

			'loggedin_hide_title'            => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => $premium_setting_class,
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Do you want to hide CAPTCHA tests for logged in users?', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'loggedin_hide'      => array(
				'label'      => esc_html__( 'Logged in Hide', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'checkbox',
				'class'      => $premium_setting_class .' checkbox remove-space-below',
				'cb_label'   => esc_html__( 'Hide Captcha for logged in users?', 'advanced-nocaptcha-recaptcha' ),
			),
			'loggedin_hide_for_roles'      => array(
				'label'      => '',
				'section_id' => 'forms',
				'type'       => 'checkbox',
				'class'      => $premium_setting_class .' checkbox remove-space-below remove-space-above',
				'cb_label'   => esc_html__( 'Hide CAPTCHA tests for logged in users with these user roles', 'advanced-nocaptcha-recaptcha' ),
			),
			'loggedin_hide_roles'      => array(
				'label'      =>  esc_html__( 'Select roles', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
				'class'      => $premium_setting_class .' checkbox loggedin_hide',
				'options'    => $allRoles,
				'desc'       => '</br>'. esc_html__( 'By default the CAPTCHA tests are always shown. However, you can disable CAPTCHA tests for logged in users, or for users with a specific user role.', 'advanced-nocaptcha-recaptcha' ),
			),
			'whitelisted_ips_title'            => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => $premium_setting_class,
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Do you want to remove CAPTCHA tests for some IP addresses?', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'whitelisted_ips_input'              => array(
				'label'      => esc_html__( 'No CAPTCHA tests for these IP addresses', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => $premium_setting_class .' regular',
				'std'        => '<div id="whitelist-ips-userfacing"></div><input type="text" id="whitelist_ips_input"></input><a href="#" class="button button-primary" id="add-ip">Add IP</a>',
				'desc'       => esc_html__( 'If you do not want any CAPTCHA tests from traffic coming from specific IP addresses, add these IP addresses in the option above.', 'advanced-nocaptcha-recaptcha' ),
			),
			'whitelisted_ips'              => array(
				'section_id' => 'forms',
				'type'       => 'textarea',
				'class'      => $premium_setting_class .' regular hidden',
			),
			'whitelisted_urls_input'              => array(
				'label'      => esc_html__( 'Exclude CAPTCHA from these URLs', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => $premium_setting_class .' regular',
				'std'        => '<div id="whitelist-urls-userfacing"></div><input type="text" id="whitelist_urls_input"></input><a href="#" class="button button-primary" id="add-url">Add  URL</a>',
				'desc'       => esc_html__( 'If you want to exlude certain paths from CAPTCHA verification (for example /wp-json/, add the path to the option above.', 'advanced-nocaptcha-recaptcha' ),
			),
			'whitelisted_urls'              => array(
				'section_id' => 'forms',
				'type'       => 'textarea',
				'class'      => $premium_setting_class .' regular hidden',
			),
			'failed_login_enable_title'            => array(
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => $premium_setting_class,
				'label'      => sprintf(
					'<strong style="position: absolute;">%1$s</strong>',
					esc_html__( 'Trigger the CAPTCHA check on the login page only when there are failed logins', 'advanced-nocaptcha-recaptcha' )
				),
			),
			'failed_login_enable' => array(
				'label'             => esc_html__( 'Only trigger CAPTCHA on failed logins', 'advanced-nocaptcha-recaptcha' ),
				'section_id'        => 'forms',
				'std'               => 0,
				'type'       => 'checkbox',
				'class'      =>  $premium_setting_class .' checkbox',
				'desc'              => esc_html__( 'You can bypass the CAPTCHA tests by default and only initialize it when there are a number of failed logins from a particular IP address.', 'advanced-nocaptcha-recaptcha' ),
			),
			'failed_login_allow' => array(
				'label'             => esc_html__( 'Number of failed logins required to trigger CAPTCHA tests', 'advanced-nocaptcha-recaptcha' ),
				'section_id'        => 'forms',
				'std'               => 3,
				'type'              => 'number',
				'class'             =>  $premium_setting_class .' regular-number remove-space-below',
				'sanitize_callback' => 'absint',
				'desc'              => esc_html__( 'Number of failed login attempts can be between 1 and 20.', 'advanced-nocaptcha-recaptcha' ),				
			),
			'failed_login_cron_schedule' => array(
				'cb_label'          => esc_html__( 'Flush the list of IP addresses every', 'advanced-nocaptcha-recaptcha' ),
				'cb_label_after'    => esc_html__( 'days', 'advanced-nocaptcha-recaptcha' ),
				'section_id'        => 'forms',
				'std'               => 7,
				'type'              => 'number-inline',
				'class'             =>  $premium_setting_class .' regular-number number-inline',
				'sanitize_callback' => 'absint',
			),
		);
		if ( ! c4wp_is_premium_version() ) :

			$premium_area['premium_title'] = array(
				'section_id' => 'forms',
				'type'       => 'html',				
				'class'      => 'premium-title-wrapper',
				'label'      => sprintf(
					'<strong class="premium-title">%1$s </br></br><span style="font-weight: normal;">%2$s </br><a href="%4$s" target="_blank" class="premium-link">%3$s</a></span></strong>',
					esc_html__( 'Upgrade now for these premium features.', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'With CAPTCHA 4WP Pro you can quickly and easily add s CAPTCHA to your WooCommerce checkout, login, and registration forms. Using Contact Form 7? No problem, CAPTCHA 4WP has you covered and so much more.', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'Find out more', 'advanced-nocaptcha-recaptcha' ),
					esc_url( 'http://captcha-pro.local/wp-admin/admin.php?billing_cycle=annual&page=c4wp-admin-captcha-pricing' )
				),
			);

			$fields = $this->push_at_to_associative_array( $fields, 'enabled_forms', $premium_area );
		endif;

		if ( c4wp_is_premium_version() ) {
			$additonal_options = [
				'ms_user_signup'   => esc_html__( 'Multisite User Signup Form', 'advanced-nocaptcha-recaptcha' ),
				'bbp_new'          => esc_html__( 'bbPress New topic', 'advanced-nocaptcha-recaptcha' ),
				'bbp_reply'        => esc_html__( 'bbPress reply to topic', 'advanced-nocaptcha-recaptcha' ),
				'bp_register'      => esc_html__( 'BuddyPress register', 'advanced-nocaptcha-recaptcha' ),
				'bp_comments_form' => esc_html__( 'BuddyPress comments form', 'advanced-nocaptcha-recaptcha' ),
				'bp_create_group'  => esc_html__( 'BuddyPress create group', 'advanced-nocaptcha-recaptcha' ),
				'wc_checkout'      => esc_html__( 'WooCommerce Checkout', 'advanced-nocaptcha-recaptcha' ),
				'wc_login'         => esc_html__( 'WooCommerce Login', 'advanced-nocaptcha-recaptcha' ),
			];

			$fields['enabled_forms']['options'] = $fields['enabled_forms']['options'] + $additonal_options;
			
			$additonal_wc_options['wc_checkout_position'] = [
				'label'      => esc_html__( 'WooCommerce checkout position', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => c4wp_recaptcha_domain(),
				'options'    => array(
					'default'            => esc_html__( 'Below checkout', 'advanced-nocaptcha-recaptcha' ),
					'above_checkout_btn' => esc_html__( 'Above checkout button', 'advanced-nocaptcha-recaptcha' ),
					'above_payment'      => esc_html__( 'Above payment selection', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'        =>  '</br>'. esc_html__( 'Choose a location for the captcha input (v2 checkbox only)', 'advanced-nocaptcha-recaptcha' ),
			];

			$fields = $this->push_at_to_associative_array( $fields, 'enabled_forms', $additonal_wc_options );

			$premium_area['auto_detect_lang'] = array(
				'section_id' => 'google_keys',
				'label'      => esc_html__( 'Auto detect language', 'advanced-nocaptcha-recaptcha' ),
				'std'        => 0,
				'type'       => 'checkbox',
				'class'      =>  $premium_setting_class .'checkbox',
				'cb_label'   => esc_html__( 'Enable this setting to have our plugin auto-detect the correct language.', 'advanced-nocaptcha-recaptcha' ),
			);

			$fields = $this->push_at_to_associative_array( $fields, 'v3_script_load', $premium_area );
		}

		$fields = apply_filters( 'c4wp_settings_fields', $fields );

		foreach ( $fields as $field_id => $field ) {
			$fields[ $field_id ] = wp_parse_args(
				$field, [
					'id'             => $field_id,
					'label'          => '',
					'cb_label'       => '',
					'cb_label_after' => '',
					'type'           => 'text',
					'class'          => 'regular-text',
					'section_id'     => '',
					'desc'           => '',
					'std'            => '',
				]
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

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
			case 'number':
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
			case 'number-inline':
				printf(
						'%8$s <input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" value="%6$s"%7$s /> %9$s',
						esc_attr( 'number' ),
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] ),
						esc_attr( $field['id'] ),
						isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
						esc_attr( $value ),
						$attrib,
						esc_attr( $field['cb_label'] ),
						esc_attr( $field['cb_label_after'] )
					);
					break;
			case 'textarea':
					printf( '<textarea id="c4wp_admin_options_%1$s" class="%2$s" name="c4wp_admin_options[%3$s]" placeholder="%4$s" %5$s >%6$s</textarea>',
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
			case 'number-inline':
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
					$sanitized = isset( $field['std']) ? $field['std'] : '';
				}
				break;
			default:
				$sanitized = apply_filters( 'c4wp_settings_field_sanitize_filter_' . $field['type'], $value, $field );
				break;
		}
		
		return apply_filters( 'c4wp_settings_field_sanitize_filter', $sanitized, $field, $value );
	}

	function menu_page() {
		add_menu_page( esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA 4WP', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-captcha', [ $this, 'admin_settings' ],  '', 99 );
		$hook_captcha_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-captcha', [ $this, 'admin_settings' ] );
		$hook_settings_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'CAPTCHA 4WP Settings', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Settings', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-settings', [ $this, 'admin_settings' ] );
		$hook_help_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-help', [ $this, 'admin_settings' ] );
		
		if ( ! function_exists( 'c4wp_fs' ) || function_exists( 'c4wp_fs' ) && c4wp_fs()->is_not_paying() ) {
			$hook_upgrade_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), 'manage_options', 'c4wp-admin-upgrade', [ $this, 'admin_settings' ] );
			add_action( "load-$hook_upgrade_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		}

		add_action( "load-$hook_captcha_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		add_action( "load-$hook_help_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		add_action( "load-$hook_settings_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
	}
	
	function network_menu_page() {
		add_menu_page( esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA 4WP', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-captcha', [ $this, 'admin_settings' ],  '', 99 );
		$hook_captcha_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'CAPTCHA', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-captcha', [ $this, 'admin_settings' ] );
		$hook_settings_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'CAPTCHA 4WP Settings', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Settings', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-settings', [ $this, 'admin_settings' ] );
		$hook_help_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Help & Contact Us', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-help', [ $this, 'admin_settings' ] );

		if ( ! function_exists( 'c4wp_fs' ) || function_exists( 'c4wp_fs' ) && c4wp_fs()->is_not_paying() ) {
			$hook_upgrade_submenu = add_submenu_page( 'c4wp-admin-captcha',  esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), esc_html__( 'Premium Features ➤', 'advanced-nocaptcha-recaptcha' ), 'manage_network_options', 'c4wp-admin-upgrade', [ $this, 'admin_settings' ] );
			add_action( "load-$hook_upgrade_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		}

		add_action( "load-$hook_captcha_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		add_action( "load-$hook_help_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
		add_action( "load-$hook_settings_submenu", [ $this, 'c4wp_admin_page_enqueue_scripts' ] );
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
				$value = [];
			}

			$value = $this->validate_and_set_notices( $value );

			c4wp_update_option( $value );

			wp_safe_redirect( add_query_arg( 'updated', true ) );
			exit;
		}
	}

	function admin_settings() {
		wp_enqueue_script( 'c4wp-admin' );
		wp_localize_script( 'c4wp-admin', 'anrScripts', array(
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'ipWarning' => esc_html__( 'Please supply a valid IP', 'advanced-nocaptcha-recaptcha' )
		) );

		$current_tab = 'c4wp-admin-captcha';
		if( ! empty( $_GET['page'] ) ) {
			$current_tab = $_GET['page'];
		}
		?>
		<div class="wrap fs-section">
			<!-- Plugin settings go here -->
			<div id="poststuff">
				<h1>
				<?php
				if ( 'c4wp-admin-captcha' == $current_tab ) {
					_e( 'Captcha Configuration', 'advanced-nocaptcha-recaptcha' );
				} else if ( 'c4wp-admin-settings' == $current_tab ) {
					_e( 'Settings', 'advanced-nocaptcha-recaptcha' );
				}
				?>
				</h1>
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div id="tab_container">
							<?php
							if ( 'c4wp-admin-captcha' == $current_tab || 'c4wp-admin-settings' == $current_tab ) {
								$this->settings_form();
							} else if ( 'c4wp-admin-help' == $current_tab ) {
								$this->display_help_page();
							} else if ( 'c4wp-admin-upgrade' == $current_tab ) {
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

	function settings_form(){

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
		if ( empty ( $errors ) ) {
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
			if ( ! class_exists( 'C4WP_Pro' ) ) :
			$return .= '<style>ul.c4wp-pro-features-ul li.dashicons-yes-alt:before {color:green;}</style>';
			$return .= sprintf( '<div class="postbox">
				<h3 class="hndle" style="text-align: center;">
					<span>' . esc_html__( 'PRO Features', 'advanced-nocaptcha-recaptcha' ) . '</span>
				</h3>
				<div class="inside">
					<div>
						<ul class="c4wp-pro-features-ul">
							<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'WooCommerce forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Contact Form 7 forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'bbPress New topic form', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'bbPress reply to topic form', 'advanced-nocaptcha-recaptcha' ) . '</li>
							<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'BuddyPress register form', 'advanced-nocaptcha-recaptcha' ) . '</li>
						</ul>
						<p style="text-align: center; margin: auto"><a class="button button-secondary" href="%1$s">' . esc_html__( 'View Details', 'advanced-nocaptcha-recaptcha' ) . '</a></p>
					</div>
				</div>
			</div>', function_exists( 'c4wp_fs' ) ? c4wp_fs()->get_upgrade_url() : 'https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/' );
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
		$errors = [];

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
			set_transient( 'c4wp_admin_options_errors', [ 'success' ], 30 );
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

	function push_at_to_associative_array( $array, $key, $new ){
        $keys  = array_keys( $array );
		$index = array_search( $key, $keys, true  );
        $pos   = false === $index ? count( $array ) : $index + 1;
        
        $array = array_slice($array, 0, $pos, true) + $new + array_slice($array, $pos, count($array) - 1, true);
        return $array;
    }

		
} //END CLASS

add_action( 'wp_loaded', array( C4WP_Settings::init(), 'actions_filters' ) );
