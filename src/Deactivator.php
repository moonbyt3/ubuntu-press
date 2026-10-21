<?php
/**
 * Runs on plugin deactivation.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
