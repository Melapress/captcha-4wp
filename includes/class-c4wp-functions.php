<?php
/**
 * Util functions.
 *
 * @package C4WP
 */

namespace C4WP;

if ( ! class_exists( 'C4WP_Functions' ) ) {

	/**
	 * Main class.
	 */
	class C4WP_Functions {

		/**
		 * Class constructor.
		 */
		private function __construct() {
			// Silence.
		}

		/**
		 * Adding required actions.
		 *
		 * @return void
		 */
		public static function actions() {

			add_filter( 'shake_error_codes', array( __CLASS__, 'c4wp_add_shake_error_codes' ) );
		}

		/**
		 * Check if update scripts need to run.
		 *
		 * @return void
		 */
		public static function c4wp_plugin_update() {
			$prev_version = self::c4wp_get_option( 'version', '3.1' );

			if ( version_compare( $prev_version, C4WP_PLUGIN_VERSION, '!=' ) ) {
				self::do_plugin_updpate( $prev_version );
				self::c4wp_update_option( 'version', C4WP_PLUGIN_VERSION );
			}
		}

		public static function do_plugin_updpate( $prev_version  ) {
			// 3.2.
			if ( version_compare( $prev_version, '3.2', '<' ) ) {
				if ( method_exists( __CLASS__, 'c4wp_same_settings_for_all_sites' ) && self::c4wp_same_settings_for_all_sites() ) {
					$options = get_site_option( 'c4wp_admin_options' );
				} else {
					$options = get_option( 'c4wp_admin_options' );
				}
				if ( ! $options || ! is_array( $options ) ) {
					return;
				}
				$options['error_message'] = str_replace( esc_html__( '<strong>ERROR</strong>: ', 'advanced-nocaptcha-recaptcha' ), '', self::c4wp_get_option( 'error_message' ) );

				$enabled_forms = array();
				if ( ! empty( $options['login'] ) ) {
					$enabled_forms[] = 'login';
				}
				if ( ! empty( $options['registration'] ) ) {
					$enabled_forms[] = 'registration';
				}
				if ( ! empty( $options['ms_user_signup'] ) ) {
					$enabled_forms[] = 'ms_user_signup';
				}
				if ( ! empty( $options['lost_password'] ) ) {
					$enabled_forms[] = 'lost_password';
				}
				if ( ! empty( $options['reset_password'] ) ) {
					$enabled_forms[] = 'reset_password';
				}
				if ( ! empty( $options['comment'] ) ) {
					$enabled_forms[] = 'comment';
				}
				if ( ! empty( $options['bb_new'] ) ) {
					$enabled_forms[] = 'bbp_new';
				}
				if ( ! empty( $options['bb_reply'] ) ) {
					$enabled_forms[] = 'bbp_reply';
				}
				if ( ! empty( $options['wc_checkout'] ) ) {
					$enabled_forms[] = 'wc_checkout';
				}
				$options['enabled_forms'] = $enabled_forms;

				unset( $options['login'], $options['registration'], $options['ms_user_signup'], $options['lost_password'], $options['reset_password'], $options['comment'], $options['bb_new'], $options['bb_reply'], $options['wc_checkout'] );

				self::c4wp_update_option( $options );
			}

			if ( version_compare( $prev_version, '5.1', '<' ) ) {
				$options = array();
				if ( 'invisible' === self::c4wp_get_option( 'size' ) ) {
					$options['size']            = 'normal';
					$options['captcha_version'] = 'v2_invisible';
				}

				self::c4wp_update_option( $options );
			}

			if ( version_compare( $prev_version, '7.0.6.1', '<' ) ) {
				if ( is_multisite() ) {
					if ( ! get_site_option( 'c4wp_70_upgrade_complete' ) ) {
						$original_options = get_site_option( 'anr_admin_options' );
						update_site_option( 'c4wp_admin_options', $original_options );
						update_site_option( 'c4wp_70_upgrade_complete', true );
						delete_site_option( 'anr_admin_options' );
					}
				} else {
					if ( ! get_option( 'c4wp_70_upgrade_complete' ) ) {
						$original_options = get_option( 'anr_admin_options' );
						update_option( 'c4wp_admin_options', $original_options );
						update_option( 'c4wp_70_upgrade_complete', true );
						delete_option( 'anr_admin_options' );
					}
				}
				global $wpdb;
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = %s", array( 'anr-post' ) ) );
				$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
			}

			$current_lang = self::c4wp_get_option( 'language' );
			if ( version_compare( $prev_version, '7.0.6.1', '<' ) && empty( $current_lang ) ) {
				self::c4wp_update_option( 'language', 'en' );
			}

			$captcha_version = self::c4wp_get_option( 'captcha_version' );
			if ( version_compare( $prev_version, '7.2.1', '<' ) && 'v3' == $captcha_version ) {
				add_option( 'c4wp_v3_failover_available', true );
			}

			if ( version_compare( $prev_version, '7.3.1', '<' ) ) {
				delete_transient( 'c4wp_config_file_hash' );
			}
		}

		/**
		 * Handle getting options for our plugin.
		 *
		 * @param string $option - Name of option to update.
		 * @param string $default - Default value.
		 * @param string $section - Section which handles the option.
		 *
		 * @return bool:string - Option value.
		 */
		public static function c4wp_get_option( $option, $default = '', $section = 'c4wp_admin_options' ) {

			$get_site_options = is_multisite();

			if ( $get_site_options ) {
				$options = get_site_option( $section );
			} else {
				$options = get_option( $section );
			}

			if ( isset( $options[ $option ] ) ) {
				$value      = $options[ $option ];
				$is_default = false;
			} else {
				$value      = $default;
				$is_default = true;
			}
			return apply_filters( 'c4wp_get_option', $value, $option, $default, $is_default );
		}

		/**
		 * Handle updating option for our plugin.
		 *
		 * @param string $options - Name of option to update.
		 * @param string $value - New value.
		 * @param string $section - Section which handles the option.
		 * @return bool - Was option updated.
		 */
		public static function c4wp_update_option( $options, $value = '', $section = 'c4wp_admin_options' ) {

			if ( $options && ! is_array( $options ) ) {
				$options = array(
					$options => $value,
				);
			}
			if ( ! is_array( $options ) ) {
				return false;
			}

			$update_site_options = is_multisite();

			if ( $update_site_options ) {
				update_site_option( $section, wp_parse_args( $options, get_site_option( $section ) ) );
			} else {
				update_option( $section, wp_parse_args( $options, get_option( $section ) ) );
			}

			return true;
		}

		/**
		 * Undocumented function
		 *
		 * @param string $form - Form name.
		 * @return bool - Is enabled?
		 */
		public static function c4wp_is_form_enabled( $form ) {
			if ( ! $form ) {
				return false;
			}
			$enabled_forms = array_merge( self::c4wp_get_option( 'enabled_forms', array() ), self::c4wp_get_option( 'enabled_forms_wc', array() ), self::c4wp_get_option( 'enabled_forms_bp', array() ), self::c4wp_get_option( 'enabled_forms_bbp', array() ) );

			if ( ! is_array( $enabled_forms ) ) {
				return false;
			}
			return in_array( $form, $enabled_forms, true );
		}

		/**
		 * Add transation file.
		 *
		 * @return void
		 */
		public static function c4wp_translation() {
			// SETUP TEXT DOMAIN FOR TRANSLATIONS.
			load_plugin_textdomain( 'advanced-nocaptcha-recaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public static function c4wp_login_enqueue_scripts() {

			if ( ! self::c4wp_get_option( 'remove_css' ) && 'normal' === self::c4wp_get_option( 'size', 'normal' ) ) {
				$verion = C4WP_PLUGIN_VERSION;
				wp_enqueue_style( 'c4wp-login-style', C4WP_PLUGIN_URL . 'assets/css/style.css', C4WP_PLUGIN_VERSION, $verion );
			}
		}

		/**
		 * Add our foot scripts.
		 *
		 * @return void
		 */
		public function c4wp_wp_footer() {
			C4WP_Captcha_Class::footer_script();
		}

		/**
		 * Create a captcha field.
		 *
		 * @param boolean $echo - Should echo or return.
		 * @return string - HTML Markup.
		 */
		public static function c4wp_captcha_form_field( $echo = false ) {
			if ( $echo ) {
				C4WP_Captcha_Class::form_field();
			} else {
				return C4WP_Captcha_Class::form_field_return();
			}

		}

		/**
		 * Verify a captcha response (old version of plugin).
		 *
		 * @param boolean $response - Response to check.
		 * @return bool - Verification.
		 */
		public function anr_verify_captcha( $response = false ) {
			return C4WP_Captcha_Class::init()->verify( $response );
		}

		/**
		 * Verify a captcha response.
		 *
		 * @param boolean $response - Response to check.
		 * @return bool - Verification.
		 */
		public static function c4wp_verify_captcha( $response = false ) {
			return C4WP_Captcha_Class::init()->verify( $response );
		}

		/**
		 * Add shake script to error screen.
		 *
		 * @param array $shake_error_codes - Current error codes.
		 * @return array - Codes, with ours appended.
		 */
		public static function c4wp_add_shake_error_codes( $shake_error_codes ) {
			$shake_error_codes[] = 'c4wp_error';

			return $shake_error_codes;
		}

		/**
		 * Create URL for our contact page.
		 *
		 * @param string $wp_org_support_forum_url - Original URL.
		 * @return string - Our URL.
		 */
		public static function c4wp_fs_support_forum_url( $wp_org_support_forum_url ) {
			return 'https://melapress.com/contact/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=c4wp';
		}

		/**
		 * Create correct captcha domain URL.
		 *
		 * @return string - URL.
		 */
		public static function c4wp_recaptcha_domain() {
			$domain = self::c4wp_get_option( 'recaptcha_domain', 'google.com' );
			return apply_filters( 'c4wp_recaptcha_domain', $domain );
		}


		/**
		 * Setup settings page URL.
		 *
		 * @param boolean $tab - Is tab settings.
		 * @return string - URL.
		 */
		public static function c4wp_settings_page_url( $tab = false ) {
			$url = ( method_exists( __CLASS__, 'c4wp_same_settings_for_all_sites' ) && self::c4wp_same_settings_for_all_sites() || ! method_exists( __CLASS__, 'c4wp_same_settings_for_all_sites' ) ) ? network_admin_url( 'admin.php?page=c4wp-admin-captcha' ) : admin_url( 'admin.php?page=c4wp-admin-captcha' );
			return $url;
		}

		/**
		 * Hode freemius contact link.
		 *
		 * @param bool $is_visible - Is currently visible.
		 * @param int  $submenu_id - Item ID.
		 * @return bool - Is isible.
		 */
		public static function hide_freemius_submenu_items( $is_visible, $submenu_id ) {
			if ( 'contact' === $submenu_id ) {
				$is_visible = false;
			}
			return $is_visible;
		}

		/**
		 * Create system info for debugging.
		 *
		 * @return string - File markup.
		 */
		public static function c4wp_get_sysinfo() {
			// System info.
			global $wpdb;

			$sysinfo = '### System Info → Begin ###' . "\n\n";

			// Start with the basics...
			$sysinfo .= '-- Site Info --' . "\n\n";
			$sysinfo .= 'Site URL (WP Address):    ' . site_url() . "\n";
			$sysinfo .= 'Home URL (Site Address):  ' . home_url() . "\n";
			$sysinfo .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

			// Get theme info.
			$theme_data   = wp_get_theme();
			$theme        = $theme_data->Name . ' ' . $theme_data->Version; // phpcs:ignore.
			$parent_theme = $theme_data->Template; // phpcs:ignore.
			if ( ! empty( $parent_theme ) ) {
				$parent_theme_data = wp_get_theme( $parent_theme );
				$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version; // phpcs:ignore.
			}

			// Language information.
			$locale = get_locale();

			// WordPress configuration.
			$sysinfo .= "\n" . '-- WordPress Configuration --' . "\n\n";
			$sysinfo .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
			$sysinfo .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
			$sysinfo .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
			$sysinfo .= 'Active Theme:             ' . $theme . "\n";
			if ( $parent_theme !== $theme ) {
				$sysinfo .= 'Parent Theme:             ' . $parent_theme . "\n";
			}
			$sysinfo .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

			// Only show page specs if frontpage is set to 'page'.
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$front_page_id = (int) get_option( 'page_on_front' );
				$blog_page_id  = (int) get_option( 'page_for_posts' );

				$sysinfo .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
				$sysinfo .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
			}

			$sysinfo .= 'ABSPATH:                  ' . ABSPATH . "\n";
			$sysinfo .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$sysinfo .= 'WP Memory Limit:          ' . WP_MEMORY_LIMIT . "\n";

			// Get plugins that have an update.
			$updates = get_plugin_updates();

			// Must-use plugins.
			// NOTE: MU plugins can't show updates!
			$muplugins = get_mu_plugins();
			if ( count( $muplugins ) > 0 ) {
				$sysinfo .= "\n" . '-- Must-Use Plugins --' . "\n\n";

				foreach ( $muplugins as $plugin => $plugin_data ) {
					$sysinfo .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
				}
			}

			// WordPress active plugins.
			$sysinfo .= "\n" . '-- WordPress Active Plugins --' . "\n\n";

			$plugins        = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			// WordPress inactive plugins.
			$sysinfo .= "\n" . '-- WordPress Inactive Plugins --' . "\n\n";

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array( $plugin_path, $active_plugins, true ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			if ( is_multisite() ) {
				// WordPress Multisite active plugins.
				$sysinfo .= "\n" . '-- Network Active Plugins --' . "\n\n";

				$plugins        = wp_get_active_network_plugins();
				$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

				foreach ( $plugins as $plugin_path ) {
					$plugin_base = plugin_basename( $plugin_path );

					if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
						continue;
					}

					$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
					$plugin   = get_plugin_data( $plugin_path );
					$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
				}
			}

			// Server configuration.
			$posted          = filter_input_array( INPUT_SERVER );
			$server_software = sanitize_text_field( $posted[ 'SERVER_SOFTWARE' ] );
			$sysinfo        .= "\n" . '-- Webserver Configuration --' . "\n\n";
			$sysinfo        .= 'PHP Version:              ' . PHP_VERSION . "\n";
			$sysinfo        .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";

			if ( isset( $server_software ) ) {
				$sysinfo .= 'Webserver Info:           ' . $server_software . "\n";
			} else {
				$sysinfo .= 'Webserver Info:           Global $_SERVER array is not set.' . "\n";
			}

			// PHP configs.
			$sysinfo .= "\n" . '-- PHP Configuration --' . "\n\n";
			$sysinfo .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
			$sysinfo .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
			$sysinfo .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
			$sysinfo .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
			$sysinfo .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

			$sysinfo .= "\n" . '-- Captcha4WP Settings  --' . "\n\n";

			$c4wp_options = get_option( 'c4wp_admin_options' );

			if ( ! empty( $c4wp_options ) ) {
				foreach ( $c4wp_options as $option => $value ) {
					$sysinfo .= 'Option: ' . $option . "\n";
					$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n";
				}
			}

			$sysinfo .= "\n" . '### System Info → End ###' . "\n\n";

			return $sysinfo;
		}

		/**
		 * Determines if an install is premium/paying.
		 *
		 * @return bool - Is premium or not.
		 */
		public static function c4wp_is_premium_version() {
			return ( ( class_exists( 'C4WP_Pro' ) && ! c4wp_fs()->is_not_paying() ) || ( class_exists( 'C4WP_Pro' ) && c4wp_fs()->is_trial() ) ) ? true : false;
		}

		/**
		 * Add a small log during testing.
		 *
		 * @param array $result - Result data.
		 * @return void
		 */
		public static function c4wp_log_verify_result( $result ) {
			$stored = self::c4wp_get_option( 'c4wp_recent_results' );
			if ( ! $stored || ! is_array( $stored ) ) {
				$updated_results[] = $result;
				self::c4wp_update_option( 'c4wp_recent_results', $updated_results );
			} else {
				$updated_results = array_unshift( $stored, $result );
				self::c4wp_update_option( 'c4wp_recent_results', $updated_results );
			}
		}

		/**
		 * An easy to use array of allowed HTML for use with sanitzation of our admin areas etc.
		 *
		 * @return $wp_kses_args - Our array.
		 */
		public static function c4wp_allowed_kses_args() {
			$wp_kses_args = array(
				'input'    => array(
					'type'     => array(),
					'id'       => array(),
					'name'     => array(),
					'value'    => array(),
					'size'     => array(),
					'class'    => array(),
					'min'      => array(),
					'required' => array(),
					'checked'  => array(),
				),
				'select'   => array(
					'id'   => array(),
					'name' => array(),
				),
				'option'   => array(
					'id'       => array(),
					'name'     => array(),
					'value'    => array(),
					'selected' => array(),
				),
				'tr'       => array(
					'valign' => array(),
					'class'  => array(),
					'id'     => array(),
				),
				'th'       => array(
					'scope' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'td'       => array(
					'class' => array(),
					'id'    => array(),
				),
				'fieldset' => array(
					'class' => array(),
					'id'    => array(),
				),
				'legend'   => array(
					'class' => array(),
					'id'    => array(),
				),
				'label'    => array(
					'for'   => array(),
					'class' => array(),
					'id'    => array(),
				),
				'p'        => array(
					'class' => array(),
					'id'    => array(),
				),
				'span'     => array(
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'li'       => array(
					'class'         => array(),
					'id'            => array(),
					'data-role-key' => array(),
				),
				'a'        => array(
					'class'             => array(),
					'id'                => array(),
					'style'             => array(),
					'data-tab-target'   => array(),
					'data-wizard-goto'  => array(),
					'data-check-inputs' => array(),
					'data-nonce'        => array(),
					'href'              => array(),
					'target'            => array(),
				),
				'h3'       => array(
					'class' => array(),
				),
				'br'       => array(),
				'b'        => array(),
				'i'        => array(),
				'div'      => array(
					'style' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'table'    => array(
					'class' => array(),
					'id'    => array(),
				),
				'tbody'    => array(
					'class' => array(),
					'id'    => array(),
				),
				'strong'   => array(
					'class'            => array(),
					'data-key-invalid' => array(),
					'id'               => array(),
				),
				'img'      => array(
					'class' => array(),
					'src'   => array(),
					'id'    => array(),
				),
			);
			return $wp_kses_args;
		}

	}
}
