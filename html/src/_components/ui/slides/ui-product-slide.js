export default class UiProductSlide {
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
        const container = document.createElement('article');
        container.classList.add("ui-product-slide");

        container.append(this.drawProductHeader(), this.drawProductBody(), this.drawProductFooter());

        return container;
    }

    drawProductHeader() {
        const header = document.createElement('div');
        header.classList.add("l-product-slide-header", "ui-product-slide-header");


        if (this.data.promotionPercentage > 0) {
            const headerCornerStart = document.createElement("div");
            headerCornerStart.classList.add("l-product-slide-header-corner-start");
            const promotionBadge = document.createElement("span");
            promotionBadge.classList.add("l-product-slide-badge", "ui-product-slide-badge", `ui-product-slide-badge--color-${this.data.promotionPercentageColor}`);
            promotionBadge.textContent = this.data.promotionPercentage + "%";

            headerCornerStart.appendChild(promotionBadge);
            header.appendChild(headerCornerStart);
        }
        const headerCornerEnd = document.createElement("div");
        headerCornerEnd.classList.add("l-product-slide-header-corner-end");

        const actionBtns = document.createElement("div");
        actionBtns.classList.add("l-product-slide-header-actions");

        const wishListTxt = __("Add to Wishlist");
        const wishListBtn = document.createElement("button");
        wishListBtn.type = "button"
        wishListBtn.classList.add("l-product-slide-header-action", "ui-product-slide-header-action", "ui-product-slide-header-action--hoverable")
        if (this.data.isFavorite) {
            wishListBtn.classList.add("ui-product-slide-header-action--active");
        }
        wishListBtn.setAttribute("role", "button");
        wishListBtn.setAttribute("aria-label", wishListTxt);
        wishListBtn.setAttribute("aria-labelledby", wishListTxt);
        wishListBtn.setAttribute("data-toggle-product-wishlist", "true");
        wishListBtn.setAttribute("data-toggle-product-wishlist-url", this.data.addRemoveFromFavorite);
        wishListBtn.setAttribute("title", wishListTxt);
        wishListBtn.dataset.tooltip = "true";
        wishListBtn.dataset.tippyContent = wishListTxt;
        wishListBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" fill="none"><path d="M16.2717 26.7137C15.8434 26.7137 15.4307 26.5603 15.109 26.2816C13.8941 25.2308 12.7227 24.2433 11.6893 23.3722L11.684 23.3677C8.6541 20.8138 6.03767 18.6082 4.21722 16.4356C2.18225 14.0068 1.23438 11.7038 1.23438 9.18802C1.23438 6.74373 2.08175 4.48868 3.62019 2.83805C5.17702 1.16789 7.3132 0.248047 9.63593 0.248047C11.372 0.248047 12.9618 0.790937 14.3613 1.8615C15.0675 2.40183 15.7076 3.06319 16.2717 3.83459C16.8359 3.06319 17.4758 2.40183 18.1823 1.8615C19.5817 0.790937 21.1716 0.248047 22.9076 0.248047C25.2301 0.248047 27.3665 1.16789 28.9234 2.83805C30.4618 4.48868 31.3089 6.74373 31.3089 9.18802C31.3089 11.7038 30.3613 14.0068 28.3263 16.4353C26.5058 18.6082 23.8896 20.8136 20.8602 23.3673C19.8249 24.2397 18.6518 25.2287 17.4341 26.282C17.1122 26.5604 16.6992 26.7138 16.2717 26.7137ZM9.63593 1.99059C7.81113 1.99059 6.13477 2.71098 4.91522 4.01911C3.67758 5.34706 2.99585 7.18268 2.99585 9.18802C2.99585 11.3039 3.79089 13.1962 5.57351 15.3238C7.29646 17.3802 9.8592 19.5403 12.8265 22.0416L12.8319 22.0462C13.8693 22.9206 15.0452 23.9119 16.2691 24.9705C17.5004 23.9099 18.6782 22.917 19.7175 22.0412C22.6846 19.5399 25.2471 17.3802 26.97 15.3238C28.7524 13.1962 29.5475 11.3039 29.5475 9.18802C29.5475 7.18262 28.8657 5.347 27.6281 4.01911C26.4088 2.71098 24.7322 1.99059 22.9076 1.99059C21.5708 1.99059 20.3435 2.41095 19.2598 3.23975C18.2941 3.97873 17.6213 4.91287 17.2268 5.5665C17.0241 5.90261 16.667 6.10323 16.2717 6.10323C15.8763 6.10323 15.5193 5.90261 15.3164 5.5665C14.9223 4.91287 14.2495 3.97873 13.2835 3.23975C12.1998 2.41095 10.9725 1.99059 9.63593 1.99059Z" fill="#686868" stroke="#686868" stroke-width="0.481194"/></svg>`;
        // wishListBtn.innerHTML = `<svg viewBox="0 0 25 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="#686868" style="stroke-width:16;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:1;stroke-miterlimit:4;" d="M 127.983871 216.001106 C 127.983871 216.001106 27.984414 160.014528 27.984414 91.984126 C 27.984414 67.208259 45.480813 45.873484 69.814482 40.987133 C 94.113088 36.169603 118.481819 49.108111 127.983871 71.991378 C 137.520986 49.108111 161.889718 36.169603 186.188324 40.987133 C 210.521993 45.873484 227.983328 67.208259 227.983328 91.984126 C 227.983328 160.014528 127.983871 216.001106 127.983871 216.001106 Z M 127.983871 216.001106 " transform="matrix(0.111407,0,0,0.113518,-1.844218,-3.547297)"/></svg>`;
        actionBtns.append(wishListBtn);


        const shareTxt = __("Share");
        const shareBtn = document.createElement("button");
        shareBtn.setAttribute("role", "button");
        shareBtn.setAttribute("aria-label", shareTxt);
        shareBtn.setAttribute("aria-labelledby", shareTxt);
        shareBtn.classList.add("l-product-slide-header-action", "ui-product-slide-header-action", "ui-product-slide-header-action--hoverable")
        shareBtn.type = "button"
        shareBtn.setAttribute("title", shareTxt);
        shareBtn.dataset.tooltip = "true";
        shareBtn.dataset.tippyContent = shareTxt;
        shareBtn.dataset.lModalBtn = "share-product";
        shareBtn.dataset.shareProductModalBtn = this.data.absoluteUrl;
        shareBtn.innerHTML = `<svg viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.299 18.161a4.83 4.83 0 0 0-3.477 1.448l-7.74-4.507a4.163 4.163 0 0 0 0-3.204l7.74-4.507a4.83 4.83 0 0 0 3.477 1.448C22.89 8.839 25 6.856 25 4.419S22.891 0 20.299 0c-2.593 0-4.702 1.982-4.702 4.42 0 .564.115 1.105.321 1.602l-7.74 4.506a4.83 4.83 0 0 0-3.477-1.447C2.11 9.08 0 11.063 0 13.5c0 2.437 2.109 4.42 4.701 4.42a4.829 4.829 0 0 0 3.477-1.448l7.74 4.506a4.168 4.168 0 0 0-.321 1.603c0 2.436 2.11 4.419 4.702 4.419S25 25.017 25 22.58c0-2.436-2.109-4.419-4.701-4.419ZM17.31 4.42c0-1.548 1.34-2.808 2.988-2.808 1.647 0 2.987 1.26 2.987 2.808 0 1.549-1.34 2.808-2.988 2.808-1.647 0-2.987-1.26-2.987-2.808ZM4.701 16.308c-1.647 0-2.987-1.26-2.987-2.808 0-1.548 1.34-2.808 2.987-2.808 1.648 0 2.987 1.26 2.987 2.808 0 1.548-1.34 2.808-2.987 2.808Zm12.61 6.273c0-1.549 1.34-2.808 2.988-2.808 1.647 0 2.987 1.26 2.987 2.808 0 1.548-1.34 2.808-2.988 2.808-1.647 0-2.987-1.26-2.987-2.808Z" fill="#686868"/></svg>`;
        actionBtns.append(shareBtn);


        /*if (this.data.isFulfilled === true) {
            const fulfilledTxt = __("Fulfilled by JustPiece");
            const fulfilledIcon = document.createElement("span");
            fulfilledIcon.classList.add("l-product-slide-header-action", "ui-product-slide-header-action", "ui-product-slide-header-action--not-clickable");
            fulfilledIcon.title = fulfilledTxt;
            // fulfilledIcon.setAttribute("aria-label", fulfilledTxt);
            // fulfilledIcon.setAttribute("aria-labelledby", fulfilledTxt);
            fulfilledIcon.dataset.tooltip = "true";
            fulfilledIcon.dataset.tippyContent = fulfilledTxt;
            fulfilledIcon.innerHTML = `<svg viewBox="0 0 25 29" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="25" height="29" rx="5" fill="#37A446"/><path d="M8.38 9.201h-.126V15.854c0 .1-.057.304-.246.594-.18.276-.446.58-.774.864-.668.582-1.503 1.018-2.244 1.078l-.126.01.01.126.077.884.01.125.125-.01c1.082-.088 2.146-.687 2.922-1.361a5.45 5.45 0 0 0 .982-1.103c.238-.366.428-.79.428-1.207V9.201H8.38ZM11.723 12.843v-.126H10.56V19.453H11.723v-6.61Z" fill="#fff" stroke="#fff" stroke-width=".252"/><path d="M18.775 13.433c0 1.886-1.573 3.42-3.52 3.42s-3.52-1.534-3.52-3.42 1.573-3.42 3.52-3.42 3.52 1.534 3.52 3.42Zm-3.52 4.56c2.583 0 4.684-2.038 4.684-4.56 0-2.521-2.1-4.56-4.684-4.56-2.583 0-4.683 2.039-4.683 4.56 0 2.521 2.1 4.56 4.683 4.56Z" fill="#fff" stroke="#fff" stroke-width=".252"/></svg>`;

            actionBtns.append(fulfilledIcon);
        } else if (this.data.isGlobal === true) {
            const globalTxt = "Global";
            const globalIcon = document.createElement("span");
            globalIcon.classList.add("l-product-slide-header-action", "ui-product-slide-header-action", "ui-product-slide-header-action--not-clickable");
            globalIcon.title = globalTxt;
            // globalIcon.setAttribute("aria-label", globalTxt);
            // globalIcon.setAttribute("aria-labelledby", globalTxt);
            globalIcon.dataset.tooltip = "true";
            globalIcon.dataset.tippyContent = globalTxt;
            globalIcon.innerHTML = `<svg viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 0A12.5 12.5 0 1 0 25 12.5 12.514 12.514 0 0 0 12.5 0Zm8.91 7.408h-3.054A15.267 15.267 0 0 0 16.844 3.2a10.334 10.334 0 0 1 4.567 4.21Zm1.358 5.092c0 .968-.138 1.93-.408 2.86h-3.646c.105-.95.157-1.905.156-2.86 0-.955-.051-1.91-.156-2.86h3.646c.27.93.408 1.892.408 2.86ZM12.5 22.768c-1.328 0-2.762-2.03-3.545-5.176h7.09c-.783 3.145-2.217 5.176-3.545 5.176Zm-3.958-7.409a22.84 22.84 0 0 1 0-5.719h7.916c.12.949.18 1.904.18 2.86s-.06 1.911-.18 2.86H8.542ZM2.232 12.5c0-.968.138-1.93.408-2.86h3.646a26.342 26.342 0 0 0 0 5.72H2.64c-.27-.93-.408-1.892-.408-2.86ZM12.5 2.232c1.328 0 2.762 2.03 3.545 5.176h-7.09c.783-3.145 2.217-5.176 3.545-5.176ZM8.156 3.2a15.266 15.266 0 0 0-1.512 4.21H3.589a10.334 10.334 0 0 1 4.567-4.21ZM3.589 17.592h3.055c.295 1.47.804 2.887 1.512 4.209a10.334 10.334 0 0 1-4.567-4.21ZM16.844 21.8a15.268 15.268 0 0 0 1.512-4.21h3.055a10.334 10.334 0 0 1-4.567 4.21Z" fill="#2D6ADF" /></svg>`;

            actionBtns.append(globalIcon);
        }*/
        headerCornerEnd.appendChild(actionBtns);
        header.appendChild(headerCornerEnd);

        const imageContainer = document.createElement("a");
        imageContainer.classList.add("l-product-slide-img-link", "ui-product-slide-img-link");
        imageContainer.href = this.data.absoluteUrl;
        const img = document.createElement("img");
        img.classList.add("l-product-slide-img", "ui-product-slide-img", "lazyload");
        if (!this.data.mainImage) {
            img.classList.add("ui-product-slide-img--placeholder");
        }
        img.width = 146;
        img.height = 146;
        img.src = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        img.dataset.expand = -20;
        img.dataset.src = this.data.mainImage ? this.data.mainImage : '/assets/img/product-img-placeholder.webp';
        img.alt = this.data.title;
        imageContainer.append(img);

        header.appendChild(imageContainer);

        return header;
    }

    drawProductBody() {
        const wrapper = document.createElement('div');
        const body = document.createElement('a');
        body.classList.add("l-product-slide-body", "ui-product-slide-body", "l-product-slide-body--one-line");
        body.href = this.data.absoluteUrl;
        body.textContent = this.data.title;
        wrapper.append(body)
        if (typeof this.data.category !== "undefined") {
            const category = document.createElement('div');
            category.classList.add("l-product-slide-body-txt", "ui-product-slide-body-txt");
            category.textContent = "(" + this.data.category + ")";
            wrapper.append(category)
        }
        return wrapper;
    }

    drawProductFooter() {
        const footer = document.createElement('div');
        footer.classList.add("l-product-slide-footer", "ui-product-slide-footer");

        const productMeta = document.createElement('div');
        productMeta.classList.add("l-product-slide-meta");

        if (this.data.enableAddToCart === true) {
            const priceWrapper = document.createElement('div');
            const priceAnchor = document.createElement("a");
            priceAnchor.href = this.data.absoluteUrl;
            priceAnchor.classList.add("ui-product-slide-price-container");

            const price = document.createElement("p");
            price.classList.add("l-product-slide-price", "ui-product-slide-price");
            price.textContent = this.data.sellPrice;
            priceAnchor.append(price);
            if (this.data.promotionPercentage > 0) {
                const discount = document.createElement("span");
                discount.classList.add("l-product-slide-price-desc", "ui-product-slide-price-desc");
                discount.textContent = `(` + __("Saved") + ` ${this.data.priceSaved})`;
                priceAnchor.append(discount);
            }

            priceWrapper.appendChild(priceAnchor);
            productMeta.append(priceWrapper);
        }

        if (this.data.rate > 0) {
            const ratingWrapper = document.createElement('div');

            const ratingTxt = __("Ratings");
            const ratingContainer = document.createElement("span");
            ratingContainer.classList.add("l-product-slide-rating-container", "ui-product-slide-rating-container");
            ratingContainer.title = ratingTxt;
            ratingContainer.dataset.tooltip = "true";
            ratingContainer.dataset.tippyContent = ratingTxt;

            const ratings = document.createElement("span");
            ratings.classList.add("l-product-slide-rating", "ui-product-slide-rating");
            ratings.innerHTML = `<svg width="15" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.85 8.812a.758.758 0 0 0-.219.668l.612 3.39a.744.744 0 0 1-.31.743.758.758 0 0 1-.806.055l-3.05-1.59a.78.78 0 0 0-.345-.091h-.186a.56.56 0 0 0-.186.062l-3.052 1.599a.805.805 0 0 1-.489.075.766.766 0 0 1-.613-.875l.613-3.39a.77.77 0 0 0-.22-.673L1.113 6.374a.744.744 0 0 1-.185-.779.773.773 0 0 1 .612-.516l3.424-.497a.766.766 0 0 0 .606-.42L7.077 1.07a.717.717 0 0 1 .138-.186l.062-.048a.462.462 0 0 1 .111-.09L7.463.72 7.58.67h.29a.77.77 0 0 1 .606.414l1.529 3.079c.11.225.324.381.571.42L14 5.078a.78.78 0 0 1 .627.516c.09.277.012.58-.2.779L11.85 8.812Z" fill="#EEC337"/></svg>
                            <span>${this.data.rate}</span>`;
            ratingContainer.append(ratings);

            if (this.data.rateCount > 0) {
                const rateCount = document.createElement("span");
                rateCount.classList.add("ui-product-slide-rating-count")
                rateCount.textContent = `(${this.data.rateCount})`;
                ratingContainer.append(rateCount);
            }

            ratingWrapper.append(ratingContainer);
            productMeta.append(ratingWrapper);
        }
        footer.append(productMeta);
        if (this.data.enableAddToCart === true) {
            if (this.data.hasMultiPrices) {
                const quickViewTxt = __("Quick View");
                const quickViewBtn = document.createElement("button");
                quickViewBtn.classList.add("l-product-slide-footer-action", "ui-product-slide-footer-action");
                quickViewBtn.setAttribute("data-l-modal-btn", "quick-view");
                quickViewBtn.setAttribute("data-quick-view-modal-btn", this.data.id);
                quickViewBtn.setAttribute("data-quick-view-modal-btn-title", this.data.title);
                quickViewBtn.setAttribute("data-quick-view-modal-btn-img", this.data.mainImage ? this.data.mainImage : '');
                quickViewBtn.setAttribute("data-quick-view-modal-btn-url", this.data.absoluteUrl);
                quickViewBtn.setAttribute("role", "button");
                quickViewBtn.setAttribute("aria-label", quickViewTxt);
                quickViewBtn.type = "button"
                quickViewBtn.textContent = quickViewTxt

                footer.append(quickViewBtn);
            } else {
                const addToCartTxt = __("Add to Cart");
                const addToCartBtn = document.createElement("button");
                addToCartBtn.classList.add("l-product-slide-footer-action", "ui-product-slide-footer-action");
                addToCartBtn.setAttribute("data-header-cart-sidebar-btn", "true");
                addToCartBtn.setAttribute("data-header-cart-sidebar-add-product-btn", this.data.productPriceId);
                addToCartBtn.setAttribute("role", "button");
                addToCartBtn.setAttribute("aria-label", addToCartTxt);
                addToCartBtn.type = "button"
                addToCartBtn.textContent = addToCartTxt
                addToCartBtn.dataset.headerCartSidebarBtn = "true";
    
                footer.append(addToCartBtn);
            }
        } else {
            const outOfStockTxt = __("Out of Stock");
            const outOfStockBtn = document.createElement("button");
            outOfStockBtn.type = "button"
            outOfStockBtn.setAttribute("role", "button");
            outOfStockBtn.setAttribute("aria-label", outOfStockTxt);
            outOfStockBtn.classList.add("l-product-slide-footer-action", "ui-product-slide-footer-action");
            outOfStockBtn.disabled = true;
            outOfStockBtn.textContent = outOfStockTxt
            footer.append(outOfStockBtn);
        }

        return footer;
    }

}