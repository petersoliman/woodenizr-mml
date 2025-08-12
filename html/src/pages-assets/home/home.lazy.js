import './../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import LayoutSlider from '../../_components/layout/sliders/l-slider';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormRate from '../../_components/ui/forms/ui-form-rate';
import axios from 'axios';
import inView from 'in-view-modern';
import {selector} from '../../_components/helpers/dom';
import UiFeaturedProductsSection from '../../_components/ui/sections/ui-featured-products-section';
import LayoutToast from '../../_components/layout/toasts/l-toast';

initTooltips();

// Sections
if (document.querySelector('[data-l-slider="featured-floating-banners"]')) {
    new LayoutSlider('[data-l-slider="featured-floating-banners"]', {
        slidesPerView: 4,
        spaceBetween: 20,
        autoplay: {
            pauseOnMouseEnter: true,
            disableOnInteraction: true
        },
        pagination: {
            el: '[data-l-slider-pagination="featured-floating-banners"]',
            clickable: true
        },
        navigation: {
            nextEl: '[data-l-slider-navigation-next="featured-floating-banners"]',
            prevEl: '[data-l-slider-navigation-prev="featured-floating-banners"]',
        },
        // Responsive breakpoints
        breakpoints: {
            // when window width is >= 320px
            320: {
                slidesPerView: 1.5,
                spaceBetween: 15,
            },
            // when window width is >= 480px
            480: {
                slidesPerView: 2.25,
                spaceBetween: 15,
            },
            // when window width is >= 640px
            640: {
                slidesPerView: 2,
            },
            // when window width is >= 992px
            992: {
                slidesPerView: 3,
            },
            1200: {
                slidesPerView: 4,
            }
        },
    });
}

if (document.querySelector('[data-l-slider="on-sale-products"]')) {
    new LayoutSlider('[data-l-slider="on-sale-products"]', {
        slidesPerView: 5,
        spaceBetween: 20,
        autoplay: {
            pauseOnMouseEnter: true,
            disableOnInteraction: true
        },
        pagination: false,
        navigation: {
            nextEl: '[data-l-slider-navigation-next="on-sale-products"]',
            prevEl: '[data-l-slider-navigation-prev="on-sale-products"]',
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
}

// Lazy Sections
inView('[data-l-lazy-section]')
    .on('enter', section => {
        if (section.dataset.lLazySection && typeof section.dataset.lLazySectionLoaded == 'undefined') {
            const name = section.dataset.lLazySection;
            const type = section.dataset.lLazySectionType;
            const url = section.dataset.lLazySectionUrl;
            axios.get(url).then((response) => {
                section.dataset.lLazySectionLoaded = "true";
                switch (type) {
                    case 'simple':
                        section.innerHTML = response.data;
                        section.classList.remove('loading');
                        switch (name) {
                            case 'featured-reviews':
                                if (document.querySelector('[data-l-slider="featured-reviews-section-last-order"]')) {
                                    new LayoutSlider('[data-l-slider="featured-reviews-section-last-order"]', {
                                        slidesPerView: 2.05,
                                        spaceBetween: 20,
                                        autoplay: {
                                            pauseOnMouseEnter: true,
                                            disableOnInteraction: true
                                        },
                                        pagination: {
                                            el: '[data-l-slider-pagination="featured-reviews-section-last-order"]',
                                            clickable: true
                                        },
                                        navigation: {
                                            nextEl: '[data-l-slider-navigation-next="featured-reviews-section-last-order"]',
                                            prevEl: '[data-l-slider-navigation-prev="featured-reviews-section-last-order"]',
                                        },
                                        // Responsive breakpoints
                                        breakpoints: {
                                            // when window width is >= 320px
                                            320: {
                                                slidesPerView: 1.5,
                                            },
                                            // when window width is >= 480px
                                            480: {
                                                slidesPerView: 1.5,
                                            },
                                            // when window width is >= 640px
                                            640: {
                                                slidesPerView: 2.05,
                                            }
                                        }
                                    });
                                }
                                if (document.querySelector('[data-ui-form-rate="true"]')) {
                                    new UiFormRate(document.querySelector('[data-ui-form-rate="true"]')).bindEvents();
                                }
                                break;

                            case 'featured-brands':
                                if (document.querySelector('[data-l-slider="featured-brands"]')) {
                                    new LayoutSlider('[data-l-slider="featured-brands"]', {
                                        slidesPerView: 5.8,
                                        centeredSlides: true,
                                        spaceBetween: 20,
                                        initialSlide: 2,
                                        autoplay: {
                                            delay: 800,
                                            pauseOnMouseEnter: true,
                                            disableOnInteraction: true
                                        },
                                        loop: true,
                                        pagination: false,
                                        lazy: {
                                            enabled: true,
                                            loadPrevNext: true,
                                        },
                                        navigation: {
                                            nextEl: '[data-l-slider-navigation-next="featured-brands"]',
                                            prevEl: '[data-l-slider-navigation-prev="featured-brands"]',
                                        },
                                        // Responsive breakpoints
                                        breakpoints: {
                                            // when window width is >= 320px
                                            320: {
                                                slidesPerView: 3.25,
                                                spaceBetween: 8,
                                            },
                                            // when window width is >= 480px
                                            480: {
                                                slidesPerView: 3.25,
                                                spaceBetween: 8,
                                            },
                                            // when window width is >= 640px
                                            640: {
                                                slidesPerView: 3.8,
                                                spaceBetween: 20,
                                            },
                                            992: {
                                                slidesPerView: 4.8,
                                                spaceBetween: 20,
                                            },
                                            1400: {
                                                slidesPerView: 5.8,
                                                spaceBetween: 20,
                                            }
                                        }
                                    });
                                }
                                break;

                            case 'featured-collections':
                                if (document.querySelector('[data-l-slider="featured-collections"]')) {
                                    new LayoutSlider('[data-l-slider="featured-collections"]', {
                                        slidesPerView: 1.2,
                                        centeredSlides: true,
                                        spaceBetween: 14,
                                        initialSlide: 2,
                                        autoplay: {
                                            pauseOnMouseEnter: true,
                                            disableOnInteraction: true
                                        },
                                        loop: true,
                                        pagination: {
                                            el: '[data-l-slider-pagination="featured-collections"]',
                                            clickable: true
                                        },
                                        lazy: {
                                            enabled: true,
                                            loadPrevNext: true,
                                        },
                                        navigation: {
                                            nextEl: '[data-l-slider-navigation-next="featured-collections"]',
                                            prevEl: '[data-l-slider-navigation-prev="featured-collections"]',
                                        },
                                        // Responsive breakpoints
                                        breakpoints: {
                                            // when window width is >= 768px
                                            768: {
                                                slidesPerView: 1.3,
                                                spaceBetween: 20,
                                            },
                                            992: {
                                                slidesPerView: 2.5,
                                                spaceBetween: 20,
                                            },
                                            1400: {
                                                slidesPerView: 3,
                                                spaceBetween: 20,
                                            }
                                        }
                                    });
                                }
                                break;

                            default:
                                break;
                        }
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
                            case 'recommended-for-you':
                                if (document.querySelector('[data-l-slider="recommended-for-you"]')) {
                                    new LayoutSlider('[data-l-slider="recommended-for-you"]', {
                                        slidesPerView: 5,
                                        spaceBetween: 20,
                                        autoplay: {
                                            pauseOnMouseEnter: true,
                                            disableOnInteraction: true
                                        },
                                        pagination: false,
                                        navigation: {
                                            nextEl: '[data-l-slider-navigation-next="recommended-for-you"]',
                                            prevEl: '[data-l-slider-navigation-prev="recommended-for-you"]',
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
                                }
                                break;

                            case 'fulfilled-by-justpiece':
                                if (document.querySelector('[data-l-slider="fulfilled-by-justpiece"]')) {
                                    new LayoutSlider('[data-l-slider="fulfilled-by-justpiece"]', {
                                        slidesPerView: 5,
                                        spaceBetween: 20,
                                        autoplay: {
                                            pauseOnMouseEnter: true,
                                            disableOnInteraction: true
                                        },
                                        pagination: false,
                                        navigation: {
                                            nextEl: '[data-l-slider-navigation-next="fulfilled-by-justpiece"]',
                                            prevEl: '[data-l-slider-navigation-prev="fulfilled-by-justpiece"]',
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
                                }
                                break;

                            case 'best-seller':
                            case 'new-arrivals':
                            case 'featured-categories-products':
                                if (document.querySelector(`[data-l-slider="${name}"]`)) {
                                    document.querySelectorAll(`[data-l-slider="${name}"]`).forEach(section => {
                                       const  next = section.parentElement.querySelector(`[data-l-slider-navigation-next="${name}"]`);
                                       const  prev = section.parentElement.querySelector(`[data-l-slider-navigation-prev="${name}"]`);
                                        new LayoutSlider(selector(section), {
                                            slidesPerView: 5,
                                            spaceBetween: 20,
                                            autoplay: {
                                                pauseOnMouseEnter: true,
                                                disableOnInteraction: true
                                            },
                                            pagination: false,
                                            navigation: {
                                                nextEl: next,
                                                prevEl: prev,
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
                console.log(error.message, 'error');
            });
        }
    });
