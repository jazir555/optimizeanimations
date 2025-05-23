<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://example.com/lha-animation-optimizer-uri
 * @since             1.0.0
 * @package           LHA_Animation_Optimizer
 *
 * @wordpress-plugin
 * Plugin Name:       LHA Animation Optimizer
 * Plugin URI:        https://example.com/lha-animation-optimizer-plugin-uri
 * Description:       Optimizes animations for speed and performance.
 * Version:           1.0.0
 * Author:            LHA Plugin Author
 * Author URI:        https://example.com/lha-author-uri
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lha-animation-optimizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lha-animation-optimizer-main.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function lha_animation_optimizer_run() {

	$plugin = new \LHA\Animation_Optimizer\Main();
	$plugin->run();

}
lha_animation_optimizer_run();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lha-animation-optimizer-activator.php
 */
function lha_animation_optimizer_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-lha-animation-optimizer-activator.php';
	\LHA\Animation_Optimizer\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lha-animation-optimizer-deactivator.php
 */
function lha_animation_optimizer_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-lha-animation-optimizer-deactivator.php';
	\LHA\Animation_Optimizer\Core\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'lha_animation_optimizer_activate' );
register_deactivation_hook( __FILE__, 'lha_animation_optimizer_deactivate' );
