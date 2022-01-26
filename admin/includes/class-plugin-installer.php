<?php
/**
 * Plugin installer action
 *
 * Class file for installing plugins from the repo.
 *
 * @since 7.0.2
 * @package C4WP
 */

if ( ! class_exists( 'C4WP_PluginInstallerAction' ) ) {

	/**
	 * Class to handle the installation and activation of plugins.
	 *
	 * @since 7.0.2
	 */
	class C4WP_PluginInstallerAction {

		/**
		 * Register the ajax action.
		 *
		 * @method register
		 * @since 7.0.2
		 */
		public function register() {
			add_action( 'wp_ajax_c4wp_run_downgrade', array( $this, 'c4wp_run_downgrade' ) );
			add_action( 'wp_ajax_c4wp_proceed_with_upgrade', array( $this, 'c4wp_proceed_with_upgrade' ) );
		}

		/**
		 * Run the installer.
		 *
		 * @method run_addon_install
		 * @since 7.0.2
		 */
		public function c4wp_run_downgrade() {
            
            $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
            if ( ! wp_verify_nonce( $nonce, 'back_to_617' ) ) {
				wp_send_json_error();
			}

            $plugin_zip  = 'https://downloads.wordpress.org/plugin/advanced-nocaptcha-recaptcha.6.1.7.zip';
            $plugin_slug = 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php';

			$this->install_plugin( $plugin_zip );
            $this->run_activate( $plugin_slug );
            $this->activate( $plugin_zip );
            $result = 'success';

			wp_send_json( $result );
		}

        public function c4wp_proceed_with_upgrade() {
            $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
            if ( ! wp_verify_nonce( $nonce, 'proceed_with_upgrade' ) ) {
				wp_send_json_error();
			}
            
            if ( is_multisite() ) {
                delete_site_option( 'c4wp_70_changes_notice_needed' );
            } else {
                delete_option( 'c4wp_70_changes_notice_needed' );
            }

            $result = 'success';
			wp_send_json( $result );
		}

		/**
		 * Install a plugin given a slug.
		 *
		 * @method install
		 * @since 7.0.2
		 * @param  string $plugin_zip URL to the direct zip file.
		 */
		public function install_plugin( $plugin_zip = '' ) {
			// bail early if we don't have a slug to work with.
			if ( empty( $plugin_zip ) ) {
				return;
			}
			// get the core plugin upgrader if not already in the runtime.
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			// clear the cache so we're using fresh data.
			wp_cache_flush();
			$upgrader       = new Plugin_Upgrader();
			$install_result = $upgrader->install( $plugin_zip );
			if ( ! $install_result || is_wp_error( $install_result ) ) {
				if ( is_wp_error( $install_result ) ) {
					return $install_result->get_error_message();
				}
				die();
			}
		}

		/**
		 * Activates a plugin that is available on the site.
		 *
		 * @method activate
		 * @since 7.0.2
		 * @param  string $plugin_zip URL to the direct zip file.
		 * @return void
		 */
		public function activate( $plugin_zip = '' ) {
			// bail early if we don't have a slug to work with.
			if ( empty( $plugin_zip ) ) {
				return;
			}

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				return;
			}

			// get core plugin functions if they are not already in runtime.
			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			activate_plugin( $plugin_zip );
		}

		/**
		 * Activates a plugin that is available on the site.
		 *
		 * @method run_activate
		 * @since 7.0.2
		 * @param  string $plugin_slug slug for plugin.
		 */
		public function run_activate( $plugin_slug = '' ) {
			// bail early if we don't have a slug to work with.
			if ( empty( $plugin_slug ) ) {
				return;
			}

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				$current = get_site_option( 'active_sitewide_plugins' );
			} else {
				$current = get_option( 'active_plugins' );
			}

			if ( ! in_array( $plugin_slug, $current, true ) ) {
				if ( function_exists( 'is_multisite' ) && is_multisite() ) {
					$current[] = $plugin_slug;
					activate_plugin( $plugin_slug, '', true );
				} else {
					$current[] = $plugin_slug;
					activate_plugin( $plugin_slug );
				}
			}
			return null;
		}

		/**
		 * Check if a plugin is installed.
		 *
		 * @method is_plugin_installed
		 * @since 7.0.2
		 * @param  string $plugin_slug slug for plugin.
		 */
		public function is_plugin_installed( $plugin_slug = '' ) {
			// bail early if we don't have a slug to work with.
			if ( empty( $plugin_slug ) ) {
				return;
			}

			// get core plugin functions if not already in the runtime.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins = get_plugins();

			// true if plugin is already installed or false if not.
			if ( ! empty( $all_plugins[ $plugin_slug ] ) ) {
				return true;
			} else {
				return false;
			}

		}
	}
}
