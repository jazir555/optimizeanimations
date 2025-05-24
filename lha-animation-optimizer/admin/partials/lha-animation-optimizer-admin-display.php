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
	// For tabs that don't save options via the main form (like CSS Analyzer, Statistics, Logging, Presets),
	// we don't want the form tag if it's not needed, or ensure it doesn't interfere.
	// However, the Import/Export tab *does* use the form for file upload.
	// The Settings API requires the form tag for `settings_fields` and `do_settings_sections` to work as intended for saving options.
	// We will conditionally show the submit button instead.
	$is_settings_form_tab = !in_array($current_active_tab, array('css_optimizations', 'statistics', 'presets')); // Logging tab might have a save button for 'enable_detailed_logging'
	
	if ('presets' === $current_active_tab) {
		// Custom UI for Presets Tab (already implemented in a previous subtask)
		// This assumes $this->presets_option_name and $this->option_name are available if included from Settings_Manager method.
		// For direct require_once, these properties would need to be passed or accessed via a global/singleton.
		// For simplicity, we'll assume this partial is included in a context where $this refers to Settings_Manager.
		// If not, these would need to be passed as arguments or fetched directly.
		$presets = get_option( 'lha-animation-optimizer_presets', array() ); 
		$current_main_options = get_option( 'lha-animation-optimizer_options', array() );
		$active_preset_name = isset( $current_main_options['active_preset'] ) ? $current_main_options['active_preset'] : '';
	?>
		<div id="lha-presets-manager" class="lha-settings-tab-content">
			<?php settings_fields( $plugin_page_slug . '_presets_group_display_only' ); // Dummy group for section display ?>
			<?php do_settings_sections( $plugin_page_slug . '-presets' ); ?>
			
			<hr>
			<h3><?php esc_html_e( 'Create New Preset', 'lha-animation-optimizer' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lha_save_preset">
				<?php wp_nonce_field( 'lha_save_preset_action', 'lha_save_preset_nonce' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="lha_new_preset_name"><?php esc_html_e( 'New Preset Name', 'lha-animation-optimizer' ); ?></label>
						</th>
						<td>
							<input type="text" id="lha_new_preset_name" name="lha_new_preset_name" value="" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Enter a descriptive name for your new preset. This will save all current settings from other tabs (General, Lazy Loading, JavaScript Animations, Targeting Rules).', 'lha-animation-optimizer' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Current Settings as New Preset', 'lha-animation-optimizer' ) ); ?>
			</form>

			<hr>
			<h3><?php esc_html_e( 'Manage Existing Presets', 'lha-animation-optimizer' ); ?></h3>
			<?php if ( ! empty( $presets ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Preset Name', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'lha-animation-optimizer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $presets as $name => $settings_array ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $name ); ?></strong>
									<?php if ( $name === $active_preset_name ) : ?>
										<span class="lha-active-preset-indicator">(<?php esc_html_e( 'Currently Active', 'lha-animation-optimizer' ); ?>)</span>
									<?php endif; ?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 10px;">
										<input type="hidden" name="action" value="lha_apply_preset">
										<input type="hidden" name="lha_preset_name" value="<?php echo esc_attr( $name ); ?>">
										<?php wp_nonce_field( 'lha_apply_preset_action', 'lha_apply_preset_nonce' ); ?>
										<?php submit_button( __( 'Apply Preset', 'lha-animation-optimizer' ), 'primary', 'lha_apply_preset_submit', false ); ?>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;">
										<input type="hidden" name="action" value="lha_delete_preset">
										<input type="hidden" name="lha_preset_name" value="<?php echo esc_attr( $name ); ?>">
										<?php wp_nonce_field( 'lha_delete_preset_action', 'lha_delete_preset_nonce' ); ?>
										<?php submit_button( __( 'Delete Preset', 'lha-animation-optimizer' ), 'delete lha-delete-preset-button', 'lha_delete_preset_submit', false, array('onclick' => "return confirm('" . esc_js( sprintf( __( 'Are you sure you want to delete the preset "%s"? This action cannot be undone.', 'lha-animation-optimizer' ), $name ) ) . "');") ); ?>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No presets saved yet.', 'lha-animation-optimizer' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : // For all other tabs, use the standard settings form ?>
		<form method="post" action="options.php" enctype="multipart/form-data">
			<?php
			settings_fields( $option_group_slug );
			echo '<input type="hidden" name="current_tab_lha_ao" value="' . esc_attr( $current_active_tab ) . '" />';

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
			} elseif ( 'logging' === $current_active_tab ) { // New Logging Tab
				do_settings_sections( $plugin_page_slug . '-logging' );
			} elseif ( 'statistics' === $current_active_tab ) { 
				do_settings_sections( $plugin_page_slug . '-statistics' );
			} elseif ( 'import_export' === $current_active_tab ) {
				do_settings_sections( $plugin_page_slug . '-import-export' );
			}
			
			// Only show the "Save Settings" button for tabs that actually save settings via this form.
			// The CSS Optimization and Statistics tabs do not save options through this form.
			// The Logging tab DOES save the "Enable Detailed Logging" setting.
			if ( 'css_optimizations' !== $current_active_tab && 'statistics' !== $current_active_tab ) {
				submit_button( __( 'Save Settings', 'lha-animation-optimizer' ) );
			}
			?>
		</form>
	<?php endif; ?>
</div>

<?php // This file is production-ready. ?>
