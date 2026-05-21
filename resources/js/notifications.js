export function initNotifications() {
    const root = document.querySelector('[data-notifications]');

    if (! root) {
        return;
    }

    const toggle = root.querySelector('[data-notifications-toggle]');
    const panel = root.querySelector('[data-notifications-panel]');

    if (! toggle || ! panel) {
        return;
    }

    const close = () => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();

        if (panel.hidden) {
            open();
        } else {
            close();
        }
    });

    document.addEventListener('click', (event) => {
        if (! root.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}
