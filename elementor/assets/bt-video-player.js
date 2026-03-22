/**
 * BT — Video Player (Plyr)
 *
 * <video> is always in the DOM (rendered by PHP).
 * JS only initializes Plyr on it + handles lazy/autoplay.
 */
(function () {
    'use strict';

    class BtVideoPlayer {
        constructor(el) {
            this.root   = el;
            this.wrap   = el.querySelector('.bt-video-player__wrap');
            this.poster = el.querySelector('.bt-video-player__poster');
            this.config = JSON.parse(el.dataset.btVideo || '{}');
            this.plyr   = null;
            this.loaded = false;

            if (this.config.lazy) {
                this.setupLazy();
            } else {
                this.initPlyr();
            }
        }

        /* ── Lazy: IO preloads, click or autoplay triggers play ─── */
        setupLazy() {
            if (this.poster) {
                var self = this;
                var handler = function (e) { e.preventDefault(); e.stopPropagation(); self.activate(); };
                this.poster.addEventListener('click', handler);
            }

            if (!window.IntersectionObserver) { this.initPlyr(); return; }

            var margin = this.config.lazyMargin || '200px';
            var self = this;
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    obs.unobserve(self.root);
                    if (self.config.autoplay) {
                        self.activate();
                    } else {
                        self.initPlyr();
                    }
                });
            }, { rootMargin: margin, threshold: 0 });

            obs.observe(this.root);
        }

        /* ── Activate: init + play + hide poster ──────────────── */
        async activate() {
            if (!this.loaded) {
                this.root.classList.add('bt-video-player--loading');
                await this.initPlyr();
            }
            this.root.classList.remove('bt-video-player--loading');
            this.root.classList.add('bt-video-player--active');

            if (!this.plyr) return;
            try {
                this.plyr.muted = true;
                await this.plyr.play();
            } catch (_) {}
            if (!this.config.muted) this.plyr.muted = false;
        }

        /* ── Init Plyr on existing <video> ────────────────────── */
        async initPlyr() {
            if (this.loaded) return;
            this.loaded = true;

            await this.waitForLib();

            var videoEl = this.wrap.querySelector('.bt-video-player__video');
            if (!videoEl) return;

            if (videoEl.tagName === 'VIDEO' && videoEl.preload === 'none') {
                videoEl.preload = 'metadata';
            }

            var cfg = this.config;

            try {
                this.plyr = new Plyr(videoEl, cfg.plyr || {});

                this.plyr.on('ready', function () {
                    if (cfg.autoplay && !cfg.lazy) {
                        this.root.classList.add('bt-video-player--active');
                        this.plyr.muted = true;
                        this.plyr.play().catch(function () {});
                    }
                }.bind(this));

                this.plyr.on('playing', function () {
                    this.root.classList.add('bt-video-player--active');
                }.bind(this));

            } catch (err) {
                console.error('[BT Video] Plyr init failed:', err);
            }
        }

        waitForLib() {
            return new Promise(function (resolve) {
                if (typeof Plyr !== 'undefined') { resolve(); return; }
                var t = 0;
                var iv = setInterval(function () {
                    if (typeof Plyr !== 'undefined' || ++t > 50) { clearInterval(iv); resolve(); }
                }, 100);
            });
        }

        destroy() {
            if (this.plyr) { try { this.plyr.destroy(); } catch (_) {} this.plyr = null; }
        }
    }

    /* ── Bootstrap ─────────────────────────────────────────────── */
    function initAll() {
        document.querySelectorAll('.bt-video-player:not([data-init])').forEach(function (el) {
            el.setAttribute('data-init', '1');
            new BtVideoPlayer(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Elementor editor / popups
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('elementor/popup/show', initAll);
        jQuery(window).on('elementor/frontend/init', function () {
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                elementorFrontend.hooks.addAction(
                    'frontend/element_ready/bt-video-player.default',
                    function ($scope) {
                        var el = $scope.find('.bt-video-player:not([data-init])')[0];
                        if (el) { el.setAttribute('data-init', '1'); new BtVideoPlayer(el); }
                    }
                );
            }
        });
    }

    // Dynamic content
    if (window.MutationObserver) {
        new MutationObserver(initAll).observe(document.body, { childList: true, subtree: true });
    }

    window.BtVideoPlayer = BtVideoPlayer;
})();
