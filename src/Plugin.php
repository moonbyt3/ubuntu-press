<?php
/**
 * Main plugin class.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress;

use UbuntuPress\Admin\Admin;
use UbuntuPress\Admin\DashboardCards;
use UbuntuPress\Admin\Dock;
use UbuntuPress\Admin\Notifications;
use UbuntuPress\Admin\Settings;
use UbuntuPress\Admin\SettingsPages;
use UbuntuPress\Admin\Sidebar;
use UbuntuPress\Admin\Theme;
use UbuntuPress\Contracts\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/**
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var Loader
	 */
	private $loader;

	/**
	 * Registered services, keyed by class name.
	 *
	 * @var Hookable[]
	 */
	private $services = array();

	/**
	 * @var bool
	 */
	private $booted = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->loader = new Loader();
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \LogicException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Instantiates services, collects their hooks and attaches them to WordPress.
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		foreach ( $this->get_service_classes() as $class ) {
			$service = new $class();
			if ( $service instanceof Hookable ) {
				$service->register_hooks( $this->loader );
			}
			$this->services[ $class ] = $service;
		}

		$this->loader->run();

		do_action( 'ubuntupress_booted', $this );
	}

	/**
	 * Service classes to load. Admin-only services are skipped on the frontend.
	 *
	 * @return string[]
	 */
	private function get_service_classes() {
		$services = array(
			I18n::class,
		);

		if ( is_admin() ) {
			$services[] = Admin::class;
			$services[] = Settings::class;
			$services[] = Theme::class;
			$services[] = SettingsPages::class;
			$services[] = DashboardCards::class;
			$services[] = Sidebar::class;
			$services[] = Notifications::class;
			$services[] = Dock::class;
		}

		/**
		 * Filters the list of service classes UbuntuPress instantiates on boot.
		 *
		 * @param string[] $services Fully qualified class names.
		 */
		return apply_filters( 'ubuntupress_services', $services );
	}

	/**
	 * Retrieves a booted service instance.
	 *
	 * @param string $class Fully qualified class name.
	 * @return object|null
	 */
	public function get( $class ) {
		return isset( $this->services[ $class ] ) ? $this->services[ $class ] : null;
	}

	public function loader() {
		return $this->loader;
	}

	public function version() {
		return UBUNTUPRESS_VERSION;
	}

	/**
	 * Cache-busting version for a plugin asset: the file's modification time,
	 * so browsers refetch it whenever it changes.
	 *
	 * @param string $path Asset path relative to the plugin root.
	 * @return string
	 */
	public static function asset_version( $path ) {
		$mtime = @filemtime( UBUNTUPRESS_PATH . $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $mtime ? (string) $mtime : UBUNTUPRESS_VERSION;
	}
}
