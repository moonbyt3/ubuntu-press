<?php
/**
 * Each user's own Style, Accent color and Dock, in an "UbuntuPress" section of their Profile screen.
 * Stored in user meta on top of the site settings.
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

class Preferences implements Hookable {

	/**
	 * Form field name the values are submitted under.
	 */
	const FIELD = 'ubuntupress_preferences';

	/**
	 * Section anchor on the Profile screen.
	 */
	const ANCHOR = 'ubuntupress';

	public function register_hooks( Loader $loader ) {
		// Own profile only: the Dock apps offered depend on what the current user can open.
		$loader->add_action( 'show_user_profile', array( $this, 'render_section' ) );
		$loader->add_action( 'personal_options_update', array( $this, 'save' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * @return string Profile screen URL, scrolled to the UbuntuPress section.
	 */
	public static function url() {
		return admin_url( 'profile.php#' . self::ANCHOR );
	}

	/**
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'profile.php' !== $hook || ! Settings::personal_allowed() ) {
			return;
		}

		// Accent swatches.
		wp_enqueue_style(
			'ubuntupress-admin',
			UBUNTUPRESS_URL . 'assets/css/admin.css',
			array(),
			Plugin::asset_version( 'assets/css/admin.css' )
		);

		// Live accent preview.
		wp_enqueue_script(
			'ubuntupress-admin',
			UBUNTUPRESS_URL . 'assets/js/admin.js',
			array(),
			Plugin::asset_version( 'assets/js/admin.js' ),
			true
		);
	}

	/**
	 * Stores only the values that differ from the site settings, so everything else keeps
	 * following the defaults when an admin changes them. WordPress has already checked the
	 * profile form's nonce when this runs.
	 *
	 * @param int $user_id User being saved; always the current user on this hook.
	 */
	public function save( $user_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by wp-admin/user-edit.php.
		if ( ! isset( $_POST[ self::FIELD ] ) || ! is_array( $_POST[ self::FIELD ] ) || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! empty( $_POST[ self::FIELD ]['reset'] ) ) {
			delete_user_meta( $user_id, Settings::USER_META );
			return;
		}

		$input = wp_unslash( $_POST[ self::FIELD ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		// phpcs:enable

		$values = Settings::sanitize_values( $input, Settings::PERSONAL );
		$site   = Settings::site_values();
		$own    = array();

		foreach ( $values as $key => $value ) {
			if ( ! $this->same_as_site( $key, $value, $site[ $key ] ) ) {
				$own[ $key ] = $value;
			}
		}

		if ( $own ) {
			update_user_meta( $user_id, Settings::USER_META, $own );
		} else {
			delete_user_meta( $user_id, Settings::USER_META );
		}
	}

	/**
	 * @param string $key   Setting key.
	 * @param mixed  $value Submitted value.
	 * @param mixed  $site  Site value.
	 * @return bool
	 */
	private function same_as_site( $key, $value, $site ) {
		if ( 'dock_items' !== $key ) {
			return (string) $value === (string) $site;
		}

		// The form only lists apps this user can open, so compare against that part of the site's apps.
		$offered = wp_list_pluck( AdminMenu::items(), 'slug' );
		$site    = array_intersect( (array) $site, $offered );
		$value   = (array) $value;
		sort( $site );
		sort( $value );
		return $site === $value;
	}

	/**
	 * Outputs the section as a standard form table, using the rows defined for the settings page.
	 */
	public function render_section() {
		$settings = ubuntupress()->get( Settings::class );
		if ( ! $settings || ! Settings::personal_allowed() ) {
			return;
		}
		?>
		<h2 id="<?php echo esc_attr( self::ANCHOR ); ?>"><?php esc_html_e( 'UbuntuPress', 'ubuntupress' ); ?></h2>
		<p><?php esc_html_e( 'Settings you leave unchanged follow the site defaults.', 'ubuntupress' ); ?></p>
		<table class="form-table" role="presentation">
			<?php
			foreach ( $settings->groups( Settings::PERSONAL ) as $group ) {
				foreach ( $group['rows'] as $row ) {
					$this->render_row( $row, Settings::get( $row['key'] ) );
				}
			}
			?>
			<?php if ( Settings::user_values() ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Site defaults', 'ubuntupress' ); ?></th>
					<td>
						<label for="ubuntupress-reset">
							<input type="checkbox" id="ubuntupress-reset" name="<?php echo esc_attr( self::FIELD ); ?>[reset]" value="1" />
							<?php esc_html_e( 'Reset these settings to the site defaults', 'ubuntupress' ); ?>
						</label>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * @param array $row   Row definition from Settings::groups().
	 * @param mixed $value Current value.
	 */
	private function render_row( array $row, $value ) {
		$id   = 'ubuntupress-' . $row['key'];
		$name = self::FIELD . '[' . $row['key'] . ']';
		$type = isset( $row['type'] ) ? $row['type'] : ( isset( $row['choices'] ) ? 'select' : 'checkbox' );
		?>
		<tr>
			<?php if ( 'select' === $type ) : ?>
				<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $row['title'] ); ?></label></th>
				<td>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" aria-describedby="<?php echo esc_attr( $id ); ?>-description">
						<?php foreach ( $row['choices'] as $choice => $label ) : ?>
							<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $value, $choice ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description" id="<?php echo esc_attr( $id ); ?>-description"><?php echo esc_html( $row['subtitle'] ); ?></p>
				</td>
			<?php elseif ( 'checkbox' === $type ) : ?>
				<th scope="row"><?php echo esc_html( $row['title'] ); ?></th>
				<td>
					<label for="<?php echo esc_attr( $id ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( 1, (int) $value ); ?> />
						<?php echo esc_html( $row['subtitle'] ); ?>
					</label>
				</td>
			<?php elseif ( 'accent' === $type ) : ?>
				<th scope="row"><?php echo esc_html( $row['title'] ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php echo esc_html( $row['title'] ); ?></legend>
						<div class="ubuntupress-swatches">
							<?php foreach ( $row['choices'] as $choice => $label ) : ?>
								<?php $color = Theme::ACCENTS[ $choice ]; ?>
								<input
									type="radio"
									class="ubuntupress-swatch"
									name="<?php echo esc_attr( $name ); ?>"
									value="<?php echo esc_attr( $choice ); ?>"
									style="--ubuntupress-swatch: <?php echo esc_attr( $color ); ?>"
									data-color="<?php echo esc_attr( $color ); ?>"
									data-hover="<?php echo esc_attr( Theme::hover_shade( $color ) ); ?>"
									aria-label="<?php echo esc_attr( $label ); ?>"
									title="<?php echo esc_attr( $label ); ?>"
									<?php checked( $value, $choice ); ?>
								/>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php echo esc_html( $row['subtitle'] ); ?></p>
					</fieldset>
				</td>
			<?php elseif ( 'apps' === $type ) : ?>
				<th scope="row"><?php echo esc_html( $row['title'] ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php echo esc_html( $row['title'] ); ?></legend>
						<?php foreach ( AdminMenu::items() as $index => $item ) : ?>
							<label for="<?php echo esc_attr( $id . '-' . $index ); ?>">
								<input
									type="checkbox"
									id="<?php echo esc_attr( $id . '-' . $index ); ?>"
									name="<?php echo esc_attr( $name ); ?>[]"
									value="<?php echo esc_attr( $item['slug'] ); ?>"
									<?php checked( in_array( $item['slug'], (array) $value, true ) ); ?>
								/>
								<?php echo esc_html( $item['name'] ); ?>
							</label><br />
						<?php endforeach; ?>
						<p class="description"><?php echo esc_html( $row['subtitle'] ); ?></p>
					</fieldset>
				</td>
			<?php endif; ?>
		</tr>
		<?php
	}
}
