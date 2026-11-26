// Same storage key and values as next-themes on gerritalex.de. The layout inlines an equivalent of
// applyTheme() in <head> so the page never paints in the wrong scheme.
const storageKey = 'theme';

export function getStoredTheme() {
    try {
        return localStorage.getItem(storageKey) ?? 'system';
    } catch {
        return 'system';
    }
}

export function applyTheme(theme) {
    const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', dark);
}

export function setTheme(theme) {
    try {
        localStorage.setItem(storageKey, theme);
    } catch {
        // storage may be unavailable, e.g. in private mode; the choice then lasts for this page only
    }

    applyTheme(theme);
}
