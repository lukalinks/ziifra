export function initAdminMobileNav() {
    const root = document.getElementById('ziifra-admin-mobile-nav');

    if (! root) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-admin-nav-open]');
    const closeTargets = root.querySelectorAll('[data-admin-nav-close]');

    const open = () => {
        root.removeAttribute('aria-hidden');
        root.removeAttribute('inert');
        document.body.classList.add('ziifra-admin-nav-open');
    };

    const close = () => {
        root.setAttribute('aria-hidden', 'true');
        root.setAttribute('inert', '');
        document.body.classList.remove('ziifra-admin-nav-open');
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });
    });

    closeTargets.forEach((target) => {
        target.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('ziifra-admin-nav-open')) {
            close();
        }
    });
}
