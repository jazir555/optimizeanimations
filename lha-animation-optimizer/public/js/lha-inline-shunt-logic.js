(function() {
    'use strict';

    // Existing shunt logic preamble (if any)

    // --- Phase 4 (Smarter Fallback): Dynamic Loading of External Animation Data Script ---
    // This section handles the loading of animation data if it's provided via an external URL.

    if (window.lhaExternalAnimationDataUrl && typeof window.lhaExternalAnimationDataUrl === 'string' && window.lhaExternalAnimationDataUrl.trim() !== '') {
        console.log('LHA Shunt: External animation data URL found: ' + window.lhaExternalAnimationDataUrl + '. Loading data script.');
        try {
            const dataScript = document.createElement('script');
            dataScript.src = window.lhaExternalAnimationDataUrl;
            dataScript.async = true;
            dataScript.defer = true; // Ensures it's executed in order but doesn't block DOM parsing

            dataScript.onload = function() {
                console.log('LHA Shunt: External animation data script loaded successfully.');
                // Optional: Set a flag indicating success, though player will poll for the data object itself
                // window.lhaExternalDataLoaded = true; 
                // window.lhaExternalDataLoadFailed = false; // if using the flag
            };

            dataScript.onerror = function() {
                console.error('LHA Shunt: ERROR loading external animation data script from: ' + window.lhaExternalAnimationDataUrl);
                // Potentially set a flag that player can check: window.lhaExternalDataLoadFailed = true;
            };

            document.head.appendChild(dataScript); // Append to head to start loading
            // console.log('LHA Shunt: External animation data script appended to head for loading.'); // Optional: Log for append action

        } catch (e) {
            console.error('LHA Shunt: Error creating or appending external animation data script tag.', e);
        }
    } else {
        // This case handles when no external URL is provided.
        // The main player script will then rely on lhaAnimationOptimizerSettings.lhaPreloadedAnimations.
        // console.log('LHA Shunt: No external animation data URL found or URL is invalid. Preloaded data will be used if available.');
    }

    // --- Existing Shunt Logic for Loading Main Player Script ---
    // This part should remain and execute as before.
    // The main player script (e.g., lha-animation-optimizer-public.js) should be loaded after this shunt logic.
    // This shunt script's primary role is to prepare data or handle data loading strategy.
    // The actual loading of the main player script might be handled by WordPress enqueueing
    // or another mechanism ensuring it loads after this inline script.

    // Example placeholder for how main player script loading might be initiated if done by shunt:
    /*
    if (window.lhaPlayerScriptUrl) {
        console.log('LHA Shunt: Main player script URL found: ' + window.lhaPlayerScriptUrl + '. Loading player script.');
        const playerScript = document.createElement('script');
        playerScript.src = window.lhaPlayerScriptUrl;
        playerScript.async = true;
        playerScript.defer = true;
        playerScript.onload = function() {
            console.log('LHA Shunt: Main player script loaded successfully.');
        };
        playerScript.onerror = function() {
            console.error('LHA Shunt: CRITICAL ERROR - Failed to load main player script from: ' + window.lhaPlayerScriptUrl);
        };
        document.head.appendChild(playerScript);
    } else {
        console.error('LHA Shunt: Critical error - `lhaPlayerScriptUrl` is not defined. Main player script cannot be loaded by shunt.');
    }
    */

})();
