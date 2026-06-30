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
//                     url: base_url + "admin/blog-all-list",
//                     type: "GET",
//                     data: function (d) {
//                         d.from_date = $('#cl_from_date').val();
//                         d.to_date = $('#cl_to_date').val();
//                         d.client_filter = $('#client_filter').val();
//                     }
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'BlogInfo' },
//                     { data: 'Status' },
//                     { data: 'Action' }
//                 ]
//             });


//             $(document).on("change", ".blog_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("toggle-url");

//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change the brand status to this?",
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
//                                         text: "brand status updated successfully!",
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
//                                     checkbox.prop("checked", false);
//                                     checkbox.prop("checked", isChecked);

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

$(document).ready(function () {
    // ---------------------------
    // ✅ DEMO MODE HELPERS (optional)
    // ---------------------------
    function isDemoMode() {
        return !!(
            (window.APP_CONFIG && window.APP_CONFIG.DEMO === true) ||
            window.DEMOVERSION === true
        );
    }

    function demoBlockAlert(msg) {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: msg || "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false,
        });
    }

    var KTDatatablesExample = (function () {
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
                    url: base_url + "admin/blog-all-list",
                    type: "GET",
                    data: function (d) {
                        d.from_date = $("#cl_from_date").val();
                        d.to_date = $("#cl_to_date").val();
                        d.client_filter = $("#client_filter").val();
                    },
                },
                columns: [
                    { data: "RecordID" },
                    { data: "BlogInfo" },
                    { data: "Status" },
                    { data: "Action" },
                ],
            });

            // ✅ Toggle status (with demo block)
            $(document).on("change", ".blog_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("toggle-url");

                // temporarily revert until confirm
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the blog status to this?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Change it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary",
                    },
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // ✅ DEMO MODE STOP
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
                            if (response.status === "success") {
                                checkbox.prop("checked", isChecked);

                                Swal.fire({
                                    text: "Blog status updated successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });

                                datatable.ajax.reload(null, false);
                            } else {
                                Swal.fire({
                                    text: response.message || "Something went wrong!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!",
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Failed to change the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!",
                            });
                        },
                    });
                });
            });

            // ✅ Delete (with demo block)
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
                        cancelButton: "btn btn-secondary",
                    },
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // ✅ DEMO MODE STOP
                    if (isDemoMode()) {
                        demoBlockAlert("You Can not delete item.");
                        return;
                    }

                    $.ajax({
                        url: deleteUrl,
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                Swal.fire({
                                    text: response.message || "Deleted successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });

                                datatable.ajax.reload(null, false);
                            } else {
                                Swal.fire({
                                    text: response.message || "Something went wrong!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!",
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Failed to delete the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!",
                            });
                        },
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
            },
        };
    })();

    KTDatatablesExample.init();
});
