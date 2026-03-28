<?php
/**
 * Class PluginSettings
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes\Settings;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use WC_Admin_Settings;
use wcu\Models\OptionsModel;
use Throwable;

/**
 * Class PluginSettings
 *
 * @package wcu\FileUploader
 */
class PluginSettings {

	const SERVER_URL = 'api.snowray.co/api/';

	/**
	 * Options model
	 *
	 * @var OptionsModel|bool|mixed|null
	 */
	protected OptionsModel $options_model;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'woocommerce_settings_page_init', array( $this, 'init_settings_page' ) );
			add_action( 'woocommerce_update_options_wcu_settings', array( $this, 'save_plugin_settings' ) );
		}

		$this->options_model = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );
	}

	/**
	 * Save plugin settings
	 *
	 * @return void
	 */
	public function save_plugin_settings() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_REQUEST['_wpnonce'] )
			),
			'woocommerce-settings'
		)
		) {
			return;
		}

		$section = '';
		if ( isset( $_GET['section'] ) ) {
			$section = sanitize_text_field( wp_unslash( $_GET['section'] ) );
		}

		if ( ! $section ) {
			$section = 'default';
		}

		switch ( $section ) {
			case 'default':
				$this->save_default_block();
				break;
			case 'sign_in':
				$this->save_sign_in_data();
				break;
		}

		$options = new OptionsModel();
		gprop()->set_property( 'wcu_plugin_options', $options );
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
	 * Save default block
	 *
	 * @return void
	 */
	protected function save_default_block(): void {
		/**
		 * Options model
		 *
		 * @var OptionsModel $options - options.
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		if ( $options->get_user_login() && $options->get_public_api_key() ) {
			$this->save_plugin_main_options();
		} else {
			$this->save_sign_up_data();
		}
	}

	/**
	 * Save sign up data
	 *
	 * @return void
	 */
	public function save_sign_up_data(): void {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_REQUEST['_wpnonce'] )
			),
			'woocommerce-settings'
		)
		) {
			return;
		}

		$data = array(
			array(
				'id'   => 'wcu_plugin_setting_user_login',
				'type' => 'email',
			),
			array(
				'id'   => 'wcu_plugin_setting_user_password',
				'type' => 'password',
			),
			array(
				'id'   => 'wcu_plugin_setting_user_password_confirmation',
				'type' => 'password',
			),
		);

		if ( empty( $_POST['wcu_plugin_setting_user_login'] ) ) {
			WC_Admin_Settings::add_error( 'Login is required' );

			return;
		}

		$login = sanitize_email( wp_unslash( $_POST['wcu_plugin_setting_user_login'] ) );
		if ( ! $login ) {
			WC_Admin_Settings::add_error( 'Login is required' );

			return;
		}

		if ( empty( $_POST['wcu_plugin_setting_user_password'] ) ) {
			WC_Admin_Settings::add_error( 'Password is required' );

			return;
		}
		$pass1 = trim( $_POST['wcu_plugin_setting_user_password'] );

		if ( empty( $_POST['wcu_plugin_setting_user_password_confirmation'] ) ) {
			WC_Admin_Settings::add_error( 'Password confirmation is required' );

			return;
		}
		$pass2 = trim( $_POST['wcu_plugin_setting_user_password_confirmation'] );

		if ( ! $pass1 ) {
			WC_Admin_Settings::add_error( 'Password is required' );

			return;
		} elseif ( ! $pass2 ) {
			WC_Admin_Settings::add_error( 'Password confirmation is required' );

			return;
		} elseif ( $pass1 !== $pass2 ) {
			WC_Admin_Settings::add_error( 'Passwords do not match' );

			return;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$pass1 = base64_encode( $pass1 );

		try {
			$this->check_user_password( 'register', $login, $pass1 );
			woocommerce_update_options( $data );
		} catch ( Throwable $tw ) {
			if ( $tw->getCode() === 409 ) {
				WC_Admin_Settings::add_error( 'User already exists' );
			} else {
				WC_Admin_Settings::add_error( 'Server error' );
			}
		}
	}

	/**
	 * Save sign in data
	 *
	 * @return void
	 */
	public function save_sign_in_data(): void {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_REQUEST['_wpnonce'] )
			),
			'woocommerce-settings'
		)
		) {
			return;
		}

		$data = array(
			array(
				'id'   => 'wcu_plugin_setting_user_login',
				'type' => 'email',
			),
			array(
				'id'   => 'wcu_plugin_setting_user_password',
				'type' => 'password',
			),
		);

		if ( empty( $_POST['wcu_plugin_setting_user_login'] ) || empty( $_POST['wcu_plugin_setting_user_password'] ) ) {
			return;
		}

		$login = sanitize_email( wp_unslash( $_POST['wcu_plugin_setting_user_login'] ) );
		if ( ! $login ) {
			WC_Admin_Settings::add_error( 'Login is required' );

			return;
		}

		if ( empty( $_POST['wcu_plugin_setting_user_password'] ) ) {
			WC_Admin_Settings::add_error( 'Password is required' );

			return;
		}
		$pass1 = trim( $_POST['wcu_plugin_setting_user_password'] );

		if ( ! $pass1 ) {
			WC_Admin_Settings::add_error( 'Password is required' );

			return;
		}
		$pass1 = base64_encode( $pass1 );

		try {
			$this->check_user_password( 'login', $login, $pass1 );
			woocommerce_update_options( $data );
		} catch ( Throwable $tw ) {
			if ( $tw->getCode() === 401 ) {
				WC_Admin_Settings::add_error( 'Wrong username or password' );
			} else {
				WC_Admin_Settings::add_error( 'Server error' );
			}

			return;
		}
		wp_safe_redirect( get_admin_url() . 'admin.php?page=wc-settings&tab=wcu_settings' );
	}

	/**
	 * Save options when suer is logged in
	 *
	 * @return void
	 */
	public function save_plugin_main_options(): void {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_REQUEST['_wpnonce'] )
			),
			'woocommerce-settings'
		)
		) {
			return;
		}

		if ( ! empty( $_POST['wcu_product_category'] ) ) {
			$categories = array_map( 'sanitize_text_field', wp_unslash( $_POST['wcu_product_category'] ) );
		} else {
			$categories = array();
		}

		update_option( 'wcu_product_category', $categories );

		$is_enabled = ! empty( $_POST['wcu_enable_plugin'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['wcu_enable_plugin'] ) ) ) : 0;
		update_option( 'wcu_enable_plugin', $is_enabled );
	}

	/**
	 * Check user password
	 *
	 * @param string $url_action - login/register.
	 * @param string $login - login.
	 * @param string $password - password.
	 *
	 * @return void
	 * @throws GuzzleException - exception.
	 * @throws Exception - exception.
	 */
	public function check_user_password( string $url_action, string $login, string $password ): void {

		$login    = sanitize_email( $login );
		$password = esc_js( $password );

		$client = new Client();

		$form_params = array(
			'email'    => sanitize_email( $login ),
			'password' => esc_js( $password ),
		);

		if ( 'register' === $url_action ) {
			$form_params['name']       = uniqid();
			$form_params['c_password'] = esc_js( $password );
		}

		$res = $client->request(
			'POST',
			self::SERVER_URL . $url_action,
			array(
				'form_params' => $form_params,
			)
		);

		if ( $res->getStatusCode() === 200 ) {
			$content = json_decode( $res->getBody()->getContents(), true );
			if ( ! $content['success'] ) {
				throw new Exception( sanitize_text_field( $content['message'] ) );
			}

			update_option( 'wcu_uc_public_key', sanitize_text_field( $content['data']['key'] ) );
			update_option( 'wcu_plugin_user_id', sanitize_text_field( $content['data']['user_id'] ) );
		} else {
			throw new Exception( sanitize_text_field( 'Request error' ) );
		}
	}
}
