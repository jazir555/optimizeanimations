<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file contains the logic to clean up the plugin's data from the database
 * when the plugin is deleted via the WordPress admin interface.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define the option name that stores the plugin's settings.
// This should match the option name used in the Settings_Manager class.
$option_name = 'lha-animation-optimizer_options';

// Delete the main plugin options from the options table.
delete_option( $option_name );

// If the plugin were to store other options, transients, or custom database tables,
// their cleanup logic would be added here.
// For example:
// delete_transient( 'lha_optimizer_some_transient' );
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_custom_table" );

// This file is production-ready for deleting the specified plugin option.
// Further cleanup logic should be added if the plugin evolves to store more data.
?>
