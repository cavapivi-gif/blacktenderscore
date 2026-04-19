/**
 * BT Carousel — Swiper Integration for Elementor Container
 * Transforms Elementor Container children into full-featured carousel
 *
 * @package BlackTenders
 */

(function () {
    'use strict';

    var instances = new WeakMap();
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
     * Initialize Swiper on a container
     * @param {HTMLElement} container
     */
    function initSwiper(container) {
        // Already initialized?
        if (instances.has(container)) return;

        // Read config from data attribute
        var configAttr = container.dataset.btSwiper;
        if (!configAttr) return;

        var config;
        try {
            config = JSON.parse(configAttr);
        } catch (e) {
            console.warn('BT Carousel: Invalid config JSON', e);
            return;
        }

        // Store config for resize handler
        container._btConfig = config;

        // Check device
        if (!isActiveOnDevice(config)) {
            destroySwiper(container);
            return;
        }

        // Find the wrapper (e-con-inner for boxed containers, or container itself for full-width)
        var wrapper = container.querySelector(':scope > .e-con-inner');
        var isBoxed = !!wrapper;

        if (!wrapper) {
            // Full-width container: we need to wrap children in a swiper-wrapper div
            wrapper = document.createElement('div');
            wrapper.className = 'swiper-wrapper';

            // Move all child containers/widgets into wrapper
            var children = container.querySelectorAll(':scope > .e-con, :scope > .elementor-widget, :scope > .elementor-element');
            if (children.length === 0) return;

            children.forEach(function (child) {
                wrapper.appendChild(child);
            });

            // Insert wrapper as first child of container
            container.insertBefore(wrapper, container.firstChild);
        }

        // Find slides: direct children of wrapper that are containers or widgets
        var slides = wrapper.querySelectorAll(':scope > .e-con, :scope > .elementor-widget, :scope > .elementor-element');

        if (slides.length === 0) {
            console.warn('BT Carousel: No slides found in container');
            return;
        }

        // Add Swiper structure classes
        container.classList.add('swiper');

        if (isBoxed) {
            wrapper.classList.add('swiper-wrapper');
        }

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

        // Get parent and ID
        var id = container.getAttribute('data-id');
        var parent = container.parentElement;
        var elementorWrapper = container.closest('.elementor-element');

        // Store offset config
        var offsetSides = config._offsetSides || 'none';
        var offsetBefore = config.slidesOffsetBefore || 0;
        var offsetAfter = config.slidesOffsetAfter || 0;

        // Navigation arrows — find and move INSIDE container
        var prevBtn = null;
        var nextBtn = null;
        if (parent) {
            prevBtn = parent.querySelector(':scope > .bt-arrow--prev');
            nextBtn = parent.querySelector(':scope > .bt-arrow--next');
            if (prevBtn) container.appendChild(prevBtn);
            if (nextBtn) container.appendChild(nextBtn);
        }

        // Create arrows if not found
        if (config.navigation && !prevBtn && !nextBtn) {
            prevBtn = document.createElement('div');
            prevBtn.className = 'bt-arrow bt-arrow--prev';
            prevBtn.setAttribute('role', 'button');
            prevBtn.setAttribute('tabindex', '0');
            prevBtn.setAttribute('aria-label', 'Précédent');
            prevBtn.innerHTML = '<i class="eicon-chevron-left"></i>';
            container.appendChild(prevBtn);

            nextBtn = document.createElement('div');
            nextBtn.className = 'bt-arrow bt-arrow--next';
            nextBtn.setAttribute('role', 'button');
            nextBtn.setAttribute('tabindex', '0');
            nextBtn.setAttribute('aria-label', 'Suivant');
            nextBtn.innerHTML = '<i class="eicon-chevron-right"></i>';
            container.appendChild(nextBtn);
        }

        // Setup navigation config
        if (config.navigation && prevBtn && nextBtn) {
            config.navigation = {
                prevEl: prevBtn,
                nextEl: nextBtn,
            };
        } else {
            config.navigation = false;
        }

        // Pagination — keep OUTSIDE container (sibling), don't move inside
        var paginationEl = null;
        if (config.pagination && parent) {
            paginationEl = parent.querySelector(':scope > .bt-pagination');
            var paginationType = typeof config.pagination === 'string' ? config.pagination : 'bullets';
            config.pagination = {
                el: paginationEl || '.elementor-element-' + id + ' .bt-pagination',
                type: paginationType,
                clickable: true,
                dynamicBullets: paginationType === 'bullets' && slides.length > 7,
            };
        } else {
            config.pagination = false;
        }

        // Keyboard navigation
        config.keyboard = {
            enabled: true,
            onlyInViewport: true,
        };

        // Touch/drag settings
        config.touchEventsTarget = 'container';
        config.preventClicksPropagation = false;
        config.slideToClickedSlide = false;

        // Clean internal props before passing to Swiper
        // Remove slidesOffsetBefore/After — we use padding + border-box instead
        var swiperConfig = Object.assign({}, config);
        delete swiperConfig._devices;
        delete swiperConfig._breakpoints;
        delete swiperConfig._offsetSides;
        delete swiperConfig.slidesOffsetBefore;
        delete swiperConfig.slidesOffsetAfter;

        console.log('BT Carousel config:', swiperConfig);

        // Initialize Swiper
        try {
            var swiper = new Swiper(container, swiperConfig);
            instances.set(container, swiper);

            // Store for external access (both formats)
            container._btSwiper = swiper;
            container.btSwiper = swiper;

            // AFTER Swiper init: apply offset via padding + border-box
            if (offsetSides !== 'none') {
                container.setAttribute('data-bt-overflow', 'visible');
                container.style.boxSizing = 'border-box';

                if (offsetSides === 'left' || offsetSides === 'both') {
                    container.style.paddingLeft = offsetBefore + 'px';
                }
                if (offsetSides === 'right' || offsetSides === 'both') {
                    container.style.paddingRight = offsetAfter + 'px';
                }

                // Find first parent .e-con that is NOT e-child to clip overflow
                var clipParent = container.parentElement;
                while (clipParent) {
                    if (clipParent.classList.contains('e-con') && !clipParent.classList.contains('e-child')) {
                        clipParent.style.setProperty('overflow', 'hidden', 'important');
                        break;
                    }
                    clipParent = clipParent.parentElement;
                }

                // Recalculate slide widths with new padding
                swiper.update();

                console.log('BT Carousel offset applied:', {
                    sides: offsetSides,
                    paddingRight: container.style.paddingRight,
                    clientWidth: container.clientWidth
                });
            }

            // Handle autoplay pause on hover via events (more reliable)
            if (swiperConfig.autoplay && swiperConfig.autoplay.pauseOnMouseEnter) {
                container.addEventListener('mouseenter', function () {
                    swiper.autoplay.stop();
                });
                container.addEventListener('mouseleave', function () {
                    swiper.autoplay.start();
                });
            }

            console.log('BT Carousel OK:', container.getAttribute('data-id'), slides.length + ' slides');

        } catch (e) {
            console.error('BT Carousel: Swiper init failed', e);
            // Cleanup classes on failure
            container.classList.remove('swiper');
            if (isBoxed) {
                wrapper.classList.remove('swiper-wrapper');
            }
            slides.forEach(function (slide) {
                slide.classList.remove('swiper-slide');
                slide.removeAttribute('role');
                slide.removeAttribute('aria-label');
            });
        }
    }

    /**
     * Destroy Swiper instance
     * @param {HTMLElement} container
     */
    function destroySwiper(container) {
        var swiper = instances.get(container);
        if (swiper) {
            swiper.destroy(true, true);
            instances.delete(container);
            delete container._btSwiper;
            delete container.btSwiper;

            // Remove Swiper classes and reset attributes/styles
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
    }

    /**
     * Re-evaluate all carousels on resize
     */
    function onResize() {
        document.querySelectorAll('[data-bt-swiper]').forEach(function (container) {
            var config = container._btConfig;
            if (!config) return;

            var shouldBeActive = isActiveOnDevice(config);
            var isActive = instances.has(container);

            if (shouldBeActive && !isActive) {
                initSwiper(container);
            } else if (!shouldBeActive && isActive) {
                destroySwiper(container);
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
     * Initialize all containers with data-bt-swiper
     */
    function initAll() {
        // Wait for Swiper to be available (loaded by Elementor)
        if (typeof Swiper === 'undefined') {
            setTimeout(initAll, 100);
            return;
        }

        var containers = document.querySelectorAll('[data-bt-swiper]');
        containers.forEach(initSwiper);
    }

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll();
            window.addEventListener('resize', handleResize);
        });
    } else {
        initAll();
        window.addEventListener('resize', handleResize);
    }

    // Also init when Elementor frontend is ready (for dynamic content)
    if (window.jQuery) {
        jQuery(window).on('elementor/frontend/init', function () {
            elementorFrontend.hooks.addAction('frontend/element_ready/container', function ($element) {
                var container = $element[0];
                if (container.hasAttribute('data-bt-swiper')) {
                    // Small delay to ensure children are rendered
                    setTimeout(function () {
                        initSwiper(container);
                    }, 50);
                }
            });
        });
    }

    // Re-init on DOM mutations (Elementor editor live preview)
    var observer = new MutationObserver(function (mutations) {
        var shouldInit = false;
        for (var i = 0; i < mutations.length; i++) {
            var nodes = mutations[i].addedNodes;
            for (var j = 0; j < nodes.length; j++) {
                var node = nodes[j];
                if (node.nodeType === 1) {
                    if (node.hasAttribute && node.hasAttribute('data-bt-swiper')) {
                        shouldInit = true;
                        break;
                    }
                    if (node.querySelector && node.querySelector('[data-bt-swiper]')) {
                        shouldInit = true;
                        break;
                    }
                }
            }
            if (shouldInit) break;
        }
        if (shouldInit) {
            requestAnimationFrame(initAll);
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Public API
    window.BtCarousel = {
        init: initAll,
        initElement: initSwiper,
        destroy: destroySwiper,
        getInstance: function (el) { return instances.get(el); }
    };

    // Legacy API alias
    window.BtScrollContainer = window.BtCarousel;
})();
