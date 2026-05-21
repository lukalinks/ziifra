const STORAGE_KEY = 'ziifra-page-loading';

function shouldHandleNavClick(event, link) {
    if (! link || event.defaultPrevented) {
        return false;
    }

    if (event.button !== 0) {
        return false;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download')) {
        return false;
    }

    const href = link.getAttribute('href');

    if (! href || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    let url;

    try {
        url = new URL(link.href, window.location.href);
    } catch {
        return false;
    }

    if (url.origin !== window.location.origin) {
        return false;
    }

    const samePath = url.pathname === window.location.pathname;
    const sameSearch = url.search === window.location.search;

    if (samePath && sameSearch) {
        return false;
    }

    return true;
}

export function initPageLoader() {
    const root = document.getElementById('ziifra-page-loader');

    if (! root) {
        return;
    }

    const show = () => {
        document.documentElement.classList.add('ziifra-page-loading');
        root.setAttribute('aria-hidden', 'false');
    };

    const hide = () => {
        document.documentElement.classList.remove('ziifra-page-loading');
        root.setAttribute('aria-hidden', 'true');

        try {
            sessionStorage.removeItem(STORAGE_KEY);
        } catch {
            // Ignore storage errors in restricted contexts.
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-page-nav]');

        if (! shouldHandleNavClick(event, link)) {
            return;
        }

        show();

        try {
            sessionStorage.setItem(STORAGE_KEY, '1');
        } catch {
            // Continue navigation even if storage is unavailable.
        }

        event.preventDefault();

        const destination = link.href;

        requestAnimationFrame(() => {
            window.location.assign(destination);
        });
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hide();
        }
    });

    const finishLoad = () => {
        if (! document.documentElement.classList.contains('ziifra-page-loading')) {
            return;
        }

        requestAnimationFrame(() => {
            window.setTimeout(hide, 100);
        });
    };

    if (document.readyState === 'complete') {
        finishLoad();
    } else {
        window.addEventListener('load', finishLoad, { once: true });
    }
}
