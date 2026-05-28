function initPayrollTimeGrid() {
    const root = document.querySelector('[data-payroll-time]');

    if (! root) {
        return;
    }

    const csrf = root.dataset.csrf;
    const upsertUrl = root.dataset.upsertUrl;
    const rateUrlTemplate = root.dataset.rateUrlTemplate;
    const projectId = root.dataset.projectId ? Number.parseInt(root.dataset.projectId, 10) : null;
    const timers = new WeakMap();

    const number = (value) => {
        const parsed = Number.parseFloat(value);

        return Number.isNaN(parsed) ? 0 : parsed;
    };

    const flagState = (input, state) => {
        input.classList.remove('ziifra-pt-cell--saving', 'ziifra-pt-cell--saved', 'ziifra-pt-cell--error');

        if (state) {
            input.classList.add(`ziifra-pt-cell--${state}`);
        }
    };

    const rateUrlFor = (employeeId) => rateUrlTemplate.replace('__EMPLOYEE__', employeeId);

    const recalcRow = (row) => {
        const isMonthly = row.dataset.monthly === '1';
        const rate = number(row.dataset.rate);
        const trustPct = number(row.dataset.trust);

        let totalHours = 0;
        row.querySelectorAll('[data-pt-hours]').forEach((input) => {
            totalHours += number(input.value);
        });

        const gross = isMonthly ? number(row.dataset.gross) : Math.round(totalHours * rate * 100) / 100;
        const trustAmount = Math.round(gross * (trustPct / 100) * 100) / 100;

        row.dataset.gross = gross;

        const totalHoursEl = row.querySelector('[data-pt-total-hours]');
        const trustAmountEl = row.querySelector('[data-pt-trust-amount]');
        const grossEl = row.querySelector('[data-pt-gross]');

        if (totalHoursEl) {
            totalHoursEl.textContent = totalHours.toFixed(1);
        }

        if (trustAmountEl) {
            trustAmountEl.textContent = trustAmount.toFixed(2);
        }

        if (grossEl) {
            grossEl.textContent = gross.toFixed(2);
        }

        recalcFooter();
    };

    const recalcFooter = () => {
        let hours = 0;
        let gross = 0;
        let trust = 0;

        document.querySelectorAll('[data-pt-row]').forEach((row) => {
            const isMonthly = row.dataset.monthly === '1';
            const rate = number(row.dataset.rate);
            const trustPct = number(row.dataset.trust);

            let rowHours = 0;
            row.querySelectorAll('[data-pt-hours]').forEach((input) => {
                rowHours += number(input.value);
            });

            const rowGross = isMonthly ? number(row.dataset.gross) : rowHours * rate;
            hours += rowHours;
            gross += rowGross;
            trust += rowGross * (trustPct / 100);
        });

        const footHours = document.querySelector('[data-pt-foot-hours]');
        const footGross = document.querySelector('[data-pt-foot-gross]');
        const footTrust = document.querySelector('[data-pt-foot-trust]');

        if (footHours) {
            footHours.textContent = hours.toFixed(1);
        }

        if (footGross) {
            footGross.textContent = gross.toFixed(2);
        }

        if (footTrust) {
            footTrust.textContent = trust.toFixed(2);
        }
    };

    const saveHours = async (input) => {
        if (! projectId) {
            return;
        }

        const employeeId = Number.parseInt(input.dataset.employeeId, 10);
        const hours = number(input.value);

        flagState(input, 'saving');

        try {
            const response = await fetch(upsertUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    employee_id: employeeId,
                    project_id: projectId,
                    work_date: input.dataset.workDate,
                    hours,
                }),
            });

            if (! response.ok) {
                throw new Error('save failed');
            }

            flagState(input, 'saved');
            window.setTimeout(() => flagState(input, null), 800);
        } catch {
            flagState(input, 'error');
        }
    };

    const saveRate = async (row, payload) => {
        const employeeId = Number.parseInt(row.dataset.employeeId, 10);

        try {
            const response = await fetch(rateUrlFor(employeeId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            if (! response.ok) {
                throw new Error('save failed');
            }
        } catch {
            // Keep the on-screen value; a reload will resync from the server.
        }
    };

    const debounce = (input, fn, delay = 500) => {
        clearTimeout(timers.get(input));
        timers.set(input, setTimeout(fn, delay));
    };

    document.querySelectorAll('[data-pt-hours]').forEach((input) => {
        input.addEventListener('input', () => {
            recalcRow(input.closest('[data-pt-row]'));
            debounce(input, () => saveHours(input));
        });

        input.addEventListener('change', () => saveHours(input));
    });

    document.querySelectorAll('[data-pt-rate]').forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('[data-pt-row]');
            row.dataset.rate = number(input.value);
            recalcRow(row);
            debounce(input, () => saveRate(row, { fixed_hourly_rate: number(input.value) }));
        });
    });

    document.querySelectorAll('[data-pt-trust]').forEach((input) => {
        input.addEventListener('input', () => {
            const row = input.closest('[data-pt-row]');
            row.dataset.trust = number(input.value);
            recalcRow(row);
            debounce(input, () => saveRate(row, { trust_override_percent: number(input.value) }));
        });
    });
}

function initPayrollTimeFilter() {
    const form = document.querySelector('[data-payroll-time-filter]');

    if (! form) {
        return;
    }

    form.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => form.requestSubmit());
    });

    const search = form.querySelector('[data-payroll-time-search]');

    if (search) {
        let timer = null;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form.requestSubmit(), 350);
        });
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(timer);
                form.requestSubmit();
            }
        });
    }
}

initPayrollTimeGrid();
initPayrollTimeFilter();
