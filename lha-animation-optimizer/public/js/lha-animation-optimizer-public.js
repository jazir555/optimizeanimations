(function( window, document ) {
	'use strict';

	/**
	 * LHA Animation Optimizer Public Script
	 *
	 * This file contains the JavaScript for the public-facing functionality of the plugin.
	 * It focuses on optimizing animations, primarily through lazy loading via IntersectionObserver.
	 */

	// Default settings, to be potentially overridden by PHP via wp_localize_script
	const defaultSettings = {
		lazyLoadAnimations: true,
		intersectionObserverThreshold: 0.1,
		// optimizeCssAnimations: true, // Placeholder
		// optimizeJsAnimations: true,  // Placeholder
		// debounceScrollAnimations: true, // Placeholder
		// debounceDelay: 100, // Placeholder
	};

	// Start with defaults
	// Make settings accessible to the API for modification in preview mode.
	let LHA_Player_Settings = { ...defaultSettings };

	function updateSettings(newSettings) {
		if (newSettings) {
			LHA_Player_Settings = {
				...LHA_Player_Settings,
				...newSettings
			};
			// console.log('LHA Player: Settings updated by API:', LHA_Player_Settings);
		}
	}

	// Merge localized settings if available on normal page load
	// For admin preview, these will be set by PHP localization for the public script
	if ( window.lhaAnimationOptimizerSettings ) {
		updateSettings(window.lhaAnimationOptimizerSettings);
	}
	
	// --- LHA Animation Data Sourcing Logic ---

	function getAnimationData(callback) {
		console.log('LHA Player: Attempting to source animation data.');

		if (window.lhaExternalAnimationDataUrl && typeof window.lhaExternalAnimationDataUrl === 'string' && window.lhaExternalAnimationDataUrl.trim() !== '') {
			console.log('LHA Player: External data URL was present (' + window.lhaExternalAnimationDataUrl + '). Polling for window.lhaExternalAnimationData...');
			let pollingStartTime = Date.now();
			const maxPollDuration = 5000; // 5 seconds
			const pollIntervalTime = 100; // 100ms

			const pollInterval = setInterval(function() {
				if (window.lhaExternalAnimationData) {
					console.log('LHA Player: External animation data (window.lhaExternalAnimationData) found after polling.');
					clearInterval(pollInterval);
					callback(window.lhaExternalAnimationData);
				} else if (Date.now() - pollingStartTime > maxPollDuration) {
					console.warn('LHA Player: Timeout waiting for window.lhaExternalAnimationData.');
					clearInterval(pollInterval);
					// Fallback to preloaded after timeout
					if (window.lhaPreloadedAnimations) {
						console.log('LHA Player: Using window.lhaPreloadedAnimations as fallback after external data timeout.');
						callback(window.lhaPreloadedAnimations);
					} else {
						console.error('LHA Player: No animation data found after external data timeout and no preloaded data.');
						callback(null); // Or callback([])
					}
				}
			}, pollIntervalTime);
		} else {
			console.log('LHA Player: No external data URL. Checking for window.lhaPreloadedAnimations.');
			if (window.lhaPreloadedAnimations) {
				console.log('LHA Player: Using window.lhaPreloadedAnimations.');
				callback(window.lhaPreloadedAnimations);
			} else {
				console.warn('LHA Player: No preloaded animation data found.');
				callback(null); // Or callback([])
			}
		}
	}

	function cleanupGlobalAnimationVars() {
		if (window.lhaExternalAnimationData) {
			try { delete window.lhaExternalAnimationData; } catch (e) { window.lhaExternalAnimationData = undefined; }
		}
		if (window.lhaPreloadedAnimations) {
			try { delete window.lhaPreloadedAnimations; } catch (e) { window.lhaPreloadedAnimations = undefined; }
		}
		if (window.lhaExternalAnimationDataUrl) {
			try { delete window.lhaExternalAnimationDataUrl; } catch (e) { window.lhaExternalAnimationDataUrl = undefined; }
		}
		// Optional: cleanup flags from shunt
		// if (window.lhaExternalDataLoaded) { try { delete window.lhaExternalDataLoaded; } catch (e) { window.lhaExternalDataLoaded = undefined; } }
		// if (window.lhaExternalDataLoadFailed) { try { delete window.lhaExternalDataLoadFailed; } catch (e) { window.lhaExternalDataLoadFailed = undefined; } }
		console.log('LHA Player: Global animation variables cleaned up.');
	}

	/**
	 * Debounce function to limit the rate at which a function can fire.
	 * @param {Function} func The function to debounce.
	 * @param {number} wait The time to wait before firing the function.
	 * @param {boolean} immediate If true, fire the function on the leading edge, otherwise on the trailing edge.
	 * @returns {Function} The debounced function.
	 */
	function debounce(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}

	/**
	 * Initializes the lazy loading of animations using IntersectionObserver and applies GSAP animations.
	 * @param {Array} animations - Array of animation objects.
	 */
	function initLazyLoadAnimations(animations) {
		// Use LHA_Player_Settings instead of local settings
		if (!LHA_Player_Settings.lazyLoadAnimations) {
			console.log('LHA Player: Lazy loading of animations is disabled. Applying all GSAP animations directly.');
			if (!animations || animations.length === 0) {
				// console.log('LHA Player: No animations to apply directly (lazyload disabled).');
				return;
			}
			animations.forEach(animObject => {
				if (animObject.type === 'gsap' && animObject.selector && animObject.animation) {
					const targets = document.querySelectorAll(animObject.selector);
					if (targets.length > 0) {
						if (typeof gsap !== 'undefined' && gsap.to) {
							console.log('LHA Player: Applying GSAP animation (lazyload disabled) to', animObject.selector, 'with properties:', animObject.animation);
							gsap.to(targets, animObject.animation);
						} else {
							console.warn('LHA Player: GSAP is not available. Cannot apply animation to', animObject.selector);
						}
					}
				}
				// Handle other types if necessary, or non-GSAP animations when lazyload disabled
			});
			return;
		}

		if (!('IntersectionObserver' in window)) {
			console.warn('LHA Player: IntersectionObserver not supported. Applying animations directly.');
			// Fallback: Apply all animations directly if IO is not supported but lazy loading is enabled.
			if (!animations || animations.length === 0) {
				// console.log('LHA Player: No animations to apply directly (IO not supported).');
				return;
			}
			animations.forEach(animObject => {
				if (animObject.type === 'gsap' && animObject.selector && animObject.animation) {
					const targets = document.querySelectorAll(animObject.selector);
					if (targets.length > 0) {
						if (typeof gsap !== 'undefined' && gsap.to) {
							console.log('LHA Player: Applying GSAP animation (IO not supported) to', animObject.selector, 'with properties:', animObject.animation);
							gsap.to(targets, animObject.animation);
						} else {
							console.warn('LHA Player: GSAP is not available. Cannot apply animation to', animObject.selector);
						}
					}
				}
			});
			return;
		}

		let thresholdValue = LHA_Player_Settings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			console.warn('LHA Player: Invalid intersectionObserverThreshold, defaulting to 0.1.');
			thresholdValue = 0.1; // Use the corrected default from LHA_Player_Settings or a hardcoded one
		}

		const observerOptions = {
			root: null,
			rootMargin: '0px',
			threshold: thresholdValue,
		};

		animations.forEach(animObject => {
			if (animObject.type !== 'gsap' || !animObject.selector || !animObject.animation) {
				// console.log('LHA Player: Skipping non-GSAP or invalid animation object:', animObject);
				return;
			}

			const targetElements = document.querySelectorAll(animObject.selector);

			if (!targetElements.length) {
				// console.log('LHA Player: No elements found for selector:', animObject.selector);
				return;
			}
			
			// console.log('LHA Player: Setting up IntersectionObserver for selector:', animObject.selector, 'with options:', observerOptions);

			const animationObserver = new IntersectionObserver((entries, observer) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						const currentTarget = entry.target; // GSAP will apply to this specific target or all under selector based on stagger
						try {
							if (typeof gsap !== 'undefined' && gsap.to) {
								// If animObject.animation.stagger is present, GSAP will handle it for the `targetElements`
								// when it was initially set up. Here we animate the specific `entry.target`.
								// For a stagger effect on a group, you might need to trigger on a parent
								// or apply to all `targetElements` once the first one is intersecting.
								// For simplicity here, we trigger animation for the specific intersecting element.
								// If stagger is meant for the whole group identified by `animObject.selector`,
								// the GSAP call should target `targetElements` (plural).
								
								console.log('LHA Player: Applying GSAP animation to', animObject.selector, '(triggered by an element) with properties:', animObject.animation);
								// Pass the whole animObject.animation which includes stagger
								gsap.to(targetElements, animObject.animation); 

								// We only want to trigger the animation for the group once.
								// So, unobserve all elements related to this animObject.selector.
								targetElements.forEach(el => observer.unobserve(el));

							} else {
								console.warn('LHA Player: GSAP is not available. Cannot apply animation to', currentTarget);
								// Fallback to class-based animation if GSAP is missing?
								// currentTarget.classList.add('lha-animate-now'); 
								targetElements.forEach(el => observer.unobserve(el)); // Still unobserve
							}
						} catch (e) {
							console.error('LHA Player: Error applying GSAP animation:', e, currentTarget, animObject.animation);
							targetElements.forEach(el => observer.unobserve(el)); // Unobserve on error
						}
					}
				});
			}, observerOptions);

			targetElements.forEach(element => {
				animationObserver.observe(element);
			});
		});
	}


	/**
	 * Initializes the plugin's public-facing JavaScript logic.
	 */
	function initializeAnimations(animationData) {
		if (!animationData || (Array.isArray(animationData) && animationData.length === 0)) {
			console.warn('LHA Player: No animation data provided or data is empty. Animations will not be initialized.');
			cleanupGlobalAnimationVars(); // Clean up even if no data
			return;
		}

		console.log('LHA Player: Initializing animations with data:', animationData);
		
		// Pass the animationData to the lazy load initializer.
		initLazyLoadAnimations(animationData); 

		// Placeholder for CSS Animation Optimization logic (if settings.optimizeCssAnimations)
		// ...

		// Placeholder for JS Animation Optimization logic (if settings.optimizeJsAnimations)
		// ...

		// After data is processed and used (or processing is initiated):
		cleanupGlobalAnimationVars();
	}

	// Main execution starts here
	function main() {
		// Use LHA_Player_Settings
		console.log('LHA Animation Optimizer: Main execution started. Settings:', LHA_Player_Settings);
		getAnimationData(initializeAnimations);
	}
	
	// Standard execution for public-facing pages
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', main);
	} else {
		main();
	}

	// Expose API for admin preview or other external calls
	window.LHA_Animation_Optimizer_Public_API = {
		initializeAnimations: initializeAnimations,
		// Expose settings and update function if admin needs to tweak them for preview
		// For instance, to ensure lazyLoadAnimations is false for preview.
		updateSettings: updateSettings,
		getCurrentSettings: function() { return { ...LHA_Player_Settings }; }
	};

})( window, document );
// This file is production-ready.
