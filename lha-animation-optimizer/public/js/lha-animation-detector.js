(function( window, document, $ ) { // $ is passed but we'll check window.jQuery
    'use strict';

    // Localized settings from PHP (ajaxUrl, saveNonce, saveAction).
    var lhaDetectorSettings = window.lhaDetectorSettings || {};
    // Global array to store all detected animation objects before sending to server.
    // This array is populated by detectJQueryAnimationsWrapperSetup (as animations are defined)
    // and by detectGSAPAnimationsOnDemand (when it's called).
    let detectedAnimations = [];

    /**
     * Generates a reasonably specific CSS selector for a given DOM element.
     * Prioritizes ID, then tag name with unique class combinations.
     * Includes a basic :nth-of-type refinement if similar siblings are found.
     * Note: This is a best-effort approach and may not be perfectly unique in all complex DOMs.
     * @param {Element} el The DOM element.
     * @returns {string|null} A CSS selector string or null if element is invalid.
     */
    function generateSelector(el) {
        if (!el || !(el instanceof Element)) {
            // console.warn('LHA Detector: Invalid element passed to generateSelector.');
            return null;
        }

        // Priority 1: Element ID (should be unique)
        if (el.id) {
            // Basic sanitization for IDs starting with a digit or containing special characters.
            // CSS.escape() would be more robust but has limited browser support for older versions.
            let id = el.id;
            // If ID starts with a digit, it needs to be escaped in CSS: \3 followed by the digit and a space.
            if (/^[0-9]/.test(id)) { 
                id = '\\3' + id.charAt(0) + ' ' + id.substring(1);
            }
            // Escape other common special characters. This list is not exhaustive.
            return '#' + id.replace(/([!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        }

        // Priority 2: Tag name with classes
        let selector = el.tagName.toLowerCase();
        if (el.className && typeof el.className === 'string') {
            // Filter out empty strings that can result from multiple spaces, then join.
            const classes = el.className.trim().split(/\s+/).filter(Boolean).join('.');
            if (classes) {
                selector += '.' + classes;
            }
        }
        
        // Refinement: If this selector is still not unique among siblings, add :nth-of-type.
        // This helps distinguish between elements like multiple <p class="highlight"> under the same parent.
        try {
            const parent = el.parentNode;
            if (parent && parent.nodeType === Node.ELEMENT_NODE) { // Ensure parent is an element
                const siblings = Array.from(parent.children);
                // Filter for siblings with the exact same tag and class list for a more accurate :nth-of-type
                const sameTagAndClassSiblings = siblings.filter(sibling => 
                    sibling.tagName === el.tagName && sibling.className === el.className
                );
                
                if (sameTagAndClassSiblings.length > 1) {
                    // If there are multiple siblings with the same tag and class, find the index of the current element
                    // among *all* siblings of the same tag to determine its :nth-of-type index.
                    const typeSiblings = siblings.filter(s => s.tagName === el.tagName);
                    const nthOfTypeIndex = typeSiblings.indexOf(el);
                    if (nthOfTypeIndex !== -1) {
                        selector += ':nth-of-type(' + (nthOfTypeIndex + 1) + ')';
                    }
                // Check if selector is unique globally. If not, and if parent isn't body/document, try to add parent selector.
                // Limit recursion by not calling generateSelector for parent if parent is body to avoid overly long selectors.
                } else if (document.querySelectorAll(selector).length > 1 && parent.tagName.toLowerCase() !== 'body' && parent !== document) {
                    const parentSelector = generateSelector(parent); // Recursive call for parent
                    if (parentSelector) {
                        selector = parentSelector + ' > ' + selector;
                    }
                }
            }
        } catch (e) {
            // console.warn("LHA Detector: Error generating more specific selector part", e);
        }


        return selector;
    }

    /**
     * Detects jQuery animations by wrapping jQuery.fn.animate.
     */
    function detectJQueryAnimations() {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.animate === 'undefined') {
            // console.log('LHA Detector: jQuery or jQuery.fn.animate not found.');
            return;
        }

        const originalJQueryAnimate = window.jQuery.fn.animate;
        window.jQuery.fn.animate = function() {
            const elements = this; // 'this' is the jQuery object (collection of elements)
            const properties = arguments[0];
            let duration = 400; // jQuery's default
            let easing = 'swing'; // jQuery's default
            // let callback; // Not captured for now

            if (typeof arguments[1] === 'number') {
                duration = arguments[1];
                if (typeof arguments[2] === 'string') easing = arguments[2];
                // if (typeof arguments[3] === 'function') callback = arguments[3];
            } else if (typeof arguments[1] === 'string') {
                easing = arguments[1];
                // if (typeof arguments[2] === 'function') callback = arguments[2];
            } else if (typeof arguments[1] === 'object') { // options object
                duration = arguments[1].duration || duration;
                easing = arguments[1].easing || easing;
                // callback = arguments[1].complete || callback;
            }


            if (elements && elements.length) {
                elements.each(function() { // Iterate over each DOM element in the jQuery collection
                    const element = this; // 'this' is the raw DOM element
                    const selector = generateSelector(element);
                    if (selector) {
                        try {
                            detectedAnimations.push({
                                id: 'jquery-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                                selector: selector,
                                type: 'jquery',
                                // Deep clone properties, ensure it's a plain object.
                                jquery_properties: JSON.parse(JSON.stringify(properties || {})),
                                jquery_duration: duration,
                                jquery_easing: easing, // Store easing
                                triggerClass: 'lha-animate-now' // Default trigger class for player
                            });
                        } catch (e) {
                            console.warn("LHA Detector: Error processing jQuery animation data for selector:", selector, e);
                        }
                    }
                });
            }
            // Call the original animation function
            return originalJQueryAnimate.apply(this, arguments);
        };
    }

    /**
     * Detects GSAP animations by inspecting the global timeline.
     * This is a best-effort approach and might not capture all GSAP animations.
     */
    function detectGSAPAnimations() {
        if (typeof window.gsap === 'undefined') {
            // console.log('LHA Detector: GSAP not found.');
            return;
        }

        try {
            // Get all tweens and timelines from the global timeline.
            // true, true, true means (position, nested, tweens)
            const allTweens = window.gsap.globalTimeline.getChildren(true, true, true);

            if (allTweens && allTweens.length) {
                allTweens.forEach(tween => {
                    // We are interested in tweens (not timelines themselves, though they contain tweens)
                    if (tween.targets && typeof tween.targets === 'function') {
                        const targets = tween.targets();
                        targets.forEach(targetElement => {
                            if (targetElement instanceof Element) { // Ensure it's a DOM element
                                const selector = generateSelector(targetElement);
                                if (selector) {
                                    try {
                                        // Clone vars, ensure it's a plain object.
                                        // GSAP vars can be complex, this will simplify them.
                                        const varsCopy = JSON.parse(JSON.stringify(tween.vars || {}));
                                        detectedAnimations.push({
                                            id: 'gsap-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                                            selector: selector,
                                            type: 'gsap',
                                            gsap_to_vars: varsCopy,
                                            gsap_duration: tween.duration(),
                                            triggerClass: 'lha-animate-now' // Default trigger class
                                        });
                                    } catch (e) {
                                        console.warn("LHA Detector: Error processing GSAP animation data for selector:", selector, e);
                                    }
                                }
                            }
                        });
                    }
                });
            }
        } catch (e) {
            console.warn('LHA Detector: Error while trying to inspect GSAP globalTimeline:', e);
        }
    }

    /**
     * Collects animation data from various detectors and sends it to the server.
     */
    function collectAndSendAnimationData() {
        if ( !lhaDetectorSettings.ajaxUrl || !lhaDetectorSettings.saveNonce || !lhaDetectorSettings.saveAction ) {
            console.error('LHA Detector: Missing required settings (ajaxUrl, saveNonce, or saveAction). Data cannot be sent.');
            return;
        }

        // Initialize/clear the array for this run
        detectedAnimations = [];

        console.log('LHA Detector: Starting animation detection...');
        
        // Detection functions should populate `detectedAnimations`
        detectJQueryAnimations(); // This wraps jQuery.fn.animate, so it runs as animations are defined
        detectGSAPAnimations();   // This inspects GSAP timeline, better called after some delay or on an event

        // To capture GSAP animations that might be set up after initial script load,
        // we might need to delay this or trigger it based on a custom event.
        // For now, we call it once on DOMContentLoaded or interactively.
        // A more robust GSAP detection might involve wrapping gsap.to, .from, .fromTo etc.

        console.log('LHA Detector: Current detected animations:', detectedAnimations);
        
        // If no animations detected, we might choose not to send.
        // For now, send even if empty to signal completion of detection phase.
        // if (detectedAnimations.length === 0) {
        //     console.log('LHA Detector: No animations detected. Nothing to send.');
        //     return;
        // }

        console.log('LHA Detector: Sending data to server...', detectedAnimations);

        // Use jQuery for AJAX if available and $ was passed, otherwise try native fetch if $ is not jQuery
        const ajaxFunction = (typeof $ === 'function' && $.ajax) ? $.ajax : function(options) {
            fetch(options.url, {
                method: options.type,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams(options.data).toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(options.success)
            .catch(options.error);
        };


        ajaxFunction({
            url: lhaDetectorSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: lhaDetectorSettings.saveAction,
                nonce: lhaDetectorSettings.saveNonce,
                detected_animations: JSON.stringify(detectedAnimations)
            },
            success: function( response ) {
                if ( response.success ) {
                    console.log('LHA Detector: Animation data successfully sent and saved.', response.data);
                } else {
                    console.error('LHA Detector: Server responded with an error.', response.data && response.data.message ? response.data.message : 'Unknown error');
                }
            },
            error: function( jqXHR_or_Error, textStatus, errorThrown_or_undefined ) {
                let errorMessage = textStatus || 'AJAX error';
                if (jqXHR_or_Error instanceof Error) {
                    errorMessage = jqXHR_or_Error.message;
                } else if (typeof errorThrown_or_undefined === 'string') {
                    errorMessage = errorThrown_or_undefined;
                }
                console.error('LHA Detector: AJAX error sending animation data.', errorMessage);
            }
        });
    }

    // The jQuery wrapper needs to be set up as early as possible.
    // GSAP detection and data sending can happen on DOMContentLoaded or later.
    detectJQueryAnimations(); // Setup jQuery wrapper immediately.

    // Delay GSAP detection and data sending until DOM is ready,
    // allowing more GSAP animations to be potentially registered.
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        detectGSAPAnimations(); // Try to detect GSAP animations that might have already run
        collectAndSendAnimationData();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            detectGSAPAnimations(); // Detect GSAP animations again
            collectAndSendAnimationData();
        });
    }

})( window, document, typeof jQuery !== 'undefined' ? jQuery : null );
