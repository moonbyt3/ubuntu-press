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
			'theme'               => 'light',
			'settings_pages'      => 0,
			'dashboard_cards'     => 1,
			'hide_sidebar'        => 'everywhere',
			'notification_center' => 1,
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
		// Late priority: a menu item without a position is appended, so this keeps WPSkin last.
		$loader->add_action( 'admin_menu', array( $this, 'add_page' ), PHP_INT_MAX );
		$loader->add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_page() {
		add_menu_page(
			__( 'WPSkin', 'wpskin' ),
			__( 'WPSkin', 'wpskin' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' ),
			'dashicons-admin-appearance'
		);
	}

	/**
	 * @return string Settings page URL.
	 */
	public static function url() {
		return admin_url( 'admin.php?page=' . self::PAGE );
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
	}

	/**
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'theme'               => isset( $input['theme'] ) && in_array( $input['theme'], Theme::MODES, true )
				? $input['theme']
				: self::defaults()['theme'],
			'settings_pages'      => empty( $input['settings_pages'] ) ? 0 : 1,
			'dashboard_cards'     => empty( $input['dashboard_cards'] ) ? 0 : 1,
			'hide_sidebar'        => isset( $input['hide_sidebar'] ) && in_array( $input['hide_sidebar'], Sidebar::MODES, true )
				? $input['hide_sidebar']
				: self::defaults()['hide_sidebar'],
			'notification_center' => empty( $input['notification_center'] ) ? 0 : 1,
		);
	}

	/**
	 * Settings rows, grouped like a GNOME Settings panel.
	 *
	 * @return array[] Groups of rows: key, title, subtitle, control type and choices.
	 */
	private function groups() {
		return array(
			array(
				'title' => __( 'Appearance', 'wpskin' ),
				'rows'  => array(
					array(
						'key'      => 'theme',
						'title'    => __( 'Style', 'wpskin' ),
						'subtitle' => __( 'Applies to the Dashboard and all admin screens, including the block editor.', 'wpskin' ),
						'choices'  => array(
							'light' => __( 'Light', 'wpskin' ),
							'dark'  => __( 'Dark', 'wpskin' ),
						),
					),
					array(
						'key'      => 'settings_pages',
						'title'    => __( 'Ubuntu style for Settings screens', 'wpskin' ),
						'subtitle' => __( 'Give General, Writing, Reading, Discussion, Media and Permalinks the same look as this screen.', 'wpskin' ),
					),
				),
			),
			array(
				'title' => __( 'Dashboard', 'wpskin' ),
				'rows'  => array(
					array(
						'key'      => 'dashboard_cards',
						'title'    => __( 'Dashboard cards', 'wpskin' ),
						'subtitle' => __( 'Replace the Dashboard with large cards built from the admin menu.', 'wpskin' ),
					),
					array(
						'key'      => 'hide_sidebar',
						'title'    => __( 'Hide admin sidebar', 'wpskin' ),
						'subtitle' => __( 'Only applies while Dashboard cards are enabled. A "Dashboard" link is added to the toolbar for navigation.', 'wpskin' ),
						'choices'  => array(
							'everywhere' => __( 'On all admin screens', 'wpskin' ),
							'dashboard'  => __( 'On the Dashboard only', 'wpskin' ),
							'never'      => __( 'Never', 'wpskin' ),
						),
					),
				),
			),
			array(
				'title' => __( 'Notifications', 'wpskin' ),
				'rows'  => array(
					array(
						'key'      => 'notification_center',
						'title'    => __( 'Notification center', 'wpskin' ),
						'subtitle' => __( 'Collect admin notices into a toolbar popup with a counter.', 'wpskin' ),
					),
				),
			),
		);
	}

	/**
	 * Outputs one row: label and description on the left, a dropdown or switch on the right.
	 *
	 * @param array $row Row definition from groups().
	 */
	private function render_row( array $row ) {
		$id   = 'wpskin-setting-' . $row['key'];
		$name = self::OPTION . '[' . $row['key'] . ']';
		?>
		<div class="wpskin-prefs-row">
			<div class="wpskin-prefs-row__text">
				<label class="wpskin-prefs-row__title" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $row['title'] ); ?></label>
				<p class="wpskin-prefs-row__subtitle" id="<?php echo esc_attr( $id ); ?>-description"><?php echo esc_html( $row['subtitle'] ); ?></p>
			</div>
			<div class="wpskin-prefs-row__control">
				<?php if ( isset( $row['choices'] ) ) : ?>
					<select class="wpskin-prefs-row__select" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" aria-describedby="<?php echo esc_attr( $id ); ?>-description">
						<?php foreach ( $row['choices'] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( self::get( $row['key'] ), $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="checkbox"
						role="switch"
						class="wpskin-switch"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="1"
						aria-describedby="<?php echo esc_attr( $id ); ?>-description"
						<?php checked( 1, (int) self::get( $row['key'] ) ); ?>
					/>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap wpskin-settings">
			<h1 class="wpskin-settings__title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<form class="wpskin-settings__form" action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<?php foreach ( $this->groups() as $group ) : ?>
					<section class="wpskin-prefs-group">
						<h2 class="wpskin-prefs-group__title"><?php echo esc_html( $group['title'] ); ?></h2>
						<div class="wpskin-prefs-group__list">
							<?php
							foreach ( $group['rows'] as $row ) {
								$this->render_row( $row );
							}
							?>
						</div>
					</section>
				<?php endforeach; ?>

				<div class="wpskin-settings__actions">
					<?php submit_button( __( 'Save Changes', 'wpskin' ), 'primary wpskin-settings__save', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}
