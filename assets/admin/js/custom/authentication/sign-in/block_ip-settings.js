// document.addEventListener("DOMContentLoaded", function () {
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");

//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         ip_number: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The IP Number is Required"
//                                 },
//                                 regexp: {
//                                     regexp: /^(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)$/,
//                                     message: "Please enter a valid IPv4 address (e.g., 192.168.1.1)"
//                                 }
//                             }
//                         }
//                     },
//                     plugins: {
//                         trigger: new FormValidation.plugins.Trigger,
//                         bootstrap: new FormValidation.plugins.Bootstrap5({
//                             rowSelector: ".fv-row",
//                             eleInvalidClass: "",
//                             eleValidClass: ""
//                         })
//                     }
//                 });

//                 e.addEventListener("click", function (i) {
//                     i.preventDefault();

//                     r.validate().then(function (status) {
//                         if (status === "Valid") {
//                             e.setAttribute("data-kt-indicator", "on"); // Show loading indicator
//                             e.disabled = true; // Disable the button to prevent multiple submissions

//                             // Create a FormData object and append the form data
//                             var formData = new FormData(t);

//                             // Send the form data via axios
//                             axios.post(t.getAttribute("action"), formData)
//                                 .then(function (response) {
//                                     var res = response.data;

//                                     // Check the response status
//                                     if (res.status) {
//                                         Swal.fire({
//                                             text: "IP Blocked Successfully!",
//                                             icon: "success",
//                                             buttonsStyling: false,
//                                             confirmButtonText: "Ok, got it!",
//                                             customClass: {
//                                                 confirmButton: "btn btn-primary"
//                                             }
//                                         }).then(function (e) {
//                                             var redirectUrl = t.getAttribute("data-kt-redirect-url");
//                                             if (e.isConfirmed) {
//                                                 if (redirectUrl) {
//                                                     location.href = redirectUrl; // Redirect if a URL is provided
//                                                 }
//                                             }
//                                         });
//                                     } else {
//                                         Swal.fire({
//                                             text: "Sorry, " + res.message,
//                                             icon: "error",
//                                             buttonsStyling: false,
//                                             confirmButtonText: "Ok, got it!",
//                                             customClass: {
//                                                 confirmButton: "btn btn-primary"
//                                             }
//                                         });
//                                     }
//                                 })
//                                 .catch(function (error) {
//                                     Swal.fire({
//                                         text: error.message || error,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });
//                                 })
//                                 .finally(function () {
//                                     e.removeAttribute("data-kt-indicator");
//                                     e.disabled = false;
//                                 });
//                         } else {
//                             Swal.fire({
//                                 text: "Please correct the errors in the form.",
//                                 icon: "warning",
//                                 buttonsStyling: false,
//                                 confirmButtonText: "Ok, got it!",
//                                 customClass: {
//                                     confirmButton: "btn btn-warning"
//                                 }
//                             });
//                         }
//                     });
//                 });
//             }
//         }
//     }();

//     KTUtil.onDOMContentLoaded(function () {
//         KTSigninGeneral.init();
//     });
// });



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

    document.addEventListener("DOMContentLoaded", function () {

        var KTSigninGeneral = function () {
            var t, e, r;

            return {
                init: function () {
                    t = document.querySelector("#kt_account_meta_details_form");
                    e = document.querySelector("#kt_account_meta_details_submit");

                    if (!t || !e) return;

                    r = FormValidation.formValidation(t, {
                        fields: {
                            ip_number: {
                                validators: {
                                    notEmpty: {
                                        message: "The IP Number is Required"
                                    },
                                    regexp: {
                                        regexp: /^(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)$/,
                                        message: "Please enter a valid IPv4 address (e.g., 192.168.1.1)"
                                    }
                                }
                            }
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger(),
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: ""
                            })
                        }
                    });

                    e.addEventListener("click", function (i) {
                        i.preventDefault();

                        r.validate().then(function (status) {

                            if (status !== "Valid") {
                                Swal.fire({
                                    text: "Please correct the errors in the form.",
                                    icon: "warning",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-warning" }
                                });
                                return;
                            }

                            // ✅ DEMO BLOCK
                            if (isDemoMode()) {
                                demoBlockAlert();
                                return;
                            }

                            e.setAttribute("data-kt-indicator", "on");
                            e.disabled = true;

                            var formData = new FormData(t);
                            var redirectUrl = t.getAttribute("data-kt-redirect-url") || "";

                            axios.post(t.getAttribute("action"), formData)
                                .then(function (response) {
                                    var res = response.data;

                                    if (res && res.status) {
                                        Swal.fire({
                                            text: "IP Blocked Successfully!",
                                            icon: "success",
                                            buttonsStyling: false,
                                            confirmButtonText: "Ok, got it!",
                                            customClass: { confirmButton: "btn btn-primary" }
                                        }).then(function (x) {
                                            if (x.isConfirmed && redirectUrl) {
                                                location.href = redirectUrl;
                                            }
                                        });
                                    } else {
                                        Swal.fire({
                                            text: "Sorry, " + (res && res.message ? res.message : "Unknown error"),
                                            icon: "error",
                                            buttonsStyling: false,
                                            confirmButtonText: "Ok, got it!",
                                            customClass: { confirmButton: "btn btn-primary" }
                                        });
                                    }
                                })
                                .catch(function (error) {
                                    Swal.fire({
                                        text: (error && error.message) ? error.message : String(error),
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    });
                                })
                                .finally(function () {
                                    e.removeAttribute("data-kt-indicator");
                                    e.disabled = false;
                                });

                        });
                    });

                }
            };
        }();

        // keep your KTUtil hook
        KTUtil.onDOMContentLoaded(function () {
            KTSigninGeneral.init();
        });

    });

})();
