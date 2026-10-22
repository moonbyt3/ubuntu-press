<?php
/**
 * Replaces the default Dashboard screen with an Ubuntu / GNOME style
 * application grid built from the admin sidebar menu.
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

class DashboardCards implements Hookable {

	/**
	 * Yaru accent colours, used for app icon backgrounds.
	 */
	const PALETTE = AdminMenu::PALETTE;

	/**
	 * Query arg that shows the default WordPress Dashboard instead of the cards.
	 */
	const CLASSIC_ARG = 'ubuntupress_classic';

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'load-index.php', array( $this, 'take_over' ) );
	}

	/**
	 * Renders the card screen in place of wp-admin/index.php.
	 */
	public function take_over() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view switch.
		if ( ! Settings::get( 'dashboard_cards' ) || is_network_admin() || is_user_admin() || isset( $_GET[ self::CLASSIC_ARG ] ) ) {
			return;
		}

		global $title, $parent_file, $submenu_file;

		$title        = __( 'Dashboard', 'ubuntupress' );
		$parent_file  = 'index.php';
		$submenu_file = 'index.php';

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );

		require_once ABSPATH . 'wp-admin/admin-header.php';
		$this->render();
		require_once ABSPATH . 'wp-admin/admin-footer.php';
		exit;
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$classes .= ' ubuntupress-admin--dashboard';
		return Background::current()['image'] ? $classes . ' ubuntupress-admin--background-image' : $classes;
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'ubuntupress-dashboard',
			UBUNTUPRESS_URL . 'assets/css/dashboard.css',
			array( 'dashicons' ),
			Plugin::asset_version( 'assets/css/dashboard.css' )
		);
		wp_add_inline_style( 'ubuntupress-dashboard', Background::css() );

		wp_enqueue_script(
			'ubuntupress-dashboard',
			UBUNTUPRESS_URL . 'assets/js/dashboard.js',
			array(),
			Plugin::asset_version( 'assets/js/dashboard.js' ),
			true
		);
	}

	private function render() {
		$cards = $this->get_cards();
		?>
		<div class="wrap ubuntupress-dashboard">
			<h1 class="ubuntupress-dashboard__title screen-reader-text"><?php esc_html_e( 'Dashboard', 'ubuntupress' ); ?></h1>

			<div class="ubuntupress-dashboard__search ubuntupress-search" role="search">
				<span class="ubuntupress-search__icon dashicons dashicons-search" aria-hidden="true"></span>
				<input
					type="search"
					class="ubuntupress-search__input"
					placeholder="<?php esc_attr_e( 'Type to search', 'ubuntupress' ); ?>"
					aria-label="<?php esc_attr_e( 'Search admin pages', 'ubuntupress' ); ?>"
					autocomplete="off"
				/>
			</div>

			<div class="ubuntupress-dashboard__cards ubuntupress-cards">
				<?php
				foreach ( $cards as $card ) {
					if ( count( $card['children'] ) > 1 ) {
						$this->render_folder_card( $card );
					} else {
						$this->render_card( $card, 'ubuntupress-cards__item' );
					}
				}
				?>
			</div>

			<div class="ubuntupress-dashboard__results ubuntupress-cards ubuntupress-cards--results" hidden>
				<?php
				foreach ( $cards as $card ) {
					if ( count( $card['children'] ) > 1 ) {
						foreach ( $card['children'] as $child ) {
							$this->render_card( $child, 'ubuntupress-cards__item', $card['name'] );
						}
					} else {
						$this->render_card( $card, 'ubuntupress-cards__item' );
					}
				}
				?>
			</div>

			<p class="ubuntupress-dashboard__no-results" hidden><?php esc_html_e( 'No results found.', 'ubuntupress' ); ?></p>

			<?php
			foreach ( $cards as $card ) {
				if ( count( $card['children'] ) > 1 ) {
					$this->render_folder_dialog( $card );
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Outputs a single app: coloured icon, label and optional badge.
	 *
	 * @param array       $card    Card data.
	 * @param string      $classes Extra classes (BEM mixes).
	 * @param string|null $context Parent menu name, shown in search results.
	 */
	private function render_card( array $card, $classes, $context = null ) {
		$search = strtolower( $card['name'] . ' ' . (string) $context );
		?>
		<a
			class="<?php echo esc_attr( $classes ); ?> ubuntupress-card"
			href="<?php echo esc_url( $card['url'] ); ?>"
			style="--ubuntupress-card-color: <?php echo esc_attr( $card['color'] ); ?>"
			data-search="<?php echo esc_attr( $search ); ?>"
		>
			<span class="ubuntupress-card__icon">
				<?php AdminMenu::render_glyph( $card['icon'], 'ubuntupress-card__glyph' ); ?>
				<?php $this->render_badge( $card['count'] ); ?>
			</span>
			<span class="ubuntupress-card__name"><?php echo esc_html( $card['name'] ); ?></span>
			<?php if ( $context ) : ?>
				<span class="ubuntupress-card__context"><?php echo esc_html( $context ); ?></span>
			<?php endif; ?>
		</a>
		<?php
	}

	/**
	 * Outputs a GNOME-style app folder tile with a 2x2 preview of its contents.
	 *
	 * @param array $card Card data.
	 */
	private function render_folder_card( array $card ) {
		?>
		<button
			type="button"
			class="ubuntupress-cards__item ubuntupress-card ubuntupress-card--folder"
			aria-haspopup="dialog"
			aria-controls="<?php echo esc_attr( $this->folder_id( $card ) ); ?>"
		>
			<span class="ubuntupress-card__icon ubuntupress-card__icon--folder">
				<?php foreach ( array_slice( $card['children'], 0, 4 ) as $child ) : ?>
					<span class="ubuntupress-card__preview" style="--ubuntupress-card-color: <?php echo esc_attr( $child['color'] ); ?>">
						<?php AdminMenu::render_glyph( $child['icon'], 'ubuntupress-card__preview-glyph' ); ?>
					</span>
				<?php endforeach; ?>
				<?php $this->render_badge( $card['count'] ); ?>
			</span>
			<span class="ubuntupress-card__name"><?php echo esc_html( $card['name'] ); ?></span>
		</button>
		<?php
	}

	/**
	 * Outputs the popup that opens when a folder is clicked.
	 *
	 * @param array $card Card data.
	 */
	private function render_folder_dialog( array $card ) {
		$id = $this->folder_id( $card );
		?>
		<div class="ubuntupress-folder" id="<?php echo esc_attr( $id ); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $id ); ?>-title" hidden>
			<div class="ubuntupress-folder__backdrop"></div>
			<div class="ubuntupress-folder__dialog">
				<h2 class="ubuntupress-folder__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $card['name'] ); ?></h2>
				<div class="ubuntupress-folder__cards ubuntupress-cards ubuntupress-cards--folder">
					<?php
					foreach ( $card['children'] as $child ) {
						$this->render_card( $child, 'ubuntupress-cards__item' );
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	private function folder_id( array $card ) {
		return 'ubuntupress-folder-' . $card['id'];
	}

	private function render_badge( $count ) {
		if ( ! $count ) {
			return;
		}
		printf( '<span class="ubuntupress-card__badge">%s</span>', esc_html( number_format_i18n( $count ) ) );
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

			// UbuntuPress's own menu item is skipped: settings_card() adds it last instead.
			if ( '' === $slug || 'index.php' === $slug || Settings::PAGE === $slug || false !== strpos( $item[4], 'wp-menu-separator' ) ) {
				continue;
			}
			if ( ! current_user_can( $item[1] ) ) {
				continue;
			}

			$icon       = isset( $item[6] ) ? $item[6] : '';
			$children   = array();
			$self_index = 0;

			if ( ! empty( $submenu[ $slug ] ) ) {
				foreach ( $submenu[ $slug ] as $sub ) {
					$name = AdminMenu::clean_title( $sub[0] );
					if ( '' === $name || ! current_user_can( $sub[1] ) ) {
						continue;
					}
					if ( $sub[2] === $slug ) {
						$self_index = count( $children );
					}
					$children[] = array(
						'name'  => $name,
						'url'   => AdminMenu::menu_url( $sub[2], $slug ),
						'icon'  => $icon,
						'color' => AdminMenu::color_for( $slug . '|' . $sub[2] ),
						'count' => AdminMenu::extract_count( $sub[0] ),
					);
				}
			}

			// Some bubbles (e.g. Site Health) live only on a submenu item; fall back to their total.
			$count       = AdminMenu::extract_count( $item[0] );
			$child_count = array_sum( wp_list_pluck( $children, 'count' ) );
			if ( ! $count ) {
				$count = $child_count;
			} elseif ( $children && ! $child_count ) {
				// Others (e.g. Plugins, Comments) live only on the top-level item; show them on the
				// child that opens the same page so the folder's badge can be traced inside it.
				$children[ $self_index ]['count'] = $count;
			}

			$cards[] = array(
				'id'       => sanitize_html_class( sanitize_title( $slug ) ),
				'name'     => AdminMenu::clean_title( $item[0] ),
				'url'      => $children ? $children[0]['url'] : AdminMenu::menu_url( $slug, 'admin.php' ),
				'icon'     => $icon,
				'color'    => AdminMenu::color_for( $slug ),
				'count'    => $count,
				'children' => $children,
			);
		}

		/**
		 * Filters the cards shown on the UbuntuPress dashboard.
		 *
		 * @param array[] $cards Card data.
		 */
		$cards = apply_filters( 'ubuntupress_dashboard_cards', $cards );

		// Added after the filter so these shortcuts always stay last.
		$cards[] = $this->classic_dashboard_card();

		if ( current_user_can( 'manage_options' ) ) {
			$cards[] = $this->settings_card();
		}

		return $cards;
	}

	/**
	 * Shortcut card to the default WordPress Dashboard that the cards replace.
	 *
	 * @return array
	 */
	private function classic_dashboard_card() {
		return array(
			'id'       => 'wordpress-dashboard',
			'name'     => __( 'WordPress Dashboard', 'ubuntupress' ),
			'url'      => add_query_arg( self::CLASSIC_ARG, '1', admin_url( 'index.php' ) ),
			'icon'     => 'dashicons-dashboard',
			'color'    => self::PALETTE[6],
			'count'    => 0,
			'children' => array(),
		);
	}

	/**
	 * Shortcut card to the UbuntuPress settings page.
	 *
	 * @return array
	 */
	private function settings_card() {
		return array(
			'id'       => 'ubuntupress-settings',
			'name'     => __( 'UbuntuPress', 'ubuntupress' ),
			'url'      => Settings::url(),
			'icon'     => 'dashicons-admin-appearance',
			'color'    => self::PALETTE[0],
			'count'    => 0,
			'children' => array(),
		);
	}
}
