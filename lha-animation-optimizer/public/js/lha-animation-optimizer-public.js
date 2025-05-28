(function( window, document, $ ) { // $ is passed but jQuery availability is still checked for operations
	'use strict';

	/**
	 * LHA Animation Optimizer Main Player Script
	 * This script is responsible for:
	 * 1. Restoring original jQuery/GSAP functions if they were shunted.
	 * 2. Processing any animations queued by the shunt script.
	 * 3. Lazy loading animations (both CSS and JavaScript-driven) using IntersectionObserver.
	 * 4. If animation data is cached (from preloaded data or lhaPluginData):
	 *    - Preparing elements by transferring cached animation details to their `dataset` attributes.
	 *    - Playing these cached animations (jQuery, GSAP, CSS) when elements become visible.
	 */

	// --- 1. Data Sourcing & Settings Initialization ---
	const defaultPluginSettings = {
		lazyLoadAnimations: true,
		intersectionObserverThreshold: 0.1,
		ajax_url: '', // Not actively used by player but part of settings structure
		animationTriggerClass: 'lha-animate-now',
        debugMode: false // Default debugMode for player itself
	};

    // Prioritize preloaded data from shunt, then lhaPluginData, then defaults.
    let lhaSettings = { 
        ...defaultPluginSettings, 
        ...(window.lhaPluginData && window.lhaPluginData.settings), // Fallback to lhaPluginData first
        ...(window.lhaPreloadedSettings) // Preloaded settings take highest precedence
    };
	let lhaCachedAnimations = window.lhaPreloadedAnimations || (window.lhaPluginData && window.lhaPluginData.cachedAnimations) || [];

    // Clean up global preloaded variables
    try {
        if (window.lhaPreloadedAnimations) delete window.lhaPreloadedAnimations;
        if (window.lhaPreloadedSettings) delete window.lhaPreloadedSettings;
    } catch (e) {
        // console.warn("LHA Player: Could not delete preloaded globals.", e);
    }
    
    // --- Debug Logging Utility ---
    function debugLog(...args) {
        if (lhaSettings.debugMode) {
            console.log('LHA Player:', ...args);
        }
    }

    // Initial log indicating script load and data source.
    if (window.lhaPreloadedSettings || window.lhaPreloadedAnimations) {
        debugLog("Script loaded. Initializing with PRELOADED data (shunt active). Preloaded Settings:", window.lhaPreloadedSettings, "Preloaded Animations Count:", (window.lhaPreloadedAnimations || []).length);
    } else if (window.lhaPluginData) {
        debugLog("Script loaded. Initializing with LHA_PLUGIN_DATA (no shunt or shunt failed). Settings:", window.lhaPluginData.settings, "Animations Count:", (window.lhaPluginData.cachedAnimations || []).length);
    } else {
        debugLog("Script loaded. No preloaded data or lhaPluginData found. Using defaults.");
    }
    debugLog("Effective initial settings:", lhaSettings, "Effective cached animations count:", lhaCachedAnimations.length);


	// --- 2. Restore Original Animation Functions ---
	function restoreOriginalAnimationFunctions() {
        debugLog("Phase 2: Attempting to restore original animation functions...");
        let restoredJQuery = false;
        let restoredGSAP = false;

        if (window.LHA_Originals) {
            // jQuery
            if (typeof window.jQuery === 'function' && typeof window.jQuery.fn === 'object' && window.jQuery.fn.lhaShuntWrapped && window.LHA_Originals.jquery) {
                debugLog("Restoring jQuery methods...");
                for (const methodName in window.LHA_Originals.jquery) {
                    if (Object.hasOwnProperty.call(window.LHA_Originals.jquery, methodName)) {
                        window.jQuery.fn[methodName] = window.LHA_Originals.jquery[methodName];
                        debugLog("Restored jQuery method:", methodName);
                        restoredJQuery = true;
                    }
                }
                try { delete window.jQuery.fn.lhaShuntWrapped; } catch(e) { /* ignore */ }
            } else {
                debugLog("jQuery or its shunt wrappers/originals not found for restoration (this is normal if jQuery not used or shunt inactive).");
            }

            // GSAP
            if (typeof window.gsap === 'object' && window.gsap.lhaShuntWrapped && window.LHA_Originals.gsap) {
                debugLog("Restoring GSAP methods...");
                for (const methodName in window.LHA_Originals.gsap) {
                    if (Object.hasOwnProperty.call(window.LHA_Originals.gsap, methodName)) {
                        window.gsap[methodName] = window.LHA_Originals.gsap[methodName];
                        debugLog("Restored GSAP method:", methodName);
                        restoredGSAP = true;
                    }
                }
                 try { delete window.gsap.lhaShuntWrapped; } catch(e) { /* ignore */ }
            } else {
                 debugLog("GSAP or its shunt wrappers/originals not found for restoration (normal if GSAP not used or shunt inactive).");
            }
        } else {
            debugLog("window.LHA_Originals not found. No functions to restore (normal if shunt was not active).");
        }
        debugLog("Phase 2: Original function restoration finished.", "jQuery restored:", restoredJQuery, "GSAP restored:", restoredGSAP);
	}

	// --- 3. Process Early Animation Queue ---
	function processEarlyAnimationQueue() {
        debugLog("Phase 3: Processing early animation queue...");
        if (window.lhaEarlyAnimationQueue && window.lhaEarlyAnimationQueue.length > 0) {
            debugLog("Processing early animation queue. Items:", window.lhaEarlyAnimationQueue.length);
            let queuedCall;
            while (queuedCall = window.lhaEarlyAnimationQueue.shift()) { // Process and empty the queue
                debugLog("Replaying queued call:", queuedCall);
                try {
                    if (queuedCall.type === 'jquery' && window.LHA_Originals && window.LHA_Originals.jquery && window.LHA_Originals.jquery[queuedCall.method]) {
                        debugLog("Replaying jQuery method:", queuedCall.method, "on target:", queuedCall.target, "with args:", queuedCall.args);
                        window.LHA_Originals.jquery[queuedCall.method].apply(queuedCall.target, queuedCall.args);
                    } else if (queuedCall.type === 'gsap' && window.LHA_Originals && window.LHA_Originals.gsap && window.LHA_Originals.gsap[queuedCall.method]) {
                        debugLog("Replaying GSAP method:", queuedCall.method, "with args:", queuedCall.args);
                        window.LHA_Originals.gsap[queuedCall.method].apply(null, queuedCall.args);
                    } else {
                        debugLog("Could not replay queued call - original method not found or library missing:", queuedCall);
                    }
                } catch (e) {
                    debugLog("Error replaying queued animation call:", queuedCall, e);
                }
            }
            debugLog("Early animation queue processed.");
        } else {
            debugLog("No early animation queue to process or queue already processed.");
        }
        // Clean up queue and originals store after processing
        try {
            if(window.lhaEarlyAnimationQueue) delete window.lhaEarlyAnimationQueue;
            debugLog("Early animation queue deleted from window.");
            if(window.LHA_Originals) delete window.LHA_Originals; 
            debugLog("LHA_Originals deleted from window.");
        } catch(e) { debugLog("Minor error during queue/originals cleanup:", e); }
	}

	/**
	 * Initializes elements based on cached animation data.
	 */
	function initializeCachedAnimations(animations) {
		if (!animations || !Array.isArray(animations)) {
			debugLog("initializeCachedAnimations called with invalid or no animations data.");
			return;
		}
		debugLog("Initializing cached animations setup. Number of animation objects:", animations.length);
		animations.forEach((animation, index) => {
			if (!animation.selector || !animation.type) {
				debugLog(`Skipping cached animation at index ${index} due to missing selector or type.`, animation);
				return;
			}
			try {
				const elements = document.querySelectorAll(animation.selector);
				if (elements.length === 0) {
					debugLog(`No elements found for cached animation selector: "${animation.selector}" (type: ${animation.type}).`);
					return;
				}
				elements.forEach(element => {
					element.classList.add('lha-animation-target');
					for (const key in animation) {
						if (Object.hasOwnProperty.call(animation, key)) {
							let datasetKey = key;
							if (key === 'id' || key === 'selector') continue; 
							if (typeof animation[key] === 'object' && animation[key] !== null) {
								try {
									element.dataset[datasetKey] = JSON.stringify(animation[key]);
								} catch (e) {
									debugLog(`Could not stringify object for dataset key "${datasetKey}":`, animation[key], e);
								}
							} else {
								element.dataset[datasetKey] = animation[key];
							}
						}
					}
                    debugLog("Prepared element for animation:", element, animation.type, element.dataset);
				});
			} catch (e) {
				debugLog(`Error processing cached animation for selector "${animation.selector}":`, e);
			}
		});
	}

	/**
	 * Initializes lazy loading of animations.
	 */
	function initLazyLoadAnimations() {
		if (!lhaSettings.lazyLoadAnimations) {
			debugLog('Lazy loading of animations is disabled in settings.');
			return;
		}
		const animationTargets = document.querySelectorAll('.lha-animation-target');
		if (!animationTargets.length) {
			debugLog('No elements found with class .lha-animation-target to observe.');
			return;
		}
		let thresholdValue = lhaSettings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			debugLog('Invalid intersectionObserverThreshold, defaulting to 0.1.');
			thresholdValue = 0.1;
		}
		if (!('IntersectionObserver' in window)) {
			debugLog('IntersectionObserver not supported. Activating all animations directly.');
			animationTargets.forEach(target => playAnimation(target));
			return;
		}
		const observerOptions = { root: null, rootMargin: '0px', threshold: thresholdValue };
		const animationObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					playAnimation(entry.target); 
					observer.unobserve(entry.target);
				}
			});
		}, observerOptions);
		animationTargets.forEach(target => animationObserver.observe(target));
        debugLog("IntersectionObserver initialized for", animationTargets.length, "targets.");
	}

	/**
	 * Plays the animation for a given element.
	 */
	function playAnimation(element) {
		const configuredTriggerClass = element.dataset.triggerClass || lhaSettings.animationTriggerClass;
		element.classList.add(configuredTriggerClass);
		const animationType = element.dataset.animationType;
        debugLog("Attempting to play animation for:", element, "Type:", animationType || "CSS (default)");

		try {
			if (animationType === 'jquery-animate') {
				const properties = element.dataset.jqueryProperties ? JSON.parse(element.dataset.jqueryProperties) : null;
				const duration = element.dataset.jqueryDuration ? parseInt(element.dataset.jqueryDuration, 10) : 400;
                const easing = element.dataset.jqueryEasing || 'swing';
				debugLog("Applying jQuery .animate() to:", element, { properties, duration, easing });
				if (properties && typeof $ !== 'undefined') $(element).animate(properties, duration, easing);
				else if (typeof $ === 'undefined') console.warn("LHA Player: jQuery ($) is not defined.");

			} else if (animationType && animationType.startsWith('jquery-') && animationType !== 'jquery-animate') {
                const method = animationType.substring('jquery-'.length); 
                if (typeof $ !== 'undefined' && typeof $.fn[method] === 'function') {
                    const durationArg = element.dataset.jqueryDuration;
                    const duration = durationArg ? (isNaN(parseInt(durationArg, 10)) ? durationArg : parseInt(durationArg, 10)) : 400;
                    const easing = element.dataset.jqueryEasing || 'swing';
                    if (method === 'fadeTo') {
                        const opacity = element.dataset.jqueryTargetOpacity ? parseFloat(element.dataset.jqueryTargetOpacity) : 1;
                        debugLog(`Applying jQuery .${method}() to:`, element, { duration, opacity, easing });
                        $(element)[method](duration, opacity, easing);
                    } else {
                        debugLog(`Applying jQuery .${method}() to:`, element, { duration, easing });
                        $(element)[method](duration, easing);
                    }
                } else if (typeof $ === 'undefined') console.warn(`LHA Player: jQuery ($) is not defined for ${animationType}.`);
                else console.warn(`LHA Player: jQuery method .${method}() not found for ${animationType}.`);

			} else if (animationType === 'gsap-tween') {
				const toVars = element.dataset.gsapToVars ? JSON.parse(element.dataset.gsapToVars) : null;
				const duration = element.dataset.gsapDuration ? parseFloat(element.dataset.gsapDuration) : 1;
                const staggerStr = element.dataset.gsapStagger;
                const stagger = staggerStr ? (staggerStr.startsWith('{') ? JSON.parse(staggerStr) : parseFloat(staggerStr)) : undefined;
				debugLog("Applying GSAP .to() to:", element, { toVars, duration, stagger });
				if (toVars && typeof gsap !== 'undefined') {
                    let animVars = { ...toVars, duration: duration };
                    if (stagger !== undefined) animVars.stagger = stagger;
					gsap.to(element, animVars);
				} else if (typeof gsap === 'undefined') console.warn("LHA Player: GSAP is not defined.");

			} else if (animationType === 'gsap-fromto') {
                const fromVars = element.dataset.gsapFromVars ? JSON.parse(element.dataset.gsapFromVars) : null;
				const toVars = element.dataset.gsapToVars ? JSON.parse(element.dataset.gsapToVars) : null;
				const duration = element.dataset.gsapDuration ? parseFloat(element.dataset.gsapDuration) : 1;
                const staggerStr = element.dataset.gsapStagger;
                const stagger = staggerStr ? (staggerStr.startsWith('{') ? JSON.parse(staggerStr) : parseFloat(staggerStr)) : undefined;
                debugLog("Applying GSAP .fromTo() to:", element, { fromVars, toVars, duration, stagger });
                if (fromVars && toVars && typeof gsap !== 'undefined') {
                    let animVars = { ...toVars, duration: duration };
                    if (stagger !== undefined) animVars.stagger = stagger;
                    gsap.fromTo(element, fromVars, animVars);
                } else if (typeof gsap === 'undefined') console.warn("LHA Player: GSAP is not defined.");
            
            } else if (animationType === 'css-animation') {
                debugLog("Applying cached CSS animation styles to:", element, element.dataset);
                if(element.dataset.cssAnimationName) element.style.animationName = element.dataset.cssAnimationName;
                if(element.dataset.cssDuration) element.style.animationDuration = element.dataset.cssDuration;
                if(element.dataset.cssTimingFunction) element.style.animationTimingFunction = element.dataset.cssTimingFunction;
                if(element.dataset.cssDelay) element.style.animationDelay = element.dataset.cssDelay;
                if(element.dataset.cssIterationCount) element.style.animationIterationCount = element.dataset.cssIterationCount;
                if(element.dataset.cssDirection) element.style.animationDirection = element.dataset.cssDirection;
                if(element.dataset.cssFillMode) element.style.animationFillMode = element.dataset.cssFillMode;

            } else if (animationType === 'css-transition') {
                debugLog("Expecting CSS transition for:", element, "on properties:", element.dataset.cssTransitionProperty, "triggered by class:", configuredTriggerClass);
            
            } else {
				debugLog("Generic CSS animation target. Triggered by class:", configuredTriggerClass, "on element:", element);
			}
		} catch (e) {
			debugLog("Error playing animation for element:", element, "Type:", animationType, e);
            // Fallback: ensure the trigger class is still there for basic CSS animations
            if(!element.classList.contains(configuredTriggerClass)) {
                element.classList.add(configuredTriggerClass);
            }
		}
	}

	/**
	 * Main initialization function for the player script.
	 */
	function init() {
		debugLog("Main player init sequence started. Debug mode:", lhaSettings.debugMode);

        // Step 1: Restore original shunted functions (jQuery, GSAP)
        restoreOriginalAnimationFunctions();

        // Step 2: Process any animations that were called by user scripts
        // before this main player script loaded and had a chance to restore originals.
        processEarlyAnimationQueue();

		// Step 3: If cached animations data is available (from preloaded or lhaPluginData),
        // prepare the target DOM elements by adding .lha-animation-target and dataset attributes.
		if (lhaCachedAnimations && lhaCachedAnimations.length > 0) {
			initializeCachedAnimations(lhaCachedAnimations);
		} else {
            debugLog("Phase 4: No cached animations to initialize or lhaCachedAnimations is empty.");
        }

		// Step 4: Initialize lazy loading for all `.lha-animation-target` elements.
        // This will observe elements prepared by initializeCachedAnimations and any others manually tagged.
		initLazyLoadAnimations();
        debugLog("Main player init sequence completed.");
	}

	// Execute the initialization function once the DOM is ready, or immediately if already ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})( window, document, typeof jQuery !== 'undefined' ? jQuery : null );
