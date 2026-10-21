<?php
/**
 * Loads the plugin text domain.
 *
 * @package WPSkin
 */

namespace WPSkin;

use WPSkin\Contracts\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I18n implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wpskin', false, dirname( WPSKIN_BASENAME ) . '/languages' );
	}
}
