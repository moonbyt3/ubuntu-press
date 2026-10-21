<?php
/**
 * Replaces the default Dashboard screen with a grid of large cards
 * built from the admin sidebar menu.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DashboardCards implements Hookable {

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'load-index.php', array( $this, 'take_over' ) );
	}

	/**
	 * Renders the card screen in place of wp-admin/index.php.
	 */
	public function take_over() {
		if ( ! Settings::get( 'dashboard_cards' ) || is_network_admin() || is_user_admin() ) {
			return;
		}

		global $title, $parent_file, $submenu_file;

		$title        = __( 'Dashboard', 'wpskin' );
		$parent_file  = 'index.php';
		$submenu_file = 'index.php';

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		require_once ABSPATH . 'wp-admin/admin-header.php';
		$this->render();
		require_once ABSPATH . 'wp-admin/admin-footer.php';
		exit;
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'wpskin-dashboard',
			WPSKIN_URL . 'assets/css/dashboard.css',
			array( 'dashicons' ),
			WPSKIN_VERSION
		);
	}

	private function render() {
		$user  = wp_get_current_user();
		$cards = $this->get_cards();
		?>
		<div class="wrap wpskin-dashboard">
			<h1 class="wpskin-dashboard__title">
				<?php
				/* translators: %s: user display name. */
				printf( esc_html__( 'Welcome, %s', 'wpskin' ), esc_html( $user->display_name ) );
				?>
			</h1>

			<div class="wpskin-dashboard__cards wpskin-cards">
				<?php foreach ( $cards as $card ) : ?>
					<div class="wpskin-cards__item wpskin-card">
						<a class="wpskin-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
							<?php $this->render_icon( $card['icon'] ); ?>
							<span class="wpskin-card__name"><?php echo esc_html( $card['name'] ); ?></span>
							<?php if ( $card['count'] ) : ?>
								<span class="wpskin-card__badge"><?php echo esc_html( number_format_i18n( $card['count'] ) ); ?></span>
							<?php endif; ?>
						</a>

						<?php if ( count( $card['children'] ) > 1 ) : ?>
							<ul class="wpskin-card__submenu">
								<?php foreach ( $card['children'] as $child ) : ?>
									<li class="wpskin-card__submenu-item">
										<a class="wpskin-card__submenu-link" href="<?php echo esc_url( $child['url'] ); ?>">
											<?php echo esc_html( $child['name'] ); ?>
											<?php if ( $child['count'] ) : ?>
												<span class="wpskin-card__badge wpskin-card__badge--small"><?php echo esc_html( number_format_i18n( $child['count'] ) ); ?></span>
											<?php endif; ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Builds card data from the global admin menu, filtered by user capabilities.
	 *
	 * @return array[]
	 */
	private function get_cards() {
		global $menu, $submenu;

		$cards = array();

		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';

			if ( '' === $slug || 'index.php' === $slug || false !== strpos( $item[4], 'wp-menu-separator' ) ) {
				continue;
			}
			if ( ! current_user_can( $item[1] ) ) {
				continue;
			}

			$children = array();
			if ( ! empty( $submenu[ $slug ] ) ) {
				foreach ( $submenu[ $slug ] as $sub ) {
					$name = $this->clean_title( $sub[0] );
					if ( '' === $name || ! current_user_can( $sub[1] ) ) {
						continue;
					}
					$children[] = array(
						'name'  => $name,
						'url'   => $this->menu_url( $sub[2], $slug ),
						'count' => $this->extract_count( $sub[0] ),
					);
				}
			}

			$cards[] = array(
				'name'     => $this->clean_title( $item[0] ),
				'url'      => $children ? $children[0]['url'] : $this->menu_url( $slug, 'admin.php' ),
				'icon'     => isset( $item[6] ) ? $item[6] : '',
				'count'    => $this->extract_count( $item[0] ),
				'children' => $children,
			);
		}

		/**
		 * Filters the cards shown on the WPSkin dashboard.
		 *
		 * @param array[] $cards Card data.
		 */
		return apply_filters( 'wpskin_dashboard_cards', $cards );
	}

	/**
	 * Resolves a menu slug to a URL, mirroring wp-admin/menu-header.php.
	 *
	 * @param string $slug   Menu or submenu slug.
	 * @param string $parent Parent slug.
	 * @return string
	 */
	private function menu_url( $slug, $parent ) {
		if ( preg_match( '#^https?://#', $slug ) ) {
			return $slug;
		}

		$file           = $this->strip_query( $slug );
		$is_plugin_page = get_plugin_page_hook( $slug, $parent )
			|| ( 'index.php' !== $slug && file_exists( WP_PLUGIN_DIR . "/$file" ) && ! file_exists( ABSPATH . "wp-admin/$file" ) );

		if ( ! $is_plugin_page ) {
			return admin_url( $slug );
		}

		$parent_file = $this->strip_query( $parent );
		if ( $parent !== $slug && 'admin.php' !== $parent_file
			&& file_exists( ABSPATH . "wp-admin/$parent_file" ) && ! file_exists( WP_PLUGIN_DIR . "/$parent_file" ) ) {
			return add_query_arg( 'page', $slug, admin_url( $parent ) );
		}

		return add_query_arg( 'page', $slug, admin_url( 'admin.php' ) );
	}

	private function strip_query( $slug ) {
		$pos = strpos( $slug, '?' );
		return false === $pos ? $slug : substr( $slug, 0, $pos );
	}

	/**
	 * Removes notification bubbles and markup from a menu title.
	 *
	 * @param string $title Raw menu title.
	 * @return string
	 */
	private function clean_title( $title ) {
		$name = trim( wp_strip_all_tags( preg_replace( '/<span.*$/s', '', $title ) ) );
		return '' !== $name ? $name : trim( wp_strip_all_tags( $title ) );
	}

	/**
	 * Pulls the number out of a "count-N" notification bubble.
	 *
	 * @param string $title Raw menu title.
	 * @return int
	 */
	private function extract_count( $title ) {
		return preg_match( '/count-(\d+)/', $title, $m ) ? (int) $m[1] : 0;
	}

	/**
	 * Outputs a menu icon: dashicon class, data URI / URL image, or fallback.
	 *
	 * @param string $icon Icon value from the menu item.
	 */
	private function render_icon( $icon ) {
		if ( 0 === strpos( $icon, 'data:image' ) || preg_match( '#^https?://#', $icon ) ) {
			printf( '<span class="wpskin-card__icon wpskin-card__icon--image" style="background-image:url(\'%s\')"></span>', esc_attr( $icon ) );
			return;
		}

		$class = 0 === strpos( $icon, 'dashicons-' ) ? $icon : 'dashicons-admin-generic';
		printf( '<span class="wpskin-card__icon dashicons %s" aria-hidden="true"></span>', esc_attr( $class ) );
	}
}
