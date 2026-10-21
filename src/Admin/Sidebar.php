<?php
/**
 * Hides the default admin sidebar and adds a toolbar link back to the card dashboard.
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

class Sidebar implements Hookable {

	const MODES = array( 'everywhere', 'dashboard', 'never' );

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'admin_bar_menu', array( $this, 'toolbar_link' ), 5 );
	}

	/**
	 * Whether the sidebar should be hidden on the current screen.
	 *
	 * Never hides it when the card dashboard is off, otherwise users would be left without navigation.
	 *
	 * @return bool
	 */
	private function should_hide() {
		if ( ! Settings::get( 'dashboard_cards' ) || is_network_admin() || is_user_admin() ) {
			return false;
		}

		$mode = Settings::get( 'hide_sidebar' );

		if ( 'everywhere' === $mode ) {
			return true;
		}

		if ( 'dashboard' === $mode ) {
			$screen = get_current_screen();
			return $screen && 'dashboard' === $screen->id;
		}

		return false;
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return $this->should_hide() ? $classes . ' ubuntupress-admin--no-sidebar' : $classes;
	}

	public function enqueue_assets() {
		if ( ! $this->should_hide() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-sidebar',
			UBUNTUPRESS_URL . 'assets/css/sidebar.css',
			array(),
			Plugin::asset_version( 'assets/css/sidebar.css' )
		);
	}

	/**
	 * Adds a "Dashboard" link as the first toolbar item when the sidebar is hidden.
	 *
	 * @param \WP_Admin_Bar $admin_bar Toolbar instance.
	 */
	public function toolbar_link( $admin_bar ) {
		if ( ! is_admin() || ! $this->should_hide() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'ubuntupress-home',
				'title' => '<span class="ab-icon ubuntupress-home-link__icon" aria-hidden="true"></span><span class="ab-label ubuntupress-home-link__label">' . esc_html__( 'Dashboard', 'ubuntupress' ) . '</span>',
				'href'  => admin_url(),
				'meta'  => array(
					'class' => 'ubuntupress-home-link',
					'title' => __( 'Dashboard', 'ubuntupress' ),
				),
			)
		);
	}
}
