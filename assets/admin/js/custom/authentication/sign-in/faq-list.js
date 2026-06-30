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
//                     url: base_url + "faq-list-cms",
//                     type: "GET",
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'temp_name' },
//                     { data: 'temp_status' },
//                     { data: 'temp_content' },
//                 ],
//             });


//             $('#kt-client-follow-table').on('click', '.view-summary', function () {
//                 var aiSummary = $(this).data('summary');

//                 $.ajax({
//                     url: base_url + "view-announceemnt-section-cms/" + aiSummary,
//                     type: 'GET',
//                     success: function (data) {
//                         $('#ai-summary-content').html(data);
//                     },
//                     error: function () {
//                         console.log('error')
//                     }
//                 });

//             });


//             $(document).on("click", ".btn-delete", function (e) {
//                 e.preventDefault();
//                 var deleteUrl = $(this).data("delete-url");
//                 var row = $(this).closest("tr");

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You won't be able to revert this!",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, delete it!",
//                     cancelButtonText: "No, cancel!",
//                     buttonsStyling: false,
//                     customClass: {
//                         confirmButton: "btn btn-danger",
//                         cancelButton: "btn btn-secondary"
//                     }
//                 }).then((result) => {
//                     if (result.isConfirmed) {

//                         // Swal.fire({
//                         //     icon: 'info',
//                         //     title: 'Demo Version',
//                         //     text: 'You Can not delete records.',
//                         //     confirmButtonText: 'Ok, got it!',
//                         //     customClass: {
//                         //         confirmButton: 'btn btn-primary'
//                         //     },
//                         //     buttonsStyling: false
//                         // });

//                         $.ajax({
//                             url: deleteUrl,
//                             type: "POST",
//                             dataType: "json",
//                             success: function (response) {
//                                 if (response.status === "success") {
//                                     Swal.fire({
//                                         text: "Record deleted successfully!",
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     }).then(() => {
//                                         row.remove();
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
//                                     text: "Failed to delete the record!",
//                                     icon: "error",
//                                     confirmButtonText: "Ok, got it!"
//                                 });
//                             }
//                         });

//                     }
//                 });

//             });


//             $(document).on("change", ".template_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("template_status-url");

//                 // Revert checkbox state temporarily
//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change the template status to this?",
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

//                         // Swal.fire({
//                         //     icon: 'info',
//                         //     title: 'Demo Version',
//                         //     text: 'You Can not change record status.',
//                         //     confirmButtonText: 'Ok, got it!',
//                         //     customClass: {
//                         //         confirmButton: 'btn btn-primary'
//                         //     },
//                         //     buttonsStyling: false
//                         // });

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
            table.querySelectorAll("tbody tr"); // optional

            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, "desc"]],
                stateSave: true,
                ajax: {
                    url: base_url + "faq-list-cms",
                    type: "GET",
                },
                columns: [
                    { data: "RecordID" },
                    { data: "temp_name" },
                    { data: "temp_status" },
                    { data: "temp_content" },
                ],
            });

            // ✅ View summary
            $("#kt-client-follow-table").on("click", ".view-summary", function () {
                var aiSummary = $(this).data("summary");

                $.ajax({
                    url: base_url + "view-announceemnt-section-cms/" + aiSummary,
                    type: "GET",
                    success: function (data) {
                        $("#ai-summary-content").html(data);
                    },
                    error: function () {
                        console.log("error");
                    },
                });
            });

            // ✅ Delete
            $(document).on("click", ".btn-delete", function (e) {
                e.preventDefault();

                // ✅ DEMO MODE STOP
                if (isDemoMode()) {
                    demoBlockAlert("You Can not delete records.");
                    return;
                }

                var deleteUrl = $(this).data("delete-url");
                var row = $(this).closest("tr");

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
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

                    $.ajax({
                        url: deleteUrl,
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                Swal.fire({
                                    text: "Record deleted successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                }).then(() => {
                                    row.remove();
                                });
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

            // ✅ Toggle status
            $(document).on("change", ".template_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("template_status-url");

                // Revert checkbox state temporarily
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the template status to this?",
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
                        demoBlockAlert("You Can not change record status.");
                        return;
                    }

                    $.ajax({
                        url: changestatusUrl,
                        type: "POST",
                        data: { template_status: isChecked ? 1 : 0 },
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                checkbox.prop("checked", false);
                                checkbox.prop("checked", isChecked);

                                Swal.fire({
                                    text: "status updated successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });
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
        };

        return {
            init: function () {
                table = document.querySelector("#kt-client-follow-table");

                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }

                if (!table) return;

                initDatatable();
            },
        };
    })();

    KTDatatablesExample.init();
});
