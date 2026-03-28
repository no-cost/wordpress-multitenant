<?php
/**
 * Global functions
 *
 * @package wcu\FileUploader
 */

use wcu\Components\GlobalProperties;

if ( ! function_exists( 'gprop' ) ) {
	/**
	 * Global properties
	 *
	 * @return GlobalProperties
	 */
	function gprop(): GlobalProperties {
		return GlobalProperties::get_instance();
	}
}
