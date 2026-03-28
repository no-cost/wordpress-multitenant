<?php
/**
 * Class Render
 *
 * @package wcu\FileUploader
 */

namespace wcu\Components;

/**
 * Class Render
 *
 * @package wcu\Components
 */
class Render {

	/**
	 * Template folder
	 *
	 * @var string - template folder.
	 */
	protected static string $template_folder = 'templates/';

	/**
	 * Echo view file with variables
	 *
	 * @param  string $file - file.
	 * @param  array  $variables - variables.
	 */
	public static function view( $file, array $variables = array() ) {
		echo self::view_partial( $file, $variables );
	}

	/**
	 * Return view file with variables as string
	 *
	 * @param  string $file - file path.
	 * @param  array  $variables - variables.
	 *
	 * @return false|string
	 */
	public static function view_partial( $file, array $variables = array() ) {
		extract( $variables );
		ob_start();

		$template = WCU_DIR . self::$template_folder . $file;
		$file_end = substr( $template, - 4 );
		if ( strcasecmp( $file_end, '.php' ) !== 0 ) {
			$template .= '.php';
		}
		if ( file_exists( $template ) ) {
			include $template;
		}

		return ob_get_clean();
	}
}
