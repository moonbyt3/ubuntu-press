<?php
/**
 * Optional Ubuntu (GNOME Settings) styling for WordPress' own Settings screens.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;
use WPSkin\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPages implements Hookable {

	/**
	 * Core screens built from .form-table rows. Privacy has its own layout, plugin pages
	 * under Settings use arbitrary markup, so neither is included.
	 */
	const SCREENS = array(
		'options-general',
		'options-writing',
		'options-reading',
		'options-discussion',
		'options-media',
		'options-permalink',
	);

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * @return bool
	 */
	private function applies() {
		if ( ! Settings::get( 'settings_pages' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && in_array( $screen->base, self::SCREENS, true );
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return $this->applies() ? $classes . ' wpskin-admin--prefs' : $classes;
	}

	public function enqueue_assets() {
		if ( ! $this->applies() ) {
			return;
		}

		// Shared tokens and switch / dropdown styles from the WPSkin settings screen.
		wp_enqueue_style(
			'wpskin-admin',
			WPSKIN_URL . 'assets/css/admin.css',
			array(),
			Plugin::asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_style(
			'wpskin-settings-pages',
			WPSKIN_URL . 'assets/css/settings-pages.css',
			array( 'wpskin-admin' ),
			Plugin::asset_version( 'assets/css/settings-pages.css' )
		);
	}
}
