<?php
/**
 * Class JsonApi
 *
 * @package wcu\FileUploader
 */

namespace wcu\JsonApi;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Class AbstractJsonApi
 */
abstract class AbstractJsonApi {

	/**
	 * Namespace. By default it equal /v1/
	 *
	 * @var string
	 */
	protected string $namespace = 'v1';
	/**
	 * Route to endpoint. Without trailing slash
	 *
	 * @var string
	 */
	private string $route;

	/**
	 * Supported methods. Read \WP_REST_Server for more information
	 *
	 * @var string
	 */
	protected string $methods = WP_REST_Server::ALLMETHODS;

	/**
	 * AbstractJsonApi constructor.
	 */
	public function __construct() {
		$this->route = $this->set_route();

		if ( ! $this->route ) {
			wp_die( 'Incorrect register json api: route missing' );
		} elseif ( ! $this->namespace ) {
			wp_die( 'Incorrect register json api: namespace missing' );
		} elseif ( ! $this->methods ) {
			wp_die( 'Incorrect register json api: supported methods missing' );
		}

		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					$this->namespace,
					$this->route,
					array(
						'methods'             => $this->methods,
						'callback'            => array( $this, 'register_rest_route' ),
						'permission_callback' => array( $this, 'check_user_permissions' ),
					)
				);
			}
		);
		$this->init();
	}

	/**
	 * Init method. Use it instead __construct()
	 */
	protected function init(): void {
	}

	/**
	 * Register REST route
	 *
	 * @param WP_REST_Request $request - request instance.
	 *
	 * @return mixed
	 */
	abstract public function register_rest_route( WP_REST_Request $request );

	/**
	 * Check if user has permission to do API action
	 *
	 * @return bool
	 */
	abstract public function check_user_permissions(): bool;

	/**
	 * Set route here. Should be just a string or correct relative url
	 *
	 * @return string
	 */
	abstract protected function set_route(): string;
}

