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

            // Désactive autoplay en mode éditeur Elementor (évite que les vidéos se lancent pendant l'édition)
            this.isEditor = document.body.classList.contains('elementor-editor-active');
            if (this.isEditor) {
                this.config.autoplay = false;
            }

            if (this.config.lazy) {
                this.setupLazy();
            } else {
                this.initPlyr();
            }
        }

        /* ── Marge IntersectionObserver adaptive au réseau ───────── */
        getSmartMargin() {
            var forced = this.config.lazyMargin;
            if (forced && forced !== 'auto') return forced;

            var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

            // Mode économie de données → preload seulement au scroll
            if (conn && conn.saveData) return '0px';

            var type = conn && conn.effectiveType;
            if (type === 'slow-2g' || type === '2g') return '0px';
            // 3G : cap à 600px max pour éviter preload agressif sur tablettes/grands écrans
            if (type === '3g') return Math.min(Math.round(window.innerHeight * 1.5), 600) + 'px';

            // 4g / wifi / API absente → 1 hauteur d'écran (adaptatif au device)
            return Math.min(Math.round(window.innerHeight), 800) + 'px';
        }

        /* ── Lazy: IO preloads, click or autoplay triggers play ─── */
        setupLazy() {
            if (this.poster) {
                var self = this;
                var handler = function (e) { e.preventDefault(); e.stopPropagation(); self.activate(); };
                // Handler sur le poster ET directement sur le bouton —
                // sur iOS/Android, les clics sur <button> à l'intérieur d'un div
                // cliquable ne remontent pas toujours correctement.
                this.poster.addEventListener('click', handler);
                var btn = this.poster.querySelector('.bt-video-player__play');
                if (btn) btn.addEventListener('click', handler);
            }

            if (!window.IntersectionObserver) { this.initPlyr(); return; }

            var margin = this.getSmartMargin();
            var self = this;

            if (this.config.autoplay) {
                // Avec 0px : un seul observer qui fait preload + play au scroll
                // Avec marge > 0 : preload en avance, play quand visible
                var isZeroMargin = margin === '0px';

                if (isZeroMargin) {
                    // Observer unique : init + play dès que l'élément entre dans le viewport
                    var scrollObs = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            scrollObs.unobserve(self.root);
                            self.activate(); // activate() fait initPlyr() si besoin
                        });
                    }, { rootMargin: '0px', threshold: 0.1 });
                    scrollObs.observe(this.root);
                } else {
                    // Observer 1 : preload Plyr quand l'élément approche du viewport (marge large).
                    var preloadObs = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            preloadObs.unobserve(self.root);
                            self.initPlyr();
                        });
                    }, { rootMargin: margin, threshold: 0 });
                    preloadObs.observe(this.root);

                    // Observer 2 : lance la lecture seulement quand l'élément est réellement visible
                    var playObs = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            playObs.unobserve(self.root);
                            self.activate();
                        });
                    }, { rootMargin: '0px', threshold: 0.5 });
                    playObs.observe(this.root);
                }
            } else {
                // Sans autoplay : init Plyr à l'approche pour que le player soit prêt au clic.
                var obs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        obs.unobserve(self.root);
                        self.initPlyr();
                    });
                }, { rootMargin: margin, threshold: 0 });
                obs.observe(this.root);
            }
        }

        /* ── Activate: init + play + hide poster ──────────────── */
        async activate() {
            // Éviter les appels multiples
            if (this._activating) return;
            this._activating = true;

            try {
                if (!this.loaded) {
                    this.root.classList.add('bt-video-player--loading');
                    await this.initPlyr();
                }

                if (!this.plyr) {
                    this._stopLoading();
                    return;
                }

                // Vimeo / YouTube chargent leur iframe en async :
                // plyr.ready résout quand le player est connecté à l'API embed.
                try {
                    await Promise.race([
                        this.plyr.ready,
                        new Promise(function (resolve) { setTimeout(resolve, 6000); }),
                    ]);
                } catch (_) {}

                // Stoppe le spinner et cache le poster
                this._stopLoading();

                // Démute si l'utilisateur n'a pas explicitement demandé le silence.
                var played = false;
                try {
                    this.plyr.muted = true;
                    await this.plyr.play();
                    played = true;
                } catch (_) {
                    this._showTapToPlay();
                }
                if (played && !this.config.user_muted) {
                    this.plyr.muted = false;
                    try { this.plyr.volume = 0.25; } catch (_) {}
                }
            } catch (err) {
                console.error('[BT Video] activate failed:', err);
                this._stopLoading();
            }
        }

        /* ── Arrête le spinner et active le player ────────────── */
        _stopLoading() {
            this.root.classList.remove('bt-video-player--loading');
            this.root.classList.add('bt-video-player--active');
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
                        var self = this;
                        this.root.classList.add('bt-video-player--active');
                        this.plyr.muted = true;
                        this.plyr.play().then(function () {
                            // Démute après lecture effective — même logique que activate().
                            if (!cfg.user_muted) {
                                self.plyr.muted = false;
                                try { self.plyr.volume = 0.25; } catch (_) {}
                            }
                        }).catch(function () {
                            // Autoplay bloqué (iOS Low Power Mode, politique navigateur…)
                            // → affiche un overlay tap-to-play pour que l'user puisse lancer
                            // avec une vraie interaction (qui débloque play() nativement).
                            self._showTapToPlay();
                        });
                    }
                }.bind(this));

                this.plyr.on('playing', function () {
                    this.root.classList.add('bt-video-player--active');
                }.bind(this));

            } catch (err) {
                console.error('[BT Video] Plyr init failed:', err);
            }
        }

        /* ── Tap-to-play overlay quand l'autoplay est bloqué (Low Power Mode…) ── */
        _showTapToPlay() {
            if (this.wrap.querySelector('.bt-video-player__tap-play')) return;
            var self = this;
            var overlay = document.createElement('div');
            overlay.className = 'bt-video-player__tap-play';
            overlay.setAttribute('aria-label', 'Lire la vidéo');
            overlay.innerHTML = '<button type="button" aria-hidden="true">'
                + '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M8 5v14l11-7z"/></svg>'
                + '</button>';
            overlay.addEventListener('click', function () {
                overlay.remove();
                self.plyr.muted = true;
                self.plyr.play().then(function () {
                    if (!self.config.user_muted) {
                        self.plyr.muted = false;
                        try { self.plyr.volume = 0.25; } catch (_) {}
                    }
                }).catch(function () {});
            });
            this.wrap.appendChild(overlay);
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
        // WP Rocket compatibility — écoute aussi l'événement différé
        document.addEventListener('rocket-DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // WP Rocket — si le script est chargé après que tout soit prêt
    window.addEventListener('rocket-allScriptsLoaded', initAll);

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

    // Dynamic content — debounced pour éviter querySelectorAll à chaque mutation DOM.
    // Scopé au conteneur Elementor (ou <main>) plutôt qu'au body entier pour
    // réduire le bruit des mutations hors-page (admin bar, scripts tiers…).
    if (window.MutationObserver) {
        var _moTimer = null;
        var _moTarget = document.querySelector('.elementor') || document.querySelector('main') || document.body;
        new MutationObserver(function () {
            clearTimeout(_moTimer);
            _moTimer = setTimeout(initAll, 200);
        }).observe(_moTarget, { childList: true, subtree: true });
    }

    window.BtVideoPlayer = BtVideoPlayer;
})();
