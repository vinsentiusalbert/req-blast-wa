(() => {
    'use strict';
    const toggle = document.querySelector('[data-reject-toggle]');
    const panel = document.getElementById('broadcast-rejection-panel');
    if (!toggle || !panel) return;
    const cancel = panel.querySelector('[data-reject-cancel]');
    const reason = panel.querySelector('textarea');
    const setOpen = (open, focus = true) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (focus) (open ? reason : toggle).focus();
    };
    toggle.hidden = false;
    cancel.hidden = false;
    setOpen(panel.dataset.hasError === 'true', false);
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    cancel.addEventListener('click', () => setOpen(false));
})();
