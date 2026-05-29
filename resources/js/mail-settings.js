export function initMailSettings() {
    const root = document.querySelector('[data-mail-settings]');

    if (! root) {
        return;
    }

    const toggle = root.querySelector('[data-mail-enabled]');
    const fields = root.querySelector('[data-mail-fields]');
    const hostInput = root.querySelector('#mail_host');
    const portInput = root.querySelector('#mail_port');
    const encryptionSelect = root.querySelector('#mail_encryption');

    const defaultPorts = {
        tls: '587',
        ssl: '465',
        none: '25',
    };

    const applyEnabledState = () => {
        const on = toggle?.checked ?? false;

        if (fields) {
            fields.classList.toggle('opacity-50', ! on);
            fields.classList.toggle('pointer-events-none', ! on);
            fields.setAttribute('aria-hidden', on ? 'false' : 'true');
        }
    };

    toggle?.addEventListener('change', applyEnabledState);
    applyEnabledState();

    encryptionSelect?.addEventListener('change', () => {
        const encryption = encryptionSelect.value;

        if (portInput && (! portInput.value || portInput.dataset.autoPort === '1')) {
            portInput.value = defaultPorts[encryption] ?? '587';
            portInput.dataset.autoPort = '1';
        }
    });

    portInput?.addEventListener('input', () => {
        portInput.dataset.autoPort = '0';
    });

    root.querySelectorAll('[data-mail-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const host = button.dataset.host ?? '';
            const port = button.dataset.port ?? '587';
            const encryption = button.dataset.encryption ?? 'tls';

            if (hostInput) {
                hostInput.value = host;
            }

            if (encryptionSelect) {
                encryptionSelect.value = encryption;
            }

            if (portInput) {
                portInput.value = port;
                portInput.dataset.autoPort = '1';
            }

            if (toggle && ! toggle.checked) {
                toggle.checked = true;
                applyEnabledState();
            }
        });
    });
}
