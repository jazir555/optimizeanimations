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

		// Data to pass to the public script
		$script_data = array(
			'ajax_url'                      => admin_url( 'admin-ajax.php' ), // Retained for potential future use
			'lazyLoadAnimations'            => $lazy_load_animations,
			'intersectionObserverThreshold' => $intersection_observer_threshold,
			// Add other settings here as needed in the future
		);

		wp_localize_script( $this->plugin_name, 'lhaAnimationOptimizerSettings', $script_data );

	}

}

// This class is production-ready.
?>
