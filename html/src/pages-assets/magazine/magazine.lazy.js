import '../../_components/main/header/header.lazy';
import {initTooltips} from '../../_components/layout/tooltips/init-l-tooltip';
import axios from 'axios';
import inView from 'in-view-modern';
import {selector} from '../../_components/helpers/dom';
import LayoutToast from '../../_components/layout/toasts/l-toast';


// Lazy Sections
document.querySelectorAll('[data-l-lazy-section]').forEach(section => {
    inView(selector(section))
        .once('enter', section => {
            const type = section.dataset.lLazySectionType;
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