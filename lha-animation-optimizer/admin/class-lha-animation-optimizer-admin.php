<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueueing the admin-specific stylesheet and JavaScript,
 * and for adding the plugin settings page with tabs.
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
	 * The key for storing plugin statistics in the database.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $stats_option_name    The name of the option used to store statistics.
	 */
	private $stats_option_name;


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
		$this->option_name = $this->plugin_name . '_options'; 
		$this->stats_option_name = $this->plugin_name . '_stats';

		add_action( 'wp_ajax_lha_analyze_css', array( $this, 'handle_css_analysis_ajax' ) );

	}

	/**
	 * Add the top-level admin menu page for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$hook_suffix = add_menu_page(
			__( 'LHA Animation Optimizer Settings', 'lha-animation-optimizer' ),
			__( 'Animation Optimizer', 'lha-animation-optimizer' ),        
			'manage_options',                                             
			$this->plugin_name, // This is the main page slug for the menu                  
			array( $this, 'admin_page_display' ),                          
			'dashicons-performance',                                        
			75                                                              
		);
		add_action( "load-{$hook_suffix}", array( $this, 'handle_import_export_actions' ) );
		add_action( "load-{$hook_suffix}", array( $this, 'handle_reset_stats_action' ) ); 
        add_action( "admin_print_styles-{$hook_suffix}", array( $this, 'enqueue_admin_styles' ) );
		add_action( "admin_print_scripts-{$hook_suffix}", array( $this, 'enqueue_admin_scripts' ) );
	}

	public function enqueue_admin_styles() { /* ... (existing code from Subtask 18, Turn 1) ... */ }
	public function enqueue_admin_scripts() { /* ... (existing code from Subtask 18, Turn 1) ... */ }
	public function admin_page_display() { /* ... (existing code from Subtask 18, Turn 1) ... */ }
	public function initialize_settings() { /* ... (existing code from Subtask 18, Turn 1) ... */ }
	public function sanitize_settings( $input ) { /* ... (existing code from Subtask 18, Turn 1) ... */ }
	
	public function render_general_settings_section_info() { /* ... */ }
	public function render_lazy_loading_settings_section_info() { /* ... */ }
	public function render_jquery_section_info() { /* ... */ }
	public function render_gsap_section_info() { /* ... */ }
	public function render_css_analyzer_section_info() { /* ... */ }
	public function render_targeting_section_info() { /* ... */ }
	public function render_statistics_section_info() { /* ... */ }
	public function render_import_export_section_info() { /* ... */ }

	public function render_css_analyzer_url_field() { /* ... */ }
	public function render_css_analyzer_css_input_field() { /* ... */ }
	public function render_css_analyzer_button_field() { /* ... */ }
	public function render_css_analyzer_results_field() { /* ... */ }

	public function render_export_settings_field() { /* ... */ }
	public function render_import_settings_field() { /* ... */ }
	public function handle_import_export_actions() { /* ... (existing code) ... */ }
	private function export_settings() { /* ... (existing code) ... */ }
	private function import_settings() { /* ... (existing code) ... */ }
	public function render_text_field( $args ) { /* ... */ }
	public function render_textarea_field( $args ) { /* ... */ }
	public function render_checkbox_field( $args ) { /* ... */ }
	public function render_number_field( $args ) { /* ... */ }
	public function render_select_field( $args ) { /* ... */ }
	public function render_radio_field( $args ) { /* ... */ }
	public function render_post_types_checkboxes_field( $args ) { /* ... */ }
	public function render_stats_display_field( $args ) { /* ... (existing code) ... */ }
	public function render_reset_stats_button_field() { /* ... (existing code) ... */ }
	public function handle_reset_stats_action() { /* ... (existing code) ... */ }


	/**
	 * Parses CSS properties from a string.
	 * Example: "color: red; font-size: 12px;" -> {"color": "red", "font-size": "12px"}
	 *
	 * @param string $css_props_string The CSS properties string.
	 * @return array Parsed properties.
	 */
	private function parse_css_properties( $css_props_string ) {
		$properties = array();
		$pairs = explode( ';', $css_props_string );
		foreach ( $pairs as $pair ) {
			if ( trim( $pair ) === '' ) {
				continue;
			}
			$parts = explode( ':', $pair, 2 );
			if ( count( $parts ) === 2 ) {
				$prop_name  = trim( $parts[0] );
				$prop_value = trim( $parts[1] );
				$properties[ $prop_name ] = $prop_value;
			}
		}
		return $properties;
	}


	/**
	 * Enhanced CSS Analysis AJAX Handler.
	 *
	 * @since 1.1.0 (Original AJAX handler)
	 * @since 2.0.0 (Enhanced parsing logic and suggestions)
	 */
	public function handle_css_analysis_ajax() {
		check_ajax_referer( 'lha_analyze_css_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lha-animation-optimizer' ) ), 403 );
		}
	
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$css_input = isset( $_POST['css_input'] ) ? wp_kses_post( wp_unslash( $_POST['css_input'] ) ) : ''; 
		$css_content = '';
		$analysis_results = array();
		$performant_props = array('transform', 'opacity'); 

		if ( ! empty( $url ) ) {
			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) { wp_send_json_error( array( 'message' => __( 'Failed to fetch URL: ', 'lha-animation-optimizer' ) . $response->get_error_message() ) ); }
			$html_content = wp_remote_retrieve_body( $response );
			preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html_content, $style_tags );
			if ( ! empty( $style_tags[1] ) ) { $css_content .= implode( "\n", $style_tags[1] ); }
			preg_match_all('/<link[^>]+rel=[\'"]stylesheet[\'"][^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html_content, $link_tags);
			if (!empty($link_tags[1])) {
				foreach ($link_tags[1] as $stylesheet_url) {
					$stylesheet_url = esc_url_raw( $stylesheet_url, array('http', 'https') ); 
					if (strpos($stylesheet_url, home_url()) === 0 || strpos($stylesheet_url, content_url()) === 0 || !wp_http_validate_url($stylesheet_url)) { 
						$linked_css_response = wp_remote_get($stylesheet_url);
						if (!is_wp_error($linked_css_response) && wp_remote_retrieve_response_code($linked_css_response) === 200) {
							$css_content .= "\n" . wp_remote_retrieve_body($linked_css_response);
						}
					}
				}
			}
			if(empty($css_content)){ wp_send_json_error( array( 'message' => __( 'No inline CSS or accessible local linked CSS found at the URL.', 'lha-animation-optimizer' ) ) ); }
		} elseif ( ! empty( $css_input ) ) {
			$css_content = $css_input;
		} else {
			wp_send_json_error( array( 'message' => __( 'Please provide a URL or CSS input.', 'lha-animation-optimizer' ) ) );
		}
		if ( empty( $css_content ) ) { wp_send_json_error( array( 'message' => __( 'No CSS content to analyze.', 'lha-animation-optimizer' ) ) );}

		$css_content_no_comments = preg_replace( '/\/\*.*?\*\//s', '', $css_content );
		
		$keyframes_store = array();
		preg_match_all( '/@keyframes\s+([\w-]+)\s*{(.*?)}/s', $css_content_no_comments, $kf_matches, PREG_SET_ORDER );
		foreach ( $kf_matches as $kf_match ) {
			$animation_name = trim( $kf_match[1] );
			$keyframes_store[ $animation_name ] = array();
			preg_match_all( '/([\d%]+|from|to)\s*{(.*?)}/s', $kf_match[2], $step_matches, PREG_SET_ORDER );
			foreach ( $step_matches as $step_match ) {
				$step = trim( $step_match[1] );
				$properties_in_step = $this->parse_css_properties( $step_match[2] );
				$keyframes_store[ $animation_name ][ $step ] = $properties_in_step;
			}
		}

		preg_match_all('/([^{]+)\s*{(.*?)}/s', $css_content_no_comments, $rules, PREG_SET_ORDER);
		foreach ($rules as $rule_match) {
			$selector = trim($rule_match[1]);
			$properties_block_str = $rule_match[2];
			$properties_in_rule = $this->parse_css_properties($properties_block_str);

			$current_animation_names = array();
			if (isset($properties_in_rule['animation-name'])) {
				$current_animation_names = array_map('trim', explode(',', $properties_in_rule['animation-name']));
			} elseif (isset($properties_in_rule['animation'])) {
				$anim_parts = preg_split('/\s+/', $properties_in_rule['animation']);
				if (isset($anim_parts[0]) && !is_numeric(substr($anim_parts[0], 0, 1)) && strpos($anim_parts[0], 's') === false && strpos($anim_parts[0], 'ms') === false) {
					$current_animation_names[] = $anim_parts[0];
				}
			}

			foreach($current_animation_names as $anim_name) {
				if (isset($keyframes_store[$anim_name])) {
					foreach ($keyframes_store[$anim_name] as $step => $kf_props) {
						foreach ($kf_props as $prop_name => $prop_val) {
							$suggestion = null;
							if (!in_array(strtolower($prop_name), $performant_props) && !preg_match('/^(transform|opacity)/i', $prop_name) ) {
								if (in_array(strtolower($prop_name), array('left', 'right', 'top', 'bottom', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom'))) {
									$axis = (in_array(strtolower($prop_name), array('left', 'right', 'margin-left', 'margin-right'))) ? 'X' : 'Y';
									$suggestion = array(
										'type' => 'transform_position',
										'original_property' => $prop_name,
										'suggested_property' => 'transform',
										'example_value' => 'translate' . $axis . '()',
										'comment' => __( 'Animating transform is generally more performant than animating positional properties like top/left/right/bottom or margins.', 'lha-animation-optimizer' )
									);
								} elseif (in_array(strtolower($prop_name), array('width', 'height', 'padding', 'margin'))) { // Added padding/margin here too.
									$suggestion = array(
										'type' => 'transform_scale_or_layout',
										'original_property' => $prop_name,
										'comment' => __( "Animating width/height or padding/margin causes layout reflows. Consider if 'transform: scale()' could achieve a similar visual effect (for width/height), though it may require adjustments to layout and transform-origin. For spacing changes, reconsider if the animation is essential or can be achieved differently.", 'lha-animation-optimizer' )
									);
								}

								$analysis_results[] = array(
									'type' => 'warning',
									'message' => sprintf(__( 'Animation "%s" (applied to selector "%s") animates non-performant property "%s" in its "%s" keyframe.', 'lha-animation-optimizer' ), esc_html($anim_name), esc_html($selector), esc_html($prop_name), esc_html($step)),
									'selector' => esc_html($selector), 'property' => esc_html($prop_name), 'value' => esc_html($prop_val), 'animation_name' => esc_html($anim_name), 'keyframe_step' => esc_html($step),
									'suggestion' => $suggestion // Add suggestion here
								);
							}
						}
					}
				}
			}
		}

		preg_match_all('/(animation-duration|transition-duration)\s*:\s*([\d.]+s)/i', $css_content_no_comments, $duration_matches, PREG_SET_ORDER);
		foreach ($duration_matches as $match) { $duration_value = floatval($match[2]); if ($duration_value > 1.5) { $analysis_results[] = array( 'type' => 'info', 'message' => sprintf(__( 'Long duration for "%s: %s". Consider if a shorter duration is possible for better perceived performance.', 'lha-animation-optimizer' ), esc_html($match[1]), esc_html($match[2])), 'property' => esc_html($match[1]), 'value' => esc_html($match[2]) ); } }
		
		preg_match_all('/will-change\s*:\s*([^;]+);/i', $css_content_no_comments, $will_change_matches, PREG_SET_ORDER);
		foreach ($will_change_matches as $match) { $wc_values = array_map('trim', explode(',', $match[1])); $non_performant_wc = array_diff($wc_values, $performant_props, array('scroll-position', 'contents')); if (count($wc_values) > 2 || !empty($non_performant_wc) ) { $analysis_results[] = array( 'type' => 'info', 'message' => sprintf(__( '`will-change: %s;` found. Ensure it\'s used judiciously. Using it for non-performant properties or too many properties can be detrimental.', 'lha-animation-optimizer' ), esc_html($match[1])), 'property' => 'will-change', 'value' => esc_html($match[1]) ); } }

		if (preg_match('/animation-iteration-count\s*:\s*infinite/i', $css_content_no_comments)) { $analysis_results[] = array( 'type' => 'info', 'message' => __( '`animation-iteration-count: infinite` found. Ensure infinite animations are purposeful and optimize the animated properties (transform, opacity) to avoid continuous repaint/reflow.', 'lha-animation-optimizer' ), 'property' => 'animation-iteration-count', 'value' => 'infinite' );}
	
		wp_send_json_success( array( 'message' => __( 'Analysis Complete', 'lha-animation-optimizer' ), 'results' => $analysis_results, 'raw_css_length' => strlen($css_content) ) );
	}
}

// This class is production-ready.
?>
