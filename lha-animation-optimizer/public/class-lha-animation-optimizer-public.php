<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for
 * enqueueing the public-facing stylesheet and JavaScript.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/public
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Public_Facing;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 */
class Public_Script_Manager {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		add_action( 'wp_ajax_lha_update_stats', array( $this, 'handle_update_stats_ajax' ) );
	}

	/**
	 * Check if optimizations should run on the current page based on targeting rules.
	 *
	 * @since 1.1.0
	 * @return bool True if optimizations should run, false otherwise.
	 */
	private function should_run_optimizations() {
		$options = get_option( $this->plugin_name . '_options', array() );

		$global_enable_plugin = isset( $options['global_enable_plugin'] ) ? (bool) $options['global_enable_plugin'] : true;
		if ( ! $global_enable_plugin ) {
			return false;
		}
		
		$optimization_scope = isset( $options['optimization_scope'] ) ? $options['optimization_scope'] : 'global';
		$exclude_page_ids_raw = isset( $options['exclude_page_ids'] ) ? $options['exclude_page_ids'] : '';
		
		$current_id = get_the_ID();

		if ( ! empty( $exclude_page_ids_raw ) && $current_id ) {
			$exclude_ids = array_map( 'absint', explode( ',', $exclude_page_ids_raw ) );
			if ( in_array( $current_id, $exclude_ids, true ) ) {
				return false; 
			}
		}

		if ( 'global' === $optimization_scope ) {
			return true; 
		}

		if ( 'conditional' === $optimization_scope ) {
			if ( ! $current_id ) { 
				$is_targeted_archive = false;
				if (is_archive()) {
					$current_post_type_archive = get_queried_object();
					if ($current_post_type_archive instanceof \WP_Term) { 
                    } elseif ($current_post_type_archive instanceof \WP_Post_Type) {
						$target_post_types = isset( $options['target_post_types'] ) && is_array( $options['target_post_types'] ) ? $options['target_post_types'] : array();
						if (in_array($current_post_type_archive->name, $target_post_types, true)) {
							$is_targeted_archive = true;
						}
					}
				}
				if (!$is_targeted_archive) return false;

			} else { 
				$target_post_types = isset( $options['target_post_types'] ) && is_array( $options['target_post_types'] ) ? $options['target_post_types'] : array();
				$target_page_ids_raw = isset( $options['target_page_ids'] ) ? $options['target_page_ids'] : '';
				$target_ids = array();
				if ( ! empty( $target_page_ids_raw ) ) {
					$target_ids = array_map( 'absint', explode( ',', $target_page_ids_raw ) );
				}
				$current_post_type = get_post_type( $current_id );
				if ( ! in_array( $current_post_type, $target_post_types, true ) && ! in_array( $current_id, $target_ids, true ) ) {
					return false; 
				}
			}
		}
		return true; 
	}

	public function enqueue_styles() {
		if ( ! $this->should_run_optimizations() ) { return; }
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-public.css', array(), defined( 'LHA_ANIMATION_OPTIMIZER_VERSION' ) ? LHA_ANIMATION_OPTIMIZER_VERSION : $this->version, 'all' );
	}

	public function enqueue_scripts() {
		if ( ! $this->should_run_optimizations() ) { return; }

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js', array( 'jquery' ), defined( 'LHA_ANIMATION_OPTIMIZER_VERSION' ) ? LHA_ANIMATION_OPTIMIZER_VERSION : $this->version, true );

		$options = get_option( $this->plugin_name . '_options', array() );
		$default_settings = \LHA\Animation_Optimizer\Core\Activator::get_default_options();
		
		$script_data = array(
			'ajax_url'                             => admin_url( 'admin-ajax.php' ),
			'update_stats_nonce'                   => wp_create_nonce( 'lha_update_stats_nonce' ),
			'lazyLoadAnimations'                   => isset( $options['lazy_load_animations'] ) ? (bool) $options['lazy_load_animations'] : $default_settings['lazy_load_animations'],
			'intersectionObserverThreshold'        => isset( $options['intersection_observer_threshold'] ) ? (float) $options['intersection_observer_threshold'] : $default_settings['intersection_observer_threshold'],
			'enable_jquery_animate_optimization'   => isset( $options['enable_jquery_animate_optimization'] ) ? (bool) $options['enable_jquery_animate_optimization'] : $default_settings['enable_jquery_animate_optimization'],
			'jquery_animate_optimization_mode'     => isset( $options['jquery_animate_optimization_mode'] ) ? sanitize_key($options['jquery_animate_optimization_mode']) : $default_settings['jquery_animate_optimization_mode'],
			'gsap_prefers_reduced_motion_helper'   => isset( $options['gsap_prefers_reduced_motion_helper'] ) ? (bool) $options['gsap_prefers_reduced_motion_helper'] : $default_settings['gsap_prefers_reduced_motion_helper'],
			'lazy_load_include_selector'           => isset( $options['lazy_load_include_selector'] ) && !empty(trim($options['lazy_load_include_selector'])) ? trim($options['lazy_load_include_selector']) : $default_settings['lazy_load_include_selector'],
			'lazy_load_exclude_selectors'          => isset( $options['lazy_load_exclude_selectors'] ) ? trim($options['lazy_load_exclude_selectors']) : $default_settings['lazy_load_exclude_selectors'],
			'lazy_load_critical_selectors'         => isset( $options['lazy_load_critical_selectors'] ) ? trim($options['lazy_load_critical_selectors']) : $default_settings['lazy_load_critical_selectors'],
			'enable_statistics_tracking'           => isset( $options['enable_statistics_tracking'] ) ? (bool) $options['enable_statistics_tracking'] : $default_settings['enable_statistics_tracking'],
			'globalEnablePlugin'                   => isset( $options['global_enable_plugin'] ) ? (bool) $options['global_enable_plugin'] : $default_settings['global_enable_plugin'], 
		);

		wp_localize_script( $this->plugin_name, 'lhaAnimationOptimizerSettings', $script_data );
	}

	/**
	 * Handle AJAX request for updating animation statistics.
	 *
	 * @since 1.1.0
	 */
	public function handle_update_stats_ajax() {
		check_ajax_referer( 'lha_update_stats_nonce', 'nonce' );

		$options = get_option( $this->plugin_name . '_options', array() );
		$enable_statistics_tracking = isset( $options['enable_statistics_tracking'] ) ? (bool) $options['enable_statistics_tracking'] : false; // Default false if not set

		if ( ! $enable_statistics_tracking ) {
			wp_send_json_error( array( 'message' => __( 'Statistics tracking is disabled.', 'lha-animation-optimizer' ) ), 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) { // Changed from 'read' to 'manage_options'
			wp_send_json_error( array( 'message' => __( 'Permission denied. Statistics are only tracked for administrators.', 'lha-animation-optimizer' ) ), 403 );
		}

		$observed_count = isset( $_POST['observed_animations_count'] ) ? absint( $_POST['observed_animations_count'] ) : 0;

		if ( $observed_count > 0 ) {
			$stats_option_name = $this->plugin_name . '_stats';
			$current_stats = get_option( $stats_option_name, array() );

			$defaults = array(
				'total_observed_animations' => 0,
				'total_page_loads_with_animations' => 0,
				'last_reset_date' => '',
			);
			$current_stats = wp_parse_args( $current_stats, $defaults );

			$current_stats['total_observed_animations'] += $observed_count;
			$current_stats['total_page_loads_with_animations']++;
			
			update_option( $stats_option_name, $current_stats );
			wp_send_json_success( array( 'message' => 'Stats updated.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'No new animations observed.' ) );
		}
	}
}

// This class is production-ready.
?>
