import { addLiveEventListener, closest, createNode } from "../../helpers/dom";

export default class LayoutModal {
    onHide = () => {};
    onShow = () => {};
    constructor(modal, backdrops = ['body', 'footer']) {
        this.modal = modal;
        this.backdropNode = {};
        this.backdrops = backdrops;
        this.name = modal.dataset.lModal;
        return this;
    }
    bindEvents() {
        addLiveEventListener('click', `[data-l-modal-btn="${this.name}"]`, e => {
            if (this.modal.classList.contains('show-modal')) {
                this.hide(e);
            } else {
                this.show(e);
            }
        });
        if (typeof window.modalEventListener === 'undefined') {
            window.modalEventListener = true;
            window.addEventListener('click', (e) => {
                let anyModalsShown = false;
                document.querySelectorAll('[data-l-modal]').forEach(modal => {
                    if (modal.classList.contains('show-modal')) {
                        anyModalsShown = true;
                    }
                });
                if (anyModalsShown) {
                    const btn = e.target.dataset.lModalBtn ? e.target : closest(e.target, '[data-l-modal-btn]');
                    const modal = e.target.dataset.lModal ? e.target : closest(e.target, '[data-l-modal]');

                    if (!btn && !modal) {
                        this.hideAllModals();
                    }
                }
            });
        }
    }
    hide(e) {
        this.hideModal(this.modal);
        document.body.classList.remove("l-modal-open");
        this.backdropNode.remove();
        this.onHide(e);
    }
    show(e) {
        document.body.classList.add('l-modal-open');
        this.modal.classList.add('show-modal');
        setTimeout(() => {
            let backdropClass = 'l-modal-backdrop--';
            this.backdrops.forEach((backdropName, i) => {
                backdropClass += backdropName;
                if (i < this.backdrops.length - 1) {
                    backdropClass += '-';
                }
            });
            document.body.appendChild(createNode(`<div class="l-modal-backdrop ui-modal-backdrop ${backdropClass} show"></div>`));
            this.backdropNode = document.body.lastChild;
            this.modal.addEventListener('click', this.hideFromBackdrop.bind(this));
        }, 5);
        this.onShow(e);
    }
    hideFromBackdrop(e) {
        if (e.target === this.modal) {
            this.hide(e);
        }
    }
    hideAllModals() {
        document.querySelectorAll('[data-l-modal]').forEach(modal => {
            this.hideModal(modal);
        });
    }
    hideModal(modal){
        if (this.backdropNode && this.backdropNode.remove) {
            this.backdropNode.remove();
        }
        modal.classList.remove('show-modal');
    }
}