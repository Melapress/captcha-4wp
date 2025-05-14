<?php
/**
 * Util functions.
 *
 * @package C4WP
 * @since 7.6.0
 */

declare(strict_types=1);

namespace C4WP;

if ( ! class_exists( '\C4WP\C4WP_Functions' ) ) {

	/**
	 * Main class.
	 *
	 * @since 7.6.0
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
		 *
		 * @since 7.6.0
		 */
		public static function actions() {

			add_filter( 'shake_error_codes', array( __CLASS__, 'c4wp_add_shake_error_codes' ) );
		}

		/**
		 * Check if update scripts need to run.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_plugin_update() {
			$prev_version = self::c4wp_get_option( 'version', '3.1' );

			if ( version_compare( $prev_version, C4WP_VERSION, '!=' ) ) {
				self::do_plugin_update( $prev_version );
				if ( method_exists( __CLASS__, 'c4wp_same_settings_for_all_sites' ) && self::c4wp_same_settings_for_all_sites() ) {
					$options = get_site_option( 'c4wp_admin_options' );
				} else {
					$options = get_option( 'c4wp_admin_options' );
				}
				if ( ! empty( $options ) ) {
					update_site_option( 'c4wp_update_redirection_needed', true );
				}
				self::c4wp_update_option( 'version', C4WP_VERSION );
			}
		}

		/**
		 * Handle plugin updates.
		 *
		 * @param string $prev_version - Old outgoing version number.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function do_plugin_update( $prev_version ) {
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
				} elseif ( ! get_option( 'c4wp_70_upgrade_complete' ) ) {
						$original_options = get_option( 'anr_admin_options' );
						update_option( 'c4wp_admin_options', $original_options );
						update_option( 'c4wp_70_upgrade_complete', true );
						delete_option( 'anr_admin_options' );
				}
				global $wpdb;
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = %s", array( 'anr-post' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching 
				$wpdb->query( $wpdb->prepare( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS %s", null ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching 
			}

			/* @free:start */
			$current_lang = self::c4wp_get_option( 'language' );
			if ( version_compare( $prev_version, '7.0.6.1', '<' ) && empty( $current_lang ) ) {
				self::c4wp_update_option( 'language', 'en' );
			}
			/* @free:end */

			$captcha_version = self::c4wp_get_option( 'captcha_version' );
			if ( version_compare( $prev_version, '7.2.1', '<' ) && 'v3' === $captcha_version ) {
				add_option( 'c4wp_v3_failover_available', true );
			}

			if ( version_compare( $prev_version, '7.5.1', '<' ) ) {
				delete_transient( 'c4wp_config_file_hash' );
			}

			$current_load = self::c4wp_get_option( 'inline_or_file' );
			if ( version_compare( $prev_version, '7.6.0.0', '<' ) && empty( $current_load ) ) {
				self::c4wp_update_option( 'inline_or_file', 'inline' );
			} else {
				self::c4wp_update_option( 'inline_or_file', 'file' );
			}
		}

		/**
		 * Simple function to return site key.
		 *
		 * @return string - Site key.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_get_site_key() {
			/* @dev:start */
			$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );
			if ( $dev_mode ) {
				// phpcs:disable
				if ( isset( $_GET['override-captcha-version'] ) ) {
					$override = trim( sanitize_key( wp_unslash( $_GET['override-captcha-version'] ) ) );
					if ( 'v2_checkbox' === $override ) {
						return self::c4wp_get_option( 'testing_v2_checkbox_key' );
					} elseif ( 'v2_invisible' === $override ) {
						return self::c4wp_get_option( 'testing_v2_invisible_key' );
					} elseif ( 'v3' === $override ) {
						return self::c4wp_get_option( 'testing_v3_key' );
					} elseif ( 'hcaptcha' === $override ) {
						return self::c4wp_get_option( 'testing_hcaptcha_key' );
					} elseif ( 'cloudflare' === $override ) {
						return self::c4wp_get_option( 'testing_cloudflare_key' );
					}
				}
				// phpcs:enable
			}
			/* @dev:end */
			return self::c4wp_get_option( 'site_key' );
		}

		/**
		 * Simple function to return secret key.
		 *
		 * @return string - Secret key.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_get_secret_key() {
			/* @dev:start */
			$dev_mode = apply_filters( 'c4wp_enable_dev_mode', false );
			if ( $dev_mode ) {
				// phpcs:disable
				if ( isset( $_GET['override-captcha-version'] ) ) {
					$override = trim( sanitize_key( wp_unslash( $_GET['override-captcha-version'] ) ) );
					if ( 'v2_checkbox' === $override ) {
						return self::c4wp_get_option( 'testing_v2_checkbox_secret' );
					} elseif ( 'v2_invisible' === $override ) {
						return self::c4wp_get_option( 'testing_v2_invisible_secret' );
					} elseif ( 'v3' === $override ) {
						return self::c4wp_get_option( 'testing_v3_secret' );
					} elseif ( 'hcaptcha' === $override ) {
						return self::c4wp_get_option( 'testing_hcaptcha_secret' );
					} elseif ( 'cloudflare' === $override ) {
						return self::c4wp_get_option( 'testing_cloudflare_secret' );
					}
				}
				// phpcs:enable
			}
			/* @dev:end */
			return self::c4wp_get_option( 'secret_key' );
		}

		/**
		 * Handle getting options for our plugin.
		 *
		 * @param string $option - Name of option to update.
		 * @param string $default_value - Default value.
		 * @param string $section - Section which handles the option.
		 *
		 * @return bool|string|array - Option value.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_get_option( $option, $default_value = '', $section = 'c4wp_admin_options' ) {

			$get_site_options = is_multisite();

			if ( $get_site_options ) {
				$options = get_site_option( $section );
			} else {
				$options = get_option( $section );
			}

			if ( isset( $options[ $option ] ) ) {
				$value      = $options[ $option ];
				$is_default = false;
			} elseif ( 'all' === $option ) {
				return $options;
			} else {
				$value      = $default_value;
				$is_default = true;
			}
			return apply_filters( 'c4wp_get_option', $value, $option, $default_value, $is_default );
		}

		/**
		 * Handle updating option for our plugin.
		 *
		 * @param string $options - Name of option to update.
		 * @param string $value - New value.
		 * @param string $section - Section which handles the option.
		 *
		 * @return bool - Was option updated.
		 *
		 * @since 7.6.0
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
		 * Check if form is enabled or not.
		 *
		 * @param string $form - Form name.
		 *
		 * @return bool - Is enabled?
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_is_form_enabled( $form ) {
			if ( ! $form ) {
				return false;
			}

			$all = self::c4wp_get_option( 'all' );

			if ( ! is_array( $all ) ) {
				return false;
			}

			$result = array_filter(
				$all,
				function ( $key ) {
					if ( 'enabled_forms_title' === $key || 'enabled_forms_subtitle' === $key ) {
						return false;
					} else {
						return ( strpos( $key, 'enabled_forms' ) === 0 || strpos( $key, 'blocked_countries' ) === 0 );
					}
				},
				ARRAY_FILTER_USE_KEY
			);

			$enabled_forms = array();
			foreach ( $result as $k => $v ) {
				if ( is_array( $v ) && ! empty( $v ) ) {
					foreach ( $v as $item ) {
						if ( strpos( $k, 'blocked_countries' ) === 0 ) {
							array_push( $enabled_forms, 'blocked_countries_' . $item );
						} else {
							array_push( $enabled_forms, $item );
						}
					}
				} elseif ( is_int( $v ) && 1 === $v ) {
					array_push( $enabled_forms, str_replace( '_enabled_forms', '', $k ) );
				}
			}

			$enabled_forms = apply_filters( 'c4wp_add_to_enabled_forms_array', $enabled_forms );

			if ( ! is_array( $enabled_forms ) ) {
				return false;
			}
			return in_array( $form, $enabled_forms, true );
		}

		/**
		 * Add translation file.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_translation() {
			// SETUP TEXT DOMAIN FOR TRANSLATIONS.
			load_plugin_textdomain( 'advanced-nocaptcha-recaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Added Login css.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_login_enqueue_scripts() {
			if ( ! self::c4wp_get_option( 'remove_css' ) && 'normal' === self::c4wp_get_option( 'size', 'normal' ) && ( 'v2_checkbox' === self::c4wp_get_option( 'captcha_version' ) || 'hcaptcha' === self::c4wp_get_option( 'captcha_version' ) || 'cloudflare' === self::c4wp_get_option( 'captcha_version' ) ) ) {
				$version = C4WP_VERSION;
				wp_enqueue_style( 'c4wp-login-style', C4WP_PLUGIN_URL . 'assets/css/style.css', C4WP_VERSION, $version );
			}
		}

		/**
		 * Add our foot scripts.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public function c4wp_wp_footer() {
			C4WP_Captcha_Class::footer_script();
		}

		/**
		 * Create a captcha field.
		 *
		 * @param boolean $echo_needed - Should echo or return.
		 *
		 * @return string - HTML Markup.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_captcha_form_field( $echo_needed = false ) {
			if ( $echo_needed ) {
				C4WP_Captcha_Class::form_field();
			} else {
				return C4WP_Captcha_Class::form_field_return();
			}
		}

		/**
		 * Verify a captcha response (old version of plugin).
		 *
		 * @param boolean $response - Response to check.
		 *
		 * @return bool - Verification.
		 *
		 * @since 7.6.0
		 */
		public function anr_verify_captcha( $response = false ) {
			return C4WP_Captcha_Class::init()->verify( $response );
		}

		/**
		 * Verify a captcha response.
		 *
		 * @param boolean $response - Response to check.
		 *
		 * @return bool - Verification.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_verify_captcha( $response = false ) {
			return C4WP_Captcha_Class::init()->verify( $response );
		}

		/**
		 * Add shake script to error screen.
		 *
		 * @param array $shake_error_codes - Current error codes.
		 *
		 * @return array - Codes, with ours appended.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_add_shake_error_codes( $shake_error_codes ) {
			$shake_error_codes[] = 'c4wp_error';

			return $shake_error_codes;
		}

		/**
		 * Create URL for our contact page.
		 *
		 * @return string - Our URL.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_fs_support_forum_url() {
			return 'https://captcha4wp.com/contact/?utm_source=plugin&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=c4wp';
		}

		/**
		 * Create correct captcha domain URL.
		 *
		 * @return string - URL.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_recaptcha_domain() {
			$domain = self::c4wp_get_option( 'recaptcha_domain', 'google.com' );
			return apply_filters( 'c4wp_recaptcha_domain', $domain );
		}


		/**
		 * Setup settings page URL.
		 *
		 * @return string - URL.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_settings_page_url() {
			$url = add_query_arg( array( 'page' => 'c4wp-admin-captcha' ), network_admin_url( 'admin.php' ) );
			return $url;
		}

		/**
		 * Hide freemius contact link.
		 *
		 * @param bool $is_visible - Is currently visible.
		 * @param int  $submenu_id - Item ID.
		 *
		 * @return bool - Is visible.
		 *
		 * @since 7.6.0
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
		 *
		 * @since 7.6.0
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
			$theme        = $theme_data->Name . ' ' . $theme_data->Version; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$parent_theme = $theme_data->Template; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( ! empty( $parent_theme ) ) {
				$parent_theme_data = wp_get_theme( $parent_theme );
				$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
			$server_software = sanitize_text_field( $posted['SERVER_SOFTWARE'] );
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
					// Ignore, is for debugging purposes.
					$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				}
			}

			$sysinfo .= "\n" . '### System Info → End ###' . "\n\n";

			return $sysinfo;
		}

		/**
		 * Determines if an install is premium/paying.
		 *
		 * @return bool - Is premium or not.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_is_premium_version() {
			return ( ( class_exists( 'C4WP_Pro' ) && ! c4wp_fs()->is_not_paying() ) || ( class_exists( 'C4WP_Pro' ) && c4wp_fs()->is_trial() ) ) ? true : false;
		}

		/**
		 * Add a small log during testing.
		 *
		 * @param array $result - Result data.
		 *
		 * @return void
		 *
		 * @since 7.6.0
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
		 * An easy to use array of allowed HTML for use with sanitization of our admin areas etc.
		 *
		 * @return $wp_kses_args - Our array.
		 *
		 * @since 7.6.0
		 */
		public static function c4wp_allowed_kses_args() {
			$wp_kses_args = array(
				'input'    => array(
					'type'          => array(),
					'id'            => array(),
					'name'          => array(),
					'value'         => array(),
					'size'          => array(),
					'class'         => array(),
					'min'           => array(),
					'required'      => array(),
					'checked'       => array(),
					'aria-label'    => array(),
					'aria-readonly' => array(),
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
				'ul'       => array(
					'class' => array(),
					'id'    => array(),
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
					'style'                      => array(),
					'class'                      => array(),
					'id'                         => array(),
					'data-nonce'                 => array(),
					'data-c4wp-use-ajax'         => array(),
					'data-c4wp-failure-redirect' => array(),
					'data-c4wp-v2-site-key'      => array(),
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
					'style' => array(),
					'src'   => array(),
					'id'    => array(),
				),
			);
			return $wp_kses_args;
		}

		/**
		 * WordPress Filter
		 *
		 * @param array $old_links - Array of old links.
		 *
		 * @since 7.6.0
		 */
		public static function add_plugin_shortcuts( $old_links ) {
			$new_links = array();

			if ( ! self::c4wp_is_premium_version() ) {
				$new_links[] = '<a style="font-weight:bold; color:#824780 !important" href="https://captcha4wp.com/?utm_source=plugin&utm_medium=referral&utm_campaign=c4wp" target="_blank">' . __( 'Get Premium!', 'captcha-4wp' ) . '</a>';
				$old_links   = array_merge( array_slice( $old_links, 0, 1 ), $new_links, array_slice( $old_links, 1 ) );
			}

			return $old_links;
		}

		/**
		 * Check whether we are on an admin and plugin page.
		 *
		 * @return bool
		 *
		 * @since 7.6.0
		 */
		public static function is_admin_page() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$cur_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
			$check    = 'c4wp-';

			return \is_admin() && ( false !== strpos( $cur_page, $check ) );
		}

		/**
		 * Remove all non-WP Mail SMTP plugin notices from our plugin pages.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		public static function hide_unrelated_notices() {
			// Bail if we're not on our screen or page.
			if ( ! self::is_admin_page() ) {
				return;
			}

			self::remove_unrelated_actions( 'user_admin_notices' );
			self::remove_unrelated_actions( 'admin_notices' );
			self::remove_unrelated_actions( 'all_admin_notices' );
			self::remove_unrelated_actions( 'network_admin_notices' );
		}

		/**
		 * Remove all non-WP Mail SMTP notices from the our plugin pages based on the provided action hook.
		 *
		 * @param string $action The name of the action.
		 *
		 * @return void
		 *
		 * @since 7.6.0
		 */
		private static function remove_unrelated_actions( $action ) {

			global $wp_filter;

			if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
				return;
			}

			foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if (
						( // Cover object method callback case.
							is_array( $arr['function'] ) &&
							isset( $arr['function'][0] ) &&
							is_object( $arr['function'][0] ) &&
							false !== strpos( strtolower( get_class( $arr['function'][0] ) ), C4WP_PREFIX )
						) ||
						( // Cover class static method callback case.
							! empty( $name ) &&
							false !== strpos( strtolower( $name ), C4WP_PREFIX )
						) ||
						( // Cover class static method callback case.
							! empty( $name ) &&
							false !== strpos( strtolower( $name ), 'c4wp\\' )
						)
					) {
						continue;
					}

					unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}
}
