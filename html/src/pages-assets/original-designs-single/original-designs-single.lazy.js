import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import UiFormPassword from '../../_components/ui/forms/ui-form-password';
import LayoutSlider from '../../_components/layout/sliders/l-slider';

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
                watchSlidesProgress: true
            });
            const sliderObject = new LayoutSlider(sliderNode, {
                thumbs: {
                    swiper: thumbsObject.slider,
                }
            });
        })
    }
});