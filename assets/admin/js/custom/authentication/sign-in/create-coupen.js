
// var KTSigninGeneral = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_coupon_form");
//             e = document.querySelector("#kt_coupon_form_button");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     code: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Coupon code is required'
//                             },
//                             stringLength: {
//                                 min: 3,
//                                 max: 50,
//                                 message: 'Coupon code must be between 3 and 50 characters'
//                             }
//                         }
//                     },
//                     discount_type: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Discount type is required'
//                             }
//                         }
//                     },
//                     discount_value: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Discount value is required'
//                             },
//                             numeric: {
//                                 message: 'Must be a valid number'
//                             },
//                             greaterThan: {
//                                 min: 0,
//                                 message: 'Must be greater than zero'
//                             }
//                         }
//                     },
//                     min_order_amount: {
//                         validators: {
//                             numeric: {
//                                 message: 'Must be a valid number'
//                             }
//                         }
//                     },
//                     max_discount: {
//                         validators: {
//                             numeric: {
//                                 message: 'Must be a valid number'
//                             }
//                         }
//                     },
//                     usage_limit: {
//                         validators: {
//                             integer: {
//                                 message: 'Must be a whole number'
//                             },
//                             greaterThan: {
//                                 min: 0,
//                                 message: 'Cannot be negative'
//                             }
//                         }
//                     },
//                     usage_per_user: {
//                         validators: {
//                             integer: {
//                                 message: 'Must be a whole number'
//                             },
//                             greaterThan: {
//                                 min: 0,
//                                 message: 'Cannot be negative'
//                             }
//                         }
//                     },
//                     valid_from: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Valid from date is required'
//                             },
//                             date: {
//                                 format: 'YYYY-MM-DD',
//                                 message: 'Invalid date format (YYYY-MM-DD)'
//                             }
//                         }
//                     },
//                     valid_to: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Valid to date is required'
//                             },
//                             date: {
//                                 format: 'YYYY-MM-DD',
//                                 message: 'Invalid date format (YYYY-MM-DD)'
//                             }
//                         }
//                     },
//                     status: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Please select status'
//                             }
//                         }
//                     }
//                 },
//                 plugins: {
//                     trigger: new FormValidation.plugins.Trigger(),
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
//                                             var redirectUrl = t.getAttribute("data-kt-redirect-url");
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

(function () {
    /* =======================
       DEMO MODE HELPERS
    ======================= */
    function isDemoMode() {
        return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true);
    }

    function demoBlockAlert(msg) {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: msg || "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false
        });
    }

    var KTSigninGeneral = function () {
        var t, e, r;

        return {
            init: function () {
                t = document.querySelector("#kt_coupon_form");
                e = document.querySelector("#kt_coupon_form_button");
                if (!t || !e) return;

                r = FormValidation.formValidation(t, {
                    fields: {
                        code: {
                            validators: {
                                notEmpty: { message: "Coupon code is required" },
                                stringLength: {
                                    min: 3,
                                    max: 50,
                                    message: "Coupon code must be between 3 and 50 characters"
                                }
                            }
                        },
                        discount_type: {
                            validators: {
                                notEmpty: { message: "Discount type is required" }
                            }
                        },
                        discount_value: {
                            validators: {
                                notEmpty: { message: "Discount value is required" },
                                numeric: { message: "Must be a valid number" },
                                greaterThan: { min: 0, message: "Must be greater than zero" }
                            }
                        },
                        min_order_amount: {
                            validators: {
                                numeric: { message: "Must be a valid number" }
                            }
                        },
                        max_discount: {
                            validators: {
                                numeric: { message: "Must be a valid number" }
                            }
                        },
                        usage_limit: {
                            validators: {
                                integer: { message: "Must be a whole number" },
                                greaterThan: { min: 0, message: "Cannot be negative" }
                            }
                        },
                        usage_per_user: {
                            validators: {
                                integer: { message: "Must be a whole number" },
                                greaterThan: { min: 0, message: "Cannot be negative" }
                            }
                        },
                        valid_from: {
                            validators: {
                                notEmpty: { message: "Valid from date is required" },
                                date: { format: "YYYY-MM-DD", message: "Invalid date format (YYYY-MM-DD)" }
                            }
                        },
                        valid_to: {
                            validators: {
                                notEmpty: { message: "Valid to date is required" },
                                date: { format: "YYYY-MM-DD", message: "Invalid date format (YYYY-MM-DD)" }
                            }
                        },
                        status: {
                            validators: {
                                notEmpty: { message: "Please select status" }
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

                        // ✅ DEMO BLOCK (no API call)
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not change record.");
                            return;
                        }

                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);

                        axios
                            .post(t.getAttribute("action"), formData)
                            .then(function (response) {
                                var res = response.data;

                                if (res && res.status) {
                                    Swal.fire({
                                        text: res.message || "Saved",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" }
                                    }).then(function (x) {
                                        if (!x.isConfirmed) return;
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    });
                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + ((res && res.message) ? res.message : "Failed"),
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
})();
