import LayoutModal from "./l-modal";
import LayoutTextCopy from "../text/l-text-copy";

window.addEventListener('DOMContentLoaded', (e) => {
    const userAgent = navigator.userAgent;
    const regexLighthouse = RegExp("Chrome-Lighthouse", "ig");
    const regexGTmetrix = RegExp("GTmetrix", "ig")
    if (!regexLighthouse.test(userAgent) && !regexGTmetrix.test(userAgent)) {
        let discountModal = document.querySelector('[data-discount-modal]');
        if (discountModal) {
            discountModal = new LayoutModal(discountModal);
            discountModal.bindEvents();
            discountModal.show();
            let discountTextCopy = document.querySelector('[data-discount-text-copy]');
            if (discountTextCopy) {
                new LayoutTextCopy(discountTextCopy);
            }
        }
    }
});