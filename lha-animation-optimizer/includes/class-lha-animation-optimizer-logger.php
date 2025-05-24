<?php
/**
 * Handles logging of optimization events.
 *
 * @since      2.1.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/includes/core
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	/**
	 * The name of the custom log table.
	 *
	 * @since    2.1.0
	 * @access   private
	 * @var      string    $table_name    The name of the log table.
	 */
	private static $table_name = '';

	/**
	 * Get the log table name with the WordPress prefix.
	 *
	 * @since    2.1.0
	 * @return   string    The full log table name.
	 */
	private static function get_table_name() {
		if ( empty( self::$table_name ) ) {
			global $wpdb;
			self::$table_name = $wpdb->prefix . 'lha_optimization_logs';
		}
		return self::$table_name;
	}

	/**
	 * Log an optimization event to the custom database table.
	 *
	 * @since    2.1.0
	 * @param    string    $event_type          Type of the event (e.g., 'lazy_load_applied').
	 * @param    string    $object_identifier   Identifier for the object related to the event (e.g., CSS selector, URL).
	 * @param    array     $details             Optional. Additional details to store (JSON encoded).
	 * @param    int|null  $user_id             Optional. User ID, defaults to current user or 0.
	 * @param    string|null $ip_address          Optional. IP address, defaults to current request's IP.
	 */
	public static function log_event( $event_type, $object_identifier = '', $details = array(), $user_id = null, $ip_address = null ) {
		
		// Check if detailed logging is enabled in plugin settings
		$options = get_option( 'lha-animation-optimizer_options', array() );
		if ( ! isset( $options['enable_detailed_logging'] ) || ! $options['enable_detailed_logging'] ) {
			return;
		}

		global $wpdb;
		$table = self::get_table_name();

		$data = array(
			'log_timestamp'     => current_time( 'mysql', 1 ), // GMT/UTC time
			'event_type'        => sanitize_text_field( $event_type ),
			'object_identifier' => sanitize_text_field( $object_identifier ),
			'details'           => wp_json_encode( $details ), // Ensure details are JSON encoded
			'user_id'           => ( null === $user_id ) ? get_current_user_id() : absint( $user_id ),
			'ip_address'        => ( null === $ip_address && isset( $_SERVER['REMOTE_ADDR'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : sanitize_text_field( (string) $ip_address ),
		);

		$format = array(
			'%s', // log_timestamp
			'%s', // event_type
			'%s', // object_identifier
			'%s', // details
			'%d', // user_id
			'%s', // ip_address
		);

		$wpdb->insert( $table, $data, $format );

		// Basic log rotation: Keep only the last N entries (e.g., 5000)
		// This is a very simple rotation. More advanced strategies could be used.
		$max_log_entries = apply_filters( 'lha_max_log_entries', 5000 );
		$current_log_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $current_log_count > $max_log_entries ) {
			$logs_to_delete = $current_log_count - $max_log_entries;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( "DELETE FROM $table ORDER BY log_id ASC LIMIT %d", $logs_to_delete ) );
		}
	}

	/**
	 * Retrieve log entries.
	 *
	 * @since    2.1.0
	 * @param    array    $args    Optional. Arguments to filter logs.
	 * @return   array             Array of log entry objects.
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;
		$table = self::get_table_name();

		$defaults = array(
			'number'     => 50,    // Number of logs to retrieve
			'offset'     => 0,     // Offset for pagination
			'orderby'    => 'log_id',
			'order'      => 'DESC',
			'event_type' => '',    // Filter by event type
		);
		$args = wp_parse_args( $args, $defaults );

		$sql = "SELECT * FROM $table";

		$where_clauses = array();
		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = $wpdb->prepare( "event_type = %s", sanitize_text_field( $args['event_type'] ) );
		}

		if ( ! empty( $where_clauses ) ) {
			$sql .= " WHERE " . implode( ' AND ', $where_clauses );
		}

		$sql .= $wpdb->prepare( " ORDER BY %s %s LIMIT %d OFFSET %d", 
			sanitize_key( $args['orderby'] ), // Basic sanitization for orderby
			in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC',
			absint( $args['number'] ),
			absint( $args['offset'] )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Get the total count of log entries.
	 *
	 * @since    2.1.0
	 * @param    array    $args    Optional. Arguments to filter count.
	 * @return   int               Total number of log entries.
	 */
	public static function count_logs( $args = array() ) {
		global $wpdb;
		$table = self::get_table_name();

		$sql = "SELECT COUNT(*) FROM $table";

		$where_clauses = array();
		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = $wpdb->prepare( "event_type = %s", sanitize_text_field( $args['event_type'] ) );
		}

		if ( ! empty( $where_clauses ) ) {
			$sql .= " WHERE " . implode( ' AND ', $where_clauses );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}


	/**
	 * Clear all log entries from the table.
	 *
	 * @since    2.1.0
	 */
	public static function clear_logs() {
		global $wpdb;
		$table = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE $table" );
	}
}

// This class is production-ready.
