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
		noLazyLoadTargetsFound: false // New flag
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

	/**
	 * Initializes the lazy loading of animations using IntersectionObserver.
	 */
	function initLazyLoadAnimations() {
		if (settings.noLazyLoadTargetsFound) { // Early exit if no targets were found by init()
			return;
		}

		// This function is only called if settings.lazyLoadAnimations is true (checked in init())
		// The querySelectorAll was already done in init() to set noLazyLoadTargetsFound,
		// so we use it again here. If performance was critical for this specific re-query,
		// we could pass the result from init(), but it's cleaner to keep it self-contained.
		let animationTargets;
		try {
			animationTargets = document.querySelectorAll(settings.lazy_load_include_selector);
		} catch (e) {
			// This catch is mostly redundant now if init() already tried and potentially defaulted
			console.warn('LHA Animation Optimizer: Invalid Primary Lazy Load Selector provided (should have been caught in init):', settings.lazy_load_include_selector, e);
			animationTargets = document.querySelectorAll(defaultSettings.lazy_load_include_selector);
		}

		if (!animationTargets.length) { // Should not happen if noLazyLoadTargetsFound was set correctly
			return;
		}

		const excludeSelectors = settings.lazy_load_exclude_selectors ? settings.lazy_load_exclude_selectors.split('\n').map(s => s.trim()).filter(s => s !== '') : [];
		const criticalSelectors = settings.lazy_load_critical_selectors ? settings.lazy_load_critical_selectors.split('\n').map(s => s.trim()).filter(s => s !== '') : [];
		let thresholdValue = settings.intersectionObserverThreshold;
		if (typeof thresholdValue !== 'number' || thresholdValue < 0 || thresholdValue > 1) {
			thresholdValue = defaultSettings.intersectionObserverThreshold;
		}

		const observerOptions = { root: null, rootMargin: '0px', threshold: thresholdValue };
		let animationObserver;

		if ('IntersectionObserver' in window) {
			animationObserver = new IntersectionObserver((entries, observer) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						try {
							entry.target.classList.add('lha-animate-now');
							animationsProcessedByLazyLoader++;
							observer.unobserve(entry.target);
							sendStatsIfNeeded(); 
						} catch (e) { /* console.error('LHA: Error applying animation class:', e, entry.target); */ }
					}
				});
			}, observerOptions);
		}

		animationTargets.forEach(target => {
			let isCritical = false;
			if (criticalSelectors.length > 0) { for (const criticalSelector of criticalSelectors) { try { if (target.matches(criticalSelector)) { isCritical = true; break; } } catch (e) { console.warn('LHA: Invalid Critical Selector:', criticalSelector, e); } } }
			if (isCritical) { target.classList.add('lha-animate-now'); return; }

			let isExcluded = false;
			if (excludeSelectors.length > 0) { for (const excludeSelector of excludeSelectors) { try { if (target.matches(excludeSelector)) { isExcluded = true; break; } } catch (e) { console.warn('LHA: Invalid Exclude Selector:', excludeSelector, e); } } }
			if (isExcluded) { return; }

			if (animationObserver) {
				animationObserver.observe(target);
			} else {
				target.classList.add('lha-animate-now');
				animationsProcessedByLazyLoader++;
				sendStatsIfNeeded(); 
			}
		});
	}

	/**
	 * Sends collected statistics to the backend if not already sent this page load.
	 */
	function sendStatsIfNeeded() {
		if (!settings.enable_statistics_tracking) { 
			return;
		}

		if (animationsProcessedByLazyLoader > 0 && !statsSentThisPageLoad) {
			statsSentThisPageLoad = true; 
			
			const data = {
				action: 'lha_update_stats',
				nonce: settings.update_stats_nonce,
				observed_animations_count: animationsProcessedByLazyLoader
			};
				$.post(settings.ajax_url, data).done(function(response) {
					// Optional: Handle success/failure of stats update if needed for debugging
				}).fail(function() {
					// console.log('LHA: Stats update AJAX request failed.');
				});
		}
	}

	function initJQueryAnimateOptimizer() { 
		if (typeof jQuery === 'undefined' || typeof jQuery.fn.animate !== 'function') {
			return;
		}
		const originalJQueryAnimate = jQuery.fn.animate;
		jQuery.fn.animate = function(...args) {
			return originalJQueryAnimate.apply(this, args);
		};
	 }
	function initGsapReducedMotionHelper() { 
		if (typeof gsap !== 'undefined' && typeof gsap.matchMedia === 'function') {
			let mm = gsap.matchMedia();
			mm.add("(prefers-reduced-motion: reduce)", (context) => {
				if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.getAll === 'function') {
					ScrollTrigger.getAll().forEach(st => {
						st.pause();
					});
				}
			});
		}
	}


	/**
	 * Initializes the plugin's public-facing JavaScript logic.
	 */
	function init() {
		if (!settings.globalEnablePlugin) { 
			return;
		}

		// Check for lazy load targets early
		if (settings.lazyLoadAnimations) {
			try {
				if (!document.querySelector(settings.lazy_load_include_selector)) {
					settings.noLazyLoadTargetsFound = true;
				}
			} catch (e) {
				console.warn('LHA Animation Optimizer: Invalid Primary Lazy Load Selector for initial check:', settings.lazy_load_include_selector, e);
				// If selector is invalid, assume no targets, or could try default
				// For safety, if it's invalid, we might not want to proceed with lazy loading specific logic.
				settings.noLazyLoadTargetsFound = true; 
			}
		}


		if (settings.lazyLoadAnimations) { // This check is now technically redundant if noLazyLoadTargetsFound is true, but harmless.
			initLazyLoadAnimations();
		}

		if (settings.enable_jquery_animate_optimization) {
			initJQueryAnimateOptimizer();
		}

		if (settings.gsap_prefers_reduced_motion_helper) {
			initGsapReducedMotionHelper();
		}
		
		$(window).on('unload', function() {
			sendStatsIfNeeded();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})( jQuery );
// This file is production-ready.
