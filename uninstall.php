<?php
/**
 * Barion Pixel Uninstall
 *
 * Fired when the plugin is uninstalled via the WordPress admin.
 * Cleans up plugin options from the database.
 *
 * @package WC_Barion_Pixel
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings
delete_option( 'wc_barion_pixel_settings' );
delete_option( 'wc_barion_pixel_probe' );
