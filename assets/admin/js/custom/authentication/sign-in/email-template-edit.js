// document.addEventListener("DOMContentLoaded", function () {
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");

//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         mail_subject: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The mail subject is Required"
//                                 },
//                                 regexp: {
//                                     regexp: /^[A-Za-z\s]+$/,
//                                     message: "The mail subject must only contain letters"
//                                 }
//                             }
//                         },
//                         mail_content: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "The Mail Content is Required"
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
//                             e.setAttribute("data-kt-indicator", "on");
//                             e.disabled = true;
//                             var formData = new FormData(t);

//                             // Swal.fire({
//                             //     icon: 'info',
//                             //     title: 'Demo Version',
//                             //     text: 'You Can not change record.',
//                             //     confirmButtonText: 'Ok, got it!',
//                             //     customClass: {
//                             //         confirmButton: 'btn btn-primary'
//                             //     },
//                             //         buttonsStyling: false
//                             //     });

//                             axios.post(t.getAttribute("action"), formData)
//                                 .then(function (response) {
//                                     var res = response.data;

//                                     // Check the response status
//                                     if (res.status) {
//                                         Swal.fire({
//                                             text: "Email Template Update",
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

                // ✅ If you use CKEditor/ClassicEditor for mail_content, uncomment and use:
                // let mailEditor;
                // if (window.ClassicEditor && document.querySelector("#mail_content")) {
                //   ClassicEditor.create(document.querySelector("#mail_content"))
                //     .then((editor) => { mailEditor = editor; })
                //     .catch(console.error);
                // }

                r = FormValidation.formValidation(t, {
                    fields: {
                        mail_subject: {
                            validators: {
                                notEmpty: { message: "The mail subject is Required" },
                                // ✅ letters + numbers + space + basic symbols (better for real subjects)
                                regexp: {
                                    regexp: /^[A-Za-z0-9\s._\-()]+$/,
                                    message: "Only letters/numbers and basic symbols are allowed",
                                },
                            },
                        },
                        mail_content: {
                            validators: {
                                notEmpty: { message: "The Mail Content is Required" },
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

                        // ✅ DEMO BLOCK
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not change record.");
                            return;
                        }

                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);

                        // ✅ If using CKEditor:
                        // if (mailEditor) {
                        //   formData.set("mail_content", mailEditor.getData());
                        // }

                        axios
                            .post(t.getAttribute("action"), formData)
                            .then(function (response) {
                                var res = response.data;

                                if (res && res.status) {
                                    Swal.fire({
                                        text: res.message || "Email Template Updated Successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    }).then(function (result) {
                                        if (!result.isConfirmed) return;

                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    });
                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + ((res && res.message) ? res.message : "Update failed"),
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
