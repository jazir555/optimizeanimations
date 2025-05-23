<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
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

class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// This is the place for deactivation tasks like:
		// - Clearing any scheduled cron jobs specific to this plugin.
		// - Removing temporary transients that are safe to remove on deactivation.
		// - Other cleanup tasks that should happen when the plugin is disabled,
		//   but not necessarily when it's uninstalled (data is usually kept on deactivation).

		// For LHA Animation Optimizer v1.0.0, there are no specific deactivation
		// tasks required beyond what WordPress handles by default (e.g., not running plugin code).
		// Plugin options are intentionally not deleted here; that is handled by uninstall.php.

		// This class is production-ready.
	}

}
?>
