<?php
/**
 * Class ImageJsonApi
 *
 * @package wcu\FileUploader
 */

namespace wcu\JsonApi;

use wcu\Classes\Helpers\UploaderHelper;
use WP_REST_Request;
use WP_REST_Response;
use Throwable;
use GuzzleHttp\Client;

/**
 * Class ImageJsonApi
 */
class ImageJsonApi extends AbstractJsonApi {

	const ROUTE = 'add-image-data';

	/**
	 * Returns API route
	 *
	 * @return string
	 */
	public static function get_api_route(): string {
		$structure = get_option( 'permalink_structure' );

		if ( $structure ) {
			$url = get_site_url() . '/wp-json/v1/' . self::ROUTE;
		} else {
			$url = get_site_url() . '?rest_route=/v1/' . self::ROUTE;
		}

		return $url;
	}

	/**
	 * Register REST route
	 *
	 * @param WP_REST_Request $request - request instance.
	 *
	 * @return WP_REST_Response
	 */
	public function register_rest_route( WP_REST_Request $request ): WP_REST_Response {
		try {

			$uuid               = sanitize_text_field( wp_unslash( $request->get_param( 'uuid' ) ) );
			$original_file_name = sanitize_text_field( wp_unslash( $request->get_param( 'fileName' ) ) );
			$modifications      = sanitize_text_field( wp_unslash( $request->get_param( 'cdnUrlModifiers' ) ) );

			$url = UploaderHelper::upload_image( $uuid, $original_file_name, $modifications );
			if ( ! $url ) {
				$result = array(
					'status' => false,
				);

				return new WP_REST_Response( $result, 400 );
			}

			$result = array(
				'status'        => true,
				'thumbnail_url' => esc_url( $url . '?cc' . uniqid() ),
				'file_name'     => $original_file_name,
			);

			return new WP_REST_Response( $result, 200 );
		} catch ( Throwable $tw ) {
			$result = array(
				'status'  => 'error',
				'message' => 'Server error',
			);

			return new WP_REST_Response( $result, 500 );
		}
	}

	/**
	 * Set REST API route
	 *
	 * @return string
	 */
	protected function set_route(): string {
		return self::ROUTE;
	}

	/**
	 * Check user permissions
	 *
	 * @return bool
	 */
	public function check_user_permissions(): bool {
		return true; // Allow logged-out users.
	}
}
