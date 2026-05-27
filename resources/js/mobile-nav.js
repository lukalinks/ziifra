const PANEL_TRANSITION_MS = 260;

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

export function initMobileNav() {
    const root = document.getElementById('ziifra-mobile-nav');

    if (! root) {
        return;
    }

    const panel = root.querySelector('.ziifra-mobile-nav-panel');
    const openButtons = document.querySelectorAll('[data-mobile-nav-open]');
    const closeTargets = root.querySelectorAll('[data-mobile-nav-close]');
    const navLinks = root.querySelectorAll('a[href]');
    let lastFocused = null;
    let closeTimer = null;

    const focusableElements = () => {
        if (! panel) {
            return [];
        }

        return Array.from(panel.querySelectorAll(FOCUSABLE_SELECTOR))
            .filter((element) => element.offsetParent !== null || element === document.activeElement);
    };

    const trapFocus = (event) => {
        if (event.key !== 'Tab' || ! root.classList.contains('is-open')) {
            return;
        }

        const focusables = focusableElements();

        if (focusables.length === 0) {
            event.preventDefault();

            return;
        }

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    const open = () => {
        if (closeTimer) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }

        lastFocused = document.activeElement;
        root.removeAttribute('aria-hidden');
        root.removeAttribute('inert');
        document.body.classList.add('ziifra-mobile-nav-open');

        requestAnimationFrame(() => {
            root.classList.add('is-open');

            const closeButton = root.querySelector('[data-mobile-nav-close]');

            if (closeButton instanceof HTMLElement) {
                closeButton.focus();
            }
        });
    };

    const close = () => {
        root.classList.remove('is-open');
        document.body.classList.remove('ziifra-mobile-nav-open');

        closeTimer = window.setTimeout(() => {
            if (! root.classList.contains('is-open')) {
                root.setAttribute('aria-hidden', 'true');
                root.setAttribute('inert', '');
            }

            closeTimer = null;
        }, PANEL_TRANSITION_MS);

        if (lastFocused instanceof HTMLElement) {
            lastFocused.focus();
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            if (root.classList.contains('is-open')) {
                close();
            } else {
                open();
            }
        });
    });

    closeTargets.forEach((target) => {
        target.addEventListener('click', close);
    });

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (root.classList.contains('is-open')) {
                close();
            }
        });
    });

    root.addEventListener('keydown', trapFocus);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            close();
        }
    });
}
