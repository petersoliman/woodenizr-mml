import { closest } from "../../helpers/dom";

export default class LayoutMegaDropdown {
    constructor(el) {
        this.el = el;
        this.button = el.querySelector('[data-l-mega-dropdown-button]');
        this.menu = el.querySelector('[data-l-mega-dropdown-menu]');
        return this;
    }
    bindEvents() {
        this.button.addEventListener('click', () => {
            if (this.menu.classList.contains('show-mega-dropdown')) {
                this.hide();
            } else {
                this.hideAllMegaDropdowns();
                this.show();
            }
        });
        if (typeof window.megaDropdownEventListener === 'undefined') {
            window.megaDropdownEventListener = true;
            window.addEventListener('click', (e) => {
                let anyMegaDropdownisShown = false;
                document.querySelectorAll('[data-l-mega-dropdown-menu]').forEach(dropdown => {
                    if (dropdown.classList.contains('show-mega-dropdown')) {
                        anyMegaDropdownisShown = true;
                    }
                });
                if (anyMegaDropdownisShown) {
                    const btn = e.target.dataset.lMegaDropdownButton ? e.target : closest(e.target, '[data-l-mega-dropdown-button]');
                    const menu = e.target.dataset.lMegaDropdownMenu ? e.target : closest(e.target, '[data-l-mega-dropdown-menu]');
                    if (!btn && !menu) {
                        this.hideAllMegaDropdowns();
                    }
                }
            });
        }
    }
    hide() {
        this.button.classList.remove('active-mega-dropdown');
        this.menu.classList.remove('show-mega-dropdown');
        document.querySelector('[data-m-body-backdrop]').classList.remove('show');
        document.querySelector('[data-m-footer-backdrop]').classList.remove('show');
    }
    show() {
        this.button.classList.add('active-mega-dropdown');
        this.menu.classList.add('show-mega-dropdown');
        setTimeout(() => {
            document.querySelector('[data-m-body-backdrop]').classList.add('show');
            document.querySelector('[data-m-footer-backdrop]').classList.add('show');
        }, 5);
    }
    hideAllMegaDropdowns() {
        document.querySelectorAll('[data-l-mega-dropdown]').forEach(dropdown => {
            const menu = dropdown.querySelector('[data-l-mega-dropdown-menu]');
            const button = dropdown.querySelector('[data-l-mega-dropdown-button]');
            button.classList.remove('active-mega-dropdown');
            menu.classList.remove('show-mega-dropdown');
            document.querySelector('[data-m-body-backdrop]').classList.remove('show');
            document.querySelector('[data-m-footer-backdrop]').classList.remove('show');
        });
    }
}