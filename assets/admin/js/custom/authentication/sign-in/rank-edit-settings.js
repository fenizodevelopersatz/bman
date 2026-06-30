// document.addEventListener("DOMContentLoaded", function () {
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");
//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         rank_name: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The Rank Name is Required"
//                                 },
//                                 regexp: {
//                                     regexp: /^[A-Za-z\s]+$/,
//                                     message: "The Rank Name must only contain letters"
//                                 }
//                             }
//                         },
//                         rank_eligibel_amt: {
//                             validators: Object.assign({
//                                 notEmpty: {
//                                     message: "The Rank Eligible Amount is Required"
//                                 },
//                                 regexp: {
//                                     regexp: /^\d+(\.\d+)?$/,
//                                     message: "Only numbers and decimal values are allowed"
//                                 }
//                             }, rank_id === "1" ? {
//                                 greaterThan: {
//                                     min: 100, // Set the minimum amount required for rank 1
//                                     message: "The Rank Eligible Amount must be at least 100 for Rank 1"
//                                 }
//                             } : {})
//                         },
//                         rank_bonus: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The Rank Bonus is Required"
//                                 },
//                                 regexp: {
//                                     regexp: /^\d+(\.\d+)?$/,
//                                     message: "Only numbers and decimal values are allowed"
//                                 }
//                             }
//                         }
//                     },
//                     plugins: {
//                         trigger: new FormValidation.plugins.Trigger(),
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
//                                             text: res.message,
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

/* =======================
   DEMO MODE (GLOBAL)
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

// returns true if blocked
function stopIfDemo(e) {
    if (!isDemoMode()) return false;
    if (e && typeof e.preventDefault === "function") e.preventDefault();
    demoBlockAlert();
    return true;
}

/* =======================
   YOUR FORM SCRIPT
======================= */
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
                        rank_name: {
                            validators: {
                                notEmpty: { message: "The Rank Name is Required" },
                                regexp: {
                                    regexp: /^[A-Za-z\s]+$/,
                                    message: "The Rank Name must only contain letters"
                                }
                            }
                        },
                        rank_eligibel_amt: {
                            validators: Object.assign({
                                notEmpty: { message: "The Rank Eligible Amount is Required" },
                                regexp: {
                                    regexp: /^\d+(\.\d+)?$/,
                                    message: "Only numbers and decimal values are allowed"
                                }
                            }, (typeof rank_id !== "undefined" && String(rank_id) === "1") ? {
                                greaterThan: {
                                    min: 100,
                                    message: "The Rank Eligible Amount must be at least 100 for Rank 1"
                                }
                            } : {})
                        },
                        rank_bonus: {
                            validators: {
                                notEmpty: { message: "The Rank Bonus is Required" },
                                regexp: {
                                    regexp: /^\d+(\.\d+)?$/,
                                    message: "Only numbers and decimal values are allowed"
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

                        // ✅ DEMO BLOCK (stop before axios)
                        if (stopIfDemo(i)) return;

                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);
                        var redirectUrl = t.getAttribute("data-kt-redirect-url");

                        axios.post(t.getAttribute("action"), formData)
                            .then(function (response) {
                                var res = response.data;

                                if (res && res.status) {
                                    Swal.fire({
                                        text: res.message,
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

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
