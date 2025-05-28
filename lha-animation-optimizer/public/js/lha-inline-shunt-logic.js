(function() {
    'use strict';

    /**
     * LHA Animation Optimizer - Inline Shunt Script
     * 
     * This script is injected inline into the <head> of the page when a valid animation cache exists.
     * Its primary responsibilities are:
     * 1. Initialize global variables for queuing early animation calls (`lhaEarlyAnimationQueue`)
     *    and storing original animation functions (`LHA_Originals`).
     * 2. Dynamically load the main player script (`lha-animation-optimizer-public.js` via `window.lhaPlayerScriptUrl`).
     * 3. Intercept (shunt) calls to common jQuery and GSAP animation methods if these libraries are already loaded.
     *    - Shunted calls are added to `lhaEarlyAnimationQueue` instead of executing immediately.
     *    - Original methods are stored in `LHA_Originals` for later restoration by the main player script.
     * 
     * This two-step approach (inline shunt + async main player) helps to:
     * - Minimize render-blocking JavaScript.
     * - Capture animations that might be called very early in the page lifecycle (e.g., by other inline scripts).
     * - Ensure that the main player script has all necessary data (preloaded settings and animations)
     *   and can correctly process any early animation calls.
     *
     * Assumes PHP has defined the following globals before this script runs:
     * - `window.lhaPreloadedAnimations`: Array of cached animation data.
     * - `window.lhaPreloadedSettings`: Object containing player settings (including debugMode).
     * - `window.lhaPlayerScriptUrl`: String URL to the main player script.
     */

    // Helper for shunt-specific debug logging, checks preloaded settings
    function shuntDebugLog(...args) {
        // Check for settings and debugMode on each call, as they are set by PHP just before this script.
        if (window.lhaPreloadedSettings && window.lhaPreloadedSettings.debugMode) {
            console.log('LHA Shunt:', ...args);
        }
    }

    shuntDebugLog('Initializing.');

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
        // Check if jQuery exists, its `fn` property is an object, and if it hasn't been wrapped already by this shunt.
        if (typeof window.jQuery !== 'function' || 
            typeof window.jQuery.fn !== 'object' || 
            window.jQuery.fn.lhaShuntWrapped) {
            shuntDebugLog('jQuery not ready for shunting or already shunted by LHA.');
            return;
        }
        shuntDebugLog('jQuery detected. Attempting to wrap animation methods.');

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
        // Check if GSAP exists and hasn't been wrapped already by this shunt.
        if (typeof window.gsap !== 'object' || window.gsap.lhaShuntWrapped) {
            shuntDebugLog('GSAP not ready for shunting or already shunted by LHA.');
            return;
        }
        shuntDebugLog('GSAP detected. Attempting to wrap animation methods.');

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
