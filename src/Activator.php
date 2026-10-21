<?php
/**
 * Runs on plugin activation.
 *
 * @package WPSkin
 */

namespace WPSkin;

use WPSkin\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {

	public static function activate() {
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		update_option( 'wpskin_version', WPSKIN_VERSION );

		flush_rewrite_rules();
	}
}
