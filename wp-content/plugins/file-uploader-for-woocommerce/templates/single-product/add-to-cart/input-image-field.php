<?php
/**
 * Input field template
 *
 * @package wcu\FileUploader
 * @var OptionsModel $options
 */

use wcu\JsonApi\ImageJsonApi;
use wcu\Models\OptionsModel;

$options = gprop()->get_property( OptionsModel::PLUGIN_OPTIONS_NAME );

?>
<style>
	.wcu-uploader {
		margin: 10px 10px 30px 0;
	}

	.wcu-uploader label {
		width: 100%;
	}

	button.uploadcare--widget__button {
		cursor: pointer;
	}

	.uploadcare--powered-by {
		display: none;
	}

	#wcu-image img {
		max-height: 250px;
		max-width: 250px;
		cursor: pointer;
	}

	.uploadcare--jcrop-holder img {
		max-width: none !important;
	}
</style>

<div class="wcu-uploader">
	<div id="wcu-image"></div>
	<label for="uploader"><?php esc_html_e( 'Upload an image', 'wcu' ); ?></label>
	<input id="uploader" data-effects="crop, rotate, mirror, flip, blur, sharp, enhance, grayscale, invert" data-images-only="true" data-preview-step="true" data-tabs="file camera url facebook gdrive gphotos dropbox instagram evernote flickr onedrive box vk huddle" name="uc-product-images" type="hidden"/>
	<input type="hidden" id="image-uuid" name="image-uuid">
	<input type="hidden" id="image-modification" name="image-modification">
	<input type="hidden" id="image-filename" name="image-filename">
    <?php wp_nonce_field( 'wcu-image', 'wcu_nonce' ); ?>
</div>

<script>
	jQuery(document).ready(function () {
		new WCU({
			apiRoute: '<?php echo ImageJsonApi::get_api_route(); ?>',
			nonce: '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>',
			publicKey: '<?php echo esc_attr( $options->get_public_api_key() ); ?>',
			siteUrl: '<?php echo esc_url_raw( get_bloginfo( 'url' ) ); ?>',
			userId: '<?php echo esc_attr( $options->get_user_id() ); ?>'
		});
	});
</script>
