/**
 * BT — Fancy Menu
 *
 * Menu multi-niveaux avec navigation animée (slide horizontal).
 * Adapté du widget Rey Theme.
 */
(function () {
    'use strict';

    class BtFancyMenu {
        constructor(el) {
            this.$nav = el;
            this.$parentNav = el.querySelector('.bt-fancyMenu-nav');
            this.$indicatorTpl = el.querySelector('.bt-fancyMenu-indicator-tpl');
            this.depth = parseInt(el.dataset.depth, 10) || 20;
            this.hasIndicators = el.dataset.indicators === 'yes';

            this.init();
        }

        init() {
            this.makeHeight();
            this.createSubmenuIndicators();
            this.bindEvents();
            this.setupA11y();
        }

        bindEvents() {
            var self = this;

            // Click on menu item with children → open submenu
            var parentLinks = this.$nav.querySelectorAll('.menu-item.menu-item-has-children > a');
            parentLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    var submenu = link.nextElementSibling;
                    if (submenu && submenu.tagName === 'UL') {
                        e.preventDefault();

                        // Ensure starting height is set (handles hidden-on-init case)
                        if (!self.$nav.style.height || self.$nav.style.height === '0px') {
                            var currentUl = self.$nav.querySelector('ul.--start');
                            if (currentUl) {
                                self.$nav.style.height = currentUl.offsetHeight + 'px';
                                self.$nav.offsetHeight; // force reflow for transition
                            }
                        }

                        // Remove --start from all, add --back to parent
                        var startMenus = self.$nav.querySelectorAll('ul.--start');
                        startMenus.forEach(function (ul) { ul.classList.remove('--start'); });

                        link.closest('ul').classList.add('--back');
                        submenu.classList.add('--start');

                        // Update height
                        self.$nav.style.height = submenu.offsetHeight + 'px';
                    }
                });
            });

            // Click on back button → go back
            var backBtns = this.$parentNav.querySelectorAll('.bt-fancyMenu-back');
            backBtns.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();

                    var currentStart = btn.closest('.--start');
                    if (currentStart) {
                        currentStart.classList.remove('--start');

                        var parentUl = btn.closest('.--back');
                        if (parentUl) {
                            parentUl.classList.remove('--back');
                            parentUl.classList.add('--start');
                        }

                        var newStart = self.$nav.querySelector('ul.--start');
                        if (newStart) {
                            self.$nav.style.height = newStart.offsetHeight + 'px';
                        }
                    }
                });
            });
        }

        makeHeight() {
            var startMenu = this.$nav.querySelector('ul.--start');
            if (startMenu) {
                var h = startMenu.offsetHeight;
                if (h > 0) {
                    this.$nav.style.height = h + 'px';
                } else {
                    // Element is hidden (e.g. inside closed mobile menu),
                    // observe visibility to set height when it appears
                    this.observeVisibility();
                }
            }
        }

        observeVisibility() {
            var self = this;
            if (!window.IntersectionObserver) return;
            var observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) {
                    observer.disconnect();
                    self.makeHeight();
                }
            });
            observer.observe(this.$nav);
        }

        setupA11y() {
            var self = this;
            var popupItems = this.$nav.querySelectorAll('.menu-item-has-children');

            popupItems.forEach(function (item) {
                item.setAttribute('aria-haspopup', 'true');
                item.setAttribute('aria-expanded', 'false');

                // Tabindex -1 on submenu links
                var subLinks = item.querySelectorAll('.sub-menu a, .sub-menu .bt-fancyMenu-back');
                subLinks.forEach(function (link) {
                    link.setAttribute('tabindex', '-1');
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', function (e) {
                // Enter/Space → open submenu
                if (e.keyCode === 13 || e.keyCode === 32) {
                    var focused = self.$nav.querySelector('.menu-item[aria-haspopup="true"] > a:focus');
                    if (focused) {
                        var parentLi = focused.closest('li');
                        if (parentLi) {
                            e.preventDefault();
                            parentLi.setAttribute('aria-expanded', 'true');
                            focused.click();

                            // Enable tabindex on submenu
                            var subItems = parentLi.querySelectorAll(':scope > .sub-menu > li > a, :scope > .sub-menu .bt-fancyMenu-back');
                            subItems.forEach(function (item) {
                                item.removeAttribute('tabindex');
                            });
                        }
                    }

                    // Back button focused
                    var backFocused = self.$nav.querySelector('.bt-fancyMenu-back:focus');
                    if (backFocused) {
                        self.closeSubmenu(backFocused);
                    }
                }

                // Escape → close submenu
                if (e.keyCode === 27) {
                    self.closeSubmenu();
                }
            });
        }

        closeSubmenu(backBtn) {
            var expandedItem;
            if (backBtn) {
                expandedItem = backBtn.closest('.menu-item[aria-haspopup="true"][aria-expanded="true"]');
            } else {
                expandedItem = this.$nav.querySelector('.menu-item[aria-haspopup="true"][aria-expanded="true"]');
            }

            if (expandedItem) {
                var backBtnInside = expandedItem.querySelector(':scope > .sub-menu > .bt-fancyMenu-back');
                if (backBtnInside) {
                    backBtnInside.click();
                }

                expandedItem.setAttribute('aria-expanded', 'false');

                var subItems = expandedItem.querySelectorAll('.sub-menu a, .sub-menu .bt-fancyMenu-back');
                subItems.forEach(function (item) {
                    item.setAttribute('tabindex', '-1');
                });

                expandedItem.querySelector('a').focus();
            }
        }

        createSubmenuIndicators() {
            if (!this.hasIndicators || !this.$indicatorTpl) return;

            var self = this;
            var tplContent = this.$indicatorTpl.content || this.$indicatorTpl;

            var parentLinks = this.$nav.querySelectorAll('.menu-item-has-children > a');
            parentLinks.forEach(function (link) {
                if (link.nextElementSibling && !link.querySelector('.--submenu-indicator')) {
                    var indicator = document.createElement('span');
                    indicator.className = '--submenu-indicator';

                    // Cloner le contenu du template
                    if (tplContent.firstElementChild) {
                        indicator.appendChild(tplContent.firstElementChild.cloneNode(true));
                    } else if (tplContent.childNodes.length > 0) {
                        // Fallback: copier tout le contenu
                        Array.from(tplContent.childNodes).forEach(function(node) {
                            indicator.appendChild(node.cloneNode(true));
                        });
                    }

                    // Ajouter après le titre (dans .bt-fancyMenu-title ou directement dans le lien)
                    var title = link.querySelector('.bt-fancyMenu-title');
                    if (title) {
                        title.appendChild(indicator);
                    } else {
                        link.appendChild(indicator);
                    }
                }
            });
        }
    }

    /* ── Bootstrap ─────────────────────────────────────────────── */
    function initAll() {
        document.querySelectorAll('.bt-fancyMenu:not([data-init])').forEach(function (el) {
            el.setAttribute('data-init', '1');
            new BtFancyMenu(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Elementor editor
    if (typeof jQuery !== 'undefined') {
        jQuery(window).on('elementor/frontend/init', function () {
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                elementorFrontend.hooks.addAction(
                    'frontend/element_ready/bt-fancy-menu.default',
                    function ($scope) {
                        var el = $scope.find('.bt-fancyMenu:not([data-init])')[0];
                        if (el) {
                            el.setAttribute('data-init', '1');
                            new BtFancyMenu(el);
                        }
                    }
                );
            }
        });
    }

    // Dynamic content
    if (window.MutationObserver) {
        var _moTimer = null;
        var _moTarget = document.querySelector('.elementor') || document.querySelector('main') || document.body;
        new MutationObserver(function () {
            clearTimeout(_moTimer);
            _moTimer = setTimeout(initAll, 200);
        }).observe(_moTarget, { childList: true, subtree: true });
    }

    window.BtFancyMenu = BtFancyMenu;
})();
