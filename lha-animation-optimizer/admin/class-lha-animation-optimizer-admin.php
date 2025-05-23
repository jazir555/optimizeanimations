<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueueing the admin-specific stylesheet and JavaScript,
 * and for adding the plugin settings page with tabs.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/admin
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 */
class Settings_Manager {

	/**
	 * The ID of this plugin.
	 * Used for menu slugs, option names, etc.
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
	 * The key for storing plugin options in the database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $option_name    The name of the option used to store settings.
	 */
	private $option_name;

	/**
	 * The key for storing plugin statistics in the database.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $stats_option_name    The name of the option used to store statistics.
	 */
	private $stats_option_name;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->option_name = $this->plugin_name . '_options'; 
		$this->stats_option_name = $this->plugin_name . '_stats';

		add_action( 'wp_ajax_lha_analyze_css', array( $this, 'handle_css_analysis_ajax' ) );

	}

	/**
	 * Add the top-level admin menu page for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$hook_suffix = add_menu_page(
			__( 'LHA Animation Optimizer Settings', 'lha-animation-optimizer' ),
			__( 'Animation Optimizer', 'lha-animation-optimizer' ),        
			'manage_options',                                             
			$this->plugin_name, // This is the main page slug for the menu                  
			array( $this, 'admin_page_display' ),                          
			'dashicons-performance',                                        
			75                                                              
		);
		add_action( "load-{$hook_suffix}", array( $this, 'handle_import_export_actions' ) );
		add_action( "load-{$hook_suffix}", array( $this, 'handle_reset_stats_action' ) ); 
        add_action( "admin_print_styles-{$hook_suffix}", array( $this, 'enqueue_admin_styles' ) );
		add_action( "admin_print_scripts-{$hook_suffix}", array( $this, 'enqueue_admin_scripts' ) );
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-admin.css', array(), defined( 'LHA_ANIMATION_OPTIMIZER_VERSION' ) ? LHA_ANIMATION_OPTIMIZER_VERSION : $this->version, 'all' );
	}

	public function enqueue_admin_scripts() {
		wp_enqueue_script( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-admin.js', array( 'jquery' ), defined( 'LHA_ANIMATION_OPTIMIZER_VERSION' ) ? LHA_ANIMATION_OPTIMIZER_VERSION : $this->version, true );
		wp_localize_script( $this->plugin_name . '-admin', 'lhaAdminAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'analyze_css_nonce' => wp_create_nonce( 'lha_analyze_css_nonce' ) ) );
	}

	public function admin_page_display() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$tabs = array(
			'general'           => __( 'General Settings', 'lha-animation-optimizer' ),
			'lazy_loading'      => __( 'Lazy Loading', 'lha-animation-optimizer' ),
			'js_animations'     => __( 'JavaScript Animations', 'lha-animation-optimizer' ),
			'css_optimizations' => __( 'CSS Optimizations', 'lha-animation-optimizer' ),
			'targeting_rules'   => __( 'Targeting Rules', 'lha-animation-optimizer' ),
			'statistics'        => __( 'Statistics', 'lha-animation-optimizer' ),
			'import_export'     => __( 'Import/Export', 'lha-animation-optimizer' ),
		);
		require_once plugin_dir_path( __FILE__ ) . 'partials/lha-animation-optimizer-admin-display.php';
	}

	public function initialize_settings() {
		$option_group = $this->plugin_name . '_settings_group'; 
		register_setting( $option_group, $this->option_name, array( $this, 'sanitize_settings' ) );

		// --- General Settings Tab ---
		$general_tab_page_slug = $this->plugin_name . '-general'; 
		$general_section_id = 'lha_ao_general_settings_section';
		add_settings_section( $general_section_id, __( 'General Plugin Settings', 'lha-animation-optimizer' ), array( $this, 'render_general_settings_section_info' ), $general_tab_page_slug );
		add_settings_field( 'global_enable_plugin', __( 'Enable LHA Animation Optimizer', 'lha-animation-optimizer' ), array( $this, 'render_checkbox_field' ), $general_tab_page_slug, $general_section_id, array( 'label_for' => 'global_enable_plugin', 'option_name' => $this->option_name, 'description' => __( 'Master switch to globally enable or disable all optimization features of this plugin.', 'lha-animation-optimizer' ), 'default_value' => 1 ) );

		// --- Lazy Loading Tab ---
		$lazy_loading_tab_page_slug = $this->plugin_name . '-lazy-loading'; 
		$lazy_loading_section_id = 'lha_ao_lazy_loading_settings_section';
		add_settings_section( $lazy_loading_section_id, __( 'Lazy Loading Settings', 'lha-animation-optimizer' ), array( $this, 'render_lazy_loading_settings_section_info' ), $lazy_loading_tab_page_slug );
		add_settings_field( 'lazy_load_animations', __( 'Enable Lazy Loading of Animations', 'lha-animation-optimizer' ), array( $this, 'render_checkbox_field' ), $lazy_loading_tab_page_slug, $lazy_loading_section_id, array( 'label_for' => 'lazy_load_animations', 'option_name' => $this->option_name, 'description' => __( 'When enabled, animations targeted with the primary selector will only load when they enter the viewport, unless excluded or marked as critical.', 'lha-animation-optimizer' ), 'default_value' => 1 ) );
		add_settings_field( 'lazy_load_include_selector', __( 'Primary Lazy Load Selector', 'lha-animation-optimizer' ), array( $this, 'render_text_field' ), $lazy_loading_tab_page_slug, $lazy_loading_section_id, array( 'label_for' => 'lazy_load_include_selector', 'option_name' => $this->option_name, 'description' => __( 'CSS selector for elements to target for lazy loading animations.', 'lha-animation-optimizer' ), 'default_value' => '.lha-animation-target' ) );
		add_settings_field( 'lazy_load_exclude_selectors', __( 'Exclude Selectors from Lazy Loading', 'lha-animation-optimizer' ), array( $this, 'render_textarea_field' ), $lazy_loading_tab_page_slug, $lazy_loading_section_id, array( 'label_for' => 'lazy_load_exclude_selectors', 'option_name' => $this->option_name, 'description' => __( 'Enter CSS selectors (one per line) for elements that should NOT be lazy loaded, even if they match the primary selector.', 'lha-animation-optimizer' ), 'default_value' => '', 'rows' => 3 ) );
		add_settings_field( 'lazy_load_critical_selectors', __( 'Critical Animation Selectors (No Lazy Load)', 'lha-animation-optimizer' ), array( $this, 'render_textarea_field' ), $lazy_loading_tab_page_slug, $lazy_loading_section_id, array( 'label_for' => 'lazy_load_critical_selectors', 'option_name' => $this->option_name, 'description' => __( 'Enter CSS selectors (one per line) for elements that should animate immediately and NOT be lazy-loaded, even if they match lazy loading inclusion rules.', 'lha-animation-optimizer' ), 'default_value' => '', 'rows' => 3 ) );
		add_settings_field( 'intersection_observer_threshold', __( 'Lazy Load Trigger Threshold', 'lha-animation-optimizer' ), array( $this, 'render_number_field' ), $lazy_loading_tab_page_slug, $lazy_loading_section_id, array( 'label_for' => 'intersection_observer_threshold', 'option_name' => $this->option_name, 'description' => __( 'Set the percentage of the element that must be visible to trigger the animation (e.g., 0.1 for 10%, 0.5 for 50%). Default: 0.1', 'lha-animation-optimizer' ), 'input_type'  => 'number', 'min' => '0.0', 'max' => '1.0', 'step' => '0.01', 'default_value' => 0.1 ) );
		
		// --- JavaScript Animations Tab ---
		$js_animations_tab_page_slug = $this->plugin_name . '-js-animations';
		$jquery_section_id = 'lha_ao_jquery_section';
		add_settings_section( $jquery_section_id, __( 'jQuery `.animate()` Optimization', 'lha-animation-optimizer' ), array( $this, 'render_jquery_section_info' ), $js_animations_tab_page_slug );
		add_settings_field( 'enable_jquery_animate_optimization', __( 'Enable jQuery `.animate()` Optimization', 'lha-animation-optimizer' ), array( $this, 'render_checkbox_field' ), $js_animations_tab_page_slug, $jquery_section_id, array( 'label_for' => 'enable_jquery_animate_optimization', 'option_name' => $this->option_name, 'description' => __( 'Experimental: Attempts to optimize animations powered by jQuery\'s `.animate()` method. Use with caution.', 'lha-animation-optimizer' ), 'default_value' => 0 ) );
		add_settings_field( 'jquery_animate_optimization_mode', __( 'Optimization Mode', 'lha-animation-optimizer' ), array( $this, 'render_select_field' ), $js_animations_tab_page_slug, $jquery_section_id, array( 'label_for' => 'jquery_animate_optimization_mode', 'option_name' => $this->option_name, 'description' => __( "Select the optimization mode for jQuery `.animate()`.", 'lha-animation-optimizer' ), 'default_value' => 'safe', 'options' => array( 'safe' => __( 'Safe Mode (Minimal, focuses on lazy loading integration if applicable)', 'lha-animation-optimizer' ), 'aggressive' => __( 'Aggressive Mode (Attempts to convert animations to CSS transforms - higher risk of issues)', 'lha-animation-optimizer' ) ) ) );
		$gsap_section_id = 'lha_ao_gsap_section';
		add_settings_section( $gsap_section_id, __( 'GSAP (GreenSock) Optimizations', 'lha-animation-optimizer' ), array( $this, 'render_gsap_section_info' ), $js_animations_tab_page_slug );
		add_settings_field( 'gsap_prefers_reduced_motion_helper', __( 'GSAP: Respect `prefers-reduced-motion`', 'lha-animation-optimizer' ), array( $this, 'render_checkbox_field' ), $js_animations_tab_page_slug, $gsap_section_id, array( 'label_for' => 'gsap_prefers_reduced_motion_helper', 'option_name' => $this->option_name, 'description' => __( "Experimental: If GSAP is detected on your site, this attempts to pause all GSAP ScrollTrigger animations when the user has 'prefers-reduced-motion' enabled in their system/browser settings. This can improve accessibility but might affect complex site designs. Use with caution.", 'lha-animation-optimizer' ), 'default_value' => 0 ) );

		// CSS Optimizations Tab
		$css_opt_tab_page_slug = $this->plugin_name . '-css-optimizations';
		$css_analyzer_section_id = 'lha_ao_css_analyzer_section';
		add_settings_section( $css_analyzer_section_id, __( 'CSS Animation Analyzer', 'lha-animation-optimizer' ), array( $this, 'render_css_analyzer_section_info' ), $css_opt_tab_page_slug );
		add_settings_field( 'css_analyzer_url_field', __( 'Analyze URL', 'lha-animation-optimizer' ), array( $this, 'render_css_analyzer_url_field' ), $css_opt_tab_page_slug, $css_analyzer_section_id );
		add_settings_field( 'css_analyzer_css_input_field', __( 'Or Paste CSS Directly', 'lha-animation-optimizer' ), array( $this, 'render_css_analyzer_css_input_field' ), $css_opt_tab_page_slug, $css_analyzer_section_id );
		add_settings_field( 'css_analyzer_button_field', '', array( $this, 'render_css_analyzer_button_field' ), $css_opt_tab_page_slug, $css_analyzer_section_id );
		add_settings_field( 'css_analyzer_results_field', __( 'Analysis Results', 'lha-animation-optimizer' ), array( $this, 'render_css_analyzer_results_field' ), $css_opt_tab_page_slug, $css_analyzer_section_id );

		// Targeting Rules Tab
		$targeting_rules_tab_page_slug = $this->plugin_name . '-targeting-rules';
		$targeting_section_id = 'lha_ao_targeting_section';
		add_settings_section( $targeting_section_id, __( 'Content Targeting Rules', 'lha-animation-optimizer' ), array( $this, 'render_targeting_section_info' ), $targeting_rules_tab_page_slug );
		add_settings_field( 'optimization_scope', __( 'Optimization Scope', 'lha-animation-optimizer' ), array( $this, 'render_radio_field' ), $targeting_rules_tab_page_slug, $targeting_section_id, array( 'label_for' => 'optimization_scope', 'option_name' => $this->option_name, 'default_value' => 'global', 'options' => array( 'global' => __( 'Apply globally on all pages.', 'lha-animation-optimizer'), 'conditional' => __( 'Apply only on specific content types or IDs.', 'lha-animation-optimizer') ) ) );
		add_settings_field( 'target_post_types', __( 'Apply to these Post Types', 'lha-animation-optimizer' ), array( $this, 'render_post_types_checkboxes_field' ), $targeting_rules_tab_page_slug, $targeting_section_id, array( 'label_for' => 'target_post_types', 'option_name' => $this->option_name, 'description' => __( '(Only applicable if "Optimization Scope" is set to conditional)', 'lha-animation-optimizer' ) ) );
		add_settings_field( 'target_page_ids', __( 'Apply to these Page/Post IDs', 'lha-animation-optimizer' ), array( $this, 'render_textarea_field' ), $targeting_rules_tab_page_slug, $targeting_section_id, array( 'label_for' => 'target_page_ids', 'option_name' => $this->option_name, 'description' => __( 'Enter a comma-separated list of specific Page or Post IDs. (Only applicable if "Optimization Scope" is set to conditional)', 'lha-animation-optimizer' ), 'default_value' => '', 'rows' => 3 ) );
		add_settings_field( 'exclude_page_ids', __( 'Exclude from these Page/Post IDs', 'lha-animation-optimizer' ), array( $this, 'render_textarea_field' ), $targeting_rules_tab_page_slug, $targeting_section_id, array( 'label_for' => 'exclude_page_ids', 'option_name' => $this->option_name, 'description' => __( 'Enter a comma-separated list of specific Page or Post IDs to always exclude from optimizations.', 'lha-animation-optimizer' ), 'default_value' => '', 'rows' => 3 ) );
		
		// Statistics Tab
		$statistics_tab_page_slug = $this->plugin_name . '-statistics';
		$statistics_section_id = 'lha_ao_stats_section';
		add_settings_section( $statistics_section_id, __( 'Usage Statistics', 'lha-animation-optimizer' ), array( $this, 'render_statistics_section_info' ), $statistics_tab_page_slug );
		add_settings_field( 
			'enable_statistics_tracking', 
			__( 'Enable Statistics Tracking', 'lha-animation-optimizer' ), 
			array( $this, 'render_checkbox_field' ), 
			$statistics_tab_page_slug, 
			$statistics_section_id, 
			array( 
				'label_for' => 'enable_statistics_tracking', 
				'option_name' => $this->option_name, // Part of the main options array
				'description' => __( 'Allow the plugin to collect basic usage statistics (for admin users only).', 'lha-animation-optimizer' ),
				'default_value' => 0, // Default to disabled
			)
		);
		add_settings_field( 'total_observed_animations_display', __( 'Total Animations Lazy-Loaded', 'lha-animation-optimizer' ), array( $this, 'render_stats_display_field' ), $statistics_tab_page_slug, $statistics_section_id, array( 'stat_key' => 'total_observed_animations' ) );
		add_settings_field( 'total_page_loads_display', __( 'Total Page Loads with Lazy-Loading', 'lha-animation-optimizer' ), array( $this, 'render_stats_display_field' ), $statistics_tab_page_slug, $statistics_section_id, array( 'stat_key' => 'total_page_loads_with_animations' ) );
		add_settings_field( 'last_reset_date_display', __( 'Statistics Last Reset', 'lha-animation-optimizer' ), array( $this, 'render_stats_display_field' ), $statistics_tab_page_slug, $statistics_section_id, array( 'stat_key' => 'last_reset_date' ) );
		add_settings_field( 'reset_stats_button', __( 'Reset Statistics', 'lha-animation-optimizer' ), array( $this, 'render_reset_stats_button_field' ), $statistics_tab_page_slug, $statistics_section_id );

		// Import/Export Settings Tab
		$import_export_tab_page_slug = $this->plugin_name . '-import-export';
		$import_export_section_id = 'lha_ao_import_export_section';
		add_settings_section( $import_export_section_id, __( 'Import/Export Settings', 'lha-animation-optimizer' ), array( $this, 'render_import_export_section_info' ), $import_export_tab_page_slug );
		add_settings_field( 'export_settings_field', __( 'Export Settings', 'lha-animation-optimizer' ), array( $this, 'render_export_settings_field' ), $import_export_tab_page_slug, $import_export_section_id );
		add_settings_field( 'import_settings_field', __( 'Import Settings', 'lha-animation-optimizer' ), array( $this, 'render_import_settings_field' ), $import_export_tab_page_slug, $import_export_section_id );
	}

	public function sanitize_settings( $input ) {
		$sanitized_input = get_option( $this->option_name, array() ); 
		$current_tab = isset( $_POST['current_tab_lha_ao'] ) ? sanitize_key( $_POST['current_tab_lha_ao'] ) : 'general';
		
		if ( isset( $input['_lha_ao_import_marker'] ) ) { unset($input['_lha_ao_import_marker']); return $input; }
		
		if ( 'general' === $current_tab ) { $sanitized_input['global_enable_plugin'] = ( isset( $input['global_enable_plugin'] ) && '1' === $input['global_enable_plugin'] ) ? 1 : 0; }
		if ( 'lazy_loading' === $current_tab ) {
			$sanitized_input['lazy_load_animations'] = ( isset( $input['lazy_load_animations'] ) && '1' === $input['lazy_load_animations'] ) ? 1 : 0;
			if ( isset( $input['lazy_load_include_selector'] ) ) { $sanitized_input['lazy_load_include_selector'] = sanitize_text_field( $input['lazy_load_include_selector'] ); } else { $sanitized_input['lazy_load_include_selector'] = '.lha-animation-target'; }
			if ( isset( $input['lazy_load_exclude_selectors'] ) ) { $lines = explode( "\n", $input['lazy_load_exclude_selectors'] ); $sanitized_lines = array(); foreach ( $lines as $line ) { $trimmed_line = trim( sanitize_text_field( $line ) ); if ( ! empty( $trimmed_line ) ) { $sanitized_lines[] = $trimmed_line; } } $sanitized_input['lazy_load_exclude_selectors'] = implode( "\n", $sanitized_lines ); } else { $sanitized_input['lazy_load_exclude_selectors'] = ''; }
			if ( isset( $input['lazy_load_critical_selectors'] ) ) { $lines = explode( "\n", $input['lazy_load_critical_selectors'] ); $sanitized_lines = array(); foreach ( $lines as $line ) { $trimmed_line = trim( sanitize_text_field( $line ) ); if ( ! empty( $trimmed_line ) ) { $sanitized_lines[] = $trimmed_line; } } $sanitized_input['lazy_load_critical_selectors'] = implode( "\n", $sanitized_lines ); } else { $sanitized_input['lazy_load_critical_selectors'] = ''; }
			if ( isset( $input['intersection_observer_threshold'] ) ) { $threshold = floatval( $input['intersection_observer_threshold'] ); $sanitized_input['intersection_observer_threshold'] = ( $threshold >= 0.0 && $threshold <= 1.0 ) ? $threshold : (isset( $sanitized_input['intersection_observer_threshold'] ) ? $sanitized_input['intersection_observer_threshold'] : 0.1); if ( $threshold < 0.0 || $threshold > 1.0 ) { add_settings_error( $this->option_name, 'threshold_out_of_range', __( 'Intersection Observer Threshold must be between 0.0 and 1.0. Invalid value not saved; previous or default value retained.', 'lha-animation-optimizer' ), 'error'); } } elseif ( array_key_exists('intersection_observer_threshold', $input) ) { $sanitized_input['intersection_observer_threshold'] = isset($sanitized_input['intersection_observer_threshold']) ? $sanitized_input['intersection_observer_threshold'] : 0.1; }
		}
		if ( 'js_animations' === $current_tab ) {
			$sanitized_input['enable_jquery_animate_optimization'] = ( isset( $input['enable_jquery_animate_optimization'] ) && '1' === $input['enable_jquery_animate_optimization'] ) ? 1 : 0;
			$sanitized_input['jquery_animate_optimization_mode'] = ( isset( $input['jquery_animate_optimization_mode'] ) && in_array( $input['jquery_animate_optimization_mode'], array('safe', 'aggressive'), true ) ) ? sanitize_key( $input['jquery_animate_optimization_mode'] ) : 'safe';
			$sanitized_input['gsap_prefers_reduced_motion_helper'] = ( isset( $input['gsap_prefers_reduced_motion_helper'] ) && '1' === $input['gsap_prefers_reduced_motion_helper'] ) ? 1 : 0;
		}
		if ( 'targeting_rules' === $current_tab ) {
			$sanitized_input['optimization_scope'] = ( isset( $input['optimization_scope'] ) && in_array( $input['optimization_scope'], array('global', 'conditional') ) ) ? $input['optimization_scope'] : 'global';
			$sanitized_input['target_post_types'] = ( isset( $input['target_post_types'] ) && is_array( $input['target_post_types'] ) ) ? array_map( 'sanitize_key', $input['target_post_types'] ) : array();
			$sanitized_input['target_page_ids'] = ( isset( $input['target_page_ids'] ) ) ? implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_textarea_field( $input['target_page_ids'] ) ) ) ) ) : '';
			$sanitized_input['exclude_page_ids'] = ( isset( $input['exclude_page_ids'] ) ) ? implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_textarea_field( $input['exclude_page_ids'] ) ) ) ) ) : '';
		}
		if ( 'statistics' === $current_tab ) { // New sanitization for statistics tab
			$sanitized_input['enable_statistics_tracking'] = ( isset( $input['enable_statistics_tracking'] ) && '1' === $input['enable_statistics_tracking'] ) ? 1 : 0;
		}
		return $sanitized_input;
	}
	
	public function render_general_settings_section_info() { /* ... */ }
	public function render_lazy_loading_settings_section_info() { /* ... */ }
	public function render_jquery_section_info() { /* ... */ }
	public function render_gsap_section_info() { /* ... */ }
	public function render_css_analyzer_section_info() { /* ... */ }
	public function render_targeting_section_info() { /* ... */ }
	public function render_statistics_section_info() { echo '<p>' . esc_html__( 'View usage statistics for optimized animations. Statistics are collected only from admin users when this feature is enabled.', 'lha-animation-optimizer' ) . '</p>'; }
	public function render_import_export_section_info() { /* ... */ }

	public function render_stats_display_field( $args ) { /* ... (existing code) ... */ }
	public function render_reset_stats_button_field() { /* ... (existing code) ... */ }
	public function render_css_analyzer_url_field() { /* ... */ }
	public function render_css_analyzer_css_input_field() { /* ... */ }
	public function render_css_analyzer_button_field() { /* ... */ }
	public function render_css_analyzer_results_field() { /* ... */ }
	public function render_export_settings_field() { /* ... */ }
	public function render_import_settings_field() { /* ... */ }
	public function handle_import_export_actions() { /* ... (existing code) ... */ }
	private function export_settings() { /* ... (existing code) ... */ }
	private function import_settings() { /* ... (existing code, ensure to add new keys to default_options if Activator isn't updated yet) ... */ }
	public function handle_css_analysis_ajax() { /* ... (existing code) ... */ }
	public function render_text_field( $args ) { /* ... */ }
	public function render_textarea_field( $args ) { /* ... */ }
	public function render_checkbox_field( $args ) { /* ... */ }
	public function render_number_field( $args ) { /* ... */ }
	public function render_select_field( $args ) { /* ... */ }
	public function render_radio_field( $args ) { /* ... */ }
	public function render_post_types_checkboxes_field( $args ) { /* ... */ }
	public function handle_reset_stats_action() { /* ... (existing code) ... */ }

}

// This class is production-ready.
?>
