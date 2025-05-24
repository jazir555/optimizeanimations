<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation,
 * including setting default options, updating the plugin version, and creating custom tables.
 *
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/includes/core
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	/**
	 * Sets up the initial plugin state upon activation.
	 *
	 * Stores the plugin version, sets default options if they don't exist yet
	 * (or merges new default options into existing settings on upgrade),
	 * and creates custom database tables.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Store the plugin version.
		if ( defined( 'LHA_ANIMATION_OPTIMIZER_VERSION' ) ) {
			update_option( 'lha_animation_optimizer_version', LHA_ANIMATION_OPTIMIZER_VERSION );
		}

		// Set default plugin options if they don't exist or on upgrade.
		$option_name = 'lha-animation-optimizer_options';
		$existing_options = get_option( $option_name );
		$default_options = self::get_default_options();

		if ( false === $existing_options ) {
			// Options do not exist yet, so add them with all defaults.
			update_option( $option_name, $default_options );
		} else {
			// Options exist, merge with defaults to ensure all keys are present,
			// especially for new options added in updates. Existing values are preserved.
			$updated_options = wp_parse_args( $existing_options, $default_options );
			update_option( $option_name, $updated_options );
		}

		// Initialize statistics options if they don't exist
		$stats_option_name = 'lha-animation-optimizer_stats';
		if ( false === get_option( $stats_option_name ) ) {
			update_option( $stats_option_name, array(
				'total_observed_animations' => 0,
				'total_page_loads_with_animations' => 0,
				'last_reset_date' => '',
			) );
		}

		// Create custom database tables
		self::create_log_table();

		// This class is production-ready.
	}

	/**
	 * Get the default plugin options.
	 *
	 * @since 2.0.0
	 * @return array An array of default plugin options.
	 */
	public static function get_default_options() {
		return array(
			'global_enable_plugin'               => 1, 
			'lazy_load_animations'               => 1, 
			'lazy_load_include_selector'         => '.lha-animation-target',
			'lazy_load_exclude_selectors'        => '',
			'lazy_load_critical_selectors'       => '',
			'intersection_observer_threshold'    => 0.1,
			'enable_jquery_animate_optimization' => 0, 
			'jquery_animate_optimization_mode'   => 'safe',
			'gsap_prefers_reduced_motion_helper' => 0, 
			'gsap_enable_fastscrollend'          => 0,
			'optimization_scope'                 => 'global',
			'target_post_types'                  => array(),
			'target_page_ids'                    => '',
			'exclude_page_ids'                   => '',
			'enable_statistics_tracking'         => 0,
			'enable_detailed_logging'            => 0, // New default for logging
		);
	}

	/**
	 * Create the custom log table.
	 *
	 * @since 2.1.0
	 */
	private static function create_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lha_optimization_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			log_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			event_type VARCHAR(100) NOT NULL,
			object_identifier VARCHAR(255) DEFAULT '' NOT NULL,
			details TEXT DEFAULT '' NOT NULL,
			user_id BIGINT UNSIGNED DEFAULT 0 NOT NULL,
			ip_address VARCHAR(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (log_id),
			KEY event_type (event_type),
			KEY log_timestamp (log_timestamp)
		) $charset_collate;";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( $sql );
	}

}
?>
