<?php
/**
 * Light / dark colour theme for the admin.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Admin;

use UbuntuPress\Contracts\Hookable;
use UbuntuPress\Loader;
use UbuntuPress\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Theme implements Hookable {

	const MODES = array( 'light', 'dark' );

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas_assets' ) );
		// After core's Customizer styles (nav menus, widgets) are enqueued at the default priority.
		$loader->add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customize_assets' ), 20 );
		$loader->add_action( 'customize_controls_head', array( $this, 'print_customize_body_class' ) );
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
		return self::is_dark() ? $classes . ' ubuntupress-admin--dark' : $classes;
	}

	public function enqueue_assets() {
		if ( ! self::is_dark() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-theme-dark',
			UBUNTUPRESS_URL . 'assets/css/theme-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/theme-dark.css' )
		);

		if ( $this->is_block_editor() ) {
			// Depends on the editor styles so it loads after them and wins at equal specificity.
			wp_enqueue_style(
				'ubuntupress-editor-dark',
				UBUNTUPRESS_URL . 'assets/css/editor-dark.css',
				array( 'ubuntupress-theme-dark', 'wp-components', 'wp-block-editor', 'wp-editor', 'wp-edit-post' ),
				Plugin::asset_version( 'assets/css/editor-dark.css' )
			);
		}
	}

	public function enqueue_customize_assets() {
		if ( ! self::is_dark() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-theme-dark',
			UBUNTUPRESS_URL . 'assets/css/theme-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/theme-dark.css' )
		);

		wp_enqueue_style(
			'ubuntupress-customize-dark',
			UBUNTUPRESS_URL . 'assets/css/customize-dark.css',
			array( 'ubuntupress-theme-dark', 'customize-controls' ),
			Plugin::asset_version( 'assets/css/customize-dark.css' )
		);

		// The block-based widgets editor runs inside the Customizer when it is enabled.
		if ( wp_style_is( 'wp-customize-widgets' ) ) {
			wp_enqueue_style(
				'ubuntupress-editor-dark',
				UBUNTUPRESS_URL . 'assets/css/editor-dark.css',
				array( 'ubuntupress-theme-dark', 'wp-customize-widgets' ),
				Plugin::asset_version( 'assets/css/editor-dark.css' )
			);
		}
	}

	/**
	 * customize.php has no body class filter, so the class is added as soon as <body> is parsed,
	 * before the first paint.
	 */
	public function print_customize_body_class() {
		if ( ! self::is_dark() ) {
			return;
		}
		?>
		<script>
		new MutationObserver( function ( mutations, observer ) {
			if ( document.body ) {
				document.body.classList.add( 'ubuntupress-admin--dark' );
				observer.disconnect();
			}
		} ).observe( document.documentElement, { childList: true } );
		</script>
		<?php
	}

	/**
	 * Darkens the editor canvas. This hook also runs inside the editor iframe and on the frontend.
	 */
	public function enqueue_canvas_assets() {
		if ( ! is_admin() || ! self::is_dark() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-editor-canvas-dark',
			UBUNTUPRESS_URL . 'assets/css/editor-canvas-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/editor-canvas-dark.css' )
		);
	}
}
