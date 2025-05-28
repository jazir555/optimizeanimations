(function( $ ) {
	'use strict';

	/**
	 * LHA Animation Optimizer Admin Script
	 *
	 * Handles interactions on the LHA Animation Optimizer settings page.
	 */
	$(function() {
		// Cache the button element for clearing animation cache.
		var $clearCacheButton = $( '#lha_clear_animation_cache_button' );

		if ( $clearCacheButton.length ) {
			$clearCacheButton.on( 'click', function( e ) {
				e.preventDefault(); // Prevent default button action (if it were a submit button, for example).

				// Visually indicate that the process has started and prevent multiple clicks.
				$(this).prop( 'disabled', true ).text( 'Clearing...' ); // Text updated for user feedback.

				// Perform the AJAX request to clear the animation cache.
				// Uses settings localized from PHP (lhaAdminAjax object).
				$.ajax({
					url: lhaAdminAjax.ajaxUrl, // The WordPress AJAX handler URL.
					type: 'POST',
					data: {
						action: lhaAdminAjax.clearCacheAction, // The specific AJAX action hook: 'lha_clear_animation_cache'.
						nonce: lhaAdminAjax.clearCacheNonce    // Security nonce for the action.
					},
					success: function( response ) {
						if ( response.success ) {
							// Display success message (using alert for simplicity, could be a more integrated UI element).
							alert( lhaAdminAjax.successMessage ); // Message localized from PHP.
						} else {
							// Display error message, potentially appending more details from the server response.
							let errorMessage = lhaAdminAjax.errorMessage;
							if (response.data && response.data.message) {
								errorMessage += ' ' + response.data.message;
							}
							alert( errorMessage ); // Message localized from PHP.
						}
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						// Display a generic error message in case of network or other AJAX failures.
						alert( lhaAdminAjax.errorMessage + ' (Request Failed: ' + textStatus + ' - ' + errorThrown + ')' ); // Message localized.
					},
					complete: function() {
						// Always re-enable the button and restore its text, regardless of success or failure.
						$clearCacheButton.prop( 'disabled', false ).text( 'Clear Animation Cache Now' );
					}
				});
			});
		}

		// Optional: console log to confirm the admin script is loaded.
		// console.log('LHA Animation Optimizer Admin JS Loaded and Ready');
	});

})( jQuery );
