<?php
/**
 * Contract for services that register WordPress hooks.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Contracts;

use UbuntuPress\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Hookable {

	/**
	 * Queues the service's actions and filters on the loader.
	 *
	 * @param Loader $loader Hook loader.
	 */
	public function register_hooks( Loader $loader );
}
