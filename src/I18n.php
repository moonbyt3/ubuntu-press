<?php
/**
 * Loads the plugin text domain.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress;

use UbuntuPress\Contracts\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I18n implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ubuntupress', false, dirname( UBUNTUPRESS_BASENAME ) . '/languages' );
	}
}
