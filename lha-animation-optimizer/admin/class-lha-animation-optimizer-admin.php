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
		$options = get_option( $args['option_name'], array() ); // Get all options or empty array if not set
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 1; // Default to 1 (true/checked)

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
	 * Enqueue admin-specific stylesheets.
	 *
	 * This method is hooked to `admin_enqueue_scripts`.
	 *
	 * @since    1.0.1 // Assuming version increment for new feature
	 * @param    string    $hook_suffix    The current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		// No admin styles to enqueue yet.
		// Example:
		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue admin-specific JavaScript.
	 *
	 * This method is hooked to `admin_enqueue_scripts`.
	 *
	 * @since    1.0.1 // Assuming version increment for new feature
	 * @param    string    $hook_suffix    The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// No admin scripts to enqueue yet.
		// Example:
		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-admin.js', array( 'jquery' ), $this->version, false );
	}
}

// This class is production-ready.
?>
