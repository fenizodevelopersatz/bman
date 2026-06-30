// $(document).ready(function () {
//     function isDemoMode() {
//         console.log('isDemoMode()', window.APP_CONFIG, !!(window.APP_CONFIG && window.APP_CONFIG.DEMO));
//         return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO);
//     }

//     function demoBlockAlert() {
//         if (!isDemoMode()) return;
//         Swal.fire({
//             icon: 'info',
//             title: 'Demo Version',
//             text: 'You Can not change record.',
//             confirmButtonText: 'Ok, got it!',
//             customClass: {
//                 confirmButton: 'btn btn-primary'
//             },
//             buttonsStyling: false
//         });
//     }

//     $('#kt_account_profile_details_form').on('submit', function (e) {
//         e.preventDefault();

//         var isValid = true;
//         var allowedExtensions = ['png', 'jpg', 'jpeg'];

//         function validateFile(fileInput) {
//             var file = fileInput.files[0];
//             if (file) {
//                 var fileName = file.name;
//                 var fileExtension = fileName.split('.').pop().toLowerCase();
//                 if (allowedExtensions.indexOf(fileExtension) === -1) {
//                     Swal.fire({
//                         text: "Sorry, Invalid file type. Allowed types: png, jpg, jpeg.",
//                         icon: "error",
//                         buttonsStyling: false,
//                         confirmButtonText: "Ok, got it!",
//                         customClass: {
//                             confirmButton: "btn btn-primary"
//                         }
//                     });

//                     return false;
//                 }
//             }
//             return true;
//         }

//         var headerLogoInput = $('input[name="header_log"]');
//         var footerLogoInput = $('input[name="footer_log"]');
//         var favLogoInput = $('input[name="fav_logo"]');

//         if (!validateFile(headerLogoInput[0]) || !validateFile(footerLogoInput[0]) || !validateFile(favLogoInput[0])) {
//             isValid = false;
//         }

//         if (!isValid) {
//             return;
//         }

//         var formData = new FormData(this);

//         console.log('isDemoMode()', isDemoMode());

//         demoBlockAlert();

//         $.ajax({
//             url: $(this).attr('action'),
//             type: 'POST',
//             data: formData,
//             processData: false,
//             contentType: false,
//             success: function (response) {

//                 Swal.fire({
//                     text: "Logo Image Update Successfully!",
//                     icon: "success",
//                     buttonsStyling: false,
//                     confirmButtonText: "Ok, got it!",
//                     customClass: {
//                         confirmButton: "btn btn-primary"
//                     }
//                 }).then(function (e) {
//                     if (e.isConfirmed) {
//                         var redirectUrl = t.getAttribute("data-kt-redirect-url");
//                         if (redirectUrl) {
//                             location.href = redirectUrl;
//                         }
//                     }
//                 });

//             },
//             error: function (xhr, status, error) {
//                 Swal.fire({
//                     text: "Sorry, " + error,
//                     icon: "error",
//                     buttonsStyling: false,
//                     confirmButtonText: "Ok, got it!",
//                     customClass: {
//                         confirmButton: "btn btn-primary"
//                     }
//                 });
//             }
//         });
//     });
// });


// var KTSigninGeneral = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_account_meta_details_form");
//             e = document.querySelector("#kt_account_meta_details_submit");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     site_name: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The company name is required"
//                             }
//                         }
//                     },
//                     site_url: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The site url is required"
//                             }
//                         }
//                     },
//                     site_title: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The site title is required"
//                             }
//                         }
//                     },
//                     meta_keyword: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The meta keyword is required"
//                             }
//                         }
//                     },
//                     meta_discription: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The meta discription is required"
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

//                         if (!isDemoMode()) return;
//                         demoBlockAlert();

//                         axios.post(t.getAttribute("action"), new FormData(t))
//                             .then(function (responses) {
//                                 // t.reset(); 
//                                 var response = responses.data;

//                                 if (response.status) {

//                                     Swal.fire({
//                                         text: "Meta Details Update Successfully!",
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
//                                         text: "Sorry, " + response.message,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });

//                                 }

//                             })
//                             .catch(error => {
//                                 Swal.fire({
//                                     text: error,
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

//                     }
//                 });
//             });
//         }
//     }
// }();



// var KTSigninGeneralcontact = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_account_contact_details_form");
//             e = document.querySelector("#kt_account_contact_details_submit");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     contact_email: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The company email is required"
//                             }
//                         }
//                     },
//                     contact_number: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The site number is required"
//                             }
//                         }
//                     }
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

//                         if (!isDemoMode()) return;
//                         demoBlockAlert();

//                         axios.post(t.getAttribute("action"), new FormData(t))
//                             .then(function (responses) {
//                                 // t.reset(); 
//                                 var response = responses.data;

//                                 if (response.status) {

//                                     Swal.fire({
//                                         text: "Contact details update successfully!",
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
//                                         text: "Sorry, " + response.message,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });

//                                 }

//                             })
//                             .catch(error => {
//                                 Swal.fire({
//                                     text: error,
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

//                     }
//                 });
//             });
//         }
//     }
// }();




// var KTSigninGeneralconfig = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#website_meta_details_form");
//             e = document.querySelector("#kt_account_config_details_submit");

//             r = FormValidation.formValidation(t, {
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


//                         if (!isDemoMode()) return;
//                         demoBlockAlert();

//                         axios.post(t.getAttribute("action"), new FormData(t))
//                             .then(function (responses) {
//                                 // t.reset(); 
//                                 var response = responses.data;

//                                 if (response.status) {

//                                     Swal.fire({
//                                         text: "Config details update successfully!",
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
//                                         text: "Sorry, " + response.message,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });

//                                 }

//                             })
//                             .catch(error => {
//                                 Swal.fire({
//                                     text: error,
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

//                     }
//                 });
//             });
//         }
//     }
// }();


// KTUtil.onDOMContentLoaded(function () {
//     KTSigninGeneral.init();
//     KTSigninGeneralcontact.init();
//     KTSigninGeneralconfig.init();
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

// Use this guard inside any submit/click that should be blocked in demo
function stopIfDemo(e) {
    if (!isDemoMode()) return false; // not demo, allow
    if (e && typeof e.preventDefault === "function") e.preventDefault();
    demoBlockAlert();
    return true; // demo blocked
}

/* =======================
   PROFILE LOGO FORM (JQ)
======================= */
$(document).ready(function () {
    $('#kt_account_profile_details_form').on('submit', function (e) {
        e.preventDefault();

        // ✅ if demo -> stop here
        if (stopIfDemo(e)) return;

        var allowedExtensions = ['png', 'jpg', 'jpeg'];

        function validateFile(fileInput) {
            var file = fileInput.files[0];
            if (file) {
                var fileName = file.name;
                var fileExtension = fileName.split('.').pop().toLowerCase();
                if (allowedExtensions.indexOf(fileExtension) === -1) {
                    Swal.fire({
                        text: "Sorry, Invalid file type. Allowed types: png, jpg, jpeg.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                    return false;
                }
            }
            return true;
        }

        var headerLogoInput = $('input[name="header_log"]')[0];
        var footerLogoInput = $('input[name="footer_log"]')[0];
        var favLogoInput = $('input[name="fav_logo"]')[0];

        if (!validateFile(headerLogoInput) || !validateFile(footerLogoInput) || !validateFile(favLogoInput)) {
            return;
        }

        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                Swal.fire({
                    text: "Logo Image Update Successfully!",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: { confirmButton: "btn btn-primary" }
                }).then(function (x) {
                    if (x.isConfirmed) {
                        // optional redirect if your form has data-kt-redirect-url
                        var redirectUrl = document.querySelector("#kt_account_profile_details_form")?.getAttribute("data-kt-redirect-url");
                        if (redirectUrl) location.href = redirectUrl;
                    }
                });
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    text: "Sorry, " + error,
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: { confirmButton: "btn btn-primary" }
                });
            }
        });
    });
});


/* =======================
   META DETAILS (FORMVALIDATION + AXIOS)
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
                    site_name: { validators: { notEmpty: { message: "The company name is required" } } },
                    site_url: { validators: { notEmpty: { message: "The site url is required" } } },
                    site_title: { validators: { notEmpty: { message: "The site title is required" } } },
                    meta_keyword: { validators: { notEmpty: { message: "The meta keyword is required" } } },
                    meta_discription: { validators: { notEmpty: { message: "The meta discription is required" } } },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger,
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
                    if (status !== "Valid") return;

                    // ✅ demo block
                    if (stopIfDemo(i)) return;

                    e.setAttribute("data-kt-indicator", "on");
                    e.disabled = true;

                    axios.post(t.getAttribute("action"), new FormData(t))
                        .then(function (responses) {
                            var response = responses.data;

                            if (response && response.status) {
                                Swal.fire({
                                    text: "Meta Details Update Successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(function (x) {
                                    if (x.isConfirmed) {
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    text: "Sorry, " + (response && response.message ? response.message : "Unknown error"),
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


/* =======================
   CONTACT DETAILS
======================= */
var KTSigninGeneralcontact = function () {
    var t, e, r;

    return {
        init: function () {
            t = document.querySelector("#kt_account_contact_details_form");
            e = document.querySelector("#kt_account_contact_details_submit");

            if (!t || !e) return;

            r = FormValidation.formValidation(t, {
                fields: {
                    contact_email: { validators: { notEmpty: { message: "The company email is required" } } },
                    contact_number: { validators: { notEmpty: { message: "The site number is required" } } }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger,
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
                    if (status !== "Valid") return;

                    // ✅ demo block
                    if (stopIfDemo(i)) return;

                    e.setAttribute("data-kt-indicator", "on");
                    e.disabled = true;

                    axios.post(t.getAttribute("action"), new FormData(t))
                        .then(function (responses) {
                            var response = responses.data;

                            if (response && response.status) {
                                Swal.fire({
                                    text: "Contact details update successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(function (x) {
                                    if (x.isConfirmed) {
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    text: "Sorry, " + (response && response.message ? response.message : "Unknown error"),
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


/* =======================
   CONFIG DETAILS
======================= */
var KTSigninGeneralconfig = function () {
    var t, e, r;

    return {
        init: function () {
            t = document.querySelector("#website_meta_details_form");
            e = document.querySelector("#kt_account_config_details_submit");

            if (!t || !e) return;

            r = FormValidation.formValidation(t, {
                plugins: {
                    trigger: new FormValidation.plugins.Trigger,
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
                    if (status !== "Valid") return;

                    // ✅ demo block
                    if (stopIfDemo(i)) return;

                    e.setAttribute("data-kt-indicator", "on");
                    e.disabled = true;

                    axios.post(t.getAttribute("action"), new FormData(t))
                        .then(function (responses) {
                            var response = responses.data;

                            if (response && response.status) {
                                Swal.fire({
                                    text: "Config details update successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" }
                                }).then(function (x) {
                                    if (x.isConfirmed) {
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (redirectUrl) location.href = redirectUrl;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    text: "Sorry, " + (response && response.message ? response.message : "Unknown error"),
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
    KTSigninGeneralcontact.init();
    KTSigninGeneralconfig.init();
});
