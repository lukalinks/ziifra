export function initEmployeeProfileTabs() {
    const root = document.querySelector('[data-employee-profile-tabs]');

    if (!root) {
        return;
    }

    const tabs = root.querySelectorAll('[data-employee-tab]');
    const panels = root.querySelectorAll('[data-employee-panel]');
    const jumps = document.querySelectorAll('[data-employee-tab-target]');

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    const defaultTab = tabs[0]?.dataset.employeeTab ?? 'overview';
    const validTabs = new Set([...tabs].map((tab) => tab.dataset.employeeTab).filter(Boolean));

    const syncUrl = (tabId) => {
        const url = new URL(window.location.href);

        if (tabId === defaultTab) {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', tabId);
        }

        const next = `${url.pathname}${url.search}${url.hash}`;
        const current = `${window.location.pathname}${window.location.search}${window.location.hash}`;

        if (next !== current) {
            window.history.replaceState({}, '', url);
        }
    };

    const activate = (tabId, { updateUrl = true } = {}) => {
        if (!validTabs.has(tabId)) {
            return;
        }

        tabs.forEach((tab) => {
            const isActive = tab.dataset.employeeTab === tabId;
            tab.classList.toggle('ziifra-employee-workspace-tab-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.employeePanel !== tabId;
        });

        if (updateUrl) {
            syncUrl(tabId);
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.employeeTab));
    });

    jumps.forEach((jump) => {
        jump.addEventListener('click', () => activate(jump.dataset.employeeTabTarget));
    });

    const params = new URLSearchParams(window.location.search);
    const requested = params.get('tab');
    const initial = validTabs.has(requested ?? '') ? requested : defaultTab;

    activate(initial, { updateUrl: false });
}
