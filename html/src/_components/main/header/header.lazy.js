import './_auto_complete'
import LayoutMegaDropdown from '../../../_components/layout/dropdowns/l-mega-dropdown';
import LayoutDropdown from '../../layout/dropdowns/l-dropdown';
import LayoutModal from '../../layout/modals/l-modal';
import LayoutPhoneOffsidebar from '../../layout/dropdowns/l-phone-offsidebar';
import {addLiveEventListener, closest} from '../../helpers/dom';
import UiFormPassword from '../../ui/forms/ui-form-password';
import MainHeaderCartSidebar from './m-header-cart-sidebar';
import axios from 'axios';
import LayoutToast from '../../layout/toasts/l-toast';

const sidebar = document.querySelector('[data-header-cart-sidebar]');
const cart = new MainHeaderCartSidebar(sidebar);
window.APP_DATA.cart = cart;
window.APP_DOM.modals.cartModal = {
    show: () => {
        sidebar.classList.add('show');
        setTimeout(() => {
            ['header', 'body', 'footer'].forEach(backdropName => {
                document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.add('show');
            });
        }, 10);
    },
    hide: () => {
        sidebar.classList.remove('show');
        ['header', 'body', 'footer'].forEach(backdropName => {
            document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.remove('show');
        });
    }
}
cart.fetch();

let deliveryDropdown = document.querySelector('[data-header-delivery-dropdown="true"]');
if (deliveryDropdown) {
    deliveryDropdown = new LayoutMegaDropdown(deliveryDropdown)
    deliveryDropdown.bindEvents();
}

window.addEventListener('DOMContentLoaded', (e) => {
    let accountModal = document.querySelector('[data-header-account-modal="true"]');
    if (accountModal) {
        accountModal = new LayoutModal(accountModal)
        accountModal.bindEvents();
        window.APP_DOM.modals.accountModal = accountModal;
    }
    let cartDeleteModal = document.querySelector('[data-header-cart-delete-modal="true"]');
    if (cartDeleteModal) {
        const saveAction = cartDeleteModal.querySelector('[data-header-cart-delete-modal-action-save]');
        saveAction.addEventListener('click', e => {
            if (window.APP.isLoggedIn) {
                const productPriceId = saveAction.dataset.headerCartDeleteModalActionSave;

                let bodyFormData = new FormData();
                bodyFormData.append('productPriceId', productPriceId);

                axios.post(APP.cartUrls.removeFromCartAndAddToWishlist, bodyFormData).then(response => {
                    if (!response.data.error) {
                        window.APP_DATA.cart.cart = response.data.cart;
                        window.APP_DATA.cart.saveToLocalStorage();
                        window.APP_DATA.cart.render();
                        window.APP_DOM.modals.cartModal.show();
                        window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
                    } else {
                        new LayoutToast(response.data.message, 'error');
                    }
                }).catch(error => {
                    new LayoutToast(error.message, 'error');
                });
            } else {
                new LayoutToast(__('Please login first') + '.', 'error');
                setTimeout(() => {
                    window.APP_DOM.modals.accountModal.show();
                }, 50);
            }
        });
        const removeAction = cartDeleteModal.querySelector('[data-header-cart-delete-modal-action-remove]');
        removeAction.addEventListener('click', e => {
            const productPriceId = removeAction.dataset.headerCartDeleteModalActionRemove;
            let bodyFormData = new FormData();
            bodyFormData.append('productPriceId', productPriceId);

            axios.post(APP.cartUrls.removeItem, bodyFormData).then(response => {
                if (!response.data.error) {
                    window.APP_DATA.cart.cart = response.data.cart;
                    window.APP_DATA.cart.saveToLocalStorage();
                    window.APP_DATA.cart.render();
                    window.APP_DOM.modals.cartModal.show();
                    window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
                } else {
                    new LayoutToast(response.data.message, 'error');
                }
            }).catch(error => {
                new LayoutToast(error.message, 'error');
            });
        });
        cartDeleteModal = new LayoutModal(cartDeleteModal)
        cartDeleteModal.bindEvents();
        cartDeleteModal.modal.addEventListener('click', (e) => {
            if (cartDeleteModal.modal.classList.contains('show-modal') && (e.target == cartDeleteModal.modal || closest(e.target, '[data-l-modal-btn]'))) {
                setTimeout(() => {
                    window.APP_DOM.modals.cartModal.show();
                }, 50);
            }
        });
    }

    let accountDropdown = document.querySelector('[data-header-account-dropdown="true"]');
    if (accountDropdown) {
        accountDropdown = new LayoutMegaDropdown(accountDropdown)
        accountDropdown.bindEvents();
    }

    let notificationsDropdown = document.querySelector('[data-header-notifications-dropdown="true"]');
    if (notificationsDropdown) {
        notificationsDropdown = new LayoutMegaDropdown(notificationsDropdown)
        notificationsDropdown.bindEvents();
    }

    let loginPassword = document.querySelector('[data-ui-form-password="login-password"]');
    if (loginPassword) {
        loginPassword = new UiFormPassword(loginPassword);
        loginPassword.bindEvents();
    }

    let loginInputs = document.querySelectorAll('[data-m-header-login-input]');
    if (loginInputs.length) {
        const btn = document.querySelector('[data-m-header-login-btn]');
        loginInputs.forEach(input => {
            input.addEventListener('input', e => {
                const values = [];
                loginInputs.forEach(input => {
                    if (input.value) {
                        values.push(input.value);
                    }
                });
                if (values.length > 1) {
                    btn.removeAttribute('disabled');
                } else {
                    btn.disabled = 'disabled';
                }
            });
        })
    }
});


addLiveEventListener('click', '[data-header-cart-sidebar-btn]', e => {
    const sidebar = document.querySelector('[data-header-cart-sidebar]');
    if (sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
        ['header', 'body', 'footer'].forEach(backdropName => {
            document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.remove('show');
        });
    } else {
        sidebar.classList.add('show');
        setTimeout(() => {
            ['header', 'body', 'footer'].forEach(backdropName => {
                document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.add('show');
            });
        }, 10);
    }
});

window.addEventListener('click', (e) => {
    let cartSidebarIsShown = false;
    document.querySelectorAll('[data-header-cart-sidebar]').forEach(sidebar => {
        if (sidebar.classList.contains('show')) {
            cartSidebarIsShown = true;
        }
    });
    if (cartSidebarIsShown) {
        const btn = e.target.dataset.headerCartSidebarBtn ? e.target : closest(e.target, '[data-header-cart-sidebar-btn]');
        const modal = e.target.dataset.headerCartSidebar ? e.target : closest(e.target, '[data-header-cart-sidebar]');
        if (!btn && !modal) {
            const sidebar = document.querySelector('[data-header-cart-sidebar]');
            sidebar.classList.remove('show');
            ['header', 'body', 'footer'].forEach(backdropName => {
                document.querySelector(`[data-m-${backdropName}-backdrop]`).classList.remove('show');
            });
        }
    }
});

addLiveEventListener('click', '[data-header-cart-sidebar-add-product-btn]', e => {
    const id = e.target.dataset.headerCartSidebarAddProductBtn;
    const qty = typeof e.target.dataset.headerCartSidebarAddProductBtnQty != 'undefined' ? e.target.dataset.headerCartSidebarAddProductBtnQty : 1;
    cart.add(id, qty, e.target);
});

let navbarMegaMenu = null;
if (window.matchMedia('(min-width: 1200px)').matches) {
    let headerMegaDropdownElement = document.querySelector('[data-header-mega-dropdown="true"]');
    if (headerMegaDropdownElement) {
        navbarMegaMenu = new LayoutMegaDropdown(headerMegaDropdownElement);
        navbarMegaMenu.bindEvents();
    }
} else {
    new LayoutPhoneOffsidebar(document.querySelector('[data-header-phone-offsidebar-btn="true"]'), document.querySelector('[data-header-phone-offsidebar="true"]')).bindEvents();
}

if (document.querySelector('[data-header-topbar-delivery-country-dropdown="true"]')) {
    new LayoutDropdown(document.querySelector('[data-header-topbar-delivery-country-dropdown="true"]')).bindEvents();

}
if (document.querySelector('[data-header-topbar-delivery-language-dropdown="true"]')) {
    new LayoutDropdown(document.querySelector('[data-header-topbar-delivery-language-dropdown="true"]')).bindEvents();
}
