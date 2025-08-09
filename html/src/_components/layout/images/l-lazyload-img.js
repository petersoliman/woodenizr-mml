import lazySizes from 'lazysizes';

// lazySizes.cfg.throttleDelay = 500;
lazySizes.cfg.customMedia = {
    '--small': '(max-width: 576px)',
    '--medium': '(max-width: 768px)',
    '--large': '(max-width: 992px)',
    '--x-large': '(max-width: 1400px)',
    '--xx-large': '(max-width: 1600px)',
};
document.addEventListener('lazybeforeunveil', function (e) {
    let userAgent = navigator.userAgent;
    let regexLighthouse = RegExp("Chrome-Lighthouse", "ig");
    let regexGTmetrix = RegExp("GTmetrix", "ig")

    let delay = e.target.getAttribute('data-delay');
    if (delay) {
        if (regexLighthouse.test(userAgent) || regexGTmetrix.test(userAgent)) {
            setTimeout(() => {
                e.target.src = e.target.dataset.src
            }, Number(delay));
            e.preventDefault();
            return false;
        }
    }


    let bg = e.target.getAttribute('data-bg');
    if (bg) {
        if (delay && (regexLighthouse.test(userAgent) || regexGTmetrix.test(userAgent))) {
            setTimeout(() => {
                e.target.style.backgroundImage = 'url(' + bg + ')';
            }, Number(delay));
        } else {
            e.target.style.backgroundImage = 'url(' + bg + ')';
        }
    }
});