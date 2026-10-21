<?php
/**
 * Public-facing behaviour.
 *
 * @package WPSkin
 */

namespace WPSkin\Frontend;

use WPSkin\Admin\Settings;
use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		if ( ! Settings::get( 'enabled' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpskin-frontend',
			WPSKIN_URL . 'assets/css/frontend.css',
			array(),
			WPSKIN_VERSION
		);

		wp_enqueue_script(
			'wpskin-frontend',
			WPSKIN_URL . 'assets/js/frontend.js',
			array(),
			WPSKIN_VERSION,
			true
		);
	}
}
