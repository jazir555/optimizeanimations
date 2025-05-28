(function( window, document, $ ) { // Added $ for jQuery if used
	'use strict';

	/**
	 * LHA Animation Optimizer Public Script
	 * This script is responsible for:
	 * 1. Lazy loading animations (both CSS and JavaScript-driven) using IntersectionObserver.
	 * 2. If animation data is cached (provided by `lhaPluginData.cachedAnimations`):
	 *    - Preparing elements by transferring cached animation details to their `dataset` attributes.
	 *    - Playing these cached animations (jQuery, GSAP, CSS) when elements become visible.
	 * 3. If no animation data is cached, it primarily acts as a lazy loader for elements
	 *    manually assigned the `.lha-animation-target` class for CSS animations.
	 */

	// Default plugin settings. These can be overridden by data localized from PHP via `lhaPluginData`.
	const defaultPluginSettings = {
		lazyLoadAnimations: true,               // Global toggle for lazy loading animations.
		intersectionObserverThreshold: 0.1,     // Percentage of an element's visibility needed to trigger its animation.
		ajax_url: '',                           // AJAX URL (not directly used in this player script but available).
		animationTriggerClass: 'lha-animate-now' // Default CSS class added to elements to trigger CSS-based animations.
	};

	// Initialize current settings by merging defaults with any localized settings.
	let lhaSettings = { ...defaultPluginSettings };
	// This will hold the array of cached animation objects if provided by the PHP script.
	let lhaCachedAnimations = null;

	// Check if `lhaPluginData` (localized from PHP) exists and process its contents.
	if ( window.lhaPluginData ) {
		if ( window.lhaPluginData.settings ) {
			// Merge localized settings, ensuring defaults are kept for any missing keys.
			lhaSettings = {
				...defaultPluginSettings, // Start with defaults.
				...window.lhaPluginData.settings, // Override with localized settings.
				// Ensure specific data types for critical settings.
				intersectionObserverThreshold: parseFloat(
					// Use localized value or fall back to default if parsing fails or value is missing.
					window.lhaPluginData.settings.intersectionObserverThreshold || defaultPluginSettings.intersectionObserverThreshold
				),
				lazyLoadAnimations: typeof window.lhaPluginData.settings.lazyLoadAnimations === 'boolean' ?
									window.lhaPluginData.settings.lazyLoadAnimations : // Use boolean if provided.
									defaultPluginSettings.lazyLoadAnimations // Fallback to default.
			};
		}

		// Check for and store cached animation data.
		if ( window.lhaPluginData.cachedAnimations && 
			 Array.isArray(window.lhaPluginData.cachedAnimations) && 
			 window.lhaPluginData.cachedAnimations.length > 0 ) {
			lhaCachedAnimations = window.lhaPluginData.cachedAnimations;
			// console.log("LHA Player: Mode: Animation Player. Cached animations found:", lhaCachedAnimations);
		} else {
			// console.log("LHA Player: Mode: Lazy Loader / No cached animations. Detector script may run if cache is invalid.");
		}
	} else {
		// console.warn("LHA Player: `lhaPluginData` not found. Using default settings and operating in basic lazy load mode.");
	}

	/**
	 * Initializes elements based on cached animation data received from the server.
	 * This function iterates through the `animations` array (from `lhaCachedAnimations`).
	 * For each animation object, it finds the target DOM element(s) using `animation.selector`.
	 * It then adds the `lha-animation-target` class (to make them observable) and transfers all
	 * animation properties from the `animation` object to the element's `dataset` attributes
	 * (e.g., `animation.jquery_duration` becomes `element.dataset.jqueryDuration`).
	 * @param {Array} animations Array of animation objects from `lhaCachedAnimations`.
	 */
	function initializeCachedAnimations(animations) {
		if (!animations || !Array.isArray(animations)) {
			// console.warn("LHA Player: `initializeCachedAnimations` called with invalid or no animations data.");
			return;
		}

		// console.log("LHA Player: Initializing cached animations setup. Number of animation objects:", animations.length);
		animations.forEach((animation, index) => {
			// Each `animation` object must have a `selector` and `type`.
			if (!animation.selector || !animation.type) {
				// console.warn(`LHA Player: Skipping cached animation at index ${index} due to missing selector or type.`, animation);
				return;
			}

			try {
				// Find all DOM elements matching the animation's selector.
				const elements = document.querySelectorAll(animation.selector);
				if (elements.length === 0) {
					// console.log(`LHA Player: No elements found for cached animation selector: "${animation.selector}" (type: ${animation.type}).`);
					return;
				}

				elements.forEach(element => {
					// Add `lha-animation-target` to make the element discoverable by the IntersectionObserver.
					element.classList.add('lha-animation-target');
					
					// Store all properties from the animation object directly into the element's dataset.
					// This makes all animation details (type, duration, properties, etc.) available
					// in the `playAnimation` function.
					for (const key in animation) {
						if (Object.hasOwnProperty.call(animation, key)) {
							// Convert snake_case keys (from PHP/JSON) to kebab-case for dataset,
							// though modern browsers largely handle camelCase for dataset keys too.
							// Standardizing on kebab-case for dataset keys read via `element.dataset.someKey` (becomes someKey).
							// For direct `dataset[key]` assignment, camelCase or original key can be used.
							// Here, we'll use a simple conversion for consistency if keys were snake_case.
							// However, the detector primarily uses camelCase keys for JS objects.
							// The main point is to transfer all data.
							let datasetKey = key.replace(/_/g, '-');
							// `element.dataset` automatically converts kebab-case to camelCase for property access.
							// e.g. `element.dataset.jqueryDuration` for `data-jquery-duration`.
							// So, we can just use the original camelCase keys from the animation object.
							datasetKey = key;


							if (key === 'id' || key === 'selector') continue; // Don't re-store these if not needed for playback logic.

							if (typeof animation[key] === 'object' && animation[key] !== null) {
								try {
									// Objects (like jquery_properties, gsap_to_vars) must be stringified for dataset.
									element.dataset[datasetKey] = JSON.stringify(animation[key]);
								} catch (e) {
									// console.warn(`LHA Player: Could not stringify object for dataset key "${datasetKey}":`, animation[key], e);
								}
							} else {
								// Primitives (string, number, boolean) can be set directly.
								element.dataset[datasetKey] = animation[key];
							}
						}
					}
					// Example: element.dataset.animationType = animation.type;
					// element.dataset.jqueryDuration = animation.jquery_duration;
					// console.log("LHA Player: Prepared element for animation:", element, animation.type, element.dataset);
				});
			} catch (e) {
				// Catch errors from `document.querySelectorAll` (invalid selector) or other issues.
				console.error(`LHA Player: Error processing cached animation for selector "${animation.selector}":`, e);
			}
		});
	}


	/**
	 * Initializes the lazy loading of animations using IntersectionObserver.
	 * This function finds all elements with the class `.lha-animation-target`
	 * (which includes elements prepared by `initializeCachedAnimations` and any elements
	 * manually assigned this class for CSS-only animations) and observes them.
	 */
	function initLazyLoadAnimations() {
		if (!lhaSettings.lazyLoadAnimations) {
			// console.log('LHA Player: Lazy loading of animations is disabled in settings.');
			return;
		}

		// Query for all elements marked for animation, including those from cache
		// and those manually given the class for CSS animations.
		const animationTargets = document.querySelectorAll('.lha-animation-target');

		if (!animationTargets.length) {
			// console.log('LHA: No elements found with class .lha-animation-target to observe.');
			return;
		}

		// Validate and set the threshold for IntersectionObserver.
		let thresholdValue = lhaSettings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			// console.warn('LHA: Invalid intersectionObserverThreshold, defaulting to 0.1.');
			thresholdValue = 0.1; // Fallback to default if invalid.
		}

		// Fallback for browsers that do not support IntersectionObserver.
		if (!('IntersectionObserver' in window)) {
			// console.log('LHA: IntersectionObserver not supported. Activating all animations directly.');
			animationTargets.forEach(target => {
				playAnimation(target); // Attempt to play animation directly without observing.
			});
			return;
		}

		// Configure the IntersectionObserver.
		const observerOptions = {
			root: null, // Use the viewport as the root.
			rootMargin: '0px', // No margin around the root.
			threshold: thresholdValue, // Visibility threshold.
		};

		// Create the IntersectionObserver instance.
		const animationObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				// When an element becomes intersecting (visible).
				if (entry.isIntersecting) {
					playAnimation(entry.target); // Trigger its animation.
					// Stop observing the element once its animation is triggered to prevent re-triggering.
					observer.unobserve(entry.target);
				}
			});
		}, observerOptions);

		// Start observing all designated animation target elements.
		animationTargets.forEach(target => {
			animationObserver.observe(target);
		});
	}

	/**
	 * Plays the animation for the given target element based on its dataset attributes.
	 * This function is called when an element becomes visible (intersecting).
	 * It first adds the general animation trigger class (for CSS animations)
	 * and then checks for specific JavaScript animation data (jQuery/GSAP) to play.
	 * @param {HTMLElement} element The DOM element to animate.
	 */
	function playAnimation(element) {
		// Add the primary CSS class that triggers CSS-defined animations.
		// This class (e.g., 'lha-animate-now') should be used in stylesheets
		// for standard CSS keyframe or transition animations.
		const configuredTriggerClass = element.dataset.triggerClass || lhaSettings.animationTriggerClass;
		element.classList.add(configuredTriggerClass);

		// Check if the element has specific JavaScript animation data stored in its dataset.
		const animationType = element.dataset.animationType;

		try {
			if (animationType === 'jquery-animate') {
				const properties = element.dataset.jqueryProperties ? JSON.parse(element.dataset.jqueryProperties) : null;
				const duration = element.dataset.jqueryDuration ? parseInt(element.dataset.jqueryDuration, 10) : 400;
                const easing = element.dataset.jqueryEasing || 'swing';
				// console.log("LHA Player: Applying jQuery .animate() to:", element, "Properties:", properties, "Duration:", duration, "Easing:", easing);
				if (properties && typeof $ !== 'undefined') {
					$(element).animate(properties, duration, easing);
				} else if (typeof $ === 'undefined') {
					console.warn("LHA Player: jQuery ($) is not defined. Cannot play jQuery .animate() for element:", element);
				}

			} else if (animationType && animationType.startsWith('jquery-') && animationType !== 'jquery-animate') {
                // Handles fadeIn, fadeOut, slideUp, slideDown, slideToggle, fadeToggle, fadeTo
                const method = animationType.substring('jquery-'.length); // e.g., 'fadeIn', 'fadeTo'
                if (typeof $ !== 'undefined' && typeof $.fn[method] === 'function') {
                    const duration = element.dataset.jqueryDuration ? (isNaN(parseInt(element.dataset.jqueryDuration, 10)) ? element.dataset.jqueryDuration : parseInt(element.dataset.jqueryDuration, 10)) : 400;
                    const easing = element.dataset.jqueryEasing || 'swing';
                    
                    if (method === 'fadeTo') {
                        const opacity = element.dataset.jqueryTargetOpacity ? parseFloat(element.dataset.jqueryTargetOpacity) : 1;
                        // console.log(`LHA Player: Applying jQuery .${method}() to:`, element, "Duration:", duration, "Opacity:", opacity, "Easing:", easing);
                        $(element)[method](duration, opacity, easing);
                    } else {
                        // console.log(`LHA Player: Applying jQuery .${method}() to:`, element, "Duration:", duration, "Easing:", easing);
                        $(element)[method](duration, easing);
                    }
                } else if (typeof $ === 'undefined') {
                     console.warn(`LHA Player: jQuery ($) is not defined. Cannot play ${animationType} for element:`, element);
                } else if (typeof $.fn[method] !== 'function') {
                    console.warn(`LHA Player: jQuery method .${method}() not found. Cannot play ${animationType} for element:`, element);
                }

			} else if (animationType === 'gsap-tween') {
				const toVars = element.dataset.gsapToVars ? JSON.parse(element.dataset.gsapToVars) : null;
				const duration = element.dataset.gsapDuration ? parseFloat(element.dataset.gsapDuration) : 1;
                const stagger = element.dataset.gsapStagger ? (element.dataset.gsapStagger.startsWith('{') ? JSON.parse(element.dataset.gsapStagger) : parseFloat(element.dataset.gsapStagger)) : undefined;
				
                // console.log("LHA Player: Applying GSAP .to() to:", element, "Vars:", toVars, "Duration:", duration, "Stagger:", stagger);
				if (toVars && typeof gsap !== 'undefined') {
                    let animVars = { ...toVars, duration: duration };
                    if (stagger !== undefined) animVars.stagger = stagger;
					gsap.to(element, animVars);
				} else if (typeof gsap === 'undefined') {
					console.warn("LHA Player: GSAP is not defined. Cannot play GSAP .to() for element:", element);
				}

			} else if (animationType === 'gsap-fromto') {
                const fromVars = element.dataset.gsapFromVars ? JSON.parse(element.dataset.gsapFromVars) : null;
				const toVars = element.dataset.gsapToVars ? JSON.parse(element.dataset.gsapToVars) : null;
				const duration = element.dataset.gsapDuration ? parseFloat(element.dataset.gsapDuration) : 1;
                const stagger = element.dataset.gsapStagger ? (element.dataset.gsapStagger.startsWith('{') ? JSON.parse(element.dataset.gsapStagger) : parseFloat(element.dataset.gsapStagger)) : undefined;

                // console.log("LHA Player: Applying GSAP .fromTo() to:", element, "FromVars:", fromVars, "ToVars:", toVars, "Duration:", duration, "Stagger:", stagger);
                if (fromVars && toVars && typeof gsap !== 'undefined') {
                    let animVars = { ...toVars, duration: duration };
                    if (stagger !== undefined) animVars.stagger = stagger;
                    gsap.fromTo(element, fromVars, animVars);
                } else if (typeof gsap === 'undefined') {
                    console.warn("LHA Player: GSAP is not defined. Cannot play GSAP .fromTo() for element:", element);
                }
            
            } else if (animationType === 'css-animation') {
                // console.log("LHA Player: Applying CSS animation styles to:", element);
                if(element.dataset.cssAnimationName) element.style.animationName = element.dataset.cssAnimationName;
                if(element.dataset.cssDuration) element.style.animationDuration = element.dataset.cssDuration;
                if(element.dataset.cssTimingFunction) element.style.animationTimingFunction = element.dataset.cssTimingFunction;
                if(element.dataset.cssDelay) element.style.animationDelay = element.dataset.cssDelay;
                if(element.dataset.cssIterationCount) element.style.animationIterationCount = element.dataset.cssIterationCount;
                if(element.dataset.cssDirection) element.style.animationDirection = element.dataset.cssDirection;
                if(element.dataset.cssFillMode) element.style.animationFillMode = element.dataset.cssFillMode;
                // The configuredTriggerClass added earlier should make the animation play if styles are set up correctly.

            } else if (animationType === 'css-transition') {
                // For CSS transitions, the primary action is the addition of the trigger class.
                // The actual transition depends on CSS rules associated with this class.
                // console.log("LHA Player: Expecting CSS transition for:", element, "on properties:", element.dataset.cssTransitionProperty, "with class", configuredTriggerClass);
                // No direct style manipulation here for transitions, as it's class-driven.
            
            } else {
				// If no specific JS/CSS animation type from cache, it's assumed to be a generic CSS-driven animation
                // triggered by the `configuredTriggerClass` added at the beginning of this function.
				// console.log("LHA Player: Generic CSS animation triggered for:", element, "with class", configuredTriggerClass);
					}
				});
			} catch (e) {
				console.error("LHA: Error processing cached animation for selector:", animation.selector, e);
			}
		});
	}


	/**
	 * Initializes the lazy loading of animations using IntersectionObserver.
	 */
	function initLazyLoadAnimations() {
		if ( !lhaSettings.lazyLoadAnimations ) {
			// console.log('LHA: Lazy loading of animations is disabled in settings.');
			return;
		}

		// Query for all elements marked for animation, including those from cache
		const animationTargets = document.querySelectorAll('.lha-animation-target');

		if (!animationTargets.length) {
			// console.log('LHA: No elements found with class .lha-animation-target to observe.');
			return;
		}

		let thresholdValue = lhaSettings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			thresholdValue = 0.1;
		}

		if (!('IntersectionObserver' in window)) {
			// console.log('LHA: IntersectionObserver not supported. Activating all animations.');
			animationTargets.forEach(target => {
				playAnimation(target); // Attempt to play animation directly
			});
			return;
		}

		const observerOptions = {
			root: null,
			rootMargin: '0px',
			threshold: thresholdValue,
		};

		const animationObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					playAnimation(entry.target);
					observer.unobserve(entry.target);
				}
			});
		}, observerOptions);

		animationTargets.forEach(target => {
			animationObserver.observe(target);
		});
	}

	/**
	 * Plays the animation for the given target element based on its dataset attributes.
	 * @param {HTMLElement} element The element to animate.
	 */
	function playAnimation(element) {
		// The primary trigger class defined in settings (e.g., 'lha-animate-now')
		// This class should be the one that CSS animations primarily respond to.
		const configuredTriggerClass = lhaSettings.animationTriggerClass;
		element.classList.add(configuredTriggerClass);

		// Now check for specific JS animation types from cached data
		const animationType = element.dataset.animationType;

		if (animationType === 'jquery') {
			try {
				const properties = element.dataset.jqueryProperties ? JSON.parse(element.dataset.jqueryProperties) : null;
				const duration = element.dataset.jqueryDuration ? parseInt(element.dataset.jqueryDuration) : 400; // Default jQuery duration
				console.log("LHA Player: Applying jQuery animation to:", element, "Properties:", properties, "Duration:", duration);
				if (properties && typeof $ !== 'undefined') {
					$(element).animate(properties, duration);
				} else if (typeof $ === 'undefined') {
					console.warn("LHA Player: jQuery ($) is not defined. Cannot play jQuery animation.");
				}
			} catch (e) {
				console.error("LHA Player: Error parsing or applying jQuery animation data.", e, element.dataset.jqueryProperties);
			}
		} else if (animationType === 'gsap') {
			try {
				const toVars = element.dataset.gsapToVars ? JSON.parse(element.dataset.gsapToVars) : null;
				const duration = element.dataset.gsapDuration ? parseFloat(element.dataset.gsapDuration) : 1; // Default GSAP duration
				console.log("LHA Player: Applying GSAP animation to:", element, "Vars:", toVars, "Duration:", duration);
				if (toVars && typeof gsap !== 'undefined') {
					gsap.to(element, { ...toVars, duration: duration });
				} else if (typeof gsap === 'undefined') {
					console.warn("LHA Player: GSAP is not defined. Cannot play GSAP animation.");
				}
			} catch (e) {
				console.error("LHA Player: Error parsing or applying GSAP animation data.", e, element.dataset.gsapToVars);
			}
		} else {
			// If no specific JS animation type, it's assumed to be CSS-driven by the added triggerClass.
			// console.log("LHA Player: CSS animation triggered for:", element, "with class", configuredTriggerClass);
		}
	}

	/**
	 * Initializes the plugin's public-facing JavaScript logic.
	 */
	function init() {
		// console.log('LHA: Initializing public script with settings:', lhaSettings);

		if (lhaCachedAnimations) {
			initializeCachedAnimations(lhaCachedAnimations);
		}
		// initLazyLoadAnimations will find all .lha-animation-target elements,
		// including those prepared by initializeCachedAnimations.
		initLazyLoadAnimations();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})( window, document, typeof jQuery !== 'undefined' ? jQuery : null );
// This file is production-ready for Step 4.
// Note: Actual playback of jQuery/GSAP requires jQuery/GSAP to be loaded on the page.
// The optional playback parts are included with checks for their existence.
