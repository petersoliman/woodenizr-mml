import { closest } from "../../helpers/dom";

export default class LayoutDropdown {
    constructor(el) {
        this.el = el;
        this.button = el.querySelector('[data-l-dropdown-button]');
        this.menu = el.querySelector('[data-l-dropdown-menu]');
        return this;
    }
    bindEvents() {
        this.button.addEventListener('click', () => {
            if (this.menu.classList.contains('show')) {
                this.hide();
            } else {
                this.hideAllDropdowns();
                this.show();
            }
        });
        if (typeof window.dropdownEventListener === 'undefined') {
            window.dropdownEventListener = true;
            window.addEventListener('click', (e) => {
                const btn = e.target.dataset.lDropdownButton ? e.target : closest(e.target, '[data-l-dropdown-button]');
                if (!btn) {
                    this.hideAllDropdowns();
                }
            });
        }
    }
    hide() {
        this.button.classList.remove('active');
        this.menu.classList.remove('show');
    }
    show() {
        this.button.classList.add('active');
        this.menu.classList.add('show');
    }
    hideAllDropdowns() {
        document.querySelectorAll('[data-l-dropdown]').forEach(dropdown => {
            const menu = dropdown.querySelector('[data-l-dropdown-menu]');
            const button = dropdown.querySelector('[data-l-dropdown-button]');
            button.classList.remove('active');
            menu.classList.remove('show');
        });
    }
}