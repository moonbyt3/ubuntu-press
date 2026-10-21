<?php
/**
 * Light / dark colour theme for the admin.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Theme implements Hookable {

	const MODES = array( 'light', 'dark' );

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Whether the dark theme applies to the current screen.
	 *
	 * The block editor draws its own UI, so it keeps WordPress' styling.
	 *
	 * @return bool
	 */
	public static function is_dark() {
		if ( 'dark' !== Settings::get( 'theme' ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return ! ( $screen && $screen->is_block_editor() );
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return self::is_dark() ? $classes . ' wpskin-admin--dark' : $classes;
	}

	public function enqueue_assets() {
		if ( ! self::is_dark() ) {
			return;
		}

		wp_enqueue_style(
			'wpskin-theme-dark',
			WPSKIN_URL . 'assets/css/theme-dark.css',
			array(),
			WPSKIN_VERSION
		);
	}
}
