<?php
/**
 * Plugin Name:       UbuntuPress
 * Description:       Gives the WordPress admin an Ubuntu look: light and dark styles, a card-based Dashboard, an optional hidden sidebar and a notification center in the toolbar.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            UbuntuPress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ubuntupress
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UBUNTUPRESS_VERSION', '1.0.0' );
define( 'UBUNTUPRESS_FILE', __FILE__ );
define( 'UBUNTUPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'UBUNTUPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'UBUNTUPRESS_BASENAME', plugin_basename( __FILE__ ) );

require_once UBUNTUPRESS_PATH . 'src/Autoloader.php';

\UbuntuPress\Autoloader::register();

register_activation_hook( __FILE__, array( \UbuntuPress\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \UbuntuPress\Deactivator::class, 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return \UbuntuPress\Plugin
 */
function ubuntupress() {
	return \UbuntuPress\Plugin::instance();
}

add_action( 'plugins_loaded', array( ubuntupress(), 'boot' ) );
