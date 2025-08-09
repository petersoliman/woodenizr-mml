import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import LayoutFilterAccordion from '../../_components/layout/accordions/l-filter-accordion';
import LayoutDropdown from '../../_components/layout/dropdowns/l-dropdown';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormRange from '../../_components/ui/forms/ui-form-range';
import {savedDebounce} from '../../_components/helpers/requests';
import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import {addLiveEventListener, createNode} from '../../_components/helpers/dom';
import UiProductSlide from '../../_components/ui/slides/ui-product-slide';

let filtersAccordions = [];
document.querySelectorAll('[data-l-filter-accordion]').forEach(el => {
    const accordion = new LayoutFilterAccordion(el);
    accordion.bindEvents();
    filtersAccordions.push(accordion);
});
let filterFormRange = {};
if (document.querySelector('[data-ui-form-range="price"]')) {
    filterFormRange = new UiFormRange(document.querySelector('[data-ui-form-range="price"]'));
    filterFormRange.init().bindEvents()
}
if (document.querySelector('[data-product-list-grid-sort-dropdown="true"]')) {
    new LayoutDropdown(document.querySelector('[data-product-list-grid-sort-dropdown="true"]')).bindEvents();
}

initTooltips();


document.querySelectorAll('.filter-dropdown, .backdrop').forEach(elm => {
    elm.addEventListener('click', e => {
        document.querySelector('.l-product-list-grid-col-filters').style.display = (document.querySelector('.l-product-list-grid-col-filters').style.display && document.querySelector('.l-product-list-grid-col-filters').style.display == 'block') ? 'none' : 'block';
    });
});

class LayoutProductListGrid {
    constructor(el) {
        this.el = el;
    }

    init() {
        this.totalNode = this.el.querySelector('[data-l-product-list-grid-total]');
        this.productsContainerNode = this.el.querySelector('[data-l-product-list-grid-products-container]');
        this.itemsNode = this.el.querySelector('[data-l-product-list-grid-items]');
        this.emptyItemsNode = this.el.querySelector('[data-l-product-list-grid-items-empty]');
        this.paginationNode = this.el.querySelector('[data-l-product-list-grid-pagination]');
        this.sortNodes = this.el.querySelectorAll('[data-l-product-list-sort-btn]');
        this.url = this.el.dataset.lProductListGridUrl;
        let bodyFormData = new FormData();
        this.productsContainerNode.classList.add('loading');
        const url = this.el.dataset.lProductListGridUrl;
        axios.get(url, bodyFormData).then(response => {
            if (response.data.noOfProducts > 0) {
                this.itemsNode.removeAttribute('style');
                this.paginationNode.removeAttribute('style');
                this.emptyItemsNode.style.display = 'none';
            } else {
                this.emptyItemsNode.removeAttribute('style');
                this.itemsNode.style.display = 'none';
                this.paginationNode.style.display = 'none';
            }
            this.productsContainerNode.classList.remove('loading');
            this.totalNode.innerText = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(response.data.noOfProducts);
            this.paginationNode.innerHTML = response.data.paginationHTML;

            document.querySelectorAll('[data-l-filter-accordion]').forEach(el => {
                const accordion = new LayoutFilterAccordion(el);
                accordion.bindEvents();
                filtersAccordions.push(accordion);
            });
            if (document.querySelector('[data-ui-form-range="price"]')) {
                filterFormRange = new UiFormRange(document.querySelector('[data-ui-form-range="price"]'));
                // console.log(filterFormRange);
                filterFormRange.init().bindEvents()
            }
            
            // console.log(filtersAccordions);
            // console.log(filterFormRange);
            // or live
            // document.querySelectorAll('[data-l-filter-accordion]').forEach(el => {
            //     new LayoutFilterAccordion(el).bindEvents();
            // });
            // if (document.querySelector('[data-ui-form-range="price"]')) {
            //     new UiFormRange(document.querySelector('[data-ui-form-range="price"]')).init().bindEvents();
            // }
            this.itemsNode.innerHTML = '';
            response.data.products.forEach(product => {
                let productNode = createNode(`<div class="l-flex-col-xxl-3 l-flex-col-md-4 l-flex-col-xs-12 l-product-slide-col ui-product-slide-col"></div>`);
                let productSlide = new UiProductSlide(product);
                productNode.firstChild.appendChild(productSlide);
                this.itemsNode.appendChild(productNode);
            });
            /*window.scrollTo({
                top: document.querySelector( "[data-l-product-list-grid]").offsetTop,
                behavior: 'smooth'
            });*/
            initTooltips();

        }).catch(error => {
            this.productsContainerNode.classList.remove('loading');
            this.filters.el.classList.remove('loading');
            new LayoutToast(error.message, 'error');
        });
    }
}


window.addEventListener('DOMContentLoaded', (e) => {
    const products = new LayoutProductListGrid(document.querySelector('[data-l-product-list-grid]'));
    products.init();
    if (document.querySelector('[data-qty-dropdown-button="true"]')) {
        new LayoutDropdown(document.querySelector('[data-qty-dropdown="true"]')).bindEvents();
        addLiveEventListener('click', '[data-qty-dropdown-item]', (e) => {
            const qtyItemBtns = document.querySelectorAll('[data-qty-dropdown-item]');
            const qtyText = document.querySelector('[data-qty-dropdown-text]');
            const cartBtn = document.querySelector('[data-qty-dropdown-cart-btn]');
            const btn = typeof e.target.dataset.qtyDropdownItem != 'undefined' ? e.target : closest(e.target, '[data-qty-dropdown-item]');
            qtyItemBtns.forEach(c => c.classList.remove('active'));
            qtyText.innerText = btn.dataset.qtyDropdownItemValue;
            cartBtn.dataset.headerCartSidebarAddProductBtnQty = btn.dataset.qtyDropdownItemValue;
            btn.classList.add('active');
        });
    }
});