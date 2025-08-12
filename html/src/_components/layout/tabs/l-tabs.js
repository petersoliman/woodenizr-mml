import { closest } from "../../helpers/dom";

export default class LayoutTabs {
    constructor(el) {
        this.el = el;
        this.tabBtns = el.querySelectorAll('[data-l-tab-btn]');
        this.tabs = closest(el, '[data-l-tabs-container]').querySelectorAll('[data-l-tab]');
        this.onChange = () => {};
        return this;
    }
    bindEvents() {
        this.tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.dataset.lTabBtn;
                let tab = {};
                this.tabBtns.forEach(b => {
                    if (b.dataset.lTabBtn == name) {
                        b.classList.add('active');
                        return;
                    }
                    b.classList.remove('active')
                });
                this.tabs.forEach(t => {
                    if (t.dataset.lTab == name) {
                        t.classList.add('show');
                        tab = t;
                        return;
                    }
                    t.classList.remove('show');
                });
                this.onChange(tab, btn);
            });
        })
    }
}