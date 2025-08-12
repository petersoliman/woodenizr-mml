import '../../_components/main/footer/footer';
import '../../_components/layout/images/l-lazyload-img';

import '../../_components/full/form/stack-input';
import '../../_components/full/form/form-validate';

import '../../_components/full/cart/cart';

const paymentMethods = document.querySelectorAll('[data-payment-method]');
const cartSubmitBtn = document.querySelector('[data-cart-submit-btn]');
const outOfStockError = document.querySelector('[data-cart-out-of-stock-error]');
const maxStockError = document.querySelector('[data-cart-max-stock-error]');
let cartHasErrors = false;


setTimeout(() => {
    paymentMethods.forEach(paymentMethod => {
        const paymentMethodInput = paymentMethod.querySelector('input');
        if (paymentMethodInput.checked) {
            paymentMethod.classList.add('active');
            if (outOfStockError && !outOfStockError.hidden) {
                cartHasErrors = true;
            }
            if (maxStockError && !maxStockError.hidden) {
                cartHasErrors = true;
            }
            if (!cartHasErrors) {
                cartSubmitBtn.removeAttribute('disabled');
            }
        }
    });
}, 100);

paymentMethods.forEach(paymentMethod => {
    const paymentMethodInput = paymentMethod.querySelector('input');
    paymentMethodInput.addEventListener('change', () => {
        paymentMethods.forEach(paymentMethod => {
            // disabled
            paymentMethod.classList.remove('active');
        });
        if (paymentMethodInput.checked) {
            paymentMethod.classList.add('active');
            if (!cartHasErrors) {
                cartSubmitBtn.removeAttribute('disabled');
            }
        }
    });
});