(() => {
    'use strict';
    const dialog = document.getElementById('wa-request-intro');
    if (!dialog) return;
    let opener;
    const open = (button) => {
        if (dialog.open) return;
        document.dispatchEvent(new Event('portal:close-menu'));
        opener = button;
        dialog.showModal();
        document.documentElement.classList.add('request-intro-open');
    };
    document.querySelectorAll('[data-open-request-intro]').forEach(button => {
        button.addEventListener('click', () => open(button));
    });
    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove('request-intro-open');
        if (opener) opener.focus();
        else {
            const main = document.getElementById('main-content');
            main.setAttribute('tabindex', '-1');
            main.focus({preventScroll: true});
        }
    });
    if (dialog.dataset.autoOpen === 'true') open();
})();
