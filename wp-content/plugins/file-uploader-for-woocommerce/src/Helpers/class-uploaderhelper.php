<?php
/**
 * Class ProductActions
 *
 * @package wcu\FileUploader
 */

namespace wcu\Classes\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class UploaderHelper
 */
class UploaderHelper {

	/**
	 * Upload image
	 *
	 * @param  string  $uuid  - UC uuid.
	 * @param  string  $original_file_name  - original file name.
	 * @param  string  $modifications  - UC modifications.
	 *
	 * @return void|null
	 * @throws GuzzleException - GuzzleHttp Exception.
	 */
	public static function upload_image(
		string $uuid,
		string $original_file_name,
		string $modifications = ''
	): ?string {
		$client = new Client(
			array( 'base_uri' => 'https://ucarecdn.com' )
		);

		$uuid               = sanitize_text_field( $uuid );
		$original_file_name = sanitize_text_field( $original_file_name );

		$filename_from_url = pathinfo( $original_file_name );
		if ( ! isset( $filename_from_url['extension'] ) || ! $filename_from_url['extension'] ) {
			return null;
		}

		$upload_dir    = wp_get_upload_dir();
		$file_name     = sanitize_text_field( $uuid . '.' . $filename_from_url['extension'] );
		$modifications = sanitize_text_field( $modifications );

		$file_path = $upload_dir['basedir'] . '/file-uploader/' . $file_name;
		if ( is_file( $file_path ) ) {
			unlink( $file_path );
		}

		$client->request(
			'GET',
			esc_url_raw( 'https://ucarecdn.com/' . $uuid . '/' . $modifications ),
			array( 'sink' => $file_path ),
		);

		return $upload_dir['baseurl'] . '/file-uploader/' . $file_name;
	}
}

