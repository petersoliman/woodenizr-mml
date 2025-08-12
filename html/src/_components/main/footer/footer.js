import axios from "axios";
import LayoutToast from "../../layout/toasts/l-toast";

window.addEventListener('DOMContentLoaded', (e) => {

    const newsletterBtn = document.querySelector('[data-widget-content-newsletter-btn]');
    const newsletterInput = document.querySelector('[data-widget-content-newsletter-input]');

    const subscribe = () => {
        const email = newsletterInput.value;
        const _token = APP.newsletter._token
        if (/\S+@\S+\.\S+/.test(email)) {
            let bodyFormData = new FormData();
            bodyFormData.append('email', email);
            bodyFormData.append('_token', _token);

            axios.post(APP.newsletter.link, bodyFormData).then((response) => {
                if (!response.data.error) {
                    new LayoutToast(response.data.message + '.', 'success');
                    newsletterInput.value = '';
                } else {
                    new LayoutToast(response.data.message, 'error');
                }
            }).catch((error) => {
                new LayoutToast(error.message, 'error');
            });
        } else {
            new LayoutToast(__('Please type correct email address') + '.', 'error');
        }
    }
    if (newsletterBtn) {
        newsletterBtn.addEventListener('click', () => {
            subscribe();
        });
    }

    if (newsletterInput) {
        newsletterInput.addEventListener('keypress', (e) => {
            if (e.keyCode == 13) {
                subscribe();
            }
        });
    }

});