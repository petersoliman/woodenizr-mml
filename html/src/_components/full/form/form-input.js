document.querySelectorAll('[data-form-input]').forEach((div) => {
    const input = div.querySelector('[data-form-input-input]');
    const passwordToggleBtn = div.querySelector('[data-form-input-password-btn]');
    if (passwordToggleBtn) {
        passwordToggleBtn.addEventListener('click', () => {
            if (input.type == 'text') {
                input.type = 'password';
            } else {
                input.type = 'text';
            }
        });
    }
});