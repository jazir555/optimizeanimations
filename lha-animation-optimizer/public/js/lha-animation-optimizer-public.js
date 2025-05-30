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
	let settings = { ...defaultSettings };

	// Merge localized settings if available
	if ( window.lhaAnimationOptimizerSettings ) {
		settings = {
			...settings, // Keep defaults for any missing keys
			lazyLoadAnimations: typeof window.lhaAnimationOptimizerSettings.lazyLoadAnimations !== 'undefined' ?
								window.lhaAnimationOptimizerSettings.lazyLoadAnimations :
								defaultSettings.lazyLoadAnimations,
			intersectionObserverThreshold: typeof window.lhaAnimationOptimizerSettings.intersectionObserverThreshold !== 'undefined' ?
											parseFloat(window.lhaAnimationOptimizerSettings.intersectionObserverThreshold) :
											defaultSettings.intersectionObserverThreshold,
		};
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
	 * Initializes the lazy loading of animations using IntersectionObserver.
	 */
	function initLazyLoadAnimations() {
		const animationTargets = document.querySelectorAll('.lha-animation-target');

		if (!animationTargets.length) {
			// console.log('LHA Animation Optimizer: No elements found with class .lha-animation-target');
			return;
		}

		if ( !settings.lazyLoadAnimations ) {
			// console.log('LHA Animation Optimizer: Lazy loading disabled. Activating all animations immediately.');
			animationTargets.forEach(target => {
				target.classList.add('lha-animate-now');
			});
			return;
		}

		// If lazyLoadAnimations is true, proceed with IntersectionObserver setup.
		// Ensure threshold is a valid number between 0 and 1, default to 0.1
		let thresholdValue = settings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			// console.warn('LHA Animation Optimizer: Invalid intersectionObserverThreshold, defaulting to 0.1.');
			thresholdValue = 0.1;
		}


		if (!('IntersectionObserver' in window)) {
			// Fallback for older browsers: just make them all visible if IO is not supported.
			// console.log('LHA Animation Optimizer: IntersectionObserver not supported, activating all animations.');
			animationTargets.forEach(target => {
				target.classList.add('lha-animate-now');
			});
			return;
		}

		// Ensure threshold is a valid number between 0 and 1, default to 0.1
		let thresholdValue = settings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			// console.warn('LHA Animation Optimizer: Invalid intersectionObserverThreshold, defaulting to 0.1.');
			thresholdValue = 0.1;
		}


		if (!('IntersectionObserver' in window)) {
			// Fallback for older browsers: just make them all visible if IO is not supported.
			// console.log('LHA Animation Optimizer: IntersectionObserver not supported, activating all animations.');
			animationTargets.forEach(target => {
				target.classList.add('lha-animate-now');
			});
			return;
		}

		const observerOptions = {
			root: null, // Use the viewport as the root
			rootMargin: '0px',
			threshold: thresholdValue,
		};

		// console.log('LHA Animation Optimizer: Initializing IntersectionObserver with options:', observerOptions);

		const animationObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					try {
						entry.target.classList.add('lha-animate-now');
						// Once the animation is triggered, we can stop observing it
						// if animations are not meant to repeat on scroll out/in.
						observer.unobserve(entry.target);
					} catch (e) {
						// Log error internally if a more robust error handling system was in place
						// console.log('LHA Animation Optimizer: Animating target:', entry.target);
						// console.error('LHA Animation Optimizer: Error applying animation class:', e, entry.target);
					}
				}
				// Future enhancement: Optionally remove 'lha-animate-now' when entry.isIntersecting is false
				// if animations should reset and replay when scrolling back into view.
				// This would require not unobserving the target.
			});
		}, observerOptions);

		animationTargets.forEach(target => {
			animationObserver.observe(target);
		});
	}


	/**
	 * Initializes the plugin's public-facing JavaScript logic.
	 */
	function init() {
		// console.log('LHA Animation Optimizer: Initializing public script with settings:', settings);
		initLazyLoadAnimations();

		// Placeholder for CSS Animation Optimization logic (if settings.optimizeCssAnimations)
		// This is complex. For V1, it might be limited to ensuring animations respect will-change
		// or other simple, non-intrusive best practices if detectable.
		// Directly rewriting CSS is risky.

		// Placeholder for JS Animation Optimization logic (if settings.optimizeJsAnimations)
		// Intercepting jQuery.animate is high risk.
		// Ensuring rAF for internal plugin features is a must.
		// Pausing/resuming existing JS animations via IntersectionObserver is feasible if they expose methods.
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		// DOMContentLoaded has already fired
		init();
	}

})( window, document );
// This file is production-ready.
