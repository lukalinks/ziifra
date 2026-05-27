export function initPayrollCreateForm() {
    const form = document.querySelector('[data-payroll-create-form]');

    if (! form) {
        return;
    }

    const calcMode = form.querySelector('#calculation_mode');
    const genMode = form.querySelector('#generation_mode');
    const audienceSection = form.querySelector('[data-payroll-audience-section]');
    const individualWrap = form.querySelector('[data-payroll-individual-wrap]');
    const groupWrap = form.querySelector('[data-payroll-group-wrap]');

    if (! calcMode || ! genMode || ! audienceSection || ! individualWrap || ! groupWrap) {
        return;
    }

    const setFieldDisabled = (root, disabled) => {
        root.querySelectorAll('select, input').forEach((field) => {
            field.disabled = disabled;
        });
    };

    const sync = () => {
        const isHourly = calcMode.value === 'hourly';

        audienceSection.hidden = ! isHourly;

        if (! isHourly) {
            setFieldDisabled(audienceSection, true);

            return;
        }

        setFieldDisabled(audienceSection, false);

        const mode = genMode.value;
        const showIndividual = mode === 'individual';
        const showGroup = mode === 'group';

        individualWrap.hidden = ! showIndividual;
        groupWrap.hidden = ! showGroup;

        setFieldDisabled(individualWrap, ! showIndividual);
        setFieldDisabled(groupWrap, ! showGroup);
    };

    const modeHints = form.querySelectorAll('[data-payroll-mode-hint]');

    const syncModeHints = () => {
        modeHints.forEach((hint) => {
            hint.classList.toggle('hidden', hint.dataset.payrollModeHint !== calcMode.value);
        });
    };

    calcMode.addEventListener('change', () => {
        sync();
        syncModeHints();
    });
    genMode.addEventListener('change', sync);
    sync();
    syncModeHints();
}
