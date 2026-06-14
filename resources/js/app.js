import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const updateThemeToggle = () => {
    const theme = document.documentElement.getAttribute('data-bs-theme') ?? 'light';
    const icon = document.querySelector('[data-theme-icon]');
    const label = document.querySelector('[data-theme-label]');

    if (icon) {
        icon.textContent = theme === 'dark' ? '☀' : '☾';
    }

    if (label) {
        label.textContent = theme === 'dark' ? 'Helle Darstellung' : 'Dunkle Darstellung';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    updateThemeToggle();

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') ?? 'light';
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-bs-theme', nextTheme);

        try {
            localStorage.setItem('okgv-theme', nextTheme);
        } catch {
            // The selected theme still applies for the current page.
        }

        updateThemeToggle();
    });
});
