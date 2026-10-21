<?php
/**
 * Admin-side behaviour: assets and plugin action links.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_filter( 'plugin_action_links_' . WPSKIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Loads admin assets only on the plugin's own screen.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . Settings::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpskin-admin',
			WPSKIN_URL . 'assets/css/admin.css',
			array(),
			WPSKIN_VERSION
		);

		wp_enqueue_script(
			'wpskin-admin',
			WPSKIN_URL . 'assets/js/admin.js',
			array(),
			WPSKIN_VERSION,
			true
		);
	}

	/**
	 * Adds a "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( Settings::url() ), esc_html__( 'Settings', 'wpskin' ) ) );
		return $links;
	}
}
