<?php
/**
 * Ubuntu Dock: an auto-hiding app launcher that slides in when the pointer reaches a screen edge.
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

class Dock implements Hookable {

	/**
	 * Screen edges, as in Ubuntu Settings > Ubuntu Desktop > Position on screen.
	 */
	const POSITIONS = array( 'left', 'bottom', 'right' );

	/**
	 * Menu slugs pinned to the dock until the user picks their own.
	 */
	const DEFAULT_ITEMS = array(
		'index.php',
		'edit.php',
		'upload.php',
		'edit.php?post_type=page',
		'edit-comments.php',
		'themes.php',
		'plugins.php',
		'users.php',
		'options-general.php',
	);

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'admin_footer', array( $this, 'render' ) );
	}

	/**
	 * @return bool
	 */
	private function is_enabled() {
		return Settings::get( 'dock' )
			&& ! ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST )
			&& ! is_network_admin()
			&& ! is_user_admin();
	}

	/**
	 * @return string One of POSITIONS.
	 */
	private function position() {
		$position = Settings::get( 'dock_position' );
		return in_array( $position, self::POSITIONS, true ) ? $position : self::POSITIONS[0];
	}

	public function enqueue_assets() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-dock',
			UBUNTUPRESS_URL . 'assets/css/dock.css',
			array( 'dashicons' ),
			Plugin::asset_version( 'assets/css/dock.css' )
		);

		wp_enqueue_script(
			'ubuntupress-dock',
			UBUNTUPRESS_URL . 'assets/js/dock.js',
			array(),
			Plugin::asset_version( 'assets/js/dock.js' ),
			true
		);
	}

	/**
	 * Pinned apps in admin menu order, limited to what the current user can open.
	 *
	 * @return array[]
	 */
	private function items() {
		$pinned = (array) Settings::get( 'dock_items', array() );

		$items = array_values(
			array_filter(
				AdminMenu::items(),
				function ( $item ) use ( $pinned ) {
					return in_array( $item['slug'], $pinned, true );
				}
			)
		);

		/**
		 * Filters the apps shown in the UbuntuPress dock.
		 *
		 * @param array[] $items Items: slug, name, url, icon, color, count.
		 */
		return apply_filters( 'ubuntupress_dock_items', $items );
	}

	/**
	 * Whether a menu item is the one the sidebar would highlight on this screen.
	 *
	 * @param string $slug Top-level menu slug.
	 * @return bool
	 */
	private function is_current( $slug ) {
		global $parent_file, $self;
		return $parent_file ? $slug === $parent_file : $slug === $self;
	}

	public function render() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$items     = $this->items();
		$show_apps = Settings::get( 'dashboard_cards' );

		if ( ! $items && ! $show_apps ) {
			return;
		}

		$position = $this->position();
		$screen   = get_current_screen();
		?>
		<div class="ubuntupress-dock ubuntupress-dock--<?php echo esc_attr( $position ); ?>" data-position="<?php echo esc_attr( $position ); ?>">
			<div class="ubuntupress-dock__edge" aria-hidden="true"></div>
			<div class="ubuntupress-dock__track">
				<nav class="ubuntupress-dock__panel" aria-label="<?php esc_attr_e( 'Dock', 'ubuntupress' ); ?>">
					<ul class="ubuntupress-dock__list">
						<?php foreach ( $items as $item ) : ?>
							<?php $current = $this->is_current( $item['slug'] ); ?>
							<li class="ubuntupress-dock__item">
								<a
									class="ubuntupress-dock__app<?php echo $current ? ' ubuntupress-dock__app--current' : ''; ?>"
									href="<?php echo esc_url( $item['url'] ); ?>"
									data-label="<?php echo esc_attr( $item['name'] ); ?>"
									<?php echo $current ? 'aria-current="page"' : ''; ?>
								>
									<span class="ubuntupress-dock__icon" style="--ubuntupress-dock-color: <?php echo esc_attr( $item['color'] ); ?>">
										<?php AdminMenu::render_glyph( $item['icon'], 'ubuntupress-dock__glyph' ); ?>
										<?php if ( $item['count'] ) : ?>
											<span class="ubuntupress-dock__badge"><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></span>
										<?php endif; ?>
									</span>
									<span class="screen-reader-text"><?php echo esc_html( $item['name'] ); ?></span>
									<span class="ubuntupress-dock__dot" aria-hidden="true"></span>
								</a>
							</li>
						<?php endforeach; ?>

						<?php if ( $show_apps ) : ?>
							<?php $current = $screen && 'dashboard' === $screen->id; ?>
							<li class="ubuntupress-dock__item ubuntupress-dock__item--show-apps">
								<a
									class="ubuntupress-dock__app ubuntupress-dock__app--show-apps<?php echo $current ? ' ubuntupress-dock__app--checked' : ''; ?>"
									href="<?php echo esc_url( admin_url() ); ?>"
									data-label="<?php esc_attr_e( 'Show Apps', 'ubuntupress' ); ?>"
								>
									<span class="ubuntupress-dock__icon ubuntupress-dock__icon--show-apps">
										<svg class="ubuntupress-dock__grid" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
											<?php foreach ( array( 2, 8, 14 ) as $cy ) : ?>
												<?php foreach ( array( 2, 8, 14 ) as $cx ) : ?>
													<circle cx="<?php echo (int) $cx; ?>" cy="<?php echo (int) $cy; ?>" r="1.6" />
												<?php endforeach; ?>
											<?php endforeach; ?>
										</svg>
									</span>
									<span class="screen-reader-text"><?php esc_html_e( 'Show Apps', 'ubuntupress' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</nav>
			</div>
		</div>
		<?php
	}
}
