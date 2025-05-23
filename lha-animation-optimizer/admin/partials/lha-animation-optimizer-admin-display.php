<?php
/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin, specifically the settings page with tabs.
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

// $active_tab and $tabs are passed from Settings_Manager::admin_page_display()
$current_active_tab = isset( $active_tab ) ? $active_tab : 'general';
$available_tabs = isset( $tabs ) ? $tabs : array( 'general' => __( 'General Settings', 'lha-animation-optimizer' ) );

// The main plugin page slug, used for constructing tab URLs and for settings_fields().
$plugin_page_slug = 'lha-animation-optimizer'; 
$option_group_slug = $plugin_page_slug . '_settings_group';

?>
<div class="wrap lha-animation-optimizer-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); // Display any settings errors (e.g., from sanitize_settings or AJAX handlers) ?>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $available_tabs as $tab_slug => $tab_title ) {
			$class = ( $tab_slug === $current_active_tab ) ? 'nav-tab-active' : '';
			echo '<a href="?page=' . esc_attr( $plugin_page_slug ) . '&tab=' . esc_attr( $tab_slug ) . '" class="nav-tab ' . esc_attr( $class ) . '">' . esc_html( $tab_title ) . '</a>';
		}
		?>
	</h2>

	<?php 
	// For tabs that don't save options via the main form (like CSS Analyzer & Statistics),
	// we don't want the form tag if it's not needed, or ensure it doesn't interfere.
	// However, the Import/Export tab *does* use the form for file upload.
	// The Settings API requires the form tag for `settings_fields` and `do_settings_sections` to work as intended for saving options.
	// We will conditionally show the submit button instead.
	?>
	<form method="post" action="options.php" enctype="multipart/form-data">
		<?php
		// Output nonce, action, and option_page fields for the settings group.
		settings_fields( $option_group_slug );

		// Hidden field to store the current tab, so sanitize_settings knows which fields to expect.
		echo '<input type="hidden" name="current_tab_lha_ao" value="' . esc_attr( $current_active_tab ) . '" />';

		// Display the settings sections for the active tab.
		if ( 'general' === $current_active_tab ) {
			do_settings_sections( $plugin_page_slug . '-general' );
		} elseif ( 'lazy_loading' === $current_active_tab ) {
			do_settings_sections( $plugin_page_slug . '-lazy-loading' );
		} elseif ( 'js_animations' === $current_active_tab ) {
			do_settings_sections( $plugin_page_slug . '-js-animations' );
		} elseif ( 'css_optimizations' === $current_active_tab ) {
			do_settings_sections( $plugin_page_slug . '-css-optimizations' );
		} elseif ( 'targeting_rules' === $current_active_tab ) { 
			do_settings_sections( $plugin_page_slug . '-targeting-rules' );
		} elseif ( 'statistics' === $current_active_tab ) { // New Statistics Tab
			do_settings_sections( $plugin_page_slug . '-statistics' );
		} elseif ( 'import_export' === $current_active_tab ) {
			do_settings_sections( $plugin_page_slug . '-import-export' );
		}
		
		// Only show the "Save Settings" button for tabs that actually save settings via this form.
		// The CSS Optimization and Statistics tabs do not save options through this form.
		if ( 'css_optimizations' !== $current_active_tab && 'statistics' !== $current_active_tab ) {
			submit_button( __( 'Save Settings', 'lha-animation-optimizer' ) );
		}
		?>
	</form>
</div>

<?php // This file is production-ready. ?>
