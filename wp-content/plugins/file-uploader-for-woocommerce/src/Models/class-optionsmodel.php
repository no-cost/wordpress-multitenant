<?php
/**
 * Class OptionsModel
 *
 * @package wcu\FileUploader
 */

namespace wcu\Models;

use WP_Term;

/**
 * Options model
 */
class OptionsModel {

	const PLUGIN_OPTIONS_NAME = 'wcu_plugin_options';
	const PRODUCT_CATEGORY_NAME = 'product_cat';

	/**
	 * Data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * User ID
	 *
	 * @var int
	 */
	protected int $user_id = 0;

	/**
	 * User email
	 *
	 * @var string - user login
	 */
	protected string $user_login = '';

	/**
	 * User password
	 *
	 * @var string
	 */
	protected string $user_password = '';

	/**
	 * Public API key
	 *
	 * @var string
	 */
	protected string $public_api_key = '';

	/**
	 * Private API key
	 *
	 * @var string
	 */
	protected string $private_api_key = '';

	/**
	 * Is enabled
	 *
	 * @var bool
	 */
	protected bool $is_enabled = false;

	/**
	 * Product Category ID's
	 *
	 * @var int[]
	 */
	protected array $product_categories_ids = array();

	/**
	 * UC public key
	 *
	 * @var string
	 */
	protected string $wcu_uc_public_key = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$data = get_option( self::PLUGIN_OPTIONS_NAME );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$this->data = $data;

		$this->set_id();
		$this->set_public_api_key();
		$this->set_status();
		$this->set_product_categories_ids();
		$this->set_uc_public_key();
		$this->set_user_login();
		$this->set_user_password();
	}

	/**
	 * Returns user ID
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Returns public API key
	 *
	 * @return string
	 */
	public function get_public_api_key(): string {
		return $this->public_api_key;
	}

	/**
	 * Returns user login
	 *
	 * @return string
	 */
	public function get_user_login(): string {
		return $this->user_login;
	}

	/**
	 * Returns user password
	 *
	 * @return string
	 */
	public function get_user_password(): string {
		return $this->user_password;
	}

	/**
	 * Returns public API key
	 *
	 * @return string
	 */
	public function get_private_api_key(): string {
		return $this->private_api_key;
	}

	/**
	 * Get enabled product categories ID's
	 *
	 * @return WP_Term[]
	 */
	public function get_enabled_product_categories_ids(): array {
		return $this->product_categories_ids;
	}

	/**
	 * Returns UC public key
	 *
	 * @return string
	 */
	public function get_wcu_uc_public_key(): string {
		return $this->wcu_uc_public_key;
	}

	/**
	 * Check if the plugin logic is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->is_enabled;
	}

	/**
	 * Set User ID
	 *
	 * @return void
	 */
	protected function set_id(): void {
		$user_id = get_option( 'wcu_plugin_user_id' );
		if ( $user_id ) {
			$this->user_id = intval( $user_id );
		}
	}

	/**
	 * Ser user login
	 *
	 * @return void
	 */
	protected function set_user_login(): void {
		$user_login = get_option( 'wcu_plugin_setting_user_login' );
		if ( $user_login ) {
			$this->user_login = sanitize_text_field( $user_login );
		}
	}

	/**
	 * Set user password
	 *
	 * @return void
	 */
	protected function set_user_password(): void {
		if ( isset( $this->data['user_password'] ) ) {
			$this->user_password = esc_js( $this->data['user_password'] );
		}
	}

	/**
	 * Set the public API key
	 *
	 * @return void
	 */
	protected function set_public_api_key(): void {
		$value = get_option( 'wcu_uc_public_key' );
		if ( $value ) {
			$this->public_api_key = sanitize_text_field( $value );
		}
	}

	/**
	 * Set the plugin status (enabled or disabled)
	 *
	 * @return void
	 */
	protected function set_status(): void {
		$value = get_option( 'wcu_enable_plugin' );
		$value = intval( $value );

		if ( $value ) {
			$this->is_enabled = true;
		} else {
			$this->is_enabled = false;
		}
	}

	/**
	 * Set enabled product categories
	 *
	 * @return void
	 */
	protected function set_product_categories_ids(): void {
		$terms = get_option( 'wcu_product_category' );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term_id ) {
				$this->product_categories_ids[] = intval( $term_id );
			}
		}
	}

	/**
	 * Set UC public key
	 *
	 * @return void
	 */
	protected function set_uc_public_key(): void {
		$value = get_option( 'wcu_uc_public_key', '' );
		if ( $value ) {
			$this->wcu_uc_public_key = $value;
		}
	}
}
