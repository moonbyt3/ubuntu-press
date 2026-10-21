<?php
/**
 * PSR-4 autoloader for the UbuntuPress namespace.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {

	const PREFIX = 'UbuntuPress\\';

	/**
	 * Registers the autoloader with SPL.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Maps UbuntuPress\Foo\Bar to src/Foo/Bar.php.
	 *
	 * @param string $class Fully qualified class name.
	 */
	public static function load( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
