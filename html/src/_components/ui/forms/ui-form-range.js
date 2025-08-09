import noUiSlider from 'nouislider';
import {debounce} from "../../helpers/requests";

export default class UiFormRange {

    constructor(el) {
        this.name = el.dataset.uiFormRange;
        this.el = el;
        this.options = el.dataset.uiFormRangeOptions ? JSON.parse(el.dataset.uiFormRangeOptions) : {min: 0, max: 50000};
        this.minInput = document.querySelector(`[data-ui-form-range-min="${this.name}"]`);
        this.maxInput = document.querySelector(`[data-ui-form-range-max="${this.name}"]`);
    }

    init() {
        this.slider = noUiSlider.create(this.el, {
            start: [
                Number(this.minInput.value ? this.minInput.value : Number(this.options.min)),
                Number(this.maxInput.value ? this.maxInput.value : Number(this.options.max))
            ],
            step: 1,
            format: {
                to: function(value) {
                    return Math.round(value);
                },
                from: function (value) {
                    return Math.round(value);
                }
            },
            range: {
              'min': [Number(this.options.min)],
              'max': [Number(this.options.max)]
            },
            connect: true
        });
        return this;
    }

    bindEvents() {
        this.slider.on('change', (values, handle) => {
            this.minInput.value = values[0];
            this.minInput.dispatchEvent(new Event('change'));
            this.maxInput.value = values[1];
            this.maxInput.dispatchEvent(new Event('change'));
        });
        [this.minInput, this.maxInput].forEach(input => {
            input.addEventListener('input',  debounce(() => {
                this.slider.set([this.minInput.value,this.maxInput.value]);
            }, 200));
        })
    }

    destroy() {
        this.slider.destroy();
    }

}