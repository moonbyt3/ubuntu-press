<?php
/**
 * Light / dark colour theme for the admin.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;
use WPSkin\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Theme implements Hookable {

	const MODES = array( 'light', 'dark' );

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas_assets' ) );
	}

	/**
	 * @return bool
	 */
	public static function is_dark() {
		return 'dark' === Settings::get( 'theme' );
	}

	/**
	 * @return bool
	 */
	private function is_block_editor() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && $screen->is_block_editor();
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
			Plugin::asset_version( 'assets/css/theme-dark.css' )
		);

		if ( $this->is_block_editor() ) {
			// Depends on the editor styles so it loads after them and wins at equal specificity.
			wp_enqueue_style(
				'wpskin-editor-dark',
				WPSKIN_URL . 'assets/css/editor-dark.css',
				array( 'wpskin-theme-dark', 'wp-components', 'wp-block-editor', 'wp-editor', 'wp-edit-post' ),
				Plugin::asset_version( 'assets/css/editor-dark.css' )
			);
		}
	}

	/**
	 * Darkens the editor canvas. This hook also runs inside the editor iframe and on the frontend.
	 */
	public function enqueue_canvas_assets() {
		if ( ! is_admin() || ! self::is_dark() ) {
			return;
		}

		wp_enqueue_style(
			'wpskin-editor-canvas-dark',
			WPSKIN_URL . 'assets/css/editor-canvas-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/editor-canvas-dark.css' )
		);
	}
}
