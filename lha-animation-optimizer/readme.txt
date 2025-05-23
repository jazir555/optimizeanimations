=== LHA Animation Optimizer ===
Contributors: Jules
Tags: animation, performance, optimize, lazy load, speed, optimize animations, improve animation performance
Requires at least: 5.0
Tested up to: 6.8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize animations for speed and performance by lazy-loading them as they enter the viewport.

== Description ==

LHA Animation Optimizer enhances your website's performance by optimizing when animations are loaded and triggered. The primary feature in this version is the lazy loading of animations. Instead of all animations loading and potentially running as soon as the page loads (which can slow down initial page rendering and affect Core Web Vitals), this plugin allows animations to be deferred until the element they are attached to scrolls into the user's viewport.

This is achieved by using the efficient IntersectionObserver API. You can control which elements are targeted for lazy loading by adding a specific CSS class. The plugin provides a settings page in the WordPress admin area where you can enable/disable the lazy loading feature and configure the visibility threshold for triggering the animations.

Future versions may include more advanced techniques for direct CSS and JavaScript animation optimization.

== Installation ==

1.  Upload the `lha-animation-optimizer` folder to the `/wp-content/plugins/` directory on your WordPress installation.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the "Animation Optimizer" settings page in your WordPress admin menu.
4.  Ensure "Enable Lazy Loading of Animations" is checked (this is the default).
5.  Adjust the "Lazy Load Trigger Threshold" if needed. This value (between 0.0 and 1.0) determines what percentage of an element must be visible before its animation is triggered. For example, 0.1 means 10% visible, 0.5 means 50% visible.
6.  For elements on your site that you wish to lazy-load:
    *   Add the CSS class `lha-animation-target` to the HTML element.
    *   Ensure your theme or custom CSS is set up so that the animation for this element is triggered when the class `lha-animate-now` is added to it. For example, if your element initially has `opacity: 0; transform: translateY(20px);`, your animation CSS might look like:
        `.lha-animation-target.lha-animate-now {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }`

== Frequently Asked Questions ==

= How do I use the lazy loading feature? =

1.  Make sure the plugin is activated and "Enable Lazy Loading of Animations" is checked in the "Animation Optimizer" settings page.
2.  For any HTML element you want to animate as it scrolls into view, add the CSS class `lha-animation-target` to that element.
3.  Your theme's or plugin's CSS should define the actual animation. The animation should be set up to start when the class `lha-animate-now` is added to an element that already has `lha-animation-target`. The plugin will add the `lha-animate-now` class when the element becomes visible.

= What types of animations does this optimize? =

Currently, this plugin focuses on lazy loading animations. This means it controls *when* an animation (CSS or JavaScript-triggered by class addition) starts, based on viewport visibility. It doesn't alter the animation code itself. Future versions may explore more direct optimization techniques for CSS keyframes or JavaScript animation patterns.

= Is it compatible with my theme? =

Generally, yes. The plugin uses standard WordPress features for its settings and relies on you adding CSS classes to your elements. The key is that your theme's or custom CSS animations must be designed to be triggered by the addition of a CSS class (specifically, `lha-animate-now` being added to your `lha-animation-target` elements). If your animations are purely time-based or triggered by other JavaScript events not related to class changes, this plugin won't directly affect them in its current version.

== Screenshots ==

1.  The LHA Animation Optimizer settings page in the WordPress admin area, showing options for enabling lazy loading and setting the IntersectionObserver threshold. (Actual screenshot to be added if plugin were submitted to repository)

== Changelog ==

= 1.0.0 =
* Initial release.
* Feature: Lazy loading of animations using IntersectionObserver for elements with the `lha-animation-target` class.
* Feature: Admin settings page ("Animation Optimizer") to enable/disable lazy loading and configure the visibility threshold for triggering animations.
* Feature: Public-facing JavaScript to handle IntersectionObserver logic and add `lha-animate-now` class to visible elements.
* Feature: Basic plugin structure based on WordPress Plugin Boilerplate principles for organization and maintainability.
* Feature: Internationalization support with a defined text domain and languages folder.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the LHA Animation Optimizer. Configure settings under the "Animation Optimizer" menu in your WordPress admin.

== Developer Notes ==

This plugin is built using an architecture inspired by the WordPress Plugin Boilerplate, promoting a clean, organized, and object-oriented structure. It adheres to WordPress coding standards and security best practices, including namespacing, proper hook usage, data sanitization/validation, nonces for admin forms, and output escaping. Internationalization is supported via the `lha-animation-optimizer` text domain.
The plugin uses `wp_localize_script` to pass settings from PHP to the public-facing JavaScript. The public JavaScript uses IntersectionObserver to efficiently detect when elements with the class `lha-animation-target` enter the viewport, at which point it adds the `lha-animate-now` class to trigger the CSS-defined animations.

This file is production-ready.The `readme.txt` file has been created.

Now for **Task 2: Update `LICENSE.txt`**.
I need the full text of the GPLv2 license. I will use `view_text_website` to fetch this.
