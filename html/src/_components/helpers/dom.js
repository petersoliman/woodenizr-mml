export const closest = (elem, selector) => {
    while (elem !== document.body) {
        elem = elem.parentElement;
        if (elem.matches(selector)) return elem;
    }
    return null;
}

export const selector = (elm) => {
    if (elm.tagName === "BODY") return "BODY";
    const names = [];
    while (elm.parentElement && elm.tagName !== "BODY") {
        if (elm.id) {
            names.unshift("#" + elm.getAttribute("id")); // getAttribute, because `elm.id` could also return a child element with name "id"
            break; // Because ID should be unique, no more is needed. Remove the break, if you always want a full path.
        } else {
            let c = 1, e = elm;
            for (; e.previousElementSibling; e = e.previousElementSibling, c++) ;
            names.unshift(elm.tagName + ":nth-child(" + c + ")");
        }
        elm = elm.parentElement;
    }
    return names.join(">");
}

export const createNode = (htmlStr) => {
    let frag = document.createDocumentFragment(),
        temp = document.createElement('div');
    temp.innerHTML = htmlStr;
    while (temp.firstChild) {
        frag.appendChild(temp.firstChild);
    }
    return frag;
}

export const addLiveEventListener = (eventType, elementQuerySelector, cb) => {
    document.addEventListener(eventType, function (event) {
        var qs = document.querySelectorAll(elementQuerySelector);
        if (qs) {
            var el = event.target, index = -1;
            while (el && ((index = Array.prototype.indexOf.call(qs, el)) === -1)) {
                el = el.parentElement;
            }

            if (index > -1) {
                cb.call(el, event);
            }
        }
    });
}