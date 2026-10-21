<?php
/**
 * Replaces the default Dashboard screen with an Ubuntu / GNOME style
 * application grid built from the admin sidebar menu.
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

	/**
	 * Yaru accent colours, used for app icon backgrounds.
	 */
	const PALETTE = array(
		'#e95420', // Orange.
		'#787859', // Bark.
		'#657b69', // Sage.
		'#4b8501', // Olive.
		'#03875b', // Viridian.
		'#308280', // Prussian green.
		'#0073e5', // Blue.
		'#7764d8', // Purple.
		'#b34cb3', // Magenta.
		'#da3450', // Red.
	);

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
		return $classes . ' wpskin-admin--dashboard';
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'wpskin-dashboard',
			WPSKIN_URL . 'assets/css/dashboard.css',
			array( 'dashicons' ),
			WPSKIN_VERSION
		);

		wp_enqueue_script(
			'wpskin-dashboard',
			WPSKIN_URL . 'assets/js/dashboard.js',
			array(),
			WPSKIN_VERSION,
			true
		);
	}

	private function render() {
		$cards = $this->get_cards();
		?>
		<div class="wrap wpskin-dashboard">
			<h1 class="wpskin-dashboard__title screen-reader-text"><?php esc_html_e( 'Dashboard', 'wpskin' ); ?></h1>

			<div class="wpskin-dashboard__search wpskin-search" role="search">
				<span class="wpskin-search__icon dashicons dashicons-search" aria-hidden="true"></span>
				<input
					type="search"
					class="wpskin-search__input"
					placeholder="<?php esc_attr_e( 'Type to search', 'wpskin' ); ?>"
					aria-label="<?php esc_attr_e( 'Search admin pages', 'wpskin' ); ?>"
					autocomplete="off"
				/>
			</div>

			<div class="wpskin-dashboard__cards wpskin-cards">
				<?php
				foreach ( $cards as $card ) {
					if ( count( $card['children'] ) > 1 ) {
						$this->render_folder_card( $card );
					} else {
						$this->render_card( $card, 'wpskin-cards__item' );
					}
				}
				?>
			</div>

			<div class="wpskin-dashboard__results wpskin-cards wpskin-cards--results" hidden>
				<?php
				foreach ( $cards as $card ) {
					if ( count( $card['children'] ) > 1 ) {
						foreach ( $card['children'] as $child ) {
							$this->render_card( $child, 'wpskin-cards__item', $card['name'] );
						}
					} else {
						$this->render_card( $card, 'wpskin-cards__item' );
					}
				}
				?>
			</div>

			<p class="wpskin-dashboard__no-results" hidden><?php esc_html_e( 'No results found.', 'wpskin' ); ?></p>

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
			class="<?php echo esc_attr( $classes ); ?> wpskin-card"
			href="<?php echo esc_url( $card['url'] ); ?>"
			style="--wpskin-card-color: <?php echo esc_attr( $card['color'] ); ?>"
			data-search="<?php echo esc_attr( $search ); ?>"
		>
			<span class="wpskin-card__icon">
				<?php $this->render_glyph( $card['icon'], 'wpskin-card__glyph' ); ?>
				<?php $this->render_badge( $card['count'] ); ?>
			</span>
			<span class="wpskin-card__name"><?php echo esc_html( $card['name'] ); ?></span>
			<?php if ( $context ) : ?>
				<span class="wpskin-card__context"><?php echo esc_html( $context ); ?></span>
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
			class="wpskin-cards__item wpskin-card wpskin-card--folder"
			aria-haspopup="dialog"
			aria-controls="<?php echo esc_attr( $this->folder_id( $card ) ); ?>"
		>
			<span class="wpskin-card__icon wpskin-card__icon--folder">
				<?php foreach ( array_slice( $card['children'], 0, 4 ) as $child ) : ?>
					<span class="wpskin-card__preview" style="--wpskin-card-color: <?php echo esc_attr( $child['color'] ); ?>">
						<?php $this->render_glyph( $child['icon'], 'wpskin-card__preview-glyph' ); ?>
					</span>
				<?php endforeach; ?>
				<?php $this->render_badge( $card['count'] ); ?>
			</span>
			<span class="wpskin-card__name"><?php echo esc_html( $card['name'] ); ?></span>
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
		<div class="wpskin-folder" id="<?php echo esc_attr( $id ); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $id ); ?>-title" hidden>
			<div class="wpskin-folder__backdrop"></div>
			<div class="wpskin-folder__dialog">
				<h2 class="wpskin-folder__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $card['name'] ); ?></h2>
				<div class="wpskin-folder__cards wpskin-cards wpskin-cards--folder">
					<?php
					foreach ( $card['children'] as $child ) {
						$this->render_card( $child, 'wpskin-cards__item' );
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	private function folder_id( array $card ) {
		return 'wpskin-folder-' . $card['id'];
	}

	private function render_badge( $count ) {
		if ( ! $count ) {
			return;
		}
		printf( '<span class="wpskin-card__badge">%s</span>', esc_html( number_format_i18n( $count ) ) );
	}

	/**
	 * Outputs a menu icon as a white glyph: dashicon, SVG data URI, image URL, or fallback.
	 *
	 * @param string $icon  Icon value from the menu item.
	 * @param string $class Element class.
	 */
	private function render_glyph( $icon, $class ) {
		if ( 0 === strpos( $icon, 'data:image/svg' ) ) {
			printf( '<span class="%1$s %1$s--svg" style="background-image:url(\'%2$s\')" aria-hidden="true"></span>', esc_attr( $class ), esc_attr( $icon ) );
			return;
		}

		if ( 0 === strpos( $icon, 'data:image' ) || preg_match( '#^https?://#', $icon ) ) {
			printf( '<span class="%1$s %1$s--image" style="background-image:url(\'%2$s\')" aria-hidden="true"></span>', esc_attr( $class ), esc_attr( $icon ) );
			return;
		}

		$dashicon = 0 === strpos( $icon, 'dashicons-' ) ? $icon : 'dashicons-admin-generic';
		printf( '<span class="%s dashicons %s" aria-hidden="true"></span>', esc_attr( $class ), esc_attr( $dashicon ) );
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

			$icon     = isset( $item[6] ) ? $item[6] : '';
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
						'icon'  => $icon,
						'color' => $this->color_for( $slug . '|' . $sub[2] ),
						'count' => $this->extract_count( $sub[0] ),
					);
				}
			}

			// Some bubbles (e.g. Site Health) live only on a submenu item; fall back to their total.
			$count = $this->extract_count( $item[0] );
			if ( ! $count ) {
				$count = array_sum( wp_list_pluck( $children, 'count' ) );
			}

			$cards[] = array(
				'id'       => sanitize_html_class( sanitize_title( $slug ) ),
				'name'     => $this->clean_title( $item[0] ),
				'url'      => $children ? $children[0]['url'] : $this->menu_url( $slug, 'admin.php' ),
				'icon'     => $icon,
				'color'    => $this->color_for( $slug ),
				'count'    => $count,
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
	 * Picks a stable palette colour for a menu slug.
	 *
	 * @param string $key Menu slug.
	 * @return string Hex colour.
	 */
	private function color_for( $key ) {
		return self::PALETTE[ abs( crc32( $key ) ) % count( self::PALETTE ) ];
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
}
