<?php
/**
 * Admin-side behaviour: assets and plugin action links.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Admin;

use UbuntuPress\Contracts\Hookable;
use UbuntuPress\Loader;
use UbuntuPress\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_filter( 'plugin_action_links_' . UBUNTUPRESS_BASENAME, array( $this, 'action_links' ) );
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

		Background::enqueue_media();

		wp_enqueue_style(
			'ubuntupress-admin',
			UBUNTUPRESS_URL . 'assets/css/admin.css',
			array(),
			Plugin::asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			'ubuntupress-admin',
			UBUNTUPRESS_URL . 'assets/js/admin.js',
			array(),
			Plugin::asset_version( 'assets/js/admin.js' ),
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
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( Settings::url() ), esc_html__( 'Settings', 'ubuntupress' ) ) );
		return $links;
	}
}
