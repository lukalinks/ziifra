export function initConfirmDialog() {
    const root = document.getElementById('ziifra-confirm-dialog');

    if (! root) {
        return;
    }

    const messageEl = root.querySelector('[data-confirm-message]');
    const titleEl = root.querySelector('[data-confirm-title]');
    const cancelBtn = root.querySelector('[data-confirm-cancel]');
    const acceptBtn = root.querySelector('[data-confirm-accept]');
    const backdrop = root.querySelector('[data-confirm-backdrop]');

    if (! messageEl || ! titleEl || ! cancelBtn || ! acceptBtn || ! backdrop) {
        return;
    }

    let pendingForm = null;

    const hide = () => {
        root.hidden = true;
        root.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('ziifra-confirm-open');
        pendingForm = null;
    };

    const show = (form) => {
        messageEl.textContent = form.dataset.confirm ?? '';
        titleEl.textContent = form.dataset.confirmTitle || root.dataset.confirmDefaultTitle || 'Confirm';
        acceptBtn.textContent = form.dataset.confirmAccept || root.dataset.confirmDefaultAccept || 'Confirm';

        acceptBtn.classList.remove('ziifra-confirm-dialog__accept--danger', 'ziifra-confirm-dialog__accept--primary');

        if (form.dataset.confirmVariant === 'danger') {
            acceptBtn.classList.add('ziifra-confirm-dialog__accept--danger');
        } else {
            acceptBtn.classList.add('ziifra-confirm-dialog__accept--primary');
        }

        root.hidden = false;
        root.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('ziifra-confirm-open');
        cancelBtn.focus();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement) || ! form.dataset.confirm) {
            return;
        }

        if (form.dataset.confirmBypass === '1') {
            delete form.dataset.confirmBypass;

            return;
        }

        event.preventDefault();
        pendingForm = form;
        show(form);
    }, true);

    acceptBtn.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.dataset.confirmBypass = '1';
            pendingForm.requestSubmit();
        }

        hide();
    });

    cancelBtn.addEventListener('click', hide);
    backdrop.addEventListener('click', hide);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! root.hidden) {
            hide();
        }
    });
}
