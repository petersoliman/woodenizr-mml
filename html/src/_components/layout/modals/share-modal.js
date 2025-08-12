import LayoutModal from "./l-modal";
import {closest} from "../../helpers/dom";
import LayoutTextCopy from "../../layout/text/l-text-copy";

window.addEventListener('DOMContentLoaded', (e) => {
    let shareProductModal = document.querySelector('[data-share-product-modal]');
    if (shareProductModal) {
        let shareProductModalInputCopy = document.querySelector('[data-share-product-modal-input-copy]');
        shareProductModal = new LayoutModal(shareProductModal)
        shareProductModal.bindEvents();
        shareProductModal.onShow = (e) => {
            const btn = e.target.dataset.shareProductModalBtn ? e.target : closest(e.target, '[data-share-product-modal-btn]');
            if (btn) {
                const url = btn.dataset.shareProductModalBtn;
                shareProductModal.modal.querySelectorAll('[data-share-product-modal-share-btn-href]').forEach(socialBtn => {
                    socialBtn.href = socialBtn.dataset.shareProductModalShareBtnHref.replaceAll('__url__', url);
                });
                shareProductModalInputCopy.value = url;
            }
        };
        if (shareProductModalInputCopy) {
            new LayoutTextCopy(shareProductModalInputCopy);
        }
    }
});