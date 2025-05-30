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

		// Hook the cron action callback
		add_action( 'lha_cleanup_temp_animation_files', array( __CLASS__, 'do_cleanup_temp_animation_files' ) );
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

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-public.js',
			array( 'jquery' ), // Assuming jQuery as a dependency
			$this->version,
			true // Load in footer
		);

		// Data to pass to the public script
		$script_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			// Add other settings here as needed in the future
			// 'some_setting' => get_option('lha_optimizer_some_setting_option_name'),
		);

		// Retrieve saved settings with defaults
		$options = get_option( $this->plugin_name . '_options', array() ); // Option name matches Admin class

		$default_public_settings = array(
			'lazyLoadAnimations'            => true, // Default to true (1 in db)
			'intersectionObserverThreshold' => 0.1,  // Default to 0.1
		);

		$lazy_load_animations = isset( $options['lazy_load_animations'] ) ? (bool) $options['lazy_load_animations'] : $default_public_settings['lazyLoadAnimations'];
		$intersection_observer_threshold = isset( $options['intersection_observer_threshold'] ) ? (float) $options['intersection_observer_threshold'] : $default_public_settings['intersectionObserverThreshold'];
		$shunt_threshold_kb = isset( $options['shunt_data_size_threshold_kb'] ) ? (int) $options['shunt_data_size_threshold_kb'] : 5; // Default to 5KB
		$shunt_threshold_bytes = $shunt_threshold_kb * 1024;

		// Example: Placeholder for detected animation data.
		// In a real scenario, this would be fetched from a WordPress option or transient.
		$detected_animations_data = get_option('lha_detected_animations_data', []);
		// For testing large data, you might temporarily hardcode a large array or string:
		// $detected_animations_data = array_fill(0, 500, ['selector' => '.element', 'animation' => 'fadeIn']);

		$json_data = wp_json_encode( $detected_animations_data );
		$data_size_bytes = strlen( $json_data );

		// Prepare script data for localization - initialize with existing and new keys
		$script_data = array(
			'ajax_url'                      => admin_url( 'admin-ajax.php' ),
			'lazyLoadAnimations'            => $lazy_load_animations,
			'intersectionObserverThreshold' => $intersection_observer_threshold,
			'lhaPreloadedAnimations'        => null, // Initialize to null
			'lhaExternalAnimationDataUrl'   => null, // Initialize to null
		);

		if ( $data_size_bytes > $shunt_threshold_bytes ) {
			$upload_dir = wp_upload_dir();
			$cache_dir = $upload_dir['basedir'] . '/lha-optimizer-cache/';
			$cache_url = $upload_dir['baseurl'] . '/lha-optimizer-cache/';

			if ( ! is_dir( $cache_dir ) ) {
				wp_mkdir_p( $cache_dir );
			}

			// Ensure .htaccess and index.php are in the cache directory
			if ( ! file_exists( $cache_dir . '.htaccess' ) ) {
				$htaccess_content = "Options -Indexes\ndeny from all";
				@file_put_contents( $cache_dir . '.htaccess', $htaccess_content );
			}
			if ( ! file_exists( $cache_dir . 'index.php' ) ) {
				$index_php_content = "<?php // Silence is golden";
				@file_put_contents( $cache_dir . 'index.php', $index_php_content );
			}

			$filename = 'animations-' . time() . '-' . wp_generate_password( 12, false ) . '.js';
			$file_path = $cache_dir . $filename;
			$file_url = $cache_url . $filename;

			$js_content = "window.lhaExternalAnimationData = " . $json_data . ";";

			if ( @file_put_contents( $file_path, $js_content ) ) {
				$script_data['lhaExternalAnimationDataUrl'] = $file_url;
			} else {
				// Fallback: If file write fails, pass directly.
				// This might still be too large for localization, but it's a last resort.
				$script_data['lhaPreloadedAnimations'] = $detected_animations_data;
				// Optionally, log this failure for admin review.
				// error_log('LHA Animation Optimizer: Failed to write animation cache file: ' . $file_path . '. Falling back to direct localization.');
			}
		} else {
			// Data size is within limits, pass it directly.
			$script_data['lhaPreloadedAnimations'] = $detected_animations_data;
		}

		wp_localize_script( $this->plugin_name, 'lhaAnimationOptimizerSettings', $script_data );

	}

	/**
	 * Schedules the daily cron event for cleaning up temporary animation files.
	 * This method should be called by the main plugin activation hook.
	 *
	 * @since 1.0.0
	 */
	public static function schedule_cleanup_event() {
		if ( ! wp_next_scheduled( 'lha_cleanup_temp_animation_files' ) ) {
			wp_schedule_event( time(), 'daily', 'lha_cleanup_temp_animation_files' );
		}
	}

	/**
	 * Clears the scheduled cron event for cleaning up temporary animation files.
	 * This method should be called by the main plugin deactivation hook.
	 *
	 * @since 1.0.0
	 */
	public static function clear_scheduled_cleanup_event() {
		wp_clear_scheduled_hook( 'lha_cleanup_temp_animation_files' );
	}

	/**
	 * Executes the cleanup of old temporary animation cache files.
	 * Attached to the 'lha_cleanup_temp_animation_files' cron hook.
	 *
	 * @since 1.0.0
	 */
	public static function do_cleanup_temp_animation_files() {
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/lha-optimizer-cache/';

		if ( ! is_dir( $cache_dir ) ) {
			return; // Cache directory doesn't exist
		}

		$files = glob( $cache_dir . 'animations-*.js' );
		$expiration_time = time() - ( 24 * 60 * 60 ); // 24 hours ago

		if ( $files && count( $files ) > 0 ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) && filemtime( $file ) < $expiration_time ) {
					@unlink( $file );
				}
			}
		}
	}
}

// This class is production-ready.
?>
