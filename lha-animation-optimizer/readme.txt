=== LHA Animation Optimizer ===
Contributors: LHA Plugin Author
Tags: animation, performance, optimization, jquery, gsap, css animation, css transition, lazy load, mutationobserver
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimizes web animations for speed and performance by lazy loading, and by detecting and caching JavaScript-based and CSS-based animations.

== Description ==

LHA Animation Optimizer significantly enhances your website's performance by intelligently managing how and when animations are loaded and played. It provides a comprehensive suite of tools for animation optimization:

1.  **Lazy Loading of Animations:** All animations (CSS, jQuery, GSAP, etc.) associated with elements having the `.lha-animation-target` class (or elements with detected animations) will only play when the element scrolls into the viewport. This prevents a flood of animations from playing simultaneously on page load, improving perceived performance and reducing initial processing.

2.  **Automatic Animation Caching (Enhanced in 2.0.0):** The plugin automatically detects a wide range of animations on a user's first visit (or when the cache is empty/invalidated):
    *   **jQuery Animations:** Detects animations from `.animate()` and also from specific methods like `.fadeIn()`, `.fadeOut()`, `.slideUp()`, `.slideDown()`, `.slideToggle()`, `.fadeTo()`, and `.fadeToggle()`.
    *   **GSAP (GreenSock Animation Platform) Animations:** Detects tweens added directly or via `Timeline.prototype.add`, including "fromTo" variations and stagger effects.
    *   **CSS Animations & Transitions:** Detects CSS keyframe animations and CSS transitions applied to elements, often triggered by class changes or style modifications.
    These detected animation details (selectors, properties, durations, types, etc.) are stored in a cache. On subsequent page views, if a valid cache exists, the heavier detection scripts are not loaded. Instead, a lightweight player script uses the cached data to re-initialize and play these animations when they become visible. This drastically reduces JavaScript execution time and improves load speed.

The plugin provides a comprehensive admin settings page to control lazy loading, animation detection mechanisms, and a debug mode for troubleshooting.

== Features ==

*   **Lazy Loading:** Animations only play when elements enter the viewport using IntersectionObserver.
*   **Configurable Threshold:** Set what percentage of an element must be visible to trigger its animation.
*   **Comprehensive Automatic Animation Caching (Enhanced in 2.0.0):**
    *   **Advanced jQuery Detection:** Captures animations from `.animate()` and common effects like `fadeIn`, `slideUp`, etc.
    *   **Advanced GSAP Detection:** Identifies tweens created with `gsap.to()`, `gsap.fromTo()`, etc., and those added to timelines via `Timeline.prototype.add`. Captures "fromTo" states and stagger properties.
    *   **CSS Animation & Transition Detection:** Uses `MutationObserver` to detect CSS keyframe animations and transitions applied dynamically or through class/style changes.
    *   Caches detailed animation data (selectors, types, properties, durations, easing, CSS animation/transition specifics).
    *   On subsequent views, loads a lightweight animation player instead of the full detection scripts if a valid cache exists.
    *   The animation cache is automatically cleared when plugin settings are saved or can be manually cleared.
*   **Configurable Detection Mechanisms (New in 2.0.0):**
    *   Admin options to enable/disable "Advanced jQuery Detection," "Advanced GSAP Detection," and "CSS Animation/Transition Detection (MutationObserver)" to fine-tune performance vs. detection scope.
*   **Debug Mode (New in 2.0.0):**
    *   An option to enable detailed logging to the browser console for troubleshooting detection issues.
*   **Admin Settings Page:** Easy-to-use interface to configure plugin behavior.
*   **Manual Cache Control:** A "Clear Animation Cache" button in the admin settings.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Animation Optimizer" in the WordPress admin menu to configure the settings.

== Configuration ==

After installation, navigate to the "Animation Optimizer" settings page in your WordPress admin dashboard. Here you can configure:

*   **Enable Lazy Loading of Animations:** (Default: Enabled) Check this to enable the IntersectionObserver-based lazy loading for all targeted animations.
*   **Lazy Load Trigger Threshold:** (Default: 0.1) A value between 0.0 and 1.0 representing the percentage of an element that must be visible for its animation to trigger.
*   **Enable Advanced jQuery Detection:** (Default: Enabled) Enables detection of animations from specific jQuery methods like `fadeIn`, `slideUp`, etc., in addition to `.animate()`. Disable if you only use `.animate()` or suspect conflicts with highly custom jQuery usage.
*   **Enable Advanced GSAP Detection:** (Default: Enabled) Enables more comprehensive GSAP detection, including observing `Timeline.prototype.add`. Disable if you are not using GSAP timelines heavily or if you notice issues with GSAP detection.
*   **Enable CSS Animation/Transition Detection (MutationObserver):** (Default: Enabled) Enables the use of `MutationObserver` to detect CSS-driven animations and transitions. This is powerful but can have a performance overhead on sites with extremely frequent and complex DOM manipulations. Disable if you suspect performance issues on such sites or do not need CSS animation caching.
*   **Enable Debug Mode:** (Default: Disabled) Check this to output detailed animation detection information to your browser's developer console. This is very useful for troubleshooting why certain animations might not be detected or played as expected.
*   **Clear Animation Cache Now Button:** Manually clears all cached animation data, forcing a fresh detection cycle on the next public page load. The cache is also cleared automatically when you save any settings on this page.

== How to Use ==

1.  **For Lazy Loading (General):**
    *   Ensure the "Enable Lazy Loading of Animations" setting is checked.
    *   For CSS animations not automatically detected or for custom JavaScript animations you want to control, add the class `lha-animation-target` to the HTML element.
    *   When the element scrolls into view, the plugin will add the class `lha-animate-now` (this is the default `animationTriggerClass` in the player script). Your CSS should use this class to trigger the animation (e.g., `.lha-animation-target.lha-animate-now { animation-name: my-animation; }`).

2.  **For Automatic Animation Caching (jQuery, GSAP, CSS):**
    *   Ensure the relevant detection features are enabled in the plugin settings (they are by default).
    *   The plugin's detector script (`lha-animation-detector.js`) will attempt to capture details of animations from these sources on the first visit (when no valid cache exists).
    *   On subsequent visits, if a cache was successfully built, `lha-animation-detector.js` will not be loaded. Instead, `lha-animation-optimizer-public.js` (the player script) will use the cached data to replay the animations when elements become visible.
    *   No special classes are needed for detection of animations from these sources, but the elements animated by them will be effectively treated as `lha-animation-target`s by the player script.

== Frequently Asked Questions ==

= How does lazy loading work? =
The plugin uses the IntersectionObserver API to monitor elements that are either manually assigned the `.lha-animation-target` class or are identified by the animation detection process. When an element enters the viewport (based on the configured threshold), the plugin adds a CSS class (default: `lha-animate-now`) to that element. This class can trigger CSS animations, or in the case of cached JavaScript animations, signal the player script to execute the animation.

= How does Automatic Animation Caching work? =
On a user's first visit (or after the cache has been cleared), a special JavaScript file (`lha-animation-detector.js`) is loaded. This script:
1.  Wraps common jQuery animation methods (like `.animate()`, `.fadeIn()`, etc.).
2.  Wraps GSAP's `Timeline.prototype.add` method and scans the global GSAP timeline.
3.  Uses a `MutationObserver` to watch for DOM changes that might apply CSS animations or transitions (e.g., class changes, style changes).
It records information about these animations (target elements, properties, duration, type, etc.). This data is then sent to your WordPress server and stored as a "cache."

On subsequent page views, if this cache is valid, the heavy `lha-animation-detector.js` is *not* loaded. Instead, the main public script (`lha-animation-optimizer-public.js`) reads the cached animation data and re-applies those animations to the correct elements when they become visible in the viewport. This reduces JavaScript execution and processing on typical page loads.

= What types of animations can the plugin now detect? =
The plugin can detect and cache:
*   **jQuery animations:** Created by `.animate()` and specific methods like `fadeIn`, `fadeOut`, `slideUp`, `slideDown`, `slideToggle`, `fadeTo`, `fadeToggle`.
*   **GSAP animations:** Tweens created with methods like `gsap.to()`, `gsap.fromTo()`, and those added to GSAP Timelines. It attempts to capture "fromTo" states and stagger properties.
*   **CSS Keyframe Animations:** Detected when applied to elements (e.g., via class changes or direct style manipulation).
*   **CSS Transitions:** Detected when properties change on elements that have transitions defined for them.

= How does the CSS Animation/Transition detection work? =
It uses the `MutationObserver` API, a browser feature that allows the script to watch for changes made to the DOM (Document Object Model – the structure of your page). When attributes like `class` or `style` change on an element, or when new elements are added to the page, the MutationObserver notifies our script. The script then inspects the affected element(s) to see if any CSS animations or transitions are active (by checking `getComputedStyle`). If so, it records their details. This detection is conditional and can be disabled in settings if needed.

= What are the 'Advanced jQuery/GSAP Detection' settings for? =
*   **Advanced jQuery Detection:** By default, the plugin wraps jQuery's core `.animate()` method. Enabling this setting extends detection to other common jQuery animation methods like `.fadeIn()`, `.slideUp()`, etc. This provides more comprehensive jQuery animation caching.
*   **Advanced GSAP Detection:** By default, the plugin scans the GSAP global timeline. Enabling this setting also wraps `Timeline.prototype.add`, allowing it to potentially capture tweens that are part of complex, nested timelines more effectively.

If you are not using these specific jQuery methods or complex GSAP timeline structures, you *could* disable these settings to slightly reduce the detector script's initial setup, but they are generally safe to leave enabled.

= What is Debug Mode and how do I use it? =
When "Enable Debug Mode" is checked in the plugin settings, the `lha-animation-detector.js` script (which runs on first visits or when the cache is empty) will output detailed information to your browser's developer console (usually accessible by pressing F12). This includes:
*   Which detection features are enabled/disabled.
*   When specific animation wrappers (like for jQuery or GSAP) are being set up.
*   Details of each animation it detects (selector, type, properties).
*   Information about AJAX calls made to save the data.
*   MutationObserver activity (if enabled).
To use it, enable the setting, clear the animation cache (or save settings again), then visit a public page of your site with the developer console open. This is primarily for troubleshooting if you suspect an animation isn't being detected correctly. Remember to disable it on a live site after troubleshooting.

= What should I do if an animation isn't detected? =
1.  **Enable Debug Mode:** Check the browser console for logs from "LHA Detector:" for clues.
2.  **Check Configuration:** Ensure the relevant detection features (Advanced jQuery, Advanced GSAP, CSS Detection) are enabled in the plugin settings.
3.  **Clear Cache:** Use the "Clear Animation Cache" button in settings and reload the public page twice (once for detection, once to test playback from cache).
4.  **Complexity:** Very complex or unusually implemented JavaScript animations might be beyond the scope of automatic detection. The system relies on common patterns.
5.  **Timing:** Animations triggered very late after page load by complex user interactions might not be caught by the initial detection phase.
6.  **External Libraries:** Ensure jQuery and GSAP (if used) are loaded correctly and are accessible when the detector script runs.
7.  **Plugin/Theme Conflicts:** Test with other plugins disabled and a default theme to rule out conflicts.

== Screenshots ==

1.  The LHA Animation Optimizer settings page in the WordPress admin area, showing general and advanced detection settings.

== Changelog ==

= 2.0.0 (YYYY-MM-DD) =
*   **NEW:** Comprehensive Animation Detection & Caching Overhaul:
    *   **Enhanced jQuery Detection:** Now wraps specific methods like `fadeIn`, `fadeOut`, `slideUp`, `slideDown`, `slideToggle`, `fadeTo`, `fadeToggle` in addition to `.animate()`.
    *   **Enhanced GSAP Detection:** Wraps `Timeline.prototype.add` for broader capture of tweens within timelines. Improved data extraction for "fromTo" states and stagger properties. Implemented deduplication for GSAP tweens.
    *   **NEW: CSS Animation & Transition Detection:** Added detection for CSS keyframe animations and CSS transitions using `MutationObserver`.
*   **NEW:** Configurable Detection Mechanisms:
    *   Added admin settings to enable/disable "Advanced jQuery Detection," "Advanced GSAP Detection," and "CSS Animation/Transition Detection (MutationObserver)".
*   **NEW:** Debug Mode:
    *   Added an admin setting to "Enable Debug Mode" for detailed console logging from the detector script.
*   **ENHANCEMENT:** Player Script (`lha-animation-optimizer-public.js`):
    *   Updated to correctly interpret and play back all newly detected animation types (jQuery specific methods, GSAP fromTo/stagger, CSS animations).
    *   Improved data handling from `dataset` attributes for animation playback.
*   **ENHANCEMENT:** Admin Panel:
    *   Added new settings fields with descriptions.
    *   Cache invalidation (version update and data deletion) now occurs reliably when any plugin settings are saved.
*   **ENHANCEMENT:** Performance & Robustness:
    *   Refined selector generation in the detector script with depth limiting.
    *   Added `element.isConnected` check before `getComputedStyle` in MutationObserver logic.
    *   Optimized GSAP data cloning in the detector.
*   **DOCUMENTATION:** Updated `readme.txt` and inline code comments extensively to reflect all new features and logic.

= 1.1.0 =
*   NEW: Implemented Automatic Animation Caching for jQuery (`.animate()`) and GSAP animations.
*   NEW: Added "Clear Animation Cache" button to the admin settings page.
*   ENHANCEMENT: Animation cache version is now updated when plugin settings are saved.
*   ENHANCEMENT: Improved sanitization for detected animation data.
*   ENHANCEMENT: Refined selector generation in the detector script.
*   REFACTOR: Consolidated public script enqueuing and localization logic.
*   Updated inline code comments and documentation.

= 1.0.0 =
*   Initial release.
*   Features: Lazy loading of animations using IntersectionObserver, configurable threshold.

== Upgrade Notice ==

= 2.0.0 =
This is a major update that significantly expands the plugin's animation detection capabilities to include more jQuery methods, more GSAP scenarios, and CSS animations/transitions. It also introduces new admin settings for finer control over detection and a debug mode. Please review the new configuration options and test your site's animations after updating.

== Known Limitations/Considerations ==

*   **Detection Accuracy:** While significantly improved, 100% accurate detection of all possible animation implementations (especially highly dynamic or unusually coded JavaScript animations, or very complex CSS interactions) is challenging. The plugin focuses on common, well-structured patterns.
*   **Performance Trade-offs:**
    *   The `MutationObserver` for CSS detection is powerful but can add overhead on sites with extremely frequent and complex DOM changes. If you notice performance issues on such specific sites, consider disabling this feature.
    *   Advanced JS library wrapping (jQuery, GSAP) adds a small overhead during the detection phase. These are generally negligible but can be disabled if not needed.
*   **Complex Callbacks/Logic:** Callbacks or complex logic within JavaScript animations (e.g., `onComplete` functions in GSAP/jQuery that trigger other actions) are not replicated by the caching system. The animation's visual properties are cached and replayed.
*   **Dynamic Selectors:** If JavaScript generates highly dynamic selectors or frequently changes element IDs/classes that are critical for animation targeting, the cached selectors might become stale. Regular cache clearing might be needed in such edge cases.

== Support ==

For support, please use the WordPress.org support forums for this plugin.
