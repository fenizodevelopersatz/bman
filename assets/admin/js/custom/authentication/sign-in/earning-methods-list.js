// "use strict";

// (function () {
//     const tableId = "#kt-earning-methods-table";
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
//                 url: base_url + "admin/earning-methods/list",
//                 type: "GET",
//             },
//             columns: [
//                 { data: "RecordID" },
//                 { data: "temp_title", orderable: false },
//                 { data: "temp_reward", orderable: false },
//                 { data: "temp_target", orderable: false },
//                 { data: "temp_time", orderable: false },
//                 { data: "temp_btn", orderable: false },
//                 { data: "temp_sort", orderable: false },
//                 { data: "temp_status", orderable: false },
//                 { data: "temp_action", orderable: false },
//             ],
//             columnDefs: [{ targets: [1, 2, 3, 4, 5, 6, 7, 8], searchable: false }],
//             dom: "rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
//         });

//         if (searchInput) {
//             searchInput.addEventListener("keyup", function (e) {
//                 dt.search(e.target.value).draw();
//             });
//         }

//         // export buttons (same style as your FAQ)
//         new $.fn.dataTable.Buttons(dt, {
//             buttons: [
//                 { extend: "copyHtml5", title: "Earning Methods" },
//                 { extend: "excelHtml5", title: "Earning Methods" },
//                 { extend: "csvHtml5", title: "Earning Methods" },
//                 { extend: "pdfHtml5", title: "Earning Methods" },
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
//         $(document).on("change", ".js-method-status", function () {
//             const url = $(this).data("status-url");
//             const checked = $(this).is(":checked") ? 1 : 0;

//             $.ajax({
//                 url: url,
//                 type: "POST",
//                 data: { template_status: checked },
//                 success: function () { },
//                 error: function () {
//                     location.reload();
//                 },
//             });
//         });
//     };

//     // ✅ Open modal & fill data
//     const bindEdit = () => {
//         $(document).on("click", ".js-edit", function () {
//             const id = $(this).data("id");
//             $.ajax({
//                 url: base_url + "admin/earning-methods/show/" + id,
//                 type: "GET",
//                 success: function (res) {
//                     try { res = typeof res === "string" ? JSON.parse(res) : res; } catch (e) { }

//                     if (!res || !res.status) {
//                         Swal.fire("Error", (res && res.message) ? res.message : "Failed", "error");
//                         return;
//                     }

//                     const it = res.item;

//                     $("#method_id").val(it.id);
//                     $("#m_title").val(it.title || "");
//                     $("#m_subtitle").val(it.subtitle || "");
//                     $("#m_icon").val(it.icon || "");
//                     $("#m_badge_text").val(it.badge_text || "");
//                     $("#m_badge_bg").val(it.badge_bg || "");
//                     $("#m_badge_color").val(it.badge_color || "");
//                     $("#m_progress_color").val(it.progress_color || "");
//                     $("#m_btn_text").val(it.btn_text || "");
//                     $("#m_btn_gradient").val(it.btn_gradient || "");
//                     $("#m_daily_target").val(it.daily_target || 0);
//                     $("#m_reward_usd").val(it.reward_usd || 0);
//                     $("#m_est_time_label").val(it.est_time_label || "");
//                     $("#m_sort_order").val(it.sort_order || 1);

//                     $("#m_is_active").prop("checked", String(it.is_active) === "1");

//                     const modal = new bootstrap.Modal(document.getElementById("kt_modal_edit_method"));
//                     modal.show();
//                 },
//                 error: function () {
//                     Swal.fire("Error", "Failed to load data", "error");
//                 },
//             });
//         });
//     };

//     // ✅ Save modal
//     const bindSave = () => {
//         $("#kt_method_edit_form").on("submit", function (e) {
//             e.preventDefault();

//             const id = $("#method_id").val();
//             const form = $(this);

//             $.ajax({
//                 url: base_url + "admin/earning-methods/save/" + id,
//                 type: "POST",
//                 data: form.serialize(),
//                 success: function (res) {
//                     try { res = typeof res === "string" ? JSON.parse(res) : res; } catch (e) { }

//                     if (!res || !res.status) {
//                         Swal.fire("Error", (res && res.message) ? res.message : "Update failed", "error");
//                         return;
//                     }

//                     Swal.fire({
//                         text: res.message || "Updated",
//                         icon: "success",
//                         buttonsStyling: false,
//                         confirmButtonText: "Ok",
//                         customClass: { confirmButton: "btn btn-primary" }
//                     });

//                     // close modal
//                     const modalEl = document.getElementById("kt_modal_edit_method");
//                     const modal = bootstrap.Modal.getInstance(modalEl);
//                     if (modal) modal.hide();

//                     dt.ajax.reload(null, false);
//                 },
//                 error: function (xhr) {
//                     Swal.fire("Error", "Update failed", "error");
//                 },
//             });
//         });
//     };

//     document.addEventListener("DOMContentLoaded", function () {
//         initTable();
//         bindStatusToggle();
//         bindEdit();
//         bindSave();
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

    const tableId = "#kt-earning-methods-table";
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
                url: base_url + "admin/earning-methods/list",
                type: "GET",
            },
            columns: [
                { data: "RecordID" },
                { data: "temp_title", orderable: false },
                { data: "temp_reward", orderable: false },
                { data: "temp_target", orderable: false },
                { data: "temp_time", orderable: false },
                { data: "temp_btn", orderable: false },
                { data: "temp_sort", orderable: false },
                { data: "temp_status", orderable: false },
                { data: "temp_action", orderable: false },
            ],
            columnDefs: [{ targets: [1, 2, 3, 4, 5, 6, 7, 8], searchable: false }],
            dom: "rt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        if (searchInput) {
            searchInput.addEventListener("keyup", function (e) {
                dt.search(e.target.value).draw();
            });
        }

        // export buttons (same style as your FAQ)
        new $.fn.dataTable.Buttons(dt, {
            buttons: [
                { extend: "copyHtml5", title: "Earning Methods" },
                { extend: "excelHtml5", title: "Earning Methods" },
                { extend: "csvHtml5", title: "Earning Methods" },
                { extend: "pdfHtml5", title: "Earning Methods" },
            ],
        }).container().appendTo($("#kt_datatable_example_buttons"));

        document.querySelectorAll("#kt_datatable_example_export_menu [data-kt-export]").forEach((el) => {
            el.addEventListener("click", function (e) {
                e.preventDefault();
                const type = el.getAttribute("data-kt-export");
                const btn = document.querySelector(`.dt-buttons .buttons-${type}`);
                if (btn) btn.click();
            });
        });
    };

    /* =======================
       TOGGLE STATUS (POST)
    ======================= */
    const bindStatusToggle = () => {
        $(document).on("change", ".js-method-status", function () {
            const $cb = $(this);
            const url = $cb.data("status-url");
            const willBeChecked = $cb.is(":checked");
            const checkedVal = willBeChecked ? 1 : 0;

            // ✅ DEMO BLOCK
            if (isDemoMode()) {
                $cb.prop("checked", !willBeChecked);
                demoBlockAlert();
                return;
            }

            $.ajax({
                url: url,
                type: "POST",
                data: { template_status: checkedVal },
                success: function () { },
                error: function () {
                    $cb.prop("checked", !willBeChecked);
                    Swal.fire({
                        text: "Status update failed",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                },
            });
        });
    };

    /* =======================
       EDIT MODAL (GET allowed)
    ======================= */
    const bindEdit = () => {
        $(document).on("click", ".js-edit", function () {
            const id = $(this).data("id");

            $.ajax({
                url: base_url + "admin/earning-methods/show/" + id,
                type: "GET",
                success: function (res) {
                    try { res = typeof res === "string" ? JSON.parse(res) : res; } catch (e) { }

                    if (!res || !res.status) {
                        Swal.fire("Error", (res && res.message) ? res.message : "Failed", "error");
                        return;
                    }

                    const it = res.item;

                    $("#method_id").val(it.id);
                    $("#m_title").val(it.title || "");
                    $("#m_subtitle").val(it.subtitle || "");
                    $("#m_icon").val(it.icon || "");
                    $("#m_badge_text").val(it.badge_text || "");
                    $("#m_badge_bg").val(it.badge_bg || "");
                    $("#m_badge_color").val(it.badge_color || "");
                    $("#m_progress_color").val(it.progress_color || "");
                    $("#m_btn_text").val(it.btn_text || "");
                    $("#m_btn_gradient").val(it.btn_gradient || "");
                    $("#m_daily_target").val(it.daily_target || 0);
                    $("#m_reward_usd").val(it.reward_usd || 0);
                    $("#m_est_time_label").val(it.est_time_label || "");
                    $("#m_sort_order").val(it.sort_order || 1);

                    $("#m_is_active").prop("checked", String(it.is_active) === "1");

                    const modal = new bootstrap.Modal(document.getElementById("kt_modal_edit_method"));
                    modal.show();
                },
                error: function () {
                    Swal.fire("Error", "Failed to load data", "error");
                },
            });
        });
    };

    /* =======================
       SAVE MODAL (POST)
    ======================= */
    const bindSave = () => {
        $("#kt_method_edit_form").on("submit", function (e) {
            e.preventDefault();

            // ✅ DEMO BLOCK
            if (isDemoMode()) {
                demoBlockAlert();
                return;
            }

            const id = $("#method_id").val();
            const form = $(this);

            $.ajax({
                url: base_url + "admin/earning-methods/save/" + id,
                type: "POST",
                data: form.serialize(),
                success: function (res) {
                    try { res = typeof res === "string" ? JSON.parse(res) : res; } catch (e) { }

                    if (!res || !res.status) {
                        Swal.fire("Error", (res && res.message) ? res.message : "Update failed", "error");
                        return;
                    }

                    Swal.fire({
                        text: res.message || "Updated",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" }
                    });

                    const modalEl = document.getElementById("kt_modal_edit_method");
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    if (dt) dt.ajax.reload(null, false);
                },
                error: function () {
                    Swal.fire("Error", "Update failed", "error");
                },
            });
        });
    };

    document.addEventListener("DOMContentLoaded", function () {
        initTable();
        bindStatusToggle();
        bindEdit();
        bindSave();
    });

})();
