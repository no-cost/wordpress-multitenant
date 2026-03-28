<?php
/**
 * Class PluginInitialization
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes;

use wcu\Models\OptionsModel;
use wcu\Components\Render;

/**
 * Class PluginInitialization
 */
class PluginInitialization {

	/**
	 * JS file version for cache
	 *
	 * @var string
	 */
	private string $plugin_js_version = '1.0.0';

	/**
	 * Is enabled
	 *
	 * @var bool
	 */
	protected bool $is_enabled = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		gprop()->set_property( OptionsModel::PLUGIN_OPTIONS_NAME, new OptionsModel() ); // Set plugin options.
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add actions
	 *
	 * @return void
	 */
	public function add_actions(): void {
		add_action( 'wp', array( $this, 'add_button_actions_for_upload_button' ) );
	}

	public function add_filters(): void {
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( WCU_DIR . '/file-uploader-woocommerce.php' ),
			array( $this, 'add_plugin_settings_links' ) );

	}

	/**
	 * We have to check how it works
	 *
	 * @return void
	 */
	public function add_button_actions_for_upload_button(): void {
		if ( $this->is_allowed_button() ) {
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_image_upload_field' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_plugin_assets' ) );
		}
	}

	/**
	 * Add input image field after cart quantity
	 *
	 * @return void
	 */
	public function add_image_upload_field(): void {
		Render::view( 'single-product/add-to-cart/input-image-field' );
	}

	/**
	 * Add plugin assets
	 *
	 * @return void
	 */
	public function add_plugin_assets(): void {
		wp_enqueue_script( 'wcu-main-script', WCU_URL . '/dist/js/main.min.js', array(), $this->plugin_js_version,
			true );
	}

	/**
	 * Check if button is allowed on the current screen
	 *
	 * @param int|null $product_id
	 *
	 * @return bool
	 */
	public function is_allowed_button( int $product_id = null ): bool {
		/**
		 * Options model
		 *
		 * @var OptionsModel $options
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		if ( ! $options->is_enabled() ) {
			return false;
		}

		if ( is_product() || $product_id ) {
			$chosen_categories_ids = $options->get_enabled_product_categories_ids();
			if ( ! $chosen_categories_ids ) {
				return true;
			}

			if ( ! is_product() && $product_id ) {
				$post = get_post( $product_id );
			} else {
				global $post;
			}

			$post_terms = wp_get_post_terms( $post->ID, OptionsModel::PRODUCT_CATEGORY_NAME );

			foreach ( $post_terms as $post_term ) {
				if ( in_array( $post_term->term_id, $chosen_categories_ids, true ) ) {
					return true;
				}

				$parents = get_ancestors( $post_term->term_id, OptionsModel::PRODUCT_CATEGORY_NAME );
				foreach ( $parents as $parent ) {
					if ( in_array( $parent, $chosen_categories_ids, true ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Add a link to the settings page from the plugin page
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ): array {
		if ( str_contains( $file, 'file-uploader-woocommerce.php' ) ) {
			$new_links = array(
				'<bold><a href="' . esc_url( 'https://snowray.co/buy-pro-file-uploader-plugin-woocommerce' ) . '" target="_blank">' . esc_html__( 'Upgrade to PRO',
					'wcu' ) . '</a></bold>',
			);
			$links     = array_merge( $links, $new_links );
		}

		return $links;
	}

	public function add_plugin_settings_links( $links ): array {
		$settings_page = admin_url( 'admin.php?page=wc-settings&tab=wcu_settings' );
		$new_links     = array(
			'<a href="' . esc_url( $settings_page ) . '">' . esc_html__( 'Settings',
				'wcu' ) . '</a>',
		);

		return array_merge( $links, $new_links );
	}

}

