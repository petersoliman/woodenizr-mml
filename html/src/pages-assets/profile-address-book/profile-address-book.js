import '../../_components/main/footer/footer';
import '../../_components/layout/images/l-lazyload-img';
import '../../_components/full/form/form-validate';
import '../../_components/full/form/form-input';

window.addresses = [];

const mediaQuery = window.matchMedia("(max-width: 1200px)");
const sidebar = document.querySelector('[data-profile-sidebar]');
const card = document.querySelector('[data-profile-card]');
const onMediaQueryChange = (mediaQuery) => {
    if (mediaQuery.matches) {
        sidebar.style.display = 'none';
        card.style.display = 'block';
    } else {
        sidebar.style.display = 'block';
        card.style.display = 'block';
    }
}
onMediaQueryChange(mediaQuery);
mediaQuery.addListener(onMediaQueryChange);

document.querySelectorAll('[data-profile-sidebar-btn]').forEach(sidebarBtn => {
    sidebarBtn.addEventListener('click', (e) => {
        if (mediaQuery.matches && sidebarBtn.classList.contains('active')) {
            e.preventDefault();
            sidebar.style.display = 'none';
            card.style.display = 'block';
        }
    });
});

document.querySelectorAll('[data-profile-card-btn]').forEach(cardBtn => {
    cardBtn.addEventListener('click', () => {
        if (mediaQuery.matches) {
            sidebar.style.display = 'block';
            card.style.display = 'none';
        }
    });
});

import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import { createNode } from '../../_components/helpers/dom';
import LayoutModal from "../../_components/layout/modals/l-modal";

let addressAddModal = document.querySelector('[data-address-add-modal]');
let addressAddForm = document.querySelector('[data-address-add-form]');

window.addEventListener('DOMContentLoaded', (e) => {
    addressAddModal = new LayoutModal(addressAddModal);
    addressAddModal.bindEvents();    
    axios.get(APP.addressUrls.list)
        .then(function (response) {
        if (!response.data.error) {
            if (typeof response.data.addresses != 'undefined') {
                refreshAddresses(response.data.addresses);
            }
        } else {
            new LayoutToast(response.data.message, 'error');
        }
    }).catch(function (error) {
        new LayoutToast(error.message, 'error');
    });
    addressAddForm.__onValidateSuccess = () => {
        let url = APP.addressUrls.add;
        let bodyFormData = new FormData(addressAddForm);
        if (typeof window.editAddressId != 'undefined' && window.editAddressId) {
            bodyFormData.append('id', window.editAddressId);
            url = APP.addressUrls.edit;
        }
        axios.post(url, bodyFormData).then((response) => {
            if (!response.data.error) {
                addressAddForm.reset();
                new LayoutToast(response.data.message + '.', 'success');
                refreshAddresses(response.data.addresses);
                addressAddModal.hide();
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
        });
    };
});

document.querySelector('[data-add-address-btn]').addEventListener('click', () => {
    addressAddForm.reset();
    window.editAddressId = null;
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

function refreshAddresses(addresses) {
    window.addresses = addresses;
    const addressList = document.querySelector('[data-address-list]');
    const addressListTitle = document.querySelector('[data-address-list-title]');
    const defaultAddress = document.querySelector('[data-default-address]');
    const noAddressesWrapper = document.querySelector('[data-no-addresses-wrapper]');
    const defaultAddressWrapper = document.querySelector('[data-default-address-wrapper]');
    const addressesWrapper = document.querySelector('[data-addresses-wrapper]');
    let defaultAddresses = (addresses.filter(a => a.default));
    let otherAddresses = (addresses.filter(a => !a.default));
    if (addressList && typeof addressList.children != 'undefined') {
        [].slice.call(addressList.children).forEach(addressNode => {
            addressNode.remove();
        });
    }
    if (defaultAddress && typeof defaultAddress.children != 'undefined') {
        [].slice.call(defaultAddress.children).forEach(addressNode => {
            addressNode.remove();
        });
    }
    if (addresses.length) {
        noAddressesWrapper.style.display = 'none';
        addressesWrapper.style.display = 'block';
    } else {
        addressesWrapper.style.display = 'none';
        noAddressesWrapper.style.display = 'block';
    }
    if (defaultAddresses.length) {
        let defaultUserAddress = defaultAddresses[0];
        defaultAddressWrapper.style.display = 'block';
        const addressNode = createAddressNode(defaultUserAddress);
        const addressCard = addressNode.querySelector('[data-address-card]');
        addressCard.dataset.addressCard = defaultUserAddress.id;
        addAddressCardEventListeners(addressCard);
        defaultAddress.appendChild(addressCard);
    } else {
        defaultAddressWrapper.style.display = 'none';
    }
    if (otherAddresses.length) {
        addressListTitle.removeAttribute('style');
    } else {
        addressListTitle.style.display = 'none';
    }
    otherAddresses.forEach(address => {
        const addressNode = createAddressNode(address);
        const addressCard = addressNode.querySelector('[data-address-card]');
        addressCard.dataset.addressCard = address.id;
        const setAsDefaultBtn = addressCard.querySelector('[data-address-card-default-btn]');
        setAsDefaultBtn.style.display = 'inline-block';
        addAddressCardEventListeners(addressCard);
        setAsDefaultBtn.addEventListener('click', () => {
            const id = addressCard.dataset.addressCard;
            let bodyFormData = new FormData();
            bodyFormData.append('id', id);
            axios.post(APP.addressUrls.makeDefault, bodyFormData).then((response) => {
                if (!response.data.error) {
                    new LayoutToast(response.data.message + '.', 'success');
                    refreshAddresses(response.data.addresses);
                } else {
                    new LayoutToast(response.data.message, 'error');
                }
            }).catch((error) => {
                new LayoutToast(error.message, 'error');
            });
        });
        addressList.appendChild(addressNode);
    });
}

const addAddressCardEventListeners = (addressCard) => {
    const editBtn = addressCard.querySelector('[data-address-card-edit-btn]');
    const removeBtn = addressCard.querySelector('[data-address-card-remove-btn]');
    removeBtn.addEventListener('click', () => {
        const id = addressCard.dataset.addressCard;
        let bodyFormData = new FormData();
        bodyFormData.append('id', id);
        axios.post(APP.addressUrls.remove, bodyFormData).then((response) => {
            if (!response.data.error) {
                new LayoutToast(response.data.message + '.', 'success');
                refreshAddresses(response.data.addresses);
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
        });
    });
    editBtn.addEventListener('click', () => {
        const id = addressCard.dataset.addressCard;
        const address = window.addresses.filter(a => a.id == id)[0];
        const addressMappedFormEntries = {};
        addressAddForm.reset();
        Object.entries(address).forEach(i => {
            const name = i[0];
            const value = i[1];
            switch (name) {
                case 'title':
                    addressMappedFormEntries.title = value;
                    break;
                case 'mobileNumber':
                    addressMappedFormEntries.mobileNumber = value;
                    break;
                case 'address':
                    addressMappedFormEntries.address = value;
                    break;
                case 'zone':
                    addressMappedFormEntries.zoneId = value.id;
                    break;
            
                default:
                    break;
            }
        });
        addressAddForm.querySelectorAll('[data-stack-input-input]').forEach(input => {
            input.value = addressMappedFormEntries[input.name];
        });
        window.editAddressId = id;
    });
}