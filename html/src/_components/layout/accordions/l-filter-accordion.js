import Cookies from 'js-cookie'

export default class LayoutFilterAccordion {
    constructor(el) {
        this.cookieName = "product-list-accordion-filter";
        this.el = el;
        this.label = el.querySelector('[data-l-filter-accordion-title]');
    }

    bindEvents() {
        this.label.addEventListener('click', this.onClick.bind(this));
    }

    onClick(e) {
        if (this.el.classList.contains('expanded')) { // collapsed
            this.el.classList.remove('expanded');

                if (typeof this.el.dataset.lFilterAccordion !== "undefined") {
                    this.addToCookie(this.el.dataset.lFilterAccordion, "collapsed");
                }
        } else { // expanded
            this.el.classList.add('expanded');

                if (typeof this.el.dataset.lFilterAccordion !== "undefined") {
                    this.addToCookie(this.el.dataset.lFilterAccordion, "expanded");
                }
        }
    }

    destroy() {
        this.label.removeEventListener('click', this.onClick.bind(this));
    }

    addToCookie(name, value) {
        let cookieValue = this.getCookie();
        cookieValue[name] = value;
        Cookies.set(this.cookieName, JSON.stringify(cookieValue));

    }

    getCookie() {
        let cookieValue = Cookies.get(this.cookieName);
        if (typeof cookieValue === "undefined") {
            return {};
        }
        return JSON.parse(cookieValue);
    }

}