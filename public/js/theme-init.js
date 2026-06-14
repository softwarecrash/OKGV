(() => {
    let storedTheme = null;

    try {
        storedTheme = localStorage.getItem('okgv-theme');
    } catch {
        storedTheme = null;
    }

    const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';

    document.documentElement.setAttribute('data-bs-theme', storedTheme ?? preferredTheme);
})();
