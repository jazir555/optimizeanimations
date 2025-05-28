(function() {
    'use strict';

    /**
     * LHA Animation Optimizer - Inline Shunt Script
     * (Detailed description from previous turn remains relevant)
     */

    // --- Settings Initialization ---
    // Assumes window.lhaPreloadedSettings is defined by PHP.
    // Provides defaults if any setting is missing.
    const LHA_settings = window.lhaPreloadedSettings || {};
    const LHA_debugMode = LHA_settings.debugMode === true; // Ensure boolean
    // New settings for shunt interception: Default to true (interception enabled) if the setting is undefined.
    const LHA_shuntEnableJqueryIntercept = LHA_settings.shuntEnableJqueryInterception !== false; 
    const LHA_shuntEnableGsapIntercept = LHA_settings.shuntEnableGsapInterception !== false;  

    // Helper for shunt-specific debug logging
    function shuntDebugLog(...args) {
        // Use the LHA_debugMode constant derived from settings.
        if (LHA_debugMode && window.console && typeof console.log === 'function') {
            // Prepend "LHA Shunt:" to all debug messages.
            Array.prototype.unshift.call(args, 'LHA Shunt:');
            console.log.apply(console, args);
        }
    }

    shuntDebugLog('Initializing. Debug Mode:', LHA_debugMode);
    shuntDebugLog('jQuery Interception Setting:', LHA_shuntEnableJqueryIntercept);
    shuntDebugLog('GSAP Interception Setting:', LHA_shuntEnableGsapIntercept);
    
    // 1. Global Variables and Data Handling
    // Queue for animation calls made before the main player script loads and restores originals.
    window.lhaEarlyAnimationQueue = window.lhaEarlyAnimationQueue || [];
    // Store for original jQuery/GSAP methods to be restored by the main player.
    window.LHA_Originals = window.LHA_Originals || { jquery: {}, gsap: {} };
    shuntDebugLog('Global queue and originals store initialized/ensured.');

    // 2. Dynamic Loading of Main Player Script
    if (window.lhaPlayerScriptUrl) {
        shuntDebugLog('Player script URL found:', window.lhaPlayerScriptUrl);
        try {
            var playerScriptTag = document.createElement('script'); // Renamed for clarity
            playerScriptTag.src = window.lhaPlayerScriptUrl;
            playerScriptTag.async = true;
            playerScriptTag.defer = true; // Helps ensure it executes after DOM parsing but can run before DOMContentLoaded.
            // Appending to document.head is standard; document.documentElement is a robust fallback.
            (document.head || document.documentElement).appendChild(playerScriptTag);
            shuntDebugLog('Main player script is being loaded asynchronously.');
        } catch (e) {
            // Use console.error for critical operational failures of the shunt.
            console.error('LHA Shunt: Critical error - Failed to create or inject player script tag.', e);
        }
    } else {
        console.error('LHA Shunt: Critical error - `lhaPlayerScriptUrl` is not defined. Main player cannot be loaded.');
    }

    // 3. Animation Interception Logic (Shunting)

    // jQuery Interception: Wraps common jQuery animation methods.
    function wrapJQueryMethods() {
        if (!LHA_shuntEnableJqueryIntercept) {
            shuntDebugLog('jQuery interception disabled by setting.');
            return;
        }

        // Check if jQuery exists, its `fn` property is an object, and if it hasn't been wrapped already by this shunt.
        if (typeof window.jQuery !== 'function' || 
            typeof window.jQuery.fn !== 'object' || 
            window.jQuery.fn.lhaShuntWrapped) {
            shuntDebugLog('jQuery not ready for shunting or already shunted by LHA.');
            return;
        }
        shuntDebugLog('jQuery interception enabled, attempting to wrap methods.');

        const jqueryMethodsToWrap = [
            'animate', 'fadeIn', 'fadeOut', 'slideDown', 'slideUp', 
            'slideToggle', 'fadeTo', 'fadeToggle'
        ];

        jqueryMethodsToWrap.forEach(function(methodName) {
            if (typeof window.jQuery.fn[methodName] === 'function') {
                // Store the original method only if not already stored (e.g., if script runs multiple times by mistake).
                if (!window.LHA_Originals.jquery[methodName]) {
                    window.LHA_Originals.jquery[methodName] = window.jQuery.fn[methodName];
                }
                
                // Replace the original jQuery method with the shunt version.
                window.jQuery.fn[methodName] = function() {
                    const argsForQueue = Array.prototype.slice.call(arguments);
                    shuntDebugLog("Shunt: Queuing jQuery call - Method:", methodName, "Target elements:", (this.length || 0), "Args:", argsForQueue.length ? argsForQueue : '<no args>');
                    // Add the call details to the early animation queue.
                    window.lhaEarlyAnimationQueue.push({
                        type: 'jquery',     // Identifies the library
                        method: methodName, // The specific jQuery method called
                        target: this,       // The jQuery object (set of DOM elements)
                        args: argsForQueue  // Arguments passed to the original call
                    });
                    // Return `this` to maintain jQuery's chainability.
                    return this;
                };
            } else {
                shuntDebugLog("jQuery method not found for wrapping:", methodName);
            }
        });

        window.jQuery.fn.lhaShuntWrapped = true; // Flag to indicate jQuery methods have been shunted.
        shuntDebugLog('jQuery animation methods shunted successfully.');
    }

    // GSAP Interception: Wraps common GSAP static animation methods.
    function wrapGSAPMethods() {
        if (!LHA_shuntEnableGsapIntercept) {
            shuntDebugLog('GSAP interception disabled by setting.');
            return;
        }

        // Check if GSAP exists and hasn't been wrapped already by this shunt.
        if (typeof window.gsap !== 'object' || window.gsap.lhaShuntWrapped) {
            shuntDebugLog('GSAP not ready for shunting or already shunted by LHA.');
            return;
        }
        shuntDebugLog('GSAP interception enabled, attempting to wrap methods.');

        const gsapMethodsToWrap = ['to', 'from', 'fromTo']; // Common static methods.

        gsapMethodsToWrap.forEach(function(methodName) {
            if (typeof window.gsap[methodName] === 'function') {
                // Store the original method only if not already stored.
                if (!window.LHA_Originals.gsap[methodName]) {
                     window.LHA_Originals.gsap[methodName] = window.gsap[methodName];
                }
                // Replace the original GSAP method with the shunt version.
                window.gsap[methodName] = function() {
                    const argsForQueue = Array.prototype.slice.call(arguments);
                    shuntDebugLog("Shunt: Queuing GSAP call - Method:", methodName, "Args:", argsForQueue.length ? argsForQueue : '<no args>');
                    // Add the call details to the early animation queue.
                    window.lhaEarlyAnimationQueue.push({
                        type: 'gsap',       // Identifies the library
                        method: methodName, // The specific GSAP method called
                        args: argsForQueue  // Arguments passed to the original call
                    });
                    // GSAP methods often return a Tween or Timeline instance.
                    // For a shunt, returning `undefined` is the safest approach to prevent errors
                    // if calling code expects a specific GSAP object structure before the main player restores it.
                    return undefined; 
                };
            } else {
                shuntDebugLog("GSAP method not found for wrapping:", methodName);
            }
        });
        
        window.gsap.lhaShuntWrapped = true; // Flag to indicate GSAP methods have been shunted.
        shuntDebugLog('GSAP animation methods shunted successfully.');
    }

    // Attempt to wrap methods immediately if libraries are already present.
    // This script is designed to run very early in the page load.
    try {
        wrapJQueryMethods();
    } catch (e) {
        console.error('LHA Shunt: Critical error during jQuery method wrapping setup.', e);
    }

    try {
        wrapGSAPMethods();
    } catch (e) {
        console.error('LHA Shunt: Critical error during GSAP method wrapping setup.', e);
    }

    // Note: The main player script (`lha-animation-optimizer-public.js`) is responsible for:
    // 1. Checking again if jQuery/GSAP loaded after this shunt and applying wrappers if needed (though less likely with this early shunt).
    // 2. Processing `window.lhaEarlyAnimationQueue`.
    // 3. Restoring original methods from `window.LHA_Originals`.
    // 4. Initializing and playing animations based on `window.lhaPreloadedAnimations` and `window.lhaPreloadedSettings`.
})();
