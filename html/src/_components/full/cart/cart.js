import axios from 'axios';
import validator from 'validator';
import LayoutToast from '../../../_components/layout/toasts/l-toast';
import {closest, createNode} from '../../../_components/helpers/dom';
import {debounce} from '../../../_components/helpers/requests';
import LayoutModal from '../../layout/modals/l-modal';

window.cart = {};

const cartItemsTotalNode = document.querySelectorAll('[data-cart-items-count]');
const cartCouponWrapperNode = document.querySelectorAll('[data-cart-coupon-wrapper]');
const cartCouponInputNode = document.querySelectorAll('[data-cart-coupon-input]');
const cartCouponBtnNode = document.querySelectorAll('[data-cart-coupon-btn]');
const cartCouponMsgNode = document.querySelectorAll('[data-cart-coupon-msg]');
const cartCouponErrorsNode = document.querySelectorAll('[data-validate-input-errors="coupon"]');
const cartCouponSuccessNode = document.querySelectorAll('[data-validate-input-success="coupon"]');
const cartSubmitBtnNode = document.querySelectorAll('[data-cart-submit-btn]');
const cartOutOfStockErrorNode = document.querySelectorAll('[data-cart-out-of-stock-error]');
const cartMaxStockErrorNode = document.querySelectorAll('[data-cart-max-stock-error]');
const cartItemsListNode = document.querySelectorAll('[data-cart-items-list]');
const cartItemsTemplateNode = document.querySelectorAll('[data-cart-items-template]');
const cartWrapper = document.querySelector('[data-cart-wrapper]');
const cartEmptyWrapper = document.querySelector('[data-cart-empty-wrapper]');
const cartSummaryNodes = {
    subTotal: {
        wrapper: document.querySelectorAll('[data-cart-summary-subtotal]'),
        value: document.querySelectorAll('[data-cart-summary-subtotal-value]'),
        currency: document.querySelectorAll('[data-cart-summary-subtotal-currency]'),
    },
    discount: {
        wrapper: document.querySelectorAll('[data-cart-summary-discount]'),
        value: document.querySelectorAll('[data-cart-summary-discount-value]'),
        currency: document.querySelectorAll('[data-cart-summary-discount-currency]'),
    },
    shippingFee: {
        wrapper: document.querySelectorAll('[data-cart-summary-shipping-fee]'),
        value: document.querySelectorAll('[data-cart-summary-shipping-fee-value]'),
        currency: document.querySelectorAll('[data-cart-summary-shipping-fee-currency]'),
    },
    extraFees: {
        wrapper: document.querySelectorAll('[data-cart-summary-extra-fees]'),
        value: document.querySelectorAll('[data-cart-summary-extra-fees-value]'),
        currency: document.querySelectorAll('[data-cart-summary-extra-fees-currency]'),
    },
    grandTotal: {
        wrapper: document.querySelectorAll('[data-cart-summary-grand-total]'),
        value: document.querySelectorAll('[data-cart-summary-grand-total-value]'),
        currency: document.querySelectorAll('[data-cart-summary-grand-total-currency]'),
    },
};
const cartSummaryKeys = ['subTotal', 'discount', 'shippingFee', 'extraFees', 'grandTotal'];

let cartWishlistModal = document.querySelector('[data-cart-wishlist-modal]');
if (cartWishlistModal) {
    cartWishlistModal = new LayoutModal(cartWishlistModal);
    cartWishlistModal.bindEvents();
}

let cartRemoveModal = document.querySelector('[data-cart-remove-modal]');
if (cartRemoveModal) {
    cartRemoveModal = new LayoutModal(cartRemoveModal);
    cartRemoveModal.bindEvents();
}

const fetchFromApi = () => {
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

const cartLoadedEvent = new Event('cart-loaded');

fetchFromApi().then(data => {
    window.cart = data;
    refreshCart();
    document.dispatchEvent(cartLoadedEvent);
});

/*if (cartRemoveModal) {
    const undoBtn = cartRemoveModal.modal.querySelector('[data-cart-undo-remove]');
    undoBtn.addEventListener('click', e => {
        refreshCart();
    });
}*/

if (cartRemoveModal) {
    const undoBtn = cartRemoveModal.modal.querySelector('[data-cart-undo-remove]');
    document.addEventListener("cart-page-refresh", () => {
        refreshCart();
        undoBtn.textContent = __("Undo");
    });
}

const refreshCart = () => {
    const cart = window.cart;
    cartItemsListNode.forEach(list => {
        list.innerHTML = '';
    });
    // Check if One Of Products is Out Of Stock 
    let oneOfProductsOutOfStock = false;
    let oneOfProductsOutOfStockError = '';
    cart.products.forEach(p => {
        if (p.product.price.stock < 1 || p.qty > p.product.price.stock) {
            oneOfProductsOutOfStock = true;
        }
        if (p.product.price.stock < 1) {
            oneOfProductsOutOfStockError = 'zero';
        } else if (p.qty > p.product.price.stock) {
            oneOfProductsOutOfStockError = 'max';
        }
    });
    if (oneOfProductsOutOfStock) {
        cartSubmitBtnNode.forEach(c => c.setAttribute('disabled', 'disabled'));
        if (oneOfProductsOutOfStockError == 'zero') {
            cartOutOfStockErrorNode.forEach(c => c.removeAttribute('hidden'));
        } else if (oneOfProductsOutOfStockError == 'max') {
            cartMaxStockErrorNode.forEach(c => c.removeAttribute('hidden'));
        }
    } else {
        cartSubmitBtnNode.forEach(c => c.removeAttribute('disabled'));
        cartOutOfStockErrorNode.forEach(c => c.setAttribute('hidden', 'hidden'));
        cartMaxStockErrorNode.forEach(c => c.setAttribute('hidden', 'hidden'));
    }
    // Change Coupon (DOM)
    if (cart.coupon != null) {
        cartCouponInputNode.forEach(c => {
            c.setAttribute('disabled', 'disabled')
            c.value = cart.coupon.code
        });
        cartCouponMsgNode.forEach(c => {
            c.innerHTML = cart.coupon.description
        });
        cartCouponBtnNode.forEach(c => c.classList.add('active'));
    } else {
        cartCouponInputNode.forEach(c => c.removeAttribute('disabled'));
        cartCouponBtnNode.forEach(c => c.classList.remove('active'));
    }
    // Change Total Items Count (DOM)
    if (typeof cart.noOfItems != 'undefined') {
        cartItemsTotalNode.forEach(c => c.innerHTML = cart.noOfItems);
        if (cart.noOfItems == 0) {
            cartCouponWrapperNode.forEach(c => c.classList.remove('has-error'));
            cartCouponBtnNode.forEach(c => c.setAttribute('disabled', 'disabled'));
            cartCouponInputNode.forEach(c => c.setAttribute('disabled', 'disabled'));
            cartCouponErrorsNode.forEach(c => c.innerHTML = '');
        }
    }
    // Change Summary Totals (DOM)
    cartSummaryKeys.forEach(key => {
        cartSummaryNodes[key]['value'].forEach(c => c.innerHTML = key == 'discount' ? '-' + new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(cart[key]) : new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(cart[key]));
        cartSummaryNodes[key]['currency'].forEach(c => c.innerHTML = APP.currency);
        if (cart[key]) {
            cartSummaryNodes[key]['wrapper'].forEach(c => c.removeAttribute('hidden'));
        } else {
            cartSummaryNodes[key]['wrapper'].forEach(c => c.setAttribute('hidden', 'hidden'));
        }
        if (validator.isNumeric(String(cart[key]))) {
            cartSummaryNodes[key]['currency'].forEach(c => c.removeAttribute('hidden'));
        } else {
            cartSummaryNodes[key]['currency'].forEach(c => c.setAttribute('hidden', 'hidden'));
        }
    });
    // Change Items (DOM)
    if (cartItemsListNode.length) {
        cart.products.forEach(productObject => {
            const product = productObject.product;
            let htmlTemplate = '';
            cartItemsTemplateNode.forEach(t => {
                htmlTemplate += t.innerHTML;
            });
            const productNode = createNode(htmlTemplate);
            if (product.price.stock == 0) {
                productNode.querySelector('[data-cart-item-out-of-stock-wish-list]') ? productNode.querySelector('[data-cart-item-out-of-stock-wish-list]').removeAttribute('style') : null;
                productNode.querySelector('[data-cart-item-out-of-stock-message]') ? productNode.querySelector('[data-cart-item-out-of-stock-message]').removeAttribute('style') : null;
                productNode.querySelector('[data-cart-item-message]') ? productNode.querySelector('[data-cart-item-message]').removeAttribute('style') : null;
            } else if (productObject.qty > product.price.stock) {
                productNode.querySelector('[data-cart-item-max-stock-change-qty]') ? productNode.querySelector('[data-cart-item-max-stock-change-qty]').removeAttribute('style') : null;
                productNode.querySelector('[data-cart-item-max-stock-message]') ? productNode.querySelector('[data-cart-item-max-stock-message]').removeAttribute('style') : null;
                productNode.querySelector('[data-cart-item-message]') ? productNode.querySelector('[data-cart-item-message]').removeAttribute('style') : null;
            }
            productNode.querySelectorAll('[data-cart-item-remove]').forEach(btn => {
                btn.dataset.cartItemRemove = product.price.id;
                btn.addEventListener('click', () => {
                    const qty = (closest(btn, '[data-cart-item]')).querySelector('[data-cart-item-qty]').value;
                    removeFromCart(product.price.id, qty);
                });
            });
            productNode.querySelectorAll('[data-cart-item-wish-list]').forEach(btn => {
                if (window.APP.isLoggedIn) {
                    btn.dataset.cartWishListRemove = product.price.id;
                    btn.addEventListener('click', () => {
                        removeFromCartAndAddWishList(product.price.id);
                    });
                } else {
                    btn.style.display = 'none';
                }
            });
            productNode.querySelectorAll('[data-cart-item-qty-stock]').forEach(span => {
                span.innerHTML = product.price.stock;
            });
            productNode.querySelectorAll('[data-cart-item-qty-text]').forEach(span => {
                span.innerHTML = productObject.qty;
            });
            productNode.querySelectorAll('[data-cart-item-name]').forEach(span => {
                span.innerHTML = product.title;
            });
            productNode.querySelectorAll('[data-cart-item-url]').forEach(span => {
                span.setAttribute('href', product.absoluteUrl);
            });
            productNode.querySelectorAll('[data-cart-item-img]').forEach(span => {
                span.setAttribute('src', product.mainImage);
            });
            productNode.querySelectorAll('[data-cart-item-price]').forEach(span => {
                span.innerHTML = new Intl.NumberFormat('en-US', {
                    maximumFractionDigits: 0,
                    minimumFractionDigits: 0
                }).format(product.price.salePrice);
            });
            productNode.querySelectorAll('[data-cart-item-currency]').forEach(span => {
                span.innerHTML = APP.currency;
            });
            productNode.querySelectorAll('[data-cart-item-variants]').forEach(varientsNode => {
                if (product.variants && Array.isArray(product.variants) && product.variants.length) {
                    product.variants.forEach(v => {
                        varientsNode.appendChild(createNode(`<p>${v}</p>`));
                    });
                }
            });
            const qtyInput = productNode.querySelector('[data-cart-item-qty]');
            if (qtyInput) {
                qtyInput.value = productObject.qty;
                qtyInput.addEventListener('input', debounce(e => {
                    if (qtyInput.value > 0) {
                        updateQty(product.price.id, qtyInput.value);
                    } else {
                        qtyInput.value = Number(1);
                    }
                }, 500));
            }
            productNode.querySelectorAll('[data-cart-item-qty-minus]').forEach(btn => {
                btn.dataset.cartItemQtyMinus = product.price.id;
                if (productObject.qty > 1) {
                    btn.classList.remove('disabled');
                }
                btn.addEventListener('click', e => {
                    if (!btn.classList.contains('disabled') && productObject.qty > 0) {
                        qtyInput.value = Number(qtyInput.value) - 1;
                    }
                });
                btn.addEventListener('click', debounce(e => {
                    if (!btn.classList.contains('disabled') && productObject.qty > 0) {
                        updateQty(product.price.id, productObject.qty - 1);
                    }
                }, 200));
            });
            productNode.querySelectorAll('[data-cart-item-qty-plus]').forEach(btn => {
                btn.dataset.cartItemQtyPlus = product.price.id;
                if (productObject.qty < product.price.stock) {
                    btn.classList.remove('disabled');
                }
                btn.addEventListener('click', () => {
                    if (!btn.classList.contains('disabled')) {
                        qtyInput.value = Number(qtyInput.value) + 1;
                    }
                });
                btn.addEventListener('click', debounce(e => {
                    if (!btn.classList.contains('disabled')) {
                        updateQty(product.price.id, Number(productObject.qty) + 1);
                    }
                }, 200));
            });
            productNode.querySelectorAll('[data-cart-item-max-stock-change-qty]').forEach(btn => {
                btn.dataset.cartItemMaxStockChangeQty = product.price.id;
                btn.addEventListener('click', () => {
                    qtyInput.value = product.price.stock;
                });
                btn.addEventListener('click', debounce(e => {
                    updateQty(product.price.id, qtyInput.value);
                }, 200));
            });

            cartItemsListNode.forEach(list => {
                list.appendChild(productNode);
            });
        });
    }
    // Empty Cart
    if (cartWrapper || cartEmptyWrapper) {
        if (cart.products && Array.isArray(cart.products) && cart.products.length) {
            cartWrapper.style.display = 'block';
            cartEmptyWrapper.style.display = 'none';
        } else {
            cartEmptyWrapper.style.display = 'block';
            cartWrapper.style.display = 'none';
        }
    }
}

const validateCoupon = (valid = false, message = '') => {
    if (valid) {
        cartCouponErrorsNode.forEach(c => {
            c.setAttribute('hidden', 'hidden');
            c.innerHTML = '';
        });
        cartCouponSuccessNode.forEach(c => {
            c.removeAttribute('hidden');
            c.innerHTML = message;
        });
        cartCouponWrapperNode.forEach(c => c.classList.add('has-success'));
    } else {
        cartCouponErrorsNode.forEach(c => {
            c.removeAttribute('hidden');
            c.innerHTML = message;
        });
        cartCouponSuccessNode.forEach(c => {
            c.setAttribute('hidden', 'hidden');
            c.innerHTML = '';
        });
        cartCouponWrapperNode.forEach(c => c.classList.add('has-error'));
    }
};

const updateQty = (productPriceId, qty) => {
    let bodyFormData = new FormData();
    bodyFormData.append('productPriceId', productPriceId);
    bodyFormData.append('qty', qty);

    axios.post(APP.cartUrls.updateQty, bodyFormData).then(response => {
        if (!response.data.error) {
            window.APP_DATA.cart.cart = response.data.cart;
            window.APP_DATA.cart.saveToLocalStorage();
            window.APP_DATA.cart.render();
            window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
            window.cart = response.data.cart;
        } else {
            new LayoutToast(response.data.message, 'error');
            window.APP_DATA.cart.render();
        }
        refreshCart();
    }).catch(error => {
        new LayoutToast(error.message, 'error');
    });
};

const removeFromCart = (productPriceId, qty = 1) => {
    let bodyFormData = new FormData();
    bodyFormData.append('productPriceId', productPriceId);

    axios.post(APP.cartUrls.removeItem, bodyFormData).then(response => {
        if (!response.data.error) {
            window.APP_DATA.cart.cart = response.data.cart;
            window.APP_DATA.cart.saveToLocalStorage();
            window.APP_DATA.cart.render();
            window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
            // new LayoutToast(__('Success'), 'success');
            window.cart = response.data.cart;
            if (cartRemoveModal) {
                cartRemoveModal.show();
                const undoBtn = cartRemoveModal.modal.querySelector('[data-cart-undo-remove]');
                if (undoBtn) {
                    undoBtn.dataset.headerCartSidebarAddProductBtn = productPriceId;
                    undoBtn.dataset.headerCartSidebarAddProductBtnQty = qty;
                }
            }
        } else {
            new LayoutToast(response.data.message, 'error');
        }
        refreshCart();
    }).catch(error => {
        new LayoutToast(error.message, 'error');
    });
};

const removeFromCartAndAddWishList = (productPriceId) => {
    if (window.APP.isLoggedIn) {

        let bodyFormData = new FormData();
        bodyFormData.append('productPriceId', productPriceId);

        axios.post(APP.cartUrls.removeFromCartAndAddToWishlist, bodyFormData).then(response => {
            if (!response.data.error) {
                window.APP_DATA.cart.cart = response.data.cart;
                window.APP_DATA.cart.saveToLocalStorage();
                window.APP_DATA.cart.render();
                window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
                window.cart = response.data.cart;
                // new LayoutToast(__('Success'), 'success');
                if (cartWishlistModal) {
                    cartWishlistModal.show();
                }
            } else {
                new LayoutToast(response.data.message, 'error');
            }
            refreshCart();
        }).catch(error => {
            new LayoutToast(error.message, 'error');
        });
    } else {
        new LayoutToast(__('Please login first') + '.', 'error');
        setTimeout(() => {
            window.APP_DOM.modals.accountModal.show();
        }, 50);
    }
}

// Coupon Button Event Listener
cartCouponBtnNode.forEach(btn => btn.addEventListener('click', () => {
    const code = cartCouponInputNode[0].value;
    if (code) {
        if (!btn.classList.contains('active')) {
            let bodyFormData = new FormData();
            bodyFormData.append('code', code);
            axios.post(APP.cartUrls.addCouponCode, bodyFormData).then(response => {
                refreshCart();
                if (response.data.error) {
                    new LayoutToast(response.data.message, 'error');
                } else {
                    window.cart = response.data.cart;
                    if (response.data.cart.coupon != null) {
                        validateCoupon(true, response.data.message);
                    } else {
                        validateCoupon(false, response.data.message);
                    }
                }
            }).catch(error => {
                new LayoutToast(error.message, 'error');
            });
        } else {
            axios.post(APP.cartUrls.removeCouponCode).then(response => {
                if (response.data.error) {
                    new LayoutToast(response.data.message, 'error');
                } else {
                    window.cart = response.data.cart;
                    refreshCart();
                    cartCouponMsgNode.forEach(c => {
                        c.innerHTML = '';
                    });
                    cartCouponErrorsNode.forEach(c => {
                        c.setAttribute('hidden', 'hidden');
                        c.innerHTML = '';
                    });
                    cartCouponSuccessNode.forEach(c => {
                        c.setAttribute('hidden', 'hidden');
                        c.innerHTML = '';
                    });
                    cartCouponWrapperNode.forEach(c => {
                        c.classList.remove('has-error');
                        c.classList.remove('has-success');
                    });
                }
            }).catch(error => {
                new LayoutToast(error.message, 'error');
            });
        }
    } else {
        new LayoutToast(__('Required'), 'error');
    }
}));