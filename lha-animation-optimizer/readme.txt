=== LHA Animation Optimizer ===
Contributors: LHA Plugin Author
Tags: animation, performance, optimization, jquery, gsap, css animation, css transition, lazy load, mutationobserver, inline script
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimizes web animations for speed and performance by lazy loading, and by detecting and caching JavaScript-based and CSS-based animations. Features an advanced two-step loading mechanism for cached animations with configurable data size limits and interception controls.

== Description ==

LHA Animation Optimizer significantly enhances your website's performance by intelligently managing how and when animations are loaded and played. It provides a comprehensive suite of tools for animation optimization:

1.  **Lazy Loading of Animations:** All animations (CSS, jQuery, GSAP, etc.) associated with elements having the `.lha-animation-target` class (or elements with detected animations) will only play when the element scrolls into the viewport. This prevents a flood of animations from playing simultaneously on page load, improving perceived performance and reducing initial processing.

2.  **Automatic Animation Caching (Enhanced in 2.0.0, Refined in 2.1.0 & 2.2.0):** The plugin automatically detects a wide range of animations on a user's first visit (or when the cache is empty/invalidated). These include jQuery, GSAP, CSS Keyframe Animations, and CSS Transitions.
    These detected animation details are stored in a cache. On subsequent page views, if a valid cache exists, a **two-step loading mechanism** is used:
    *   A very small inline "shunt" script is added to the page header. This script contains the cached animation data and settings. Its primary roles are to intercept any animation calls (jQuery/GSAP, if enabled) that occur very early in the page load and to dynamically load the main animation player script. The size of data inlined with this shunt script is configurable to prevent excessive HTML bloat.
    *   The main player script (`lha-animation-optimizer-public.js`) then loads asynchronously. Once loaded, it restores any intercepted animation functions to their original state, processes the queue of early animation calls, and then manages the initialization and lazy-loaded playback of all cached animations.
    This two-step approach minimizes the initial HTML impact, ensures even very early or inline animations can be captured and managed (if interception is enabled), and optimizes the overall script loading strategy for cached views.

The plugin provides a comprehensive admin settings page to control lazy loading, animation detection mechanisms, shunt script behavior, and a debug mode.

== Features ==

*   **Lazy Loading:** Animations only play when elements enter the viewport using IntersectionObserver.
*   **Configurable Threshold:** Set what percentage of an element must be visible to trigger its animation.
*   **Comprehensive Automatic Animation Caching:**
    *   **Advanced jQuery Detection:** Captures animations from `.animate()` and common effects like `fadeIn`, `slideUp`, etc.
    *   **Advanced GSAP Detection:** Identifies tweens created with `gsap.to()`, `gsap.fromTo()`, etc., and those added to timelines via `Timeline.prototype.add`. Captures "fromTo" states and stagger properties.
    *   **CSS Animation & Transition Detection:** Uses `MutationObserver` to detect CSS keyframe animations and transitions.
    *   Caches detailed animation data.
*   **Two-Step Inline Player for Cached Animations:**
    *   When cache is valid, a minimal inline "shunt" script is injected.
    *   This shunt script holds preloaded animation data/settings, can queue early jQuery/GSAP calls (if enabled), and dynamically loads the main player script.
    *   The main player script then restores original animation functions, processes the queue, and handles playback.
*   **Configurable Detection Mechanisms:** Admin options to enable/disable "Advanced jQuery Detection," "Advanced GSAP Detection," and "CSS Animation/Transition Detection".
*   **Configurable Shunt Script Behavior (New in 2.2.0):**
    *   **Inline Data Size Threshold:** Set a maximum size (KB) for animation data to be inlined with the shunt script. If exceeded, the plugin falls back to loading the player script externally to prevent HTML bloat.
    *   **Granular Shunt Interception:** Enable/disable jQuery and GSAP animation interception by the shunt script independently. Useful for resolving conflicts with other early-executing scripts.
*   **Debug Mode:** Option for detailed console logging from detector, shunt, and player scripts.
*   **Admin Settings Page:** Easy-to-use interface.
*   **Manual Cache Control:** "Clear Animation Cache" button.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Animation Optimizer" in the WordPress admin menu to configure the settings.

== Configuration ==

Navigate to the "Animation Optimizer" settings page in your WordPress admin dashboard. Key settings include:

*   **Enable Lazy Loading of Animations:** (Default: Enabled)
*   **Lazy Load Trigger Threshold:** (Default: 0.1)
*   **Enable Advanced jQuery Detection:** (Default: Enabled) For the main detector script.
*   **Enable Advanced GSAP Detection:** (Default: Enabled) For the main detector script.
*   **Enable CSS Animation/Transition Detection (MutationObserver):** (Default: Enabled) For the main detector script.
*   **Inline Data Size Threshold (KB):** (Default: 5 KB, New in 2.2.0) Maximum size for animation data to be inlined with the shunt script. If the JSON-encoded animation data exceeds this, the plugin will load the main player script externally instead of using the inline shunt method. Set to 0 to disable this check (always attempt inline if cache is valid).
*   **Enable jQuery Interception by Shunt Script:** (Default: Enabled, New in 2.2.0) Allows the early inline shunt script to intercept and queue jQuery animations. Disable if you encounter conflicts with other scripts that modify jQuery behavior very early, or if you prefer jQuery animations not to be shunted.
*   **Enable GSAP Interception by Shunt Script:** (Default: Enabled, New in 2.2.0) Allows the early inline shunt script to intercept and queue GSAP animations. Disable if you encounter conflicts or prefer GSAP animations not to be shunted.
*   **Enable Debug Mode:** (Default: Disabled)
*   **Clear Animation Cache Now Button.**

== How to Use ==

(Largely the same as v2.1.0, with the new settings providing more control over the shunt behavior)

1.  **For Lazy Loading (General):** (Same as v2.1.0)
2.  **For Automatic Animation Caching & Playback:** (Same as v2.1.0, but now influenced by shunt data size and interception settings)

== Frequently Asked Questions ==

= How does lazy loading work? = (Same as v2.1.0)

= How does Automatic Animation Caching work? (Updated for 2.2.0) =
(Largely the same as v2.1.0, but mention the new controls)
The process is similar to version 2.1.0. The key difference is that the behavior of the inline "shunt" script is now more configurable:
*   **Data Size Check:** If the total size of cached animation data exceeds the "Inline Data Size Threshold" (default 5KB), the plugin will skip the inline shunt method and load the player script externally, similar to how it behaves when the cache is invalid. This prevents overly large inline scripts in the HTML head. A threshold of 0 disables this check.
*   **Shunt Interception Control:** You can now enable or disable the shunt script's ability to intercept jQuery and GSAP animations independently. If you disable interception for a library, animations from that library will execute normally when they are called, even if very early, and won't be queued by the shunt.

= What happens if my page has a huge amount of animation data? =
The "Inline Data Size Threshold (KB)" setting (default 5KB) helps manage this. If your cached animation data (when converted to JSON for inlining) is larger than this threshold, the plugin will automatically fall back to loading the main player script externally (like `lha-animation-optimizer-public.js`) instead of inlining the data and the shunt script. This prevents the initial HTML page from becoming too large due to extensive inline JavaScript. You can adjust this threshold or set it to 0 to always attempt inlining (if the cache is valid).

= Some animations seem to conflict with the plugin, especially very early ones. What can I try? =
This plugin's two-step loading mechanism (inline shunt script + main player) is designed to handle early animations better. However, conflicts can still occur.
1.  **Try Disabling Shunt Interception:** Go to Animation Optimizer settings and uncheck "Enable jQuery Interception by Shunt Script" or "Enable GSAP Interception by Shunt Script" (or both). This stops the very early inline script from modifying jQuery/GSAP functions. Animations will then run as they normally would, without being queued by the shunt. The main player script will still use cached data for lazy loading if available.
2.  **Disable Main Detection Features:** If the issue persists or is related to how animations are detected, try disabling "Advanced jQuery Detection," "Advanced GSAP Detection," or "CSS Animation/Transition Detection" in the plugin settings. This will reduce what the plugin tries to cache.
3.  **Enable Debug Mode:** This will provide detailed logs in your browser's console from the plugin's scripts, which can help identify what the plugin is doing and where a conflict might arise.
4.  **Clear Cache:** Always clear the plugin's animation cache and any server/browser caches after changing settings.

= What types of animations can the plugin now detect? = (Same as v2.0.0)
= How does the CSS Animation/Transition detection work? = (Same as v2.0.0)
= What are the 'Advanced jQuery/GSAP Detection' settings for? = (Same as v2.0.0)
= What is Debug Mode and how do I use it? = (Updated for 2.1.0, still relevant)
= What should I do if an animation isn't detected? = (Same as v2.0.0)

== Performance Considerations ==
(Updated for 2.2.0)
*   **Shunt Script Size:** The inline shunt script's JavaScript code is minimal (gzipped to <0.5KB).
*   **Cached Data Size & Threshold:** The primary variable part of the inline script is the JSON data for cached animations. The "Inline Data Size Threshold" setting now controls whether this data is inlined or if the plugin falls back to an external script load to prevent HTML bloat.
*   The main player script is loaded asynchronously/deferred when the shunt is active.

== Screenshots ==
1.  The LHA Animation Optimizer settings page in the WordPress admin area, showing general, advanced detection, and shunt control settings.

== Changelog ==

= 2.2.0 (YYYY-MM-DD) =
*   **NEW:** Added "Inline Data Size Threshold (KB)" setting to control whether cached animation data is inlined with the shunt script or if an external player script is used as a fallback for large datasets. (Default: 5KB, 0 disables check).
*   **NEW:** Added admin settings for "Enable jQuery Interception by Shunt Script" and "Enable GSAP Interception by Shunt Script" to granularly control the shunt's behavior (Default: Enabled).
*   **ENHANCEMENT:** Shunt script (`lha-inline-shunt-logic.js`) now conditionally wraps jQuery/GSAP methods based on the new admin settings.
*   **ENHANCEMENT:** PHP logic in `class-lha-animation-optimizer-public.php` updated to implement the data size check and pass new interception settings to the shunt script.
*   **DOCUMENTATION:** Updated `readme.txt` and inline code comments for Phase 4 features.

= 2.1.0 =
*   NEW: Introduced a two-step loading mechanism for cached animation playback using an inline "shunt" script.
*   ENHANCEMENT: Minified the inline shunt script and implemented loading of `.min.js` or `.js` based on `SCRIPT_DEBUG` (Note: This SCRIPT_DEBUG behavior for shunt was later changed in 2.2.0 to always prioritize .min.js).
*   ENHANCEMENT: Added detailed debug logging to shunt and player scripts.
*   DOCUMENTATION: Updated readme and inline comments for Phase 3.

= 2.0.0 =
*   NEW: Comprehensive Animation Detection & Caching Overhaul.
*   NEW: Configurable Detection Mechanisms.
*   NEW: Debug Mode for detector script.
*   ENHANCEMENT: Player script updated for new animation types.
*   ENHANCEMENT: Admin panel updates and reliable cache invalidation.
*   ENHANCEMENT: Performance refinements in detector script.

= 1.1.0 =
*   NEW: Automatic Animation Caching for jQuery (`.animate()`) and GSAP.
*   NEW: "Clear Animation Cache" button.
*   ENHANCEMENT: Cache version updated on settings save.

= 1.0.0 =
*   Initial release.

== Upgrade Notice ==

= 2.2.0 =
This version adds more control over the inline shunt script behavior, including a data size threshold to prevent excessive inline script size and options to disable jQuery/GSAP interception by the shunt if needed for compatibility. The shunt script now always attempts to load the minified version first, falling back to the non-minified if necessary, regardless of SCRIPT_DEBUG.

= 2.1.0 =
This version introduces an optimized two-step loading mechanism for cached animations using an inline "shunt" script. This should improve the handling of animations that fire very early in the page load and further reduce the initial script footprint on cached views. Debug mode has also been enhanced.

= 2.0.0 =
This is a major update that significantly expands the plugin's animation detection capabilities. Please review the new configuration options.

== Known Limitations/Considerations ==
(Same as v2.1.0)
*   **Detection Accuracy:** Highly complex or unusually coded JS animations, or very intricate CSS interactions might not be fully captured.
*   **Performance Trade-offs:** MutationObserver for CSS detection can be resource-intensive on highly dynamic sites.
*   **Complex Callbacks/Logic:** Callbacks within JS animations are not replicated.
*   **Dynamic Selectors:** Cached selectors might become stale if IDs/classes change frequently without cache clearing.

== Support ==

For support, please use the WordPress.org support forums for this plugin.
