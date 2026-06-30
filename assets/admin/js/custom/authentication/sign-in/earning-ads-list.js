// "use strict";

// (function () {
//     const tableId = "#kt-earning-ads-table";
//     const searchInput = document.querySelector('[data-kt-docs-table-filter="search"]');

//     let dt;

//     const initTable = () => {
//         dt = $(tableId).DataTable({
//             processing: true,
//             serverSide: true,
//             searching: true,
//             paging: true,
//             info: true,
//             order: [],
//             ajax: {
//                 url: base_url + "admin/earning-ads/list",
//                 type: "GET",
//             },
//             columns: [
//                 { data: "RecordID" },
//                 { data: "temp_name", orderable: false },
//                 { data: "temp_reward", orderable: false },
//                 { data: "temp_duration", orderable: false },
//                 { data: "temp_sort", orderable: false },
//                 { data: "temp_status", orderable: false },
//                 { data: "temp_content", orderable: false },
//             ],
//             columnDefs: [
//                 { targets: [1, 2, 3, 4, 5, 6], searchable: false },
//             ],
//             dom: "rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
//         });

//         // ✅ search
//         if (searchInput) {
//             searchInput.addEventListener("keyup", function (e) {
//                 dt.search(e.target.value).draw();
//             });
//         }

//         // ✅ export buttons (same style like FAQ)
//         const exportButtons = new $.fn.dataTable.Buttons(dt, {
//             buttons: [
//                 { extend: "copyHtml5", title: "Earning Ads" },
//                 { extend: "excelHtml5", title: "Earning Ads" },
//                 { extend: "csvHtml5", title: "Earning Ads" },
//                 { extend: "pdfHtml5", title: "Earning Ads" },
//             ],
//         }).container().appendTo($("#kt_datatable_example_buttons"));

//         document.querySelectorAll("#kt_datatable_example_export_menu [data-kt-export]").forEach((el) => {
//             el.addEventListener("click", function (e) {
//                 e.preventDefault();
//                 const type = el.getAttribute("data-kt-export");
//                 const btn = document.querySelector(`.dt-buttons .buttons-${type}`);
//                 if (btn) btn.click();
//             });
//         });
//     };

//     // ✅ Toggle status
//     const bindStatusToggle = () => {
//         $(document).on("change", ".template_status", function () {
//             const url = $(this).data("template_status-url");
//             const checked = $(this).is(":checked") ? 1 : 0;

//             $.ajax({
//                 url: url,
//                 type: "POST",
//                 data: { template_status: checked },
//                 success: function (res) {
//                     // optional toast
//                 },
//                 error: function () {
//                     // revert if error
//                     location.reload();
//                 },
//             });
//         });
//     };

//     // ✅ Delete
//     const bindDelete = () => {
//         $(document).on("click", ".btn-delete", function () {
//             const url = $(this).data("delete-url");

//             Swal.fire({
//                 text: "Are you sure you want to delete this Ad?",
//                 icon: "warning",
//                 showCancelButton: true,
//                 confirmButtonText: "Yes, delete!",
//                 cancelButtonText: "Cancel",
//                 buttonsStyling: false,
//                 customClass: {
//                     confirmButton: "btn btn-danger",
//                     cancelButton: "btn btn-light",
//                 },
//             }).then((result) => {
//                 if (!result.isConfirmed) return;

//                 $.ajax({
//                     url: url,
//                     type: "POST",
//                     success: function (res) {
//                         try { res = typeof res === "string" ? JSON.parse(res) : res; } catch (e) { }
//                         Swal.fire({
//                             text: (res && res.message) ? res.message : "Deleted",
//                             icon: "success",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok",
//                             customClass: { confirmButton: "btn btn-primary" }
//                         });
//                         dt.ajax.reload(null, false);
//                     },
//                     error: function () {
//                         Swal.fire({
//                             text: "Delete failed",
//                             icon: "error",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok",
//                             customClass: { confirmButton: "btn btn-primary" }
//                         });
//                     },
//                 });
//             });
//         });
//     };

//     document.addEventListener("DOMContentLoaded", function () {
//         initTable();
//         bindStatusToggle();
//         bindDelete();
//     });
// })();



// "use strict";

// (function () {
//     const tableId = "#kt-earning-ads-table";
//     const searchInput = document.querySelector('[data-kt-docs-table-filter="search"]');

//     let dt;

//     const initTable = () => {
//         dt = $(tableId).DataTable({
//             processing: true,
//             serverSide: true,
//             searching: true,
//             paging: true,
//             info: true,
//             order: [],
//             ajax: {
//                 url: base_url + "admin/earning-ads/list",
//                 type: "GET",
//             },

//             // ✅ MUST match JSON keys from controller
//             columns: [
//                 { data: "RecordID" },
//                 { data: "title", orderable: false },     // was temp_name
//                 { data: "reward", orderable: false },    // was temp_reward
//                 { data: "duration", orderable: false },  // was temp_duration
//                 { data: "sort", orderable: false },      // was temp_sort
//                 { data: "status", orderable: false },    // was temp_status
//                 { data: "action", orderable: false },    // was temp_content
//             ],

//             // ✅ allow search only on title column (optional)
//             columnDefs: [
//                 { targets: [2, 3, 4, 5, 6], searchable: false },
//             ],

//             dom: "rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
//         });

//         // ✅ search
//         if (searchInput) {
//             searchInput.addEventListener("keyup", function (e) {
//                 dt.search(e.target.value).draw();
//             });
//         }

//         // ✅ export buttons
//         new $.fn.dataTable.Buttons(dt, {
//             buttons: [
//                 { extend: "copyHtml5", title: "Earning Ads" },
//                 { extend: "excelHtml5", title: "Earning Ads" },
//                 { extend: "csvHtml5", title: "Earning Ads" },
//                 { extend: "pdfHtml5", title: "Earning Ads" },
//             ],
//         })
//             .container()
//             .appendTo($("#kt_datatable_example_buttons"));

//         document
//             .querySelectorAll("#kt_datatable_example_export_menu [data-kt-export]")
//             .forEach((el) => {
//                 el.addEventListener("click", function (e) {
//                     e.preventDefault();
//                     const type = el.getAttribute("data-kt-export");
//                     const btn = document.querySelector(`.dt-buttons .buttons-${type}`);
//                     if (btn) btn.click();
//                 });
//             });
//     };

//     // ✅ Toggle status (your API returns: data-status-url=".../status/{id}")
//     const bindStatusToggle = () => {
//         $(document).on("change", ".js-ad-status", function () {
//             const url = $(this).data("status-url");
//             const checked = $(this).is(":checked") ? 1 : 0;

//             $.ajax({
//                 url: url,
//                 type: "POST",
//                 data: { is_active: checked },
//                 success: function () {
//                     // optional toast
//                 },
//                 error: function () {
//                     location.reload();
//                 },
//             });
//         });
//     };

//     // ✅ Delete
//     const bindDelete = () => {
//         $(document).on("click", ".btn-delete", function () {
//             const url = $(this).data("delete-url");

//             Swal.fire({
//                 text: "Are you sure you want to delete this Ad?",
//                 icon: "warning",
//                 showCancelButton: true,
//                 confirmButtonText: "Yes, delete!",
//                 cancelButtonText: "Cancel",
//                 buttonsStyling: false,
//                 customClass: {
//                     confirmButton: "btn btn-danger",
//                     cancelButton: "btn btn-light",
//                 },
//             }).then((result) => {
//                 if (!result.isConfirmed) return;

//                 $.ajax({
//                     url: url,
//                     type: "POST",
//                     success: function (res) {
//                         try {
//                             res = typeof res === "string" ? JSON.parse(res) : res;
//                         } catch (e) { }

//                         Swal.fire({
//                             text: res?.message || "Deleted",
//                             icon: "success",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok",
//                             customClass: { confirmButton: "btn btn-primary" },
//                         });

//                         dt.ajax.reload(null, false);
//                     },
//                     error: function () {
//                         Swal.fire({
//                             text: "Delete failed",
//                             icon: "error",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok",
//                             customClass: { confirmButton: "btn btn-primary" },
//                         });
//                     },
//                 });
//             });
//         });
//     };

//     document.addEventListener("DOMContentLoaded", function () {
//         initTable();
//         bindStatusToggle();
//         bindDelete();
//     });
// })();




"use strict";

(function () {

    /* =======================
       DEMO MODE HELPERS
    ======================= */
    function isDemoMode() {
        return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true);
    }

    function demoBlockAlert() {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false
        });
    }

    const tableId = "#kt-earning-ads-table";
    const searchInput = document.querySelector('[data-kt-docs-table-filter="search"]');

    let dt;

    const initTable = () => {
        dt = $(tableId).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            paging: true,
            info: true,
            order: [],
            ajax: {
                url: base_url + "admin/earning-ads/list",
                type: "GET",
            },

            columns: [
                { data: "RecordID" },
                { data: "title", orderable: false },
                { data: "reward", orderable: false },
                { data: "duration", orderable: false },
                { data: "sort", orderable: false },
                { data: "status", orderable: false },
                { data: "action", orderable: false },
            ],

            columnDefs: [
                { targets: [2, 3, 4, 5, 6], searchable: false },
            ],

            dom: "rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // ✅ search
        if (searchInput) {
            searchInput.addEventListener("keyup", function (e) {
                dt.search(e.target.value).draw();
            });
        }

        // ✅ export buttons
        new $.fn.dataTable.Buttons(dt, {
            buttons: [
                { extend: "copyHtml5", title: "Earning Ads" },
                { extend: "excelHtml5", title: "Earning Ads" },
                { extend: "csvHtml5", title: "Earning Ads" },
                { extend: "pdfHtml5", title: "Earning Ads" },
            ],
        }).container().appendTo($("#kt_datatable_example_buttons"));

        document
            .querySelectorAll("#kt_datatable_example_export_menu [data-kt-export]")
            .forEach((el) => {
                el.addEventListener("click", function (e) {
                    e.preventDefault();
                    const type = el.getAttribute("data-kt-export");
                    const btn = document.querySelector(`.dt-buttons .buttons-${type}`);
                    if (btn) btn.click();
                });
            });
    };

    /* =======================
       TOGGLE STATUS
    ======================= */
    const bindStatusToggle = () => {
        $(document).on("change", ".js-ad-status", function () {
            const $cb = $(this);
            const url = $cb.data("status-url");
            const willBeChecked = $cb.is(":checked");   // user intent
            const checkedVal = willBeChecked ? 1 : 0;

            // ✅ DEMO BLOCK: revert UI + alert + stop
            if (isDemoMode()) {
                $cb.prop("checked", !willBeChecked);
                demoBlockAlert();
                return;
            }

            $.ajax({
                url: url,
                type: "POST",
                data: { is_active: checkedVal },
                success: function () {
                    // optional toast
                },
                error: function () {
                    // revert on error
                    $cb.prop("checked", !willBeChecked);
                    Swal.fire({
                        text: "Status update failed",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" },
                    });
                },
            });
        });
    };

    /* =======================
       DELETE
    ======================= */
    const bindDelete = () => {
        $(document).on("click", ".btn-delete", function (e) {
            e.preventDefault();

            const url = $(this).data("delete-url");

            Swal.fire({
                text: "Are you sure you want to delete this Ad?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete!",
                cancelButtonText: "Cancel",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: "btn btn-light",
                },
            }).then((result) => {
                if (!result.isConfirmed) return;

                // ✅ DEMO BLOCK: alert + stop
                if (isDemoMode()) {
                    demoBlockAlert();
                    return;
                }

                $.ajax({
                    url: url,
                    type: "POST",
                    success: function (res) {
                        try {
                            res = typeof res === "string" ? JSON.parse(res) : res;
                        } catch (e) { }

                        Swal.fire({
                            text: (res && res.message) ? res.message : "Deleted",
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: { confirmButton: "btn btn-primary" },
                        });

                        if (dt) dt.ajax.reload(null, false);
                    },
                    error: function () {
                        Swal.fire({
                            text: "Delete failed",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: { confirmButton: "btn btn-primary" },
                        });
                    },
                });
            });
        });
    };

    document.addEventListener("DOMContentLoaded", function () {
        initTable();
        bindStatusToggle();
        bindDelete();
    });

})();
