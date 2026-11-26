import { initExport } from './export';
import { initFlyouts } from './flyout';
import { initRelativeTimes } from './relative-time';
import { applyTheme, getStoredTheme, setTheme } from './theme';

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getStoredTheme() === 'system') {
        applyTheme('system');
    }
});

const themeButton = document.querySelector('[data-theme-button]');
const themeMenu = document.querySelector('[data-theme-menu]');

function toggleThemeMenu(open) {
    themeMenu.hidden = !open;
    themeButton.setAttribute('aria-expanded', String(open));

    if (open) {
        const current = getStoredTheme();

        for (const item of themeMenu.querySelectorAll('[data-theme-value]')) {
            item.setAttribute('aria-checked', String(item.dataset.themeValue === current));
        }

        themeMenu.querySelector('[aria-checked="true"]')?.focus();
    }
}

themeButton?.addEventListener('click', () => toggleThemeMenu(themeMenu.hidden));

themeMenu?.addEventListener('click', (event) => {
    const item = event.target.closest('[data-theme-value]');

    if (item) {
        setTheme(item.dataset.themeValue);
        toggleThemeMenu(false);
        themeButton.focus();
    }
});

document.addEventListener('click', (event) => {
    if (themeMenu && !themeMenu.hidden && !event.target.closest('[data-theme-switch]')) {
        toggleThemeMenu(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && themeMenu && !themeMenu.hidden) {
        toggleThemeMenu(false);
        themeButton.focus();
    }
});

initExport();
initFlyouts();
initRelativeTimes();

for (const select of document.querySelectorAll('select[data-navigate]')) {
    select.addEventListener('change', () => window.location.assign(select.value));
}

for (const form of document.querySelectorAll('form[data-auto-submit]')) {
    form.addEventListener('change', () => form.requestSubmit());
}

for (const range of document.querySelectorAll('input[type="range"][data-output]')) {
    const output = document.getElementById(range.dataset.output);
    range.addEventListener('input', () => {
        output.value = range.value;
    });
}
