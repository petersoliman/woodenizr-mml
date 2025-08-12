import { closest } from "../../helpers/dom";

export default class LayoutPhoneOffsidebar {
    constructor(button, menu) {
        this.button = button;
        this.menu = menu;
        this.closeButton = menu.querySelector('[data-l-phone-offsidebar-close-btn]');
        this.menuItems = menu.querySelectorAll('[data-l-phone-offsidebar-menu-item]');
        this.backButtons = menu.querySelectorAll('[data-l-phone-offsidebar-menu-item-dropdown-back-btn]');
    }
    bindEvents() {
        this.button.addEventListener('click', () => {
            this.menu.classList.add('show');
        });
        this.closeButton.addEventListener('click', () => {
            this.menu.classList.remove('show');
        });
        this.menuItems.forEach(el => {
            el.addEventListener('click', () => {
                const dropdown = closest(el, '[data-l-phone-offsidebar-menu-item-container="true"]').querySelector('[data-l-phone-offsidebar-menu-item-dropdown="true"]');
                dropdown.classList.add('show');
            });
        });
        this.backButtons.forEach(el => {
            el.addEventListener('click', () => {
                const dropdown = closest(el, '[data-l-phone-offsidebar-menu-item-container="true"]').querySelector('[data-l-phone-offsidebar-menu-item-dropdown="true"]');
                dropdown.classList.remove('show');
            });
        });
    }
}