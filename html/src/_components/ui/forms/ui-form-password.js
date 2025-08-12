export default class UiFormPassword {

    constructor(el) {
        this.el = el;
        this.name = el.dataset.uiFormPassword;
        this.buttons = document.querySelectorAll(`[data-ui-form-password-btn="${this.name}"]`);
    }

    bindEvents() {
        this.buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (this.el.type == 'password') {
                    this.el.type = 'text';
                } else {
                    this.el.type = 'password';
                }
                this.el.focus();
            });
        });
    }

}