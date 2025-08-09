import {addLiveEventListener, closest, createNode } from '../../helpers/dom';
import LayoutToast from '../../layout/toasts/l-toast';
import LayoutModal from "../../layout/modals/l-modal";
import LayoutTextCopy from "../../layout/text/l-text-copy";
import axios from 'axios';
import { initTooltips } from '../../layout/tooltips/init-l-tooltip';
import _ from 'lodash';

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
    let quickViewModal = document.querySelector('[data-quick-view-modal]');
    if (quickViewModal) {
        quickViewModal = new LayoutModal(quickViewModal)
        quickViewModal.bindEvents();
        quickViewModal.onShow = (e) => {
            const btn = e.target.dataset.quickViewModalBtn ? e.target : closest(e.target, '[data-quick-view-modal-btn]');
            if (btn) {
                const id = btn.dataset.quickViewModalBtn;
                const title = btn.dataset.quickViewModalBtnTitle;
                const img = btn.dataset.quickViewModalBtnImg;
                const url = btn.dataset.quickViewModalBtnUrl;
                if (img) {
                    document.querySelectorAll('[data-img]').forEach(elm => {
                        elm.src = img;
                    });
                }
                if (title) {
                    document.querySelectorAll('[data-title]').forEach(elm => {
                        elm.innerHTML = title;
                    });
                }
                if (url) {
                    document.querySelectorAll('[data-url]').forEach(elm => {
                        elm.href = url;
                    });
                }
                window.variants = [];
                window.prices = [];
                axios.get(`${APP.productUrls.variants}?id=${id}`).then(response => {
                    if (!response.data.error) {
                        if (response.data.variants && response.data.prices) {
                            initVariants(response.data.variants, response.data.prices)
                        }
                    } else {
                        new LayoutToast(response.data.message, 'error');
                    }
                }).catch((error) => {
                    new LayoutToast(error || error.message, 'error');
                });
            }
        };
    }
});

window.variants = [];
window.prices = [];

function initVariants (variants, prices) {
    window.variants = variants;
    window.prices = prices;
    const lowestPrice = window.prices.reduce((acc, curr) => curr.sellPrice < acc.sellPrice ? curr : acc, window.prices[0] || undefined);
    let lowestPriceOptions = [];
    if (typeof  lowestPrice !=="undefined" && typeof lowestPrice.options != 'undefined') {
        lowestPriceOptions = lowestPrice.options.map((p) => p.id);
    }
    const container = document.querySelector('[data-product-variants-container]');
    const template = document.querySelector('[data-product-variants-template]');
    container.innerHTML = '';
    // Create Variants Div
    variants.forEach(variant => {
        let variantNode = {};
        variantNode = createNode(template.innerHTML);
        variantNode = variantNode.querySelector('[data-product-variant]');
        variantNode.dataset.productVariant = variant.id;
        variantNode.querySelector('[data-product-variant-header-title]').innerHTML = variant.title;
        variantNode.querySelector('[data-product-variant-header]').addEventListener('click', () => {
            if (variantNode.classList.contains('active')) {
                variantNode.classList.remove('active');
            } else {
                variantNode.classList.add('active');
            }
        });
        variant.options.forEach((option, i) => {
            const optionTemplate = `<label class="choice" data-product-variant-choice="true">
                                        <input type="radio" name="" value="" title="" class="input" data-product-variant-choice-input="true">
                                        <span class="btn" data-product-variant-choice-btn="true" title="" data-tippy-content="" data-tooltip="true">
                                        </span>
                                    </label>`;
            const optionNode = createNode(optionTemplate);
            optionNode.querySelector('[data-product-variant-choice]').dataset.productVariantChoice = option.id;
            optionNode.querySelector('[data-product-variant-choice-input]').setAttribute('name', variant.id);
            optionNode.querySelector('[data-product-variant-choice-input]').setAttribute('value', option.id);
            optionNode.querySelector('[data-product-variant-choice-input]').setAttribute('title', option.title);
            optionNode.querySelector('[data-product-variant-choice-btn]').setAttribute('title', option.title);
            if (variant.type == 'color' || variant.type == 'image') {
                optionNode.querySelector('[data-product-variant-choice]').classList.add('choice-img');
                if (variant.type == 'color') {
                    optionNode.querySelector('[data-product-variant-choice-btn]').style.backgroundColor = option.value;
                } else if (variant.type == 'image') {
                    optionNode.querySelector('[data-product-variant-choice-btn]').appendChild(createNode(`<img src="${option.value}" />`));
                }
            } else {
                optionNode.querySelector('[data-product-variant-choice-btn]').innerHTML = option.title;
            }
            optionNode.querySelector('[data-product-variant-choice-btn]').dataset.tippyContent = option.title;
            // Change Product to lowest variant
            if (lowestPriceOptions.includes(option.id)) {
                optionNode.querySelector('[data-product-variant-choice-input]').checked = true;
                variantNode.querySelector('[data-product-variant-header-value]').innerHTML = option.title;
            }
            variantNode.querySelector('[data-product-variant-content]').appendChild(optionNode);
        });
        container.appendChild(variantNode);
        initTooltips();
    });
    // Change Product to first Variant
    refreshVariants();
};

window.initVariants = initVariants;

function refreshVariants() {
    const choices = [];
    const choicesNodes = document.querySelectorAll('[data-product-variant-choice-input]');
    choicesNodes.forEach((choiceNode) => {
        if (choiceNode.checked) {
            const { title, value } = choiceNode;
            choices.push({
                id: Number(value),
                title
            });
        }
    });
    let activePrice = window.prices.filter(price => {
        if (_.isEqual(_.sortBy(price.options), _.sortBy(choices))) {
            return price;
        }
    });
    if (activePrice.length) {
        activePrice = activePrice[0];
        document.querySelectorAll('[data-product-variant-choice]').forEach(elm => {
            activePrice.options.forEach(option => {
                if (option.id == elm.dataset.productVariantChoice) {
                    closest(elm, '[data-product-variant]').querySelector('[data-product-variant-header-value]').innerHTML = option.title;
                }
            });
        });
        document.querySelectorAll('[data-list-price]').forEach(elm => {
            const row = closest(elm, '[data-price-row]');
            const price = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(activePrice.originalPrice);
            elm.dataset.listPrice = activePrice.originalPrice;
            elm.innerHTML = `${price} ${APP.currency}`;
            if (activePrice.originalPrice - activePrice.sellPrice == 0) {
                row.setAttribute('hidden', 'hidden');
            } else {
                row.removeAttribute('hidden');
            }
        });
        document.querySelectorAll('[data-price]').forEach(elm => {
            const price = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(activePrice.sellPrice);
            elm.dataset.price = activePrice.sellPrice;
            elm.innerHTML = `${price} ${APP.currency}`;
        });

        document.querySelectorAll('[data-price-title]').forEach(elm => {
            const labels = (elm.dataset.priceLabels).split(',');
            if (activePrice.originalPrice - activePrice.sellPrice == 0) {
                elm.innerHTML=labels[0];
            }else{
                elm.innerHTML=labels[1];
            }
        });


        document.querySelectorAll('[data-discount]').forEach(elm => {
            const row = closest(elm, '[data-price-row]');
            const discount = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(activePrice.originalPrice - activePrice.sellPrice);
            elm.dataset.discount = activePrice.originalPrice - activePrice.sellPrice;
            elm.dataset.discountPercent = activePrice.promotionalPercentage;
            elm.innerHTML = `${discount} ${APP.currency} (${activePrice.promotionalPercentage}%)`;
            if (activePrice.originalPrice - activePrice.sellPrice == 0) {
                row.setAttribute('hidden', 'hidden');
            } else {
                row.removeAttribute('hidden');
            }
        });
        document.querySelectorAll('[data-qty]').forEach(elm => {
            let qty = 1;
            if (activePrice.stock == 0) {
                qty = 0;
                document.querySelectorAll('[data-qty-dropdown-btn]').forEach(el => el.setAttribute('disabled', 'disabled'));
                document.querySelectorAll('[data-out-of-stock-notify-checkbox-container]').forEach(el => el.removeAttribute('hidden'));
            } else {
                document.querySelectorAll('[data-qty-dropdown-btn]').forEach(el => el.removeAttribute('disabled'));
                document.querySelectorAll('[data-out-of-stock-notify-checkbox-container]').forEach(el => el.setAttribute('hidden', 'hidden'));
            }
            elm.innerHTML = qty;
        });
        document.querySelectorAll('[data-qty-menu]').forEach(menu => {
            let qty = 1;
            let maxQty = 10;
            const dropdownItemTemplate = `<button class="l-dropdown-menu-item ui-dropdown-menu-item" data-qty-dropdown-item-value="true" data-qty-dropdown-item="true"></button>`;
            if (activePrice.stock == 0) {
                qty = 0;
                maxQty = 0;
            } else {
                if (activePrice.stock < 10) {
                    maxQty = activePrice.stock;
                }
            }
            menu.innerHTML = '';
            for (var i = qty; i <= maxQty; i++) {
                let dropdownItemNode = createNode(dropdownItemTemplate);
                dropdownItemNode.querySelector('[data-qty-dropdown-item]').dataset.qtyDropdownItemValue = i;
                dropdownItemNode.querySelector('[data-qty-dropdown-item]').innerHTML = i;
                if (i == qty) {
                    dropdownItemNode.querySelector('[data-qty-dropdown-item]').classList.add('active');
                }
                menu.appendChild(dropdownItemNode);
            }
        });
        document.querySelectorAll('[data-header-cart-sidebar-add-product-btn]').forEach(elm => {
            let id = activePrice.id;
            elm.dataset.headerCartSidebarAddProductBtn = id;
        });
        document.querySelectorAll('[data-header-cart-sidebar-add-product-btn-qty]').forEach(elm => {
            const labels = (elm.dataset.cartBtnLabels).split(',');
            let qty = 1;
            elm.dataset.headerCartSidebarAddProductBtnQty = qty;
            if (activePrice.stock == 0) {
                elm.setAttribute('disabled', 'disabled');
                elm.classList.add('out-of-stock');
                elm.innerHTML = labels[1];
            } else {
                elm.removeAttribute('disabled');
                elm.classList.remove('out-of-stock');
                elm.innerHTML = labels[0];
            }
        });
    } else {
        new LayoutToast(__('Error') + ', ' + __('Price not found') , 'error');
        document.querySelectorAll('[data-qty]').forEach(elm => {
            document.querySelectorAll('[data-qty-dropdown-btn]').forEach(el => el.setAttribute('disabled', 'disabled'));
            document.querySelectorAll('[data-out-of-stock-notify-checkbox-container]').forEach(el => el.removeAttribute('hidden'));
        });
        document.querySelectorAll('[data-header-cart-sidebar-add-product-btn-qty]').forEach(elm => {
            elm.setAttribute('disabled', 'disabled');
            elm.classList.add('out-of-stock');
        });
    }
};

window.refreshVariants = refreshVariants;

window.addEventListener('DOMContentLoaded', (e) => {
    addLiveEventListener('change', '[data-product-variant-choice-input]', (e) => {
        refreshVariants();
    });
});
addLiveEventListener('click', '[data-toggle-product-wishlist]', e => {
    if (window.APP.isLoggedIn) {

        const node = typeof e.target.dataset.toggleProductWishlist != 'undefined' ? e.target : closest(e.target, '[data-toggle-product-wishlist]');
        const url = node.dataset.toggleProductWishlistUrl;

        if (!node.classList.contains("ui-product-slide-header-action--active")) {
            node._tippy.setContent(__('Remove from Wishlist'));
            node.classList.add('ui-product-slide-header-action--active');
        } else {
            node._tippy.setContent(__('Add to Wishlist'));
            node.classList.remove('ui-product-slide-header-action--active');
        }

        axios.post(url).then((response) => {
            if (!response.data.error) {
                if (response.data.isFavorite) {
                    node._tippy.setContent(__('Remove from Wishlist'));
                    node.classList.add('ui-product-slide-header-action--active');
                } else {
                    node._tippy.setContent(__('Add to Wishlist'));
                    node.classList.remove('ui-product-slide-header-action--active');
                }
                new LayoutToast(response.data.message, 'success');
            } else {
                new LayoutToast(response.data.message, 'error');
                window.APP_DOM.modals.accountModal.show();
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
        });
    } else {
        new LayoutToast(__('Please login first') + '.', 'error');
        setTimeout(() => {
            window.APP_DOM.modals.accountModal.show();
        }, 50);
    }
});