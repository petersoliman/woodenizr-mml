import {closest} from "../../helpers/dom";
export default class LayoutFormDatalist {
    constructor(input, backdrops = ['body', 'footer']) {
        this.onInput = () => {};
        this.input = input;
        this.backdrops = backdrops;
        this.container = closest(this.input, '[data-l-form-datalist-container]');
        this.dropdown = this.container.querySelector('[data-l-form-datalist-dropdown-menu]');
        this.dropdownItems = this.container.querySelector('[data-l-form-datalist-dropdown-menu-items]');
        this.items = this.container.querySelectorAll('[data-l-form-datalist-dropdown-menu-item]');
        return this;
    }

    bindEvents() {
        this.input.addEventListener('focus', () => {
            const value = this.input.value.trim();
            if (value.length > 3) {
                this.showBackdrops();
                this.show();
            }
        });
        this.input.addEventListener('input', () => {
            const value = this.input.value.trim();
            if (value.length > 3) {
                this.showBackdrops();
                this.show();
                this.dropdownItems.classList.add('loading');
                this.onInput(this);
            }
        });
        this.input.addEventListener('focusout', () => {
            setTimeout(() => {
                this.hide();
                this.hideBackdrops();
            }, 200);
        });
    }

    hide() {
        this.dropdown.classList.remove('show');
    }

    hideBackdrops() {
        this.backdrops.forEach(backdropName => {
            document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.remove('show');
        });
    }

    showBackdrops() {
        setTimeout(() => {
            this.backdrops.forEach(backdropName => {
                document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.add('show');
            });
        }, 5);
    }

    show() {
        this.dropdown.classList.add('show');
    }
}