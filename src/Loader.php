<?php
/**
 * Collects actions and filters, then registers them with WordPress.
 *
 * @package WPSkin
 */

namespace WPSkin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loader {

	/**
	 * @var array[]
	 */
	private $actions = array();

	/**
	 * @var array[]
	 */
	private $filters = array();

	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = compact( 'hook', 'callback', 'priority', 'accepted_args' );
		return $this;
	}

	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = compact( 'hook', 'callback', 'priority', 'accepted_args' );
		return $this;
	}

	/**
	 * Registers all queued hooks with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $filter ) {
			add_filter( $filter['hook'], $filter['callback'], $filter['priority'], $filter['accepted_args'] );
		}

		foreach ( $this->actions as $action ) {
			add_action( $action['hook'], $action['callback'], $action['priority'], $action['accepted_args'] );
		}

		$this->actions = array();
		$this->filters = array();
	}
}
