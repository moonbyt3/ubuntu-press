<?php
/**
 * Removes plugin data when WPSkin is deleted from the Plugins screen.
 *
 * @package WPSkin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpskin_settings' );
delete_option( 'wpskin_version' );
