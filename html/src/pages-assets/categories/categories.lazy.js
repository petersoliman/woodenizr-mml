import '../../_components/main/header/header.lazy';
import '../../_components/ui/products/product-listeners';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import inView from "in-view-modern";
import {selector} from "../../_components/helpers/dom";
import axios from "axios";
import LayoutToast from "../../_components/layout/toasts/l-toast";

document.querySelectorAll('[data-l-lazy-section]').forEach(section => {
    inView(selector(section))
        .once('enter', section => {
            const url = section.dataset.lLazySectionUrl;
            axios.get(url).then((response) => {
                section.innerHTML = response.data;
                section.classList.remove('loading');
                initTooltips();

            }).catch((error) => {
                new LayoutToast(error.message, 'error');
            });
        });
});
