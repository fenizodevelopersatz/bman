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
//                     url: base_url + "get-list-investment",
//                     type: "GET",
//                     data: function (d) {
//                         d.from_date = $('#cl_from_date').val();
//                         d.to_date = $('#cl_to_date').val();
//                         d.client_filter = $('#client_filter').val();
//                         d.call_status = $('#call_status').val();
//                     }
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'UserInfo' },
//                     { data: 'InvestInfo' },
//                     { data: 'DateInfo' },
//                     { data: 'EndDate' },
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
//                 var deleteUrl = $(this).data("reject-url");
//                 var row = $(this).closest("tr");

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You won't be able to revert this!",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, Delete it!",
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
//                             text: 'You Can not delete investment pacakge.',
//                             confirmButtonText: 'Ok, got it!',
//                             customClass: {
//                                 confirmButton: 'btn btn-primary'
//                             },
//                             buttonsStyling: false
//                         });

//                         // $.ajax({
//                         //     url: deleteUrl,
//                         //     type: "POST",
//                         //     dataType: "json",
//                         //     success: function (response) {
//                         //         if (response.status === "success") {
//                         //             Swal.fire({
//                         //                 text: response.message,
//                         //                 icon: "success",
//                         //                 buttonsStyling: false,
//                         //                 confirmButtonText: "Ok, got it!",
//                         //                 customClass: {
//                         //                     confirmButton: "btn btn-primary"
//                         //                 }
//                         //             }).then(() => {
//                         //                 row.remove(); 
//                         //             });
//                         //         } else {
//                         //             Swal.fire({
//                         //                 text: response.message || "Something went wrong!",
//                         //                 icon: "error",
//                         //                 confirmButtonText: "Ok, got it!"
//                         //             });
//                         //         }
//                         //     },
//                         //     error: function () {
//                         //         Swal.fire({
//                         //             text: "Failed to delete the record!",
//                         //             icon: "error",
//                         //             confirmButtonText: "Ok, got it!"
//                         //         });
//                         //     }
//                         // });

//                     }
//                 });
//             });


//             $(document).on("change", ".template_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("template_status-url");

//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change the reinvestment status to this?",
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
//                                     checkbox.prop("checked", false);
//                                     checkbox.prop("checked", isChecked);

//                                     Swal.fire({
//                                         text: "reinvestment status updated successfully!",
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

//             $(document).on("click", ".btn-approve", function (e) {
//                 e.preventDefault();
//                 var deleteUrl = $(this).data("approve-url");
//                 var row = $(this).closest("tr");

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You won't be able to revert this!",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, Approve it!",
//                     cancelButtonText: "No, cancel!",
//                     buttonsStyling: false,
//                     customClass: {
//                         confirmButton: "btn btn-success",
//                         cancelButton: "btn btn-secondary"
//                     }
//                 }).then((result) => {
//                     if (result.isConfirmed) {
//                         $.ajax({
//                             url: deleteUrl,
//                             type: "POST",
//                             dataType: "json",
//                             success: function (response) {
//                                 if (response.status === "success") {
//                                     Swal.fire({
//                                         text: response.message,
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

//         }

//         var handleFilterChange = function () {
//             $('#cl_from_date, #cl_to_date, #client_filter, #call_status').on('change', function () {
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
//     loadData();


//     function loadData() {

//         var from_date = $('#cl_from_date').val();
//         var to_date = $('#cl_to_date').val();
//         var client_filter = $('#client_filter').val();
//         var call_status = $('#call_status').val();

//         $.ajax({
//             url: base_url + 'all-investment-get',
//             type: 'POST',
//             data: {
//                 from_date: from_date,
//                 to_date: to_date,
//                 client_filter: client_filter,
//                 call_status: call_status,
//             },
//             success: function (response) {
//                 var data = JSON.parse(response);
//                 let totalAmount = typeof data.total_amount === "string"
//                     ? data.total_amount.replace(/,/g, '')
//                     : data.total_amount;

//                 let totalTokenAmount = typeof data.total_token_amount === "string"
//                     ? data.total_token_amount.replace(/,/g, '')
//                     : data.total_token_amount;

//                 const count1 = new countUp.CountUp("icd", parseFloat(totalAmount) || 0);
//                 const count2 = new countUp.CountUp("tcd", parseFloat(totalTokenAmount) || 0);

//                 count1.start();
//                 count2.start();
//             },
//             error: function (xhr, status, error) {
//                 console.error('Error fetching chart data:', error);
//             }
//         });
//     };

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
                    url: base_url + "get-list-investment",
                    type: "GET",
                    data: function (d) {
                        d.from_date = $("#cl_from_date").val();
                        d.to_date = $("#cl_to_date").val();
                        d.client_filter = $("#client_filter").val();
                        d.call_status = $("#call_status").val();
                    },
                },
                columns: [
                    { data: "RecordID" },
                    { data: "UserInfo" },
                    { data: "InvestInfo" },
                    { data: "DateInfo" },
                    { data: "EndDate" },
                    { data: "temp_content" },
                ],
            });

            // View summary modal/content
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

            // Delete / Reject
            $(document).on("click", ".btn-delete", function (e) {
                e.preventDefault();

                var deleteUrl = $(this).data("reject-url");
                var row = $(this).closest("tr");

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Delete it!",
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
                        demoBlockAlert("You Can not delete investment package.");
                        return;
                    }

                    // ✅ REAL API (uncomment if needed)

                    $.ajax({
                        url: deleteUrl,
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                Swal.fire({
                                    text: response.message,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                }).then(() => {
                                    datatable.ajax.reload(null, false);
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


                    // If you still want to visually remove row (without API)
                    // row.remove();
                });
            });

            // Reinvestment status toggle
            $(document).on("change", ".template_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("template_status-url");

                // revert until confirmed
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the reinvestment status to this?",
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
                        demoBlockAlert("You Can not change reinvestment status.");
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
                                    text: "reinvestment status updated successfully!",
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

            // Approve
            $(document).on("click", ".btn-approve", function (e) {
                e.preventDefault();

                var approveUrl = $(this).data("approve-url");
                var row = $(this).closest("tr");

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Approve it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-secondary",
                    },
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // ✅ DEMO MODE STOP
                    if (isDemoMode()) {
                        demoBlockAlert("You Can not approve in demo mode.");
                        return;
                    }

                    $.ajax({
                        url: approveUrl,
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                Swal.fire({
                                    text: response.message,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                }).then(() => {
                                    // Better than row.remove() when serverSide: true
                                    datatable.ajax.reload(null, false);
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
                                text: "Failed to approve the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!",
                            });
                        },
                    });
                });
            });
        };

        var handleFilterChange = function () {
            $("#cl_from_date, #cl_to_date, #client_filter, #call_status").on(
                "change",
                function () {
                    datatable.ajax.reload(null, false);
                    loadData();
                }
            );
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
    loadData();

    function loadData() {
        var from_date = $("#cl_from_date").val();
        var to_date = $("#cl_to_date").val();
        var client_filter = $("#client_filter").val();
        var call_status = $("#call_status").val();

        $.ajax({
            url: base_url + "all-investment-get",
            type: "POST",
            data: {
                from_date: from_date,
                to_date: to_date,
                client_filter: client_filter,
                call_status: call_status,
            },
            success: function (response) {
                var data = JSON.parse(response);

                let totalAmount =
                    typeof data.total_amount === "string"
                        ? data.total_amount.replace(/,/g, "")
                        : data.total_amount;

                let totalTokenAmount =
                    typeof data.total_token_amount === "string"
                        ? data.total_token_amount.replace(/,/g, "")
                        : data.total_token_amount;

                const count1 = new countUp.CountUp("icd", parseFloat(totalAmount) || 0);
                const count2 = new countUp.CountUp(
                    "tcd",
                    parseFloat(totalTokenAmount) || 0
                );

                count1.start();
                count2.start();
            },
            error: function (xhr, status, error) {
                console.error("Error fetching chart data:", error);
            },
        });
    }
});
