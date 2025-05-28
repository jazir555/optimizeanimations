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
		$player_script_handle = $this->plugin_name . '-player'; // Renamed for clarity, was $this->plugin_name
		$detector_script_handle = $this->plugin_name . '-detector';

		// Retrieve animation cache metadata.
		$animation_cache_version = get_option( 'lha_animation_cache_version' );
		$detected_animations_data = get_option( 'lha_detected_animations_data', false ); // Default to false if not found

		// Determine if the animation cache is considered valid.
		$is_cache_valid = ( false !== $detected_animations_data && ! empty( $animation_cache_version ) );

		// Retrieve all plugin options.
		$options = get_option( $this->plugin_name . '_options', array() );

		// Prepare settings data for player/detector scripts.
		// This includes general player settings and specific settings for the shunt script if used.
		$player_settings = array(
			'lazyLoadAnimations'            => isset( $options['lazy_load_animations'] ) ? (bool) $options['lazy_load_animations'] : true,
			'intersectionObserverThreshold' => isset( $options['intersection_observer_threshold'] ) ? (float) $options['intersection_observer_threshold'] : 0.1,
			'debugMode'                     => isset( $options['enable_debug_mode'] ) ? (bool) $options['enable_debug_mode'] : false, 
			'animationTriggerClass'         => 'lha-animate-now', // Default trigger class for CSS animations.
			
			// --- Phase 4: Shunt Interception Control Settings for lhaPreloadedSettings ---
			// These control if the shunt script will attempt to intercept jQuery/GSAP calls.
			// Defaults to true if the specific option is not found (e.g., new install, or before these settings were added).
			'shuntEnableJqueryInterception' => isset( $options['shunt_enable_jquery_interception'] ) ? 
												(bool) $options['shunt_enable_jquery_interception'] : true,
			'shuntEnableGsapInterception'   => isset( $options['shunt_enable_gsap_interception'] ) ? 
												(bool) $options['shunt_enable_gsap_interception'] : true,
		);

		if ( $is_cache_valid ) {
			// Cache is valid: Decide whether to use inline shunt or fallback based on data size.
			$player_script_url = plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js';
			$cached_animations = is_array( $detected_animations_data ) ? $detected_animations_data : array();

			// --- Phase 4: Shunt Data Size Check ---
			// Retrieve the admin-defined threshold for inlining data with the shunt script. Default to 5KB.
			$shunt_threshold_kb = isset( $options['shunt_data_size_threshold_kb'] ) ? 
									(int) $options['shunt_data_size_threshold_kb'] : 5; // Default threshold.

			$use_inline_shunt = true; // Assume inline shunt will be used unless data size exceeds threshold.
			$log_data_size_check = defined( 'WP_DEBUG' ) && WP_DEBUG && $player_settings['debugMode'];

			// If $shunt_threshold_kb is 0, the size check is disabled, and inline shunt is always attempted (if cache is valid).
			if ( $shunt_threshold_kb > 0 ) {
				// Calculate the size of the JSON-encoded animation data.
				$json_cached_animations = wp_json_encode( $cached_animations );
				$data_size_bytes = strlen( $json_cached_animations );
				$data_size_kb = $data_size_bytes / 1024;

				if ( $data_size_kb > $shunt_threshold_kb ) {
					$use_inline_shunt = false; // Data size exceeds the configured threshold.
					if ( $log_data_size_check ) {
						error_log(
							sprintf(
								'LHA Animation Optimizer: Inline animation data size (~%.2f KB) exceeds threshold (%d KB). Falling back to external player script loading.',
								$data_size_kb,
								$shunt_threshold_kb
							)
						);
					}
				} elseif ( $log_data_size_check ) {
					error_log(
						sprintf(
							'LHA Animation Optimizer: Inline animation data size (~%.2f KB) is within threshold (%d KB). Proceeding with inline shunt.',
							$data_size_kb,
							$shunt_threshold_kb
						)
					);
				}
			} elseif ( $log_data_size_check ) {
				error_log( 'LHA Animation Optimizer: Shunt data size threshold is 0 KB (disabled). Proceeding with inline shunt.' );
			}

			if ( $use_inline_shunt ) {
				// --- Shunt Script Loading Logic ---
				$min_shunt_script_path = plugin_dir_path( __FILE__ ) . 'js/lha-inline-shunt-logic.min.js';
				$dev_shunt_script_path = plugin_dir_path( __FILE__ ) . 'js/lha-inline-shunt-logic.js';
				$shunt_script_content = '';

				if ( file_exists( $min_shunt_script_path ) && is_readable( $min_shunt_script_path ) ) {
					$shunt_script_content = file_get_contents( $min_shunt_script_path );
				} elseif ( file_exists( $dev_shunt_script_path ) && is_readable( $dev_shunt_script_path ) ) {
					$shunt_script_content = file_get_contents( $dev_shunt_script_path );
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $shunt_script_content ) {
						error_log( 'LHA Animation Optimizer: Minified shunt script (lha-inline-shunt-logic.min.js) is missing or unreadable. Using development version (lha-inline-shunt-logic.js) as a fallback.' );
					}
				} else {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'LHA Animation Optimizer: CRITICAL - Both minified and development shunt script files are missing or unreadable. Path attempted for minified: ' . $min_shunt_script_path );
					}
				}

				if ( ! empty( $shunt_script_content ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $player_settings['debugMode'] ) {
						error_log( 'LHA Animation Optimizer: Injecting inline shunt script. Preloaded settings: ' . wp_json_encode( $player_settings ) );
					}
					$inline_js  = '<script id="lha-inline-shunt-script" type="text/javascript">';
					$inline_js .= 'window.lhaPreloadedAnimations = ' . wp_json_encode( $cached_animations ) . ';';
					$inline_js .= 'window.lhaPreloadedSettings = ' . wp_json_encode( $player_settings ) . ';';
					$inline_js .= 'window.lhaPlayerScriptUrl = "' . esc_url( $player_script_url ) . '?ver=' . $this->version .'";';
					$inline_js .= $shunt_script_content;
					$inline_js .= '</script>';

					add_action( 'wp_head', function() use ( $inline_js ) { echo $inline_js; }, 1 );
				} else {
					// Shunt script content could not be loaded, fallback to external player script.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'LHA Animation Optimizer: Shunt script content is empty (both .min.js and .js versions failed to load). Falling back to standard player script enqueue.' );
					}
					$this->enqueue_player_script_fallback( $player_script_handle, $player_settings, $cached_animations );
				}
			} else {
				// Data size exceeded threshold, use fallback loading method.
				$this->enqueue_player_script_fallback( $player_script_handle, $player_settings, $cached_animations );
			}

		} else {
			// Cache is NOT valid or empty: Enqueue the detector script and the player script externally.
			
			// 1. Enqueue and localize the detector script.
			wp_enqueue_script(
				$detector_script_handle,
				plugin_dir_url( __FILE__ ) . 'js/lha-animation-detector.js',
				array( 'jquery' ),
				$this->version,
				true
			);
			$detector_script_settings = array(
				'ajaxUrl'                       => admin_url( 'admin-ajax.php' ),
				'saveAction'                    => 'lha_save_detected_animations',
				'saveNonce'                     => wp_create_nonce( 'lha_save_detected_animations_nonce' ),
				'enableAdvancedJQueryDetection' => isset( $options['enable_advanced_jquery_detection'] ) ? (bool) $options['enable_advanced_jquery_detection'] : true,
				'enableAdvancedGSAPDetection'   => isset( $options['enable_advanced_gsap_detection'] ) ? (bool) $options['enable_advanced_gsap_detection'] : true,
				'enableMutationObserver'        => isset( $options['enable_mutation_observer'] ) ? (bool) $options['enable_mutation_observer'] : true,
				'debugMode'                     => isset( $options['enable_debug_mode'] ) ? (bool) $options['enable_debug_mode'] : false,
			);
			wp_localize_script( $detector_script_handle, 'lhaDetectorSettings', $detector_script_settings );

			// 2. Enqueue and localize the main player script (which will act in a basic mode).
			wp_enqueue_script(
				$player_script_handle, // Use the player handle
				plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js',
				array( 'jquery' ),
				$this->version,
				true
			);
			$player_script_localization = array( // Renamed from $public_script_localization
				'settings' => $player_settings, // Pass the common player settings
				// No 'cachedAnimations' key here, as the cache is invalid/empty.
			);
			wp_localize_script( $player_script_handle, 'lhaPluginData', $player_script_localization );
		}
	}
	
	/**
	 * Fallback method to enqueue the main player script externally with full data localization.
	 * Used if the inline shunt method fails or if cache is invalid but detector isn't running.
	 *
	 * @since 2.0.0
	 * @param string $handle The script handle for the player.
	 * @param array  $player_settings Settings for the player.
	 * @param array|false $cached_animations Detected animation data, or false.
	 */
	private function enqueue_player_script_fallback( $handle, $player_settings, $cached_animations ) {
		wp_enqueue_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		$localized_data = array(
			'settings'         => $player_settings,
			'cachedAnimations' => is_array( $cached_animations ) ? $cached_animations : array(),
		);
		wp_localize_script( $handle, 'lhaPluginData', $localized_data );
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
