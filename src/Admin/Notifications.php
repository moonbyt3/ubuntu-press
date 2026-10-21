<?php
/**
 * Collects admin notices into a toolbar notification popup with a counter.
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

class Notifications implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'admin_bar_menu', array( $this, 'toolbar_button' ), 0 );
		$loader->add_action( 'admin_footer', array( $this, 'render_panel' ) );
	}

	private function is_enabled() {
		return (bool) Settings::get( 'notification_center' );
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return $this->is_enabled() ? $classes . ' wpskin-admin--notices' : $classes;
	}

	public function enqueue_assets() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'wpskin-notifications',
			WPSKIN_URL . 'assets/css/notifications.css',
			array( 'dashicons' ),
			Plugin::asset_version( 'assets/css/notifications.css' )
		);

		// Must load after "common": it relocates every notice on DOM ready.
		wp_enqueue_script(
			'wpskin-notifications',
			WPSKIN_URL . 'assets/js/notifications.js',
			array( 'jquery', 'common' ),
			Plugin::asset_version( 'assets/js/notifications.js' ),
			true
		);

		wp_localize_script(
			'wpskin-notifications',
			'wpskinNotices',
			array(
				/* translators: %d: number of notifications (always 1). */
				'one'  => __( '%d notification', 'wpskin' ),
				/* translators: %d: number of notifications. */
				'many' => __( '%d notifications', 'wpskin' ),
			)
		);
	}

	/**
	 * Adds the bell button to the right side of the toolbar.
	 *
	 * @param \WP_Admin_Bar $admin_bar Toolbar instance.
	 */
	public function toolbar_button( $admin_bar ) {
		if ( ! is_admin() || ! $this->is_enabled() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'     => 'wpskin-notices',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-icon wpskin-notices-toggle__icon" aria-hidden="true"></span>'
					. '<span class="wpskin-notices-toggle__count" hidden>0</span>'
					. '<span class="screen-reader-text">' . esc_html__( 'Notifications', 'wpskin' ) . '</span>',
				'href'   => '#wpskin-notices-panel',
				'meta'   => array(
					'class' => 'wpskin-notices-toggle',
					'title' => __( 'Notifications', 'wpskin' ),
				),
			)
		);
	}

	public function render_panel() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?>
		<div id="wpskin-notices-panel" class="wpskin-notices-panel" role="dialog" aria-labelledby="wpskin-notices-title" hidden>
			<div class="wpskin-notices-panel__header">
				<h2 id="wpskin-notices-title" class="wpskin-notices-panel__title"><?php esc_html_e( 'Notifications', 'wpskin' ); ?></h2>
				<button type="button" class="wpskin-notices-panel__close">
					<span class="wpskin-notices-panel__close-icon dashicons dashicons-no-alt" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Close notifications', 'wpskin' ); ?></span>
				</button>
			</div>
			<div class="wpskin-notices-panel__list"></div>
			<p class="wpskin-notices-panel__empty"><?php esc_html_e( 'No notifications.', 'wpskin' ); ?></p>
		</div>
		<?php
	}
}
