<?php
/**
 * Light / dark / automatic colour theme and accent colour for the admin.
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

class Theme implements Hookable {

	const MODES = array( 'light', 'dark', 'auto' );

	/**
	 * Accent colours (Yaru), keyed by setting value.
	 */
	const ACCENTS = array(
		'orange'         => '#e95420',
		'bark'           => '#787859',
		'sage'           => '#657b69',
		'olive'          => '#4b8501',
		'viridian'       => '#03875b',
		'prussian-green' => '#308280',
		'blue'           => '#0073e5',
		'purple'         => '#7764d8',
		'magenta'        => '#b34cb3',
		'red'            => '#da3450',
	);

	/**
	 * Media query the dark styles follow in automatic mode.
	 */
	const DARK_QUERY = '(prefers-color-scheme: dark)';

	public function register_hooks( Loader $loader ) {
		$loader->add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		$loader->add_action( 'admin_head', array( $this, 'print_head' ), 1 );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$loader->add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas_assets' ) );
		// After core's Customizer styles (nav menus, widgets) are enqueued at the default priority.
		$loader->add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customize_assets' ), 20 );
		$loader->add_action( 'customize_controls_head', array( $this, 'print_head' ) );
	}

	/**
	 * @return string One of MODES.
	 */
	public static function mode() {
		$mode = Settings::get( 'theme' );
		return in_array( $mode, self::MODES, true ) ? $mode : self::MODES[0];
	}

	/**
	 * Dark on the server's side; automatic mode decides in the browser.
	 *
	 * @return bool
	 */
	public static function is_dark() {
		return 'dark' === self::mode();
	}

	/**
	 * Whether the dark stylesheets are needed. They only apply under body.ubuntupress-admin--dark,
	 * so in automatic mode they load and the class is toggled by print_head().
	 *
	 * @return bool
	 */
	private static function has_dark_styles() {
		return 'light' !== self::mode();
	}

	/**
	 * @return string Hex colour of the current accent.
	 */
	public static function accent() {
		$accent = Settings::get( 'accent' );
		return isset( self::ACCENTS[ $accent ] ) ? self::ACCENTS[ $accent ] : self::ACCENTS['orange'];
	}

	/**
	 * Lighter shade for hover states: the colour mixed with 15% white.
	 *
	 * @param string $hex Hex colour.
	 * @return string
	 */
	public static function hover_shade( $hex ) {
		$channels = array_map( 'hexdec', str_split( ltrim( $hex, '#' ), 2 ) );
		return vsprintf(
			'#%02x%02x%02x',
			array_map(
				function ( $channel ) {
					return (int) round( $channel + ( 255 - $channel ) * 0.15 );
				},
				$channels
			)
		);
	}

	/**
	 * @return bool
	 */
	private function is_block_editor() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && $screen->is_block_editor();
	}

	/**
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return self::is_dark() ? $classes . ' ubuntupress-admin--dark' : $classes;
	}

	/**
	 * Accent colour tokens for every UbuntuPress stylesheet, plus the dark body class where PHP can't
	 * set it: automatic mode (decided by the device) and the Customizer (no body class filter).
	 * Runs in <head>, so the class is added as soon as <body> is parsed, before the first paint.
	 */
	public function print_head() {
		$accent = self::accent();
		printf(
			'<style id="ubuntupress-accent">:root{--ubuntupress-accent:%1$s;--ubuntupress-accent-hover:%2$s;}</style>' . "\n",
			esc_html( $accent ),
			esc_html( self::hover_shade( $accent ) )
		);

		$customizer = doing_action( 'customize_controls_head' );
		if ( 'auto' !== self::mode() && ! ( $customizer && self::is_dark() ) ) {
			return;
		}
		?>
		<script>
		( function () {
			var dark = <?php echo self::is_dark() ? 'null' : 'window.matchMedia( ' . wp_json_encode( self::DARK_QUERY ) . ' )'; ?>;

			function apply() {
				document.body.classList.toggle( 'ubuntupress-admin--dark', ! dark || dark.matches );
			}

			if ( document.body ) {
				apply();
			} else {
				new MutationObserver( function ( mutations, observer ) {
					if ( document.body ) {
						apply();
						observer.disconnect();
					}
				} ).observe( document.documentElement, { childList: true } );
			}

			// Follows the device when it switches between light and dark.
			if ( dark && dark.addEventListener ) {
				dark.addEventListener( 'change', apply );
			}
		}() );
		</script>
		<?php
	}

	public function enqueue_assets() {
		if ( ! self::has_dark_styles() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-theme-dark',
			UBUNTUPRESS_URL . 'assets/css/theme-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/theme-dark.css' )
		);

		if ( $this->is_block_editor() ) {
			// Depends on the editor styles so it loads after them and wins at equal specificity.
			wp_enqueue_style(
				'ubuntupress-editor-dark',
				UBUNTUPRESS_URL . 'assets/css/editor-dark.css',
				array( 'ubuntupress-theme-dark', 'wp-components', 'wp-block-editor', 'wp-editor', 'wp-edit-post' ),
				Plugin::asset_version( 'assets/css/editor-dark.css' )
			);
		}
	}

	public function enqueue_customize_assets() {
		if ( ! self::has_dark_styles() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-theme-dark',
			UBUNTUPRESS_URL . 'assets/css/theme-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/theme-dark.css' )
		);

		wp_enqueue_style(
			'ubuntupress-customize-dark',
			UBUNTUPRESS_URL . 'assets/css/customize-dark.css',
			array( 'ubuntupress-theme-dark', 'customize-controls' ),
			Plugin::asset_version( 'assets/css/customize-dark.css' )
		);

		// The block-based widgets editor runs inside the Customizer when it is enabled.
		if ( wp_style_is( 'wp-customize-widgets' ) ) {
			wp_enqueue_style(
				'ubuntupress-editor-dark',
				UBUNTUPRESS_URL . 'assets/css/editor-dark.css',
				array( 'ubuntupress-theme-dark', 'wp-customize-widgets' ),
				Plugin::asset_version( 'assets/css/editor-dark.css' )
			);
		}
	}

	/**
	 * Darkens the editor canvas. This hook also runs inside the editor iframe and on the frontend.
	 * The iframe has no UbuntuPress body class, so automatic mode uses a media query instead.
	 */
	public function enqueue_canvas_assets() {
		if ( ! is_admin() || ! self::has_dark_styles() ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-editor-canvas-dark',
			UBUNTUPRESS_URL . 'assets/css/editor-canvas-dark.css',
			array(),
			Plugin::asset_version( 'assets/css/editor-canvas-dark.css' ),
			self::is_dark() ? 'all' : self::DARK_QUERY
		);
	}
}
