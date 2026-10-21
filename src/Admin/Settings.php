<?php
/**
 * Settings page built on the WordPress Settings API.
 *
 * @package WPSkin
 */

namespace WPSkin\Admin;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings implements Hookable {

	const OPTION = 'wpskin_settings';
	const PAGE   = 'wpskin';
	const GROUP  = 'wpskin_settings_group';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'         => 1,
			'dashboard_tiles' => 1,
			'hide_sidebar'    => 'everywhere',
		);
	}

	/**
	 * Returns a single setting, falling back to its default.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when unset.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$options = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'admin_menu', array( $this, 'add_page' ) );
		$loader->add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_page() {
		add_options_page(
			__( 'WPSkin', 'wpskin' ),
			__( 'WPSkin', 'wpskin' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'wpskin_general',
			__( 'General', 'wpskin' ),
			'__return_false',
			self::PAGE
		);

		add_settings_field(
			'enabled',
			__( 'Enable WPSkin', 'wpskin' ),
			array( $this, 'render_enabled_field' ),
			self::PAGE,
			'wpskin_general'
		);

		add_settings_field(
			'dashboard_tiles',
			__( 'Dashboard tiles', 'wpskin' ),
			array( $this, 'render_dashboard_tiles_field' ),
			self::PAGE,
			'wpskin_general'
		);

		add_settings_field(
			'hide_sidebar',
			__( 'Hide admin sidebar', 'wpskin' ),
			array( $this, 'render_hide_sidebar_field' ),
			self::PAGE,
			'wpskin_general'
		);
	}

	/**
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'enabled'         => empty( $input['enabled'] ) ? 0 : 1,
			'dashboard_tiles' => empty( $input['dashboard_tiles'] ) ? 0 : 1,
			'hide_sidebar'    => isset( $input['hide_sidebar'] ) && in_array( $input['hide_sidebar'], Sidebar::MODES, true )
				? $input['hide_sidebar']
				: self::defaults()['hide_sidebar'],
		);
	}

	public function render_hide_sidebar_field() {
		$choices = array(
			'everywhere' => __( 'On all admin screens', 'wpskin' ),
			'dashboard'  => __( 'On the Dashboard only', 'wpskin' ),
			'never'      => __( 'Never', 'wpskin' ),
		);

		printf( '<select name="%s[hide_sidebar]">', esc_attr( self::OPTION ) );
		foreach ( $choices as $value => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( self::get( 'hide_sidebar' ), $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Only applies while Dashboard tiles are enabled. A "Dashboard" link is added to the toolbar for navigation.', 'wpskin' )
		);
	}

	public function render_dashboard_tiles_field() {
		printf(
			'<label><input type="checkbox" name="%1$s[dashboard_tiles]" value="1" %2$s /> %3$s</label>',
			esc_attr( self::OPTION ),
			checked( 1, (int) self::get( 'dashboard_tiles' ), false ),
			esc_html__( 'Replace the Dashboard with large tiles built from the admin menu.', 'wpskin' )
		);
	}

	public function render_enabled_field() {
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s /> %3$s</label>',
			esc_attr( self::OPTION ),
			checked( 1, (int) self::get( 'enabled' ), false ),
			esc_html__( 'Load WPSkin on the frontend.', 'wpskin' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap wpskin-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
