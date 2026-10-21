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
		// Carry settings over from when the plugin was called WPSkin.
		$legacy = get_option( 'wpskin_settings' );
		if ( false !== $legacy && false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, $legacy );
		}
		delete_option( 'wpskin_settings' );
		delete_option( 'wpskin_version' );

		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		update_option( 'ubuntupress_version', UBUNTUPRESS_VERSION );

		flush_rewrite_rules();
	}
}
