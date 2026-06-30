// $(document).ready(function () {

//     var KTDatatablesExample = function () {
//         var table;
//         var datatable;

//         var initDatatable = function () {
//             const tableRows = table.querySelectorAll('tbody tr');
//             datatable = $(table).DataTable({
//                 searchDelay: 500,
//                 processing: true,
//                 serverSide: true,
//                 order: [[5, 'desc']],
//                 stateSave: true,
//                 ajax: {
//                     url: base_url + "admin/shipping_zone_list_view",
//                     type: "GET",
//                     data: function (d) {
//                         d.from_date = $('#cl_from_date').val();
//                         d.to_date = $('#cl_to_date').val();
//                         d.client_filter = $('#client_filter').val();
//                     }
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'PincodeInfo' },
//                     { data: 'ShippingCharge' },
//                     { data: 'COD' },
//                     { data: 'Status' },
//                     { data: 'Action' }
//                 ]
//             });


//             $(document).on("change", ".toggle_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("toggle-url");

//                 // Revert checkbox state temporarily
//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change status to this?",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, Change it!",
//                     cancelButtonText: "No, cancel!",
//                     buttonsStyling: false,
//                     customClass: {
//                         confirmButton: "btn btn-danger",
//                         cancelButton: "btn btn-secondary"
//                     }
//                 }).then((result) => {
//                     if (result.isConfirmed) {

//                         Swal.fire({
//                             icon: 'info',
//                             title: 'Demo Version',
//                             text: 'You Can not change status.',
//                             confirmButtonText: 'Ok, got it!',
//                             customClass: {
//                                 confirmButton: 'btn btn-primary'
//                             },
//                             buttonsStyling: false
//                         });

//                         $.ajax({
//                             url: changestatusUrl,
//                             type: "POST",
//                             data: { template_status: isChecked ? 1 : 0 },
//                             dataType: "json",
//                             success: function (response) {
//                                 if (response.status === "success") {
//                                     checkbox.prop("checked", false);
//                                     checkbox.prop("checked", isChecked);

//                                     Swal.fire({
//                                         text: "status updated successfully!",
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });

//                                 } else {
//                                     Swal.fire({
//                                         text: response.message || "Something went wrong!",
//                                         icon: "error",
//                                         confirmButtonText: "Ok, got it!"
//                                     });
//                                 }
//                             },
//                             error: function () {
//                                 Swal.fire({
//                                     text: "Failed to change the record!",
//                                     icon: "error",
//                                     confirmButtonText: "Ok, got it!"
//                                 });
//                             }
//                         });

//                     }
//                 });
//             });

//             $(document).on("click", ".delete_user", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("url");

//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to delete this item?",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, Change it!",
//                     cancelButtonText: "No, cancel!",
//                     buttonsStyling: false,
//                     customClass: {
//                         confirmButton: "btn btn-danger",
//                         cancelButton: "btn btn-secondary"
//                     }
//                 }).then((result) => {
//                     if (result.isConfirmed) {

//                         Swal.fire({
//                             icon: 'info',
//                             title: 'Demo Version',
//                             text: 'You Can not delete item.',
//                             confirmButtonText: 'Ok, got it!',
//                             customClass: {
//                                 confirmButton: 'btn btn-primary'
//                             },
//                             buttonsStyling: false
//                         });

//                         $.ajax({
//                             url: changestatusUrl,
//                             type: "POST",
//                             data: { template_status: isChecked ? 1 : 0 },
//                             dataType: "json",
//                             success: function (response) {
//                                 if (response.status === "success") {
//                                     // Uncheck all other checkboxes except the current one
//                                     checkbox.prop("checked", false);
//                                     checkbox.prop("checked", isChecked); // Keep the selected one checked

//                                     Swal.fire({
//                                         text: response.message || "Something went wrong!",
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });
//                                     datatable.ajax.reload(null, false);
//                                 } else {
//                                     Swal.fire({
//                                         text: response.message || "Something went wrong!",
//                                         icon: "error",
//                                         confirmButtonText: "Ok, got it!"
//                                     });
//                                 }
//                             },
//                             error: function () {
//                                 Swal.fire({
//                                     text: "Failed to change the record!",
//                                     icon: "error",
//                                     confirmButtonText: "Ok, got it!"
//                                 });
//                             }
//                         });

//                     }
//                 });
//             });

//         }

//         var handleFilterChange = function () {
//             $('#cl_from_date, #cl_to_date, #client_filter').on('change', function () {
//                 datatable.ajax.reload(null, false);
//                 loadData();
//             });
//         }

//         return {
//             init: function () {
//                 table = document.querySelector('#kt-client-follow-table');

//                 if ($.fn.DataTable.isDataTable(table)) {
//                     $(table).DataTable().clear().destroy();
//                 }

//                 if (!table) {
//                     return;
//                 }

//                 initDatatable();
//                 handleFilterChange();
//             }
//         };
//     }();

//     KTDatatablesExample.init();

// });


"use strict";

(function () {

    /* =======================
       DEMO MODE HELPERS
    ======================= */
    function isDemoMode() {
        return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true);
    }

    function demoBlockAlert(msg) {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: msg || "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false
        });
    }

    $(document).ready(function () {

        var KTDatatablesExample = function () {
            var table;
            var datatable;

            var initDatatable = function () {
                datatable = $(table).DataTable({
                    searchDelay: 500,
                    processing: true,
                    serverSide: true,
                    order: [[5, "desc"]],
                    stateSave: true,
                    ajax: {
                        url: base_url + "admin/shipping_zone_list_view",
                        type: "GET",
                        data: function (d) {
                            d.from_date = $("#cl_from_date").val();
                            d.to_date = $("#cl_to_date").val();
                            d.client_filter = $("#client_filter").val();
                        }
                    },
                    columns: [
                        { data: "RecordID" },
                        { data: "PincodeInfo" },
                        { data: "ShippingCharge" },
                        { data: "COD" },
                        { data: "Status" },
                        { data: "Action" }
                    ]
                });

                // ✅ Toggle status
                $(document).on("change", ".toggle_status", function (e) {
                    e.preventDefault();

                    var checkbox = $(this);
                    var isChecked = checkbox.prop("checked");
                    var changestatusUrl = checkbox.data("toggle-url");

                    // revert UI until confirmed
                    checkbox.prop("checked", !isChecked);

                    Swal.fire({
                        title: "Are you sure?",
                        text: "You want to change status to this?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, Change it!",
                        cancelButtonText: "No, cancel!",
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (!result.isConfirmed) return;

                        // ✅ DEMO BLOCK
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not change status.");
                            return;
                        }

                        $.ajax({
                            url: changestatusUrl,
                            type: "POST",
                            data: { template_status: isChecked ? 1 : 0 },
                            dataType: "json",
                            success: function (response) {
                                if (response && (response.status === "success" || response.status === true)) {
                                    checkbox.prop("checked", isChecked);

                                    Swal.fire({
                                        text: response.message || "Status updated successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    });

                                    datatable.ajax.reload(null, false);
                                } else {
                                    Swal.fire({
                                        text: (response && response.message) ? response.message : "Something went wrong!",
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    text: "Failed to change the record!",
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                });
                            }
                        });
                    });
                });

                // ✅ Delete item
                $(document).on("click", ".delete_user", function (e) {
                    e.preventDefault();

                    var btn = $(this);
                    var deleteUrl = btn.data("url");

                    Swal.fire({
                        title: "Are you sure?",
                        text: "You want to delete this item?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, delete it!",
                        cancelButtonText: "No, cancel!",
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (!result.isConfirmed) return;

                        // ✅ DEMO BLOCK
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not delete item.");
                            return;
                        }

                        $.ajax({
                            url: deleteUrl,
                            type: "POST",
                            dataType: "json",
                            success: function (response) {
                                if (response && (response.status === "success" || response.status === true)) {
                                    Swal.fire({
                                        text: response.message || "Deleted successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    });

                                    datatable.ajax.reload(null, false);
                                } else {
                                    Swal.fire({
                                        text: (response && response.message) ? response.message : "Something went wrong!",
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    text: "Failed to delete the record!",
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                });
                            }
                        });
                    });
                });

            };

            var handleFilterChange = function () {
                $("#cl_from_date, #cl_to_date, #client_filter").on("change", function () {
                    datatable.ajax.reload(null, false);
                    if (typeof loadData === "function") loadData();
                });
            };

            return {
                init: function () {
                    table = document.querySelector("#kt-client-follow-table");

                    if ($.fn.DataTable.isDataTable(table)) {
                        $(table).DataTable().clear().destroy();
                    }

                    if (!table) return;

                    initDatatable();
                    handleFilterChange();
                }
            };
        }();

        KTDatatablesExample.init();

    });

})();
