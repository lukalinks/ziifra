export function initEmployeeProfileEdit() {
    const root = document.querySelector('[data-employee-profile]');

    if (!root) {
        return;
    }

    const editPanel = root.querySelector('[data-employee-edit-panel]');

    if (!editPanel) {
        return;
    }

    const viewLayers = root.querySelectorAll('[data-employee-view]');
    const viewActions = root.querySelectorAll('[data-employee-view-actions]');

    const setHidden = (nodes, hidden) => {
        nodes.forEach((node) => {
            node.hidden = hidden;
        });
    };

    const open = () => {
        root.classList.add('is-editing');
        setHidden(viewLayers, true);
        setHidden(viewActions, true);
        editPanel.hidden = false;
        editPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        editPanel.querySelector('input, select, textarea')?.focus({ preventScroll: true });
    };

    const close = () => {
        root.classList.remove('is-editing');
        editPanel.hidden = true;
        setHidden(viewLayers, false);
        setHidden(viewActions, false);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    root.querySelectorAll('[data-employee-edit-toggle]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });
    });

    root.querySelectorAll('[data-employee-edit-cancel]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            close();
        });
    });

    if (root.hasAttribute('data-edit-open')) {
        open();
    }
}
