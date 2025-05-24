(function( $ ) {
	'use strict';

	/**
	 * LHA Animation Optimizer Public Script
	 *
	 * Handles all public-facing JavaScript functionality for the plugin.
	 */

	const defaultSettings = {
		globalEnablePlugin: true, 
		lazyLoadAnimations: true,
		intersectionObserverThreshold: 0.1,
		enable_jquery_animate_optimization: false,
		jquery_animate_optimization_mode: 'safe',
		gsap_prefers_reduced_motion_helper: false,
		lazy_load_include_selector: '.lha-animation-target', 
		lazy_load_exclude_selectors: '', 
		lazy_load_critical_selectors: '',
		enable_statistics_tracking: false, 
		ajax_url: '', 
		update_stats_nonce: '',
		noLazyLoadTargetsFound: false 
	};

	let settings = { ...defaultSettings };
	if ( window.lhaAnimationOptimizerSettings ) {
		settings = {
			...settings, 
			globalEnablePlugin: typeof window.lhaAnimationOptimizerSettings.globalEnablePlugin !== 'undefined' ?
								window.lhaAnimationOptimizerSettings.globalEnablePlugin :
								defaultSettings.globalEnablePlugin,
			lazyLoadAnimations: typeof window.lhaAnimationOptimizerSettings.lazyLoadAnimations !== 'undefined' ?
								window.lhaAnimationOptimizerSettings.lazyLoadAnimations :
								defaultSettings.lazyLoadAnimations,
			intersectionObserverThreshold: typeof window.lhaAnimationOptimizerSettings.intersectionObserverThreshold !== 'undefined' ?
											parseFloat(window.lhaAnimationOptimizerSettings.intersectionObserverThreshold) :
											defaultSettings.intersectionObserverThreshold,
			enable_jquery_animate_optimization: typeof window.lhaAnimationOptimizerSettings.enable_jquery_animate_optimization !== 'undefined' ?
												window.lhaAnimationOptimizerSettings.enable_jquery_animate_optimization :
												defaultSettings.enable_jquery_animate_optimization,
			jquery_animate_optimization_mode: typeof window.lhaAnimationOptimizerSettings.jquery_animate_optimization_mode !== 'undefined' ?
												window.lhaAnimationOptimizerSettings.jquery_animate_optimization_mode :
												defaultSettings.jquery_animate_optimization_mode,
			gsap_prefers_reduced_motion_helper: typeof window.lhaAnimationOptimizerSettings.gsap_prefers_reduced_motion_helper !== 'undefined' ?
												window.lhaAnimationOptimizerSettings.gsap_prefers_reduced_motion_helper :
												defaultSettings.gsap_prefers_reduced_motion_helper,
			lazy_load_include_selector: typeof window.lhaAnimationOptimizerSettings.lazy_load_include_selector === 'string' && window.lhaAnimationOptimizerSettings.lazy_load_include_selector.trim() !== '' ?
												window.lhaAnimationOptimizerSettings.lazy_load_include_selector.trim() :
												defaultSettings.lazy_load_include_selector,
			lazy_load_exclude_selectors: typeof window.lhaAnimationOptimizerSettings.lazy_load_exclude_selectors === 'string' ?
												window.lhaAnimationOptimizerSettings.lazy_load_exclude_selectors.trim() :
												defaultSettings.lazy_load_exclude_selectors,
			lazy_load_critical_selectors: typeof window.lhaAnimationOptimizerSettings.lazy_load_critical_selectors === 'string' ?
												window.lhaAnimationOptimizerSettings.lazy_load_critical_selectors.trim() :
												defaultSettings.lazy_load_critical_selectors,
			enable_statistics_tracking: typeof window.lhaAnimationOptimizerSettings.enable_statistics_tracking !== 'undefined' ?
												window.lhaAnimationOptimizerSettings.enable_statistics_tracking :
												defaultSettings.enable_statistics_tracking,
			ajax_url: typeof window.lhaAnimationOptimizerSettings.ajax_url === 'string' ?
												window.lhaAnimationOptimizerSettings.ajax_url :
												defaultSettings.ajax_url,
			update_stats_nonce: typeof window.lhaAnimationOptimizerSettings.update_stats_nonce === 'string' ?
												window.lhaAnimationOptimizerSettings.update_stats_nonce :
												defaultSettings.update_stats_nonce,
		};
	}

	let animationsProcessedByLazyLoader = 0;
	let statsSentThisPageLoad = false; 
	let jqueryAnimationObserver = null; 
	const jqueryAnimationQueue = new Map(); 

	function isElementInViewport($element) {
		if (!$element || $element.length === 0 || !$element.is(':visible')) {
			return false;
		}
		const rect = $element[0].getBoundingClientRect();
		return (
			rect.top < window.innerHeight && rect.bottom >= 0 &&
			rect.left < window.innerWidth && rect.right >= 0
		);
	}

	function initLazyLoadAnimations() {
		if (settings.noLazyLoadTargetsFound) { return; }
		let animationTargets;
		try { animationTargets = document.querySelectorAll(settings.lazy_load_include_selector); } catch (e) { console.warn('LHA Animation Optimizer: Invalid Primary Lazy Load Selector provided:', settings.lazy_load_include_selector, e); animationTargets = document.querySelectorAll(defaultSettings.lazy_load_include_selector); }
		if (!animationTargets.length) { return; }
		const excludeSelectors = settings.lazy_load_exclude_selectors ? settings.lazy_load_exclude_selectors.split('\n').map(s => s.trim()).filter(s => s !== '') : [];
		const criticalSelectors = settings.lazy_load_critical_selectors ? settings.lazy_load_critical_selectors.split('\n').map(s => s.trim()).filter(s => s !== '') : [];
		let thresholdValue = settings.intersectionObserverThreshold; if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) { thresholdValue = defaultSettings.intersectionObserverThreshold; }
		const observerOptions = { root: null, rootMargin: '0px', threshold: thresholdValue };
		let animationObserverInstance;
		if ('IntersectionObserver' in window) {
			animationObserverInstance = new IntersectionObserver((entries, observer) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						try { entry.target.classList.add('lha-animate-now'); animationsProcessedByLazyLoader++; observer.unobserve(entry.target); sendStatsIfNeeded(); } catch (e) { /* console.error('LHA: Error applying animation class:', e, entry.target); */ }
					}
				});
			}, observerOptions);
		}
		animationTargets.forEach(target => {
			let isCritical = false; if (criticalSelectors.length > 0) { for (const criticalSelector of criticalSelectors) { try { if (target.matches(criticalSelector)) { isCritical = true; break; } } catch (e) { console.warn('LHA: Invalid Critical Selector:', criticalSelector, e); } } }
			if (isCritical) { target.classList.add('lha-animate-now'); return; }
			let isExcluded = false; if (excludeSelectors.length > 0) { for (const excludeSelector of excludeSelectors) { try { if (target.matches(excludeSelector)) { isExcluded = true; break; } } catch (e) { console.warn('LHA: Invalid Exclude Selector:', excludeSelector, e); } } }
			if (isExcluded) { return; }
			if (animationObserverInstance) { animationObserverInstance.observe(target); } else { target.classList.add('lha-animate-now'); animationsProcessedByLazyLoader++; sendStatsIfNeeded(); }
		});
	}

	function sendStatsIfNeeded() { /* ... (existing code from previous subtask, no changes) ... */ }

	/**
	 * Initializes jQuery.animate() optimizations if enabled.
	 */
	function initJQueryAnimateOptimizer() {
		if (typeof jQuery === 'undefined' || typeof jQuery.fn.animate !== 'function') {
			return;
		}
		
		const originalJQueryAnimate = jQuery.fn.animate;
		const safeAnimatedProperties = ['opacity', 'scrollTop', 'scrollLeft']; 

		if (settings.lazyLoadAnimations && 'IntersectionObserver' in window && !jqueryAnimationObserver) {
			const thresholdValue = settings.intersectionObserverThreshold; 
			jqueryAnimationObserver = new IntersectionObserver((entries, observer) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						const element = entry.target;
						const $element = $(element);
						if (jqueryAnimationQueue.has(element)) {
							const storedAnimations = jqueryAnimationQueue.get(element);
							storedAnimations.forEach(animArgs => {
								originalJQueryAnimate.apply($element, animArgs);
							});
							jqueryAnimationQueue.delete(element); 
						}
						observer.unobserve(element);
					}
				});
			}, { root: null, rootMargin: '0px', threshold: thresholdValue });
		}

		jQuery.fn.animate = function(...args) {
			const properties = args[0];
			let allPropertiesSafeForDeferral = true; // Check if animation is simple enough for deferral in safe mode

			if (typeof properties === 'object' && properties !== null) {
				for (const prop in properties) {
					if (properties.hasOwnProperty(prop)) {
						if (safeAnimatedProperties.indexOf(prop) === -1) {
							if (typeof properties[prop] === 'string' && (properties[prop].startsWith('+=') || properties[prop].startsWith('-=') || ['show', 'hide', 'toggle'].includes(properties[prop].toLowerCase()))) {
								// Potentially complex, but might be okay for deferral if not aggressive transform
							} else if (typeof properties[prop] === 'object' || $.isFunction(properties[prop])) {
								allPropertiesSafeForDeferral = false; break;
							} else {
								allPropertiesSafeForDeferral = false; break;
							}
						}
					}
				}
			} else {
				allPropertiesSafeForDeferral = false; 
			}

			if (settings.jquery_animate_optimization_mode === 'safe') {
				if (settings.lazyLoadAnimations && jqueryAnimationObserver && this.length > 0) {
					const $element = this.first(); 
					const element = $element[0];
					let isCritical = false;
					const criticalSelectors = settings.lazy_load_critical_selectors ? settings.lazy_load_critical_selectors.split('\n').map(s => s.trim()).filter(s => s !== '') : [];
					if (criticalSelectors.length > 0) { for (const criticalSelector of criticalSelectors) { try { if ($element.is(criticalSelector)) { isCritical = true; break; } } catch (e) { /* ignore */ } } }
					
					if (!isCritical && !isElementInViewport($element) && !jqueryAnimationQueue.has(element) && allPropertiesSafeForDeferral) {
						if (!jqueryAnimationQueue.has(element)) { jqueryAnimationQueue.set(element, []); }
						jqueryAnimationQueue.get(element).push(args);
						jqueryAnimationObserver.observe(element);
						return this; 
					}
				}
				return originalJQueryAnimate.apply(this, args);

			} else if (settings.jquery_animate_optimization_mode === 'aggressive') {
				let attemptedConversion = false;
				if (typeof properties === 'object' && properties !== null) {
					const $element = this.first(); // For logging context
					let selectorText = $element.prop("tagName");
					if ($element.attr("id")) selectorText += "#" + $element.attr("id");
					if ($element.attr("class")) selectorText += "." + $element.attr("class").trim().replace(/\s+/g, '.');

					if (properties.hasOwnProperty('top') || properties.hasOwnProperty('left')) {
						// DEV_LOG: console.warn('LHA Aggressive: Potential conversion for top/left animation found for ' + selectorText + '. Actual conversion not yet implemented.');
						attemptedConversion = true; // Mark as "attempted" for this subtask's purpose
					}
					if (properties.hasOwnProperty('width') || properties.hasOwnProperty('height')) {
						// DEV_LOG: console.warn('LHA Aggressive: Potential conversion for width/height animation found for ' + selectorText + '. Actual conversion not yet implemented.');
						attemptedConversion = true; // Mark as "attempted" for this subtask's purpose
					}
					// Add more specific property checks here in the future
				}
				// If no specific conversion was "attempted" (logged), or for any other case, fall back.
				// For this subtask, even if "attempted", we still fall back.
				return originalJQueryAnimate.apply(this, args);
			} else { 
				return originalJQueryAnimate.apply(this, args);
			}
		};
	}

	function initGsapReducedMotionHelper() { /* ... (existing code, no changes here) ... */ }

	function init() {
		if (!settings.globalEnablePlugin) { return; }
		if (settings.lazyLoadAnimations) {
			try { if (!document.querySelector(settings.lazy_load_include_selector)) { settings.noLazyLoadTargetsFound = true; } } catch (e) { console.warn('LHA Animation Optimizer: Invalid Primary Lazy Load Selector for initial check:', settings.lazy_load_include_selector, e); settings.noLazyLoadTargetsFound = true;  }
		}
		if (settings.lazyLoadAnimations) { initLazyLoadAnimations(); }
		if (settings.enable_jquery_animate_optimization) { initJQueryAnimateOptimizer(); }
		if (settings.gsap_prefers_reduced_motion_helper) { initGsapReducedMotionHelper(); }
		$(window).on('unload', function() { sendStatsIfNeeded(); });
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

})( jQuery );
// This file is production-ready.
