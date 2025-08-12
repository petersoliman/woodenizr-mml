
/**
 * @param {integer} stepId
 * @param {string} checkoutOption
 */
function enhancedEcommerceTrackCheckoutOption(stepId, checkoutOption) {
    if (typeof (dataLayer) === "undefined") {
        return false;
    }
    var obj = {
        'event': 'checkoutOption',
        'ecommerce': {
            'checkout_option': {
                'actionField': {
                    'step': stepId,
                    'option': checkoutOption
                }
            }
        }
    };
    /** global: dataLayer */
    dataLayer.push(obj);
}
/**
 * @param {integer} stepId
 * @param {object} option
 */
function enhancedEcommerceTrackPurchaseOption(option) {
    if (typeof (dataLayer) === "undefined") {
        return false;
    }
    var obj = {
        'event': 'transaction',
        'ecommerce': {
            'purchase': option
        }
    };
    /** global: dataLayer */
    dataLayer.push(obj);
}
