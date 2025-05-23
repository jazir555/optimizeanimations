(function( $ ) {
	'use strict';

	/**
	 * LHA Animation Optimizer Admin Script
	 *
	 * Handles admin-specific JavaScript, including the CSS Analyzer tool
	 * and conditional visibility for targeting rules.
	 */
	$(function() {

		// CSS Analyzer AJAX Handler
		$('#lha_analyze_css_button').on('click', function() {
			const $button = $(this);
			const $resultsDiv = $('#lha_css_analyzer_results');
			const url = $('#lha_css_analyzer_url').val();
			const cssInput = $('#lha_css_analyzer_css_input').val();

			$resultsDiv.html('<p>Analyzing...</p>');
			$button.prop('disabled', true);

			const ajaxData = {
				action: 'lha_analyze_css',
				nonce: lhaAdminAjax.analyze_css_nonce, 
				url: url,
				css_input: cssInput
			};

			$.post(lhaAdminAjax.ajax_url, ajaxData, function(response) {
				if (response.success) {
					let resultsHtml = '<p>' + esc_html(response.data.message) + '</p>';
					if (response.data.results && response.data.results.length > 0) {
						resultsHtml += '<h4>Analysis Details:</h4>';
						resultsHtml += '<ul class="lha-analysis-results-list">';
						response.data.results.forEach(function(item) {
							let itemClass = 'lha-analysis-item';
							if (item.type === 'warning') {
								itemClass += ' lha-analysis-item-warning';
							} else if (item.type === 'info') {
								itemClass += ' lha-analysis-item-info';
							} else if (item.type === 'error') { 
								itemClass += ' lha-analysis-item-error';
							}

							resultsHtml += '<li class="' + itemClass + '">';
							resultsHtml += '<strong>' + esc_html(item.type.toUpperCase()) + ':</strong> ' + esc_html(item.message);
							if (item.selector) {
								resultsHtml += '<br><em>Selector:</em> <code>' + esc_html(item.selector) + '</code>';
							}
							if (item.property) {
								resultsHtml += '<br><em>Property:</em> <code>' + esc_html(item.property) + '</code>';
							}
							if (item.value) {
								resultsHtml += '<br><em>Value:</em> <code>' + esc_html(item.value) + '</code>';
							}
							resultsHtml += '</li>';
						});
						resultsHtml += '</ul>';
						if (typeof response.data.raw_css_length !== 'undefined') {
							resultsHtml += '<p>Raw CSS analyzed (approx. ' + response.data.raw_css_length + ' characters).</p>';
						}

					} else {
						resultsHtml += '<p>' + esc_html__('No specific issues found based on current rules, or no CSS content was analyzable.', 'lha-animation-optimizer') + '</p>';
						if (typeof response.data.raw_css_length !== 'undefined') {
							resultsHtml += '<p>Raw CSS analyzed (approx. ' + response.data.raw_css_length + ' characters).</p>';
						}
					}
					$resultsDiv.html(resultsHtml);
				} else {
					$resultsDiv.html('<p class="lha-analysis-item-error">Error: ' + esc_html(response.data.message) + '</p>');
				}
			})
			.fail(function(xhr, textStatus, errorThrown) {
				$resultsDiv.html('<p class="lha-analysis-item-error">AJAX Error: ' + esc_html(textStatus) + ' - ' + esc_html(errorThrown) + '</p>');
			})
			.always(function() {
				$button.prop('disabled', false);
			});
		});

		// Basic HTML escaping function for client-side display
		function esc_html(unsafe) {
			if (typeof unsafe !== 'string') {
				if (typeof unsafe === 'number' || typeof unsafe === 'boolean') {
					return String(unsafe);
				}
				return ''; 
			}
			return unsafe
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");
		}
		
		// Helper for WordPress internationalization (if needed for dynamic strings)
		// const { __ } = wp.i18n;


		// Tab persistence and active class handling
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab');
        if (currentTab) {
            $('.nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab-wrapper a.nav-tab[href*="tab=' + esc_html(currentTab) + '"]').addClass('nav-tab-active');
        } else {
			 $('.nav-tab-wrapper a.nav-tab[href*="tab=general"]').addClass('nav-tab-active');
		}

		// Targeting Rules: Conditional field visibility
		const optimizationScopeRadios = $('input[type="radio"][name="lha_animation_optimizer_options[optimization_scope]"]');
		const conditionalFields = $('#target_post_types').closest('tr').add( $('#target_page_ids').closest('tr') );
		// Using the class ".lha-targeting-conditional" added to the fieldset and p.description for post types
		const conditionalPostTypesElements = $('.lha-targeting-conditional');


		function toggleConditionalTargetingFields() {
			if ($('input[type="radio"][name="lha_animation_optimizer_options[optimization_scope]"]:checked').val() === 'conditional') {
				conditionalFields.show();
				conditionalPostTypesElements.show();
			} else {
				conditionalFields.hide();
				conditionalPostTypesElements.hide();
			}
		}

		// Initial state
		toggleConditionalTargetingFields();

		// Toggle on change
		optimizationScopeRadios.on('change', function() {
			toggleConditionalTargetingFields();
		});

	}); // End document ready

})( jQuery );
