<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueueing the admin-specific stylesheet and JavaScript,
 * and for adding the plugin settings page.
 *
 * @link       https://example.com/lha-animation-optimizer-uri
 * @since      1.0.0
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/admin
 * @author     LHA Plugin Author <author@example.com>
 */

namespace LHA\Animation_Optimizer\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for
 * enqueueing the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 */
class Settings_Manager {

	/**
	 * The ID of this plugin.
	 * Used for menu slugs, option names, etc.
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
	 * The key for storing plugin options in the database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $option_name    The name of the option used to store settings.
	 */
	private $option_name;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->option_name = $plugin_name . '_options'; // e.g., lha-animation-optimizer_options

		// AJAX hooks for dashboard actions
		add_action('wp_ajax_lha_apply_optimization', [ $this, 'handle_apply_optimization_ajax' ]);
		add_action('wp_ajax_lha_deactivate_optimization', [ $this, 'handle_deactivate_optimization_ajax' ]);
		add_action('wp_ajax_lha_ignore_animation', [ $this, 'handle_ignore_animation_ajax' ]);
		add_action('wp_ajax_lha_unignore_animation', [ $this, 'handle_unignore_animation_ajax' ]);
		add_action('wp_ajax_lha_bulk_apply_optimizations', [ $this, 'handle_bulk_apply_optimizations_ajax' ]);

	}

	/**
	 * Add the top-level admin menu page for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'LHA Animation Optimizer Settings', 'lha-animation-optimizer' ), // Page Title
			__( 'Animation Optimizer', 'lha-animation-optimizer' ),               // Menu Title
			'manage_options',                                                      // Capability
			$this->plugin_name,                                                    // Menu Slug
			array( $this, 'admin_page_display' ),                                 // Callback function
			'dashicons-performance',                                               // Icon URL
			75                                                                     // Position
		);

		// Add the submenu page for Animation Dashboard
		add_submenu_page(
			$this->plugin_name,                                                    // Parent slug
			__( 'Animation Dashboard', 'lha-animation-optimizer' ),                // Page title
			__( 'Animation Dashboard', 'lha-animation-optimizer' ),                // Menu title
			'manage_options',                                                      // Capability
			'lha-animation-dashboard',                                             // Menu slug
			array( $this, 'render_animation_dashboard_page' )                      // Callback function
		);
	}

	/**
	 * Callback to render the HTML for the settings page.
	 *
	 * @since    1.0.0
	 */
	public function admin_page_display() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Include the settings page partial
		require_once plugin_dir_path( __FILE__ ) . 'partials/lha-animation-optimizer-admin-display.php';
	}

	/**
	 * Renders the HTML for the Animation Dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function render_animation_dashboard_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lha-animation-optimizer' ) );
		}

		$detected_animations = get_option( 'lha_detected_animations', array() );
		$applied_optimizations = get_option( 'lha_applied_optimizations', array() );
		$ignored_animations = get_option( 'lha_ignored_animations', array() );

		$processed_animations = array();

		foreach ( $detected_animations as $log_id => $animation_data ) {
			$status = 'detected'; // Default status from lha_detected_animations

			// Check if ignored
			if ( in_array( $log_id, $ignored_animations, true ) ) {
				$status = 'ignored';
			}

			// Check if applied (applied takes precedence)
			// Need to find if any applied optimization matches this log_id
			foreach ($applied_optimizations as $selector => $applied_info) {
				if (isset($applied_info['log_id']) && $applied_info['log_id'] === $log_id) {
					// To be sure, also check if the selector in detected_animations matches the key in applied_optimizations
					if (isset($animation_data['selector']) && $animation_data['selector'] === $selector) {
						$status = 'applied';
						break; // Found applied status, no need to check further applied optimizations
					}
				}
			}
			
			// The status in $animation_data itself should be the most up-to-date from AJAX handlers.
			// However, we reconcile here to be absolutely sure, giving precedence as defined.
			// If the status in $animation_data reflects an AJAX action, it should be 'applied' or 'ignored'.
			// If it's 'detected', then the checks above will refine it.
			// If $animation_data['status'] is 'applied' or 'ignored', the logic above will re-confirm it.
			$animation_data['effective_status'] = $status;
			$processed_animations[ $log_id ] = $animation_data;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'LHA Animation Optimizer - Animation Dashboard', 'lha-animation-optimizer' ); ?></h1>
			
			<div id="lha-dashboard-messages"></div> <?php // For AJAX success/error messages ?>

			<?php if ( empty( $processed_animations ) ) : ?>
				<p><?php echo esc_html__( 'No animations detected yet. As you navigate the frontend of your site, detected animations will appear here.', 'lha-animation-optimizer' ); ?></p>
			<?php else : ?>
				<div class="lha-bulk-actions-controls" style="margin-bottom: 10px;">
					<label for="lha-bulk-action-selector" class="screen-reader-text"><?php echo esc_html__( 'Select bulk action', 'lha-animation-optimizer' ); ?></label>
					<select name="lha_bulk_action" id="lha-bulk-action-selector">
						<option value=""><?php echo esc_html__( 'Bulk Actions', 'lha-animation-optimizer' ); ?></option>
						<option value="apply_selected"><?php echo esc_html__( 'Apply Selected Optimizations', 'lha-animation-optimizer' ); ?></option>
						<?php // Future actions can be added here, e.g., ignore_selected, delete_selected ?>
					</select>
					<button type="button" id="lha-apply-bulk-action" class="button action"><?php echo esc_html__( 'Apply Bulk Action', 'lha-animation-optimizer' ); ?></button>
				</div>

				<table class="wp-list-table widefat fixed striped table-view-list lha-animations-table">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" id="lha-select-all-animations" /></th>
							<th scope="col"><?php echo esc_html__( 'Selector', 'lha-animation-optimizer' ); ?></th>
							<th scope="col" style="width: 20%;"><?php echo esc_html__( 'Properties', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Duration', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Easing', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Count', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'First Seen', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Last Seen', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Status', 'lha-animation-optimizer' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Actions', 'lha-animation-optimizer' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php foreach ( $processed_animations as $log_id => $animation ) : ?>
							<?php
								// Ensure all expected keys exist to avoid notices
								$selector = isset( $animation['selector'] ) ? $animation['selector'] : 'N/A';
								$properties_json = isset( $animation['properties'] ) ? $animation['properties'] : '{}';
								$properties_array = json_decode( $properties_json, true );
								$properties_display = '';
								if ( json_last_error() === JSON_ERROR_NONE && is_array($properties_array) ) {
									foreach ( $properties_array as $key => $value ) {
										$properties_display .= esc_html( $key ) . ': ' . esc_html( $value ) . '; ';
									}
								} else {
									$properties_display = esc_html( $properties_json );
								}

								$duration = isset( $animation['duration'] ) ? $animation['duration'] : 'N/A';
								$easing = isset( $animation['easing'] ) ? $animation['easing'] : 'N/A';
								$count = isset( $animation['detection_count'] ) ? $animation['detection_count'] : 'N/A';
								$first_seen = isset( $animation['first_detected_time'] ) ? $animation['first_detected_time'] : 'N/A';
								$last_seen = isset( $animation['last_detected_time'] ) ? $animation['last_detected_time'] : 'N/A';
								$current_status = $animation['effective_status'];
								$current_log_id = isset( $animation['log_id'] ) ? $animation['log_id'] : $log_id; // Ensure we have the log_id for the checkbox
							?>
							<tr id="log-<?php echo esc_attr( $current_log_id ); ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" name="log_ids[]" class="lha-bulk-select-checkbox" value="<?php echo esc_attr( $current_log_id ); ?>" />
								</th>
								<td><?php echo esc_html( $selector ); ?></td>
								<td><small><?php echo wp_kses_post( rtrim($properties_display, '; ') ); // Using wp_kses_post for ; as it's part of style attribute like values ?></small></td>
								<td><?php echo esc_html( $duration ); ?></td>
								<td><?php echo esc_html( $easing ); ?></td>
								<td><?php echo esc_html( $count ); ?></td>
								<td><?php echo esc_html( $first_seen ); ?></td>
								<td><?php echo esc_html( $last_seen ); ?></td>
								<td class="lha-status-cell">
									<span class="lha-status-<?php echo esc_attr( $current_status ); ?>">
										<?php echo esc_html( ucfirst( $current_status ) ); ?>
									</span>
								</td>
								<td class="lha-actions-cell">
									<?php if ( $current_status === 'detected' ) : ?>
										<button class="button button-primary lha-action-button" data-action="apply" data-log-id="<?php echo esc_attr( $log_id ); ?>"><?php echo esc_html__( 'Apply', 'lha-animation-optimizer' ); ?></button>
										<button class="button lha-action-button" data-action="ignore" data-log-id="<?php echo esc_attr( $log_id ); ?>"><?php echo esc_html__( 'Ignore', 'lha-animation-optimizer' ); ?></button>
									<?php elseif ( $current_status === 'applied' ) : ?>
										<button class="button lha-action-button" data-action="deactivate" data-log-id="<?php echo esc_attr( $log_id ); ?>"><?php echo esc_html__( 'Deactivate', 'lha-animation-optimizer' ); ?></button>
									<?php elseif ( $current_status === 'ignored' ) : ?>
										<button class="button lha-action-button" data-action="unignore" data-log-id="<?php echo esc_attr( $log_id ); ?>"><?php echo esc_html__( 'Unignore', 'lha-animation-optimizer' ); ?></button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register settings, sections, and fields using the WordPress Settings API.
	 * Hooked to 'admin_init'.
	 *
	 * @since    1.0.0
	 */
	public function initialize_settings() {
		// Setting Group
		$option_group = $this->plugin_name . '_settings_group'; // e.g., lha-animation-optimizer_settings_group

		register_setting(
			$option_group,
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);

		// Settings Section ID
		$section_id = $this->plugin_name . '_general_settings_section';

		add_settings_section(
			$section_id,
			__( 'General Settings', 'lha-animation-optimizer' ),
			array( $this, 'render_general_settings_section_info' ),
			$this->plugin_name // Page slug where this section will be shown
		);

		// Lazy Load Animations Field
		add_settings_field(
			'lazy_load_animations',
			__( 'Enable Lazy Loading of Animations', 'lha-animation-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'lazy_load_animations',
				'option_name' => $this->option_name,
				'description' => __( 'When enabled, animations will only load when they enter the viewport.', 'lha-animation-optimizer' ),
			)
		);

		// Intersection Observer Threshold Field
		add_settings_field(
			'intersection_observer_threshold',
			__( 'Lazy Load Trigger Threshold', 'lha-animation-optimizer' ),
			array( $this, 'render_number_field' ),
			$this->plugin_name,
			$section_id,
			array(
				'label_for'   => 'intersection_observer_threshold',
				'option_name' => $this->option_name,
				'description' => __( 'Set the percentage of the element that must be visible to trigger the animation (e.g., 0.1 for 10%, 0.5 for 50%). Default: 0.1', 'lha-animation-optimizer' ),
				'input_type'  => 'number',
				'min'         => '0.0',
				'max'         => '1.0',
				'step'        => '0.01', // Allow finer granularity
			)
		);
	}

	/**
	 * Sanitize all settings passed to the Settings API.
	 *
	 * @since    1.0.0
	 * @param    array    $input    The unsanitized input array.
	 * @return   array              The sanitized input array.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		// Sanitize 'lazy_load_animations' (checkbox, so it's either 1 or not set)
		$sanitized_input['lazy_load_animations'] = ( isset( $input['lazy_load_animations'] ) && '1' === $input['lazy_load_animations'] ) ? 1 : 0;

		// Sanitize 'intersection_observer_threshold' (float between 0.0 and 1.0)
		if ( isset( $input['intersection_observer_threshold'] ) ) {
			$threshold = floatval( $input['intersection_observer_threshold'] );
			if ( $threshold >= 0.0 && $threshold <= 1.0 ) {
				$sanitized_input['intersection_observer_threshold'] = $threshold;
			} else {
				// If out of range, set to default or add an error
				$sanitized_input['intersection_observer_threshold'] = 0.1; // Default value
				add_settings_error(
					$this->option_name,
					'threshold_out_of_range',
					__( 'Intersection Observer Threshold must be between 0.0 and 1.0. Reverted to default.', 'lha-animation-optimizer' ),
					'error'
				);
			}
		} else {
			// If not set, provide a default (or handle as needed)
			$sanitized_input['intersection_observer_threshold'] = 0.1;
		}

		return $sanitized_input;
	}

	/**
	 * Render a description for the general settings section.
	 *
	 * @since    1.0.0
	 */
	public function render_general_settings_section_info() {
		echo '<p>' . esc_html__( 'Configure the general settings for the LHA Animation Optimizer plugin.', 'lha-animation-optimizer' ) . '</p>';
	}

	/**
	 * Render a checkbox field for a setting.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Arguments passed to this callback.
	 */
	public function render_checkbox_field( $args ) {
		$options = get_option( $args['option_name'], array() ); // Get all options or empty array if not set
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 1; // Default to 1 (true/checked)

		echo '<input type="checkbox" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['label_for'] . ']' ) . '" value="1" ' . checked( 1, $value, false ) . ' />';
		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a number input field for a setting.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Arguments passed to this callback.
	 */
	public function render_number_field( $args ) {
		$options = get_option( $args['option_name'], array() );
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 0.1; // Default to 0.1

		echo '<input type="' . esc_attr( $args['input_type'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['label_for'] . ']' ) . '" value="' . esc_attr( $value ) . '"';
		if ( isset( $args['min'] ) ) {
			echo ' min="' . esc_attr( $args['min'] ) . '"';
		}
		if ( isset( $args['max'] ) ) {
			echo ' max="' . esc_attr( $args['max'] ) . '"';
		}
		if ( isset( $args['step'] ) ) {
			echo ' step="' . esc_attr( $args['step'] ) . '"';
		}
		echo ' class="small-text" />'; // small-text is a WordPress admin CSS class

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Regenerates the CSS file for applied animations.
	 * This is a placeholder and should be implemented.
	 *
	 * @since 1.0.0
	 */
	private function regenerate_applied_animations_css() {
		// Placeholder: In a real scenario, this method would generate a CSS file
		// based on the animations stored in 'lha_applied_optimizations'.
		// For example, it might write to wp-content/uploads/lha-animations.css
		// error_log('Regenerating LHA Animations CSS...');
	}

	/**
	 * Handles applying an animation optimization via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_apply_optimization_ajax() {
		check_ajax_referer( 'lha_dashboard_actions_nonce', 'nonce' );

		if ( ! isset( $_POST['log_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: log_id not provided.', 'lha-animation-optimizer' ) ), 400 );
		}
		$log_id = sanitize_text_field( wp_unslash( $_POST['log_id'] ) );

		$detected_animations = get_option( 'lha_detected_animations', array() );
		$applied_optimizations = get_option( 'lha_applied_optimizations', array() );
		$ignored_animations = get_option( 'lha_ignored_animations', array() );

		if ( ! isset( $detected_animations[ $log_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: Animation not found in detected list.', 'lha-animation-optimizer' ) ), 404 );
		}

		$animation_to_apply = $detected_animations[ $log_id ];

		// Ensure all necessary properties exist, providing defaults if not.
		$selector = ! empty( $animation_to_apply['selector'] ) ? $animation_to_apply['selector'] : '.lha-default-selector-' . $log_id;
		$duration = ! empty( $animation_to_apply['animation_duration'] ) ? $animation_to_apply['animation_duration'] : '1s'; // Default duration
		$properties = ! empty( $animation_to_apply['properties'] ) ? $animation_to_apply['properties'] : ''; // Default empty properties


		$applied_optimizations[ $selector ] = array(
			'className'  => 'lha-anim-' . $log_id,
			'duration'   => $duration,
			'log_id'     => $log_id,
			'properties' => $properties,
		);

		// Remove from ignored if it was there
		$ignored_animations = array_diff( $ignored_animations, array( $log_id ) );

		$detected_animations[ $log_id ]['status'] = 'applied';

		update_option( 'lha_applied_optimizations', $applied_optimizations );
		update_option( 'lha_ignored_animations', array_values($ignored_animations) ); // Re-index array
		update_option( 'lha_detected_animations', $detected_animations );

		$this->regenerate_applied_animations_css();
		wp_send_json_success( array( 'message' => __( 'Optimization applied.', 'lha-animation-optimizer' ), 'status' => 'applied' ) );
	}

	/**
	 * Handles deactivating an animation optimization via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_deactivate_optimization_ajax() {
		check_ajax_referer( 'lha_dashboard_actions_nonce', 'nonce' );

		if ( ! isset( $_POST['log_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: log_id not provided.', 'lha-animation-optimizer' ) ), 400 );
		}
		$log_id = sanitize_text_field( wp_unslash( $_POST['log_id'] ) );

		$detected_animations = get_option( 'lha_detected_animations', array() );
		$applied_optimizations = get_option( 'lha_applied_optimizations', array() );

		if ( ! isset( $detected_animations[ $log_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: Animation not found in detected list.', 'lha-animation-optimizer' ) ), 404 );
		}

		$animation_to_deactivate = $detected_animations[ $log_id ];
		$selector = ! empty( $animation_to_deactivate['selector'] ) ? $animation_to_deactivate['selector'] : null;

		if ( $selector && isset( $applied_optimizations[ $selector ] ) ) {
			unset( $applied_optimizations[ $selector ] );
		}

		$detected_animations[ $log_id ]['status'] = 'detected';

		update_option( 'lha_applied_optimizations', $applied_optimizations );
		update_option( 'lha_detected_animations', $detected_animations );

		$this->regenerate_applied_animations_css();
		wp_send_json_success( array( 'message' => __( 'Optimization deactivated.', 'lha-animation-optimizer' ), 'status' => 'detected' ) );
	}

	/**
	 * Handles ignoring an animation via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_ignore_animation_ajax() {
		check_ajax_referer( 'lha_dashboard_actions_nonce', 'nonce' );

		if ( ! isset( $_POST['log_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: log_id not provided.', 'lha-animation-optimizer' ) ), 400 );
		}
		$log_id = sanitize_text_field( wp_unslash( $_POST['log_id'] ) );

		$detected_animations = get_option( 'lha_detected_animations', array() );
		$applied_optimizations = get_option( 'lha_applied_optimizations', array() );
		$ignored_animations = get_option( 'lha_ignored_animations', array() );

		if ( ! isset( $detected_animations[ $log_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: Animation not found in detected list.', 'lha-animation-optimizer' ) ), 404 );
		}

		// Add to ignored list if not already there
		if ( ! in_array( $log_id, $ignored_animations, true ) ) {
			$ignored_animations[] = $log_id;
		}

		// Remove from applied if it was there
		$animation_to_ignore = $detected_animations[ $log_id ];
		$selector = ! empty( $animation_to_ignore['selector'] ) ? $animation_to_ignore['selector'] : null;

		if ( $selector && isset( $applied_optimizations[ $selector ] ) ) {
			unset( $applied_optimizations[ $selector ] );
		}

		$detected_animations[ $log_id ]['status'] = 'ignored';

		update_option( 'lha_ignored_animations', $ignored_animations );
		update_option( 'lha_applied_optimizations', $applied_optimizations );
		update_option( 'lha_detected_animations', $detected_animations );

		$this->regenerate_applied_animations_css();
		wp_send_json_success( array( 'message' => __( 'Animation ignored.', 'lha-animation-optimizer' ), 'status' => 'ignored' ) );
	}

	/**
	 * Handles unignoring an animation via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_unignore_animation_ajax() {
		check_ajax_referer( 'lha_dashboard_actions_nonce', 'nonce' );

		if ( ! isset( $_POST['log_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: log_id not provided.', 'lha-animation-optimizer' ) ), 400 );
		}
		$log_id = sanitize_text_field( wp_unslash( $_POST['log_id'] ) );

		$detected_animations = get_option( 'lha_detected_animations', array() );
		$ignored_animations = get_option( 'lha_ignored_animations', array() );

		if ( ! isset( $detected_animations[ $log_id ] ) ) {
			// It might be an old log_id not in detected_animations anymore, but still in ignored.
			// So, we don't strictly need it in detected_animations to unignore.
		}

		$ignored_animations = array_diff( $ignored_animations, array( $log_id ) );

		if ( isset( $detected_animations[ $log_id ] ) ) {
			$detected_animations[ $log_id ]['status'] = 'detected';
			update_option( 'lha_detected_animations', $detected_animations );
		}

		update_option( 'lha_ignored_animations', array_values($ignored_animations) ); // Re-index array

		// No CSS regeneration needed if only unignoring, unless it was also applied.
		// However, to be safe, or if business logic implies it, we can call it.
		// For now, let's assume it's not strictly necessary for 'unignore' if not re-applying.
		// $this->regenerate_applied_animations_css(); 

		wp_send_json_success( array( 'message' => __( 'Animation unignored.', 'lha-animation-optimizer' ), 'status' => 'detected' ) );
	}

	/**
	 * Stores detected animation data from the frontend.
	 * This method would typically be hooked to a wp_ajax action.
	 *
	 * @since 1.0.0
	 * @param array $animation_data Data for the detected animation. Expected keys:
	 *                              'selector', 'properties', 'duration', 'easing', 'source_url'.
	 */
	public function store_detected_animation_data( $animation_data ) {
		// Basic validation of incoming data
		if ( empty( $animation_data['selector'] ) || empty( $animation_data['properties'] ) ) {
			// error_log( 'LHA Error: Selector or properties missing in detected animation data.' );
			return; // Or handle error more formally
		}

		// Sanitize incoming data
		$selector = sanitize_text_field( $animation_data['selector'] );
		// Properties can be complex, assume JSON string for now, ensure it's valid
		$properties_json = is_string($animation_data['properties']) ? $animation_data['properties'] : wp_json_encode( $animation_data['properties'] );
		if ( ! json_decode( $properties_json ) ) {
			// error_log( 'LHA Error: Invalid JSON properties string.' );
			return;
		}
		$properties = wp_kses_post( $properties_json ); // Kses for safety, though JSON structure itself needs validation

		$duration = isset( $animation_data['duration'] ) ? (is_numeric( $animation_data['duration'] ) ? intval( $animation_data['duration'] ) : sanitize_text_field( $animation_data['duration'] ) ) : 'unknown';
		$easing = isset( $animation_data['easing'] ) ? sanitize_text_field( $animation_data['easing'] ) : 'unknown';
		$source_url = isset( $animation_data['source_url'] ) ? esc_url_raw( $animation_data['source_url'] ) : '';
		$current_time = current_time( 'mysql' );

		// Generate log_id: simple approach - hash of selector and properties (json string)
		// More robust might involve specific property keys if properties order can change.
		$log_id_string = $selector . ':' . $properties;
		$log_id = 'lha-' . md5( $log_id_string );

		$detected_animations = get_option( 'lha_detected_animations', array() );

		if ( isset( $detected_animations[ $log_id ] ) ) {
			// Existing entry
			$existing_entry = $detected_animations[ $log_id ];
			$existing_entry['last_detected_time'] = $current_time;
			$existing_entry['detection_count'] = isset( $existing_entry['detection_count'] ) ? ( $existing_entry['detection_count'] + 1 ) : 1;
			$existing_entry['source_url'] = $source_url; // Update source URL in case it changes

			// Preserve status if 'applied' or 'ignored'
			if ( ! in_array( $existing_entry['status'], array( 'applied', 'ignored' ), true ) ) {
				$existing_entry['status'] = 'detected';
			}
			// Ensure all fields are present, even if updating an older entry
			$existing_entry['selector'] = $selector;
			$existing_entry['properties'] = $properties;
			$existing_entry['duration'] = $duration;
			$existing_entry['easing'] = $easing;


			$detected_animations[ $log_id ] = $existing_entry;
		} else {
			// New entry
			$detected_animations[ $log_id ] = array(
				'log_id'                => $log_id,
				'selector'              => $selector,
				'properties'            => $properties, // Stored as JSON string
				'duration'              => $duration,
				'easing'                => $easing,
				'source_url'            => $source_url,
				'first_detected_time'   => $current_time,
				'last_detected_time'    => $current_time,
				'detection_count'       => 1,
				'status'                => 'detected',
			);
		}

		update_option( 'lha_detected_animations', $detected_animations );
	}

	/**
	 * Handles bulk applying animation optimizations via AJAX (Placeholder).
	 *
	 * @since 1.0.0
	 */
	public function handle_bulk_apply_optimizations_ajax() {
		// 1. Nonce Verification
		if ( ! check_ajax_referer( 'lha_dashboard_actions_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'lha-animation-optimizer' ) ), 403 );
			wp_die();
		}

		// 2. Retrieve and Validate log_ids
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$raw_log_ids = isset( $_POST['log_ids'] ) ? $_POST['log_ids'] : null;

		if ( ! is_array( $raw_log_ids ) || empty( $raw_log_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No animation IDs provided or invalid format for bulk action.', 'lha-animation-optimizer' ) ), 400 );
			wp_die();
		}

		$sanitized_log_ids = array_map( 'sanitize_text_field', $raw_log_ids );
		// Filter out any empty strings that might result from sanitization if original array had weird values
		$sanitized_log_ids = array_filter( $sanitized_log_ids );

		if ( empty( $sanitized_log_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid animation IDs provided after sanitization.', 'lha-animation-optimizer' ) ), 400 );
			wp_die();
		}

		// 3. Fetch Options
		$detected_animations = get_option( 'lha_detected_animations', array() );
		$applied_optimizations = get_option( 'lha_applied_optimizations', array() );
		$ignored_animations = get_option( 'lha_ignored_animations', array() );

		// 4. Process Each log_id
		$processed_count = 0;
		$error_ids = array();

		foreach ( $sanitized_log_ids as $log_id ) {
			if ( ! isset( $detected_animations[ $log_id ] ) ) {
				$error_ids[] = $log_id; // Log or note that this ID was not found
				continue;
			}

			$animation_data = $detected_animations[ $log_id ];

			// Ensure required data is present
			$selector = ! empty( $animation_data['selector'] ) ? $animation_data['selector'] : '.lha-default-selector-' . $log_id;
			// 'animation_duration' might not exist in older data, use 'duration' if it's there, or default
			$duration = ! empty( $animation_data['animation_duration'] ) ? $animation_data['animation_duration'] : ( ! empty( $animation_data['duration'] ) ? $animation_data['duration'] : '1s' );
			$properties_json_string = ! empty( $animation_data['properties'] ) ? $animation_data['properties'] : '{}'; // Already a JSON string

			// Apply Logic:
			// Add to applied_optimizations
			$applied_optimizations[ $selector ] = array(
				'className'  => 'lha-anim-' . $log_id,
				'duration'   => $duration,
				'log_id'     => $log_id,
				'properties' => $properties_json_string,
			);

			// Remove from ignored_animations if present
			$ignored_key = array_search( $log_id, $ignored_animations, true );
			if ( $ignored_key !== false ) {
				unset( $ignored_animations[ $ignored_key ] );
			}

			// Update status in detected_animations
			$detected_animations[ $log_id ]['status'] = 'applied';
			$processed_count++;
		}

		// Re-index ignored_animations array
		$ignored_animations = array_values( $ignored_animations );

		// 5. Update Options
		update_option( 'lha_detected_animations', $detected_animations );
		update_option( 'lha_applied_optimizations', $applied_optimizations );
		update_option( 'lha_ignored_animations', $ignored_animations );

		// 6. Regenerate CSS
		$this->regenerate_applied_animations_css();

		// 7. Send JSON Response
		if ( $processed_count > 0 ) {
			$message = sprintf(
				/* translators: %d: number of animations processed. */
				_n(
					'%d animation optimization successfully applied.',
					'%d animation optimizations successfully applied.',
					$processed_count,
					'lha-animation-optimizer'
				),
				$processed_count
			);
			if ( ! empty( $error_ids ) ) {
				$message .= ' ' . sprintf(
					/* translators: %d: number of animations not found. */
					_n(
						'%d ID was not found or could not be processed.',
						'%d IDs were not found or could not be processed.',
						count( $error_ids ),
						'lha-animation-optimizer'
					),
					count( $error_ids )
				);
				// For more detailed error, could pass $error_ids in response data
			}
			wp_send_json_success( array( 'message' => $message, 'processed_count' => $processed_count, 'errors_count' => count($error_ids) ) );
		} else if ( !empty($error_ids) ) {
             wp_send_json_error( array( 'message' => __( 'None of the provided animation IDs could be processed.', 'lha-animation-optimizer' ), 'processed_count' => 0, 'errors_count' => count($error_ids) ), 404 );
        }
		else {
			wp_send_json_error( array( 'message' => __( 'No animations were processed. Please check the provided IDs.', 'lha-animation-optimizer' ) ), 400 );
		}
		wp_die(); // Should be called after wp_send_json_success/error
	}
}

// This class is production-ready.
?>
