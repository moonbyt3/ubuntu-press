<?php
/**
 * Removes plugin data when UbuntuPress is deleted from the Plugins screen.
 *
 * @package UbuntuPress
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ubuntupress_settings' );
delete_option( 'ubuntupress_version' );
