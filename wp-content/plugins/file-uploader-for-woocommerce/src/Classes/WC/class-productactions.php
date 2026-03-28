<?php
/**
 * Class ProductActions
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes\WC;

use GuzzleHttp\Exception\GuzzleException;
use wcu\Classes\Helpers\UploaderHelper;
use wcu\Components\Render;
use wcu\Models\OptionsModel;

/**
 * Class ProductActions
 */
class ProductActions {

	/**
	 * Constructor
	 */
	public function __construct() {
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

		if ( $options->is_enabled() ) {
			$this->add_filters();
			$this->add_actions();
		}
	}

	/**
	 * Add filters
	 *
	 * @return void
	 */
	public function add_filters(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_uploaded_images' ), 10, 2 );
		add_filter(
			'woocommerce_add_cart_item_data',
			array( $this, 'add_image_id_to_cart_item' ),
			10,
			1,
		);
		add_filter(
			'woocommerce_order_item_display_meta_key',
			array( $this, 'show_link_title' ),
			20,
			1,
		);
		add_filter(
			'woocommerce_order_item_display_meta_value',
			array( $this, 'convert_order_meta_image_id_to_link' ),
			20,
			3,
		);
	}

	/**
	 * Add actions
	 *
	 * @return void
	 */
	public function add_actions(): void {
		add_action(
			'woocommerce_after_cart_item_name',
			array( $this, 'show_uploaded_image_thumbnail_on_the_cart_item' ),
			1,
			50,
		);

		add_action(
			'woocommerce_checkout_create_order_line_item',
			array( $this, 'save_uploaded_image_id_to_order_item_meta' ),
			4,
			50,
		);
	}

	/**
	 * Check if the image is uploaded
	 *
	 * @param bool $passed  - passed option.
	 * @param int  $product_id  - product ID.
	 *
	 * @return bool
	 */
	public function validate_uploaded_images( bool $passed, int $product_id ): bool {
		/**
		 * Options model
		 *
		 * @var OptionsModel $options
		 */
		$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );
		if ( ! $options->is_enabled() ) {
			return $passed;
		}

		$chosen_categories_ids = $options->get_enabled_product_categories_ids();
		$post_terms            = wp_get_post_terms( $product_id, OptionsModel::PRODUCT_CATEGORY_NAME );

		if ( $chosen_categories_ids ) {
			$exist_category = false;
			foreach ( $post_terms as $post_term ) {
				if ( in_array( $post_term->term_id, $chosen_categories_ids, true ) ) {
					$exist_category = true;
					break;
				}

				$parents = get_ancestors( $post_term->term_id, OptionsModel::PRODUCT_CATEGORY_NAME );
				foreach ( $parents as $parent ) {
					if ( in_array( $parent, $chosen_categories_ids, true ) ) {
						$exist_category = true;
						break ( 2 );
					}
				}
			}

			if ( ! $exist_category ) {
				return $passed;
			}
		}

		if ( empty( $_REQUEST['wcu_nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wcu_nonce'] ) ), 'wcu-image' ) ) {
			return false;
		}

		return $passed;
	}

	/**
	 * Set the uploaded image identifier to the cart item
	 *
	 * @param mixed $cart_item_data  - cart item data.
	 *
	 * @return mixed
	 * @throws GuzzleException - Guzzle exception.
	 */
	public function add_image_id_to_cart_item( $cart_item_data ) {
		if ( empty( $_REQUEST['wcu_nonce'] ) ) {
			return $cart_item_data;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wcu_nonce'] ) ), 'wcu-image' ) ) {
			return $cart_item_data;
		}

		if ( empty( $_POST['image-uuid'] ) || empty( $_POST['image-filename'] ) ) {
			return $cart_item_data;
		}

		if ( ! empty( $_POST['uc-product-images'] ) ) {
			$uuid               = sanitize_text_field( wp_unslash( $_POST['image-uuid'] ) );
			$original_file_name = sanitize_text_field( wp_unslash( $_POST['image-filename'] ) );
			$modifications      = empty( $_POST['image-modification'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['image-modification'] ) );

			$url = UploaderHelper::upload_image( $uuid, $original_file_name, $modifications );
			if ( $url ) {
				$cart_item_data['uc_image_id']  = $uuid;
				$cart_item_data['uc_image_url'] = $url;
			}
		}

		return $cart_item_data;
	}

	/**
	 * Show image thumbnail
	 *
	 * @param mixed $cart_item - cart item.
	 *
	 * @return void
	 */
	public function show_uploaded_image_thumbnail_on_the_cart_item( $cart_item ): void {
		if ( isset( $cart_item['uc_image_url'] ) ) {
			echo wp_kses_post(
				Render::view_partial(
					'common/thumbnail-image',
					array( 'url' => $cart_item['uc_image_url'] )
				)
			);
		}
	}

	/**
	 * Save uploaded image ID to the order item metadata
	 *
	 * @param mixed $item  - item.
	 * @param mixed $cart_item_key  - cart item key.
	 * @param mixed $values  - values.
	 * @param mixed $order  - order.
	 *
	 * @return void
	 */
	public function save_uploaded_image_id_to_order_item_meta( $item, $cart_item_key, $values, $order ): void {
		if ( isset( $values['uc_image_id'] ) ) {
			$item->add_meta_data( 'uc_image_url', esc_url_raw( $values['uc_image_url'] ) );
			$item->add_meta_data( 'uc_image_id', sanitize_text_field( $values['uc_image_id'] ) );
			$item->save();
		}
	}

	/**
	 * Show the correct display name for the UC image meta-identifier on the order view page
	 *
	 * @param mixed $display_key  - display key.
	 *
	 * @return mixed|string|null
	 */
	public function show_link_title( $display_key ) {
		if ( 'uc_image_id' === $display_key ) {
			return __( 'Uploaded image ID', 'wcu' );
		}

		if ( 'uc_image_url' === $display_key ) {
			return __( 'Uploaded image URL', 'wcu' );
		}

		return $display_key;
	}

	/**
	 * Replace the UC ID with the image download link for the UC image meta value on the order view page
	 *
	 * @param mixed $display_value  - dispaly value.
	 * @param mixed $meta  - meta data.
	 * @param mixed $obj  - instance.
	 *
	 * @return string
	 */
	public function convert_order_meta_image_id_to_link( $display_value, $meta, $obj ): string {
		$data = $meta->get_data();
		if ( 'uc_image_url' === $data['key'] && strcasecmp( $display_value, $data['value'] ) === 0 ) {
			return wp_kses_post(
				Render::view_partial(
					'common/thumbnail-image',
					array( 'url' => $display_value )
				)
			);
		}

		return $display_value;
	}
}
