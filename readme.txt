=== LHA Animation Optimizer ===
Contributors: Your Name or Company
Tags: animation, optimize, performance, lazy load, jquery, gsap, css, admin, settings
Requires at least: 5.8
Tested up to: 6.5.3
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize your website's animations for better performance and user experience. Features lazy loading for CSS animations, experimental jQuery and GSAP optimizations, a CSS Animation Analyzer, and granular control over where and how optimizations are applied.

== Description ==

LHA Animation Optimizer enhances your website's performance by providing advanced control over animations.

**Key Features:**

*   **Lazy Loading for CSS Animations:** Only load animations when they are visible in the viewport, significantly improving initial page load times.
*   **Advanced Tabbed Settings UI:** A clear and organized settings panel with multiple tabs for easy configuration.
*   **Global Enable/Disable:** A master switch to quickly turn all plugin optimizations on or off.
*   **Import/Export Settings:** Easily back up, restore, or migrate your plugin configuration between sites.
*   **jQuery `.animate()` Optimization (Experimental):**
    *   Provides options to potentially optimize jQuery-based animations.
    *   **Safe Mode:** Minimal intervention, primarily for compatibility with lazy loading.
    *   **Aggressive Mode:** Attempts to convert jQuery animations to more performant CSS transforms (use with extreme caution and test thoroughly).
*   **GSAP `prefers-reduced-motion` Helper (Experimental):**
    *   If GSAP is detected on your site, this feature attempts to pause all GSAP ScrollTrigger animations when a user has `prefers-reduced-motion` enabled in their system or browser settings, enhancing accessibility.
*   **CSS Animation Analyzer Tool:**
    *   Analyze CSS from a URL or direct input to identify animation properties.
    *   Provides a basic count of `animation` and `transition` properties.
    *   Identifies potentially non-performant animated properties, long animation durations, usage of `will-change`, and infinite iteration counts. (Note: This tool is regex-based and has limitations with complex CSS or linked stylesheets not directly embedded in `<style>` tags of the analyzed URL).
*   **Granular Lazy Loading Controls:**
    *   **Primary Include Selector:** Define the main CSS selector for elements to target for lazy loading (default: `.lha-animation-target`).
    *   **Exclude Selectors:** Specify CSS selectors to exclude from lazy loading, even if they match the primary selector.
    *   **Critical Animation Selectors:** Specify CSS selectors for animations that should load immediately and bypass lazy loading (e.g., for above-the-fold critical content).
*   **Page/Post Type Targeting Rules:**
    *   Apply optimizations globally across your entire site.
    *   Conditionally apply optimizations to specific post types or a comma-separated list of Page/Post IDs.
    *   Exclude specific Page/Post IDs from all optimizations.
*   **Statistics Tracking:**
    *   Collects basic, anonymous statistics on how many animations are lazy-loaded and on how many page loads.
    *   View these statistics in the admin area and reset them if needed.

This plugin aims to provide a balance between animation richness and web performance, giving you tools to make informed decisions about your site's animations.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the 'Animation Optimizer' settings page in your WordPress admin menu to configure the plugin.
4.  For lazy loading CSS animations, ensure your animated elements have the class `.lha-animation-target` (or your configured "Primary Lazy Load Selector") and that the animation itself is defined to start when an additional class, `.lha-animate-now`, is applied. For example:
    ```css
    .my-animated-element {
        opacity: 0;
        transform: translateY(20px);
        /* Other initial styles */
    }
    .my-animated-element.lha-animate-now {
        opacity: 1;
        transform: translateY(0);
        animation: my-animation 1s forwards;
        /* Or transition: all 1s; */
    }
    @keyframes my-animation {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    ```
5.  Review the "Targeting Rules" tab to define where optimizations should apply.
6.  Explore other settings tabs for jQuery, GSAP, and CSS analysis tools.

== Frequently Asked Questions ==

= How does Lazy Loading for CSS Animations work? =

The plugin uses the IntersectionObserver API (with a fallback for older browsers) to detect when an element targeted by the "Primary Lazy Load Selector" (default: `.lha-animation-target`) enters the viewport. When it does, the plugin adds the class `.lha-animate-now` to that element. You need to define your CSS animations to start when this `.lha-animate-now` class is present.

= How do the new lazy load selectors (include, exclude, critical) interact? =

1.  **Critical Selectors:** Elements matching these selectors will animate immediately on page load and will *not* be lazy-loaded, regardless of other settings. This is for above-the-fold or essential animations.
2.  **Primary Lazy Load Selector:** Elements matching this selector are candidates for lazy loading.
3.  **Exclude Selectors:** If an element matches the Primary Lazy Load Selector BUT ALSO matches an Exclude Selector, it will *not* be lazy-loaded and will animate based on its original CSS (typically on page load).

The order of precedence is: Critical > Exclude > Include.

= How does the jQuery `.animate()` optimization work? =

This feature is **experimental**. When enabled, it intercepts calls to jQuery's `.animate()` function.
*   **Safe Mode:** Currently makes minimal changes, primarily intended to ensure compatibility with other plugin features like lazy loading (though direct integration is complex). It mostly calls the original jQuery `.animate()` function.
*   **Aggressive Mode:** This mode *attempts* to convert jQuery animations to more performant CSS transforms. This is highly experimental and can easily break complex jQuery animations or those that animate non-CSS properties. **Use with extreme caution and test thoroughly.**
It's generally recommended to use modern CSS animations or libraries like GSAP for new animation work.

= What does the GSAP 'Respect prefers-reduced-motion' option do? =

This **experimental** feature checks if the GSAP animation library is used on your site. If it is, and this option is enabled, the plugin will attempt to use GSAP's `matchMedia` utility to pause all GSAP ScrollTrigger animations when a user has indicated a preference for reduced motion in their operating system or browser settings. This is an accessibility enhancement. However, it's experimental because it globally affects ScrollTrigger instances and might not be suitable for all site designs or GSAP implementations.

= How do I use the CSS Analyzer? =

Navigate to the "CSS Optimizations" tab in the plugin settings. You can either:
1.  Enter a URL of a page on your site (or any public URL). The tool will attempt to fetch and analyze inline `<style>` tags and linked stylesheets (from the same domain).
2.  Paste CSS code directly into the textarea.
Click "Analyze CSS". The tool will provide a basic report including:
*   A count of `animation:` and `transition:` properties found.
*   Warnings for potentially non-performant CSS properties used in rules that also define animations/transitions.
*   Notices for long animation durations (>1.5s).
*   Information on `will-change` usage.
*   Detection of infinite animations.
The analyzer is regex-based and has limitations, especially with complex CSS or heavily nested/imported stylesheets. It's a helper tool, not a definitive parser.

= How can I apply optimizations only to specific posts or pages? =

Go to the "Targeting Rules" tab in the plugin settings.
1.  Set "Optimization Scope" to "Apply only on specific content types or IDs."
2.  **Target Post Types:** Select the checkboxes for the post types (e.g., Posts, Pages) where you want optimizations to run.
3.  **Target Page/Post IDs:** Enter a comma-separated list of specific Page or Post IDs where optimizations should apply.
4.  **Exclude Page/Post IDs:** You can also specify IDs to always exclude, even if they fall under a targeted post type or are listed in "Target Page/Post IDs".

= What do the statistics mean? =

Under the "Statistics" tab, you can see:
*   **Total Animations Lazy-Loaded:** The total count of animations that have been successfully lazy-loaded by the plugin since stats were last reset.
*   **Total Page Loads with Lazy-Loading:** The number of page loads where at least one animation was processed by the lazy loader.
*   **Stats Last Reset:** The date and time when the statistics were last reset.
These stats provide a basic insight into how often the lazy loading feature is being utilized on your site.

= How do I use the Import/Export settings feature? =

Navigate to the "Import/Export" tab:
*   **Export:** Click the "Export Settings" button. This will download a `.json` file containing all your current LHA Animation Optimizer settings.
*   **Import:** Click "Choose File", select a previously exported `.json` settings file, and then click the main "Save Settings" button. The settings from the file will be loaded and applied. This is useful for backups or migrating configurations.

= Is this plugin compatible with Elementor (or other page builders)? =

LHA Animation Optimizer can be used on sites built with Elementor or other page builders, but with some important considerations:
*   **Elementor's Built-in Animations:** Elementor Pro has its own "Motion Effects" (including entrance animations, scrolling effects, etc.) which are already optimized and/or scroll-triggered. You should **avoid** applying LHA Optimizer's lazy-loading features (e.g., by adding `.lha-animation-target` class) to elements already animated by Elementor's built-in Motion Effects, as this can lead to conflicts. Use Elementor's controls for those animations.
*   **Custom Animations within Page Builders:** If you add custom CSS animations to elements within a page builder (e.g., to a custom HTML widget, or via custom CSS fields on an Elementor element that isn't using Elementor's own Motion Effects), then LHA Optimizer's lazy loading can be beneficial for those.
*   **jQuery/GSAP Optimizations:** If your page builder or its addons use jQuery `.animate()` or GSAP, the experimental optimization features in LHA Optimizer *could* interact. Test thoroughly if you enable these. Elementor itself respects `prefers-reduced-motion` for its effects.
*   **Targeting Rules:** Use LHA Optimizer's "Targeting Rules" to include or exclude specific pages built with page builders if needed.

In general, use LHA Optimizer for animations not already managed by your page builder's internal animation system.

== Screenshots ==

1.  The General Settings tab showing global enable/disable.
2.  The Lazy Loading tab, highlighting the primary selector, exclude selectors, and critical selectors fields.
3.  The JavaScript Animations tab, showing options for jQuery and GSAP optimizations.
4.  The CSS Optimizations tab, displaying the CSS Animation Analyzer tool interface.
5.  The Targeting Rules tab, illustrating the scope selection and conditional input fields.
6.  The Statistics tab, showing example usage data.
7.  The Import/Export tab.

== Changelog ==

= 2.0.0 - YYYY-MM-DD =
*   **NEW:** Complete settings UI overhaul with a tabbed interface.
*   **NEW:** Global enable/disable switch for all plugin optimizations.
*   **NEW:** Import/Export functionality for plugin settings.
*   **NEW:** Lazy Loading - Added "Primary Lazy Load Selector" to customize the main animation target class.
*   **NEW:** Lazy Loading - Added "Exclude Selectors from Lazy Loading" textarea for more granular control.
*   **NEW:** Lazy Loading - Added "Critical Animation Selectors" textarea to specify animations that should load immediately, bypassing lazy load.
*   **NEW:** Targeting Rules - Added "Optimization Scope" (global/conditional), "Target Post Types", "Target Page/Post IDs", and "Exclude Page/Post IDs" for precise control over where optimizations apply.
*   **NEW:** JavaScript Animations Tab:
    *   Experimental jQuery `.animate()` optimization with "Safe" and "Aggressive" modes.
    *   Experimental GSAP `prefers-reduced-motion` helper to pause ScrollTrigger animations.
*   **NEW:** CSS Optimizations Tab with a "CSS Animation Analyzer" tool (AJAX-based) to inspect CSS from a URL or direct input for animation properties and potential issues.
*   **NEW:** Statistics Tab - Tracks and displays basic usage statistics for lazy-loaded animations, with a reset option.
*   ENHANCEMENT: Updated public JavaScript to conditionally load modules based on their respective settings.
*   ENHANCEMENT: Improved sanitization for all settings.
*   ENHANCEMENT: Admin JavaScript for better UX on settings pages (e.g., conditional field display).
*   DEV: Added admin-specific CSS and JS enqueueing.
*   DEV: Added AJAX handlers for CSS Analyzer and Statistics updates with nonce security.

= 1.0.0 - YYYY-MM-DD =
*   Initial release. Features include lazy loading for CSS animations based on the `.lha-animation-target` class and IntersectionObserver. Basic settings for enabling/disabling lazy loading and setting IntersectionObserver threshold.

== Upgrade Notice ==

= 2.0.0 =
This is a major update with significant new features and a new settings UI. Please review your settings after upgrading, especially the new "Targeting Rules" and "Lazy Loading" selectors if you had custom needs. It's recommended to backup your settings using the new Import/Export feature before upgrading a live site, or test in a staging environment.

== Credits ==

This plugin was created by Your Name/Company.

== Support ==

For support, please use the WordPress.org support forums for this plugin.
