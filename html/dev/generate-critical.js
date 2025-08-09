import glob from 'glob';
import fs from 'fs';
import {generate} from 'critical';
import {JSDOM} from 'jsdom';
import EventEmitter from 'events';

EventEmitter.setMaxListeners(0)

const breakpoints = [
    {
        width: 1920,
        height: 900,
    },
    {
        width: 1600,
        height: 900,
    },
    {
        width: 1400,
        height: 900,
    },
    {
        width: 1200,
        height: 900,
    },
    {
        width: 991,
        height: 1200,
    },
    {
        width: 412,
        height: 915,
    },
    {
        width: 390,
        height: 844,
    },
    {
        width: 360,
        height: 640,
    }
]
let i = 0;
glob.sync('./dist/!(*.critical|partials*)*.html').forEach((filePath) => {
    const fileName = filePath.replace('./dist/', '').replace('.html', '');
    i++;
    generate({
        inline: true,
        base: './dist/',
        src: fileName + '.html',
        dimensions: breakpoints,
        penthouse: {
            timeout: 180000
        }
        // target: {
        //   css: fileName + '-cr.css',
        // },
    }).then(({css}) => {
        fs.readFile(filePath, 'utf8', function (err, content) {
            const dom = new JSDOM(content);
            dom.window.document.head.insertAdjacentHTML("beforeend", `<style>${css}</style>`);
            dom.window.document.body.querySelectorAll('link[lazy="true"]').forEach((elm => {
                elm.remove();
            }));
            dom.window.document.body.querySelectorAll('link[rel="stylesheet"]').forEach((elm => {
                const url = elm.attributes.href.value;
                elm.setAttribute('rel', 'preload');
                elm.setAttribute('as', 'style');
                elm.setAttribute('onload', "this.onload=null;this.rel='stylesheet'");
                elm.insertAdjacentHTML('afterend', `<noscript><link rel="stylesheet" href="${url}"></noscript>`)
            }));
            dom.window.document.body.insertAdjacentHTML("beforeend", `<script>!function (n) { "use strict"; n.loadCSS || (n.loadCSS = function () { }); var t, o = loadCSS.relpreload = {}; o.support = function () { var e; try { e = n.document.createElement("link").relList.supports("preload") } catch (t) { e = !1 } return function () { return e } }(), o.bindMediaToggle = function (t) { var e = t.media || "all"; function a() { t.addEventListener ? t.removeEventListener("load", a) : t.attachEvent && t.detachEvent("onload", a), t.setAttribute("onload", null), t.media = e } t.addEventListener ? t.addEventListener("load", a) : t.attachEvent && t.attachEvent("onload", a), setTimeout(function () { t.rel = "stylesheet", t.media = "only x" }), setTimeout(a, 3e3) }, o.poly = function () { if (!o.support()) for (var t = n.document.getElementsByTagName("link"), e = 0; e < t.length; e++) { var a = t[e]; "preload" !== a.rel || "style" !== a.getAttribute("as") || a.getAttribute("data-loadcss") || (a.setAttribute("data-loadcss", !0), o.bindMediaToggle(a)) } }, o.support() || (o.poly(), t = n.setInterval(o.poly, 500), n.addEventListener ? n.addEventListener("load", function () { o.poly(), n.clearInterval(t) }) : n.attachEvent && n.attachEvent("onload", function () { o.poly(), n.clearInterval(t) })), "undefined" != typeof exports ? exports.loadCSS = loadCSS : n.loadCSS = loadCSS }("undefined" != typeof global ? global : this);</script>`);
            fs.writeFileSync('./dist/' + fileName + `.critical.html`, dom.serialize());
        });
    }).catch((error) => {
        console.error(`Error generating critical CSS for ${filePath}:`, error);
    });
    ;
});

{/*
<link rel="stylesheet" href="pages-assets/home/home.styles.css">

<link rel="preload" href="pages-assets/home/home.styles.css" as="style"
onload="this.onload=null;this.rel='stylesheet'">
<noscript>
<link rel="stylesheet" href="pages-assets/home/home.styles.css">
</noscript> */
}