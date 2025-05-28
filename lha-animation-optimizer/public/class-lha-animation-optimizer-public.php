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

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-public.css',
			array(),
			$this->version,
			'all'
		);

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Define handles for the public-facing scripts.
		$public_script_handle = $this->plugin_name; // Main public script (player/lazy-loader)
		$detector_script_handle = $this->plugin_name . '-detector'; // Animation detector script

		// Retrieve animation cache metadata.
		// `lha_animation_cache_version` stores a timestamp indicating when the cache was last considered fresh.
		$animation_cache_version = get_option( 'lha_animation_cache_version' );
		// `lha_detected_animations_data` stores the array of detected animation objects.
		$detected_animations_data = get_option( 'lha_detected_animations_data' );

		// Determine if the animation cache is considered valid.
		// Cache is valid if data exists (even an empty array, meaning no animations were detected)
		// and a cache version is set.
		$is_cache_valid = ( false !== $detected_animations_data ) && ! empty( $animation_cache_version );
		// Note: `empty([])` is false. `get_option` returns `false` if option not found.
		// So, `false !== $detected_animations_data` ensures the option exists.

		// Retrieve general plugin settings (lazy load toggle, threshold).
		$options = get_option( $this->plugin_name . '_options', array() ); // Admin-configurable options.
		$default_settings = array(
			'lazyLoadAnimations'            => true, // Default: lazy loading enabled.
			'intersectionObserverThreshold' => 0.1,  // Default: 10% visibility triggers animation.
		);
		$lazy_load_animations = isset( $options['lazy_load_animations'] ) ? (bool) $options['lazy_load_animations'] : $default_settings['lazyLoadAnimations'];
		$intersection_observer_threshold = isset( $options['intersection_observer_threshold'] ) ? (float) $options['intersection_observer_threshold'] : $default_settings['intersectionObserverThreshold'];

		// Prepare data for localization, common to the public script.
		$public_settings_data = array(
			'ajax_url'                      => admin_url( 'admin-ajax.php' ), // For potential future AJAX needs in public script.
			'lazyLoadAnimations'            => $lazy_load_animations,
			'intersectionObserverThreshold' => $intersection_observer_threshold,
		);

		// Always enqueue the main public script (lha-animation-optimizer-public.js).
		// This script handles lazy loading and, if cache is valid, animation playback.
		wp_enqueue_script(
			$public_script_handle,
			plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js',
			array( 'jquery' ), // jQuery as a dependency (used by public script, and detector).
			$this->version,
			true // Load in footer.
		);

		if ( $is_cache_valid ) {
			// Cache is valid: Provide settings and cached animation data to the public script.
			// The public script will then act as a player for these animations.
			$localized_data = array(
				'settings'         => $public_settings_data,
				'cachedAnimations' => $detected_animations_data, // Pass the array of detected animation objects.
			);
			wp_localize_script( $public_script_handle, 'lhaPluginData', $localized_data );

		} else {
			// Cache is NOT valid or empty: Enqueue the detector script and localize both scripts.
			// The detector script will attempt to find animations and send them for caching.

			// 1. Enqueue and localize the detector script (lha-animation-detector.js).
			wp_enqueue_script(
				$detector_script_handle,
				plugin_dir_url( __FILE__ ) . 'js/lha-animation-detector.js',
				array( 'jquery' ), // Detector also uses jQuery (or its own fetch fallback) for AJAX.
				$this->version,
				true // Load in footer.
			);
			// Provide necessary settings for the detector script (AJAX URL, action, nonce).
			$detector_script_settings = array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'saveAction' => 'lha_save_detected_animations', // AJAX action for saving detected data.
				'saveNonce'  => wp_create_nonce( 'lha_save_detected_animations_nonce' ), // Nonce for the save action.
			);
			wp_localize_script( $detector_script_handle, 'lhaDetectorSettings', $detector_script_settings );

			// 2. Localize the main public script with settings only (no cachedAnimations).
			// In this scenario, it primarily handles lazy loading for non-JS/manual animations.
			$public_script_localization = array(
				'settings' => $public_settings_data,
				// No 'cachedAnimations' key, as the cache is invalid/empty.
			);
			wp_localize_script( $public_script_handle, 'lhaPluginData', $public_script_localization );
		}
	}

	/**
	 * AJAX handler for saving detected animation data sent by lha-animation-detector.js.
	 *
	 * @since 1.0.0 (New in version 1.1.0 for animation caching)
	 */
	public function ajax_save_detected_animations() {
		// 1. Verify the nonce for security.
		// The key 'nonce' in check_ajax_referer corresponds to the key sent in the AJAX data.
		check_ajax_referer( 'lha_save_detected_animations_nonce', 'nonce' );

		// 2. Retrieve and process the submitted animation data.
		// `stripslashes` is used because WordPress automatically adds slashes to POST data.
		$detected_animations_json = isset( $_POST['detected_animations'] ) ? stripslashes( $_POST['detected_animations'] ) : '[]';
		$detected_animations = json_decode( $detected_animations_json, true ); // true for associative array.

		// Check for JSON decoding errors.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid JSON data received.', 'lha-animation-optimizer' ),
				),
				400 // Bad Request.
			);
			return;
		}

		// Sanitize the decoded array of animation objects.
		// `sanitize_detected_animations_deep` is a custom recursive function for this structure.
		$sanitized_animations = is_array( $detected_animations ) ? $this->sanitize_detected_animations_deep( $detected_animations ) : array();

		// 3. Store the sanitized animation data in WordPress options.
		// This will be retrieved by `enqueue_scripts` on subsequent page loads.
		update_option( 'lha_detected_animations_data', $sanitized_animations );

		// 4. Update the cache version timestamp.
		// This signifies that a new cache has been successfully generated.
		update_option( 'lha_animation_cache_version', time() );

		// 5. Send a success JSON response back to the detector script.
		wp_send_json_success(
			array(
				'message' => __( 'Detected animation data saved successfully.', 'lha-animation-optimizer' ),
				'data_received_count' => count( $sanitized_animations ), // Send back count for confirmation.
			)
		);
	}

	/**
	 * Recursively sanitize detected animation data.
	 * This function is designed to handle the specific structure of animation objects
	 * collected by the detector script.
	 *
	 * @param array $array The array of animation objects or properties to sanitize.
	 * @return array The sanitized array.
	 */
	private function sanitize_detected_animations_deep( $array ) {
		$sanitized_array = array();
		foreach ( $array as $key => $value ) {
			// Sanitize the key itself to prevent issues if unexpected keys are submitted.
			$s_key = sanitize_text_field( $key );

			if ( is_array( $value ) ) {
				// If the value is an array, recurse into it.
				// This handles nested structures like 'jquery_properties' or 'gsap_to_vars'.
				$sanitized_array[ $s_key ] = $this->sanitize_detected_animations_deep( $value );
			} elseif ( is_string( $value ) ) {
				// For string values, apply context-specific sanitization.
				// This attempts to balance security with the need to preserve valid animation data.
				if ( 'selector' === $s_key ) {
					// Selectors can contain a wide range of characters.
					// `sanitize_text_field` is a good general choice. More restrictive like `sanitize_html_class`
					// would be too limiting for complex selectors (e.g. `div > .class#id`).
					$sanitized_array[ $s_key ] = sanitize_text_field( $value );
				} elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
					// If it looks like a URL (e.g., for background images in properties).
					$sanitized_array[ $s_key ] = esc_url_raw( $value );
				} elseif ( preg_match( '/^#[a-f0-9]{3,6}$/i', $value ) ) { // Hex color
					$sanitized_array[ $s_key ] = sanitize_hex_color( $value );
				} elseif ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vw|vh|s|ms|deg)?$/', $value ) ) { // Numeric with optional unit
					$sanitized_array[ $s_key ] = sanitize_text_field( $value ); // Allows numbers, units
				} elseif ( in_array( strtolower( $value ), array( 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'swing', 'true', 'false', 'null' ), true ) ) {
					// Common keywords for easing, booleans as strings
					$sanitized_array[ $s_key ] = sanitize_key( $value ); // `sanitize_key` for simple keywords
				} else {
					// General fallback for other string properties (e.g., class names, string values in properties).
					$sanitized_array[ $s_key ] = sanitize_text_field( $value );
				}
			} elseif ( is_numeric( $value ) ) {
				// Numeric values (durations, opacity, raw numbers) are allowed directly.
				$sanitized_array[ $s_key ] = $value;
			} elseif ( is_bool( $value ) ) {
				// Boolean values are allowed directly.
				$sanitized_array[ $s_key ] = $value;
			} else {
				// For any other types, try to convert to string and sanitize as text.
				$sanitized_array[ $s_key ] = sanitize_text_field( (string) $value );
			}
		}
		return $sanitized_array;
	}
}

// This class is production-ready.
?>
