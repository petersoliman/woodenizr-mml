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

// temp code

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
        this.filters = new LayoutProductListGridFilters(document.querySelector('[data-l-product-list-grid-filters]'));
        this.totalNode = this.el.querySelector('[data-l-product-list-grid-total]');
        this.productsContainerNode = this.el.querySelector('[data-l-product-list-grid-products-container]');
        this.itemsNode = this.el.querySelector('[data-l-product-list-grid-items]');
        this.emptyItemsNode = this.el.querySelector('[data-l-product-list-grid-items-empty]');
        this.paginationNode = this.el.querySelector('[data-l-product-list-grid-pagination]');
        this.sortNodes = this.el.querySelectorAll('[data-l-product-list-sort-btn]');
        this.url = this.el.dataset.lProductListGridUrl;
        this.filters.onBeforeInit = () => {
            return new Promise((res, rej) => {
                let bodyFormData = new FormData();
                Object.entries(this.filters.data).forEach(filter => {
                    if (Array.isArray(filter[1])) {
                        filter[1].forEach((f, i) => {
                            bodyFormData.append(filter[0], f);
                        });
                    } else {
                        bodyFormData.append(filter[0], filter[1]);
                    }
                });
                this.productsContainerNode.classList.add('loading');
                this.filters.el.classList.add('loading');
                const url = this.filters.el.dataset.lProductListGridFiltersAutoLoadFromUrl;
                axios.post(url, bodyFormData).then(response => {
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
                    this.filters.el.classList.remove('loading');
                    this.totalNode.innerText = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(response.data.noOfProducts);
                    this.paginationNode.innerHTML = response.data.paginationHTML;

                    this.filters.el.innerHTML = response.data.filterHTML;
                    this.filters.inputs = this.filters.el.querySelectorAll('[data-l-product-list-grid-filters-input]');
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
                    if (typeof this.filters.el.dataset.lProductListGridFiltersAutoLoadFromUrl !== 'undefined') {
                        this.filters.prepareData();
                    }
                    this.filters.el.removeAttribute('data-l-product-list-grid-filters-auto-load-from-url');

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
                        let productNode = createNode(`<div class="l-flex-col-xxl-4 l-flex-col-md-4 l-flex-col-xs-12 l-product-slide-col ui-product-slide-col"></div>`);
                        let productSlide = new UiProductSlide(product);
                        productNode.firstChild.appendChild(productSlide);
                        this.itemsNode.appendChild(productNode);
                    });
                    /*window.scrollTo({
                        top: document.querySelector( "[data-l-product-list-grid]").offsetTop,
                        behavior: 'smooth'
                    });*/
                    if (window.matchMedia('(max-width: 1200px)').matches) {
                        document.querySelector('.l-product-list-grid-col-filters').style.display = 'none';
                    }
                    initTooltips();

                    res();
                }).catch(error => {
                    this.productsContainerNode.classList.remove('loading');
                    this.filters.el.classList.remove('loading');
                    new LayoutToast(error.message, 'error');
                    rej();
                });
            })
        }
        this.filters.onChange = (url) => {
            let bodyFormData = new FormData();
            Object.entries(this.filters.data).forEach(filter => {
                if (Array.isArray(filter[1])) {
                    filter[1].forEach((f, i) => {
                        bodyFormData.append(filter[0], f);
                    });
                } else {
                    bodyFormData.append(filter[0], filter[1]);
                }
            });
            this.productsContainerNode.classList.add('loading');
            this.filters.el.classList.add('loading');
            axios.post(this.url, bodyFormData).then(response => {
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
                this.filters.el.classList.remove('loading');
                this.totalNode.innerText = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(response.data.noOfProducts);
                this.paginationNode.innerHTML = response.data.paginationHTML;
                this.filters.destory();
                filtersAccordions.forEach(accordion => {
                    accordion.destroy();
                });
                filtersAccordions = [];
                filterFormRange.destroy();
                this.filters.el.innerHTML = response.data.filterHTML;
                this.filters.init();
                // this.filters = new LayoutProductListGridFilters(document.querySelector('[data-l-product-list-grid-filters]'));
                document.querySelectorAll('[data-l-filter-accordion]').forEach(el => {
                    const accordion = new LayoutFilterAccordion(el);
                    accordion.bindEvents();
                    filtersAccordions.push(accordion);
                });
                if (document.querySelector('[data-ui-form-range="price"]')) {
                    filterFormRange = new UiFormRange(document.querySelector('[data-ui-form-range="price"]'));
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
                window.scrollTo({
                    top: document.querySelector("[data-l-product-list-grid]").offsetTop,
                    behavior: 'smooth'
                });
                if (window.matchMedia('(max-width: 1200px)').matches) {
                    document.querySelector('.l-product-list-grid-col-filters').style.display = 'none';
                }
                this.filters.prepareData();
                initTooltips();
            }).catch(error => {
                this.productsContainerNode.classList.remove('loading');
                this.filters.el.classList.remove('loading');
                new LayoutToast(error.message, 'error');
            });
        };
        this.filters.init();
    }
}

class LayoutProductListGridFilters {
    constructor(el, data = {}) {
        this.onChange = () => {};
        this.onBeforeInit = () => {
            return new Promise((res, rej) => {
                res()
            })
        };
        this.inputsDataTypes = {};
        this.el = el;
        this.sortNodes = document.querySelectorAll('[data-l-product-list-sort-btn]');
        this.inputs = {};
        this.data = data;
    }

    init() {
        this.inputs = this.el.querySelectorAll('[data-l-product-list-grid-filters-input]');
        if (typeof this.el.dataset.lProductListGridFiltersAutoLoadFromUrl === 'undefined') {
            this.prepareData(); //Todo: sahdkjas
        }

        if (typeof this.el.dataset.lProductListGridFiltersAutoLoadFromUrl != 'undefined' && this.el.dataset.lProductListGridFiltersAutoLoadFromUrl) {
            this.data = this.getDataFromUrl();

        } else {
            this.handleInitialInputValues();
        }
        // console.log('before bind');
        if (typeof this.el.dataset.lProductListGridFiltersAutoLoadFromUrl != 'undefined' && this.el.dataset.lProductListGridFiltersAutoLoadFromUrl) {
            this.onBeforeInit().then(() => {
                this.bindEvents();
            });
        } else {
            this.bindEvents();
        }
    }

    destory() {
        this.removeEvents();
        this.inputs = this.el.querySelectorAll('[data-l-product-list-grid-filters-input]');
    }

    handleInitialInputValues() {
        this.inputs.forEach(input => {
            switch (input.type) {
                case 'text':
                    this.saveDataFromInput(input, 'text');
                    break;

                case 'checkbox':
                case 'radio':
                    this.saveDataFromInput(input, 'checkbox');
                    break;

                default:
                    this.saveDataFromInput(input);
                    break;
            }
        });
    }

    getDataFromUrl() {
        const queryParams = {};
        var url = window.location.href;
        if (url.includes("?")) {
            const paramsString = decodeURIComponent(url.split('?')[1]).split("&");
            paramsString.forEach(param => {
                const splitParam = param.split("=");
                const name = splitParam[0]
                const value = splitParam[1];
                if (String(name).indexOf('[]') > 0) {
                    if (!Array.isArray(queryParams[name])) {
                        queryParams[name] = [];
                    }
                    queryParams[name].push(value);
                } else {
                    queryParams[name] = value;
                }
            });
        }
        return queryParams;
    }

    prepareData() {
        this.inputs.forEach(input => {
            const name = input.dataset.lProductListGridFiltersInput;
            if (String(name).indexOf('[]') > 0) {
                this.inputsDataTypes[name] = 'array';
            } else {
                this.inputsDataTypes[name] = 'string';
            }
        });
    }

    bindEvents() {
        this.inputs.forEach(input => {
            switch (input.type) {
                case 'text':
                    input.addEventListener('input', this.textInputEvent.bind(this));
                    break;

                case 'checkbox':
                case 'radio':
                    input.addEventListener('change', this.checkboxInputEvent.bind(this));
                    break;

                default:
                    input.addEventListener('change', this.defaultInputEvent.bind(this));
                    break;
            }
        });
    }

    textInputEvent(e) {
        this.saveDataFromInput(e.target, 'text');
        return savedDebounce((e) => {
            const url = this.saveDataToQueryParams();
            this.onChange(url);
        }, [this, e], 1500);
    }

    checkboxInputEvent(e) {
        this.saveDataFromInput(e.target, 'checkbox');
        return savedDebounce((e) => {
            const url = this.saveDataToQueryParams();
            this.onChange(url);
        }, [this, e], 1500);
    }

    defaultInputEvent(e) {
        this.saveDataFromInput(e.target);
        return savedDebounce((e) => {
            const url = this.saveDataToQueryParams();
            this.onChange(url);
        }, [this, e], 1500);
    }

    removeEvents() {
        this.inputs.forEach(input => {
            switch (input.type) {
                case 'text':
                    input.removeEventListener('input', this.textInputEvent.bind(this));
                    break;

                case 'checkbox':
                    input.addEventListener('change', this.checkboxInputEvent.bind(this));
                    break;

                default:
                    input.addEventListener('change', this.defaultInputEvent.bind(this));
                    break;
            }
        });
    }

    saveDataFromInput(input, type = '') {
        const name = input.dataset.lProductListGridFiltersInput;
        if (input.value != input.dataset.lProductListGridFiltersInputDefault) {
            switch (type) {
                case 'text':
                    if (input.value) {
                        if (this.inputsDataTypes[name] === 'string') {
                            this.data[name] = input.value;
                        } else if (this.inputsDataTypes[name] === 'array') {
                            if (Array.isArray(this.data[name])) {
                                this.data[name].push(input.value);
                            } else {
                                this.data[name] = [input.value];
                            }
                        }
                    } else {
                        delete this.data[name];
                    }
                    break;

                case 'checkbox':
                    if (input.checked) {
                        if (this.inputsDataTypes[name] === 'string') {
                            this.data[name] = input.value;
                        } else if (this.inputsDataTypes[name] === 'array') {
                            if (Array.isArray(this.data[name])) {
                                this.data[name].push(input.value);
                            } else {
                                this.data[name] = [input.value];
                            }
                        }
                    } else {
                        if (this.inputsDataTypes[name] === 'string') {
                            delete this.data[name];
                        } else if (this.inputsDataTypes[name] === 'array') {
                            if (Array.isArray(this.data[name])) {
                                this.data[name] = this.data[name].filter(v => v != input.value);
                            } else {
                                delete this.data[name];
                            }
                        }
                    }
                    break;

                default:
                    if (this.inputsDataTypes[name] === 'string') {
                        this.data[name] = input.value;
                    } else if (this.inputsDataTypes[name] === 'array') {
                        if (Array.isArray(this.data[name])) {
                            this.data[name].push(input.value);
                        } else {
                            this.data[name] = [input.value];
                        }
                    }
                    break;
            }
        } else {
            delete this.data[name];
        }
    }

    saveDataToQueryParams() {
        const entries = Object.entries(this.data);
        const url = document.querySelector("[data-l-product-list-grid-url]").dataset.lProductListGridUrl;
        let paramsString = '';
        entries.forEach(entry => {
            const key = entry[0];
            const values = entry[1];
            if (!Array.isArray(values)) {
                paramsString += `${key}=${values}&`;
            } else {
                values.forEach(value => {
                    paramsString += `${key}=${value}&`;
                });
            }
        })
        if (paramsString[paramsString.length - 1] === '&') {
            paramsString = paramsString.slice(0, -1);
        }
        const queryParams = `${paramsString ? '?' + paramsString : ''}`;
        this.handelSortUrls(queryParams);

        const completeUrl = `${url}${queryParams}`;
        window.history.replaceState({}, '', completeUrl);
        return completeUrl;
    }

    handelSortUrls(queryParams) {
        this.sortNodes.forEach(item => {
            const href = item.dataset.lProductListSortBtnDefaultUrl.split('?')[0] + queryParams;
            item.setAttribute("href", href);
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
