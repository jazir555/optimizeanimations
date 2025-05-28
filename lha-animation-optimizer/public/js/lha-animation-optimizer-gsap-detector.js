(function() {
    'use strict';

    const LHA_GSAP_DETECTOR = {
        LOGGING_ENDPOINT: ajaxurl, // WordPress AJAX URL
        NONCE: '', // This will be localized
        MAX_ATTEMPTS: 20, // Approx 10 seconds
        RETRY_INTERVAL: 500, // Milliseconds
        attempts: 0,
        gsapVersion: null,
        initialized: false,

        init: function() {
            if (typeof lhaAnimationOptimizerGSAPSettings === 'undefined' || !lhaAnimationOptimizerGSAPSettings.nonce) {
                console.warn('LHA GSAP Detector: Nonce not available.');
                return;
            }
            this.NONCE = lhaAnimationOptimizerGSAPSettings.nonce;
            this.gsapVersion = lhaAnimationOptimizerGSAPSettings.gsapVersion || null; // Optional: if we can get version

            this.tryInitializeWrappers();
        },

        tryInitializeWrappers: function() {
            if (this.initialized) return;

            if (typeof window.gsap !== 'undefined' && typeof window.gsap.to === 'function') {
                this.logMessage('GSAP global object found. Attempting to wrap methods.');
                this.wrapGsapMethods();
                this.initialized = true;
            } else {
                this.attempts++;
                if (this.attempts < this.MAX_ATTEMPTS) {
                    setTimeout(this.tryInitializeWrappers.bind(this), this.RETRY_INTERVAL);
                } else {
                    this.logMessage('GSAP global object not found after ' + this.MAX_ATTEMPTS + ' attempts.');
                }
            }
        },

        wrapGsapMethods: function() {
            const self = this;

            // Wrap gsap.to
            const originalGsapTo = window.gsap.to;
            window.gsap.to = function(targets, vars) {
                try {
                    self.captureAnimation('to', targets, vars);
                } catch (e) {
                    self.logMessage('Error capturing gsap.to: ' + e.message, true);
                }
                return originalGsapTo.apply(this, arguments);
            };

            // Wrap gsap.from
            const originalGsapFrom = window.gsap.from;
            window.gsap.from = function(targets, vars) {
                try {
                    self.captureAnimation('from', targets, vars);
                } catch (e) {
                    self.logMessage('Error capturing gsap.from: ' + e.message, true);
                }
                return originalGsapFrom.apply(this, arguments);
            };

            // Wrap gsap.fromTo
            const originalGsapFromTo = window.gsap.fromTo;
            window.gsap.fromTo = function(targets, fromVars, toVars) {
                 try {
                    // For fromTo, 'toVars' contains the main animation properties we are interested in,
                    // similar to 'vars' in .to() or .from(). fromVars defines the starting state.
                    self.captureAnimation('fromTo', targets, toVars, fromVars);
                } catch (e) {
                    self.logMessage('Error capturing gsap.fromTo: ' + e.message, true);
                }
                return originalGsapFromTo.apply(this, arguments);
            };

            // Wrap gsap.timeline
            const originalGsapTimeline = window.gsap.timeline;
            window.gsap.timeline = function(timelineVars) {
                const timelineInstance = originalGsapTimeline.apply(this, arguments);
                try {
                    self.wrapTimelineMethods(timelineInstance);
                } catch (e) {
                    self.logMessage('Error wrapping timeline methods: ' + e.message, true);
                }
                return timelineInstance;
            };
             self.logMessage('GSAP methods wrapped.');
        },

        wrapTimelineMethods: function(timelineInstance) {
            const self = this;

            // Wrap timeline.to
            const originalTimelineTo = timelineInstance.to;
            timelineInstance.to = function(targets, vars, position) {
                try {
                    self.captureAnimation('timeline.to', targets, vars, null, timelineInstance, position);
                } catch (e) {
                    self.logMessage('Error capturing timeline.to: ' + e.message, true);
                }
                return originalTimelineTo.apply(this, arguments);
            };

            // Wrap timeline.from
            const originalTimelineFrom = timelineInstance.from;
            timelineInstance.from = function(targets, vars, position) {
                try {
                    self.captureAnimation('timeline.from', targets, vars, null, timelineInstance, position);
                } catch (e) {
                    self.logMessage('Error capturing timeline.from: ' + e.message, true);
                }
                return originalTimelineFrom.apply(this, arguments);
            };
            
            // Wrap timeline.fromTo
            const originalTimelineFromTo = timelineInstance.fromTo;
            timelineInstance.fromTo = function(targets, fromVars, toVars, position) {
                try {
                    self.captureAnimation('timeline.fromTo', targets, toVars, fromVars, timelineInstance, position);
                } catch (e) {
                    self.logMessage('Error capturing timeline.fromTo: ' + e.message, true);
                }
                return originalTimelineFromTo.apply(this, arguments);
            };
        },

        captureAnimation: function(type, targets, vars, fromVars = null, timelineInstance = null, position = null) {
            const targetSelector = this.getTargetSelector(targets);
            if (!targetSelector) {
                // this.logMessage('Could not determine selector for GSAP target.', true);
                return; // Cannot log without a selector
            }

            const animationData = {
                type: 'gsap',
                gsap_type: type,
                selector: targetSelector,
                duration: vars.duration || (timelineInstance ? timelineInstance.duration() : undefined) || (typeof gsap !== 'undefined' ? gsap.defaults().duration : undefined),
                delay: vars.delay || 0,
                ease: this.getEaseString(vars.ease),
                properties: this.extractAnimatableProperties(vars),
                // TODO: Add from_properties if fromVars is available
                // TODO: Add timeline context if timelineInstance is available (e.g., timeline duration, position of this tween)
                // TODO: Add position for timeline tweens
                source_url: window.location.href,
            };
            
            if (fromVars) {
                animationData.from_properties = this.extractAnimatableProperties(fromVars);
            }
            if (timelineInstance) {
                // We might want to log a unique ID for the timeline instance later if we want to group tweens by timeline
            }
            if (position !== null && position !== undefined) {
                animationData.position = position;
            }


            // Debounce or throttle AJAX calls if many animations are detected quickly
            this.sendToServer(animationData);
        },

        getTargetSelector: function(targets) {
            if (typeof targets === 'string') {
                return targets;
            }
            if (targets instanceof Element) {
                // Attempt to generate a unique selector
                if (targets.id) {
                    return '#' + targets.id;
                }
                let selector = targets.tagName.toLowerCase();
                if (targets.classList.length > 0) {
                    selector += '.' + Array.from(targets.classList).join('.');
                }
                // This is a very basic selector generator. Might need improvement for robustness.
                // Consider if multiple elements on the page match this basic selector.
                // For now, this is a starting point.
                // A more robust solution might involve traversing up the DOM or using a library.
                // If document.querySelectorAll(selector).length > 1, this is not unique.
                return selector;
            }
            // Add more complex target handling if needed (e.g., NodeList, jQuery objects if GSAP is used with them)
            return null; 
        },

        extractAnimatableProperties: function(vars) {
            const animatableProps = {};
            const reservedKeys = ['duration', 'delay', 'ease', 'onComplete', 'onUpdate', 'onStart', 'stagger', 'repeat', 'yoyo', 'repeatDelay', 'lazy', 'overwrite', 'immediateRender', 'callbackScope', 'keyframes', 'svgOrigin', 'transformOrigin', 'cycle'];
            for (const key in vars) {
                if (vars.hasOwnProperty(key) && !reservedKeys.includes(key) && typeof vars[key] !== 'function') {
                    // Further check if vars[key] is a valid CSS value or a GSAP-specific value (e.g. xPercent)
                    // For now, accept most things that are not functions or core GSAP control properties
                    animatableProps[key] = vars[key];
                }
            }
            return JSON.stringify(animatableProps); // Store as JSON string
        },

        getEaseString: function(ease) {
            if (typeof ease === 'string') {
                return ease; // e.g., "power1.out"
            }
            if (typeof ease === 'function') {
                // GSAP ease functions don't have a standard string representation we can directly use
                // for CSS conversion later. We might need to map common ones or just log 'custom'.
                return 'custom'; 
            }
            return 'none'; // Default or if undefined
        },

        sendToServer: function(data) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.LOGGING_ENDPOINT, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            // xhr.onload = function() { // Handle response if needed };
            
            const params = new URLSearchParams();
            params.append('action', 'lha_log_gsap_animation');
            params.append('nonce', this.NONCE);
            params.append('animation_data', JSON.stringify(data));
            
            xhr.send(params.toString());
            this.logMessage('GSAP animation data sent: ' + data.selector);
        },

        logMessage: function(message, isError = false) {
            // For debugging. Can be removed or tied to a debug setting later.
            // console.log('LHA GSAP Detector: ' + message);
        }
    };

    // Initialize after DOM is ready, or use a more robust way to wait for GSAP
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        LHA_GSAP_DETECTOR.init();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            LHA_GSAP_DETECTOR.init();
        });
    }

})();
