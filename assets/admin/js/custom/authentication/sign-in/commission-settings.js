
// var KTSigninGeneral = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_account_meta_details_form");
//             e = document.querySelector("#kt_account_meta_details_submit");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     direct_commission: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Direct Commission is required."
//                             },
//                             numeric: {
//                                 message: "Only numbers are allowed."
//                             },
//                             greaterThan: {
//                                 min: 0,
//                                 message: "Direct Commission must be greater than 0."
//                             }
//                         }
//                     },
//                     "level_commission[]": {
//                         validators: {
//                             notEmpty: {
//                                 message: "Level Commission is required."
//                             },
//                             numeric: {
//                                 message: "Only numbers are allowed."
//                             },
//                             greaterThan: {
//                                 min: -1,
//                                 message: "Level Commission must be 0 or graterthen 0 ."
//                             }
//                         }
//                     },
//                 },
//                 plugins: {
//                     trigger: new FormValidation.plugins.Trigger,
//                     bootstrap: new FormValidation.plugins.Bootstrap5({
//                         rowSelector: ".fv-row",
//                         eleInvalidClass: "",
//                         eleValidClass: ""
//                     })
//                 }
//             });

//             e.addEventListener("click", function (i) {
//                 i.preventDefault();

//                 r.validate().then(function (status) {
//                     if (status === "Valid") {
//                         e.setAttribute("data-kt-indicator", "on");
//                         e.disabled = true;

//                         var formData = new FormData(t);

//                         // Swal.fire({
//                         // icon: 'info',
//                         // title: 'Demo Version',
//                         // text: 'You Can not change record.',
//                         // confirmButtonText: 'Ok, got it!',
//                         // customClass: {
//                         //     confirmButton: 'btn btn-primary'
//                         // },
//                         //     buttonsStyling: false
//                         // });


//                         axios.post(t.getAttribute("action"), formData)
//                             .then(function (response) {
//                                 var res = response.data;

//                                 if (res.status) {
//                                     Swal.fire({
//                                         text: res.message,
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     }).then(function (e) {
//                                         if (e.isConfirmed) {
//                                             if (redirectUrl) {
//                                                 location.href = redirectUrl;
//                                             }
//                                         }
//                                     });
//                                 } else {
//                                     Swal.fire({
//                                         text: "Sorry, " + res.message,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });
//                                 }
//                             })
//                             .catch(function (error) {
//                                 Swal.fire({
//                                     text: error.message || error,
//                                     icon: "error",
//                                     buttonsStyling: false,
//                                     confirmButtonText: "Ok, got it!",
//                                     customClass: {
//                                         confirmButton: "btn btn-primary"
//                                     }
//                                 });
//                             })
//                             .finally(function () {
//                                 e.removeAttribute("data-kt-indicator");
//                                 e.disabled = false;
//                             });

//                     } else {
//                         Swal.fire({
//                             text: "Please correct the errors in the form.",
//                             icon: "warning",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok, got it!",
//                             customClass: {
//                                 confirmButton: "btn btn-warning"
//                             }
//                         });
//                     }
//                 });
//             });

//         }
//     }
// }();


// KTUtil.onDOMContentLoaded(function () {
//     KTSigninGeneral.init();
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
   FORM SCRIPT
======================= */
var KTSigninGeneral = function () {
    var t, e, r;

    return {
        init: function () {
            t = document.querySelector("#kt_account_meta_details_form");
            e = document.querySelector("#kt_account_meta_details_submit");

            if (!t || !e) return;

            r = FormValidation.formValidation(t, {
                fields: {
                    direct_commission: {
                        validators: {
                            notEmpty: { message: "Direct Commission is required." },
                            numeric: { message: "Only numbers are allowed." },
                            greaterThan: { min: 0, message: "Direct Commission must be greater than 0." }
                        }
                    },
                    "level_commission[]": {
                        validators: {
                            notEmpty: { message: "Level Commission is required." },
                            numeric: { message: "Only numbers are allowed." },
                            greaterThan: { min: -1, message: "Level Commission must be 0 or graterthen 0 ." }
                        }
                    },
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
                    var redirectUrl = t.getAttribute("data-kt-redirect-url"); // ✅ fix undefined redirectUrl

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
