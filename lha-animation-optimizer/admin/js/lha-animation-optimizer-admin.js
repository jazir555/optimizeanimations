jQuery(document).ready(function($) {
    'use strict';

    // Check if the necessary global object and nonce are available.
    if (typeof lhaAnimationOptimizerAdmin === 'undefined' || typeof lhaAnimationOptimizerAdmin.dashboard_nonce === 'undefined') {
        console.error('LHA Animation Optimizer: Admin script data (lhaAnimationOptimizerAdmin or dashboard_nonce) is not defined.');
        // Optionally, display a user-facing error on the page if a dedicated error display area exists.
        $('#lha-dashboard-messages').html('<p>Critical error: Missing required script data. Please contact support or re-activate the plugin.</p>')
            .addClass('notice notice-error is-dismissible').show();
        return; // Stop further execution if essential data is missing.
    }

    // --- "Select All/None" Checkbox Functionality ---
    var $selectAllCheckbox = $('#lha-select-all-animations');
    var $bulkCheckboxes = $('.lha-bulk-select-checkbox');

    $selectAllCheckbox.on('change', function() {
        $bulkCheckboxes.prop('checked', $(this).prop('checked'));
    });

    $bulkCheckboxes.on('change', function() {
        if ($bulkCheckboxes.length === $bulkCheckboxes.filter(':checked').length) {
            $selectAllCheckbox.prop('checked', true);
        } else {
            $selectAllCheckbox.prop('checked', false);
        }
    });

    // --- "Apply Bulk Action" Button Click Handler ---
    $('#lha-apply-bulk-action').on('click', function(event) {
        event.preventDefault();

        var $bulkButton = $(this);
        var selectedAction = $('#lha-bulk-action-selector').val();

        if (!selectedAction || selectedAction === "") {
            alert('Please select a bulk action.');
            return;
        }

        var selectedLogIds = $('.lha-bulk-select-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedLogIds.length === 0) {
            alert('Please select at least one animation to apply the bulk action.');
            return;
        }

        var ajaxAction;
        if (selectedAction === 'apply_selected') {
            ajaxAction = 'lha_bulk_apply_optimizations';
        } else {
            alert('Invalid bulk action selected.'); // Should not happen if dropdown is controlled
            return;
        }

        var ajaxData = {
            action: ajaxAction,
            log_ids: selectedLogIds,
            nonce: lhaAnimationOptimizerAdmin.dashboard_nonce
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                $bulkButton.prop('disabled', true).addClass('lha-button-loading');
                $('#lha-bulk-action-selector').prop('disabled', true);
                $('#lha-dashboard-messages').html('<p>Processing bulk action...</p>').removeClass('notice-success notice-error notice-warning is-dismissible').addClass('notice notice-warning is-dismissible').show();
            },
            success: function(response) {
                if (typeof response !== 'object') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error("Error parsing JSON response:", e, response);
                        $('#lha-dashboard-messages').html('<p>Error: Could not parse server response for bulk action. Check console.</p>')
                            .removeClass('notice-warning').addClass('notice notice-error is-dismissible').show();
                        return;
                    }
                }

                if (response.success) {
                    var successMessage = response.data && response.data.message ? response.data.message : 'Bulk action successful!';
                    $('#lha-dashboard-messages').html('<p>' + successMessage + '</p>')
                        .removeClass('notice-warning').addClass('notice notice-success is-dismissible').show();
                    location.reload(); // Reload to see changes
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'An unknown error occurred during bulk action.';
                    $('#lha-dashboard-messages').html('<p>' + errorMessage + '</p>')
                        .removeClass('notice-warning').addClass('notice notice-error is-dismissible').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var errorMessage = 'AJAX request failed for bulk action: ';
                if (textStatus) errorMessage += textStatus;
                if (errorThrown) errorMessage += ' - ' + errorThrown;
                $('#lha-dashboard-messages').html('<p>' + errorMessage + '</p>')
                    .removeClass('notice-warning').addClass('notice notice-error is-dismissible').show();
                console.error("Bulk AJAX Error Details:", jqXHR, textStatus, errorThrown);
            },
            complete: function() {
                $bulkButton.prop('disabled', false).removeClass('lha-button-loading');
                $('#lha-bulk-action-selector').prop('disabled', false);
                $selectAllCheckbox.prop('checked', false); // Uncheck select-all
                $bulkCheckboxes.prop('checked', false); // Uncheck all individual boxes
            }
        });
    });


    // --- Existing Event delegation for individual action buttons ---
    $(document).on('click', '.lha-action-button', function(event) {
        event.preventDefault();

        var $button = $(this);
        var logId = $button.data('log-id');
        var actionType = $button.data('action'); // e.g., "apply", "deactivate", "ignore", "unignore"

        if (!logId || !actionType) {
            alert('Error: Missing data-log-id or data-action attributes on the button.');
            return;
        }

        var ajaxAction;
        switch (actionType) {
            case 'apply':
                ajaxAction = 'lha_apply_optimization';
                break;
            case 'deactivate':
                ajaxAction = 'lha_deactivate_optimization';
                break;
            case 'ignore':
                ajaxAction = 'lha_ignore_animation';
                break;
            case 'unignore':
                ajaxAction = 'lha_unignore_animation';
                break;
            default:
                alert('Error: Unknown action type specified: ' + actionType);
                return;
        }

        var ajaxData = {
            action: ajaxAction,
            log_id: logId,
            nonce: lhaAnimationOptimizerAdmin.dashboard_nonce
        };

        $.ajax({
            url: ajaxurl, // Global WordPress AJAX URL
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                $button.prop('disabled', true).addClass('lha-button-loading');
                $('#lha-dashboard-messages').html('').removeClass('notice-success notice-error notice-warning is-dismissible').hide(); // Clear previous messages
            },
            success: function(response) {
                if (typeof response !== 'object') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error("Error parsing JSON response:", e, response);
                         $('#lha-dashboard-messages').html('<p>Error: Could not parse server response. Check console for details.</p>')
                            .addClass('notice notice-error is-dismissible').show();
                        return;
                    }
                }

                if (response.success) {
                    var successMessage = response.data && response.data.message ? response.data.message : 'Action successful!';
                    if ($('#lha-dashboard-messages').length) {
                        $('#lha-dashboard-messages').html('<p>' + successMessage + '</p>')
                            .addClass('notice notice-success is-dismissible').show();
                    } else {
                        alert(successMessage);
                    }
                    location.reload();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'An unknown error occurred.';
                     if ($('#lha-dashboard-messages').length) {
                        $('#lha-dashboard-messages').html('<p>' + errorMessage + '</p>')
                            .addClass('notice notice-error is-dismissible').show();
                    } else {
                        alert('Error: ' + errorMessage);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var errorMessage = 'AJAX request failed: ';
                if (textStatus) errorMessage += textStatus;
                if (errorThrown) errorMessage += ' - ' + errorThrown;
                
                if ($('#lha-dashboard-messages').length) {
                    $('#lha-dashboard-messages').html('<p>' + errorMessage + '</p>')
                        .addClass('notice notice-error is-dismissible').show();
                } else {
                    alert(errorMessage);
                }
                 console.error("AJAX Error Details:", jqXHR, textStatus, errorThrown);
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('lha-button-loading');
            }
        });
    });
});
