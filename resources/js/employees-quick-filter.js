export function initEmployeesQuickFilter() {
    const form = document.querySelector('[data-employees-quick-filter]');

    if (! form) {
        return;
    }

    const searchInput = form.querySelector('[data-employees-search]');
    const projectSelect = form.querySelector('[data-employees-project]');
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
            debounceTimer = window.setTimeout(submitForm, 350);
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                window.clearTimeout(debounceTimer);
            }
        });
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', submitForm);
    }
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
            debounceTimer = window.setTimeout(submitForm, 350);
        });
    }

    if (monthInput) {
        monthInput.addEventListener('change', submitForm);
    }
}
