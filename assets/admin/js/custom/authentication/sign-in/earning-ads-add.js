// "use strict";

// (function () {
//     const form = document.getElementById("kt_earning_ads_form");
//     const submitBtn = document.getElementById("kt_earning_ads_submit");

//     if (!form) return;

//     const redirectUrl = form.getAttribute("data-kt-redirect-url") || "";

//     form.addEventListener("submit", function (e) {
//         e.preventDefault();

//         submitBtn.setAttribute("data-kt-indicator", "on");
//         submitBtn.disabled = true;

//         const formData = new FormData(form);

//         fetch(form.getAttribute("action"), {
//             method: "POST",
//             body: formData,
//         })
//             .then((r) => r.json())
//             .then((res) => {
//                 submitBtn.removeAttribute("data-kt-indicator");
//                 submitBtn.disabled = false;

//                 if (res && res.status) {
//                     Swal.fire({
//                         text: res.message || "Saved",
//                         icon: "success",
//                         buttonsStyling: false,
//                         confirmButtonText: "Ok",
//                         customClass: { confirmButton: "btn btn-primary" },
//                     }).then(() => {
//                         if (redirectUrl) window.location.href = redirectUrl;
//                     });
//                 } else {
//                     Swal.fire({
//                         text: (res && res.message) ? res.message : "Validation error",
//                         icon: "error",
//                         buttonsStyling: false,
//                         confirmButtonText: "Ok",
//                         customClass: { confirmButton: "btn btn-primary" },
//                     });
//                 }
//             })
//             .catch(() => {
//                 submitBtn.removeAttribute("data-kt-indicator");
//                 submitBtn.disabled = false;

//                 Swal.fire({
//                     text: "Server error",
//                     icon: "error",
//                     buttonsStyling: false,
//                     confirmButtonText: "Ok",
//                     customClass: { confirmButton: "btn btn-primary" },
//                 });
//             });
//     });
// })();



"use strict";

(function () {

    // ✅ DEMO HELPERS (move to common.js later)
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

    const form = document.getElementById("kt_earning_ads_form");
    const submitBtn = document.getElementById("kt_earning_ads_submit");

    if (!form) return;

    const redirectUrl = form.getAttribute("data-kt-redirect-url") || "";

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        // ✅ DEMO BLOCK (stop before fetch)
        if (isDemoMode()) {
            demoBlockAlert();
            return;
        }

        submitBtn.setAttribute("data-kt-indicator", "on");
        submitBtn.disabled = true;

        const formData = new FormData(form);

        fetch(form.getAttribute("action"), {
            method: "POST",
            body: formData,
        })
            .then((r) => r.json())
            .then((res) => {
                submitBtn.removeAttribute("data-kt-indicator");
                submitBtn.disabled = false;

                if (res && res.status) {
                    Swal.fire({
                        text: res.message || "Saved",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" },
                    }).then(() => {
                        if (redirectUrl) window.location.href = redirectUrl;
                    });
                } else {
                    Swal.fire({
                        text: (res && res.message) ? res.message : "Validation error",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" },
                    });
                }
            })
            .catch(() => {
                submitBtn.removeAttribute("data-kt-indicator");
                submitBtn.disabled = false;

                Swal.fire({
                    text: "Server error",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-primary" },
                });
            });
    });

})();
