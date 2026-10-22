<?php
/**
 * Settings page built on the WordPress Settings API.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Admin;

use UbuntuPress\Contracts\Hookable;
use UbuntuPress\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings implements Hookable {

	const OPTION = 'ubuntupress_settings';
	const PAGE   = 'ubuntupress';
	const GROUP  = 'ubuntupress_settings_group';

	/**
	 * User meta holding a user's own choices: only values that differ from the site settings.
	 */
	const USER_META = 'ubuntupress_preferences';

	/**
	 * Settings each user can change for themselves. Everything else is site-wide only.
	 */
	const PERSONAL = array( 'theme', 'dock', 'dock_position', 'dock_autohide', 'dock_items' );

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'theme'                => 'light',
			'settings_pages'       => 0,
			'dashboard_cards'      => 1,
			'hide_sidebar'         => 'everywhere',
			'notification_center'  => 1,
			'dock'                 => 0,
			'dock_position'        => 'left',
			'dock_autohide'        => 1,
			'dock_items'           => Dock::DEFAULT_ITEMS,
			'personal_preferences' => 1,
		);
	}

	/**
	 * Site-wide settings, which are also every user's defaults.
	 *
	 * @return array
	 */
	public static function site_values() {
		return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
	}

	/**
	 * @return bool Whether users may override the PERSONAL settings.
	 */
	public static function personal_allowed() {
		return (bool) self::site_values()['personal_preferences'];
	}

	/**
	 * The current user's own choices, empty when they follow the site settings.
	 *
	 * @return array
	 */
	public static function user_values() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::personal_allowed() ) {
			return array();
		}

		$values = get_user_meta( $user_id, self::USER_META, true );
		return is_array( $values ) ? array_intersect_key( $values, array_flip( self::PERSONAL ) ) : array();
	}

	/**
	 * Returns a single setting for the current user: their own choice, else the site value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when unset.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		if ( in_array( $key, self::PERSONAL, true ) ) {
			$user = self::user_values();
			if ( array_key_exists( $key, $user ) ) {
				return $user[ $key ];
			}
		}

		$options = self::site_values();
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	public function register_hooks( Loader $loader ) {
		// Late priority: a menu item without a position is appended, so this keeps UbuntuPress last.
		$loader->add_action( 'admin_menu', array( $this, 'add_page' ), PHP_INT_MAX );
		$loader->add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_page() {
		add_menu_page(
			__( 'UbuntuPress', 'ubuntupress' ),
			__( 'UbuntuPress', 'ubuntupress' ),
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
		return self::sanitize_values( is_array( $input ) ? $input : array(), array_keys( self::defaults() ) );
	}

	/**
	 * Sanitizes submitted values. A missing switch counts as off and missing apps as none,
	 * so only pass keys whose controls were in the form.
	 *
	 * @param array    $input Raw submitted values.
	 * @param string[] $keys  Keys to return.
	 * @return array
	 */
	public static function sanitize_values( array $input, array $keys ) {
		$values = array(
			'theme'                => isset( $input['theme'] ) && in_array( $input['theme'], Theme::MODES, true )
				? $input['theme']
				: self::defaults()['theme'],
			'settings_pages'       => empty( $input['settings_pages'] ) ? 0 : 1,
			'dashboard_cards'      => empty( $input['dashboard_cards'] ) ? 0 : 1,
			'hide_sidebar'         => isset( $input['hide_sidebar'] ) && in_array( $input['hide_sidebar'], Sidebar::MODES, true )
				? $input['hide_sidebar']
				: self::defaults()['hide_sidebar'],
			'notification_center'  => empty( $input['notification_center'] ) ? 0 : 1,
			'dock'                 => empty( $input['dock'] ) ? 0 : 1,
			'dock_position'        => isset( $input['dock_position'] ) && in_array( $input['dock_position'], Dock::POSITIONS, true )
				? $input['dock_position']
				: self::defaults()['dock_position'],
			'dock_autohide'        => empty( $input['dock_autohide'] ) ? 0 : 1,
			'dock_items'           => isset( $input['dock_items'] ) && is_array( $input['dock_items'] )
				? array_values( array_unique( array_map( 'sanitize_text_field', $input['dock_items'] ) ) )
				: array(),
			'personal_preferences' => empty( $input['personal_preferences'] ) ? 0 : 1,
		);

		return array_intersect_key( $values, array_flip( $keys ) );
	}

	/**
	 * Settings rows, grouped like a GNOME Settings panel.
	 *
	 * @param string[]|null $keys Only include these rows; groups left empty are dropped.
	 * @return array[] Groups of rows: key, title, subtitle, control type and choices.
	 */
	public function groups( $keys = null ) {
		$groups = array(
			array(
				'title' => __( 'Appearance', 'ubuntupress' ),
				'rows'  => array(
					array(
						'key'      => 'theme',
						'title'    => __( 'Style', 'ubuntupress' ),
						'subtitle' => __( 'Applies to the Dashboard and all admin screens, including the block editor.', 'ubuntupress' ),
						'choices'  => array(
							'light' => __( 'Light', 'ubuntupress' ),
							'dark'  => __( 'Dark', 'ubuntupress' ),
						),
					),
					array(
						'key'      => 'settings_pages',
						'title'    => __( 'Ubuntu style for Settings screens', 'ubuntupress' ),
						'subtitle' => __( 'Give General, Writing, Reading, Discussion, Media and Permalinks the same look as this screen.', 'ubuntupress' ),
					),
				),
			),
			array(
				'title' => __( 'Dashboard', 'ubuntupress' ),
				'rows'  => array(
					array(
						'key'      => 'dashboard_cards',
						'title'    => __( 'Dashboard cards', 'ubuntupress' ),
						'subtitle' => __( 'Replace the Dashboard with large cards built from the admin menu.', 'ubuntupress' ),
					),
					array(
						'key'      => 'hide_sidebar',
						'title'    => __( 'Hide admin sidebar', 'ubuntupress' ),
						'subtitle' => __( 'Only applies while Dashboard cards are enabled. A "Dashboard" link is added to the toolbar for navigation.', 'ubuntupress' ),
						'choices'  => array(
							'everywhere' => __( 'On all admin screens', 'ubuntupress' ),
							'dashboard'  => __( 'On the Dashboard only', 'ubuntupress' ),
							'never'      => __( 'Never', 'ubuntupress' ),
						),
					),
				),
			),
			array(
				'title' => __( 'Notifications', 'ubuntupress' ),
				'rows'  => array(
					array(
						'key'      => 'notification_center',
						'title'    => __( 'Notification center', 'ubuntupress' ),
						'subtitle' => __( 'Collect admin notices into a toolbar popup with a counter.', 'ubuntupress' ),
					),
				),
			),
			array(
				'title' => __( 'Dock', 'ubuntupress' ),
				'rows'  => array(
					array(
						'key'      => 'dock',
						'title'    => __( 'Dock', 'ubuntupress' ),
						'subtitle' => __( 'An Ubuntu Dock along the edge of the screen with the admin screens you use most.', 'ubuntupress' ),
					),
					array(
						'key'      => 'dock_position',
						'title'    => __( 'Position on screen', 'ubuntupress' ),
						'subtitle' => __( 'The screen edge the Dock appears from.', 'ubuntupress' ),
						'choices'  => array(
							'left'   => __( 'Left', 'ubuntupress' ),
							'bottom' => __( 'Bottom', 'ubuntupress' ),
							'right'  => __( 'Right', 'ubuntupress' ),
						),
					),
					array(
						'key'      => 'dock_autohide',
						'title'    => __( 'Auto-hide the Dock', 'ubuntupress' ),
						'subtitle' => __( 'The Dock hides until the pointer reaches the screen edge. When off, it stays visible and admin screens make room for it.', 'ubuntupress' ),
					),
					array(
						'key'      => 'dock_items',
						'type'     => 'apps',
						'title'    => __( 'Apps in the Dock', 'ubuntupress' ),
						'subtitle' => __( 'Choose which admin menu items to keep in the Dock.', 'ubuntupress' ),
					),
				),
			),
			array(
				'title' => __( 'Users', 'ubuntupress' ),
				'rows'  => array(
					array(
						'key'      => 'personal_preferences',
						'title'    => __( 'Personal preferences', 'ubuntupress' ),
						'subtitle' => __( 'Let each user choose their own Style and Dock on their Preferences page, under Users or Profile. The values on this page are their defaults.', 'ubuntupress' ),
					),
				),
			),
		);

		if ( null === $keys ) {
			return $groups;
		}

		foreach ( $groups as $index => $group ) {
			$groups[ $index ]['rows'] = array_values(
				array_filter(
					$group['rows'],
					function ( $row ) use ( $keys ) {
						return in_array( $row['key'], $keys, true );
					}
				)
			);
		}

		return array_values(
			array_filter(
				$groups,
				function ( $group ) {
					return (bool) $group['rows'];
				}
			)
		);
	}

	/**
	 * Outputs preference groups with their rows.
	 *
	 * @param array[] $groups Groups from groups().
	 * @param array   $values Current values, keyed by setting.
	 * @param string  $name   Form field name the values are submitted under.
	 */
	public function render_groups( array $groups, array $values, $name ) {
		foreach ( $groups as $group ) :
			?>
			<section class="ubuntupress-prefs-group">
				<h2 class="ubuntupress-prefs-group__title"><?php echo esc_html( $group['title'] ); ?></h2>
				<div class="ubuntupress-prefs-group__list">
					<?php
					foreach ( $group['rows'] as $row ) {
						$this->render_row( $row, $values, $name );
					}
					?>
				</div>
			</section>
			<?php
		endforeach;
	}

	/**
	 * Outputs one row: label and description on the left, a dropdown or switch on the right.
	 *
	 * @param array  $row    Row definition from groups().
	 * @param array  $values Current values, keyed by setting.
	 * @param string $name   Form field name the values are submitted under.
	 */
	private function render_row( array $row, array $values, $name ) {
		if ( isset( $row['type'] ) && 'apps' === $row['type'] ) {
			$this->render_apps_row( $row, $values, $name );
			return;
		}

		$id    = 'ubuntupress-setting-' . $row['key'];
		$value = isset( $values[ $row['key'] ] ) ? $values[ $row['key'] ] : null;
		$name  = $name . '[' . $row['key'] . ']';
		?>
		<div class="ubuntupress-prefs-row">
			<div class="ubuntupress-prefs-row__text">
				<label class="ubuntupress-prefs-row__title" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $row['title'] ); ?></label>
				<p class="ubuntupress-prefs-row__subtitle" id="<?php echo esc_attr( $id ); ?>-description"><?php echo esc_html( $row['subtitle'] ); ?></p>
			</div>
			<div class="ubuntupress-prefs-row__control">
				<?php if ( isset( $row['choices'] ) ) : ?>
					<select class="ubuntupress-prefs-row__select" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" aria-describedby="<?php echo esc_attr( $id ); ?>-description">
						<?php foreach ( $row['choices'] as $choice => $label ) : ?>
							<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $value, $choice ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="checkbox"
						role="switch"
						class="ubuntupress-switch"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="1"
						aria-describedby="<?php echo esc_attr( $id ); ?>-description"
						<?php checked( 1, (int) $value ); ?>
					/>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs an expander row (AdwExpanderRow) holding a switch for every top-level admin menu item.
	 *
	 * @param array  $row    Row definition from groups().
	 * @param array  $values Current values, keyed by setting.
	 * @param string $name   Form field name the values are submitted under.
	 */
	private function render_apps_row( array $row, array $values, $name ) {
		$id       = 'ubuntupress-setting-' . $row['key'];
		$name     = $name . '[' . $row['key'] . '][]';
		$selected = isset( $values[ $row['key'] ] ) ? (array) $values[ $row['key'] ] : array();
		?>
		<details class="ubuntupress-prefs-row ubuntupress-prefs-expander" id="<?php echo esc_attr( $id ); ?>">
			<summary class="ubuntupress-prefs-expander__header">
				<span class="ubuntupress-prefs-row__text">
					<span class="ubuntupress-prefs-row__title"><?php echo esc_html( $row['title'] ); ?></span>
					<span class="ubuntupress-prefs-row__subtitle"><?php echo esc_html( $row['subtitle'] ); ?></span>
				</span>
				<span class="ubuntupress-prefs-expander__arrow" aria-hidden="true"></span>
			</summary>
			<div class="ubuntupress-prefs-expander__list">
				<?php foreach ( AdminMenu::items() as $index => $item ) : ?>
					<?php $item_id = $id . '-' . $index; ?>
					<div class="ubuntupress-prefs-row ubuntupress-prefs-row--app">
						<span class="ubuntupress-prefs-row__icon" style="--ubuntupress-app-color: <?php echo esc_attr( $item['color'] ); ?>">
							<?php AdminMenu::render_glyph( $item['icon'], 'ubuntupress-prefs-row__glyph' ); ?>
						</span>
						<div class="ubuntupress-prefs-row__text">
							<label class="ubuntupress-prefs-row__title" for="<?php echo esc_attr( $item_id ); ?>"><?php echo esc_html( $item['name'] ); ?></label>
						</div>
						<div class="ubuntupress-prefs-row__control">
							<input
								type="checkbox"
								role="switch"
								class="ubuntupress-switch"
								id="<?php echo esc_attr( $item_id ); ?>"
								name="<?php echo esc_attr( $name ); ?>"
								value="<?php echo esc_attr( $item['slug'] ); ?>"
								<?php checked( in_array( $item['slug'], $selected, true ) ); ?>
							/>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap ubuntupress-settings">
			<h1 class="ubuntupress-settings__title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php if ( self::personal_allowed() ) : ?>
				<p class="ubuntupress-settings__intro">
					<?php
					printf(
						/* translators: %s: link to the Preferences page. */
						esc_html__( 'These settings apply to everyone. Users can change Style and Dock for themselves on %s.', 'ubuntupress' ),
						'<a href="' . esc_url( Preferences::url() ) . '">' . esc_html__( 'Users › Preferences', 'ubuntupress' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>
			<?php settings_errors(); ?>
			<form class="ubuntupress-settings__form" action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<?php $this->render_groups( $this->groups(), self::site_values(), self::OPTION ); ?>

				<div class="ubuntupress-settings__actions">
					<?php submit_button( __( 'Save Changes', 'ubuntupress' ), 'primary ubuntupress-settings__save', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}
