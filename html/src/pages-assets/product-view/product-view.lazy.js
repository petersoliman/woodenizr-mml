import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import LayoutSlider from '../../_components/layout/sliders/l-slider';
import LayoutGallery from '../../_components/layout/gallery/l-gallery';
import inView from 'in-view-modern';
import {addLiveEventListener, closest, selector} from '../../_components/helpers/dom';
import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import UiFeaturedProductsSection from '../../_components/ui/sections/ui-featured-products-section';
import LayoutDropdown from '../../_components/layout/dropdowns/l-dropdown';
import LayoutTabs from '../../_components/layout/tabs/l-tabs';
import CloudImage360 from '../../_components/utilities/js-cloudimage-360-view/js-cloudimage-360-view.min';

initTooltips();


const threeSixtyViewContainer = document.getElementById('three-sixty-view-container');
if (threeSixtyViewContainer) {
    const cloudimage360 = new CloudImage360();
    const config = {
        folder: threeSixtyViewContainer.dataset.folder,
        filenameX: threeSixtyViewContainer.dataset.filenameX,
        amountX: threeSixtyViewContainer.dataset.amountX,
        // autoplay: true,
        initialIconShown: true,
        fullscreen: true,
        // initialIcon: true,
        // pointerZoom: 1.5,
        responsive: 'scaleflex',
    };
    cloudimage360.init(threeSixtyViewContainer, config);
}

if (document.querySelector('[data-l-gallery="product-imgs"]')) {
    (new LayoutGallery('[data-l-gallery="product-imgs"]')).bindEvents();
}

if (document.querySelector('[data-l-slider="product-imgs-slider"]')) {
    new LayoutSlider('[data-l-slider="product-imgs-slider"]', {
        slidesPerView: 1,
        spaceBetween: 0,
        autoplay: false,
        // autoplay: {
        //     pauseOnMouseEnter: true,
        //     disableOnInteraction: true
        // },
        allowTouchMove: false,
        pagination: false,
        navigation: false
    });
}

if (document.querySelector('[data-shipping-fees-dropdown="true"]')) {
    new LayoutDropdown(document.querySelector('[data-shipping-fees-dropdown="true"]')).bindEvents();
    const feesCountriesBtns = document.querySelectorAll('[data-shipping-fees-dropdown-item]');
    const feesBtn = document.querySelector('[data-shipping-fees-dropdown-button]');
    const feesText = document.querySelector('[data-shipping-fees-dropdown-text]');
    const url = feesBtn.dataset.shippingFeesDropdownButtonUrl;
    const fetchShippingFees = (zoneId) => {
        let bodyFormData = new FormData();
        bodyFormData.append('zoneId', zoneId);
        feesText.classList.add('loading');
        axios.post(url, bodyFormData).then(response => {
            if (!response.data.error) {
                feesText.innerText = response.data.price;
                feesText.classList.remove('loading');
            } else {
                new LayoutToast(response.data.message, 'error');
                feesText.classList.remove('loading');
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
            feesText.classList.remove('loading');
        });
    };
    fetchShippingFees(feesCountriesBtns[0].dataset.shippingFeesDropdownItemZoneId);
    feesCountriesBtns.forEach(feeCountryBtn => {
        feeCountryBtn.addEventListener('click', () => {
            const zoneId = feeCountryBtn.dataset.shippingFeesDropdownItemZoneId;
            feesCountriesBtns.forEach(c => c.classList.remove('active'));
            feeCountryBtn.classList.add('active');
            feesBtn.innerText = feeCountryBtn.innerText;
            fetchShippingFees(zoneId);
        });
    })
}

if (document.querySelector('[data-qty-dropdown-button="true"]')) {
    document.querySelectorAll('[data-qty-dropdown="true"]').forEach(el => {

        new LayoutDropdown(document.querySelector(selector(el))).bindEvents();
    })
    addLiveEventListener('click', '[data-qty-dropdown-item]', (e) => {
        const qtyItemBtns = document.querySelectorAll('[data-qty-dropdown-item]');
        const qtyText = document.querySelector('[data-qty-dropdown-text]');
        const btn = typeof e.target.dataset.qtyDropdownItem != 'undefined' ? e.target : closest(e.target, '[data-qty-dropdown-item]');
        qtyItemBtns.forEach(c => c.classList.remove('active'));
        qtyText.innerText = btn.dataset.qtyDropdownItemValue;

        document.querySelectorAll('[data-qty-dropdown-cart-btn]').forEach(cartBtn => {
            cartBtn.dataset.headerCartSidebarAddProductBtnQty = btn.dataset.qtyDropdownItemValue;
        });
        btn.classList.add('active');
    });
}

if (document.querySelector('[data-product-view-tabs="true"]')) {
    const tab = document.querySelector('[data-tab-reviews="true"]')
    if (tab) {
        const loadMoreBtnContainer = tab.querySelector('[data-tab-reviews-load-more-btn-container="true"]');
        const loadMoreBtn = tab.querySelector('[data-tab-reviews-load-more-btn="true"]');
        const container = tab.querySelector('[data-tab-reviews-container="true"]');
        const overall = tab.querySelector('[data-tab-reviews-overall="true"]');
        const list = tab.querySelector('[data-tab-reviews-list="true"]');
        const url = container.dataset.tabReviewsUrl;
        const loadReviews = (page) => {
            return new Promise((res, rej) => {
                axios.get(url, {
                    params: {
                        page
                    }
                }).then(response => {
                    if (!response.data.error) {
                        tab.classList.remove('loading');
                        if (overall.innerHTML == '' && page == 1) {
                            overall.innerHTML = response.data.overallRatingHTML;
                        }
                        list.innerHTML += response.data.reviewsHTML;
                        if (response.data.currentPageNumber == response.data.numberOfPages) {
                            loadMoreBtnContainer.style.display = 'none';
                        } else {
                            loadMoreBtnContainer.removeAttribute('style');
                        }
                        res();
                    } else {
                        new LayoutToast(response.data.message, 'error');
                        rej();
                    }
                }).catch((error) => {
                    new LayoutToast(error.message, 'error');
                    rej();
                });
            })
        };
        loadMoreBtn.addEventListener('click', () => {
            loadMoreBtn.classList.add('loading');
            reviewsCurrentPage += 1;
            loadReviews(reviewsCurrentPage).then(() => {
                loadMoreBtn.classList.remove('loading');
            });
        });
    }
    const loadSpecs = () => {
        return new Promise((res, rej) => {
            const tab = document.querySelector('[data-tab-specs="true"]')
            const container = tab.querySelector('[data-tab-specs-container="true"]');
            // const container = document.querySelector('[data-tab-specs-container="true"]'); // Todo: Peter edit

            const url = container.dataset.tabSpecsUrl;

            axios.get(url).then(response => {
                tab.classList.remove('loading'); // Todo: Peter edit
                if (container.innerHTML == '') {
                    container.innerHTML = response.data;
                }
                res();
            }).catch((error) => {
                new LayoutToast(error.message, 'error');
                rej();
            });
        })
    };
    const loadDesc = () => {
        return new Promise((res, rej) => {
            const tab = document.querySelector('[data-tab-desc="true"]')
            const container = tab.querySelector('[data-tab-desc-container="true"]');
            const url = container.dataset.tabDescUrl;
            axios.get(url).then(response => {
                tab.classList.remove('loading');
                if (container.innerHTML == '') {
                    container.innerHTML = response.data;
                }
                res();
            }).catch((error) => {
                new LayoutToast(error.message, 'error');
                rej();
            });
        })
    };


    if (document.querySelector('[data-product-open-reviews-tab="true"]')) {
        document.querySelectorAll('[data-product-open-reviews-tab="true"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('[data-l-tab-btn="reviews"]').dispatchEvent(new Event('click'));
                if (document.querySelector('[data-similar-products-section]')) {

                    document.querySelector('[data-similar-products-section]').scrollIntoView({
                        behavior: 'smooth'
                    });
                    setTimeout(() => {
                        document.querySelector('[data-l-tab-btn="reviews"]').scrollIntoView({
                            behavior: 'smooth'
                        });
                    }, 1500);
                } else {
                    document.querySelector('[data-l-tab-btn="reviews"]').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    let reviewsTabLoaded = false;
    let specsTabLoaded = false;
    let descTabLoaded = false;
    let reviewsCurrentPage = 1;
    const tabs = new LayoutTabs(document.querySelector('[data-product-view-tabs="true"]'));
    tabs.bindEvents();
    tabs.onChange = (tab, btn) => {
        if (tab.dataset.lTab == 'reviews' && !reviewsTabLoaded) {
            reviewsTabLoaded = true;
            loadReviews(1).then(() => {
            });
        } else if (tab.dataset.lTab == 'desc' && !descTabLoaded) {
            descTabLoaded = true;
            loadDesc().then(() => {
            });
        } else if (tab.dataset.lTab == 'specs' && !specsTabLoaded) {
            specsTabLoaded = true;
            loadSpecs().then(() => {
            });
        }
    };
    inView('[data-tab-desc-container="true"]')
        .once('enter', tab => {
            loadDesc().then(() => {
            });
        });
    inView('[data-tab-specs-container="true"]')
        .once('enter', tab => {
            loadSpecs().then(() => {
            });
        });
    // loadSpecs().then(() => {});
}

if (document.querySelector('[data-out-of-stock-notify-checkbox="true"]')) {
    const checkbox = document.querySelector('[data-out-of-stock-notify-checkbox="true"]');
    const productPriceId = checkbox.dataset.outOfStockNotifyCheckboxId;
    const url = checkbox.dataset.outOfStockNotifyCheckboxUrl;
    checkbox.addEventListener('change', () => {
        let bodyFormData = new FormData();
        bodyFormData.append('productPriceId', productPriceId);
        axios.post(url, bodyFormData).then(response => {
            if (!response.data.error) {
                if (response.data.message) {
                    new LayoutToast(response.data.message, 'success');
                }
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch((error) => {
            new LayoutToast(error.message, 'error');
        });
    });
}

// Lazy Sections
document.querySelectorAll('[data-l-lazy-section]').forEach(section => {
    inView(selector(section))
        .once('enter', section => {
            const name = section.dataset.lLazySection;
            const type = section.dataset.lLazySectionType;
            const url = section.dataset.lLazySectionUrl;
            axios.get(url).then((response) => {
                switch (type) {
                    case 'simple':
                        break;

                    case 'products':
                        if (Array.isArray(response.data)) {
                            response.data.forEach(s => {
                                section.appendChild(new UiFeaturedProductsSection(name, s.title, s.products));
                            })
                        } else {
                            section.appendChild(new UiFeaturedProductsSection(name, response.data.title, response.data.products));
                        }
                        section.classList.remove('loading');

                        switch (name) {
                            case 'related-products':
                            case 'similar-products':
                                if (document.querySelectorAll(`[data-l-slider="${name}"]`)) {
                                    document.querySelectorAll(`[data-l-slider="${name}"]`).forEach(section => {
                                        new LayoutSlider(selector(section), {
                                            slidesPerView: 6,
                                            spaceBetween: 20,
                                            autoplay: {
                                                pauseOnMouseEnter: true,
                                                disableOnInteraction: true
                                            },
                                            pagination: false,
                                            navigation: {
                                                nextEl: `[data-l-slider-navigation-next="${name}"]`,
                                                prevEl: `[data-l-slider-navigation-prev="${name}"]`,
                                            },
                                            // Responsive breakpoints
                                            breakpoints: {
                                                // when window width is >= 120px
                                                120: {
                                                    slidesPerView: 1.5,
                                                    spaceBetween: 15,
                                                },
                                                // when window width is >= 450px
                                                400: {
                                                    slidesPerView: 2,
                                                },
                                                // when window width is >= 500px
                                                500: {
                                                    slidesPerView: 2.5,
                                                    spaceBetween: 10,
                                                },
                                                // when window width is >= 840px
                                                840: {
                                                    slidesPerView: 3.5,
                                                    spaceBetween: 10,
                                                },
                                                // when window width is >= 1100px
                                                1100: {
                                                    slidesPerView: 4,
                                                },
                                                // when window width is >= 1350px
                                                1350: {
                                                    slidesPerView: 6,
                                                    spaceBetween: 15,
                                                },
                                                // when window width is >= 1400px
                                                1400: {
                                                    slidesPerView: 6,
                                                },
                                                // when window width is >= 1600px
                                                1600: {
                                                    spaceBetween: 20,
                                                }
                                            },
                                        });
                                    });
                                }
                                break;

                            default:
                                break;
                        }
                        break;

                    default:
                        break;
                }
                initTooltips();

            }).catch((error) => {
                new LayoutToast(error.message, 'error');
            });
        });
});

const loadVariants = () => {
    return new Promise((res, rej) => {
        const container = document.querySelector('[data-product-variants-container]');
        const id = container.dataset.productVariantsId;
        window.variants = [];
        window.prices = [];
        axios.get(`${APP.productUrls.variants}?id=${id}`).then(response => {
            if (!response.data.error) {
                if (response.data.variants && response.data.prices) {
                    window.initVariants(response.data.variants, response.data.prices)
                }
                if (response.data.prices.length > 0 || window.matchMedia('(max-width: 1200px)').matches) {
                    if (response.data.prices.length > 0) {
                        document.querySelector('[data-meta-actions-col]').setAttribute('style', "display: block !important");
                    }
                    document.querySelector('[data-l-product-add-to-cart-multi-prices]').setAttribute('style', "display: flex !important;");
                    document.querySelector('[data-l-product-add-to-cart-single-price]').setAttribute('style', "display: none !important;");
                } else {
                    if (response.data.prices.length === 1) {
                        // document.querySelector('[data-meta-actions-col]').setAttribute('style',"display: none !important;");
                    }
                    document.querySelector('[data-l-product-add-to-cart-multi-prices]').setAttribute('style', "display: none !important;");
                    document.querySelector('[data-l-product-add-to-cart-single-price]').setAttribute('style', 'display: flex !important;');
                }
                res();
            } else {
                new LayoutToast(response.data.message, 'error');
                rej();
            }
        }).catch((error) => {
            new LayoutToast(error || error.message, 'error');
            rej();
        });
    })
};

window.addEventListener('DOMContentLoaded', (e) => {
    loadVariants().then(() => {
    });
});