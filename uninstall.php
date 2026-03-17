<?php
/**
 * Barion Pixel Uninstall
 *
 * Fired when the plugin is uninstalled via the WordPress admin.
 * Cleans up plugin options from the database.
 *
 * @package ABPW
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings
delete_option( 'abpw_settings' );
