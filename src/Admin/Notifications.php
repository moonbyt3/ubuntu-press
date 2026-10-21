<?php
/**
 * Collects admin notices into a toolbar notification popup with a counter.
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
		return $this->is_enabled() ? $classes . ' ubuntupress-admin--notices' : $classes;
	}

	public function enqueue_assets() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-notifications',
			UBUNTUPRESS_URL . 'assets/css/notifications.css',
			array( 'dashicons' ),
			Plugin::asset_version( 'assets/css/notifications.css' )
		);

		// Must load after "common": it relocates every notice on DOM ready.
		wp_enqueue_script(
			'ubuntupress-notifications',
			UBUNTUPRESS_URL . 'assets/js/notifications.js',
			array( 'jquery', 'common' ),
			Plugin::asset_version( 'assets/js/notifications.js' ),
			true
		);

		wp_localize_script(
			'ubuntupress-notifications',
			'ubuntupressNotices',
			array(
				/* translators: %d: number of notifications (always 1). */
				'one'  => __( '%d notification', 'ubuntupress' ),
				/* translators: %d: number of notifications. */
				'many' => __( '%d notifications', 'ubuntupress' ),
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
				'id'     => 'ubuntupress-notices',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-icon ubuntupress-notices-toggle__icon" aria-hidden="true"></span>'
					. '<span class="ubuntupress-notices-toggle__count" hidden>0</span>'
					. '<span class="screen-reader-text">' . esc_html__( 'Notifications', 'ubuntupress' ) . '</span>',
				'href'   => '#ubuntupress-notices-panel',
				'meta'   => array(
					'class' => 'ubuntupress-notices-toggle',
					'title' => __( 'Notifications', 'ubuntupress' ),
				),
			)
		);
	}

	public function render_panel() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?>
		<div id="ubuntupress-notices-panel" class="ubuntupress-notices-panel" role="dialog" aria-labelledby="ubuntupress-notices-title" hidden>
			<div class="ubuntupress-notices-panel__header">
				<h2 id="ubuntupress-notices-title" class="ubuntupress-notices-panel__title"><?php esc_html_e( 'Notifications', 'ubuntupress' ); ?></h2>
				<button type="button" class="ubuntupress-notices-panel__close">
					<span class="ubuntupress-notices-panel__close-icon dashicons dashicons-no-alt" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Close notifications', 'ubuntupress' ); ?></span>
				</button>
			</div>
			<div class="ubuntupress-notices-panel__list"></div>
			<p class="ubuntupress-notices-panel__empty"><?php esc_html_e( 'No notifications.', 'ubuntupress' ); ?></p>
		</div>
		<?php
	}
}
