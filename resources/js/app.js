import './charts/leave-trend.js';
import { initPageLoader } from './page-loader.js';
import { initExpenseReceiptScan } from './expense-receipt-scan.js';
import { initEmployeesQuickFilter, initProjectHoursFilter } from './employees-quick-filter.js';
import { initEmployeeProfileTabs } from './employee-profile-tabs.js';
import { initPayrollCreateForm } from './payroll-create-form.js';
import './searchable-select.js';

import { initConfirmDialog } from './confirm-dialog.js';
import { initNotifications } from './notifications.js';
import { initAdminMobileNav } from './admin-mobile-nav.js';
import { initMobileNav } from './mobile-nav.js';
import { initTheme, initThemeSwitcher } from './theme.js';

initTheme();
initThemeSwitcher();
initMobileNav();
initAdminMobileNav();
initPageLoader();
initConfirmDialog();
initNotifications();
initExpenseReceiptScan();
initEmployeesQuickFilter();
initProjectHoursFilter();
initEmployeeProfileTabs();
initPayrollCreateForm();

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const inputId = button.getAttribute('data-password-toggle');
    const input = document.getElementById(inputId);

    if (! input) {
        return;
    }

    button.addEventListener('click', () => {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.querySelector('.ziifra-icon-show')?.classList.toggle('hidden', isHidden);
        button.querySelector('.ziifra-icon-hide')?.classList.toggle('hidden', ! isHidden);
    });
});

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-form-submit]');

        if (! submit || submit.disabled) {
            return;
        }

        submit.disabled = true;
        submit.querySelector('[data-form-submit-label]')?.classList.add('hidden');
        const spinner = submit.querySelector('[data-form-submit-spinner]');
        spinner?.classList.remove('hidden');
        spinner?.classList.add('inline-flex');
    });
});

const payrollLineIndexPattern = /\[allowance_lines\]\[\d+\]/;

document.addEventListener('click', (event) => {
    const payrollBtn = event.target.closest('[data-add-payroll-allowance-line]');

    if (payrollBtn) {
        event.preventDefault();
        const wrap = payrollBtn.closest('[data-payroll-allowance-lines]');
        const rowsRoot = wrap?.querySelector('.payroll-allowance-line-rows');

        if (! wrap || ! rowsRoot) {
            return;
        }

        const rows = rowsRoot.querySelectorAll('.payroll-allowance-line');
        const last = rows[rows.length - 1];

        if (! last) {
            return;
        }

        let next = Number.parseInt(wrap.dataset.nextIndex ?? '0', 10);

        if (Number.isNaN(next)) {
            next = rows.length;
        }

        const clone = last.cloneNode(true);

        clone.querySelectorAll('input').forEach((el) => {
            el.value = '';
            const name = el.getAttribute('name');

            if (name) {
                el.setAttribute('name', name.replace(payrollLineIndexPattern, `[allowance_lines][${next}]`));
            }
        });

        clone.querySelectorAll('select').forEach((el) => {
            el.selectedIndex = 0;
            const name = el.getAttribute('name');

            if (name) {
                el.setAttribute('name', name.replace(payrollLineIndexPattern, `[allowance_lines][${next}]`));
            }
        });

        rowsRoot.append(clone);
        wrap.dataset.nextIndex = String(next + 1);

        return;
    }

    const employeeBtn = event.target.closest('[data-add-employee-allowance-template]');

    if (employeeBtn) {
        event.preventDefault();
        const wrap = employeeBtn.closest('[data-employee-allowance-templates]');
        const rowsRoot = wrap?.querySelector('.employee-allowance-template-rows');

        if (! wrap || ! rowsRoot) {
            return;
        }

        const rows = rowsRoot.querySelectorAll('.employee-allowance-template-row');
        const last = rows[rows.length - 1];

        if (! last) {
            return;
        }

        let next = Number.parseInt(wrap.dataset.nextIndex ?? '0', 10);

        if (Number.isNaN(next)) {
            next = rows.length;
        }

        const clone = last.cloneNode(true);

        clone.querySelectorAll('input').forEach((el) => {
            el.value = '';
            const name = el.getAttribute('name');

            if (name) {
                el.setAttribute('name', name.replace(/allowance_templates\[\d+]/, `allowance_templates[${next}]`));
            }
        });

        clone.querySelectorAll('select').forEach((el) => {
            el.selectedIndex = 0;
            const name = el.getAttribute('name');

            if (name) {
                el.setAttribute('name', name.replace(/allowance_templates\[\d+]/, `allowance_templates[${next}]`));
            }
        });

        rowsRoot.append(clone);
        wrap.dataset.nextIndex = String(next + 1);
    }
});
