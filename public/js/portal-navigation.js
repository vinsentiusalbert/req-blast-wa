(() => {
    const root = document.documentElement;
    const sidebar = document.getElementById('mobile-menu');
    if (!sidebar || sidebar.dataset.navigationReady) return;
    sidebar.dataset.navigationReady = 'true';
    const mobile = window.matchMedia('(max-width: 1080px)');
    const toggles = [...document.querySelectorAll('[data-menu-toggle]')];
    let opener = null;
    const setMenuState = (open, restoreFocus = true) => {
        open = open && mobile.matches;
        root.classList.toggle('portal-menu-open', open);
        if (!open && restoreFocus && mobile.matches && sidebar.contains(document.activeElement)) opener?.focus();
        sidebar.inert = mobile.matches && !open;
        toggles.forEach(button => button.setAttribute('aria-expanded', String(open)));
        if (open) sidebar.querySelector('[data-menu-close]')?.focus();
    };
    toggles.forEach(button => button.addEventListener('click', () => {
        opener = button;
        setMenuState(!root.classList.contains('portal-menu-open'));
    }));
    document.querySelectorAll('[data-menu-close]').forEach(button => button.addEventListener('click', () => setMenuState(false)));
    document.addEventListener('portal:close-menu', () => setMenuState(false, false));
    mobile.addEventListener('change', () => setMenuState(false, false));
    document.addEventListener('keydown', event => {
        if (!root.classList.contains('portal-menu-open')) return;
        if (event.key === 'Escape') setMenuState(false);
        if (event.key !== 'Tab') return;
        const items = [...sidebar.querySelectorAll('a[href],button:not(:disabled),input')]
            .filter(el => !el.closest('[inert]') && el.getClientRects().length);
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    document.querySelectorAll('[data-nav-toggle]').forEach((button, index) => {
        const group = button.closest('[data-nav-group]');
        const submenu = group?.querySelector('.portal-subnav');
        if (!submenu) return;
        if (!submenu.id) submenu.id = `portal-subnav-${index}`;
        button.setAttribute('aria-controls', submenu.id);
        const sync = () => {
            const open = group.classList.contains('portal-nav__item--open');
            button.setAttribute('aria-expanded', String(open));
            submenu.inert = !open;
            submenu.style.setProperty('--submenu-height', `${submenu.scrollHeight + 16}px`);
        };
        button.addEventListener('click', () => { group.classList.toggle('portal-nav__item--open'); sync(); });
        sync();
    });
    // Selection follows the rendered page, including when a link is opened in another tab.
    sidebar.querySelectorAll('.portal-subnav__item--active').forEach(link => link.setAttribute('aria-current', 'page'));
    setMenuState(false, false);
})();
