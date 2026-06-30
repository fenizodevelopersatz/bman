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
//                     url: base_url + "currency-list",
//                     type: "GET",
//                     data: function (d) {
//                         d.from_date = $('#cl_from_date').val();
//                         d.to_date = $('#cl_to_date').val();
//                         d.client_filter = $('#client_filter').val();
//                         d.agent_filter = $('#agent_filter').val();
//                         d.call_status = $('#call_status').val();
//                     }
//                 },
//                 columns: [
//                     { data: 'RecordID' },
//                     { data: 'paymentImg' },
//                     { data: 'paymentSymbol' },
//                     { data: 'paymentStatus' },
//                     { data: 'Decimal' },
//                     { data: null },
//                 ],
//                 columnDefs: [
//                     {
//                         targets: -1,
//                         data: null,
//                         orderable: false,
//                         className: 'text-end',
//                         render: function (data, type, row) {
//                             var encodedAgentId = encodeURIComponent(row.paymentid);
//                             var action_url = base_url + 'currency-edit/' + encodedAgentId;
//                             var delete_url = base_url + 'currency-delete/' + encodedAgentId;
//                             return `
//                                 <a href="`+ action_url + `" class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle_s" 
//                                 data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
//                                     Edit
//                                     <span class="svg-icon fs-5 m-0">
//                                     </span>
//                                 </a>
//                                  <a href="javascript:void(0);" class="btn btn-danger btn-active-light-danger btn-sm btn-delete"
//                                 data-delete-url="`+ delete_url + `">
//                                     Delete
//                                     <span class="svg-icon fs-5 m-0"></span>
//                                 </a>
//                             `;
//                         },
//                     },
//                 ],
//             });

//             $(document).on("change", ".currency_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("currency_status-url");

//                 // Revert the checkbox state until the user confirms
//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change the main currency to this?",
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
//                             text: 'You Can not change record.',
//                             confirmButtonText: 'Ok, got it!',
//                             customClass: {
//                                 confirmButton: 'btn btn-primary'
//                             },
//                             buttonsStyling: false
//                         });

//                         $.ajax({
//                             url: changestatusUrl,
//                             type: "POST",
//                             data: { currency_status: isChecked ? 1 : 0 },
//                             dataType: "json",
//                             success: function (response) {
//                                 if (response.status === "success") {
//                                     // Uncheck all other checkboxes except the current one
//                                     $(".currency_status").not(checkbox).prop("checked", false);

//                                     Swal.fire({
//                                         text: "Currency status updated successfully!",
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     }).then(() => {
//                                         checkbox.prop("checked", isChecked);
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

//                         Swal.fire({
//                             icon: 'info',
//                             title: 'Demo Version',
//                             text: 'You Can not change record.',
//                             confirmButtonText: 'Ok, got it!',
//                             customClass: {
//                                 confirmButton: 'btn btn-primary'
//                             },
//                             buttonsStyling: false
//                         });

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



//         }



//         var handleFilterChange = function () {
//             $('#cl_from_date, #cl_to_date, #client_filter, #agent_filter, #call_status').on('change', function () {
//                 datatable.draw();
//             });
//         }

//         var exportButtons = () => {
//             const documentTitle = 'Customer Orders Report';
//             var buttons = new $.fn.dataTable.Buttons(table, {
//                 buttons: [
//                     {
//                         extend: 'copyHtml5',
//                         title: documentTitle
//                     },
//                     {
//                         extend: 'excelHtml5',
//                         title: documentTitle
//                     },
//                     {
//                         extend: 'csvHtml5',
//                         title: documentTitle
//                     },
//                     {
//                         extend: 'pdfHtml5',
//                         title: documentTitle
//                     }
//                 ]
//             }).container().appendTo($('#kt_datatable_example_buttons'));

//             const exportButtons = document.querySelectorAll('#kt_datatable_example_export_menu [data-kt-export]');
//             exportButtons.forEach(exportButton => {
//                 exportButton.addEventListener('click', e => {
//                     e.preventDefault();

//                     const exportValue = e.target.getAttribute('data-kt-export');
//                     const target = document.querySelector('.dt-buttons .buttons-' + exportValue);

//                     target.click();
//                 });
//             });
//         }

//         var handleSearchDatatable = function () {
//             const filterSearch = document.querySelector('[data-kt-docs-table-filter="search"]');
//             filterSearch.addEventListener('keyup', function (e) {
//                 datatable.search(e.target.value).draw();
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
//                 exportButtons();
//                 handleFilterChange();
//                 handleSearchDatatable();

//             }
//         };
//     }();

//     KTDatatablesExample.init();

// });



"use strict";

$(document).ready(function () {

    /* =======================
       DEMO MODE HELPERS
    ======================= */
    function isDemoMode() {
        return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true);
    }

    function demoBlockAlert() {
        Swal.fire({
            icon: 'info',
            title: 'Demo Version',
            text: 'You Can not change record.',
            confirmButtonText: 'Ok, got it!',
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false
        });
    }

    var KTDatatablesExample = function () {
        var table;
        var datatable;

        var initDatatable = function () {

            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "currency-list",
                    type: "GET",
                    data: function (d) {
                        d.from_date = $('#cl_from_date').val();
                        d.to_date = $('#cl_to_date').val();
                        d.client_filter = $('#client_filter').val();
                        d.agent_filter = $('#agent_filter').val();
                        d.call_status = $('#call_status').val();
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'paymentImg' },
                    { data: 'paymentSymbol' },
                    { data: 'paymentStatus' },
                    { data: 'Decimal' },
                    { data: null },
                ],
                columnDefs: [
                    {
                        targets: -1,
                        data: null,
                        orderable: false,
                        className: 'text-end',
                        render: function (data, type, row) {
                            var encodedAgentId = encodeURIComponent(row.paymentid);
                            var action_url = base_url + 'currency-edit/' + encodedAgentId;
                            var delete_url = base_url + 'currency-delete/' + encodedAgentId;
                            return `
                <a href="${action_url}" class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle_s"
                   data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                  Edit
                  <span class="svg-icon fs-5 m-0"></span>
                </a>
                <a href="javascript:void(0);" class="btn btn-danger btn-active-light-danger btn-sm btn-delete"
                   data-delete-url="${delete_url}">
                  Delete
                  <span class="svg-icon fs-5 m-0"></span>
                </a>
              `;
                        },
                    },
                ],
            });

            /* =======================
               CHANGE MAIN CURRENCY
            ======================= */
            $(document).on("change", ".currency_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked"); // user intended state
                var changestatusUrl = checkbox.data("currency_status-url");

                // revert until confirmed
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the main currency to this?",
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
                        demoBlockAlert();
                        return;
                    }

                    $.ajax({
                        url: changestatusUrl,
                        type: "POST",
                        data: { currency_status: isChecked ? 1 : 0 },
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {

                                // Uncheck all others
                                $(".currency_status").not(checkbox).prop("checked", false);

                                Swal.fire({
                                    text: "Currency status updated successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(() => {
                                    checkbox.prop("checked", isChecked);
                                });

                            } else {
                                Swal.fire({
                                    text: response.message || "Something went wrong!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Failed to change the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!"
                            });
                        }
                    });
                });
            });

            /* =======================
               DELETE
            ======================= */
            $(document).on("click", ".btn-delete", function (e) {
                e.preventDefault();

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
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // ✅ DEMO BLOCK
                    if (isDemoMode()) {
                        demoBlockAlert();
                        return;
                    }

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
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(() => {
                                    // remove row (datatable safe way)
                                    if ($.fn.DataTable.isDataTable(table)) {
                                        datatable.row(row).remove().draw(false);
                                    } else {
                                        row.remove();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    text: response.message || "Something went wrong!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Failed to delete the record!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!"
                            });
                        }
                    });
                });
            });

        };

        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter, #agent_filter, #call_status').on('change', function () {
                datatable.draw();
            });
        };

        var exportButtons = () => {
            const documentTitle = 'Customer Orders Report';
            new $.fn.dataTable.Buttons(table, {
                buttons: [
                    { extend: 'copyHtml5', title: documentTitle },
                    { extend: 'excelHtml5', title: documentTitle },
                    { extend: 'csvHtml5', title: documentTitle },
                    { extend: 'pdfHtml5', title: documentTitle }
                ]
            }).container().appendTo($('#kt_datatable_example_buttons'));

            const exportButtons = document.querySelectorAll('#kt_datatable_example_export_menu [data-kt-export]');
            exportButtons.forEach(exportButton => {
                exportButton.addEventListener('click', e => {
                    e.preventDefault();
                    const exportValue = e.target.getAttribute('data-kt-export');
                    const target = document.querySelector('.dt-buttons .buttons-' + exportValue);
                    if (target) target.click();
                });
            });
        };

        var handleSearchDatatable = function () {
            const filterSearch = document.querySelector('[data-kt-docs-table-filter="search"]');
            if (!filterSearch) return;
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        };

        return {
            init: function () {
                table = document.querySelector('#kt-client-follow-table');

                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }

                if (!table) return;

                initDatatable();
                exportButtons();
                handleFilterChange();
                handleSearchDatatable();
            }
        };
    }();

    KTDatatablesExample.init();

});
