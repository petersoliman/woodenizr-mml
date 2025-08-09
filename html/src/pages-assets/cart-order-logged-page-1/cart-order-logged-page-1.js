import '../../_components/main/footer/footer';
import '../../_components/layout/images/l-lazyload-img';

import '../../_components/full/form/stack-input';
import '../../_components/full/form/form-validate';
import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import { createNode } from '../../_components/helpers/dom';
import LayoutModal from "../../_components/layout/modals/l-modal";
import '../../_components/full/cart/cart';

let addressListModal = document.querySelector('[data-address-list-modal]');
let addressAddModal = document.querySelector('[data-address-add-modal]');
let addressAddForm = document.querySelector('[data-address-add-form]');
let defaultAddressInput = document.querySelector('[data-default-address-input]');
let cartSubmitBtn = document.querySelector('[data-cart-submit-btn]');
window.addresses = [];

window.addEventListener('DOMContentLoaded', (e) => {
    addressListModal = new LayoutModal(addressListModal);
    addressListModal.bindEvents();    
    addressAddModal = new LayoutModal(addressAddModal);
    addressAddModal.bindEvents();    
    addressAddForm.__onValidateSuccess = () => {
        let bodyFormData = new FormData(addressAddForm);
        axios.post(APP.addressUrls.add, bodyFormData).then((response) => {
            if (!response.data.error) {
                addressAddForm.reset();
                addressAddForm.querySelectorAll('[data-stack-input]').forEach(stackInput => stackInput.classList.remove('dirty', 'has-value'));
                new LayoutToast(response.data.message + '.', 'success');
                window.addresses = response.data.addresses;
                refreshAddresses();
                addressAddModal.hide();
                addressListModal.show();
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
        });
    };
    axios.get(APP.addressUrls.list)
        .then(function (response) {
        if (!response.data.error) {
            if (typeof response.data.addresses != 'undefined') {
                window.addresses = response.data.addresses;
                refreshAddresses();
            }
        } else {
            new LayoutToast(response.data.message, 'error');
        }
    }).catch(function (error) {
        new LayoutToast(error.message, 'error');
    });
});


function createAddressNode(address) {
    const template = document.querySelector('[data-address-template]').innerHTML;
    let addressHTML = template;
    let addressNode = {};
    addressHTML = addressHTML.replaceAll('__name__', address.title);
    addressHTML = addressHTML.replaceAll('__address__', address.address);
    addressHTML = addressHTML.replaceAll('__mobile__', address.mobileNumber);
    addressNode = createNode(addressHTML);
    return addressNode;
}

function refreshAddresses() {
    const addresses = window.addresses;
    const addressList = document.querySelector('[data-address-list]');
    const defaultAddress = document.querySelector('[data-default-address]');
    const noAddressesWrapper = document.querySelector('[data-no-addresses-wrapper]');
    const defaultAddressWrapper = document.querySelector('[data-default-address-wrapper]');
    let defaultAddresses = (addresses.filter(a => a.default));
    const outOfStockError = document.querySelector('[data-cart-out-of-stock-error]');
    const maxStockError = document.querySelector('[data-cart-max-stock-error]');
    let cartHasErrors = false;
    if (outOfStockError && !outOfStockError.hidden) {
        cartHasErrors = true;
    }
    if (maxStockError && !maxStockError.hidden) {
        cartHasErrors = true;
    }
    [].slice.call(addressList.children).forEach(addressNode => {
        addressNode.remove();
    });
    [].slice.call(defaultAddress.children).forEach(addressNode => {
        addressNode.remove();
    });
    if (addresses.length) {
        noAddressesWrapper.style.display = 'none';
        defaultAddressWrapper.style.display = 'block';
    } else {
        defaultAddressWrapper.style.display = 'none';
        noAddressesWrapper.style.display = 'block';
    }
    if (defaultAddresses.length) {
        let defaultUserAddress = defaultAddresses[0];
        defaultAddress.appendChild(createAddressNode(defaultUserAddress));
        defaultAddressInput.value = defaultUserAddress.id;
        if (!cartHasErrors) {
            cartSubmitBtn.removeAttribute('disabled');
        }
    } else {
        cartSubmitBtn.setAttribute('disabled', 'disabled');
    }
    addresses.forEach(address => {
        const addressNode = createAddressNode(address);
        const addressCard = addressNode.querySelector('[data-address-card]');
        addressCard.dataset.addressCard = address.id;
        addressCard.classList.add('clickable');
        addressCard.addEventListener('click', () => {
            const addressCards = document.querySelectorAll('[data-address-list-modal] [data-address-list] [data-address-card]');
            const currentAddressCard = document.querySelector('[data-address-list-modal] [data-address-list] [data-address-card="' + address.id + '"]');
            addressCards.forEach(addressCard => {
                addressCard.classList.remove('active');
                delete(addressCard.dataset.addressCardActive);
            });
            currentAddressCard.classList.add('active');
            addressCard.dataset.addressCardActive = 'true';
        });
        if (address.default) {
            addressCard.classList.add('active');
            addressCard.dataset.addressCardActive = 'true';
        }
        addressList.appendChild(addressNode);
    });
}

document.querySelector('[data-address-list-save-btn]').addEventListener('click', () => {
    const addressCards = document.querySelectorAll('[data-address-list-modal] [data-address-list] [data-address-card]');
    addressCards.forEach(addressCard => {
        if (addressCard.dataset.addressCardActive) {
            const id = addressCard.dataset.addressCard;
            window.addresses = window.addresses.map(a => {
                if (a.id != id) {
                    a.default = false;
                } else if (a.id == id) {
                    a.default = true;
                }
                return a;
            });
            refreshAddresses();
        }
    });
});

document.querySelector('[data-address-add-btn]').addEventListener('click', () => {
    addressListModal.hide();
});
