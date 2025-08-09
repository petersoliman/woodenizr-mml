import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import LayoutFilterAccordion from '../../_components/layout/accordions/l-filter-accordion';
import LayoutDropdown from '../../_components/layout/dropdowns/l-dropdown';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormRange from '../../_components/ui/forms/ui-form-range';
import '../../_components/full/cart/cart';

const cartSubmitBtn = document.querySelector('[data-cart-submit-btn]');
const termsAndConditionCheckbox = document.querySelector('[data-agree-terms-condition]');
const outOfStockError = document.querySelector('[data-cart-out-of-stock-error]');
const maxStockError = document.querySelector('[data-cart-max-stock-error]');
let cartHasErrors = false;

let filtersAccordions = [];
document.querySelectorAll('[data-l-filter-accordion]').forEach(el => {
    const accordion = new LayoutFilterAccordion(el);
    accordion.bindEvents();
    filtersAccordions.push(accordion);
});
if (termsAndConditionCheckbox) {

    termsAndConditionCheckbox.addEventListener("click", (e) => {
        checkTermsAndConditionCheckbox();
    });

    document.addEventListener("cart-loaded", () => {
        checkTermsAndConditionCheckbox();
    });
}
let filterFormRange = {};
if (document.querySelector('[data-ui-form-range="price"]')) {
    filterFormRange = new UiFormRange(document.querySelector('[data-ui-form-range="price"]'));
    filterFormRange.init().bindEvents()
}
if (document.querySelector('[data-product-list-grid-sort-dropdown="true"]')) {
    new LayoutDropdown(document.querySelector('[data-product-list-grid-sort-dropdown="true"]')).bindEvents();
}

function checkTermsAndConditionCheckbox() {
    if (outOfStockError && !outOfStockError.hidden) {
        cartHasErrors = true;
    }
    if (maxStockError && !maxStockError.hidden) {
        cartHasErrors = true;
    }
    if (document.querySelector('[data-agree-terms-condition]').checked && !cartHasErrors) {
        cartSubmitBtn.removeAttribute('disabled');
    } else {
        cartSubmitBtn.setAttribute('disabled', "disabled");
    }
}

initTooltips();