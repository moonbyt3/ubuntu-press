<?php
/**
 * Users › Preferences: each user's own Style and Dock, stored in user meta on top of the site settings.
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

	const PAGE   = 'ubuntupress-preferences';
	const ACTION = 'ubuntupress_save_preferences';

	/**
	 * Form field name the values are submitted under.
	 */
	const FIELD = 'ubuntupress_preferences';

	/**
	 * @var string Page hook suffix.
	 */
	private $hook = '';

	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'admin_menu', array( $this, 'add_page' ) );
		$loader->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// After core fills the account menu (priority 0).
		$loader->add_action( 'admin_bar_menu', array( $this, 'toolbar_link' ), 20 );
	}

	/**
	 * Under Users for those who can edit users, under Profile for everyone else.
	 */
	public function add_page() {
		if ( ! Settings::personal_allowed() ) {
			return;
		}

		$this->hook = add_users_page(
			__( 'Preferences', 'ubuntupress' ),
			__( 'Preferences', 'ubuntupress' ),
			'read',
			self::PAGE,
			array( $this, 'render_page' )
		);

		// Saved on the page's own load hook, where the admin menu (and so the user's apps) is known.
		add_action( 'load-' . $this->hook, array( $this, 'save' ) );
	}

	/**
	 * @return string Preferences page URL for the current user.
	 */
	public static function url() {
		$parent = current_user_can( 'edit_users' ) ? 'users.php' : 'profile.php';
		return admin_url( $parent . '?page=' . self::PAGE );
	}

	/**
	 * Adds "Preferences" to the toolbar account menu, between Edit Profile and Log Out.
	 *
	 * @param \WP_Admin_Bar $admin_bar Toolbar instance.
	 */
	public function toolbar_link( $admin_bar ) {
		if ( ! is_admin() || ! Settings::personal_allowed() || ! $admin_bar->get_node( 'user-actions' ) ) {
			return;
		}

		// Log Out is re-added after the new item so it stays last.
		$logout = $admin_bar->get_node( 'logout' );
		if ( $logout ) {
			$admin_bar->remove_node( 'logout' );
		}

		$admin_bar->add_node(
			array(
				'parent' => 'user-actions',
				'id'     => 'ubuntupress-preferences',
				'title'  => __( 'Preferences', 'ubuntupress' ),
				'href'   => self::url(),
			)
		);

		if ( $logout ) {
			$admin_bar->add_node( (array) $logout );
		}
	}

	/**
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! $this->hook || $this->hook !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ubuntupress-admin',
			UBUNTUPRESS_URL . 'assets/css/admin.css',
			array(),
			Plugin::asset_version( 'assets/css/admin.css' )
		);
	}

	/**
	 * Stores only the values that differ from the site settings, so everything else keeps
	 * following the defaults when an admin changes them.
	 */
	public function save() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['action'] ) || self::ACTION !== $_POST['action'] ) {
			return;
		}

		check_admin_referer( self::ACTION );

		$user_id = get_current_user_id();

		if ( isset( $_POST['reset'] ) ) {
			delete_user_meta( $user_id, Settings::USER_META );
			wp_safe_redirect( add_query_arg( 'reset', '1', self::url() ) );
			exit;
		}

		$input  = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
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

		wp_safe_redirect( add_query_arg( 'updated', '1', self::url() ) );
		exit;
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

	public function render_page() {
		$settings = ubuntupress()->get( Settings::class );
		if ( ! $settings ) {
			return;
		}

		$values = array();
		foreach ( Settings::PERSONAL as $key ) {
			$values[ $key ] = Settings::get( $key );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only status flags after a redirect.
		$updated = isset( $_GET['updated'] );
		$reset   = isset( $_GET['reset'] );
		// phpcs:enable
		?>
		<div class="wrap ubuntupress-settings">
			<h1 class="ubuntupress-settings__title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="ubuntupress-settings__intro"><?php esc_html_e( 'Your own Style and Dock. Anything you leave as it is follows the site defaults.', 'ubuntupress' ); ?></p>

			<?php if ( $updated || $reset ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo $reset ? esc_html__( 'Your preferences were reset to the site defaults.', 'ubuntupress' ) : esc_html__( 'Preferences saved.', 'ubuntupress' ); ?></p>
				</div>
			<?php endif; ?>

			<form class="ubuntupress-settings__form" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::ACTION ); ?>

				<?php $settings->render_groups( $settings->groups( Settings::PERSONAL ), $values, self::FIELD ); ?>

				<div class="ubuntupress-settings__actions">
					<?php // Save comes first so Enter saves; CSS shows Reset to its left. ?>
					<?php submit_button( __( 'Save Changes', 'ubuntupress' ), 'primary ubuntupress-settings__save', 'submit', false ); ?>
					<?php if ( Settings::user_values() ) : ?>
						<?php submit_button( __( 'Reset to Site Defaults', 'ubuntupress' ), 'ubuntupress-settings__reset', 'reset', false ); ?>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
	}
}
