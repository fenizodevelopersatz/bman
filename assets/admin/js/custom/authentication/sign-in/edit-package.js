// document.addEventListener("DOMContentLoaded", function () {
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");

//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         package_name: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The package name is required"
//                                 },
//                                 regexp: {
//                                     regexp: /^[A-Za-z\s]+$/,
//                                     message: "Only letters and spaces are allowed"
//                                 }
//                             }
//                         },
//                         minimum: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The minimum amount is required"
//                                 },
//                                 integer: {
//                                     message: "Only integers are allowed"
//                                 },
//                                 greaterThan: {
//                                     min: 1,
//                                     message: "The minimum amount must be greater than 0"
//                                 }
//                             }
//                         },
//                         maximum: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The maximum amount is required"
//                                 },
//                                 integer: {
//                                     message: "Only integers are allowed"
//                                 }
//                             }
//                         },
//                         period: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The package period required"
//                                 }
//                             }
//                         },
//                         roi: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The ROI is required"
//                                 },
//                                 numeric: {
//                                     message: "Only numbers and decimal values are allowed"
//                                 }
//                             }
//                         },
//                         roi_made_by: {

//                             validators: {
//                                 notEmpty: {
//                                     message: "The ROI  type is required"
//                                 }
//                             }
//                         },
//                         duration: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The package duration is required"
//                                 },
//                                 integer: {
//                                     message: "Only integers are allowed"
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

//                 // Prevent invalid input while typing
//                 document.querySelector("[name='package_name']").addEventListener("input", function () {
//                     this.value = this.value.replace(/[^A-Za-z\s]/g, ''); // Allows only letters and spaces
//                 });

//                 document.querySelector("[name='minimum']").addEventListener("input", function () {
//                     this.value = this.value.replace(/[^0-9]/g, ''); // Allows only numbers
//                 });

//                 document.querySelector("[name='maximum']").addEventListener("input", function () {
//                     this.value = this.value.replace(/[^0-9]/g, ''); // Allows only numbers
//                 });

//                 document.querySelector("[name='roi']").addEventListener("input", function () {
//                     this.value = this.value.replace(/[^0-9.]/g, ''); // Allows only numbers and decimal point
//                 });

//                 document.querySelector("[name='duration']").addEventListener("input", function () {
//                     this.value = this.value.replace(/[^0-9]/g, ''); // Allows only numbers
//                 });

//                 e.addEventListener("click", function (i) {
//                     i.preventDefault();

//                     r.validate().then(function (status) {
//                         if (status === "Valid") {
//                             e.setAttribute("data-kt-indicator", "on");
//                             e.disabled = true;

//                             var formData = new FormData(t);


//                             // Swal.fire({
//                             // icon: 'info',
//                             // title: 'Demo Version',
//                             // text: 'You Can not change record.',
//                             // confirmButtonText: 'Ok, got it!',
//                             // customClass: {
//                             //     confirmButton: 'btn btn-primary'
//                             // },
//                             //     buttonsStyling: false
//                             // });


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
                        package_name: {
                            validators: {
                                notEmpty: { message: "The package name is required" },
                                regexp: {
                                    regexp: /^[A-Za-z\s]+$/,
                                    message: "Only letters and spaces are allowed"
                                }
                            }
                        },
                        minimum: {
                            validators: {
                                notEmpty: { message: "The minimum amount is required" },
                                integer: { message: "Only integers are allowed" },
                                greaterThan: { min: 1, message: "The minimum amount must be greater than 0" }
                            }
                        },
                        maximum: {
                            validators: {
                                notEmpty: { message: "The maximum amount is required" },
                                integer: { message: "Only integers are allowed" }
                            }
                        },
                        period: {
                            validators: {
                                notEmpty: { message: "The package period required" }
                            }
                        },
                        roi: {
                            validators: {
                                notEmpty: { message: "The ROI is required" },
                                numeric: { message: "Only numbers and decimal values are allowed" }
                            }
                        },
                        roi_made_by: {
                            validators: {
                                notEmpty: { message: "The ROI type is required" }
                            }
                        },
                        duration: {
                            validators: {
                                notEmpty: { message: "The package duration is required" },
                                integer: { message: "Only integers are allowed" }
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

                // Prevent invalid input while typing (safe with null checks)
                const pkg = document.querySelector("[name='package_name']");
                if (pkg) pkg.addEventListener("input", function () {
                    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
                });

                const min = document.querySelector("[name='minimum']");
                if (min) min.addEventListener("input", function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                const max = document.querySelector("[name='maximum']");
                if (max) max.addEventListener("input", function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                const roi = document.querySelector("[name='roi']");
                if (roi) roi.addEventListener("input", function () {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                });

                const dur = document.querySelector("[name='duration']");
                if (dur) dur.addEventListener("input", function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
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

                        // ✅ DEMO BLOCK (IMPORTANT: stop before axios)
                        if (stopIfDemo(i)) return;

                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);

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
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (x.isConfirmed && redirectUrl) location.href = redirectUrl;
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
        }
    }();

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
