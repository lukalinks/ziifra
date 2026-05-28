const DEBOUNCE_MS = 150;

function bindEmployeesQuickFilterForm(form) {
    const panel = document.querySelector('[data-employees-results]');
    const liveSearch = form.hasAttribute('data-employees-live-search') && panel !== null;
    const searchInput = form.querySelector('[data-employees-search]');
    const projectSelect = form.querySelector('[data-employees-project]');
    let debounceTimer = null;
    let abortController = null;

    const submitForm = () => {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    };

    const runLiveSearch = async () => {
        if (! liveSearch) {
            submitForm();

            return;
        }

        const params = new URLSearchParams(new FormData(form));
        const url = `${form.action}?${params.toString()}`;

        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        panel.setAttribute('aria-busy', 'true');
        panel.classList.add('opacity-60', 'pointer-events-none');

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                signal: abortController.signal,
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error(`Search failed (${response.status})`);
            }

            const html = await response.text();
            panel.innerHTML = html;
            panel.removeAttribute('aria-busy');
            panel.classList.remove('opacity-60', 'pointer-events-none');

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const countEl = doc.querySelector('[data-employees-count]');
            const targetCount = document.querySelector('[data-employees-count]');

            if (countEl && targetCount) {
                targetCount.textContent = countEl.textContent;
            }

            if (window.history?.replaceState) {
                window.history.replaceState(null, '', url);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            panel.removeAttribute('aria-busy');
            panel.classList.remove('opacity-60', 'pointer-events-none');
            submitForm();
        }
    };

    const scheduleSearch = () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(runLiveSearch, DEBOUNCE_MS);
    };

    const searchNow = () => {
        window.clearTimeout(debounceTimer);
        runLiveSearch();
    };

    if (searchInput) {
        searchInput.addEventListener('input', scheduleSearch);

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchNow();
            }
        });
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', searchNow);
    }

    form.querySelectorAll('select').forEach((select) => {
        if (select !== projectSelect) {
            select.addEventListener('change', searchNow);
        }
    });

    form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('change', searchNow);
    });

    form.addEventListener('submit', (event) => {
        if (liveSearch) {
            event.preventDefault();
            searchNow();
        }
    });

    panel?.addEventListener('click', (event) => {
        const link = event.target.closest('nav[aria-label] a, .pagination a');

        if (! link?.href || link.href.includes('#')) {
            return;
        }

        try {
            const linkUrl = new URL(link.href, window.location.origin);
            const formUrl = new URL(form.action, window.location.origin);

            if (linkUrl.pathname !== formUrl.pathname) {
                return;
            }

            event.preventDefault();

            if (abortController) {
                abortController.abort();
            }

            abortController = new AbortController();
            panel.setAttribute('aria-busy', 'true');
            panel.classList.add('opacity-60', 'pointer-events-none');

            fetch(link.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: abortController.signal,
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (! response.ok) {
                        throw new Error('Pagination failed');
                    }

                    return response.text();
                })
                .then((html) => {
                    panel.innerHTML = html;
                    panel.classList.remove('opacity-60', 'pointer-events-none');
                    panel.removeAttribute('aria-busy');

                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const countEl = doc.querySelector('[data-employees-count]');
                    const targetCount = document.querySelector('[data-employees-count]');

                    if (countEl && targetCount) {
                        targetCount.textContent = countEl.textContent;
                    }

                    linkUrl.searchParams.forEach((value, key) => {
                        const field = form.elements.namedItem(key);

                        if (! field || field instanceof RadioNodeList) {
                            return;
                        }

                        if (field.type === 'checkbox') {
                            field.checked = value === field.value;
                        } else {
                            field.value = value;
                        }
                    });

                    if (window.history?.replaceState) {
                        window.history.replaceState(null, '', link.href);
                    }
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        window.location.assign(link.href);
                    }
                });
        } catch {
            // Let the browser handle invalid URLs.
        }
    });
}

export function initEmployeesQuickFilter() {
    document.querySelectorAll('[data-employees-quick-filter]').forEach(bindEmployeesQuickFilterForm);
}

export function initProjectHoursFilter() {
    const form = document.querySelector('[data-project-hours-filter]');

    if (! form) {
        return;
    }

    const searchInput = form.querySelector('[data-project-hours-search]');
    const monthInput = form.querySelector('[data-project-hours-month]');
    let debounceTimer = null;

    const submitForm = () => {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(submitForm, DEBOUNCE_MS);
        });
    }

    if (monthInput) {
        monthInput.addEventListener('change', submitForm);
    }
}
