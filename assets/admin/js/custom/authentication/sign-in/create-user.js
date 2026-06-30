
// var KTSigninGeneral = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_account_meta_details_form");
//             e = document.querySelector("#kt_account_meta_details_submit");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     selected_members: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Sponser is required."
//                             },
//                         }
//                     },
//                     username: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The username is Required"
//                             },
//                             regexp: {
//                                 regexp: /^[A-Za-z\s]+$/,
//                                 message: "The username must only contain letters"
//                             }
//                         }
//                     },
//                     select_lg: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Leg is Required"
//                             }
//                         }
//                     },
//                     useremail: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The email is required"
//                             },
//                             regexp: {
//                                 regexp: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
//                                 message: "Enter a valid email address"
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
//                 i.preventDefault(); // Prevent the default form submission

//                 // Validate the form
//                 r.validate().then(function (status) {
//                     if (status === "Valid") {
//                         e.setAttribute("data-kt-indicator", "on"); // Show loading indicator
//                         e.disabled = true; // Disable the button to prevent multiple submissions

//                         // Create a FormData object and append the CKEditor content
//                         var formData = new FormData(t);

//                         // Send the form data via axios
//                         axios.post(t.getAttribute("action"), formData)
//                             .then(function (response) {
//                                 var res = response.data;

//                                 // Check the response status
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
//                                             // If you need to redirect after successful update
//                                             if (redirectUrl) {
//                                                 location.href = redirectUrl; // Redirect to the provided URL
//                                             }
//                                         }
//                                     });
//                                 } else {
//                                     Swal.fire({
//                                         text: "Sorry, " + res.message, // Display error message
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
//                                 // Handle errors in case of request failure
//                                 Swal.fire({
//                                     text: error.message || error, // Show the error message
//                                     icon: "error",
//                                     buttonsStyling: false,
//                                     confirmButtonText: "Ok, got it!",
//                                     customClass: {
//                                         confirmButton: "btn btn-primary"
//                                     }
//                                 });
//                             })
//                             .finally(function () {
//                                 // Remove the loading indicator and re-enable the button
//                                 e.removeAttribute("data-kt-indicator");
//                                 e.disabled = false;
//                             });
//                     } else {
//                         // Handle invalid form
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

    var KTSigninGeneral = function () {
        var t, e, r;

        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");

                if (!t || !e) return;

                r = FormValidation.formValidation(t, {
                    fields: {
                        selected_members: {
                            validators: {
                                notEmpty: { message: "Sponser is required." },
                            },
                        },
                        username: {
                            validators: {
                                notEmpty: { message: "The username is Required" },
                                regexp: {
                                    // ✅ allow letters + spaces (no numbers/symbols)
                                    regexp: /^[A-Za-z\s]+$/,
                                    message: "The username must only contain letters",
                                },
                            },
                        },
                        select_lg: {
                            validators: {
                                notEmpty: { message: "Leg is Required" },
                            },
                        },
                        useremail: {
                            validators: {
                                notEmpty: { message: "The email is required" },
                                regexp: {
                                    regexp: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                                    message: "Enter a valid email address",
                                },
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

                        // ✅ DEMO MODE STOP
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not create/update member in demo mode.");
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
                                        text: res.message || "Updated successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    }).then(function (result) {
                                        if (!result.isConfirmed) return;

                                        // ✅ fixed: redirectUrl was missing in your code
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    });
                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + (res?.message || "Something went wrong!"),
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    });
                                }
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    text:
                                        error?.response?.data?.message ||
                                        error?.message ||
                                        "Request failed",
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
    }();

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
