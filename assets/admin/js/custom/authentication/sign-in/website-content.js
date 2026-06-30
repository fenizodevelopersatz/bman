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
//                     url: base_url + "websitecontent-list-cms",
//                     type: "GET",
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'temp_name' },
//                     { data: 'temp_status' },
//                     { data: 'temp_content' },
//                 ]
//             });


//             $('#kt-client-follow-table').on('click', '.view-summary', function () {
//                 var aiSummary = $(this).data('summary');

//                 $.ajax({
//                     url: base_url + "view-websitecontent-section-cms/" + aiSummary,
//                     type: 'GET',
//                     success: function (data) {
//                         $('#ai-summary-content').html(data);
//                     },
//                     error: function () {
//                         console.log('error')
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
//                                         text: "Email template status updated successfully!",
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
            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, "desc"]],
                stateSave: true,
                ajax: {
                    url: base_url + "websitecontent-list-cms",
                    type: "GET",
                },
                columns: [
                    { data: "RecordID" },
                    { data: "temp_name" },
                    { data: "temp_status" },
                    { data: "temp_content" },
                ],
            });

            // View summary
            $("#kt-client-follow-table").on("click", ".view-summary", function () {
                var aiSummary = $(this).data("summary");

                $.ajax({
                    url: base_url + "view-websitecontent-section-cms/" + aiSummary,
                    type: "GET",
                    success: function (data) {
                        $("#ai-summary-content").html(data);
                    },
                    error: function () {
                        console.log("error");
                    },
                });
            });

            // Toggle status
            $(document).on("change", ".template_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked"); // new value user selected
                var changestatusUrl = checkbox.data("template_status-url");

                // revert until confirm + success
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

                    // ✅ DEMO MODE BLOCK (no ajax)
                    if (isDemoMode()) {
                        demoBlockAlert("You Can not change record.");
                        // keep reverted state (no change)
                        checkbox.prop("checked", !isChecked);
                        return;
                    }

                    $.ajax({
                        url: changestatusUrl,
                        type: "POST",
                        data: { template_status: isChecked ? 1 : 0 },
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                // ✅ apply change
                                checkbox.prop("checked", isChecked);

                                Swal.fire({
                                    text: "Website content status updated successfully!",
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
                                // keep reverted state
                                checkbox.prop("checked", !isChecked);
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Failed to change the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!",
                            });
                            // keep reverted state
                            checkbox.prop("checked", !isChecked);
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
