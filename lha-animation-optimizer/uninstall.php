<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider defining constants specific to this context.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'lha-animation-optimizer_options' );

// TODO: Add further code here to clean up other plugin data if necessary:
// * Delete custom tables
// * Delete other plugin-specific data

// This file is production-ready for its current minimal scope (security check and option deletion).
// Actual uninstall logic needs to be implemented based on plugin features if they expand.
