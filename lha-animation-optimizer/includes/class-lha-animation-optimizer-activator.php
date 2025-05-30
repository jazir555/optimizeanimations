<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/includes/core
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Set default options.
		$default_options = [
			'lazy_load_animations'            => 1,
			'intersection_observer_threshold' => 0.1,
		];

		add_option( 'lha-animation-optimizer_options', $default_options, '', 'yes' );

		// Example: flush rewrite rules if CPTs/taxonomies are registered.
		// flush_rewrite_rules();

		// This class is production-ready for its current scope.
		// Actual activation logic needs to be implemented based on plugin features if they expand.
	}

}
?>
