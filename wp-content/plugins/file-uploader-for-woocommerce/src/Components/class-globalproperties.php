<?php
/**
 * Class GlobalProperties
 *
 * @package wcu\FileUploader
 */

namespace wcu\Components;

/**
 * Class GlobalProperties - Singleton instead $global variable
 */
final class GlobalProperties {

	/**
	 * Property
	 *
	 * @var array
	 */
	private array $props = array();

	/**
	 * Instance
	 *
	 * @var - instance.
	 */
	private static $instance;

	/**
	 * Get new or exist instance
	 *
	 * @return GlobalProperties
	 */
	public static function get_instance(): GlobalProperties {
		if ( empty( self::$instance ) ) {
			self::$instance = new GlobalProperties();
		}

		return self::$instance;
	}

	/**
	 * Set property
	 *
	 * @param  mixed  $key  - key.
	 * @param  mixed  $value  - value.
	 */
	public function set_property( $key, $value ): void {
		$this->props[ $key ] = $value;
	}

	/**
	 * Get property
	 *
	 * @param  mixed  $key  - key.
	 * @param  bool|null  $default  - default value.
	 *
	 * @return mixed|null
	 */
	public function get_property( $key, bool $default = null ) {
		return ( $this->props[ $key ] ?? $default );
	}
}
