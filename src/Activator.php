<?php
/**
 * Runs on plugin activation.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress;

use UbuntuPress\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {

	public static function activate() {
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		update_option( 'ubuntupress_version', UBUNTUPRESS_VERSION );

		flush_rewrite_rules();
	}
}
