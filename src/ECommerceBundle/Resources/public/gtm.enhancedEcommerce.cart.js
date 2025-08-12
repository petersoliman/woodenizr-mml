function enhancedEcommerceAddToCart(productObj, callbackOrRedirectUrl) {
    if (typeof (dataLayer) === "undefined") {
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
            'currencyCode': window.gtmEnhancedEcommerceCurrencyCode || '',
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
    dataLayer.push(obj);
}

/**
 *
 * @param {Object} productObj
 * @param {function|string} callbackOrRedirectUrl
 */
function enhancedEcommerceRemoveFromCart(productObj, callbackOrRedirectUrl) {
    if (typeof (dataLayer) === "undefined") {
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
            'currencyCode': window.gtmEnhancedEcommerceCurrencyCode || '',
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
    dataLayer.push(obj);
}
