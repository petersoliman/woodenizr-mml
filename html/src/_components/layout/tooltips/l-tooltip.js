import tippy from 'tippy.js';

export default class LayoutTooltip {
    constructor(el) {
        this.el = el;
        this.init();
    }

    init() {
        tippy(this.el, {
            onCreate: (instance) => {
                instance.reference.removeAttribute('title')
                this.tooltip = instance;
            }
        });
    }
    
    destroy() {
        if (typeof this.tooltip !== 'undefined') {
            this.tooltip.destroy();
        }
    }

    refresh() {
        this.destroy();
        this.init();
    }
}