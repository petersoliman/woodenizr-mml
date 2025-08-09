import Swiper, { Navigation, Pagination, Autoplay, Lazy, Thumbs } from 'swiper';

export default class LayoutSlider {
    slider = {};
    activeIndex = {};
    constructor(selector, otherOptions) {
        let el = selector;
        const slider = new Swiper(selector, {
          modules: [Navigation, Pagination, Autoplay, Lazy, Thumbs],
          ...otherOptions
        });
        this.slider = slider;
        if (typeof el == 'string') {
          el = document.querySelector(selector);
        }
        el.lSlider = this;
        if (typeof slider.on != 'undefined') {
          slider.on('slideChange', () => {
            this.activeIndex = this.slider.activeIndex;
          });
        }
    }

    slideTo(index, speed = 300, runCallbacks = true)	{
      this.slider.slideTo(index, speed, runCallbacks);
    }

    addEventListener(ev, fn) {
      this.slider.on(ev, fn);
    }
}