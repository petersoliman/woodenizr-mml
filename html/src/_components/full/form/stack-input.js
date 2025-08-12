document.querySelectorAll('[data-stack-input]').forEach((stack) => {
    const label = stack.querySelector('[data-stack-input-label]');
    const input = stack.querySelector('[data-stack-input-input]');
    refreshUI(stack, input, label);
    input.addEventListener('input', () => {
        if (!stack.classList.contains('dirty')) {
            stack.classList.add('dirty');
        }
        refreshUI(stack, input, label);
    });
    input.addEventListener('change', () => {
        if (!stack.classList.contains('dirty')) {
            stack.classList.add('dirty');
        }
        refreshUI(stack, input, label);
    });
});

function refreshUI(stack, input, label) {
    if (input.value) {
        stack.classList.add('has-value');
    } else {
        stack.classList.remove('has-value');
    }
}