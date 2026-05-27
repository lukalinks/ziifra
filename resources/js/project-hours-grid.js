const dirtyInputs = new Set();

export function initProjectHoursGrid() {
    const config = window.ziifraProjectHours;

    if (!config?.upsertUrl) {
        return;
    }

    const standardDayHours = config.standardDayHours ?? 8;
    const currency = config.currency ?? 'EUR';
    const timers = new WeakMap();
    const saveButtons = document.querySelectorAll('[data-time-attendance-save]');

    document.querySelectorAll('[data-project-hours-grid] input[data-employee-id]').forEach((input) => {
        input.addEventListener('change', () => {
            markDirty(input);
            saveCell(input, config, standardDayHours);
        });

        input.addEventListener('input', () => {
            markDirty(input);
            updateCellTone(input, standardDayHours);
            updateRowTotals(input.closest('[data-employee-row]'), standardDayHours, currency);
            updateFooterTotals(currency);

            clearTimeout(timers.get(input));
            timers.set(input, setTimeout(() => saveCell(input, config, standardDayHours), 500));
        });
    });

    saveButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                await Promise.all([...dirtyInputs].map((input) => saveCell(input, config, standardDayHours, true)));
            } finally {
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-project-hours-month]').forEach((field) => {
        field.addEventListener('change', () => field.closest('form')?.requestSubmit());
    });
}

function markDirty(input) {
    input.dataset.dirty = 'true';
    dirtyInputs.add(input);
    document.querySelectorAll('[data-time-attendance-save]').forEach((button) => {
        button.hidden = false;
    });
}

function clearDirty(input) {
    delete input.dataset.dirty;
    dirtyInputs.delete(input);
    input.classList.remove('ziifra-time-attendance-cell--error');

    if (dirtyInputs.size === 0) {
        document.querySelectorAll('[data-time-attendance-save]').forEach((button) => {
            button.hidden = true;
        });
    }
}

function parseHours(input) {
    return input.value === '' ? 0 : Number.parseFloat(input.value);
}

function updateCellTone(input, standardDayHours) {
    const hours = parseHours(input);

    input.classList.toggle('ziifra-time-attendance-cell--filled', hours > 0 && hours <= standardDayHours);
    input.classList.toggle('ziifra-time-attendance-cell--overtime', hours > standardDayHours);
}

function updateRowTotals(row, standardDayHours, currency) {
    if (!row) {
        return;
    }

    const rate = Number.parseFloat(row.dataset.hourlyRate || '0');
    let total = 0;

    row.querySelectorAll('input[data-employee-id]').forEach((input) => {
        const hours = parseHours(input);

        if (!Number.isNaN(hours)) {
            total += hours;
        }
    });

    const totalEl = row.querySelector('[data-row-total]');
    const payEl = row.querySelector('[data-row-pay]');

    if (totalEl) {
        totalEl.textContent = `${Math.round(total)}h`;
    }

    if (payEl) {
        payEl.textContent = `${Math.round(total * rate).toLocaleString()} ${currency}`;
    }
}

function updateFooterTotals(currency) {
    let totalHours = 0;
    let totalPayroll = 0;

    document.querySelectorAll('[data-employee-row]').forEach((row) => {
        const rate = Number.parseFloat(row.dataset.hourlyRate || '0');
        let rowTotal = 0;

        row.querySelectorAll('input[data-employee-id]').forEach((input) => {
            const hours = parseHours(input);

            if (!Number.isNaN(hours)) {
                rowTotal += hours;
            }
        });

        totalHours += rowTotal;
        totalPayroll += rowTotal * rate;
    });

    const hoursEl = document.querySelector('[data-footer-hours]');
    const payrollEl = document.querySelector('[data-footer-payroll]');

    if (hoursEl) {
        hoursEl.textContent = `${Math.round(totalHours)}h`;
    }

    if (payrollEl) {
        payrollEl.textContent = `${currency} ${Math.round(totalPayroll).toLocaleString()}`;
    }
}

async function saveCell(input, config, standardDayHours, force = false) {
    if (!force && input.dataset.dirty !== 'true') {
        return;
    }

    const hours = parseHours(input);

    if (Number.isNaN(hours)) {
        return;
    }

    input.disabled = true;

    try {
        const response = await fetch(config.upsertUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrf,
            },
            body: JSON.stringify({
                employee_id: Number.parseInt(input.dataset.employeeId, 10),
                work_date: input.dataset.workDate,
                hours,
            }),
        });

        if (!response.ok) {
            throw new Error('Save failed');
        }

        updateCellTone(input, standardDayHours);
        clearDirty(input);
    } catch {
        input.classList.add('ziifra-time-attendance-cell--error');
    } finally {
        input.disabled = false;
    }
}

initProjectHoursGrid();
