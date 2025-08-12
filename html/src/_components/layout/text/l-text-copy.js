import LayoutToast from "../toasts/l-toast";

export default class LayoutTextCopy {
    constructor(el) {
        el.addEventListener('click', () => {
            el.select();
            document.execCommand('copy');
            setTimeout(() => {
                new LayoutToast(__('Copied') + '!', 'success');
            }, 5);
        });
    }
}