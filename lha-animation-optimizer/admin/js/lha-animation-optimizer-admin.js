(function($) {
    'use strict';

    $(function() {
        const pageListContainer = $('#lha-admin-page-list-container');
        const loadingIndicator = $('#lha-page-list-loading');
        const loadPagesButton = $('#lha-load-pages-button');
        const previewAnimationsButton = $('#lha-preview-animations-button');
        let currentPage = 1; // Keep track of the current page
        const postsPerPage = 10; // Or make this configurable

        // Event Listener for "Load Pages" button
        loadPagesButton.on('click', function() {
            fetchPages(1); // Load the first page
        });

        function fetchPages(pageNumber) {
            currentPage = pageNumber;
            loadingIndicator.show();
            pageListContainer.hide().empty(); // Hide and clear previous content
            previewAnimationsButton.prop('disabled', true); // Disable while loading new pages

            $.ajax({
                url: lhaAdminAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'lha_get_admin_pages',
                    nonce: lhaAdminAjax.nonce,
                    page_number: pageNumber,
                    posts_per_page: postsPerPage
                },
                success: function(response) {
                    loadingIndicator.hide();
                    if (response.success) {
                        renderPageList(response.data);
                        pageListContainer.show();
                    } else {
                        const errorMessage = response.data && response.data.message ? response.data.message : lhaAdminAjax.strings.error_loading_pages;
                        pageListContainer.html('<p class="notice notice-error">' + errorMessage + '</p>').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    loadingIndicator.hide();
                    pageListContainer.html('<p class="notice notice-error">' + lhaAdminAjax.strings.error_loading_pages + ' (' + textStatus + ': ' + errorThrown + ')</p>').show();
                }
            });
        }

        function renderPageList(data) {
            pageListContainer.empty(); // Clear previous content

            if (!data || !data.pages || data.pages.length === 0) {
                pageListContainer.html('<p>' + lhaAdminAjax.strings.no_pages_found + '</p>');
                previewAnimationsButton.prop('disabled', true); // Ensure it's disabled
                return;
            }

            let tableHtml = '<table class="wp-list-table widefat striped pages lha-admin-pages-table">';
            tableHtml += '<thead><tr>';
            // Use lhaAdminAjax.strings for "Select All" title text
            tableHtml += '<th scope="col" id="lha-cb" class="manage-column column-cb check-column"><input type="checkbox" id="lha-select-all-pages" title="' + lhaAdminAjax.strings.select_all + '"></th>';
            tableHtml += '<th scope="col" id="lha-title" class="manage-column column-title">' + 'Title' + '</th>'; // WordPress admin table standard
            tableHtml += '</tr></thead>';
            
            tableHtml += '<tbody id="the-list">';
            data.pages.forEach(function(page) {
                tableHtml += '<tr>';
                tableHtml += '<th scope="row" class="check-column"><input type="checkbox" class="lha-page-checkbox" name="lha_selected_pages[]" value="' + page.id + '" data-url="' + page.link + '"></th>';
                // Sanitize title if needed, though typically titles from WP are safe.
                tableHtml += '<td class="title column-title"><a href="' + page.link + '" target="_blank" rel="noopener noreferrer">' + page.title + '</a></td>';
                tableHtml += '</tr>';
            });
            tableHtml += '</tbody></table>';
            pageListContainer.append(tableHtml);

            // Render Pagination
            if (data.max_pages > 1) {
                let paginationHtml = '<div class="tablenav"><div class="tablenav-pages">';
                // Total items: data.total_found
                paginationHtml += '<span class="displaying-num">' + data.total_found + ' ' + (data.total_found === 1 ? 'item' : 'items') + '</span>';
                paginationHtml += '<span class="pagination-links">';

                if (data.current_page > 1) {
                    paginationHtml += '<a class="prev-page button lha-prev-page" href="#" data-page="' + (data.current_page - 1) + '"><span aria-hidden="true">&laquo;</span> <span>' + lhaAdminAjax.strings.previous_page + '</span></a>';
                } else {
                    paginationHtml += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                }

                paginationHtml += '<span class="paging-input"><label for="lha-current-page-selector" class="screen-reader-text">Current Page</label>';
                // Using lhaAdminAjax.strings for "Page X of Y"
                paginationHtml += '<span class="tablenav-paging-text"> ' + lhaAdminAjax.strings.page_of.replace('%1$d', data.current_page).replace('%2$d', data.max_pages) + ' </span>';
                paginationHtml += '</span>';

                if (data.current_page < data.max_pages) {
                    paginationHtml += '<a class="next-page button lha-next-page" href="#" data-page="' + (data.current_page + 1) + '"><span aria-hidden="true">&raquo;</span> <span>' + lhaAdminAjax.strings.next_page + '</span></a>';
                } else {
                    paginationHtml += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                }

                paginationHtml += '</span></div></div>';
                pageListContainer.append(paginationHtml);
            }
            updatePreviewButtonState(); // Call after rendering to set initial state of preview button
        }

        // Delegated event listeners for pagination buttons
        pageListContainer.on('click', '.lha-prev-page', function(e) {
            e.preventDefault();
            if (!$(this).hasClass('disabled')) {
                fetchPages(parseInt($(this).data('page')));
            }
        });

        pageListContainer.on('click', '.lha-next-page', function(e) {
            e.preventDefault();
            if (!$(this).hasClass('disabled')) {
                fetchPages(parseInt($(this).data('page')));
            }
        });
        
        // Delegated event listener for individual page checkboxes
        pageListContainer.on('change', '.lha-page-checkbox', function() {
            updatePreviewButtonState();
        });

        // Delegated event listener for "Select All" checkbox
        pageListContainer.on('change', '#lha-select-all-pages', function() {
            const isChecked = $(this).prop('checked');
            pageListContainer.find('.lha-page-checkbox').prop('checked', isChecked);
            // Update title for "Select All" checkbox based on its state
            $(this).prop('title', isChecked ? lhaAdminAjax.strings.deselect_all : lhaAdminAjax.strings.select_all);
            updatePreviewButtonState();
        });

        function updatePreviewButtonState() {
            const anyCheckboxSelected = pageListContainer.find('.lha-page-checkbox:checked').length > 0;
            previewAnimationsButton.prop('disabled', !anyCheckboxSelected);
            
            // Update "Select All" checkbox state based on individual checkboxes
            const totalCheckboxes = pageListContainer.find('.lha-page-checkbox').length;
            if (totalCheckboxes > 0) {
                 const totalChecked = pageListContainer.find('.lha-page-checkbox:checked').length;
                 const allAreChecked = totalChecked === totalCheckboxes;
                 $('#lha-select-all-pages').prop('checked', allAreChecked);
                 $('#lha-select-all-pages').prop('title', allAreChecked ? lhaAdminAjax.strings.deselect_all : lhaAdminAjax.strings.select_all);
            } else {
                 // No checkboxes (e.g. no pages found)
                 $('#lha-select-all-pages').prop('checked', false);
                 $('#lha-select-all-pages').prop('title', lhaAdminAjax.strings.select_all);
            }
        }

        // --- Animation Preview Logic ---
        const previewArea = $('#lha-animation-preview-area');

        previewAnimationsButton.on('click', function() {
            if ($(this).prop('disabled')) {
                return;
            }
            fetchAndPreviewAnimations();
        });

        function fetchAndPreviewAnimations() {
            previewArea.show().html('<p>' + lhaAdminAjax.strings.loading_preview + '</p>');

            // Optional: Get selected page URL for context (not used by PHP backend yet)
            // const selectedPageCheckbox = pageListContainer.find('.lha-page-checkbox:checked').first();
            // const selectedPageUrl = selectedPageCheckbox.length > 0 ? selectedPageCheckbox.data('url') : null;

            $.ajax({
                url: lhaAdminAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'lha_get_global_animations_for_preview',
                    nonce: lhaAdminAjax.nonce,
                    // selected_page_url: selectedPageUrl 
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.animations && response.data.animations.length > 0) {
                            renderPreviewContent(response.data.animations);
                        } else {
                            const message = response.data.message || lhaAdminAjax.strings.no_animations_preview;
                            previewArea.html('<p>' + message + '</p>');
                        }
                    } else {
                        const errorMessage = response.data && response.data.message ? response.data.message : lhaAdminAjax.strings.error_preview;
                        previewArea.html('<p class="notice notice-error">' + errorMessage + '</p>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    previewArea.html('<p class="notice notice-error">' + lhaAdminAjax.strings.error_preview + ' (' + textStatus + ': ' + errorThrown + ')</p>');
                }
            });
        }

        function renderPreviewContent(animations) {
            previewArea.empty(); // Clear loading message

            // Create dummy HTML elements
            animations.forEach(function(animObject, index) {
                if (!animObject.selector) return;

                // Simple parsing for common selectors, more complex ones might need robust parsing
                let selector = animObject.selector.trim();
                let id = '';
                let classes = 'lha-preview-element'; // Common class for styling
                let tagName = 'div'; // Default tag

                if (selector.startsWith('#')) {
                    id = selector.substring(1);
                } else if (selector.startsWith('.')) {
                    classes += ' ' + selector.substring(1).replace(/\./g, ' '); // Replace multiple dots for chained classes
                } else {
                    // Assuming it's a tag name or a more complex selector; for preview, just use the selector as a class.
                    // This part can be enhanced for better representation of complex selectors.
                    classes += ' ' + selector.replace(/[#.>+~:\s\[\]()]/g, '-').replace(/^-+|-+$/g, '');
                }
                
                // Add unique ID for preview if original selector was not an ID
                const previewElementId = id ? id : `lha-preview-el-${index}`;
                if (!id) { // If original selector was not an ID, use the generated one
                    selector = `#${previewElementId}`; // The player will use this for targeting
                    animObject.preview_selector_override = selector; // Pass override to player if needed
                }


                const dummyElement = $('<' + tagName + '>', {
                    'id': id ? id : previewElementId, // Use original ID if present, else generated
                    'class': classes,
                    'text': 'Preview: ' + animObject.selector // Original selector for text
                }).css({ // Basic styling to make elements visible
                    margin: '10px',
                    padding: '10px',
                    border: '1px dashed #0073aa',
                    minHeight: '50px',
                    textAlign: 'center',
                    backgroundColor: '#f0f0f0'
                });
                previewArea.append(dummyElement);
            });
            
            console.log("LHA Admin Preview: Dummy elements rendered. Animation data:", animations);

            // Ensure player API is available
            if (window.LHA_Animation_Optimizer_Public_API && typeof window.LHA_Animation_Optimizer_Public_API.initializeAnimations === 'function') {
                console.log("LHA Admin Preview: Calling public player's initializeAnimations function.");
                
                // The public player script's settings were already localized by PHP with lazyLoadAnimations: false
                // Now, we pass the animation data directly.
                // The public script's getAnimationData will be bypassed because we call initializeAnimations directly.
                
                // Modify animation objects to use preview_selector_override if present
                const previewAnimations = animations.map(anim => {
                    if (anim.preview_selector_override) {
                        return { ...anim, selector: anim.preview_selector_override };
                    }
                    return anim;
                });

                window.LHA_Animation_Optimizer_Public_API.initializeAnimations(previewAnimations);
            } else {
                previewArea.append('<p class="notice notice-error">' + lhaAdminAjax.strings.player_not_found + '</p>');
                console.error("LHA Admin Preview: LHA_Animation_Optimizer_Public_API.initializeAnimations function not found.");
            }
        }

    });
})(jQuery);
