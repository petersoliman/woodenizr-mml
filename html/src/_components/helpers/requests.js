export const debounce = (func, timeout = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

export const savedDebounce = (fn, attachedVars, wait = 300) => {
    clearTimeout(window.timer);
    window.timer = setTimeout(() => {
        fn.bind(...attachedVars).apply(this, arguments)
    }, (wait || 1));
}
