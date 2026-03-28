<?php
/**
 * Class PluginSetup
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes;

use wcu\Classes\Settings\SettingsPage;

/**
 * Class Plugin Setup
 */
class PluginSetup {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'woocommerce_settings_page_init', array( $this, 'init_settings_page' ) );
			add_action( 'woocommerce_update_options_wcu_settings', array( $this, 'save_plugin_settings' ) );
		}
	}

	/**
	 * Add settings on the WooCommerce settings page
	 *
	 * @return void
	 */
	public function init_settings_page(): void {
		new SettingsPage();
	}

	/**
	 * Add a notification if the plugin cannot be installed
	 *
	 * @return void
	 */
	public static function add_woocommerce_inactive_notice(): void {
		if ( current_user_can( 'activate_plugins' ) ) {
			$admin_notice_content = '';

			if ( ! self::is_woocommerce_active() ) {
				$install_url = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'install-plugin',
							'plugin' => 'woocommerce',
						),
						admin_url( 'update.php' )
					),
					'install-plugin_woocommerce'
				);

				$admin_notice_content = sprintf(

				/*
				 * Translators: %s: HTML open tag.
				 * Translators: %s: HTML close tag.
				 * Translators: %s: link to WooCommerce open tag.
				 * Translators: %s: link close tag.
				 * Translators: %s: link open tag.
				 * Translators: %s: link close tag.
				 */
					esc_html__(
						'%1$sFile Uploader for WooCommerce plugin is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for File Uploader plugin to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s',
						'wcu'
					),
					'<strong>',
					'</strong>',
					'<a href="https://wordpress.org/extend/plugins/woocommerce/">',
					'</a>',
					'<a href="' . esc_url( $install_url ) . '">',
					'</a>'
				);
			}

			if ( $admin_notice_content ) {
				echo '<div class="error">';
				echo '<p>' . wp_kses_post( $admin_notice_content ) . '</p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Check if WooCommerce plugin is active
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( in_array(
			'woocommerce/woocommerce.php',
			apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
			true
		) ) {
			return true;
		}

		return false;
	}

}
