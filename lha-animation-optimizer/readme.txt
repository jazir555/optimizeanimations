=== LHA Animation Optimizer ===
Contributors: LHA Plugin Author
Tags: animation, performance, optimization, jquery, gsap, lazy load
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimizes web animations for speed and performance by lazy loading animations and caching detected JavaScript-based animations.

== Description ==

LHA Animation Optimizer enhances your website's performance by controlling how and when animations are loaded and played. It offers two main features:

1.  **Lazy Loading of Animations:** Animations (CSS or JavaScript-driven) associated with elements having the `.lha-animation-target` class will only play when the element scrolls into the viewport. This prevents a flood of animations from playing simultaneously on page load, improving perceived performance and reducing initial processing.
2.  **Automatic Animation Caching (New in 1.1.0):** The plugin automatically detects animations created by jQuery (`.animate()`) and GSAP (GreenSock Animation Platform) during a user's first visit (when the cache is not yet built). These detected animation details (like selectors, properties, and durations) are then stored in a cache. On subsequent page views, the heavier detection scripts are not loaded. Instead, a lightweight player script uses the cached data to re-initialize and play these animations when they become visible, further reducing JavaScript execution time and improving load speed.

The plugin provides settings to enable/disable lazy loading and configure the visibility threshold for triggering animations. It also includes a manual cache clearing option.

== Features ==

*   **Lazy Loading:** Animations only play when elements enter the viewport using IntersectionObserver.
*   **Configurable Threshold:** Set what percentage of an element must be visible to trigger its animation.
*   **Automatic Animation Caching (New in 1.1.0):**
    *   Detects animations created by jQuery (`.animate()`) and common GSAP timelines on the initial page view.
    *   Caches these detected animation details (selectors, properties, duration).
    *   On subsequent views, loads a lightweight animation player instead of the full detection scripts if a valid cache exists.
    *   The animation cache is automatically cleared when plugin settings are saved or can be manually cleared via a button in the admin settings.
*   **Admin Settings Page:** Easy-to-use interface to configure plugin behavior.
*   **Manual Cache Control (New in 1.1.0):** A "Clear Animation Cache" button in the admin settings allows for manually forcing a rebuild of the animation cache.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Animation Optimizer" in the WordPress admin menu to configure the settings.

== How to Use ==

1.  **For Lazy Loading (CSS or other JS animations):**
    *   Ensure the "Enable Lazy Loading of Animations" setting is checked (default).
    *   Add the class `lha-animation-target` to any HTML element you want to lazy load.
    *   When the element scrolls into view, the plugin will add the class `lha-animate-now` (or the class configured in `lhaSettings.animationTriggerClass` if customized by a filter - currently hardcoded in JS but planned for settings). Your CSS should use this class to trigger the animation (e.g., `.lha-animation-target.lha-animate-now { animation-name: my-animation; }`).

2.  **For Automatic Animation Caching (jQuery & GSAP):**
    *   This feature is automatic. If jQuery or GSAP animations are used on your site, the plugin's detector script (`lha-animation-detector.js`) will attempt to capture their details on the first visit (when no valid cache exists).
    *   On subsequent visits, if a cache was successfully built, `lha-animation-detector.js` will not be loaded. Instead, `lha-animation-optimizer-public.js` will use the cached data to replay the animations.
    *   No special classes are needed for jQuery/GSAP animation *detection*, but the elements animated by them will be effectively treated as `lha-animation-target`s by the player script.

== Frequently Asked Questions ==

= How does lazy loading work? =

The plugin uses the IntersectionObserver API to monitor elements with the `lha-animation-target` class. When an element enters the viewport (based on the configured threshold), the plugin adds a CSS class (default: `lha-animate-now`) to that element, which your CSS can then use to trigger the animation.

= How does Automatic Animation Caching work? =

On a user's first visit (or after the cache has been cleared), a special JavaScript file (`lha-animation-detector.js`) is loaded. This script attempts to identify animations being created by popular JavaScript libraries like jQuery (specifically `.animate()` calls) and GSAP. It records information about these animations, such as which elements they apply to, what properties are being animated, and for how long. This information is then sent back to your WordPress server and stored as a "cache."

On subsequent page views, the plugin checks if this cache exists and is valid. If it is, the heavier `lha-animation-detector.js` is not loaded. Instead, the main public script (`lha-animation-optimizer-public.js`) reads the cached animation data and re-applies those animations to the correct elements when they become visible in the viewport. This means your site doesn't have to spend resources re-detecting the same animations on every page load.

= How do I use the "Clear Animation Cache" button? =

In the WordPress admin area, navigate to "Animation Optimizer" from the main menu. On this settings page, you will find a button labeled "Clear Animation Cache Now". Clicking this button will delete the stored animation data. This is useful if:
*   You've made significant changes to your site's JavaScript animations and want the plugin to re-detect them.
*   You are troubleshooting an issue and want to ensure the animations are being freshly detected.
The cache will also be cleared automatically whenever you save the Animation Optimizer settings.

= What if my jQuery/GSAP animation is very complex or uses callbacks? =

The current detection mechanism for jQuery and GSAP animations focuses on common use cases and serializable properties. Complex aspects like function-based values, callbacks within animations (e.g., `complete` functions), or advanced GSAP features like ScrollTrigger might not be fully captured or replicated by the caching system. In such cases, those specific animations might not behave identically when played from the cache. The underlying elements will still be lazy-loaded.

= What if no animations are detected or played? =

*   Ensure elements meant for lazy loading have the `lha-animation-target` class.
*   For jQuery/GSAP, ensure they are firing on page load or reasonably early for detection.
*   Check your browser's developer console for any error messages from "LHA Detector" or "LHA Player".
*   Try clearing the animation cache.
*   Ensure your theme and other plugins are not causing JavaScript errors that might interfere with this plugin.

== Screenshots ==

1.  The LHA Animation Optimizer settings page in the WordPress admin area.

== Changelog ==

= 1.1.0 (Current Version) =
*   **NEW:** Implemented Automatic Animation Caching for jQuery (`.animate()`) and GSAP animations.
    *   Detector script (`lha-animation-detector.js`) now identifies and sends jQuery/GSAP animation data to the server for caching.
    *   Public script (`lha-animation-optimizer-public.js`) now acts as a player, re-initializing cached animations when the detector script is not loaded.
    *   Conditional loading: Detector script only loads if the animation cache is invalid or empty.
*   **NEW:** Added "Clear Animation Cache" button to the admin settings page.
*   **ENHANCEMENT:** Animation cache version is now updated when plugin settings are saved, effectively clearing the old animation cache.
*   **ENHANCEMENT:** Improved sanitization for detected animation data.
*   **ENHANCEMENT:** Refined selector generation in the detector script.
*   **REFACTOR:** Consolidated public script enqueuing and localization logic in `Public_Script_Manager`.
*   Updated inline code comments and documentation.

= 1.0.0 =
*   Initial release.
*   Features: Lazy loading of animations using IntersectionObserver, configurable threshold.

== Upgrade Notice ==

= 1.1.0 =
This version introduces a new animation caching system for jQuery and GSAP animations, significantly improving performance on subsequent page loads. It also adds a manual cache clearing option. Please review the new "Automatic Animation Caching" feature description.

== Known Issues ==

*   The animation detection for jQuery and GSAP captures common animation patterns. Very complex animations, especially those relying heavily on callbacks, function-based properties, or advanced GSAP plugins like ScrollTrigger, might not be fully replicated from the cache.
*   Selector generation for detected animations, while improved, might not be perfectly unique in all DOM structures, potentially leading to animations being applied to unintended elements if selectors are too generic.

== Support ==

For support, please use the WordPress.org support forums for this plugin.
