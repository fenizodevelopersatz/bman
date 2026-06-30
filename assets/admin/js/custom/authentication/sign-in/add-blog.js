// document.addEventListener("DOMContentLoaded", function () {
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");

//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         title: {
//                             validators: {
//                                 notEmpty: {
//                                     message: 'The blog title is required'
//                                 },
//                                 regexp: {
//                                     regexp: /^[A-Za-z0-9\s\-,.()'"!?]+$/,
//                                     message: 'The title may contain letters, numbers and basic punctuation'
//                                 }
//                             }
//                         },
//                         category: {
//                             validators: {
//                                 notEmpty: {
//                                     message: 'Please select a category'
//                                 }
//                             }
//                         },
//                         status: {
//                             validators: {
//                                 notEmpty: {
//                                     message: 'Please select status'
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

//                             for (instance in CKEDITOR.instances) {
//                                 CKEDITOR.instances[instance].updateElement();
//                             }

//                             e.setAttribute("data-kt-indicator", "on");
//                             e.disabled = true;
//                             var formData = new FormData(t);

//                             Swal.fire({
//                                 icon: 'info',
//                                 title: 'Demo Version',
//                                 text: 'You Can not change record.',
//                                 confirmButtonText: 'Ok, got it!',
//                                 customClass: {
//                                     confirmButton: 'btn btn-primary'
//                                 },
//                                 buttonsStyling: false
//                             });

//                             axios.post(t.getAttribute("action"), formData)
//                                 .then(function (response) {
//                                     var res = response.data;

//                                     // Check the response status
//                                     if (res.status) {
//                                         Swal.fire({
//                                             text: "Blog Update Successfully",
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
//                                                     location.href = redirectUrl;
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

document.addEventListener("DOMContentLoaded", function () {
    // ---------------------------
    // ✅ DEMO MODE HELPERS (optional)
    // ---------------------------
    function isDemoMode() {
        return !!(
            (window.APP_CONFIG && window.APP_CONFIG.DEMO === true) ||
            window.DEMOVERSION === true
        );
    }

    function demoBlockAlert(msg) {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: msg || "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false,
        });
    }

    var KTSigninGeneral = (function () {
        var t, e, r;

        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");
                if (!t || !e) return;

                r = FormValidation.formValidation(t, {
                    fields: {
                        title: {
                            validators: {
                                notEmpty: { message: "The blog title is required" },
                                regexp: {
                                    regexp: /^[A-Za-z0-9\s\-,.()'"!?]+$/,
                                    message:
                                        "The title may contain letters, numbers and basic punctuation",
                                },
                            },
                        },
                        category: {
                            validators: {
                                notEmpty: { message: "Please select a category" },
                            },
                        },
                        status: {
                            validators: {
                                notEmpty: { message: "Please select status" },
                            },
                        },
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: ".fv-row",
                            eleInvalidClass: "",
                            eleValidClass: "",
                        }),
                    },
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
                                customClass: { confirmButton: "btn btn-warning" },
                            });
                            return;
                        }

                        // ✅ Update CKEditor content (if any)
                        if (window.CKEDITOR && CKEDITOR.instances) {
                            for (var instance in CKEDITOR.instances) {
                                if (Object.prototype.hasOwnProperty.call(CKEDITOR.instances, instance)) {
                                    CKEDITOR.instances[instance].updateElement();
                                }
                            }
                        }

                        // ✅ DEMO MODE BLOCK (stop submit)
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
                                        text: "Blog Update Successfully",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    }).then(function (x) {
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (x.isConfirmed && redirectUrl) location.href = redirectUrl;
                                    });
                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + ((res && res.message) ? res.message : "Failed"),
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    });
                                }
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    text: (error && error.message) ? error.message : String(error),
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });
                            })
                            .finally(function () {
                                e.removeAttribute("data-kt-indicator");
                                e.disabled = false;
                            });
                    });
                });
            },
        };
    })();

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
