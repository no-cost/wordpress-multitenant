<?php
/**
 * Class BlocksFreeInitialization
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes;

use RuntimeException;
use Throwable;
use wcu\Components\Render;
use wcu\Models\OptionsModel;

/**
 * Class BlockInitialization
 */
class Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * Add actions
	 *
	 * @return void
	 */
	public function add_actions(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	public function register_blocks(): void {
		wp_register_script(
			'wcu-free-woocommerce-file-uploader-editor-script',
			WCU_URL . '/dist/js/fileUploaderBlock.min.js',
			[ 'wp-blocks', 'wp-element', 'wp-editor' ],
			filemtime( WCU_DIR . '/dist/js/fileUploaderBlock.min.js' )
		);

		register_block_type( WCU_DIR . '/blocks/file-uploader-block', [
			'editor_script'   => 'wcu-free-woocommerce-file-uploader-editor-script',
			'style'           => 'wcu-free-woocommerce-file-uploader-style',
			'uses_context'    => [ 'woocommerce/productId' ],
			'render_callback' => array( $this, 'file_uploader_block_render' ),
		] );
	}

	/**
	 * Render upload file button
	 *
	 * @param $attributes
	 * @param $content
	 * @param $block
	 *
	 * @return string
	 */
	public function file_uploader_block_render( $attributes, $content, $block ): string {
		try {

			$pluginInitialization = gprop()->get_property( 'pluginInitialization' );
			if ( ! $pluginInitialization instanceof PluginInitialization ) {
				throw new RuntimeException( 'PluginInitialization not found' );
			}

			$product_id = $block->context['postId'] ?? null;
			if ( ! $product_id ) {
				throw new RuntimeException( 'Product ID not found in context' );
			}

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				throw new RuntimeException( 'Product not found by ID ' . $product_id );
			}

			if ( $pluginInitialization->is_allowed_button( $product_id ) ) {
				wp_enqueue_script( 'wcu_pro-main-script', WCU_URL . '/dist/js/main.min.js', array(), null, true );

				$options = new OptionsModel();

				return Render::view_partial(
					'single-product/add-to-cart/input-image-field',
					array(
						'options'    => $options,
						'attributes' => $attributes
					),
				);
			}

			return '';
		} catch ( Throwable $tw ) {
			// TODO: Add logs
			return 'File Uploader: Block error';
		}
	}
}
