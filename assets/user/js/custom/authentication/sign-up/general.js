// "use strict";
// var KTSignupGeneral = function() {
//     var e, t, r, a, s = function() {
//         return a.getScore() > 50
//     };
//     return {
//         init: function() {
//             e = document.querySelector("#kt_sign_up_form"), t = document.querySelector("#kt_sign_up_submit"), a = KTPasswordMeter.getInstance(e.querySelector('[data-kt-password-meter="true"]')), ! function(e) {
//                 try {
//                     return new URL(e), !0
//                 } catch (e) {
//                     return !1
//                 }
//             }(t.closest("form").getAttribute("action")) ? (r = FormValidation.formValidation(e, {
//                 fields: {
//                      sponsor_id: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Sponser is required."
//                             },
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
//                     },
//                     password: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The password is required"
//                             },
//                             callback: {
//                                 message: "Please enter valid password",
//                                 callback: function(e) {
//                                     if (e.value.length > 0) return s()
//                                 }
//                             }
//                         }
//                     },
//                     "confirm-password": {
//                         validators: {
//                             notEmpty: {
//                                 message: "The password confirmation is required"
//                             },
//                             identical: {
//                                 compare: function() {
//                                     return e.querySelector('[name="password"]').value
//                                 },
//                                 message: "The password and its confirm are not the same"
//                             }
//                         }
//                     },
//                     toc: {
//                         validators: {
//                             notEmpty: {
//                                 message: "You must accept the terms and conditions"
//                             }
//                         }
//                     }
//                 },
//                 plugins: {
//                     trigger: new FormValidation.plugins.Trigger({
//                         event: {
//                             password: !1
//                         }
//                     }),
//                     bootstrap: new FormValidation.plugins.Bootstrap5({
//                         rowSelector: ".fv-row",
//                         eleInvalidClass: "",
//                         eleValidClass: ""
//                     })
//                 }
//             }), t.addEventListener("click", (function(s) {
//                 s.preventDefault(), r.revalidateField("password"), r.validate().then((function(r) {
//                     "Valid" == r ? (t.setAttribute("data-kt-indicator", "on"), t.disabled = !0, setTimeout((function() {
//                         t.removeAttribute("data-kt-indicator"), t.disabled = !1, Swal.fire({
//                             text: "You have successfully reset your password!",
//                             icon: "success",
//                             buttonsStyling: !1,
//                             confirmButtonText: "Ok, got it!",
//                             customClass: {
//                                 confirmButton: "btn btn-primary"
//                             }
//                         }).then((function(t) {
//                             if (t.isConfirmed) {
//                                 e.reset(), a.reset();
//                                 var r = e.getAttribute("data-kt-redirect-url");
//                                 // r && (location.href = r)
//                             }
//                         }))
//                     }), 1500)) : Swal.fire({
//                         text: "Sorry, looks like there are some errors detected, please try again.",
//                         icon: "error",
//                         buttonsStyling: !1,
//                         confirmButtonText: "Ok, got it!",
//                         customClass: {
//                             confirmButton: "btn btn-primary"
//                         }
//                     })
//                 }))
//             })), e.querySelector('input[name="password"]').addEventListener("input", (function() {
//                 this.value.length > 0 && r.updateFieldStatus("password", "NotValidated")
//             }))) : (r = FormValidation.formValidation(e, {
//                 fields: {
//                     name: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Name is required"
//                             }
//                         }
//                     },
//                     email: {
//                         validators: {
//                             regexp: {
//                                 regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
//                                 message: "The value is not a valid email address"
//                             },
//                             notEmpty: {
//                                 message: "Email address is required"
//                             }
//                         }
//                     },
//                     password: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The password is required"
//                             },
//                             callback: {
//                                 message: "Please enter valid password",
//                                 callback: function(e) {
//                                     if (e.value.length > 0) return s()
//                                 }
//                             }
//                         }
//                     },
//                     password_confirmation: {
//                         validators: {
//                             notEmpty: {
//                                 message: "The password confirmation is required"
//                             },
//                             identical: {
//                                 compare: function() {
//                                     return e.querySelector('[name="password"]').value
//                                 },
//                                 message: "The password and its confirm are not the same"
//                             }
//                         }
//                     },
//                     toc: {
//                         validators: {
//                             notEmpty: {
//                                 message: "You must accept the terms and conditions"
//                             }
//                         }
//                     }
//                 },
//                 plugins: {
//                     trigger: new FormValidation.plugins.Trigger({
//                         event: {
//                             password: !1
//                         }
//                     }),
//                     bootstrap: new FormValidation.plugins.Bootstrap5({
//                         rowSelector: ".fv-row",
//                         eleInvalidClass: "",
//                         eleValidClass: ""
//                     })
//                 }
//             }), t.addEventListener("click", (function(a) {
//                 a.preventDefault(), r.revalidateField("password"), r.validate().then((function(r) {
//                     "Valid" == r ? (t.setAttribute("data-kt-indicator", "on"), t.disabled = !0, axios.post(t.closest("form").getAttribute("action"), new FormData(e)).then((function(t) {
//                         if (t) {
//                             e.reset();
//                             const t = e.getAttribute("data-kt-redirect-url");
//                             t && (location.href = t)
//                         } else Swal.fire({
//                             text: "Sorry, looks like there are some errors detected, please try again.",
//                             icon: "error",
//                             buttonsStyling: !1,
//                             confirmButtonText: "Ok, got it!",
//                             customClass: {
//                                 confirmButton: "btn btn-primary"
//                             }
//                         })
//                     })).catch((function(e) {
//                         Swal.fire({
//                             text: "Sorry, looks like there are some errors detected, please try again.",
//                             icon: "error",
//                             buttonsStyling: !1,
//                             confirmButtonText: "Ok, got it!",
//                             customClass: {
//                                 confirmButton: "btn btn-primary"
//                             }
//                         })
//                     })).then((() => {
//                         t.removeAttribute("data-kt-indicator"), t.disabled = !1
//                     }))) : Swal.fire({
//                         text: "Sorry, looks like there are some errors detected, please try again.",
//                         icon: "error",
//                         buttonsStyling: !1,
//                         confirmButtonText: "Ok, got it!",
//                         customClass: {
//                             confirmButton: "btn btn-primary"
//                         }
//                     })
//                 }))
//             })), e.querySelector('input[name="password"]').addEventListener("input", (function() {
//                 this.value.length > 0 && r.updateFieldStatus("password", "NotValidated")
//             })))
//         }
//     }
// }();
// KTUtil.onDOMContentLoaded((function() {
//     KTSignupGeneral.init()
// }));

"use strict";
var KTSignupGeneral = function () {
    var e, t, r, a;

    var isPasswordStrong = function () {
        return a.getScore() > 50;
    };

    var isValidURL = function (url) {
        try {
            new URL(url, window.location.origin); // allow relative URLs
            return true;
        } catch {
            return false;
        }
    };

    return {
        init: function () {
            e = document.querySelector("#kt_sign_up_form");
            t = document.querySelector("#kt_sign_up_submit");
            a = KTPasswordMeter.getInstance(e.querySelector('[data-kt-password-meter="true"]'));

            const formAction = e.getAttribute("action");
            const isActionValid = isValidURL(formAction);

            r = FormValidation.formValidation(e, {
                fields: {
                    sponsor_id: {
                        validators: {
                            notEmpty: {
                                message: "Sponsor is required."
                            }
                        }
                    },
                    useremail: {
                        validators: {
                            notEmpty: {
                                message: "Email is required"
                            },
                            regexp: {
                                regexp: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                                message: "Enter a valid email address"
                            }
                        }
                    },
                    password: {
                        validators: {
                            notEmpty: {
                                message: "Password is required"
                            },
                            callback: {
                                message: "Please enter a valid password",
                                callback: function (field) {
                                    return field.value.length > 0 ? isPasswordStrong() : false;
                                }
                            }
                        }
                    },
                    "confirm-password": {
                        validators: {
                            notEmpty: {
                                message: "Password confirmation is required"
                            },
                            identical: {
                                compare: function () {
                                    return e.querySelector('[name="password"]').value;
                                },
                                message: "Passwords do not match"
                            }
                        }
                    },
                    toc: {
                        validators: {
                            notEmpty: {
                                message: "You must accept the terms and conditions"
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger({
                        event: {
                            password: false
                        }
                    }),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: ".fv-row",
                        eleInvalidClass: "",
                        eleValidClass: ""
                    })
                }
            });

            t.addEventListener("click", function (s) {
                s.preventDefault();

                r.revalidateField("password");
                r.validate().then(function (status) {
                    if (status === "Valid") {
                        t.setAttribute("data-kt-indicator", "on");
                        t.disabled = true;

                        axios.post(formAction, new FormData(e))
                            .then(function (response) {

                                const res = response.data;

                                if (res.status) {

                                    e.reset();
                                    a.reset();

                                    Swal.fire({
                                        text: res.message,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(function(result) {
                                          const redirect = e.getAttribute("data-kt-redirect-url");
                                        if (result.isConfirmed && redirect) {
                                            window.location.href = redirect;
                                        }
                                    });

                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + res.message, // Display error message
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });
                                }
                                
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    text: "Sorry, there was an error submitting the form.",
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });
                            })
                            .finally(function () {
                                t.removeAttribute("data-kt-indicator");
                                t.disabled = false;
                            });
                    } else {
                        Swal.fire({
                            text: "Please fix the form errors and try again.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    }
                });
            });

            // Reset password validation state on input
            e.querySelector('input[name="password"]').addEventListener("input", function () {
                if (this.value.length > 0) {
                    r.updateFieldStatus("password", "NotValidated");
                }
            });
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTSignupGeneral.init();
});
