=== LHA Animation Optimizer ===
Contributors: LHA Plugin Author
Tags: animation, performance, optimization, jquery, gsap, css animation, css transition, lazy load, mutationobserver, inline script
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimizes web animations for speed and performance by lazy loading, and by detecting and caching JavaScript-based and CSS-based animations. Now features an advanced two-step loading mechanism for cached animations.

== Description ==

LHA Animation Optimizer significantly enhances your website's performance by intelligently managing how and when animations are loaded and played. It provides a comprehensive suite of tools for animation optimization:

1.  **Lazy Loading of Animations:** All animations (CSS, jQuery, GSAP, etc.) associated with elements having the `.lha-animation-target` class (or elements with detected animations) will only play when the element scrolls into the viewport. This prevents a flood of animations from playing simultaneously on page load, improving perceived performance and reducing initial processing.

2.  **Automatic Animation Caching (Enhanced in 2.0.0, Refined in 2.1.0):** The plugin automatically detects a wide range of animations on a user's first visit (or when the cache is empty/invalidated). These include jQuery, GSAP, CSS Keyframe Animations, and CSS Transitions.
    These detected animation details are stored in a cache. On subsequent page views, if a valid cache exists, a new **two-step loading mechanism** is used:
    *   A very small inline "shunt" script is added to the page header. This script contains the cached animation data and settings. Its primary roles are to intercept any animation calls (jQuery/GSAP) that occur very early in the page load (e.g., from other inline scripts) and to dynamically load the main animation player script.
    *   The main player script (`lha-animation-optimizer-public.js`) then loads asynchronously. Once loaded, it restores any intercepted animation functions to their original state, processes the queue of early animation calls (ensuring they execute correctly), and then manages the initialization and lazy-loaded playback of all cached animations.
    This two-step approach minimizes the initial HTML impact, ensures even very early or inline animations can be captured and managed, and optimizes the overall script loading strategy for cached views.

The plugin provides a comprehensive admin settings page to control lazy loading, animation detection mechanisms, and a debug mode for troubleshooting.

== Features ==

*   **Lazy Loading:** Animations only play when elements enter the viewport using IntersectionObserver.
*   **Configurable Threshold:** Set what percentage of an element must be visible to trigger its animation.
*   **Comprehensive Automatic Animation Caching:**
    *   **Advanced jQuery Detection:** Captures animations from `.animate()` and common effects like `fadeIn`, `slideUp`, etc.
    *   **Advanced GSAP Detection:** Identifies tweens created with `gsap.to()`, `gsap.fromTo()`, etc., and those added to timelines via `Timeline.prototype.add`. Captures "fromTo" states and stagger properties.
    *   **CSS Animation & Transition Detection:** Uses `MutationObserver` to detect CSS keyframe animations and transitions.
    *   Caches detailed animation data.
*   **Two-Step Inline Player for Cached Animations (New in 2.1.0):**
    *   When cache is valid, a minimal inline "shunt" script is injected into the page header.
    *   This shunt script holds preloaded animation data/settings, queues early jQuery/GSAP calls, and dynamically loads the main player script.
    *   The main player script then restores original animation functions, processes the queue, and handles playback.
    *   Benefit: Improves handling of early/inline animations and reduces render-blocking JavaScript from the main player on cached views.
*   **Configurable Detection Mechanisms:** Admin options to enable/disable "Advanced jQuery Detection," "Advanced GSAP Detection," and "CSS Animation/Transition Detection".
*   **Debug Mode:** Option for detailed console logging from both detector and player scripts.
*   **Admin Settings Page:** Easy-to-use interface.
*   **Manual Cache Control:** "Clear Animation Cache" button.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Animation Optimizer" in the WordPress admin menu to configure the settings.

== Configuration ==

(Details for Lazy Loading, Threshold, Advanced Detection, Debug Mode, Clear Cache Button remain largely the same as v2.0.0)
*   **Enable Lazy Loading of Animations:** (Default: Enabled)
*   **Lazy Load Trigger Threshold:** (Default: 0.1)
*   **Enable Advanced jQuery Detection:** (Default: Enabled)
*   **Enable Advanced GSAP Detection:** (Default: Enabled)
*   **Enable CSS Animation/Transition Detection (MutationObserver):** (Default: Enabled)
*   **Enable Debug Mode:** (Default: Disabled)
*   **Clear Animation Cache Now Button.**

== How to Use ==

1.  **For Lazy Loading (General):** (Same as v2.0.0)
    *   Ensure "Enable Lazy Loading" is checked.
    *   Add class `lha-animation-target` for manual targeting.
    *   Animations trigger with class `lha-animate-now`.

2.  **For Automatic Animation Caching & Playback:**
    *   Ensure relevant detection features are enabled (defaults).
    *   **Detection Phase (Cache Miss):** `lha-animation-detector.js` runs, detects animations, and sends data for caching.
    *   **Playback Phase (Cache Hit - New in 2.1.0):**
        *   A small inline "shunt" script is placed in the `<head>`. This script contains all cached animation data and settings.
        *   It temporarily intercepts calls to jQuery and GSAP animation functions, queuing them if they occur before the main player loads.
        *   It then dynamically (asynchronously) loads the main player script (`lha-animation-optimizer-public.js`).
        *   Once the main player script loads, it restores the original jQuery/GSAP functions, processes any animations that were queued by the shunt, and then initializes all cached animations for lazy-loaded playback.
    *   Elements with detected animations are automatically treated as `lha-animation-target`s by the player.

== Frequently Asked Questions ==

= How does lazy loading work? = (Same as v2.0.0)

= How does Automatic Animation Caching work? (Updated for 2.1.0) =
On a user's first visit (or after the cache is cleared), `lha-animation-detector.js` loads. It wraps jQuery/GSAP methods and uses a `MutationObserver` to detect various animations, sending this data to be cached.

On subsequent page views with a valid cache:
1.  A very small **inline "shunt" script** is embedded directly into the HTML `<head>`. This script includes:
    *   All the cached animation data (`window.lhaPreloadedAnimations`).
    *   Plugin settings for the player (`window.lhaPreloadedSettings`).
    *   The URL of the main player script.
2.  The shunt script immediately:
    *   Sets up a queue (`window.lhaEarlyAnimationQueue`) for any animation calls that happen very early.
    *   Temporarily replaces (shunts) common jQuery and GSAP animation functions. If these functions are called by other scripts before the main player is ready, their details are put into the queue instead of executing immediately.
    *   Dynamically loads the main player script (`lha-animation-optimizer-public.js`) asynchronously.
3.  Once the main player script loads, it:
    *   Restores the original jQuery and GSAP functions that were shunted.
    *   Processes all animation calls from the `lhaEarlyAnimationQueue`, executing them with the now-restored original functions.
    *   Initializes all other cached animations, preparing them for lazy-loaded playback as usual.
This two-step process ensures that the initial page has minimal script impact while still capturing and managing animations that might fire very early.

= What types of animations can the plugin now detect? = (Same as v2.0.0)
*   jQuery: `.animate()`, `fadeIn`, `fadeOut`, `slideUp`, `slideDown`, `slideToggle`, `fadeTo`, `fadeToggle`.
*   GSAP: `gsap.to()`, `gsap.fromTo()`, tweens added via `Timeline.prototype.add`, "fromTo" states, stagger.
*   CSS Keyframe Animations and CSS Transitions.

= How does the CSS Animation/Transition detection work? = (Same as v2.0.0)

= What are the 'Advanced jQuery/GSAP Detection' settings for? = (Same as v2.0.0)

= What is Debug Mode and how do I use it? (Updated for 2.1.0) =
When enabled, both the `lha-animation-detector.js` (on detection runs) and the `lha-animation-optimizer-public.js` (player script, especially when processing shunt queue or preloaded data) will output detailed logs to the browser console. This helps trace the entire lifecycle from detection to shunt to playback.

= What should I do if an animation isn't detected? = (Same as v2.0.0)

== Performance Considerations ==

*   **Shunt Script Size:** The inline shunt script's own JavaScript code is very small (manually minified, it's approx 1.1KB, gzipped to **<0.5KB**).
*   **Cached Data Size:** The main variable part of the inline script's size is the JSON data for `window.lhaPreloadedAnimations` (the cached animation details) and `window.lhaPreloadedSettings`. For sites with many complex animations, this JSON data can increase the size of the inline script. However, this is generally still more performant than loading the full detector script and re-detecting animations on every page load.
*   The main player script is loaded asynchronously/deferred when the shunt is active, minimizing its impact on initial page rendering.

== Screenshots ==

1.  The LHA Animation Optimizer settings page in the WordPress admin area, showing general and advanced detection settings.

== Changelog ==

= 2.1.0 (YYYY-MM-DD) =
*   **NEW:** Introduced a two-step loading mechanism for cached animation playback.
    *   When cache is valid, a minimal inline "shunt" script (`lha-inline-shunt-logic.js` / `.min.js`) is injected into `wp_head`.
    *   The shunt script holds preloaded animation data/settings, intercepts early jQuery/GSAP calls by queuing them, and dynamically loads the main player script.
    *   The main player script (`lha-animation-optimizer-public.js`) now restores original animation functions, processes the early animation queue, then initializes cached animations and lazy loading.
*   **ENHANCEMENT:** Minified the inline shunt script and implemented loading of `.min.js` or `.js` based on `SCRIPT_DEBUG` in PHP.
*   **ENHANCEMENT:** Added more detailed debug logging to both the shunt script and the main player script to trace the new loading and execution flow.
*   **DOCUMENTATION:** Updated `readme.txt` and inline code comments to reflect the Phase 3 two-step loading architecture.

= 2.0.0 =
*   NEW: Comprehensive Animation Detection & Caching Overhaul (jQuery specific methods, GSAP Timeline.add, CSS Animations/Transitions via MutationObserver).
*   NEW: Configurable Detection Mechanisms (toggles for advanced jQuery, GSAP, CSS detection).
*   NEW: Debug Mode for detector script.
*   ENHANCEMENT: Player script updated for new animation types.
*   ENHANCEMENT: Admin panel updates for new settings and reliable cache invalidation.
*   ENHANCEMENT: Performance refinements in detector script.
*   DOCUMENTATION: Major updates to readme and inline comments.

= 1.1.0 =
*   NEW: Implemented Automatic Animation Caching for jQuery (`.animate()`) and GSAP animations.
*   NEW: Added "Clear Animation Cache" button.
*   ENHANCEMENT: Cache version updated on settings save.
*   ENHANCEMENT: Improved sanitization and selector generation.
*   REFACTOR: Consolidated public script enqueuing.

= 1.0.0 =
*   Initial release.

== Upgrade Notice ==

= 2.1.0 =
This version introduces an optimized two-step loading mechanism for cached animations using an inline "shunt" script. This should improve the handling of animations that fire very early in the page load and further reduce the initial script footprint on cached views. Debug mode has also been enhanced.

= 2.0.0 =
This is a major update that significantly expands the plugin's animation detection capabilities to include more jQuery methods, more GSAP scenarios, and CSS animations/transitions. It also introduces new admin settings for finer control over detection and a debug mode. Please review the new configuration options and test your site's animations after updating.

== Known Limitations/Considerations ==
(Same as v2.0.0, but the new loading mechanism aims to mitigate some timing issues for early animations)
*   **Detection Accuracy:** Highly complex or unusually coded JS animations, or very intricate CSS interactions might not be fully captured.
*   **Performance Trade-offs:** MutationObserver for CSS detection can be resource-intensive on highly dynamic sites.
*   **Complex Callbacks/Logic:** Callbacks within JS animations are not replicated.
*   **Dynamic Selectors:** Cached selectors might become stale if IDs/classes change frequently without cache clearing.

== Support ==

For support, please use the WordPress.org support forums for this plugin.
