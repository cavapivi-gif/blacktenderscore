/**
 * BT Carousel — Swiper init for Elementor Container
 * Features: device selection, offset, responsive
 */
(function () {
    'use strict';

    var instances = new Map();
    var resizeTimeout = null;

    /**
     * Get current device based on window width
     */
    function getCurrentDevice(breakpoints) {
        var w = window.innerWidth;
        var mobile = breakpoints.mobile || 767;
        var tablet = breakpoints.tablet || 1024;

        if (w <= mobile) return 'mobile';
        if (w <= tablet) return 'tablet';
        return 'desktop';
    }

    /**
     * Check if carousel should be active on current device
     */
    function isActiveOnDevice(config) {
        var devices = config._devices || ['desktop', 'tablet', 'mobile'];
        var breakpoints = config._breakpoints || { mobile: 767, tablet: 1024 };
        var current = getCurrentDevice(breakpoints);
        return devices.indexOf(current) !== -1;
    }

    /**
     * Initialize carousel on a container
     */
    function initCarousel(container) {
        // Read config
        var configJson = container.getAttribute('data-bt-swiper');
        if (!configJson) return;

        var config;
        try {
            config = JSON.parse(configJson);
        } catch (e) {
            console.error('BT Carousel: Invalid JSON', e);
            return;
        }

        // Store config for resize handler
        container._btConfig = config;

        // Check device
        if (!isActiveOnDevice(config)) {
            destroyCarousel(container);
            return;
        }

        // Already initialized?
        if (container._btSwiper) return;

        // Find/create wrapper
        var wrapper = container.querySelector(':scope > .e-con-inner');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'swiper-wrapper';
            var children = container.querySelectorAll(':scope > .e-con, :scope > .elementor-widget');
            children.forEach(function (child) {
                wrapper.appendChild(child);
            });
            container.insertBefore(wrapper, container.firstChild);
        } else {
            wrapper.classList.add('swiper-wrapper');
        }

        // Find slides
        var slides = wrapper.querySelectorAll(':scope > .e-con, :scope > .elementor-widget');
        if (slides.length < 1) return;

        // Add swiper classes
        container.classList.add('swiper');
        slides.forEach(function (slide, i) {
            slide.classList.add('swiper-slide');
            slide.setAttribute('role', 'group');
            slide.setAttribute('aria-label', (i + 1) + ' sur ' + slides.length);
        });

        // Guard: disable loop if not enough slides
        var slidesPerView = config.slidesPerView || 1;
        if (config.loop && slides.length <= slidesPerView) {
            config.loop = false;
        }

        // Get element ID and parent
        var id = container.getAttribute('data-id');
        var parent = container.parentElement;
        var elementorWrapper = container.closest('.elementor-element');

        // Store offset config
        var offsetSides = config._offsetSides || 'none';
        var offsetBefore = config.slidesOffsetBefore || 0;
        var offsetAfter = config.slidesOffsetAfter || 0;

        // Move navigation arrows INSIDE container (they need to be inside for Swiper)
        var prevArrow = null;
        var nextArrow = null;
        if (config.navigation && parent) {
            prevArrow = parent.querySelector(':scope > .bt-arrow--prev');
            nextArrow = parent.querySelector(':scope > .bt-arrow--next');
            if (prevArrow) container.appendChild(prevArrow);
            if (nextArrow) container.appendChild(nextArrow);

            config.navigation = {
                prevEl: prevArrow || '.elementor-element-' + id + ' .bt-arrow--prev',
                nextEl: nextArrow || '.elementor-element-' + id + ' .bt-arrow--next'
            };
        }

        // Pagination — keep OUTSIDE container for "outside" position
        var paginationEl = null;
        if (config.pagination && parent) {
            paginationEl = parent.querySelector(':scope > .bt-pagination');
            // Do NOT move pagination inside - it stays as sibling for proper positioning
            config.pagination = {
                el: paginationEl || '.elementor-element-' + id + ' .bt-pagination',
                type: config.pagination,
                clickable: true
            };
        }

        // Keyboard
        config.keyboard = { enabled: true, onlyInViewport: true };

        // Clean internal props before passing to Swiper
        // Remove slidesOffsetBefore/After — we use padding + border-box instead
        var swiperConfig = Object.assign({}, config);
        delete swiperConfig._devices;
        delete swiperConfig._breakpoints;
        delete swiperConfig._offsetSides;
        delete swiperConfig.slidesOffsetBefore;
        delete swiperConfig.slidesOffsetAfter;

        console.log('BT Carousel config:', swiperConfig);

        // Init Swiper
        var swiper = new Swiper(container, swiperConfig);

        // Store instance for external access (both formats)
        container._btSwiper = swiper;
        container.btSwiper = swiper;
        instances.set(container, swiper);

        // AFTER Swiper init: apply offset via padding + border-box
        // Swiper uses clientWidth (excludes padding) to calculate slide width
        console.log('BT Carousel offset check:', { offsetSides: offsetSides, offsetBefore: offsetBefore, offsetAfter: offsetAfter });

        if (offsetSides && offsetSides !== 'none') {
            // Container overflow via data attribute (beats Elementor's .e-con specificity)
            container.setAttribute('data-bt-overflow', 'visible');

            // Apply box-sizing and padding BEFORE swiper.update()
            container.style.boxSizing = 'border-box';

            if (offsetSides === 'left' || offsetSides === 'both') {
                container.style.paddingLeft = offsetBefore + 'px';
                console.log('BT Carousel: paddingLeft set to', offsetBefore + 'px');
            }
            if (offsetSides === 'right' || offsetSides === 'both') {
                container.style.paddingRight = offsetAfter + 'px';
                console.log('BT Carousel: paddingRight set to', offsetAfter + 'px');
            }

            // Find first parent .e-con that is NOT e-child to clip overflow
            var clipParent = container.parentElement;
            while (clipParent) {
                if (clipParent.classList.contains('e-con') && !clipParent.classList.contains('e-child')) {
                    clipParent.style.setProperty('overflow', 'hidden', 'important');
                    console.log('BT Carousel: clipParent set', clipParent.getAttribute('data-id'));
                    break;
                }
                clipParent = clipParent.parentElement;
            }

            // Recalculate slide widths with new padding
            swiper.update();

            console.log('BT Carousel offset DONE:', {
                sides: offsetSides,
                boxSizing: container.style.boxSizing,
                paddingLeft: container.style.paddingLeft,
                paddingRight: container.style.paddingRight,
                clientWidth: container.clientWidth,
                offsetWidth: container.offsetWidth
            });
        }

        console.log('BT Carousel OK:', id, slides.length + ' slides');
    }

    /**
     * Destroy carousel on a container
     */
    function destroyCarousel(container) {
        var swiper = container._btSwiper;
        if (!swiper) return;

        swiper.destroy(true, true);
        container._btSwiper = null;
        container.btSwiper = null;
        instances.delete(container);

        // Remove swiper classes and reset attributes/styles
        container.classList.remove('swiper');
        container.removeAttribute('data-bt-overflow');
        container.style.removeProperty('box-sizing');
        container.style.removeProperty('padding-left');
        container.style.removeProperty('padding-right');

        var wrapper = container.querySelector('.swiper-wrapper');
        if (wrapper && wrapper.classList.contains('e-con-inner')) {
            wrapper.classList.remove('swiper-wrapper');
        }
        container.querySelectorAll('.swiper-slide').forEach(function (slide) {
            slide.classList.remove('swiper-slide');
            slide.removeAttribute('role');
            slide.removeAttribute('aria-label');
        });

        // Reset parent overflow
        var parentElement = container.closest('.elementor-element');
        if (parentElement) {
            parentElement.style.removeProperty('overflow');
        }

        console.log('BT Carousel destroyed:', container.getAttribute('data-id'));
    }

    /**
     * Re-evaluate all carousels on resize
     */
    function onResize() {
        document.querySelectorAll('[data-bt-swiper]').forEach(function (container) {
            var config = container._btConfig;
            if (!config) return;

            var shouldBeActive = isActiveOnDevice(config);
            var isActive = !!container._btSwiper;

            if (shouldBeActive && !isActive) {
                initCarousel(container);
            } else if (!shouldBeActive && isActive) {
                destroyCarousel(container);
            }
        });
    }

    /**
     * Debounced resize handler
     */
    function handleResize() {
        if (resizeTimeout) clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(onResize, 150);
    }

    /**
     * Initialize all carousels
     */
    function initAll() {
        document.querySelectorAll('[data-bt-swiper]').forEach(initCarousel);
    }

    /**
     * Wait for Swiper to be available
     */
    function waitForSwiper() {
        if (typeof Swiper === 'undefined') {
            setTimeout(waitForSwiper, 100);
            return;
        }
        initAll();
        window.addEventListener('resize', handleResize);
    }

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForSwiper);
    } else {
        waitForSwiper();
    }

    // Elementor frontend hook
    if (window.jQuery) {
        jQuery(window).on('elementor/frontend/init', function () {
            elementorFrontend.hooks.addAction('frontend/element_ready/container', function ($el) {
                var el = $el[0];
                if (el.hasAttribute('data-bt-swiper')) {
                    setTimeout(function () { initCarousel(el); }, 50);
                }
            });
        });
    }

    // Public API
    window.BtCarousel = {
        init: initAll,
        initElement: initCarousel,
        destroy: destroyCarousel,
        getInstance: function (el) { return instances.get(el); }
    };
})();
