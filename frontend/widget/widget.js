(function () {
    document.querySelectorAll('.bt-widget-tabs').forEach(function (container) {
        const buttons = container.querySelectorAll('.bt-tab-btn');
        const panels  = container.querySelectorAll('.bt-tab-panel');

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(b => { b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false'); });
                panels.forEach(p => p.classList.add('is-hidden'));

                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');
                document.getElementById(btn.dataset.target)?.classList.remove('is-hidden');
            });
        });
    });
})();