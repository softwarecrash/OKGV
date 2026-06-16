document.addEventListener('click', (event) => {
    const trigger = event.target instanceof Element
        ? event.target.closest('[data-demo-login]')
        : null;

    if (!(trigger instanceof HTMLElement)) {
        return;
    }

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (emailInput instanceof HTMLInputElement) {
        emailInput.value = trigger.dataset.demoEmail ?? '';
        emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        emailInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (passwordInput instanceof HTMLInputElement) {
        passwordInput.value = trigger.dataset.demoPassword ?? '';
        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
        passwordInput.dispatchEvent(new Event('change', { bubbles: true }));
        passwordInput.focus();
    }
});
