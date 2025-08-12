import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import LayoutFilterAccordion from '../../_components/layout/accordions/l-filter-accordion';
import LayoutDropdown from '../../_components/layout/dropdowns/l-dropdown';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormRange from '../../_components/ui/forms/ui-form-range';
import {savedDebounce} from '../../_components/helpers/requests';
import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import {createNode} from '../../_components/helpers/dom';
import UiProductSlide from '../../_components/ui/slides/ui-product-slide';
import UiFormPassword from '../../_components/ui/forms/ui-form-password';

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


let signPassword = document.querySelector('[data-ui-form-password="sign-password"]');
if (signPassword) {
    signPassword = new UiFormPassword(signPassword);
    signPassword.bindEvents();
}