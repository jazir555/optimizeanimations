<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueueing the admin-specific stylesheet and JavaScript,
 * and for adding the plugin settings page.
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
 * Defines the plugin name, version, and two examples hooks for
 * enqueueing the admin-specific stylesheet and JavaScript.
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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->option_name = $plugin_name . '_options'; // e.g., lha-animation-optimizer_options

	}

	/**
	 * Add the top-level admin menu page for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'LHA Animation Optimizer Settings', 'lha-animation-optimizer' ), // Page Title
			__( 'Animation Optimizer', 'lha-animation-optimizer' ),               // Menu Title
			'manage_options',                                                      // Capability
			$this->plugin_name,                                                    // Menu Slug
			array( $this, 'admin_page_display' ),                                 // Callback function
			'dashicons-performance',                                               // Icon URL
			75                                                                     // Position
		);
	}

	/**
	 * Callback to render the HTML for the settings page.
	 *
	 * @since    1.0.0
	 */
	public function admin_page_display() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Include the settings page partial
		require_once plugin_dir_path( __FILE__ ) . 'partials/lha-animation-optimizer-admin-display.php';
	}

	/**
	 * Register settings, sections, and fields using the WordPress Settings API.
	 * Hooked to 'admin_init'.
	 *
	 * @since    1.0.0
	 */
	public function initialize_settings() {
		// Setting Group
		$option_group = $this->plugin_name . '_settings_group'; // e.g., lha-animation-optimizer_settings_group

		register_setting(
			$option_group,
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);

		// Settings Section ID
		$section_id = $this->plugin_name . '_general_settings_section';

		add_settings_section(
			$section_id,
			__( 'General Settings', 'lha-animation-optimizer' ),
			array( $this, 'render_general_settings_section_info' ),
			$this->plugin_name // Page slug where this section will be shown
		);

		// Lazy Load Animations Field
		add_settings_field(
			'lazy_load_animations',
			__( 'Enable Lazy Loading of Animations', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'lazy_load_animations',
				'option_name' => $this->option_name,
				'description' => __( 'When enabled, animations will only load when they enter the viewport.', 'lha-animation-optimizer' ),
			)
		);

		// Intersection Observer Threshold Field
		add_settings_field(
			'intersection_observer_threshold',
			__( 'Lazy Load Trigger Threshold', 'lha-animation-optimizer' ),
			array( $this, 'render_number_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'intersection_observer_threshold',
				'option_name' => $this->option_name,
				'description' => __( 'Set the percentage of the element that must be visible to trigger the animation (e.g., 0.1 for 10%, 0.5 for 50%). Default: 0.1', 'lha-animation-optimizer' ),
				'input_type'  => 'number',
				'min'         => '0.0',
				'max'         => '1.0',
				'step'        => '0.01', // Allow finer granularity
			)
		);

		// Clear Animation Cache Button Field
		add_settings_field(
			'clear_animation_cache',
			__( 'Clear Animation Cache', 'lha-animation-optimizer' ),
			array( $this, 'render_clear_cache_button_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'button_id'   => 'lha_clear_animation_cache_button',
				'description' => __( 'Click this button to clear the animation cache. This will force animations to be reprocessed and can help resolve issues.', 'lha-animation-optimizer' ),
			)
		);

		// --- New Settings Fields for Detection Mechanisms and Debug Mode ---

		// Enable Advanced jQuery Detection Field
		add_settings_field(
			'enable_advanced_jquery_detection',
			__( 'Enable Advanced jQuery Detection', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'enable_advanced_jquery_detection',
				'option_name' => $this->option_name,
				'default_value' => 1, // Default to true (checked)
				'description' => __( 'Detect animations from specific jQuery methods like fadeIn, slideUp, etc., beyond the basic .animate().', 'lha-animation-optimizer' ),
			)
		);

		// Enable Advanced GSAP Detection Field
		add_settings_field(
			'enable_advanced_gsap_detection',
			__( 'Enable Advanced GSAP Detection', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'enable_advanced_gsap_detection',
				'option_name' => $this->option_name,
				'default_value' => 1, // Default to true (checked)
				'description' => __( 'Attempt to detect more GSAP animations by observing Timeline creation.', 'lha-animation-optimizer' ),
			)
		);

		// Enable MutationObserver Field
		add_settings_field(
			'enable_mutation_observer',
			__( 'Enable CSS Animation/Transition Detection (MutationObserver)', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'enable_mutation_observer',
				'option_name' => $this->option_name,
				'default_value' => 1, // Default to true (checked)
				'description' => __( 'Detect CSS-based animations and transitions. May have a performance impact on complex sites.', 'lha-animation-optimizer' ),
			)
		);

		// Enable Debug Mode Field
		add_settings_field(
			'enable_debug_mode',
			__( 'Enable Debug Mode', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'enable_debug_mode',
				'option_name' => $this->option_name,
				'default_value' => 0, // Default to false (unchecked)
				'description' => __( 'Log detailed animation detection information to the browser console. Useful for troubleshooting.', 'lha-animation-optimizer' ),
			)
		);
	}

	/**
	 * Render the Clear Animation Cache button.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Arguments passed to this callback.
	 */
	public function render_clear_cache_button_field( $args ) {
		echo '<button type="button" id="' . esc_attr( $args['button_id'] ) . '" class="button">';
		echo esc_html__( 'Clear Animation Cache Now', 'lha-animation-optimizer' );
		echo '</button>';
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Sanitize all settings passed to the Settings API.
	 *
	 * @since    1.0.0
	 * @param    array    $input    The unsanitized input array.
	 * @return   array              The sanitized input array.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		// Sanitize 'lazy_load_animations' (checkbox, so it's either 1 or not set)
		$sanitized_input['lazy_load_animations'] = ( isset( $input['lazy_load_animations'] ) && '1' === $input['lazy_load_animations'] ) ? 1 : 0;

		// Sanitize 'intersection_observer_threshold' (float between 0.0 and 1.0)
		if ( isset( $input['intersection_observer_threshold'] ) ) {
			$threshold = floatval( $input['intersection_observer_threshold'] );
			if ( $threshold >= 0.0 && $threshold <= 1.0 ) {
				$sanitized_input['intersection_observer_threshold'] = $threshold;
			} else {
				// If out of range, set to default or add an error
				$sanitized_input['intersection_observer_threshold'] = 0.1; // Default value
				add_settings_error(
					$this->option_name,
					'threshold_out_of_range',
					__( 'Intersection Observer Threshold must be between 0.0 and 1.0. Reverted to default.', 'lha-animation-optimizer' ),
					'error'
				);
			}
		} else {
			// If not set, provide a default (or handle as needed)
			$sanitized_input['intersection_observer_threshold'] = 0.1;
		}

		// Sanitize new checkbox settings
		$checkbox_settings = array(
			'enable_advanced_jquery_detection',
			'enable_advanced_gsap_detection',
			'enable_mutation_observer',
			'enable_debug_mode',
		);

		foreach ( $checkbox_settings as $setting_name ) {
			$sanitized_input[ $setting_name ] = ( isset( $input[ $setting_name ] ) && '1' === $input[ $setting_name ] ) ? 1 : 0;
		}

		// After saving any settings in this group, always update the cache version and clear detected data.
		// This ensures that any changes to settings that might affect animation detection or playback
		// will trigger a fresh detection cycle on the public side.
		update_option( 'lha_animation_cache_version', time() );
		delete_option( 'lha_detected_animations_data' ); // Clear the actual cached data.

		return $sanitized_input;
	}

	/**
	 * Render a description for the general settings section.
	 *
	 * @since    1.0.0
	 */
	public function render_general_settings_section_info() {
		echo '<p>' . esc_html__( 'Configure the general settings for the LHA Animation Optimizer plugin.', 'lha-animation-optimizer' ) . '</p>';
	}

	/**
	 * Render a checkbox field for a setting.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Arguments passed to this callback.
	 */
	public function render_checkbox_field( $args ) {
		$options = get_option( $args['option_name'], array() ); 
		// Use the 'default_value' from $args if the option is not set yet.
		// This correctly applies defaults on the first view of the settings page after adding a new option.
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : ( isset( $args['default_value'] ) ? $args['default_value'] : 0 );

		echo '<input type="checkbox" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['label_for'] . ']' ) . '" value="1" ' . checked( 1, $value, false ) . ' />';
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a number input field for a setting.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Arguments passed to this callback.
	 */
	public function render_number_field( $args ) {
		$options = get_option( $args['option_name'], array() );
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 0.1; // Default to 0.1

		echo '<input type="' . esc_attr( $args['input_type'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['label_for'] . ']' ) . '" value="' . esc_attr( $value ) . '"';
		if ( isset( $args['min'] ) ) {
			echo ' min="' . esc_attr( $args['min'] ) . '"';
		}
		if ( isset( $args['max'] ) ) {
			echo ' max="' . esc_attr( $args['max'] ) . '"';
		}
		if ( isset( $args['step'] ) ) {
			echo ' step="' . esc_attr( $args['step'] ) . '"';
		}
		echo ' class="small-text" />'; // small-text is a WordPress admin CSS class

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Enqueue admin-specific stylesheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name . '-admin',
			plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue admin-specific JavaScript.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-admin',
			plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-admin.js',
			array( 'jquery' ),
			$this->version,
			true // Load in footer
		);

		// Localize script with data for AJAX requests
		wp_localize_script(
			$this->plugin_name . '-admin',
			'lhaAdminAjax',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'clearCacheNonce' => wp_create_nonce( 'lha_clear_cache_nonce' ),
				'clearCacheAction' => 'lha_clear_animation_cache', // Added action name for JS
				'successMessage'  => __( 'Animation cache cleared successfully!', 'lha-animation-optimizer' ),
				'errorMessage'    => __( 'Error clearing animation cache.', 'lha-animation-optimizer' ),
			)
		);
	}

	/**
	 * AJAX handler for clearing the animation cache.
	 *
	 * @since 1.0.0
	 */
	public function ajax_clear_animation_cache() {
		// Verify the nonce for security.
		// The nonce field name 'nonce_field_name_in_js' should match what you send from JS.
		// For simplicity, we'll assume the JS sends 'nonce' as the key for the nonce value.
		check_ajax_referer( 'lha_clear_cache_nonce', 'nonce' ); // Verify the AJAX request authenticity.

		// Update the cache version option. This effectively invalidates the animation cache,
		// prompting the detector script to run on the next public page load.
		update_option( 'lha_animation_cache_version', time() );

		// Also, explicitly clear the detected animations data.
		delete_option( 'lha_detected_animations_data' );

		// Send a success response.
		wp_send_json_success(
			array(
				'message' => __( 'Animation cache version updated.', 'lha-animation-optimizer' ),
			)
		);
	}
}

// This class is production-ready.
?>
