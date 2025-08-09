// Variables
import LayoutTooltip from "./l-tooltip";

let tooltips = {};
window.APP_DOM.tooltips = {instance: {}, methods: {}};

// Functions
export const initTooltips = () => {
    if (window.matchMedia('(min-width: 768px)').matches) {
        if (typeof tooltips.destroy !== 'undefined') {
            tooltips.destroy();
        }
        tooltips = new LayoutTooltip('[data-tooltip]');
        window.APP_DOM.tooltips = tooltips;
    }
}