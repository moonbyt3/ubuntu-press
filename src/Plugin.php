<?php
/**
 * Main plugin class.
 *
 * @package WPSkin
 */

namespace WPSkin;

use WPSkin\Admin\Admin;
use WPSkin\Admin\DashboardCards;
use WPSkin\Admin\Notifications;
use WPSkin\Admin\Settings;
use WPSkin\Admin\Sidebar;
use WPSkin\Contracts\Hookable;
use WPSkin\Frontend\Frontend;

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

		do_action( 'wpskin_booted', $this );
	}

	/**
	 * Service classes to load. Admin-only services are skipped on the frontend.
	 *
	 * @return string[]
	 */
	private function get_service_classes() {
		$services = array(
			I18n::class,
			Frontend::class,
		);

		if ( is_admin() ) {
			$services[] = Admin::class;
			$services[] = Settings::class;
			$services[] = DashboardCards::class;
			$services[] = Sidebar::class;
			$services[] = Notifications::class;
		}

		/**
		 * Filters the list of service classes WPSkin instantiates on boot.
		 *
		 * @param string[] $services Fully qualified class names.
		 */
		return apply_filters( 'wpskin_services', $services );
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
		return WPSKIN_VERSION;
	}
}
