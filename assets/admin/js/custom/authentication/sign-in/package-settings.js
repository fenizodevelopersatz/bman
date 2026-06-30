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
//                     url: base_url + "package-list",
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
//                     { data: 'Minimum' },
//                     { data: 'Maximum' },
//                     { data: 'Period' },
//                     { data: 'paymentStatus' },
//                     { data: 'total_invest' },
//                     { data: 'total_withdraw' },
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
//                             var action_url = base_url + 'edit-package/' + encodedAgentId;
//                             var delete_url = base_url + 'delete-package/' + encodedAgentId;
//                             return `
//                             <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center me-4" 
//                             href="`+ action_url + `">
//                             <i class="fa-solid fa-pen-to-square "></i> Edit
//                             </a>
//                             <a class="btn btn-danger btn-active-light-danger btn-sm text-center delete-package" href="`+ delete_url + `">
//                             <i class="fa fa-trash" aria-hidden="true"></i>  Delete
//                             </a>
//                             `;
//                         },
//                     },
//                 ],
//             });


//             $(document).on("change", ".package_status", function (e) {
//                 e.preventDefault();

//                 var checkbox = $(this);
//                 var isChecked = checkbox.prop("checked");
//                 var changestatusUrl = checkbox.data("package_status-url");

//                 // Revert the checkbox state until the user confirms
//                 checkbox.prop("checked", !isChecked);

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You want to change the package status to this?",
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

//                         // $.ajax({
//                         //     url: changestatusUrl,
//                         //     type: "POST",
//                         //     data: { package_status: isChecked ? 1 : 0 }, 
//                         //     dataType: "json",
//                         //     success: function (response) {
//                         //         if (response.status === "success") {
//                         //             $(this).prop("checked", false);
//                         //             Swal.fire({
//                         //                 text: response.message,
//                         //                 icon: "success",
//                         //                 buttonsStyling: false,
//                         //                 confirmButtonText: "Ok, got it!",
//                         //                 customClass: {
//                         //                     confirmButton: "btn btn-primary"
//                         //                 }
//                         //             }).then(() => {
//                         //                 checkbox.prop("checked", isChecked); 
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
//                         //             text: "Failed to change the record!",
//                         //             icon: "error",
//                         //             confirmButtonText: "Ok, got it!"
//                         //         });
//                         //     }
//                         // });

//                     }
//                 });
//             });


//             $(document).on("click", ".delete-package", function (e) {
//                 e.preventDefault(); // Prevent default link action

//                 var deleteUrl = $(this).attr("href"); // Get the delete URL from the button

//                 Swal.fire({
//                     title: "Are you sure?",
//                     text: "You won't be able to revert this!",
//                     icon: "warning",
//                     showCancelButton: true,
//                     confirmButtonText: "Yes, delete it!",
//                     cancelButtonText: "No, cancel!",
//                     buttonsStyling: false,
//                     customClass: {
//                         confirmButton: "btn btn-danger me-2",
//                         cancelButton: "btn btn-secondary"
//                     }
//                 }).then((result) => {
//                     if (result.isConfirmed) {
//                         // Make an AJAX request to delete the package

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

//                         // $.ajax({
//                         //     url: deleteUrl,
//                         //     type: "POST",
//                         //     dataType: "json",
//                         //     success: function (res) {
//                         //         if (res.status) {
//                         //             Swal.fire({
//                         //                 text: res.message,
//                         //                 icon: "success",
//                         //                 buttonsStyling: false,
//                         //                 confirmButtonText: "Ok, got it!",
//                         //                 customClass: {
//                         //                     confirmButton: "btn btn-primary"
//                         //                 }
//                         //             }).then(function () {
//                         //                 location.reload(); // Reload the page after successful deletion
//                         //             });
//                         //         } else {
//                         //             Swal.fire({
//                         //                 text: res.message,
//                         //                 icon: "error",
//                         //                 buttonsStyling: false,
//                         //                 confirmButtonText: "Ok, got it!",
//                         //                 customClass: {
//                         //                     confirmButton: "btn btn-danger"
//                         //                 }
//                         //             });
//                         //         }
//                         //     },
//                         //     error: function () {
//                         //         Swal.fire({
//                         //             text: "Something went wrong! Please try again.",
//                         //             icon: "error",
//                         //             buttonsStyling: false,
//                         //             confirmButtonText: "Ok, got it!",
//                         //             customClass: {
//                         //                 confirmButton: "btn btn-danger"
//                         //             }
//                         //         });
//                         //     }
//                         // });

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


$(document).ready(function () {

    // ✅ Demo helpers (keep once globally; if already in common.js remove from here)
    function isDemoMode() { return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true); }
    function demoBlockAlert() { Swal.fire({ icon: 'info', title: 'Demo Version', text: 'You Can not change record.', confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }); }
    function stopIfDemo(e) { if (!isDemoMode()) return false; if (e && e.preventDefault) e.preventDefault(); demoBlockAlert(); return true; }

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
                    url: base_url + "package-list",
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
                    { data: 'Minimum' },
                    { data: 'Maximum' },
                    { data: 'Period' },
                    { data: 'paymentStatus' },
                    { data: 'total_invest' },
                    { data: 'total_withdraw' },
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
                            var action_url = base_url + 'edit-package/' + encodedAgentId;
                            var delete_url = base_url + 'delete-package/' + encodedAgentId;
                            return `
                <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center me-4"
                   href="${action_url}">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>
                <a class="btn btn-danger btn-active-light-danger btn-sm text-center delete-package"
                   href="${delete_url}">
                  <i class="fa fa-trash" aria-hidden="true"></i> Delete
                </a>
              `;
                        },
                    },
                ],
            });

            // ✅ Status change (Demo block before anything)
            $(document).on("change", ".package_status", function (e) {
                e.preventDefault();

                // if demo -> revert UI instantly & stop
                if (isDemoMode()) {
                    $(this).prop("checked", !$(this).prop("checked"));
                    demoBlockAlert();
                    return;
                }

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("package_status-url");

                // Revert the checkbox until confirm
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the package status to this?",
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

                    // ✅ REAL ajax (uncomment in live)
                    $.ajax({
                        url: changestatusUrl,
                        type: "POST",
                        data: { package_status: isChecked ? 1 : 0 },
                        dataType: "json",
                        success: function (response) {
                            if (response.status === "success") {
                                Swal.fire({
                                    text: response.message,
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

            // ✅ Delete (Demo block before confirm)
            $(document).on("click", ".delete-package", function (e) {
                e.preventDefault();

                // demo -> stop
                if (stopIfDemo(e)) return;

                var deleteUrl = $(this).attr("href");

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-danger me-2",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // ✅ REAL ajax (uncomment in live)                    
                    $.ajax({
                        url: deleteUrl,
                        type: "POST",
                        dataType: "json",
                        success: function (res) {
                            if (res.status) {
                                Swal.fire({
                                    text: res.message,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(function () {
                                    datatable.ajax.reload(null, false); // better than full reload
                                });
                            } else {
                                Swal.fire({
                                    text: res.message,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-danger" }
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                text: "Something went wrong! Please try again.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: { confirmButton: "btn btn-danger" }
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
                    target && target.click();
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

                if (!table) return;

                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }

                initDatatable();
                exportButtons();
                handleFilterChange();
                handleSearchDatatable();
            }
        };
    }();

    KTDatatablesExample.init();
});
