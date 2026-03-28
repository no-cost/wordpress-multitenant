<?php
/**
 * SettingsPage Class
 *
 * @param OptionsModel $options_model
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes\Settings;

use WC_Settings_Page;
use wcu\Components\Render;
use wcu\Models\OptionsModel;

/**
 * SettingsPage Class
 */
class SettingsPage extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id    = 'wcu_settings';
		$this->label = __( 'File Uploader Settings', 'wcu' );

		parent::__construct();

		add_action( 'woocommerce_admin_field_select2', array( $this, 'show_categories_field' ) );
		add_action( 'woocommerce_admin_field_account_data', array( $this, 'show_account_data' ) );

	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections(): array {
		/**
		 * Options property
		 *
		 * @var OptionsModel $options - options.
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		if ( $options->get_user_login() && $options->get_public_api_key() ) {
			$tabs = array(
				'' => __( 'Plugin settings', 'wcu' ),
			);
		} else {
			$tabs = array(
				''        => __( 'Sign up', 'wcu' ),
				'sign_in' => __( 'Log in', 'wcu' ),
			);
		}

		return $tabs;
	}

	/**
	 * Returns settings for default section
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section(): array {
		/**
		 * Options model
		 *
		 * @var OptionsModel $options
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		if ( $options->get_user_login() && $options->get_public_api_key() ) {
			return $this->get_plugin_settings_content();
		} else {
			return $this->get_sign_up_settings_content();
		}
	}

	/**
	 * Prepare sign up tab content
	 *
	 * @return array
	 */
	protected function get_sign_up_settings_content(): array {
		$description = 'Please create an account. Already registered? Please <a href="' . get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=wcu_settings&section=sign_in">login</a>.';
		/**
		 * Options model
		 *
		 * @var OptionsModel $options
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		$settings =
			array(
				array(
					'title' => __( 'Sign up', 'wcu' ),
					'type'  => 'title',
					'desc'  => $description,
					'id'    => 'wcu_pro_default_settings',
				),

				array(
					'id'       => 'wcu_plugin_setting_user_login',
					'title'    => __( 'User Email', 'wcu' ),
					'type'     => 'email',
					'value'    => $options->get_user_login(),
					'autoload' => false,
				),

				array(
					'id'       => 'wcu_plugin_setting_user_password',
					'title'    => __( 'User Password', 'wcu' ),
					'value'    => '',
					'type'     => 'password',
					'autoload' => false,
				),

				array(
					'id'       => 'wcu_plugin_setting_user_password_confirmation',
					'title'    => __( 'Password Confirmation', 'wcu' ),
					'value'    => '',
					'type'     => 'password',
					'autoload' => false,
				),

				array(
					'type' => 'sectionend',
				),
			);

		return apply_filters( 'wcu_text_settings', $settings );
	}

	/**
	 * Prepare plugin settings tab content
	 *
	 * @return array
	 */
	protected function get_plugin_settings_content(): array {
		/**
		 * Options model
		 *
		 * @var OptionsModel $options - options.
		 */
		$options_model = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		$product_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( ! is_array( $product_categories ) ) {
			// TODO: Something went wrong - need add message.
			$product_categories = array();
		}

		$options = array();
		foreach ( $product_categories as $category ) {
			$options[ $category->term_id ] = $category->name;
		}

		$settings = array(
			array(
				'title' => __( 'Plugin Settings', 'wcu' ),
				'type'  => 'title',
				'id'    => 'wcu_pro_default_settings',
			),

			array(
				'title'    => __( 'Enable plugin', 'wcu' ),
				'desc'     => __( 'Enable uploads on Product Pages', 'wcu' ),
				'id'       => 'wcu_enable_plugin',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'autoload' => false,
				'value'    => $options_model->is_enabled() ? 'yes' : 'no',
			),

			array(
				'title'             => __( 'Product Categories', 'wcu' ),
				'id'                => 'wcu_product_category',
				'default'           => 'all',
				'type'              => 'select2',
				'css'               => 'min-width: 350px;',
				'desc_tip'          => false,
				'options'           => $options,
				'custom_attributes' => array( 'required' => 'required' ),
				'value'             => $options_model->get_enabled_product_categories_ids(),
			),

			array(
				'title' => __( 'Account Data', 'wcu' ),
				'type'  => 'account_data',
			),

			array(
				'type' => 'sectionend',
			),
		);

		return apply_filters( 'wcu_text_settings', $settings );

	}

	/**
	 * Prepare content for sign in tab
	 *
	 * @return mixed|null
	 */
	protected function get_settings_for_sign_in_section() {
		$settings =
			array(
				array(
					'title' => __( 'Log In', 'wcu' ),
					'type'  => 'title',
					'desc'  => __( 'Please enter login and password.' ),
					'id'    => 'wcu_pro_default_settings',
				),

				array(
					'id'       => 'wcu_plugin_setting_user_login',
					'title'    => __( 'User Email', 'wcu' ),
					'type'     => 'email',
					'autoload' => false,
				),

				array(
					'id'       => 'wcu_plugin_setting_user_password',
					'title'    => __( 'User Password', 'wcu' ),
					'type'     => 'password',
					'autoload' => false,
				),

				array(
					'type' => 'sectionend',
				),
			);

		return apply_filters( 'wcu_text_settings', $settings );
	}

	/**
	 * Show categories field
	 *
	 * @param array $value - category value.
	 *
	 * @return void
	 */
	public function show_categories_field( array $value ): void {
		Render::view( 'admin/fields/category', array( 'value' => $value ) );
	}

	/**
	 * Show account data
	 *
	 * @param array $value - settings values.
	 *
	 * @return void
	 */
	public function show_account_data( array $value ): void {
		/**
		 * Options property
		 *
		 * @var OptionsModel $options - options.
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		Render::view(
			'admin/fields/account-data',
			array(
				'title'      => $value['title'],
				'user_id' => $options->get_user_id(),
				'login'      => $options->get_user_login(),
			)
		);
	}
}
