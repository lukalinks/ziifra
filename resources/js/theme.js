const STORAGE_KEY = 'ziifra-theme';

export function getStoredPreference() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    } catch {
        return 'system';
    }
}

export function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function resolveTheme(preference = getStoredPreference()) {
    if (preference === 'system') {
        return getSystemTheme();
    }

    return preference === 'dark' ? 'dark' : 'light';
}

export function applyTheme(theme = resolveTheme()) {
    document.documentElement.dataset.theme = theme;
    document.documentElement.style.colorScheme = theme;

    const meta = document.querySelector('meta[name="theme-color"]');

    if (meta) {
        meta.content = theme === 'dark' ? '#070b12' : '#f6f5f2';
    }

    document.querySelectorAll('[data-theme-switcher]').forEach((root) => {
        root.querySelectorAll('[data-theme-option]').forEach((button) => {
            const active = button.dataset.themeOption === theme;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('ziifra-theme-option-active', active);
        });
    });

    document.dispatchEvent(new CustomEvent('ziifra:themechange', { detail: { theme } }));
}

export function setThemePreference(preference) {
    try {
        localStorage.setItem(STORAGE_KEY, preference);
    } catch {
        //
    }

    applyTheme(resolveTheme(preference));
}

export function initTheme() {
    applyTheme();

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getStoredPreference() === 'system') {
            applyTheme();
        }
    });
}

export function initThemeSwitcher() {
    document.querySelectorAll('[data-theme-switcher]').forEach((root) => {
        root.querySelectorAll('[data-theme-option]').forEach((button) => {
            button.addEventListener('click', () => {
                setThemePreference(button.dataset.themeOption);
            });
        });
    });
}
