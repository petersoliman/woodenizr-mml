import { Fancybox, Carousel, Panzoom, Image } from "@fancyapps/ui";


export default class LayoutGallery {

    constructor(selector) {
        this.selector = selector;
        this.name = document.querySelector(selector).dataset.lGallery;
        this.gallery = {};
    }

    bindEvents() {
        const thumbsSelector = `${this.selector} [data-l-gallery-thumb="${this.name}"]`;
        const fullImagesSelector = `${this.selector} [data-l-gallery-lightbox="${this.name}"]`;
        const thumbs = document.querySelectorAll(thumbsSelector);
        const sliderSelector = `${this.selector} [data-l-gallery-slider="${this.name}"]`;
        const slider = document.querySelector(sliderSelector);
        if (document.querySelectorAll(fullImagesSelector).length) {
            this.gallery = Fancybox.bind(fullImagesSelector, {
                on: {
                    "Carousel.change": (fancybox) => {
                        const id = fancybox.getSlide().index;
                        thumbs.forEach((t) => t.classList.remove('active'));
                        const thumb = thumbs[id];
                        if (thumb) {
                            thumb.classList.add('active');
                        }
                        if (slider && typeof slider.lSlider != 'undefined') {
                            slider.lSlider.slideTo(id);
                        }
                    }
                }
            });
        }
        window.addEventListener('load', function () {
            if (slider && typeof slider.lSlider != 'undefined') {
                slider.lSlider.addEventListener('slideChange', function () {
                    thumbs.forEach((t) => t.classList.remove('active'));
                    const thumb = thumbs[slider.lSlider.activeIndex];
                    if (thumb) {
                        thumb.classList.add('active');
                    }
                });
            }
        });
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                if (typeof slider.lSlider != 'undefined') {
                    const id = thumb.dataset.lGalleryThumbId;
                    slider.lSlider.slideTo(id);
                }
            });
        });
    }

}