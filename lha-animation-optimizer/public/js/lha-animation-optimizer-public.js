(function( window, document, $ ) { // Added $ for jQuery if used
	'use strict';

	/**
	 * LHA Animation Optimizer Public Script
	 * Handles lazy loading of animations and playback of cached JS animations.
	 */

	// Default plugin settings. These can be overridden by data localized from PHP.
	const defaultPluginSettings = {
		lazyLoadAnimations: true,               // Whether to lazy load animations.
		intersectionObserverThreshold: 0.1,     // Percentage of element visibility to trigger animation.
		ajax_url: '',                           // URL for AJAX requests (not currently used by this script but can be).
		animationTriggerClass: 'lha-animate-now' // Default CSS class added to trigger animations.
	};

	// Initialize current settings with defaults.
	let lhaSettings = { ...defaultPluginSettings };
	// Holds cached animation data if provided by PHP.
	let lhaCachedAnimations = null;

	// Check if lhaPluginData is localized from PHP and merge settings.
	if ( window.lhaPluginData ) {
		if ( window.lhaPluginData.settings ) {
			// Merge localized settings with defaults, ensuring all keys are present.
			lhaSettings = {
				...defaultPluginSettings,
				...window.lhaPluginData.settings, // Localized settings override defaults.
				// Ensure specific types if they are critical (e.g., threshold as float).
				intersectionObserverThreshold: parseFloat(
					window.lhaPluginData.settings.intersectionObserverThreshold || defaultPluginSettings.intersectionObserverThreshold
				),
				lazyLoadAnimations: typeof window.lhaPluginData.settings.lazyLoadAnimations === 'boolean' ?
									window.lhaPluginData.settings.lazyLoadAnimations :
									defaultPluginSettings.lazyLoadAnimations
			};
		}

		// Check for cached animation data.
		if ( window.lhaPluginData.cachedAnimations && Array.isArray(window.lhaPluginData.cachedAnimations) && window.lhaPluginData.cachedAnimations.length > 0 ) {
			lhaCachedAnimations = window.lhaPluginData.cachedAnimations;
			// console.log("LHA Player: Animation player mode - cached animations found:", lhaCachedAnimations);
		} else {
			// console.log("LHA Player: Animation discovery mode or no valid cached animations provided.");
		}
	} else {
		// console.warn("LHA Player: lhaPluginData not found. Using default settings.");
	}

	/**
	 * Initializes animations based on cached data received from the server.
	 * This function prepares elements that were previously detected by `lha-animation-detector.js`
	 * by adding the `.lha-animation-target` class and animation details to their dataset.
	 * Marks elements for lazy loading by adding 'lha-animation-target' and stores animation details in dataset attributes.
	 * @param {Array} animations Array of animation objects from the cache.
	 */
	function initializeCachedAnimations(animations) {
		if (!animations || !Array.isArray(animations)) {
			// console.warn("LHA Player: initializeCachedAnimations called with invalid or no animations data.");
			return;
		}

		// console.log("LHA Player: Initializing cached animations setup...");
		animations.forEach(animation => {
			// Essential data for an animation object.
			if (!animation.selector || !animation.type) {
				// console.warn("LHA Player: Skipping cached animation due to missing selector or type", animation);
				return;
			}

			try {
				// Find all elements matching the cached selector.
				const elements = document.querySelectorAll(animation.selector);
				if (elements.length === 0) {
					// console.log("LHA Player: No elements found for cached animation selector:", animation.selector);
					return;
				}

				elements.forEach(element => {
					// Mark the element as a target for the IntersectionObserver.
					element.classList.add('lha-animation-target');
					// Store the type of animation (e.g., 'jquery', 'gsap', or potentially 'css').
					element.dataset.animationType = animation.type;
					// Store the original trigger class if provided, though current player uses a global one.
					// element.dataset.originalTriggerClass = animation.triggerClass; 

					// Store type-specific animation parameters as JSON strings in dataset attributes.
					if (animation.type === 'jquery') {
						if (animation.jquery_properties) {
							element.dataset.jqueryProperties = JSON.stringify(animation.jquery_properties);
						}
						if (animation.jquery_duration) {
							element.dataset.jqueryDuration = animation.jquery_duration;
						}
						// Optional: Store easing if needed for jQuery playback.
						// if (animation.jquery_easing) {
						//     element.dataset.jqueryEasing = animation.jquery_easing;
						// }
						// console.log("LHA Player: Marked jQuery animation for", element, animation);
					} else if (animation.type === 'gsap') {
						if (animation.gsap_to_vars) {
							element.dataset.gsapToVars = JSON.stringify(animation.gsap_to_vars);
						}
						if (animation.gsap_duration) {
							element.dataset.gsapDuration = animation.gsap_duration;
						}
						// console.log("LHA Player: Marked GSAP animation for", element, animation);
					} else {
						// This path would be for animations detected as 'css' or other types,
						// currently, the detector focuses on jQuery and GSAP.
						// console.log("LHA Player: Marked CSS/other animation type for", element, animation);
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
