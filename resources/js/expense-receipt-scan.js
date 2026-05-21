export function initExpenseReceiptScan() {
    const form = document.querySelector('[data-expense-receipt-scan]');

    if (! form) {
        return;
    }

    const scanUrl = form.dataset.expenseScanUrl;
    const scanEnabled = form.dataset.expenseScanEnabled === '1';
    const receiptInput = form.querySelector('#receipt');
    const statusBox = form.querySelector('[data-expense-scan-status]');
    const preview = form.querySelector('[data-expense-scan-preview]');
    const previewImage = form.querySelector('[data-expense-scan-preview-image]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? form.querySelector('input[name="_token"]')?.value;

    if (! receiptInput || ! scanUrl) {
        return;
    }

    const fields = {
        title: form.querySelector('#title'),
        amount: form.querySelector('#amount'),
        expense_date: form.querySelector('#expense_date'),
        category: form.querySelector('#category'),
        notes: form.querySelector('#notes'),
    };

    let scanRequestId = 0;

    receiptInput.addEventListener('change', async () => {
        const file = receiptInput.files?.[0];

        clearAutoFilledMarks();

        if (! file) {
            hidePreview();
            setStatus('', 'idle');

            return;
        }

        showPreview(file);

        if (! scanEnabled) {
            setStatus(form.dataset.expenseScanDisabledMessage ?? 'Receipt scanning is unavailable.', 'info');

            return;
        }

        if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
            setStatus(form.dataset.expenseScanPdfMessage ?? 'PDF uploaded. Enter details manually or upload a photo receipt.', 'info');

            return;
        }

        const requestId = ++scanRequestId;
        setStatus(form.dataset.expenseScanLoadingMessage ?? 'Reading receipt…', 'loading');

        const body = new FormData();
        body.append('receipt', file);

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            const payload = await response.json();

            if (requestId !== scanRequestId) {
                return;
            }

            if (! response.ok || ! payload.success) {
                setStatus(payload.message ?? form.dataset.expenseScanFailedMessage ?? 'Could not read receipt.', 'error');

                return;
            }

            applyExtractedData(payload.data ?? {});
            setStatus(payload.message ?? form.dataset.expenseScanSuccessMessage ?? 'Fields filled from receipt. Please review before submitting.', 'success');
        } catch {
            if (requestId !== scanRequestId) {
                return;
            }

            setStatus(form.dataset.expenseScanFailedMessage ?? 'Could not read receipt.', 'error');
        }
    });

    function applyExtractedData(data) {
        if (data.title && fields.title) {
            fields.title.value = data.title;
            markAutoFilled(fields.title);
        }

        if (data.amount && fields.amount) {
            fields.amount.value = Number(data.amount).toFixed(2);
            markAutoFilled(fields.amount);
        }

        if (data.expense_date && fields.expense_date) {
            fields.expense_date.value = data.expense_date;
            markAutoFilled(fields.expense_date);
        }

        if (data.category && fields.category) {
            fields.category.value = data.category;
            markAutoFilled(fields.category);
        }

        if (data.notes && fields.notes) {
            fields.notes.value = data.notes;
            markAutoFilled(fields.notes);
        }
    }

    function markAutoFilled(element) {
        element.classList.add('ring-2', 'ring-ziifra-accent/30', 'border-ziifra-accent/50');
        element.dataset.expenseAutoFilled = '1';
    }

    function clearAutoFilledMarks() {
        Object.values(fields).forEach((field) => {
            if (! field) {
                return;
            }

            field.classList.remove('ring-2', 'ring-ziifra-accent/30', 'border-ziifra-accent/50');
            delete field.dataset.expenseAutoFilled;
        });
    }

    function setStatus(message, state) {
        if (! statusBox) {
            return;
        }

        statusBox.hidden = message === '';
        statusBox.textContent = message;
        statusBox.dataset.state = state;

        statusBox.className = 'rounded-lg border px-4 py-3 text-sm';

        if (state === 'loading') {
            statusBox.classList.add('border-sky-200', 'bg-sky-50', 'text-sky-900');
        } else if (state === 'success') {
            statusBox.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-900');
        } else if (state === 'error') {
            statusBox.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
        } else {
            statusBox.classList.add('border-ziifra-line/80', 'bg-ziifra-cream/60', 'text-ziifra-muted');
        }
    }

    function showPreview(file) {
        if (! preview || ! previewImage) {
            return;
        }

        if (! file.type.startsWith('image/')) {
            preview.hidden = true;

            return;
        }

        previewImage.src = URL.createObjectURL(file);
        preview.hidden = false;
    }

    function hidePreview() {
        if (! preview || ! previewImage) {
            return;
        }

        if (previewImage.src.startsWith('blob:')) {
            URL.revokeObjectURL(previewImage.src);
        }

        previewImage.removeAttribute('src');
        preview.hidden = true;
    }
}
