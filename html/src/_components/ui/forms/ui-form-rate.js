export default class UiFormRate {

    constructor(el) {
        this.el = el;
        this.labels = el.querySelectorAll('[data-ui-form-rate-label]');
    }

    bindEvents() {
        this.labels.forEach(label => {
            label.addEventListener('click', (e) => {
                const input = this.el.querySelector(`[data-ui-form-rate-input="${label.dataset.uiFormRateLabel}"]`);
                if (input.checked) {
                    e.preventDefault();
                    input.checked = false;
                }
            });
        });
    }

}