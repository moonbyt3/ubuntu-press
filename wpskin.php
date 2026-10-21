<?php
/**
 * Plugin Name:       WPSkin
 * Description:       Object-oriented base plugin for WordPress.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WPSkin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpskin
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSKIN_VERSION', '1.0.0' );
define( 'WPSKIN_FILE', __FILE__ );
define( 'WPSKIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSKIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSKIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WPSKIN_PATH . 'src/Autoloader.php';

\WPSkin\Autoloader::register();

register_activation_hook( __FILE__, array( \WPSkin\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPSkin\Deactivator::class, 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return \WPSkin\Plugin
 */
function wpskin() {
	return \WPSkin\Plugin::instance();
}

add_action( 'plugins_loaded', array( wpskin(), 'boot' ) );
