<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Settings class.
 */
class C4WP_Settings {

	/**
	 * Class instance.
	 *
	 * @var C4WP_Settings
	 */
	private static $instance;

	/**
	 * Class initiator.
	 *
	 * @return $instance - Class instance.
	 */
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Actions used by our class.
	 *
	 * @return void
	 */
	public function actions_filters() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'settings_save' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( C4WP_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		$use_network_hooks = is_multisite();

		if ( $use_network_hooks ) {
			add_action( 'network_admin_menu', array( $this, 'network_menu_page' ) );
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( C4WP_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'menu_page' ) );
		}


		add_action( 'wp_ajax_c4wp_reset_captcha_config', array( $this, 'c4wp_reset_captcha_config' ), 10, 1 );
		add_action( 'wp_ajax_c4wp_nocaptcha_plugin_notice_ignore', array( $this, 'c4wp_nocaptcha_plugin_notice_ignore' ), 10, 1 );

		// Logging and testing.
		$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );
		if ( $dev_mode ) {
			add_filter( 'c4wp_settings_fields', array( $this, 'add_logging_and_testing_settings' ) );
		}

		add_action( 'admin_notices', array( $this, 'v3_fallback_notice' ) );
	}

	/**
	 * Add notice to alert admin to new feature.
	 *
	 * @return void
	 */
	public function v3_fallback_notice() {
		$need_notice = get_option( 'c4wp_v3_failover_available', false );
		$current_val = c4wp_get_option( 'failure_action' );
		$user = wp_get_current_user();

		if ( $need_notice && ! $current_val ) {
			$user         = wp_get_current_user();
			$settings_url = function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
			$notice_nonce = wp_create_nonce( 'dismiss_captcha_notice' );
			$help_text    = wp_sprintf(
				'%1$s %2$s %3$s %4$s %5$s %6$s',
				'<strong>' . esc_html__( 'Important:', 'advanced-nocaptcha-recaptcha' ) . '</strong>',
				esc_html__( 'To reconfigure the failover now, once you are redirected to the plugin\'s configuration page click', 'advanced-nocaptcha-recaptcha' ),
				'<i>' . esc_html__( 'Reconfigure reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ) . '</i>',
				esc_html__( 'and click', 'advanced-nocaptcha-recaptcha' ),
				'<i>' . esc_html__( 'Next', 'advanced-nocaptcha-recaptcha' ) . '</i>',
				esc_html__( 'in the wizard until you get to the failover settings.', 'advanced-nocaptcha-recaptcha' )
			);
			if ( in_array( 'administrator', (array) $user->roles ) ) {
				echo '<div class="notice notice-info" style="padding-bottom: 15px;">
					<p>' . esc_html__( 'In the latest version of CAPTCHA 4WP you can configure a failover action for your CAPTCHA check. This means that you can configure the plugin to show a CAPTCHA checkbox or redirect the user when the current v3 reCAPTCHA check fails. Use the buttons below to configure the failover or close this admin notice.', 'advanced-nocaptcha-recaptcha' ) . '</p>
					<p>' . $help_text . '</p>
					<a href="' . esc_url( $settings_url ) . '" class="button button-primary">' . esc_html__( 'Configure failover action now', 'advanced-nocaptcha-recaptcha' ) . '</a> <a href="#c4wp-cancel-v3-failover-notice" data-nonce="' . esc_attr( $notice_nonce ) . '" data-notice-type="v3_fallback" class="button button-secondary">' . esc_html__( "I'll configure it later", 'advanced-nocaptcha-recaptcha' ) . '</a>
					</div>';
			}
		}
	}

	/**
	 * Add settings page scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_register_script( 'c4wp-admin', C4WP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-dialog' ), C4WP_PLUGIN_VERSION, false );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	/**
	 * Add settings pages to WP admin.
	 *
	 * @return void
	 */
	public function admin_init() {
		register_setting( 'c4wp_admin_options', 'c4wp_admin_options', array( $this, 'options_sanitize' ) );

		$current_tab = 'c4wp-admin-captcha';
		if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore
			$current_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore
		}

		foreach ( $this->get_sections( $current_tab ) as $section_id => $section ) {
			add_settings_section( $section_id, $section['section_title'], ! empty( $section['section_callback'] ) ? $section['section_callback'] : null, 'c4wp_admin_options' );
		}
		$skip = array(
			'captcha_version_title',
			'captcha_version',
			'site_key_title',
			'site_key_subtitle',
			'site_key',
			'secret_key',
			'key_validation',
			'failure_action',
			'failure_redirect',
			'failure_v2_site_key',
			'failure_v2_secret_key',
			'failure_key_validation',
		);
		foreach ( $this->get_fields() as $field_id => $field ) {
			if ( in_array( $field_id, $skip ) ) {
				continue;
			}
			add_settings_field( $field['id'], $field['label'], ! empty( $field['callback'] ) ? $field['callback'] : array( $this, 'callback' ), 'c4wp_admin_options', $field['section_id'], $field );
		}
	}

	/**
	 * Create sections used within settings.
	 *
	 * @param string $section_we_want - Section to return.
	 * @return array $sections - Sections created.
	 */
	public function get_sections( $section_we_want = 'c4wp-admin-captcha' ) {
		$captcha_sections = array(
			'google_keys' => array(
				'section_title'    => '',
				'section_callback' => function() {
					$settings_url = function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ? network_admin_url( 'admin.php?page=c4wp-admin-settings' ) : admin_url( 'admin.php?page=c4wp-admin-settings' );
					echo '<div id="c4wp-setup-wizard">' . wp_kses( $this->wizard_markup(), c4wp_allowed_kses_args() ) . '</div>';
					echo '<span style="margin-top: 10px; display: block;">';
					printf(
						/* translators: link to the settings page with text "Settings page" */
						esc_html__( 'Use the CAPTCHA configuration wizard to integrate the Google reCAPTCHA service so you can add CAPTCHA checks on your website. Once you configure the integration navigate to the %s page to configure where CAPTCHA should be added on your website, whitelist IP addresses and configure other settings', 'advanced-nocaptcha-recaptcha' ),
						'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings & placements', 'advanced-nocaptcha-recaptcha' ) . '</a>'
					);
					echo '</span>';
					echo wp_kses( $this->wizard_launcher_area(), c4wp_allowed_kses_args() );
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

		$sections = ( 'c4wp-admin-captcha' === $section_we_want ) ? $captcha_sections : $settings_sections;
		return apply_filters( 'c4wp_settings_sections', $sections );
	}

	/**
	 * Main plugin setting fields.
	 *
	 * @return array - Settings fields.
	 */
	public function get_fields() {
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
				/* translators:link to upgrade page */
				__( 'To add CAPTCHA checks to WooCommerce, Contact Form 7, BuddyPress and other forms created by third party plugins you need to %s', 'advanced-nocaptcha-recaptcha' ),
				'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( '#' ) . '">' . esc_html__( 'upgrade to Premium', 'advanced-nocaptcha-recaptcha' ) . '</a>'
			);
			$lang_selector_desc .= esc_html__( ' In the Premium edition you can configure the plugin to automatically detect the language settings of the visitor\'s and use that language.', 'advanced-nocaptcha-recaptcha' );
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
					'<strong style="position: absolute; font-size: 16px;">%1$s</strong>',
					esc_html__( 'Step 1: Select the type of reCAPTCHA you want to use on your website.', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content c4wp-wizard-captcha-version',
			),
			'captcha_version'        => array(
				'label'      => esc_html__( 'reCAPTCHA version', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'radio',
				'class'      => 'regular c4wp-wizard-captcha-version',
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
					'<strong style="position: absolute; font-size: 16px">%1$s</strong>',
					esc_html__( 'Step 2: Specify the Site & Secret keys', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content c4wp-wizard-site-keys',
			),
			'site_key_subtitle'      => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<p class="description c4wp-desc" style="position: absolute;">%1$s</p>',
					sprintf(
						/* translators:link to help page */
						esc_html__( 'To utilize the Google reCAPTCHA service on your website you need to get a Site and Secret key. If you do not have these keys yet, you can option them for free by registering to the Google reCAPTCHA service. Refer to the document %s for a step by step explanation of how to get these keys.', 'advanced-nocaptcha-recaptcha' ),
						'<a href="' . esc_url( 'https://www.wpwhitesecurity.com/support/kb/get-google-recaptcha-keys/' ) . '" target="_blank">' . esc_html__( 'how to get the Google reCAPTCHA keys', 'advanced-nocaptcha-recaptcha' ) . '</a>'
					)
				),
				'class'      => 'wrap-around-content c4wp-wizard-site-keys',
			),
			'site_key'               => array(
				'label'      => esc_html__( 'Site Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => true,
				'class'      => 'c4wp-wizard-site-keys',
			),
			'secret_key'             => array(
				'label'      => esc_html__( 'Secret Key', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => true,
				'class'      => 'c4wp-wizard-site-keys',
			),
			'key_validation'         => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => esc_html__( 'Key Validation', 'advanced-nocaptcha-recaptcha' ),
				'std'        => '<p class="description mb-10">' . esc_html__( 'Once you enter the correct Site and Secret keys above, the CAPTCHA method you want to use on your website will appear below. If the keys are incorrect you will instead see an error. If you see an error make sure the CAPTCHA version, website domain and both keys match.', 'advanced-nocaptcha-recaptcha' ) . '</p><div id="render-settings-placeholder"></div>',
				'class'      => 'c4wp-wizard-site-keys',
			),
			'score_title'            => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<strong style="position: absolute; font-size: 16px;">%1$s</strong>',
					esc_html__( 'Optional settings: Fine-tune reCAPTCHA to your requirements', 'advanced-nocaptcha-recaptcha' )
				),
				'class'      => 'wrap-around-content c4wp-wizard-additional-settings',
			),
			'score_subtitle'         => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => sprintf(
					'<p class="description c4wp-desc" style="position: absolute;">%1$s</p>',
					sprintf(
						esc_html__( 'Use the below settings to configure and fine-tune CAPTCHA to your requirements. All the below settings are optional and with them you can configure different aspects of the CAPTCHA checks on your website, such as look and feel and also sensitivy.', 'advanced-nocaptcha-recaptcha' )
					)
				),
				'class'      => 'wrap-around-content c4wp-wizard-additional-setting',
			),
			'score'                  => array(
				'label'      => esc_html__( 'Captcha Score', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
				'std'        => '0.5',
				'options'    => $score_values,
				'desc'       => esc_html__( 'Use this setting to specify sensitivity of the CAPTCHA check. The closer to 1 the more sensitive the CAPTCHA check will be, which also means more traffic will be marked as spam. This option is only available for reCAPTCHA v3.', 'advanced-nocaptcha-recaptcha' ),
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
				'desc'       => sprintf(
					__( 'By default the CAPTCHA service only learns about how users interact with your website via the scripts loaded on the form pages. However, for V3 you can configure it to load on all pages so it can learn and has a better context of the traffic so it can better determine what is spam and not. When CAPTCHA is configured to load on all pages will never interrupt users on non-form pages. Note that the CAPTCHA check still has to be included in the form(s). Refer to the %1$s for more information on how to add the CAPTCHA checks to your forms.', 'advanced-nocaptcha-recaptcha' ),
					sprintf(
						'<a href="https://www.wpwhitesecurity.com/support/kb/category/captcha-4wp-plugin-documentation/" target="_blank">' . esc_html__( 'CAPTCHA 4WP knowledge base', 'advanced-nocaptcha-recaptcha' ) . '</a>'
					)
				)
			),
			// 'v3_script_async'         => array(
			// 	'label'      => esc_html__( 'Load 3 scripts asynchronously:', 'advanced-nocaptcha-recaptcha' ),
			// 	'section_id' => 'google_keys',
			// 	'type'       => 'select',
			// 	'class'      => 'regular toggleable disabled c4wp-show-field-for-v3',
			// 	'std'        => 'all_pages',
			// 	'options'    => array(
			// 		'no'  => esc_html__( 'No', 'advanced-nocaptcha-recaptcha' ),
			// 		'yes' => esc_html__( 'Yes', 'advanced-nocaptcha-recaptcha' ),
			// 	),
			// 	'desc'       => esc_html__( 'Use this setting to load v3 scripts asynchronously (default no).', 'advanced-nocaptcha-recaptcha' ),

			// ),
			'language_handling'      => array(
				'label'      => esc_html__( 'CAPTCHA language', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'radio',
				'class'      => 'regular remove-space-below remove-radio-br',
				'std'        => 'manually_choose',
				'options'    => array(
					'manually_choose' => esc_html__( 'Select a language', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => $lang_selector_desc,
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
				'desc'       => esc_html__( 'Specify the message you want to show users who do not complete the CAPTCHA.', 'advanced-nocaptcha-recaptcha' ),
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
				'class'      => 'regular full-hide toggleable disabled c4wp-show-field-for-v2_invisible',
				'std'        => 'bottomright',
				'options'    => array(
					'bottomright' => esc_html__( 'Bottom Right', 'advanced-nocaptcha-recaptcha' ),
					'bottomleft'  => esc_html__( 'Bottom Left', 'advanced-nocaptcha-recaptcha' ),
					'inline'      => esc_html__( 'Inline', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => esc_html__( 'Badge shows for invisible captcha', 'advanced-nocaptcha-recaptcha' ),
			),
			'badge_v3'               => array(
				'label'      => esc_html__( 'Badge', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular full-hide toggleable disabled c4wp-show-field-for-v3',
				'std'        => 'bottomright',
				'options'    => array(
					'bottomright' => esc_html__( 'Bottom Right', 'advanced-nocaptcha-recaptcha' ),
					'bottomleft'  => esc_html__( 'Bottom Left', 'advanced-nocaptcha-recaptcha' ),
				),
				'desc'       => esc_html__( 'Badge shows for invisible captcha v3', 'advanced-nocaptcha-recaptcha' ),
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
				'desc'       => esc_html__( 'Use this setting to change the domain if Google is not accessible or blocked.', 'advanced-nocaptcha-recaptcha' ),
			),
			'remove_css'             => array(
				'label'      => esc_html__( 'Remove CSS', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'checkbox',
				'class'      => 'checkbox toggleable disabled c4wp-show-field-for-v2_checkbox',
				'cb_label'   => esc_html__( "Remove this plugin's css from login page?", 'advanced-nocaptcha-recaptcha' ),
				'desc'       => __( 'This css increase login page width to adjust with Captcha width.', 'advanced-nocaptcha-recaptcha' ),
			),

			'failure_action'         => array(
				'label'      => esc_html__( 'v3 failover action:', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => 'nothing',
				'options'    => array(
					'v2_checkbox' => esc_html__( 'Show a v2 CAPTCHA checkbox', 'advanced-nocaptcha-recaptcha' ),
					'redirect'    => esc_html__( 'Redirect the website visitor to a URL', 'advanced-nocaptcha-recaptcha' ),
					'nothing'     => esc_html__( 'Take no action', 'advanced-nocaptcha-recaptcha' ),
				),
			),
			'failure_redirect'       => array(
				'label'      => esc_html__( 'Redirect URL', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => false,
				'class'      => 'c4wp-wizard-site-keys toggleable c4wp-show-field-for-redirect',
			),
			'failure_v2_site_key'    => array(
				'label'      => esc_html__( 'v2 Site key:', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => false,
				'class'      => 'c4wp-wizard-site-keys toggleable c4wp-show-field-for-v2_checkbox',
			),
			'failure_v2_secret_key'  => array(
				'label'      => esc_html__( 'v2 Secret key:', 'advanced-nocaptcha-recaptcha' ),
				'section_id' => 'google_keys',
				'required'   => false,
				'class'      => 'c4wp-wizard-site-keys toggleable c4wp-show-field-for-v2_checkbox',
			),
			'failure_key_validation' => array(
				'section_id' => 'google_keys',
				'type'       => 'html',
				'label'      => esc_html__( 'Key Validation', 'advanced-nocaptcha-recaptcha' ),
				'std'        => '<p class="description mb-10"><span class="toggleable c4wp-show-field-for-v2_checkbox"></span>' . esc_html__( 'Once you enter the correct Site and Secret keys above, the CAPTCHA method you want to use on your website will appear below. If the keys are incorrect you will instead see an error. If you see an error make sure the CAPTCHA version, website domain and both keys match.', 'advanced-nocaptcha-recaptcha' ) . '</p><div id="render-settings-placeholder-fallback"></div>',
				'class'      => 'c4wp-wizard-site-keys toggleable c4wp-show-field-for-v2_checkbox',
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
			$logos_url                     = C4WP_PLUGIN_URL . 'assets/img/third-party-logos.png';
			$premium_area['premium_title'] = array(
				'section_id' => 'forms',
				'type'       => 'html',
				'class'      => 'premium-title-wrapper h-140',
				'label'      => sprintf(
					'<span class="premium-title"><strong>Upgrade to Premium to:</strong><p>Add spam protection to block spam bots and allow real humans to easily interact with your WordPress website by adding CAPTCHA to any form on your website, including out of the box support for forms on third party plugins such as:</p><p><ul style="list-style: disc; padding-left: 17px; font-weight: 400;"><li>%5$s</li><li>%6$s</li><li>%7$s</li><li>%8$s</li></ul></p><img src="%9$s" style="max-width: 600px; clear: both; display: block;"><a href="%3$s" class="premium-link" target="_blank">%1$s</a> <a href="%4$s" class="premium-link-not-btn">%2$s</a></span>',
					esc_html__( 'Upgrade to Premium', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'Find out more', 'advanced-nocaptcha-recaptcha' ),
					esc_url( 'https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/pricing/?utm_source=plugin&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+premium+button' ),
					esc_url( $features_url ),
					esc_html__( 'Checkout and login pages on WooCommerce stores', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'Contact Form 7, Gravity Forms, WPForms, MailChimp 4 WordPress forms', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'BuddyPress and bbPress', 'advanced-nocaptcha-recaptcha' ),
					esc_html__( 'And others', 'advanced-nocaptcha-recaptcha' ),
					esc_url( $logos_url )
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
					'el_class'       => 'regular',
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

	/**
	 * Field callback.
	 *
	 * @param  array $field - Field data.
	 * @param  bool $return - To return markup or not.
	 * @return $output - HTML Markup.
	 */
	public function callback( $field, $return = false ) {
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

		if ( $return ) {
			ob_start();
		}

		if ( ! empty( $field['desc'] ) ) {
			printf( '<p class="description mb-10">%s</p>', wp_kses_post( $field['desc'] ) );
		}

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
			case 'submit':
				printf(
					'<input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s %8$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" value="%6$s"%7$s />',
					esc_attr( $field['type'] ),
					esc_attr( $field['id'] ),
					esc_attr( $field['el_class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib, // phpcs:ignore
					esc_attr( $field['class'] )
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
					$attrib, // phpcs:ignore
					esc_attr( $field['min_val'] ),
					esc_attr( $field['max_val'] )
				);
				break;
			case 'number-inline':
				printf(
					'%8$s <input type="%1$s" id="c4wp_admin_options_%2$s" class="%3$s" name="c4wp_admin_options[%4$s]" placeholder="%5$s" min="%10$s" max="%11$s" value="%6$s" %7$s /> %9$s',
					esc_attr( 'number' ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					esc_attr( $field['id'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					$attrib, // phpcs:ignore
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
						$attrib, // phpcs:ignore
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
				echo $field['std']; // phpcs:ignore
				break;
			case 'radio':
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<input type="radio" id="%1$s" name="c4wp_admin_options[%4$s]" value="%1$s" class="%5$s" %2$s><label for="%1$s">%3$s</label><br><br>',
						esc_attr( $key ),
						checked( $value, $key, false ),
						esc_attr( $label ),
						esc_attr( $field['id'] ),
						esc_attr( $field['el_class'] )
					);
				}
				break;

			default:
				/* translators:field type */
				printf( esc_html__( 'No hook defined for %s', 'advanced-nocaptcha-recaptcha' ), esc_html( $field['type'] ) );
				break;
		}

		if ( $return ) {
			$output = ob_get_clean();
			return $output;
		}
	}

	/**
	 * Sanitize provided value.
	 *
	 * @param string $value - Value to sanitize.
	 * @return string $value - Sanitized value.
	 */
	public function options_sanitize( $value ) {
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

	/**
	 * Sanitize posted value.
	 *
	 * @param string $value - Value to sanitize.
	 * @param array  $field - Field to sanitize.
	 * @return string$sanitized - Sanitized value.
	 */
	public function posted_value_sanitize( $value, $field ) {
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
				if ( isset( $field['id'] ) && 'failed_login_cron_schedule' === $field['id'] ) {
					if ( absint( $value ) > 10 ) {
						$value = 10;
					} elseif ( absint( $value ) < 1 ) {
						$value = 1;
					}
				}
				if ( isset( $field['id'] ) && 'failed_login_allow' === $field['id'] ) {
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

	/**
	 * Create admin menu link.
	 *
	 * @return void
	 */
	public function menu_page() {
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

	/**
	 * Create network admin menu link.
	 *
	 * @return void
	 */
	public function network_menu_page() {
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

	/**
	 * Add admin CSS.
	 *
	 * @return void
	 */
	public function c4wp_admin_page_enqueue_scripts() {
		$version = C4WP_PLUGIN_VERSION;
		wp_enqueue_style( 'c4wp-admin', C4WP_PLUGIN_URL . 'assets/css/admin.css', array(), $version );
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function settings_save() {
		if ( current_user_can( 'manage_options' ) && isset( $_POST['c4wp_admin_options'] ) && isset( $_POST['action'] ) && 'update' === $_POST['action'] && isset( $_GET['page'] ) && 'c4wp-admin-settings' === $_GET['page'] ||
		current_user_can( 'manage_options' ) && isset( $_POST['c4wp_admin_options'] ) && isset( $_POST['action'] ) && 'update' === $_POST['action'] && isset( $_GET['page'] ) && 'c4wp-admin-captcha' === $_GET['page'] ) {
			check_admin_referer( 'c4wp_admin_options-options' );

			$post_array = filter_input_array( INPUT_POST );
			$value      = isset( $post_array['c4wp_admin_options'] ) ? wp_unslash( $post_array['c4wp_admin_options'] ) : false;
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			$value = $this->validate_and_set_notices( $value );

			c4wp_update_option( $value );

			wp_safe_redirect( add_query_arg( 'updated', true ) );
			exit;
		}
	}

	/**
	 * Settings page markup.
	 *
	 * @return void
	 */
	public function admin_settings() {
		wp_enqueue_script( 'c4wp-admin' );
		wp_localize_script(
			'c4wp-admin',
			'anrScripts',
			array(
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'captcha_version'          => c4wp_get_option( 'captcha_version', 'v2_checkbox' ),
				'ipWarning'                => esc_html__( 'Please supply a valid IP', 'advanced-nocaptcha-recaptcha' ),
				'switchingWarning'         => esc_html__( 'To switch the reCAPTCHA method you need to replace the current Site and Secret keys. Do you want to proceed?', 'advanced-nocaptcha-recaptcha' ),
				'switchingWarningTitle'    => esc_html__( 'Confirm change of reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ),
				'removeConfigWarningTitle' => esc_html__( 'Confirm removal of reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ),
				'removeConfigWarning'      => esc_html__( 'This will remove the current reCAPTCHA integration, which means all the CAPTCHA checks on your website will stop working. Would you like to proceed?', 'advanced-nocaptcha-recaptcha' ),
			)
		);

		$current_tab = 'c4wp-admin-captcha';
		if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore
			$current_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore
		}

		// Determine if a Site/Secret key has been stored.
		$site_key               = trim( c4wp_get_option( 'site_key' ) );
		$secret_key             = trim( c4wp_get_option( 'secret_key' ) );
		$settings_url           = function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
		$settings_wrapper_class = ( empty( $site_key ) || empty( $secret_key ) ) ? 'captcha_keys_required wrap fs-section' : 'wrap fs-section';
		$show_wizard            = ( empty( c4wp_get_option( 'site_key' ) ) && empty( $site_key ) && empty( $secret_key ) ) ? 'show_wizard_on_load' : '';

		?>
		<div class="<?php echo esc_attr( $settings_wrapper_class ); ?> <?php echo esc_attr( $show_wizard ); ?>" id="c4wp-admin-wrap">
			<div id="captcha_keys_notice" class="notice notice-info" style="display: none">
				<p>
					<?php
						printf(
							'For the CAPTCHA check to work on the forms you have selected, configure the reCAPTCHA integration in the %1$s section. %2$s %3$s',
							'<strong>' . esc_html__( 'CAPTCHA Configuration', 'advanced-nocaptcha-recaptcha' ) . '</strong>',
							'</br></br><a href="' . esc_url( $settings_url ) . '" class="button button-primary">' . esc_html__( 'Configure it now', 'advanced-nocaptcha-recaptcha' ) . '</a>',
							'<a href="#" class="button button-secondary">' . esc_html__( 'I\'ll configure it later', 'advanced-nocaptcha-recaptcha' ) . '</a>'
						);
					?>
				</p>
			</div>
			<!-- Plugin settings go here -->
			<div id="c4wp-help">
				<h1>
				<?php
				if ( 'c4wp-admin-captcha' === $current_tab ) {
					esc_html_e( 'CAPTCHA integration & configuration', 'advanced-nocaptcha-recaptcha' );
				} elseif ( 'c4wp-admin-settings' === $current_tab ) {
					esc_html_e( 'CAPTCHA Placements', 'advanced-nocaptcha-recaptcha' );
				}
				?>
				</h1>
				<div id="post-body" class="metabox-holder columns-2 c4wp-settings">
					<div id="post-body-content">
						<div id="tab_container">
						<div class="overlay" id="overlay" hidden>
							<div class="confirm-box">
							<div onclick="closeConfirmBox()" class="close">&#10006;</div>
							<h2>Confirmation</h2>
							<p>Are you sure to execute this action?</p>
							<button onclick="isConfirm(true)">Yes</button>
							<button onclick="isConfirm(false)">No</button>
							</div>
						</div>
							<?php
							if ( 'c4wp-admin-captcha' === $current_tab || 'c4wp-admin-settings' === $current_tab ) {
								$this->settings_form();
							} elseif ( 'c4wp-admin-help' === $current_tab ) {
								$this->display_help_page();
							} elseif ( 'c4wp-admin-upgrade' === $current_tab ) {
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

	/**
	 * Setting form wrapper.
	 *
	 * @return void
	 */
	public function settings_form() {

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
	public function c4wp_settings_notice() {
		$errors = get_transient( 'c4wp_admin_options_errors' );
		$notice = '';
		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $error ) {
			if ( 'empty_site_key' === $error ) {
				$notice .= '<div class="notice notice-error"><p>' . esc_html__( 'The site key that you have entered is invalid. Please try again.', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
			if ( 'empty_secret_key' === $error ) {
				$notice .= '<div class="notice notice-error"><p>' . esc_html__( 'The secret key that you have entered is invalid. Please try again.', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
			if ( 'success' === $error ) {
				$context = esc_html__( 'CAPTCHA settings', 'advanced-nocaptcha-recaptcha' );
				if ( isset( $_REQUEST['page'] ) && 'c4wp-admin-captcha' === $_REQUEST['page'] ) { // phpcs:ignore
					$context = esc_html__( 'CAPTCHA configuration', 'advanced-nocaptcha-recaptcha' ); // phpcs:ignore
				}
				$notice .= '<div class="notice notice-success"><p>' . $context . esc_html__( ' updated', 'advanced-nocaptcha-recaptcha' ) . '</p></div>';
			}
		}

		delete_transient( 'c4wp_admin_options_errors' );

		echo wp_kses_post( $notice );
	}

	/**
	 * Create admin sidebar.
	 *
	 * @return string $return - HTNML markup.
	 */
	public function c4wp_admin_sidebar() {
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
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'One-click spam protection for forms built with Contact Form 7, Gravity Forms, WPForms & MailChimp for WordPress', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'CAPTCHA tests & spam protection for BuddyPress, bbPress & other third party plugins', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Add CAPTCHA to any type of form, even PHP forms', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Boost login security, add CAPTCHA tests only failed logins', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA for logged in users', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA for specific IP addresses', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'Remove CAPTCHA from specific URLs', 'advanced-nocaptcha-recaptcha' ) . '</li>
								<li class="dashicons-before dashicons-yes-alt"> ' . esc_html__( 'No Ads!', 'advanced-nocaptcha-recaptcha' ) . '</li>
							</ul>
							<p style="text-align: center; margin: auto"><a class="premium-link-not-btn" href="%1$s" target="_blank">' . esc_html__( 'Upgrade to Premium', 'advanced-nocaptcha-recaptcha' ) . '</a> <a class="premium-link" href="%2$s" target="_blank">' . esc_html__( 'Get a FREE 14-day trial', 'advanced-nocaptcha-recaptcha' ) . '</a></p>
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
	 * @param  array $value - Value to validate.
	 * @return array - Validated value.
	 *
	 * @since 7.0.0
	 */
	public function validate_and_set_notices( $value ) {
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

	/**
	 * Add link to settings to dashboard menu.
	 *
	 * @param array $links - Default links.
	 * @return array $links - Updated links.
	 */
	public function add_settings_link( $links ) {
		// add settings link in plugins page.
		$settings_link = '<a href="' . c4wp_settings_page_url() . '">' . esc_html__( 'Settings', 'advanced-nocaptcha-recaptcha' ) . '</a>';

		$append_link = ( is_multisite() && function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ) ? true : false;
		if ( $append_link || ! is_multisite() ) {
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * Includs help page content.
	 *
	 * @return void
	 */
	public function display_help_page() {
		require_once 'templates/help/index.php';
	}

	/**
	 * Includs upgrade page content.
	 *
	 * @return void
	 */
	public function display_upgrade_page() {
		require_once 'templates/upgrade/index.php';
	}

	/**
	 * Help function to push an item to an associate array.
	 *
	 * @param array  $array - Original array.
	 * @param string $key - new item key.
	 * @param string $new - New item svalue.
	 * @return array $array - Updated array.
	 */
	public static function push_at_to_associative_array( $array, $key, $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys, true );
		$pos   = false === $index ? count( $array ) : $index + 1;

		$array = array_slice( $array, 0, $pos, true ) + $new + array_slice( $array, $pos, count( $array ) - 1, true );
		return $array;
	}


	/**
	 * Ignore admin notice.
	 *
	 * @return void
	 */
	public static function c4wp_nocaptcha_plugin_notice_ignore() {
		// Grab POSTed data.
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$notice_type = filter_input( INPUT_POST, 'notice_type', FILTER_SANITIZE_STRING );;

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'dismiss_captcha_notice' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
		}

		if ( 'multisite' == $notice_type ) {
			global $current_user;
			$user_id = $current_user->ID;
			$updated = add_user_meta( $user_id, 'nocaptcha_plugin_notice_ignore', 'true', true );
	
			if ( function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ) {
				update_site_option( 'c4wp_network_notice_dismissed', true );
			} else {
				update_option( 'c4wp_network_notice_dismissed', true );
			}
			wp_send_json_success( $updated );
	
		} elseif ( 'v3_fallback' == $notice_type ) {
			if ( function_exists( 'c4wp_same_settings_for_all_sites' ) && c4wp_same_settings_for_all_sites() ) {
				update_site_option( 'c4wp_v3_failover_available', false );
			} else {
				update_option( 'c4wp_v3_failover_available', false );
			}
		}
		wp_send_json_success();

	}

	/**
	 * Handles markup of the popup settings wizard.
	 *
	 * @return $markup - HTML markup.
	 */
	public function wizard_markup() {
		$fields = $this->get_fields();

		// Add intro card if this is running in 'first time' mode.
		$show_wizard_intro = ( empty( c4wp_get_option( 'captcha_version' ) ) && empty( $site_key ) && empty( $secret_key ) ) ? true : false;
		$logo_url          = C4WP_PLUGIN_URL . 'assets/img/c4wp-logo-full.png';
		$intro_content     = '
		<div class="c4wp-wizard-panel" id="c4wp-setup-wizard-intro">
			<div class="c4wp-panel-content">
				<img src="' . $logo_url . '" class="wizard-logo"/>
				<strong>' . esc_html__( 'Getting started with the CAPTCHA 4WP plugin', 'advanced-nocaptcha-recaptcha' ) . '</strong>
				<p class="description c4wp-desc" style="position: absolute;">' . esc_html__( 'Thank you for installing the CAPTCHA 4WP plugin. This wizard will help you get started with the plugin so you can configure CAPTCHA and protect your website from spam, and fake registrations and orders.', 'advanced-nocaptcha-recaptcha' ) . '</p>				
			</div>
			<a data-wizard-goto href="#c4wp-setup-wizard-version-select" class="button button-primary">' . esc_html__( 'Next', 'advanced-nocaptcha-recaptcha' ) . '</a>
			<a href="#c4wp-cancel-wizard" class="button button-secondary">' . esc_html__( 'Cancel', 'advanced-nocaptcha-recaptcha' ) . '</a>
		</div>
		';
		$back_to_intro     = '<a data-wizard-goto href="#c4wp-setup-wizard-intro" class="button button-secondary">' . esc_html__( 'Back', 'advanced-nocaptcha-recaptcha' ) . '</a>';

		$markup = '
			<div id="c4wp-setup-wizard-content">
				<a href="#" id="c4wp-close-wizard"><span class="dashicons dashicons-no-alt"></span></a>
				' . $intro_content . '
				<div class="c4wp-wizard-panel" id="c4wp-setup-wizard-version-select">
					<div class="c4wp-panel-content">
						' . $fields['captcha_version_title']['label'] . '
						<p>' . $fields['captcha_version']['label'] . '</p>
						' . $this->callback( $fields['captcha_version'], true ) . '
					</div>
					<a data-wizard-goto href="#c4wp-setup-wizard-site-keys" class="button button-primary">' . esc_html__( 'Next', 'advanced-nocaptcha-recaptcha' ) . '</a>
					<a href="#c4wp-cancel-wizard" class="button button-secondary">' . esc_html__( 'Cancel', 'advanced-nocaptcha-recaptcha' ) . '</a>
				</div>
				<div class="c4wp-wizard-panel" id="c4wp-setup-wizard-site-keys">
					<div class="c4wp-panel-content">
						' . $fields['site_key_title']['label'] . '
						' . $fields['site_key_subtitle']['label'] . '
						<p>' . $fields['site_key']['label'] . '
						' . $this->callback( $fields['site_key'], true ) . '</p>
						<p>' . $fields['secret_key']['label'] . '
						' . $this->callback( $fields['secret_key'], true ) . '</p>
						<p>' . $this->callback( $fields['key_validation'], true ) . '</p>
					</div>
					<a data-wizard-goto href="#c4wp-setup-wizard-additional-settings" class="button button-primary" data-check-inputs="#c4wp_admin_options_site_key, #c4wp_admin_options_secret_key">' . esc_html__( 'Next', 'advanced-nocaptcha-recaptcha' ) . '</a>
					<a data-wizard-goto href="#c4wp-setup-wizard-version-select" class="button button-secondary">' . esc_html__( 'Back', 'advanced-nocaptcha-recaptcha' ) . '</a>
					<a href="#c4wp-cancel-wizard" class="button button-secondary">' . esc_html__( 'Cancel', 'advanced-nocaptcha-recaptcha' ) . '</a>
				</div>
				<div class="c4wp-wizard-panel" id="c4wp-setup-wizard-v3-fallback">
					<div class="c4wp-panel-content">
						<strong>' . esc_html__( 'Step 3: Configure a failover action for reCAPTCHA v3 failure', 'advanced-nocaptcha-recaptcha' ) . '</strong>
						<p class="description c4wp-desc" style="position: absolute;">' . esc_html__( 'reCAPTCHA v3 is fully automated. This means that by default, if the CAPTCHA check fails the website visitor cannot proceed with what they are doing unless you configure a failover action. Use the below setting to configure the failover action.', 'advanced-nocaptcha-recaptcha' ) . '</p>
						<p>' . $fields['failure_action']['label'] . '
						' . $this->callback( $fields['failure_action'], true ) . '</p>	
						<p class="toggletext hidden disabled c4wp-show-field-for-redirect">' . esc_html__( 'Please specify the full URL, including the protocol (HTTP or HTTPS) where you would like the user to be redirected to. For example: ', 'advanced-nocaptcha-recaptcha' ) . '<i>https://melapress.com/blog/</i></p>
						<p class="toggletext hidden disabled c4wp-show-field-for-v2_checkbox">' . esc_html__( 'To show the v2 reCAPTCHA checkbox you need to specify the Site and Secret keys. Please specify them below:', 'advanced-nocaptcha-recaptcha' ) . '</p>
						<p>' . $fields['failure_redirect']['label'] . '
						' . $this->callback( $fields['failure_redirect'], true ) . '</p>			
						<p>' . $fields['failure_v2_site_key']['label'] . '
						' . $this->callback( $fields['failure_v2_site_key'], true ) . '</p>	
						<p>' . $fields['failure_v2_secret_key']['label'] . '
						' . $this->callback( $fields['failure_v2_secret_key'], true ) . '</p>	
						<p>' . $this->callback( $fields['failure_key_validation'], true ) . '</p>	
					</div>
					<a data-wizard-goto href="#c4wp-setup-wizard-additional-settings" class="button button-primary" data-check-inputs="#c4wp_admin_options_failure_v2_site_key, #c4wp_admin_options_failure_v2_secret_key">' . esc_html__( 'Next', 'advanced-nocaptcha-recaptcha' ) . '</a>
					<a data-wizard-goto href="#c4wp-setup-wizard-site-keys" class="button button-secondary">' . esc_html__( 'Back', 'advanced-nocaptcha-recaptcha' ) . '</a>
					<a href="#c4wp-cancel-wizard" class="button button-secondary">' . esc_html__( 'Cancel', 'advanced-nocaptcha-recaptcha' ) . '</a>
				</div>
				<div class="c4wp-wizard-panel" id="c4wp-setup-wizard-additional-settings">
					<div class="c4wp-panel-content">
						<strong>' . esc_html__( ' All done - you can now add CAPTCHA checks to your website', 'advanced-nocaptcha-recaptcha' ) . '</strong>
						<p class="description c4wp-desc" style="position: absolute;">' . esc_html__( "Now that the Google reCAPTCHA service is fully integrated you can use the optional settings to fine-tune CAPTCHA to your requirements. All the CAPTCHA settings are optional and with them you can configure aspects such as look and feel and CAPTCHA sensitivity. When you are ready navigate to the Settings & Placements page to configure where you'd like to add the CAPTCHA checks", 'advanced-nocaptcha-recaptcha' ) . '</p>				
					</div>
					<a href="#finish" class="button button-primary">' . esc_html__( 'Finish', 'advanced-nocaptcha-recaptcha' ) . '</a>
				</div>
			</div>
		';
		return $markup;
	}

	/**
	 * Handles markup for showing the 'launch wizard' buttons as well as current plugin config.
	 *
	 * @return $markup - HTML Markup
	 */
	public function wizard_launcher_area() {

		$site_key        = trim( c4wp_get_option( 'site_key' ) );
		$secret_key      = trim( c4wp_get_option( 'secret_key' ) );
		$captcha_version = trim( c4wp_get_option( 'captcha_version' ) );
		$failure_action  = trim( c4wp_get_option( 'failure_action' ) );
		$reset_nonce     = wp_create_nonce( 'reset_captcha_nonce' );

		if ( $site_key && $secret_key ) {
			$markup = '
				<br><a href="#" id="launch-c4wp-wizard" class="button button-primary">' . esc_html__( 'Reconfigure reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ) . '</a> <a href="#" id="reset-c4wp-config" class="button button-secondary" data-nonce="' . esc_attr( $reset_nonce ) . '">' . esc_html__( 'Remove reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ) . '</a>
			';

			$markup                 .= '
			<table class="form-table" role="presentation">
				<tbody>
					<tr class="regular">
						<th scope="row">Current reCAPTCHA configuration:</th>
						<td>';
							$markup .= '<div class="c4wp-current-setup">';
							$markup .= '<p><span>' . esc_html__( 'CAPTCHA version:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . $captcha_version . '</strong></p>';
							$markup .= '<p><span>' . esc_html__( 'Site key:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . $site_key . '</strong></p>';
							$markup .= '<p><span>' . esc_html__( 'Secret key:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . $secret_key . '</strong></p>';
							if ( 'v3' === $captcha_version ) {
								if ( 'v2_checkbox' === $failure_action ) {
									$markup .= '<p><span>' . esc_html__( 'Failover action:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . esc_html__( 'v2 checkbox', 'advanced-nocaptcha-recaptcha' ) . '</strong></p>';
									$markup .= '<p><span>' . esc_html__( 'Site key:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . esc_html__( trim( c4wp_get_option( 'failure_v2_site_key' ) ) ) . '</strong></p>';
									$markup .= '<p><span>' . esc_html__( 'Secret key:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . esc_html__( trim( c4wp_get_option( 'failure_v2_secret_key' ) ) ) . '</strong></p>';
								} elseif ( 'redirect' === $failure_action ) {
									$markup .= '<p><span>' . esc_html__( 'Failover action:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . esc_html__( 'Redirect to a URL', 'advanced-nocaptcha-recaptcha' ) . '</strong></p>';
									$markup .= '<p><span>' . esc_html__( 'Failover redirect URL:', 'advanced-nocaptcha-recaptcha' ) . '</span><strong>' . esc_html__( trim( c4wp_get_option( 'failure_redirect' ) ) ) . '</strong></p>';
								}
							}
							$markup .= '</div>';
							$markup .= '
						</td>
					</tr>
				</tbody>
			</table>
			';
		} else {
			$markup = '
				<br><a href="#" id="launch-c4wp-wizard" class="button button-primary">' . esc_html__( 'Configure Google reCAPTCHA integration', 'advanced-nocaptcha-recaptcha' ) . '</a>
			';
		}

		return $markup;
	}

	/**
	 * Reset current version and keys.
	 *
	 * @return void
	 */
	public function c4wp_reset_captcha_config() {
		// Grab POSTed data.
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'reset_captcha_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'advanced-nocaptcha-recaptcha' ) );
		}

		c4wp_update_option( 'site_key' );
		c4wp_update_option( 'secret_key' );
		c4wp_update_option( 'captcha_version' );

		wp_send_json_success();
	}

	/**
	 * Adds a setting to allow for testing different CAPTCHA results.
	 *
	 * @param  array $fields - Current settings array.
	 * @return array $fields - Modified array.
	 */
	public function add_logging_and_testing_settings( $fields ) {

		$stored = c4wp_get_option( 'c4wp_recent_results' );

		$nice_display = '';

		$additonal_options['override_result']             = array(
			'label'      => esc_html__( 'Override validation response.', 'advanced-nocaptcha-recaptcha' ),
			'section_id' => 'google_keys',
			'type'       => 'select',
			'class'      => 'regular',
			'std'        => 'no_override',
			'options'    => array(
				'no_override'  => esc_html__( 'Do no override', 'advanced-nocaptcha-recaptcha' ),
				'return_false' => esc_html__( 'Return false (failure)', 'advanced-nocaptcha-recaptcha' ),
				'return_true'  => esc_html__( 'Return true (pass)', 'advanced-nocaptcha-recaptcha' ),
			),
			'desc'       => '',
		);
		$additonal_options['recent_verification_results'] = array(
			'section_id' => 'google_keys',
			'type'       => 'html',
			'label'      => sprintf(
				'<p class="description c4wp-desc" style="position: absolute;"><p>'
			),
			'class'      => 'wrap-around-content c4wp-wizard-captcha-version',
		);

		$fields = self::push_at_to_associative_array( $fields, array_key_last( $fields ), $additonal_options );

		return $fields;
	}

} //END CLASS


add_action( 'wp_loaded', array( C4WP_Settings::init(), 'actions_filters' ) );
