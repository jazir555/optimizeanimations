<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/admin/partials
 * @author     LHA Plugin Author <author@example.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		<?php
		// This function outputs the required hidden fields, including the nonce.
		settings_fields( 'lha_animation_optimizer_settings_group' ); // Must match the group name in register_setting()

		// This function prints out all settings sections added to a particular settings page.
		// The $page parameter should match the page slug used in add_settings_section() and add_settings_field().
		do_settings_sections( 'lha-animation-optimizer' ); // This is the $menu_slug for the page.

		// Output save settings button
		submit_button( __( 'Save Settings', 'lha-animation-optimizer' ) );
		?>
	</form>
</div>

<?php // This file is production-ready. ?>
