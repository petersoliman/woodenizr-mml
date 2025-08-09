// show red bullet on tab has a not valid inputes
$('[data-toggle="tab"]').each(function () {
    var href = $(this).attr("href").replace();
    if (href.charAt(0) === '#') {
        href = href.substr(1);
    }
    if ($("#" + href).find(".form-error").length > 0) {
        $(this).append(' <i class="icon-circle2 text-danger form-error-bullet"></i>');
    }
});
$('.dropdown').each(function () {
    if ($(this).find(".form-error-bullet").length > 0) {
        $(this).children("a").append(' <i class="icon-circle2 text-danger form-error-bullet"></i>');
    }
});
$('body').on("click", ".delete-btn", function () {
    var id = $(this).data('delete');
    $('#del-form').attr('action', id);
});
$('body').on('click', 'a[href="#"]', function (e) {
    e.preventDefault();
});

if ($('.fab-menu-bottom-right').length > 0 && $('#fab-menu-affixed-demo-right').length > 0) {
    $(window).scroll(function () {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 40) {
            $('.fab-menu-bottom-right').addClass('reached-bottom');
        } else {
            $('.fab-menu-bottom-right').removeClass('reached-bottom');
        }
    });
// Right alignment
    $('#fab-menu-affixed-demo-right').affix({
        offset: {
            top: $('#fab-menu-affixed-demo-right').offset().top - 20
        }
    });
}
// Select with search
if ($('.select-search').length > 0) {
    $('.select-search').select2();
}
if ($('.anytimepicker').length > 0) {
    $('.anytimepicker').each(function () {
        $(this).AnyTime_picker({
            format: "%d/%m/%Z",
            firstDOW: 6,
        });

    });
}
if ($('.datepicker').length > 0) {
    $('.datepicker').pickadate({
        format: 'dd/mm/yyyy',
        formatSubmit: 'dd/mm/yyyy',
    });
}
$(".fn-print-html").click(function (e) {
    var page = $(this).attr('href');
    myWindow = window.open(page, 'Print', 'width=842,height=595');
    e.preventDefault();
});
$(".panel-filter .panel-heading .panel-title").click(function (e) {
    console.log(1);
    $(this).parents('.panel-filter').toggleClass('panel-collapsed');
    $(this).parent().find("a[data-action=collapse]").toggleClass('rotate-180');
    $panelCollapse = $(this).parent().nextAll();
    
    $panelCollapse.slideToggle(150);
});


function successNotify(message) {
    new PNotify({
        text: message,
        addclass: 'alert bg-success alert-styled-right',
        type: 'success'
    });
}
function errorNotify(message) {
    new PNotify({
        text: message,
        addclass: 'alert bg-danger alert-styled-right',
        type: 'error'
    });
}