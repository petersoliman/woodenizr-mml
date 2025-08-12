import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormPassword from '../../_components/ui/forms/ui-form-password';
import LayoutSlider from '../../_components/layout/sliders/l-slider';
import LayoutGallery from '../../_components/layout/gallery/l-gallery';
import inView from 'in-view-modern';
import axios from 'axios';
import LayoutToast from '../../_components/layout/toasts/l-toast';
import { selector } from '../../_components/helpers/dom';
import UiFeaturedProductsSection from '../../_components/ui/sections/ui-featured-products-section';

initTooltips();


let signPassword = document.querySelector('[data-ui-form-password="sign-password"]');
if (signPassword) {
    signPassword = new UiFormPassword(signPassword);
    signPassword.bindEvents();
}

const signForm = document.querySelector('[data-sign-form]');
if (signForm) {
    const signFormFields = signForm.querySelectorAll('[data-validate-input]');

    signFormFields.forEach(field => {
        field.addEventListener('change', () => {
            let errors = [];
            signFormFields.forEach(field => {
                switch (field.type) {
                    case 'checkbox':
                        if (!field.checked || field.__errors.length > 0) {
                            errors.push(field.name);
                        }
                        break;
                
                    default:
                        if (!field.value || field.__errors.length > 0) {
                            errors.push(field.name);
                        }
                        break;
                }
            });
            if (errors.length == 0) {
                signForm.querySelector('button[type="submit"]').removeAttribute('disabled');
            } else {
                signForm.querySelector('button[type="submit"]').setAttribute('disabled', 'disabled');
            }
        });
    });
}

window.addEventListener('DOMContentLoaded', (e) => {
    const sliders = document.querySelectorAll('[data-l-slider-wrapper]');
    if (sliders.length) {
        sliders.forEach(sliderWrapperNode => {
            const sliderNode = sliderWrapperNode.querySelector('[data-l-slider]');
            const sliderThumbsNode = sliderWrapperNode.querySelector('[data-l-slider-thumbs]');
            const thumbsObject = new LayoutSlider(sliderThumbsNode, {
                spaceBetween: 7,
                slidesPerView: 30,
                slideActiveClass: 'active',
                watchSlidesProgress: true
            });
            const sliderObject = new LayoutSlider(sliderNode, {
                thumbs: {
                    slideThumbActiveClass: 'active',
                    swiper: thumbsObject.slider,
                }
            });
        });
        if (document.querySelector('[data-l-gallery="imgs"]')) {
            (new LayoutGallery('[data-l-gallery="imgs"]')).bindEvents();
        }
    }
});

// Lazy Sections
document.querySelectorAll('[data-l-lazy-section]').forEach(section => {
    inView(selector(section))
        .once('enter', section => {
            const name = section.dataset.lLazySection;
            const type = section.dataset.lLazySectionType;
            const url = section.dataset.lLazySectionUrl;
            axios.get(url).then((response) => {
                switch (type) {

                    case 'products':
                        if (Array.isArray(response.data)) {
                            response.data.forEach(s => {
                                section.appendChild(new UiFeaturedProductsSection(name, s.title, s.products));
                            })
                        } else {
                            section.appendChild(new UiFeaturedProductsSection(name, response.data.title, response.data.products));
                        }
                        if(response.data.products.length > 0){
                            document.querySelector("[data-project-single-separator]").style.display = 'block';
                        }
                        section.classList.remove('loading');

                        switch (name) {
                            case 'featured-categories-products':
                                if (document.querySelector(`[data-l-slider="${name}"]`)) {
                                    document.querySelectorAll(`[data-l-slider="${name}"]`).forEach(section => {
                                        new LayoutSlider(selector(section), {
                                            slidesPerView: 5,
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
                                                    slidesPerView: 5,
                                                    spaceBetween: 15,
                                                },
                                                // when window width is >= 1400px
                                                1400: {
                                                    slidesPerView: 5,
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
