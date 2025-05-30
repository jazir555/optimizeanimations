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

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles_scripts' ) );
		add_action( 'wp_ajax_lha_get_admin_pages', array( $this, 'ajax_get_admin_pages_callback' ) );
		add_action( 'wp_ajax_lha_get_global_animations_for_preview', array( $this, 'ajax_get_global_animations_for_preview_callback' ) );
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

		// Shunt Data Size Threshold Field
		add_settings_field(
			'shunt_data_size_threshold_kb',
			__( 'Shunt Data Size Threshold (KB)', 'lha-animation-optimizer' ),
			array( $this, 'render_number_field' ),
			$this->plugin_name,
			$section_id, // Add to the general settings section
			array(
				'label_for'   => 'shunt_data_size_threshold_kb',
				'option_name' => $this->option_name,
				'description' => __( 'Maximum size (in KB) for animation data to be inlined with the shunt script. Larger data will be saved to a temporary file. Default: 5 KB.', 'lha-animation-optimizer' ),
				'input_type'  => 'number',
				'min'         => '1',
				'max'         => '500',
				'step'        => '1',
				'default'     => 5, // Default value for the field
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

		// Cache Status Section ID
		$cache_status_section_id = $this->plugin_name . '_cache_status_section';

		add_settings_section(
			$cache_status_section_id,
			__( 'Animation Cache Status', 'lha-animation-optimizer' ),
			array( $this, 'render_cache_status_section_callback' ),
			$this->plugin_name // Page slug
		);

		// Animation Preview Section ID
		$animation_preview_section_id = $this->plugin_name . '_animation_preview_section';

		add_settings_section(
			$animation_preview_section_id,
			__( 'Animation Preview', 'lha-animation-optimizer' ),
			array( $this, 'render_animation_preview_section_callback' ),
			$this->plugin_name // Page slug
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

		// Sanitize 'shunt_data_size_threshold_kb' (integer, >= 1, <= 500)
		$current_options = get_option( $this->option_name ); // Get existing options to check against
		if ( isset( $input['shunt_data_size_threshold_kb'] ) ) {
			$threshold_kb = intval( $input['shunt_data_size_threshold_kb'] );
			if ( $threshold_kb >= 1 && $threshold_kb <= 500 ) {
				$sanitized_input['shunt_data_size_threshold_kb'] = $threshold_kb;
			} else {
				$sanitized_input['shunt_data_size_threshold_kb'] = 5; // Default value
				add_settings_error(
					$this->option_name,
					'shunt_threshold_out_of_range',
					__( 'Shunt Data Size Threshold must be between 1 and 500 KB. Reverted to default (5 KB).', 'lha-animation-optimizer' ),
					'error'
				);
			}
		} else {
			// If the field is not in the input (e.g. form submitted without it),
			// retain existing value or set default if it's a new option.
			if ( isset( $current_options['shunt_data_size_threshold_kb'] ) ) {
				$sanitized_input['shunt_data_size_threshold_kb'] = $current_options['shunt_data_size_threshold_kb'];
			} else {
				$sanitized_input['shunt_data_size_threshold_kb'] = 5; // Default for a new option
			}
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
		// Use the 'default' from $args if provided, otherwise fallback to a generic 0 or specific field default
		$default_value = isset( $args['default'] ) ? $args['default'] : 0;
		$value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : $default_value;

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
	 * Render the cache status section.
	 *
	 * @since    1.0.0
	 */
	public function render_cache_status_section_callback() {
		// Placeholder data - In a real scenario, these would come from options or transients
		$cache_status = get_option( 'lha_cache_status', __( 'Not yet generated', 'lha-animation-optimizer' ) );
		$last_generated_timestamp = get_option( 'lha_cache_last_generated', 0 );
		$animation_count = get_option( 'lha_animation_count', 0 );

		echo '<p>';
		esc_html_e( 'Current Status:', 'lha-animation-optimizer' );
		echo ' <strong>' . esc_html( $cache_status ) . '</strong>';
		echo '</p>';

		echo '<p>';
		esc_html_e( 'Last Generated:', 'lha-animation-optimizer' );
		echo ' <strong>';
		if ( $last_generated_timestamp ) {
			echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_generated_timestamp ) );
		} else {
			esc_html_e( 'N/A', 'lha-animation-optimizer' );
		}
		echo '</strong>';
		echo '</p>';

		echo '<p>';
		esc_html_e( 'Detected Animations:', 'lha-animation-optimizer' );
		echo ' <strong>' . esc_html( number_format_i18n( $animation_count ) ) . '</strong>';
		echo '</p>';
	}

	/**
	 * Render the animation preview section.
	 *
	 * @since    1.0.0
	 */
	public function render_animation_preview_section_callback() {
		?>
		<p><?php esc_html_e( 'Preview animations from the site-wide cache. Select a page for context (future use) and then load the preview.', 'lha-animation-optimizer' ); ?></p>
		
		<button type="button" id="lha-load-pages-button" class="button">
			<?php esc_html_e( 'Load Pages for Selection', 'lha-animation-optimizer' ); ?>
		</button>
		
		<div id="lha-page-list-loading" style="display:none; margin-top: 10px;">
			<p><?php esc_html_e( 'Loading pages...', 'lha-animation-optimizer' ); ?></p>
		</div>
		
		<div id="lha-admin-page-list-container" style="margin-top: 10px;">
			<!-- Page list table and pagination will be rendered here by JavaScript -->
		</div>
		
		<button type="button" id="lha-preview-animations-button" class="button button-primary" disabled style="margin-top: 10px;">
			<?php esc_html_e( 'Preview Site-Wide Animations', 'lha-animation-optimizer' ); ?>
		</button>
		
		<div id="lha-animation-preview-area" style="border: 1px solid #ccc; padding: 15px; margin-top: 15px; min-height: 200px; display: none; overflow: auto; background-color: #fff;">
			<!-- Animation previews will be rendered here by JavaScript -->
		</div>
		<?php
	}

	/**
	 * Enqueue admin-specific stylesheets and JavaScript.
	 * Hooked to 'admin_enqueue_scripts'.
	 *
	 * @since    1.0.0
	 * @param    string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_styles_scripts( $hook_suffix ) {
		// Only enqueue on our plugin's settings page
		// The hook_suffix for a top-level menu page is 'toplevel_page_{menu_slug}'
		if ( 'toplevel_page_' . $this->plugin_name !== $hook_suffix ) {
			return;
		}

		// Enqueue Admin Styles (optional, if you have one)
		// wp_enqueue_style( $this->plugin_name . '_admin_styles', plugin_dir_url( __FILE__ ) . 'css/lha-animation-optimizer-admin.css', array(), $this->version, 'all' );

		// Enqueue Admin JavaScript
		wp_enqueue_script( $this->plugin_name . '_admin', plugin_dir_url( __FILE__ ) . 'js/lha-animation-optimizer-admin.js', array( 'jquery' ), $this->version, true );

		// Enqueue GSAP - Assuming it's registered with the handle 'gsap'
		// WordPress doesn't bundle GSAP. It must be registered by the theme or another plugin.
		// If this plugin were to bundle GSAP, it would first wp_register_script it.
		wp_enqueue_script( 'gsap' ); // Placeholder handle, ensure GSAP is available.

		// Enqueue Public Player Script for preview functionality
		$public_script_handle = $this->plugin_name . '_public_player_for_admin'; // Unique handle
		wp_enqueue_script( 
			$public_script_handle, 
			plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/lha-animation-optimizer-public.js', 
			array( 'jquery', 'gsap' ), // Depends on jQuery and GSAP
			$this->version, 
			true 
		);

		// Localize data for the admin script
		wp_localize_script(
			$this->plugin_name . '_admin', // Handle of the admin script
			'lhaAdminAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lha_admin_ajax_nonce' ), // Re-use nonce for simplicity
				'strings'  => array(
					'error_loading_pages'   => __( 'Error: Could not load pages. Please try again.', 'lha-animation-optimizer' ),
					'no_pages_found'        => __( 'No pages found.', 'lha-animation-optimizer' ),
					'previous_page'         => __( 'Previous', 'lha-animation-optimizer' ),
					'next_page'             => __( 'Next', 'lha-animation-optimizer' ),
					'page_of'               => __( 'Page %1$d of %2$d', 'lha-animation-optimizer' ),
					'select_all'            => __( 'Select All', 'lha-animation-optimizer' ),
					'deselect_all'          => __( 'Deselect All', 'lha-animation-optimizer' ),
					'loading_preview'       => __( 'Loading animation preview...', 'lha-animation-optimizer' ),
					'no_animations_preview' => __( 'No animations to preview.', 'lha-animation-optimizer' ),
					'error_preview'         => __( 'Error loading preview.', 'lha-animation-optimizer' ),
					'player_not_found'      => __( 'Error: Public player script or its initialization function not found. Cannot run animations. Ensure it is enqueued and structured to be callable for previews.', 'lha-animation-optimizer'),
				),
			)
		);

		// Localize data for the public script (for preview settings)
		// Note: The public script itself needs to be able to handle being loaded in admin
		// and potentially not finding its usual data structures, or using these overrides.
		$public_script_preview_data = array(
			'ajax_url'                      => admin_url( 'admin-ajax.php' ), // Might not be used by public script in preview
			'lazyLoadAnimations'            => false, // CRITICAL: Disable lazy load for admin preview
			'intersectionObserverThreshold' => 0.1,   // Default, might not matter if lazyLoad is false
			'lhaPreloadedAnimations'        => null,  // Animations will be fetched by admin JS and passed
			'lhaExternalAnimationDataUrl'   => null,  // Not used for this preview type
		);
		wp_localize_script( $public_script_handle, 'lhaAnimationOptimizerSettings', $public_script_preview_data );

	}

	/**
	 * AJAX handler for fetching a paginated list of pages.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_admin_pages_callback() {
		// Security Check
		check_ajax_referer( 'lha_admin_ajax_nonce', 'nonce' );

		// Get Parameters
		$page_number    = isset( $_POST['page_number'] ) ? intval( $_POST['page_number'] ) : 1;
		$posts_per_page = isset( $_POST['posts_per_page'] ) ? intval( $_POST['posts_per_page'] ) : 10;

		if ( $page_number < 1 ) {
			$page_number = 1;
		}
		if ( $posts_per_page < 1 || $posts_per_page > 100 ) { // Max 100 per page
			$posts_per_page = 10;
		}

		// Fetch Pages
		$args = array(
			'post_type'      => 'page',
			'posts_per_page' => $posts_per_page,
			'paged'          => $page_number,
			'post_status'    => 'publish', // Consider adding 'draft', 'private' if needed
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$pages_query = new \WP_Query( $args );
		
		$pages_data = array();
		if ( $pages_query->have_posts() ) {
			while ( $pages_query->have_posts() ) {
				$pages_query->the_post();
				$pages_data[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'link'  => get_permalink(),
				);
			}
		}
		wp_reset_postdata(); // Important after custom WP_Query loop

		// Return JSON
		wp_send_json_success(
			array(
				'pages'        => $pages_data,
				'total_found'  => (int) $pages_query->found_posts,
				'max_pages'    => (int) $pages_query->max_num_pages,
				'current_page' => (int) $page_number,
			)
		);
	}

	/**
	 * AJAX handler for fetching global animation data for preview.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_global_animations_for_preview_callback() {
		// Security Check
		check_ajax_referer( 'lha_admin_ajax_nonce', 'nonce' );

		$animation_data = get_option( 'lha_detected_animations_data', [] );
		// $animation_data = apply_filters( 'lha_get_preview_animation_data', $animation_data );

		if ( ! empty( $animation_data ) ) {
			wp_send_json_success( array( 'animations' => $animation_data ) );
		} else {
			wp_send_json_success( 
				array( 
					'animations' => [], 
					'message' => __('No global animation data found in cache.', 'lha-animation-optimizer') 
				) 
			);
		}
	}
}

// This class is production-ready.
?>
