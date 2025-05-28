(function( window, document, $ ) { // $ is passed but we'll check window.jQuery
    'use strict';

    // Localized settings from PHP (ajaxUrl, saveNonce, saveAction, feature flags, debugMode).
    var lhaDetectorSettings = window.lhaDetectorSettings || {
        // Provide defaults if not localized (e.g., if script loaded without WordPress context)
        ajaxUrl: '',
        saveNonce: '',
        saveAction: '',
        enableAdvancedJQueryDetection: true, // Default to true
        enableAdvancedGSAPDetection: true,   // Default to true
        enableMutationObserver: true,        // Default to true
        debugMode: false                     // Default to false
    };

    // Global array to store all detected animation objects before sending to server.
    let detectedAnimations = [];
    // Set to store processed GSAP tween instances to prevent duplicates.
    let processedGSAPTweens = new Set();
    // Map for MutationObserver deduplication: maps element to timestamp of last record.
    let recentlyRecordedMO = new Map();
    // Hold the MutationObserver instance.
    let mutationObserverInstance = null;

    /**
     * Helper function for debug logging.
     * Only logs if lhaDetectorSettings.debugMode is true.
     */
    function debugLog(...args) {
        if (lhaDetectorSettings.debugMode) {
            console.log('LHA Detector:', ...args);
        }
    }

    /**
     * Generates a reasonably specific CSS selector for a given DOM element.
     * Prioritizes ID, then tag name with unique class combinations.
     * Includes a basic :nth-of-type refinement if similar siblings are found.
     * Note: This is a best-effort approach and may not be perfectly unique in all complex DOMs.
     * @param {Element} el The DOM element.
     * @param {number} [depth=0] Current recursion depth for parent selector generation.
     * @returns {string|null} A CSS selector string or null if element is invalid.
     */
    function generateSelector(el, depth = 0) {
        if (!el || !(el instanceof Element)) {
            debugLog('Invalid element passed to generateSelector:', el);
            return null;
        }

        // Priority 1: Element ID (should be unique)
        if (el.id) {
            let id = el.id;
            if (/^[0-9]/.test(id)) { 
                id = '\\3' + id.charAt(0) + ' ' + id.substring(1);
            }
            return '#' + id.replace(/([!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        }

        let selector = el.tagName.toLowerCase();
        if (el.className && typeof el.className === 'string') {
            const classes = el.className.trim().split(/\s+/).filter(Boolean).join('.');
            if (classes) {
                selector += '.' + classes;
            }
        }
        
        try {
            const parent = el.parentNode;
            if (parent && parent.nodeType === Node.ELEMENT_NODE) {
                const siblings = Array.from(parent.children);
                const sameTagAndClassSiblings = siblings.filter(sibling => 
                    sibling.tagName === el.tagName && sibling.className === el.className
                );
                
                if (sameTagAndClassSiblings.length > 1) {
                    const typeSiblings = siblings.filter(s => s.tagName === el.tagName);
                    const nthOfTypeIndex = typeSiblings.indexOf(el);
                    if (nthOfTypeIndex !== -1) {
                        selector += ':nth-of-type(' + (nthOfTypeIndex + 1) + ')';
                    }
                } else if (depth < 3 && document.querySelectorAll(selector).length > 1 && parent.tagName.toLowerCase() !== 'body' && parent !== document) {
                    const parentSelector = generateSelector(parent, depth + 1); 
                    if (parentSelector) {
                        selector = parentSelector + ' > ' + selector;
                    }
                }
            }
        } catch (e) {
            debugLog("Error during specific selector generation part:", e, el);
        }
        return selector;
    }

    /**
     * Detects jQuery animations by wrapping jQuery.fn.animate and other animation methods.
     */
    function detectJQueryAnimationsWrapperSetup() {
        debugLog('Attempting to set up jQuery animation detectors...');
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn === 'undefined') {
            debugLog('jQuery or jQuery.fn not found. Skipping jQuery detection wrapper setup.');
            return;
        }

        function parseJQueryArgs(args) {
            let duration = 400; 
            let easing = 'swing'; 
            if (args.length > 0) {
                if (typeof args[0] === 'number' || args[0] === 'fast' || args[0] === 'slow') {
                    duration = args[0];
                    if (typeof args[1] === 'string') easing = args[1];
                } else if (typeof args[0] === 'string') {
                    if (args[0] === 'fast' || args[0] === 'slow') duration = args[0];
                    else easing = args[0];
                    if (typeof args[1] === 'function') { /* callback not stored */ }
                } else if (typeof args[0] === 'object') {
                    duration = args[0].duration || duration;
                    easing = args[0].easing || easing;
                }
            }
            return { duration, easing };
        }

        if (typeof window.jQuery.fn.animate === 'function') {
            const originalJQueryAnimate = window.jQuery.fn.animate;
            window.jQuery.fn.animate = function() {
                const elements = this; 
                const properties = arguments[0];
                let parsedArgs = {};
                if (typeof arguments[1] === 'number' || arguments[1] === 'fast' || arguments[1] === 'slow') {
                    parsedArgs.duration = arguments[1];
                    if (typeof arguments[2] === 'string') parsedArgs.easing = arguments[2];
                } else if (typeof arguments[1] === 'string') {
                    parsedArgs.easing = arguments[1];
                     if (arguments[1] === 'fast' || arguments[1] === 'slow') parsedArgs.duration = arguments[1];
                } else if (typeof arguments[1] === 'object') {
                    parsedArgs.duration = arguments[1].duration;
                    parsedArgs.easing = arguments[1].easing;
                }
                const duration = parsedArgs.duration || 400;
                const easing = parsedArgs.easing || 'swing';

                if (elements && elements.length) {
                    elements.each(function() { 
                        const element = this;
                        const selector = generateSelector(element);
                        if (selector) {
                            try {
                                const animDetail = {
                                    id: 'jquery-animate-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                                    selector: selector,
                                    type: 'jquery-animate',
                                    jquery_properties: JSON.parse(JSON.stringify(properties || {})),
                                    jquery_duration: duration,
                                    jquery_easing: easing,
                                    triggerClass: 'lha-animate-now'
                                };
                                detectedAnimations.push(animDetail);
                                debugLog("jQuery .animate() detected:", animDetail, "on element:", element);
                            } catch (e) {
                                debugLog("Error processing jQuery .animate() data for selector:", selector, e, properties);
                            }
                        }
                    });
                }
                return originalJQueryAnimate.apply(this, arguments);
            };
            debugLog("jQuery .animate() wrapper setup complete.");
        } else {
            debugLog("jQuery .animate() not found. Skipping .animate() wrapper.");
        }

        if (lhaDetectorSettings.enableAdvancedJQueryDetection) {
            debugLog("Advanced jQuery detection enabled. Setting up wrappers for fadeIn, fadeOut, etc.");
            function wrapJQuerySimpleAnimationMethod(methodName) {
                if (typeof window.jQuery.fn[methodName] !== 'function') {
                    debugLog(`jQuery method .${methodName}() not found. Skipping wrapper.`);
                    return;
                }
                const originalMethod = window.jQuery.fn[methodName];
                window.jQuery.fn[methodName] = function() {
                    const args = Array.from(arguments);
                    const parsed = parseJQueryArgs(args); 
                    this.each(function() {
                        const element = this;
                        const selector = generateSelector(element);
                        if (selector) {
                            try {
                                const animDetail = {
                                    id: 'jquery-' + methodName + '-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                                    selector: selector,
                                    type: 'jquery-' + methodName,
                                    jquery_duration: parsed.duration,
                                    jquery_easing: parsed.easing,
                                    triggerClass: 'lha-animate-now'
                                };
                                detectedAnimations.push(animDetail);
                                debugLog(`jQuery .${methodName}() detected:`, animDetail, "on element:", element);
                            } catch (e) {
                                debugLog(`Error processing jQuery .${methodName}() data for selector:`, selector, e);
                            }
                        }
                    });
                    return originalMethod.apply(this, arguments);
                };
                debugLog(`jQuery .${methodName}() wrapper setup complete.`);
            }

            function wrapJQueryFadeToMethod() {
                const methodName = 'fadeTo';
                if (typeof window.jQuery.fn[methodName] !== 'function') {
                     debugLog(`jQuery method .${methodName}() not found. Skipping wrapper.`);
                    return;
                }
                const originalMethod = window.jQuery.fn[methodName];
                window.jQuery.fn[methodName] = function() { 
                    const duration = arguments[0]; 
                    const opacity = arguments[1];  
                    let easing = 'swing';
                    if (typeof arguments[2] === 'string') easing = arguments[2];
                    this.each(function() {
                        const element = this;
                        const selector = generateSelector(element);
                        if (selector) {
                            try {
                                const animDetail = {
                                    id: 'jquery-' + methodName + '-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                                    selector: selector,
                                    type: 'jquery-' + methodName,
                                    jquery_duration: duration,
                                    jquery_target_opacity: opacity,
                                    jquery_easing: easing,
                                    triggerClass: 'lha-animate-now'
                                };
                                detectedAnimations.push(animDetail);
                                debugLog(`jQuery .${methodName}() detected:`, animDetail, "on element:", element);
                            } catch (e) {
                                debugLog(`Error processing jQuery .${methodName}() data for selector:`, selector, e);
                            }
                        }
                    });
                    return originalMethod.apply(this, arguments);
                };
                debugLog(`jQuery .${methodName}() wrapper setup complete.`);
            }
            const simpleMethods = ['fadeIn', 'fadeOut', 'slideDown', 'slideUp', 'slideToggle', 'fadeToggle'];
            simpleMethods.forEach(methodName => wrapJQuerySimpleAnimationMethod(methodName));
            wrapJQueryFadeToMethod();
        } else {
            debugLog("Advanced jQuery detection disabled.");
        }
    }

    /**
     * Detects GSAP animations by inspecting the GSAP global timeline.
     */
    function detectGSAPAnimationsOnDemand() {
        if (typeof window.gsap === 'undefined') {
            debugLog('GSAP not found. Skipping GSAP on-demand detection.');
            return;
        }
        debugLog('Performing GSAP on-demand detection from globalTimeline...');
        try {
            const allGlobalTweens = window.gsap.globalTimeline.getChildren(true, true, true);
            if (allGlobalTweens && allGlobalTweens.length) {
                allGlobalTweens.forEach(tween => processGSAPTween(tween, 'globalTimeline'));
            } else {
                debugLog('No tweens found on GSAP globalTimeline during on-demand scan.');
            }
        } catch (e) {
            debugLog('Error while trying to inspect GSAP globalTimeline:', e);
        }
    }

    /**
     * Processes a single GSAP tween instance.
     */
    function processGSAPTween(tween, context = 'unknown') {
        if (!tween || processedGSAPTweens.has(tween) || typeof tween.targets !== 'function') return;
        const targets = tween.targets();
        targets.forEach(targetElement => {
            if (targetElement instanceof Element) {
                const selector = generateSelector(targetElement);
                if (selector) {
                    try {
                        let type = 'gsap-tween';
                        let gsap_from_vars = null;
                        let gsap_to_vars = {};
                        try {
                            gsap_to_vars = JSON.parse(JSON.stringify(tween.vars || {}));
                        } catch(jsonError) {
                            debugLog("Could not stringify GSAP tween.vars, attempting manual copy for selector:", selector, jsonError, tween.vars);
                            const knownProps = ['opacity', 'x', 'y', 'scale', 'rotation', 'autoAlpha', 'width', 'height', 'left', 'top', 'backgroundColor', 'color'];
                            for(const prop in tween.vars) {
                                if(knownProps.includes(prop) && (typeof tween.vars[prop] === 'string' || typeof tween.vars[prop] === 'number' || typeof tween.vars[prop] === 'boolean')) {
                                    gsap_to_vars[prop] = tween.vars[prop];
                                }
                            }
                        }

                        if (gsap_to_vars.startAt) {
                            type = 'gsap-fromto';
                            try {
                                gsap_from_vars = JSON.parse(JSON.stringify(gsap_to_vars.startAt));
                            } catch (jsonError) {
                                debugLog("Could not stringify GSAP tween.vars.startAt, attempting manual copy for selector:", selector, jsonError, gsap_to_vars.startAt);
                                gsap_from_vars = {};
                                 const knownProps = ['opacity', 'x', 'y', 'scale', 'rotation', 'autoAlpha', 'width', 'height', 'left', 'top', 'backgroundColor', 'color'];
                                for(const prop in gsap_to_vars.startAt) {
                                     if(knownProps.includes(prop) && (typeof gsap_to_vars.startAt[prop] === 'string' || typeof gsap_to_vars.startAt[prop] === 'number' || typeof gsap_to_vars.startAt[prop] === 'boolean')) {
                                        gsap_from_vars[prop] = gsap_to_vars.startAt[prop];
                                    }
                                }
                            }
                            delete gsap_to_vars.startAt; 
                        }
                        
                        const propsToRemove = ['onComplete', 'onUpdate', 'onStart', 'onRepeat', 'onReverseComplete', 'onInterrupt', 'callbackScope', 'stagger', 'lazy', 'immediateRender', 'overwrite'];
                        propsToRemove.forEach(prop => delete gsap_to_vars[prop]);
                        if (gsap_from_vars) {
                             propsToRemove.forEach(prop => delete gsap_from_vars[prop]);
                        }

                        const animationData = {
                            id: `gsap-${context}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                            selector: selector,
                            type: type,
                            gsap_to_vars: gsap_to_vars, 
                            gsap_duration: tween.duration(),
                            triggerClass: 'lha-animate-now'
                        };
                        if (gsap_from_vars) animationData.gsap_from_vars = gsap_from_vars;
                        if (tween.vars && tween.vars.stagger) {
                            animationData.gsap_stagger = typeof tween.vars.stagger === 'object' ? 
                                JSON.parse(JSON.stringify(tween.vars.stagger)) : 
                                tween.vars.stagger;
                        }
                        detectedAnimations.push(animationData);
                        processedGSAPTweens.add(tween); 
                        debugLog(`GSAP animation (${type}) detected via ${context}:`, animationData, "on element:", targetElement);
                    } catch (e) {
                        debugLog(`Error processing GSAP animation data (context: ${context}) for selector:`, selector, e, tween.vars);
                    }
                }
            }
        });
    }
    
    /**
     * Sets up a wrapper for GSAP's Timeline.prototype.add.
     */
    function detectGSAPByWrappingTimelineAdd() {
        if (!lhaDetectorSettings.enableAdvancedGSAPDetection) {
            debugLog("Advanced GSAP detection (Timeline.add wrapper) disabled.");
            return;
        }
        debugLog("Attempting to set up GSAP Timeline.add wrapper...");
        if (typeof window.gsap === 'undefined' || typeof window.gsap.core === 'undefined' || typeof window.gsap.core.Timeline === 'undefined' || typeof window.gsap.core.Timeline.prototype.add !== 'function') {
            debugLog('GSAP Timeline.prototype.add not found. Skipping wrapper setup.');
            return;
        }
        const originalTimelineAdd = window.gsap.core.Timeline.prototype.add;
        window.gsap.core.Timeline.prototype.add = function(child, position) {
            debugLog("GSAP Timeline.add called. Child:", child);
            if (child instanceof window.gsap.core.Tween) processGSAPTween(child, 'timelineAdd');
            else if (child instanceof window.gsap.core.Timeline) debugLog('GSAP Timeline added to another timeline.');
            return originalTimelineAdd.apply(this, arguments);
        };
        debugLog('GSAP Timeline.prototype.add wrapper setup complete.');
    }

    /**
     * Collects and sends animation data.
     */
    function collectAndSendAnimationData() {
        if (!lhaDetectorSettings.ajaxUrl || !lhaDetectorSettings.saveNonce || !lhaDetectorSettings.saveAction) {
            debugLog('Missing required AJAX settings. Data cannot be sent.');
            return;
        }
        debugLog('Preparing to send animation data...');
        detectGSAPAnimationsOnDemand(); // Final scan before sending
        debugLog('Final detected animations to send:', detectedAnimations);
        if (detectedAnimations.length === 0) debugLog('No JavaScript or CSS animations detected to send.');
        if (mutationObserverInstance) {
            mutationObserverInstance.disconnect();
            debugLog('MutationObserver disconnected before sending data.');
        }
        const ajaxFunction = (typeof $ === 'function' && $.ajax) ? $.ajax : function(options) {
            fetch(options.url, {
                method: options.type,
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams(options.data).toString()
            }).then(response => {
                if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                return response.json();
            }).then(options.success).catch(options.error);
        };
        ajaxFunction({
            url: lhaDetectorSettings.ajaxUrl, type: 'POST',
            data: {
                action: lhaDetectorSettings.saveAction, 
                nonce: lhaDetectorSettings.saveNonce,
                detected_animations: JSON.stringify(detectedAnimations)
            },
            success: function(response) {
                if (response.success) debugLog('Animation data successfully sent and saved.', response.data || '');
                else debugLog('Server responded with an error.', response.data && response.data.message ? response.data.message : 'Unknown server error.');
            },
            error: function(jqXHR_or_Error, textStatus_or_unused, errorThrown_or_Error) {
                let errorMessage = 'AJAX request failed.';
                if (jqXHR_or_Error instanceof Error) errorMessage = jqXHR_or_Error.message;
                else if (typeof errorThrown_or_Error === 'string' && errorThrown_or_Error) errorMessage = errorThrown_or_Error;
                else if (typeof textStatus_or_unused === 'string' && textStatus_or_unused !== 'error') errorMessage = textStatus_or_unused;
                debugLog('Error sending animation data.', errorMessage);
            }
        });
    }

    detectJQueryAnimationsWrapperSetup();
    detectGSAPByWrappingTimelineAdd();
    if (lhaDetectorSettings.enableMutationObserver) {
        debugLog("MutationObserver detection enabled. Initializing observer.");
        initMutationObserver();
    } else {
        debugLog("MutationObserver detection disabled.");
    }

    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        collectAndSendAnimationData();
    } else {
        document.addEventListener('DOMContentLoaded', collectAndSendAnimationData);
    }

    /**
     * Initializes the MutationObserver.
     */
    function initMutationObserver() {
        if (typeof window.MutationObserver === 'undefined') {
            debugLog('MutationObserver not supported. Skipping CSS detection.');
            return;
        }
        mutationObserverInstance = new MutationObserver(handleMutations);
        try {
            mutationObserverInstance.observe(document.body, {
                childList: true, attributes: true, subtree: true,
                attributeOldValue: true, characterData: false
            });
            debugLog('MutationObserver initialized and observing document.body.');
        } catch (e) {
            debugLog('Error initializing MutationObserver.', e);
        }
    }

    /**
     * Handles DOM mutations.
     */
    function handleMutations(mutationsList, observer) {
        debugLog("MutationObserver: Handling", mutationsList.length, "mutations.");
        for (const mutation of mutationsList) {
            if (mutation.type === 'attributes') {
                const element = mutation.target;
                if (!(element instanceof Element)) continue;
                if (mutation.attributeName === 'class') {
                    const oldClasses = mutation.oldValue || '';
                    const newClasses = element.className;
                    if (oldClasses !== newClasses) {
                        debugLog("MutationObserver: Class change on", element, "Old:", oldClasses, "New:", newClasses);
                        processPotentialCSSEffect(element, 'classChange');
                    }
                } else if (mutation.attributeName === 'style') {
                     const oldStyle = mutation.oldValue || '';
                     const newStyle = element.getAttribute('style') || '';
                     if (oldStyle !== newStyle) {
                        debugLog("MutationObserver: Style change on", element, "Old:", oldStyle, "New:", newStyle);
                        processPotentialCSSEffect(element, 'styleChange');
                     }
                }
            } else if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        debugLog("MutationObserver: Node added", node);
                        processPotentialCSSEffect(node, 'nodeAdded');
                        Array.from(node.querySelectorAll('*')).forEach(child => processPotentialCSSEffect(child, 'nodeAddedDescendant'));
                    }
                });
            }
        }
    }
    
    const MO_DEDUPLICATION_TIME_MS = 150;

    /**
     * Processes an element for potential CSS effects.
     */
    function processPotentialCSSEffect(element, changeType) {
        if (!element || !(element instanceof Element) || typeof element.getComputedStyle !== 'function' && typeof window.getComputedStyle !== 'function') {
             debugLog("Invalid element or getComputedStyle not available for:", element);
             return;
        }
        const now = Date.now();
        const lastRecordedTime = recentlyRecordedMO.get(element);
        if (lastRecordedTime && (now - lastRecordedTime) < MO_DEDUPLICATION_TIME_MS) return;
        recentlyRecordedMO.set(element, now);
        if (recentlyRecordedMO.size > 100) {
            for (const [el, time] of recentlyRecordedMO.entries()) {
                if ((now - time) > MO_DEDUPLICATION_TIME_MS * 2) recentlyRecordedMO.delete(el);
            }
        }

        requestAnimationFrame(() => {
            try {
                if (!element.isConnected) {
                    debugLog('Element not connected, skipping style computation:', element);
                    return;
                }
                const styles = window.getComputedStyle(element);
                const animationName = styles.animationName;
                const transitionProperty = styles.transitionProperty;
                const selector = generateSelector(element);
                if (!selector) return;

                if (animationName && animationName !== 'none') {
                    const durationStr = styles.animationDuration;
                    const durationMs = parseFloat(durationStr) * (durationStr.includes('ms') ? 1 : 1000);
                    if (durationMs > 0) {
                        const animDetail = {
                            id: `css-anim-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                            selector: selector, type: 'css-animation',
                            css_animation_name: animationName, css_duration: durationStr,
                            css_timing_function: styles.animationTimingFunction, css_delay: styles.animationDelay,
                            css_iteration_count: styles.animationIterationCount, css_direction: styles.animationDirection,
                            css_fill_mode: styles.animationFillMode, triggerClass: 'lha-animate-now'
                        };
                        detectedAnimations.push(animDetail);
                        debugLog('CSS Animation detected:', animDetail);
                    }
                }
                
                if (transitionProperty && transitionProperty !== 'none' && transitionProperty !== 'all' && 
                    (changeType === 'styleChange' || changeType === 'classChange' || changeType === 'nodeAdded')) {
                    const durationStr = styles.transitionDuration;
                    const durationMs = parseFloat(durationStr) * (durationStr.includes('ms') ? 1 : 1000);
                    if (durationMs > 0) {
                        const transDetail = {
                            id: `css-trans-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                            selector: selector, type: 'css-transition',
                            css_transition_property: transitionProperty, css_duration: durationStr,
                            css_timing_function: styles.transitionTimingFunction, css_delay: styles.transitionDelay,
                            triggerClass: 'lha-animate-now'
                        };
                        detectedAnimations.push(transDetail);
                        debugLog('CSS Transition detected:', transDetail);
                    }
                }
            } catch (e) {
                debugLog('Error in processPotentialCSSEffect for element:', element, e);
            }
        });
    }
})( window, document, typeof jQuery !== 'undefined' ? jQuery : null );
