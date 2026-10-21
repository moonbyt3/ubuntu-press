<?php
/**
 * Runs on plugin deactivation.
 *
 * @package WPSkin
 */

namespace WPSkin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
