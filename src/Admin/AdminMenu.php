<?php
/**
 * Reads the global admin menu and renders its icons. Shared by the Dashboard cards and the Dock.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {

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

	/**
	 * Top-level menu items the current user can access, separators excluded.
	 *
	 * @return array[] Items: slug, name, url, icon, color, count.
	 */
	public static function items() {
		global $menu;

		$items = array();

		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';

			if ( '' === $slug || false !== strpos( $item[4], 'wp-menu-separator' ) || ! current_user_can( $item[1] ) ) {
				continue;
			}

			$items[] = array(
				'slug'  => $slug,
				'name'  => self::clean_title( $item[0] ),
				'url'   => self::top_level_url( $slug ),
				'icon'  => isset( $item[6] ) ? $item[6] : '',
				'color' => self::color_for( $slug ),
				'count' => self::extract_count( $item[0] ),
			);
		}

		return $items;
	}

	/**
	 * URL a top-level menu item links to: its first accessible submenu page, like the sidebar.
	 *
	 * @param string $slug Top-level menu slug.
	 * @return string
	 */
	public static function top_level_url( $slug ) {
		global $submenu;

		if ( ! empty( $submenu[ $slug ] ) ) {
			foreach ( $submenu[ $slug ] as $sub ) {
				if ( '' !== self::clean_title( $sub[0] ) && current_user_can( $sub[1] ) ) {
					return self::menu_url( $sub[2], $slug );
				}
			}
		}

		return self::menu_url( $slug, 'admin.php' );
	}

	/**
	 * Picks a stable palette colour for a menu slug.
	 *
	 * @param string $key Menu slug.
	 * @return string Hex colour.
	 */
	public static function color_for( $key ) {
		return self::PALETTE[ abs( crc32( $key ) ) % count( self::PALETTE ) ];
	}

	/**
	 * Resolves a menu slug to a URL, mirroring wp-admin/menu-header.php.
	 *
	 * @param string $slug   Menu or submenu slug.
	 * @param string $parent Parent slug.
	 * @return string
	 */
	public static function menu_url( $slug, $parent ) {
		if ( preg_match( '#^https?://#', $slug ) ) {
			return $slug;
		}

		$file           = self::strip_query( $slug );
		$is_plugin_page = get_plugin_page_hook( $slug, $parent )
			|| ( 'index.php' !== $slug && file_exists( WP_PLUGIN_DIR . "/$file" ) && ! file_exists( ABSPATH . "wp-admin/$file" ) );

		if ( ! $is_plugin_page ) {
			return admin_url( $slug );
		}

		$parent_file = self::strip_query( $parent );
		if ( $parent !== $slug && 'admin.php' !== $parent_file
			&& file_exists( ABSPATH . "wp-admin/$parent_file" ) && ! file_exists( WP_PLUGIN_DIR . "/$parent_file" ) ) {
			return add_query_arg( 'page', $slug, admin_url( $parent ) );
		}

		return add_query_arg( 'page', $slug, admin_url( 'admin.php' ) );
	}

	private static function strip_query( $slug ) {
		$pos = strpos( $slug, '?' );
		return false === $pos ? $slug : substr( $slug, 0, $pos );
	}

	/**
	 * Removes notification bubbles and markup from a menu title.
	 *
	 * @param string $title Raw menu title.
	 * @return string
	 */
	public static function clean_title( $title ) {
		$name = trim( wp_strip_all_tags( preg_replace( '/<span.*$/s', '', $title ) ) );
		return '' !== $name ? $name : trim( wp_strip_all_tags( $title ) );
	}

	/**
	 * Pulls the number out of a "count-N" notification bubble.
	 *
	 * @param string $title Raw menu title.
	 * @return int
	 */
	public static function extract_count( $title ) {
		return preg_match( '/count-(\d+)/', $title, $m ) ? (int) $m[1] : 0;
	}

	/**
	 * Outputs a menu icon as a white glyph: dashicon, SVG data URI, image URL, or fallback.
	 *
	 * @param string $icon  Icon value from the menu item.
	 * @param string $class Element class.
	 */
	public static function render_glyph( $icon, $class ) {
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
}
