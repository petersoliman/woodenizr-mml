import axios from "axios";
import {debounce} from "../../helpers/requests";
import LayoutToast from "../../layout/toasts/l-toast";

export default class MainHeaderCartSidebarItem {
    constructor(data, el = {}) {
        this.data = data;
        this.el = el;
        if (typeof this.el === 'object' && this.el !== null && Object.keys(this.el).length == 0) {
            const element = this.draw();
            this.el = element;
            this.html = element.innerHTML;
        } else {
            this.html = this.el.innerHTML;
        }
        return this.el;
    }

    draw() {
        const product = this.data.product;
        const container = document.createElement("div");
        container.classList.add("m-header-cart-sidebar-item");
        const imageContainer = document.createElement("div");
        imageContainer.classList.add("m-header-cart-sidebar-item-img-box");

        const imageAnchor = document.createElement("a")
        imageAnchor.href = product.absoluteUrl;
        const image = document.createElement("img");
        image.classList.add("m-header-cart-sidebar-item-img");
        image.src = product.mainImage;
        image.alt = product.title;
        imageAnchor.append(image);
        imageContainer.append(imageAnchor);
        container.append(imageContainer);


        container.append(
            this.drawCartItemContent(),
            this.drawCartItemActions()
        );
        return container;
    }


    drawCartItemContent() {
        const product = this.data.product;

        const container = document.createElement("div");
        container.classList.add("m-header-cart-sidebar-item-content");
        const titleAnchor = document.createElement("a");
        titleAnchor.classList.add("m-header-cart-sidebar-item-content-title");
        titleAnchor.href = product.absoluteUrl;
        titleAnchor.textContent = product.title;
        container.append(titleAnchor);
        if (product.variants && product.variants.length) {
            const variants = product.variants;
            const variantsContainer = document.createElement("div");
            variantsContainer.classList.add("m-header-cart-sidebar-item-content-variants");
            variants.forEach(variant => {
                let variantNode = document.createElement("span");
                variantNode.innerHTML = variant;
                variantsContainer.appendChild(variantNode);
            });
            container.append(variantsContainer);
        }

        const footerContainer = document.createElement("div");
        footerContainer.classList.add("m-header-cart-sidebar-item-content-footer");

        const price = document.createElement("span");
        price.classList.add("m-header-cart-sidebar-item-content-footer-price");

        price.textContent = new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(product.price.salePrice) + ' ' + APP.currency;
        footerContainer.append(price);

        const qtyInputGroup = document.createElement("div");
        qtyInputGroup.classList.add("l-form-input-group", "m-header-cart-sidebar-item-content-footer-qty-input-group");

        const minusBtn = document.createElement("button");
        minusBtn.classList.add("ui-form-input-group-item", "l-form-input-group-text", "m-header-cart-sidebar-item-content-footer-qty-btn")
        minusBtn.type = "button";
        minusBtn.innerHTML = `<svg width="13" height="2" viewBox="0 0 13 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.8463 1.3258C12.8463 0.966154 12.5127 0.674618 12.1011 0.674618H1.66841C1.25686 0.674618 0.923218 0.966154 0.923218 1.3258C0.923218 1.68545 1.25686 1.97699 1.66841 1.97699H12.1011C12.5127 1.97699 12.8463 1.68545 12.8463 1.3258Z" fill="#84818A" /></svg>`;
        if (this.data.qty <= 1) {
            minusBtn.classList.add('disabled');
        }
        qtyInputGroup.append(minusBtn);

        const qtyInput = document.createElement("input");
        qtyInput.classList.add("l-form-input", "ui-form-input", "ui-form-input-group-item", "m-header-cart-sidebar-item-content-footer-qty-input")
        qtyInput.type = "text";
        qtyInput.value = this.data.qty;
        qtyInputGroup.append(qtyInput);

        const plusBtn = document.createElement("button");
        plusBtn.classList.add("ui-form-input-group-item", "l-form-input-group-text", "m-header-cart-sidebar-item-content-footer-qty-btn")
        plusBtn.type = "button";
        plusBtn.innerHTML = `<svg width="11" height="9" viewBox="0 0 11 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22502 3.62842H6.32942C6.23535 3.62842 6.15909 3.56594 6.15909 3.48888V1.11671C6.15909 0.731404 5.77776 0.419006 5.30744 0.419006C4.83712 0.419006 4.45579 0.731404 4.45579 1.11671V3.48888C4.45579 3.56594 4.37952 3.62842 4.28546 3.62842H1.38986C0.919537 3.62842 0.538208 3.94082 0.538208 4.32612C0.538208 4.71142 0.919537 5.02382 1.38986 5.02382H4.28546C4.37952 5.02382 4.45579 5.0863 4.45579 5.16336V7.53554C4.45579 7.92084 4.83712 8.23324 5.30744 8.23324C5.77776 8.23324 6.15909 7.92084 6.15909 7.53554V5.16336C6.15909 5.0863 6.23535 5.02382 6.32942 5.02382H9.22502C9.69534 5.02382 10.0767 4.71142 10.0767 4.32612C10.0767 3.94082 9.69534 3.62842 9.22502 3.62842Z" fill="#84818A" /></svg>`;
        qtyInputGroup.append(plusBtn);
        footerContainer.append(qtyInputGroup);

        minusBtn.addEventListener('click', e => {
            if (!minusBtn.classList.contains('disabled') && this.data.qty > 0) {
                qtyInput.value = Number(qtyInput.value) - 1;
            }
        });

        minusBtn.addEventListener('click', debounce(e => {
            if (!minusBtn.classList.contains('disabled') && this.data.qty > 0) {
                this.updateQty(this.data.qty - 1);
            }
        }, 200));

        qtyInput.addEventListener('input', debounce(e => {
            if (qtyInput.value > 0) {
                this.updateQty(qtyInput.value);
            } else {
                qtyInput.value = Number(1);
            }
        }, 500));

        plusBtn.addEventListener('click', e => {
            if (!plusBtn.classList.contains('disabled')) {
                qtyInput.value = Number(qtyInput.value) + 1;
            }
        });

        plusBtn.addEventListener('click', debounce(e => {
            if (!plusBtn.classList.contains('disabled')) {
                this.updateQty(Number(this.data.qty) + 1);
            }
        }, 200));

        container.append(footerContainer);
        return container;
    }

    updateQty(qty) {
        let bodyFormData = new FormData();
        bodyFormData.append('productPriceId', this.data.product.price.id);
        bodyFormData.append('qty', qty);

        axios.post(APP.cartUrls.updateQty, bodyFormData).then(response => {
            if (!response.data.error) {
                window.APP_DATA.cart.cart = response.data.cart;
                window.APP_DATA.cart.saveToLocalStorage();
                window.APP_DATA.cart.render();
                window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
            } else {
                new LayoutToast(response.data.message, 'error');
                window.APP_DATA.cart.render();
            }
        }).catch(error => {
            new LayoutToast(error.message, 'error');
        });
    }

    drawCartItemActions() {
        const product = this.data.product;

        const actionsContainer = document.createElement("div");
        actionsContainer.classList.add("m-header-cart-sidebar-item-actions");
        if (window.APP.isLoggedIn) {
            const saveForLaterTxt = __("Save for Later");
            const saveForLaterBtn = document.createElement("button");
            saveForLaterBtn.classList.add("m-header-cart-sidebar-item-action");
            saveForLaterBtn.type = "button";
            saveForLaterBtn.setAttribute("aria-label", saveForLaterTxt);
            saveForLaterBtn.setAttribute("title", saveForLaterTxt);
            saveForLaterBtn.dataset.tooltip = "true";
            saveForLaterBtn.dataset.tippyContent = saveForLaterTxt;
            saveForLaterBtn.innerHTML = `<svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M10 17C9.74371 17 9.4967 16.9072 9.30415 16.7387C8.57698 16.1035 7.87593 15.5065 7.25739 14.9799L7.25423 14.9772C5.4408 13.4332 3.87483 12.0998 2.78527 10.7863C1.56732 9.31795 1 7.92567 1 6.40472C1 4.92701 1.50716 3.5637 2.42794 2.5658C3.35972 1.5561 4.63825 1 6.02843 1C7.06748 1 8.01902 1.32821 8.85661 1.97542C9.2793 2.30208 9.66243 2.70191 10 3.16827C10.3377 2.70191 10.7207 2.30208 11.1435 1.97542C11.9811 1.32821 12.9327 1 13.9717 1C15.3617 1 16.6404 1.5561 17.5722 2.5658C18.493 3.5637 19 4.92701 19 6.40472C19 7.92563 18.4328 9.31792 17.2149 10.7861C16.1253 12.0998 14.5594 13.433 12.7463 14.9769C12.1267 15.5043 11.4245 16.1022 10.6957 16.739C10.5031 16.9073 10.2559 17.0001 10 17ZM6.02843 2.05347C4.93627 2.05347 3.93295 2.48898 3.20303 3.27982C2.46229 4.08264 2.05427 5.19238 2.05427 6.40472C2.05427 7.6839 2.53011 8.82792 3.59703 10.1141C4.62823 11.3573 6.16206 12.6633 7.93802 14.1755L7.94129 14.1782C8.56214 14.7068 9.26597 15.3062 9.99849 15.9461C10.7354 15.3049 11.4403 14.7047 12.0624 14.1752C13.8382 12.663 15.3719 11.3573 16.4031 10.1141C17.4699 8.82792 17.9457 7.6839 17.9457 6.40472C17.9457 5.19234 17.5377 4.08261 16.797 3.27982C16.0672 2.48898 15.0638 2.05347 13.9717 2.05347C13.1716 2.05347 12.437 2.3076 11.7884 2.80866C11.2104 3.25541 10.8078 3.82015 10.5717 4.21531C10.4503 4.41851 10.2366 4.53979 10 4.53979C9.76336 4.53979 9.54968 4.41851 9.42829 4.21531C9.19239 3.82015 8.78971 3.25541 8.21153 2.80866C7.56297 2.3076 6.82838 2.05347 6.02843 2.05347Z" fill="#686868" stroke="#5F5F5F" stroke-width="0.4"/> </svg>`;
            actionsContainer.append(saveForLaterBtn);

            saveForLaterBtn.addEventListener('click', e => {
                if (window.APP.isLoggedIn) {
                    let bodyFormData = new FormData();
                    bodyFormData.append('productPriceId', product.price.id);

                    axios.post(APP.cartUrls.removeFromCartAndAddToWishlist, bodyFormData).then(response => {
                        if (!response.data.error) {
                            window.APP_DATA.cart.cart = response.data.cart;
                            window.APP_DATA.cart.saveToLocalStorage();
                            window.APP_DATA.cart.render();
                            window.APP_DATA.cart.gtmEnhancedEcommerceAddToCart(response.data.gtmProductsObjects);
                        } else {
                            new LayoutToast(response.data.message, 'error');
                        }
                    }).catch(error => {
                        new LayoutToast(error.message, 'error');
                    });
                } else {
                    new LayoutToast(__('Please login first') + '.', 'error');
                    document.querySelector('[data-header-cart-sidebar]').classList.remove('show');
                    document.querySelector(`[data-m-header-backdrop]`).classList.remove('show');
                    document.querySelector(`[data-m-body-backdrop]`).classList.remove('show');
                    document.querySelector(`[data-m-footer-backdrop]`).classList.remove('show');
                    setTimeout(() => {
                        window.APP_DOM.modals.accountModal.show();
                    }, 50);
                }
            });
        }

        const deleteTxt = __("Delete");
        const deleteBtn = document.createElement("button");
        deleteBtn.classList.add("m-header-cart-sidebar-item-action");
        deleteBtn.type = "button";
        deleteBtn.setAttribute("aria-label", deleteTxt);
        deleteBtn.setAttribute("title", deleteTxt);
        deleteBtn.dataset.tooltip = "true";
        deleteBtn.dataset.tippyContent = deleteTxt;
        deleteBtn.dataset.lModalBtn = "cart-delete";
        deleteBtn.addEventListener('click', e => {
            document.querySelectorAll('[data-header-cart-delete-modal-product-img]').forEach(node => node.src = product.mainImage);
            document.querySelectorAll('[data-header-cart-delete-modal-product-title]').forEach(node => node.innerText = product.title);
            document.querySelectorAll('[data-header-cart-delete-modal-product-price]').forEach(node => node.innerText = new Intl.NumberFormat('en-US', {
                maximumFractionDigits: 0,
                minimumFractionDigits: 0
            }).format(product.price.salePrice) + ' ' + APP.currency);
            document.querySelectorAll('[data-header-cart-delete-modal-action-save]').forEach(node => node.dataset.headerCartDeleteModalActionSave = product.price.id);
            document.querySelectorAll('[data-header-cart-delete-modal-action-remove]').forEach(node => node.dataset.headerCartDeleteModalActionRemove = product.price.id);
        });
        deleteBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.2119 17.6667H5.59343C4.93125 17.6571 4.29709 17.3979 3.81776 16.941C3.33843 16.484 3.04926 15.863 3.00808 15.202L2.22385 3.27488C2.21983 3.1884 2.23299 3.10199 2.26258 3.02063C2.29216 2.93928 2.33758 2.86459 2.39621 2.8009C2.45661 2.73489 2.52973 2.68177 2.61119 2.64475C2.69264 2.60772 2.78074 2.58755 2.87019 2.58545H14.9352C15.0238 2.58525 15.1115 2.60329 15.1929 2.63843C15.2743 2.67357 15.3475 2.72507 15.4082 2.78973C15.4688 2.85439 15.5154 2.93082 15.5453 3.01428C15.5751 3.09774 15.5874 3.18645 15.5815 3.27488L14.8318 15.202C14.7902 15.8688 14.4961 16.4948 14.0095 16.9527C13.5228 17.4105 12.8801 17.6658 12.2119 17.6667ZM3.59409 3.87813L4.25767 15.1244C4.27959 15.4636 4.42994 15.7816 4.67811 16.0137C4.92628 16.2459 5.2536 16.3747 5.59343 16.374H12.2119C12.5512 16.3727 12.8774 16.2432 13.1251 16.0114C13.3728 15.7797 13.5238 15.4628 13.5477 15.1244L14.2458 3.92122L3.59409 3.87813Z" fill="#5F5F5F" /><path d="M16.6585 3.87813H1.14634C0.974919 3.87813 0.81052 3.81003 0.689308 3.68882C0.568096 3.56761 0.5 3.40321 0.5 3.23179C0.5 3.06037 0.568096 2.89597 0.689308 2.77476C0.81052 2.65355 0.974919 2.58545 1.14634 2.58545H16.6585C16.8299 2.58545 16.9943 2.65355 17.1155 2.77476C17.2367 2.89597 17.3048 3.06037 17.3048 3.23179C17.3048 3.40321 17.2367 3.56761 17.1155 3.68882C16.9943 3.81003 16.8299 3.87813 16.6585 3.87813Z" fill="#5F5F5F" /><path d="M11.4872 3.87803H6.31651C6.14578 3.8758 5.98267 3.80699 5.86194 3.68626C5.74121 3.56553 5.6724 3.40242 5.67017 3.2317V1.68048C5.68109 1.23823 5.86165 0.817111 6.17446 0.504297C6.48728 0.191482 6.9084 0.0109248 7.35065 0H10.4531C10.9027 0.0112437 11.3301 0.197786 11.6441 0.519807C11.958 0.841829 12.1337 1.27382 12.1336 1.72357V3.2317C12.1313 3.40242 12.0625 3.56553 11.9418 3.68626C11.821 3.80699 11.6579 3.8758 11.4872 3.87803ZM6.96284 2.58536H10.8409V1.72357C10.8409 1.62072 10.8 1.52208 10.7273 1.44935C10.6546 1.37663 10.5559 1.33577 10.4531 1.33577H7.35065C7.2478 1.33577 7.14916 1.37663 7.07643 1.44935C7.0037 1.52208 6.96284 1.62072 6.96284 1.72357V2.58536Z" fill="#5F5F5F" /><path d="M11.4876 14.2197C11.3169 14.2174 11.1538 14.1486 11.0331 14.0279C10.9124 13.9072 10.8435 13.7441 10.8413 13.5733V6.67905C10.8413 6.50763 10.9094 6.34324 11.0306 6.22202C11.1518 6.10081 11.3162 6.03271 11.4876 6.03271C11.6591 6.03271 11.8235 6.10081 11.9447 6.22202C12.0659 6.34324 12.134 6.50763 12.134 6.67905V13.5733C12.1318 13.7441 12.0629 13.9072 11.9422 14.0279C11.8215 14.1486 11.6584 14.2174 11.4876 14.2197Z" fill="#5F5F5F" /><path d="M6.31651 14.2197C6.14578 14.2174 5.98267 14.1486 5.86194 14.0279C5.74121 13.9072 5.6724 13.7441 5.67017 13.5733V6.67905C5.67017 6.50763 5.73826 6.34324 5.85947 6.22202C5.98069 6.10081 6.14509 6.03271 6.31651 6.03271C6.48792 6.03271 6.65232 6.10081 6.77354 6.22202C6.89475 6.34324 6.96284 6.50763 6.96284 6.67905V13.5733C6.96061 13.7441 6.8918 13.9072 6.77107 14.0279C6.65034 14.1486 6.48723 14.2174 6.31651 14.2197Z" fill="#5F5F5F" /><path d="M8.90293 14.2197C8.73221 14.2174 8.5691 14.1486 8.44837 14.0279C8.32764 13.9072 8.25882 13.7441 8.25659 13.5733V6.67905C8.25659 6.50763 8.32469 6.34324 8.4459 6.22202C8.56711 6.10081 8.73151 6.03271 8.90293 6.03271C9.07435 6.03271 9.23875 6.10081 9.35996 6.22202C9.48117 6.34324 9.54927 6.50763 9.54927 6.67905V13.5733C9.54704 13.7441 9.47822 13.9072 9.35749 14.0279C9.23676 14.1486 9.07366 14.2174 8.90293 14.2197Z" fill="#5F5F5F" /></svg>`;

        actionsContainer.append(deleteBtn);

        return actionsContainer;
    }

}