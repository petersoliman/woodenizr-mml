import axios from "axios";
import {isLocalStorageSupported} from "../../helpers/data";
import LayoutToast from "../../layout/toasts/l-toast";
import MainHeaderCartSidebarItem from "./m-header-cart-sidebar-item";

const emptyCart = {
    "noOfItems": 0,
    "paymentMethod": null,
    "coupon": null,
    "shippingAddress": null,
    "products": [],
    "subTotal": 0,
    "discount": 0,
    "shippingFee": 0,
    "extraFees": 0,
    "grandTotal": 0,
    "lastUpdateHash" : null,
};

export default class MainHeaderCartSidebar {
    constructor(el, cart = emptyCart) {
        this.el = el;
        this.cart = cart;
        this.localStorageSupported = isLocalStorageSupported();
        this.localStorageKey = 'MainHeaderCartSidebarCart';
    }

    fetch() {
        if (this.localStorageSupported && (this.fetchFromLocalStorage() == null)) {
            this.saveToLocalStorage();
        }

        const cartFromLocalStorage = this.localStorageSupported ? this.fetchFromLocalStorage() : null;
        const lastUpdateHashFromDom = APP && typeof APP.cartLastUpdateHash != 'undefined' ? APP.cartLastUpdateHash : null;
        const lastUpdateHashFromLocalStorage = cartFromLocalStorage && typeof cartFromLocalStorage.lastUpdateHash != 'undefined' ? cartFromLocalStorage.lastUpdateHash : null;

        if (lastUpdateHashFromDom != lastUpdateHashFromLocalStorage) {
            this.fetchFromApi().then(data => {
                this.cart = data;
                this.saveToLocalStorage();
                this.render();
            });
        } else if (this.localStorageSupported) {
            this.cart = cartFromLocalStorage;
            this.render();
        } else {
            this.fetchFromApi().then(data => {
                this.cart = data;
                this.render();
            });
        }
    }

    fetchFromLocalStorage() {
        const cartString = window.localStorage.getItem(this.localStorageKey);
        if (cartString) {
            return JSON.parse(cartString);
        }
        return null;
    }

    fetchFromApi() {
        return new Promise((res, rej) => {
            axios.get(APP.cartUrls.list).then(response => {
                if (!response.data.error) {
                    res(response.data);
                } else {
                    new LayoutToast(response.data.message, 'error');
                }
            }).catch(error => {
                rej(null);
                new LayoutToast(error.message, 'error');
            });
        });
    }

    saveToLocalStorage() {
        window.localStorage.setItem(this.localStorageKey, JSON.stringify(this.cart));
    }

    render() {
        const totalNode = document.querySelectorAll('[data-header-cart-sidebar-total]');
        const totalTextNode = document.querySelectorAll('[data-header-cart-sidebar-total-text]');
        const itemsNode = document.querySelectorAll('[data-header-cart-sidebar-items]');
        const itemsEmptyNode = document.querySelectorAll('[data-header-cart-sidebar-items-empty]');
        const footerTotalNode = document.querySelectorAll('[data-header-cart-sidebar-footer-total]');
        const footerTotalPriceNode = document.querySelectorAll('[data-header-cart-sidebar-footer-total-price]');
        const footerActionCheckoutNode = document.querySelectorAll('[data-header-cart-sidebar-footer-action-checkout]');

        if (this.cart.noOfItems > 0) {
            totalNode.forEach(node => node.removeAttribute('style'));
            totalTextNode.forEach(node => node.innerText = this.cart.noOfItems);
            itemsNode.forEach(node => node.removeAttribute('style'));
            itemsNode.forEach(node => {
                node.innerHTML = '';
                this.cart.products.forEach(cartItem => {
                    node.appendChild(new MainHeaderCartSidebarItem(cartItem));
                });
            });
            itemsEmptyNode.forEach(node => node.style.display = 'none');
            footerTotalNode.forEach(node => node.removeAttribute('style'));
            footerTotalPriceNode.forEach(node => node.innerText = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(this.cart.grandTotal) + ' ' + APP.currency);
            footerActionCheckoutNode.forEach(node => node.removeAttribute('style'));
            if(typeof window.APP_DOM.tooltips.refresh !=="undefined") {
                window.APP_DOM.tooltips.refresh();
            }
        } else {
            totalNode.forEach(node => node.style.display = 'none');
            itemsNode.forEach(node => node.style.display = 'none');
            itemsEmptyNode.forEach(node => node.style.display = 'block');
            footerTotalNode.forEach(node => node.style.display = 'none');
            footerActionCheckoutNode.forEach(node => node.style.display = 'none');
        }
    }

    add(productPriceId, qty = 1, btnEl = null) {
        let bodyFormData = new FormData();
        bodyFormData.append('productPriceId', productPriceId);
        bodyFormData.append('qty', qty);

        this.el.querySelectorAll(".m-header-cart-sidebar-items").forEach(node => node.classList.add("loading"));
        axios.post(APP.cartUrls.addItem, bodyFormData).then(response => {
            this.el.querySelectorAll(".m-header-cart-sidebar-items").forEach(node => node.classList.remove("loading"));
            if (!response.data.error) {
                this.cart = response.data.cart;
                this.saveToLocalStorage();
                this.render();
                this.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
                window.cart = response.data.cart;

                if (btnEl != null && typeof btnEl !== "undefined") {
                    btnEl.textContent = __("Added to Cart");
                    btnEl.classList.add("active")
                }
                document.dispatchEvent(new Event('cart-page-refresh'));
            } else {
                new LayoutToast(response.data.message, 'error');
                this.render();
                setTimeout(() => {
                    window.APP_DOM.modals.cartModal.hide();
                }, 5);
            }
        }).catch(error => {
            this.el.querySelectorAll(".m-header-cart-sidebar-items").forEach(node => node.classList.remove("loading"));
            new LayoutToast(error.message, 'error');
            setTimeout(() => {
                window.APP_DOM.modals.cartModal.hide();
            }, 5);
        });
    }

    remove(productPriceId) {
        let bodyFormData = new FormData();
        bodyFormData.append('productPriceId', productPriceId);

        axios.post(APP.cartUrls.removeItem, bodyFormData).then(response => {
            if (!response.data.error) {
                this.gtmEnhancedEcommerceRemoveFromCart(response.data.gtmProductsObjects);
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch(error => {
            new LayoutToast(error.message, 'error');
        });
    }

    gtmEnhancedEcommerceAddToCart(productObj, callbackOrRedirectUrl) {
        if (typeof (window.dataLayer) === "undefined") {
            return false;
        }

        var products = productObj;
        if (Array.isArray(productObj) == false) {
            products = [productObj];
        }

        var obj = {
            'event': 'addToCart',
            'ecommerce': {
                /** global: gtmEnhancedEcommerceCurrencyCode */
                'currencyCode': APP.gtmEnhancedEcommerceCurrencyCode || '',
                'add': {
                    'products': products
                }
            }
        };

        if (typeof callbackOrRedirectUrl !== 'undefined') {
            if (typeof callbackOrRedirectUrl === 'string') {
                obj.eventCallback = function () {
                    document.location = callbackOrRedirectUrl
                };
            } else if (typeof callbackOrRedirectUrl === 'function') {
                obj.eventCallback = callbackOrRedirectUrl;
            }
        }

        /** global: dataLayer */
        window.dataLayer.push(obj);
    }

    /**
     * @param {Object} productObj
     * @param {function|string} callbackOrRedirectUrl
     */
    gtmEnhancedEcommerceRemoveFromCart(productObj, callbackOrRedirectUrl) {
        if (typeof (window.dataLayer) === "undefined") {
            return false;
        }


        var products = productObj;
        if (Array.isArray(productObj) == false) {
            products = [productObj];
        }

        var obj = {
            'event': 'removeFromCart',
            'ecommerce': {
                /** global: gtmEnhancedEcommerceCurrencyCode */
                'currencyCode': APP.gtmEnhancedEcommerceCurrencyCode || '',
                'remove': {
                    'products': products
                }
            }
        };

        if (typeof callbackOrRedirectUrl !== 'undefined') {
            if (typeof callbackOrRedirectUrl === 'string') {
                obj.eventCallback = function () {
                    document.location = callbackOrRedirectUrl
                };
            } else if (typeof callbackOrRedirectUrl === 'function') {
                obj.eventCallback = callbackOrRedirectUrl;
            }
        }

        /** global: dataLayer */
        window.dataLayer.push(obj);
    }

}