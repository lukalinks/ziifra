const MOBILE_BREAKPOINT = 640;
const PANEL_TRANSITION_MS = 260;

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

function isMobileViewport() {
    return window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`).matches;
}

async function postNotificationAction(url, csrfToken, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (! response.ok) {
        throw new Error('Notification action failed');
    }
}

export function initNotifications() {
    const root = document.querySelector('[data-notifications]');

    if (! root) {
        return;
    }

    const toggle = root.querySelector('[data-notifications-toggle]');
    const panel = root.querySelector('[data-notifications-panel]');
    const backdrop = root.querySelector('[data-notifications-backdrop]');
    const closeButtons = root.querySelectorAll('[data-notifications-close]');
    const markAllButton = root.querySelector('[data-notifications-mark-all]');
    const badge = root.querySelector('[data-notifications-badge]');
    const list = root.querySelector('[data-notifications-list]');
    const csrfToken = root.dataset.csrfToken ?? '';
    const readAllUrl = root.dataset.readAllUrl ?? '';
    const isAdmin = root.hasAttribute('data-notifications-admin');

    if (! toggle || ! panel) {
        return;
    }

    let lastFocused = null;
    let closeTimer = null;

    const unreadCount = () => root.querySelectorAll('[data-notification-unread]').length;

    const updateBadge = () => {
        const count = unreadCount();

        if (count <= 0) {
            badge?.remove();
            markAllButton?.remove();

            return;
        }

        if (badge) {
            badge.textContent = count > 9 ? '9+' : String(count);
        }

        toggle.setAttribute('aria-label', toggle.getAttribute('aria-label')?.replace(/\d+/, String(count)) ?? '');
    };

    const markItemRead = (item) => {
        item.removeAttribute('data-notification-unread');
        item.classList.remove('ziifra-notifications-item-unread');
        item.querySelector('.ziifra-notifications-unread-dot')?.remove();
        item.querySelector('[data-notification-dismiss]')?.remove();
        updateBadge();
    };

    const focusableElements = () => Array.from(panel.querySelectorAll(FOCUSABLE_SELECTOR))
        .filter((element) => element.offsetParent !== null || element === document.activeElement);

    const trapFocus = (event) => {
        if (event.key !== 'Tab' || panel.hidden) {
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

    const close = () => {
        if (panel.hidden) {
            return;
        }

        panel.classList.remove('is-open');
        backdrop?.classList.remove('is-open');

        closeTimer = window.setTimeout(() => {
            panel.hidden = true;
            backdrop?.setAttribute('hidden', '');
            backdrop?.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('ziifra-notifications-open');
            closeTimer = null;

            if (lastFocused instanceof HTMLElement) {
                lastFocused.focus();
            }
        }, isMobileViewport() ? PANEL_TRANSITION_MS : 0);
    };

    const open = () => {
        if (closeTimer) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }

        lastFocused = document.activeElement;
        panel.hidden = false;
        backdrop?.removeAttribute('hidden');
        backdrop?.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');

        if (isMobileViewport()) {
            document.body.classList.add('ziifra-notifications-open');
        }

        requestAnimationFrame(() => {
            panel.classList.add('is-open');
            backdrop?.classList.add('is-open');

            const firstFocusable = panel.querySelector('[data-notifications-close]')
                ?? panel.querySelector('[data-notification-link]')
                ?? panel.querySelector('button');

            if (firstFocusable instanceof HTMLElement) {
                firstFocusable.focus();
            }
        });
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();

        if (panel.hidden) {
            open();
        } else {
            close();
        }
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            close();
        });
    });

    backdrop?.addEventListener('click', () => {
        close();
    });

    document.addEventListener('click', (event) => {
        if (panel.hidden || isMobileViewport()) {
            return;
        }

        if (! root.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! panel.hidden) {
            close();
        }

        trapFocus(event);
    });

    root.addEventListener('click', async (event) => {
        const dismissButton = event.target.closest('[data-notification-dismiss]');

        if (dismissButton) {
            event.preventDefault();
            event.stopPropagation();

            const item = dismissButton.closest('[data-notification-item]');
            const url = dismissButton.dataset.notificationReadUrl;

            if (! item || ! url) {
                return;
            }

            dismissButton.disabled = true;

            try {
                await postNotificationAction(url, csrfToken);
                markItemRead(item);
            } catch {
                dismissButton.disabled = false;
            }

            return;
        }

        const link = event.target.closest('[data-notification-link]');
        const unreadItem = link?.closest('[data-notification-unread]');

        if (! link || ! unreadItem) {
            return;
        }

        const notificationId = unreadItem.dataset.notificationId;
        const readUrl = unreadItem.dataset.notificationReadUrl;

        if (! notificationId || ! readUrl) {
            return;
        }

        const destination = link.href;

        event.preventDefault();
        event.stopPropagation();

        close();

        try {
            await postNotificationAction(readUrl, csrfToken);
        } catch {
            // Continue navigation even if mark-read fails.
        }

        if (link.hasAttribute('data-page-nav')) {
            document.documentElement.classList.add('ziifra-page-loading');
            document.getElementById('ziifra-page-loader')?.setAttribute('aria-hidden', 'false');

            try {
                sessionStorage.setItem('ziifra-page-loading', '1');
            } catch {
                // Ignore storage errors.
            }
        }

        window.location.assign(destination);
    });

    markAllButton?.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (! readAllUrl) {
            return;
        }

        markAllButton.disabled = true;

        try {
            await postNotificationAction(readAllUrl, csrfToken, isAdmin ? { admin: 1 } : {});
            window.location.reload();
        } catch {
            markAllButton.disabled = false;
        }
    });
}
