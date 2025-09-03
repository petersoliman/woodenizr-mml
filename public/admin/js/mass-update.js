var selectedItems = [];
$(document).ready(function () {
    var table = $('.datatable-ajax').DataTable();
    table.on('xhr.dt', function (e, settings, json, xhr) {
        // no-op for header checkbox approach
    });
    // Remove DataTables row select dependency; manage selection by checkboxes only
    $(document.body).on("click", ".check-table-row", function (e) {
        var id = String($(this).val());
        var isChecked = $(this).is(":checked");
        var idx = selectedItems.indexOf(id);
        if (isChecked) { if (idx === -1) selectedItems.push(id); }
        else { if (idx !== -1) selectedItems.splice(idx, 1); }
        showHideMassUpdateBtns();
        updateHeaderCheckbox();
    });
    // Header select-all checkbox
    $(document).on('click', '.js-select-all', function(e){ e.stopPropagation(); });
    $(document).on('change', '.js-select-all', function(){
        var checked = $('.js-select-all:visible, .js-select-all').first().is(':checked');
        selectedItems.splice(0, selectedItems.length);
        if (checked) {
            $(".datatable-ajax .check-table-row").each(function(){
                $(this).prop("checked", true);
                var id = String($(this).val());
                if (selectedItems.indexOf(id) === -1) selectedItems.push(id);
            });
        } else {
            $(".datatable-ajax .check-table-row").prop("checked", false);
        }
        showHideMassUpdateBtns();
        updateHeaderCheckbox();
    });

    function updateHeaderCheckbox(){
        var total = $(".datatable-ajax .check-table-row").length;
        var checked = $(".datatable-ajax .check-table-row:checked").length;
        var headers = $('.js-select-all');
        headers.each(function(){
            if (total === 0) { $(this).prop('indeterminate', false).prop('checked', false); return; }
            if (checked === 0) {
                $(this).prop('indeterminate', false).prop('checked', false);
            } else if (checked === total) {
                $(this).prop('indeterminate', false).prop('checked', true);
            } else {
                $(this).prop('checked', false).prop('indeterminate', true);
            }
        });
    }

    table.on('draw', function(){
        // Re-check boxes based on selectedItems when table redraws
        $(".datatable-ajax .check-table-row").each(function(){
            var id = String($(this).val());
            $(this).prop('checked', selectedItems.indexOf(id) !== -1);
        });
        updateHeaderCheckbox();
    });
});

function showHideMassUpdateBtns() {
    if (selectedItems.length == 0) {
        $("#mass-update-btn").addClass("hidden");
    } else {
        $("#mass-update-btn").removeClass("hidden");
    }
    if (selectedItems.length > 0) {
        $("#select-all-drop-down button:first-child span.text").text(selectedItems.length + " items selected");
    } else {
        $("#select-all-drop-down button:first-child span.text").text("");
    }
}

function getSelectedItemsInGETParam(arrayParamName) {
    var s = "";
    for (var i = 0; i < selectedItems.length; i++) {
        if (i > 0) {
            s += "&";
        }
        s += arrayParamName + "[]=" + selectedItems[i];
    }
    return s;
}

function openNewWindow(url, arrayParamName) {
    var params = getSelectedItemsInGETParam(arrayParamName);
    url = url + "?" + params;
    window.open(url, 'window');

}