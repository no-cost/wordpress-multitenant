<?php
/**
 * Plugin Name: File Uploader for WooCommerce
 * Plugin URI: https://snowray.co/
 * Description: Allows to attach files from different sources to WooCommerce customer orders.
 * Author: Snowray
 * Author URI:  https://snowray.co
 * Version: 1.0.3
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package wcu\FileUploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use wcu\Classes\PluginSetup;
use wcu\Classes\PluginInitialization;
use wcu\Classes\WC\ProductActions;
use wcu\Classes\Settings\PluginSettings;
use wcu\JsonApi\ImageJsonApi;
use wcu\Classes\Blocks;

require __DIR__ . '/vendor/autoload.php';

define( 'WCU_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCU_URL', plugin_dir_url( __FILE__ ) );

add_action( 'admin_init', 'check_installed_woocommerce' );
add_action( 'admin_init', 'check_logout_action' );

/**
 * Check if WooCommerce is installed
 *
 * @return void
 */
function check_installed_woocommerce() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! PluginSetup::is_woocommerce_active() ) {
		add_action( 'admin_notices', array( '\wcu\Classes\PluginSetup', 'add_woocommerce_inactive_notice' ) );

		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

/**
 * Check logout action
 *
 * @return void
 */
function check_logout_action() {
	if ( ! empty( $_GET['wcu_logout'] ) && current_user_can( 'manage_options' ) ) {
		delete_option( 'wcu_enable_plugin' );
		delete_option( 'wcu_product_category' );
		delete_option( 'wcu_plugin_setting_user_password' );
		delete_option( 'wcu_plugin_setting_user_login' );
		delete_option( 'wcu_uc_public_key' );
		wp_safe_redirect( get_admin_url() . 'admin.php?page=wc-settings&tab=wcu_settings' );
	}
}

/**
 * Install plugin
 *
 * @return void
 */
function install_plugin(): void {
	$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'file-uploader';
	wp_mkdir_p( $uploads_dir );
}

register_activation_hook(
	__FILE__,
	'install_plugin'
);

/**
 * Remove plugin
 *
 * @return void
 */
function uninstall_plugin_data(): void {
	delete_option( 'wcu_enable_plugin' );
	delete_option( 'wcu_product_category' );
	delete_option( 'wcu_plugin_setting_user_password' );
	delete_option( 'wcu_plugin_setting_user_login' );
	delete_option( 'wcu_uc_public_key' );
}

register_deactivation_hook(
	__FILE__,
	'uninstall_plugin_data'
);

gprop()->set_property( 'pluginInitialization', new PluginInitialization() );
new ProductActions();
new PluginSettings();
new ImageJsonApi();
new Blocks();

//require __DIR__.'/blocks/file-upload--block/file-upload--block.php';

