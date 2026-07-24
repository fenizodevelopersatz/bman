
// var KTSigninGeneral = function() {
//     var t, e, r;
//     return {
//         init: function() {
//             t = document.querySelector("#kt_account_meta_details_form");
//             e = document.querySelector("#kt_account_meta_details_submit");

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     min_password_length: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Minimum password length is required."
//                             },
//                             numeric: {
//                                 message: "Only numbers are allowed."
//                             },
//                             greaterThan: {
//                                 min: 4,
//                                 message: "Password length  must be greater than 4."
//                             }
//                         }
//                     },
//                     max_password_length: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Maximum password length is required."
//                             },
//                             numeric: {
//                                 message: "Only numbers are allowed."
//                             },
//                             greaterThan: {
//                                 min: 4,
//                                 message: "Password must be greater than 4."
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


//             e.addEventListener("click", function(i) {
//                 i.preventDefault(); 

//                 r.validate().then(function(status) {
//                     if (status === "Valid") {
//                         e.setAttribute("data-kt-indicator", "on"); 
//                         var formData = new FormData(t);

//                         axios.post(t.getAttribute("action"), formData)
//                             .then(function(response) {
//                                 var res = response.data;

//                                 if (res.status) {
//                                     Swal.fire({
//                                         text: "User Settings Updated Successfully!",
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     }).then(function(e) {
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
//                             .catch(function(error) {
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
//                             .finally(function() {
//                                 e.removeAttribute("data-kt-indicator");
//                                 e.disabled = false;
//                             });

//                         // Swal.fire({
//                         // icon: 'info',
//                         // title: 'Demo Version',
//                         // text: 'You Can not change record.',
//                         // confirmButtonText: 'Ok, got it!',
//                         // customClass: {
//                         // confirmButton: 'btn btn-primary'
//                         // },
//                         // buttonsStyling: false
//                         // });

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


// KTUtil.onDOMContentLoaded(function() {
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

    var KTSigninGeneral = function () {
        var t, e, r;

        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");

                if (!t || !e) return;

                r = FormValidation.formValidation(t, {
                    fields: {
                        min_password_length: {
                            validators: {
                                notEmpty: { message: "Minimum password length is required." },
                                numeric: { message: "Only numbers are allowed." },
                                greaterThan: { min: 4, message: "Password length  must be greater than 4." }
                            }
                        },
                        max_password_length: {
                            validators: {
                                notEmpty: { message: "Maximum password length is required." },
                                numeric: { message: "Only numbers are allowed." },
                                greaterThan: { min: 4, message: "Password must be greater than 4." }
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
                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);
                        var redirectUrl = t.getAttribute("data-kt-redirect-url") || "";

                        axios.post(t.getAttribute("action"), formData)
                            .then(function (response) {
                                var res = response.data;

                                if (res && res.status) {
                                    Swal.fire({
                                        text: "User Settings Updated Successfully!",
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

})();

(function () {
    const toggle = document.getElementById('admin_twofa_login');
    const modalEl = document.getElementById('adminTwofaSetupModal');
    const confirmBtn = document.getElementById('admin-twofa-confirm');
    const codeInput = document.getElementById('admin-twofa-code');
    if (!toggle || !modalEl || !confirmBtn || !codeInput) return;

    const modal = new bootstrap.Modal(modalEl);
    const loading = document.getElementById('admin-twofa-loading');
    const setup = document.getElementById('admin-twofa-setup');
    const qr = document.getElementById('admin-twofa-qr');
    const secretEl = document.getElementById('admin-twofa-secret');
    const message = document.getElementById('admin-twofa-message');
    let setupConfirmed = toggle.checked;

    toggle.addEventListener('change', function () {
        if (!toggle.checked || setupConfirmed) return;
        toggle.checked = false;
        loading.classList.remove('d-none');
        setup.classList.add('d-none');
        confirmBtn.disabled = true;
        codeInput.value = '';
        message.textContent = '';
        modal.show();

        axios.post(base_url + 'admin/settings/twofa/setup-request', new FormData())
            .then(function (response) {
                const res = response.data || {};
                if (!res.status) throw new Error(res.message || 'Could not generate the setup key.');
                const uri = 'otpauth://totp/' + encodeURIComponent(res.issuer + ':' + res.account) +
                    '?secret=' + encodeURIComponent(res.secret) +
                    '&issuer=' + encodeURIComponent(res.issuer);
                qr.innerHTML = '';
                new QRCode(qr, {
                    text: uri,
                    width: 200,
                    height: 200,
                    colorDark: '#111827',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
                secretEl.textContent = res.secret;
                loading.classList.add('d-none');
                setup.classList.remove('d-none');
                codeInput.focus();
            })
            .catch(function (error) {
                modal.hide();
                Swal.fire({
                    text: error.message || 'Could not start two-factor setup.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    });

    codeInput.addEventListener('input', function () {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
        confirmBtn.disabled = codeInput.value.length !== 6;
        message.textContent = '';
    });

    confirmBtn.addEventListener('click', function () {
        const data = new FormData();
        data.append('code', codeInput.value);
        confirmBtn.disabled = true;
        confirmBtn.setAttribute('data-kt-indicator', 'on');

        axios.post(base_url + 'admin/settings/twofa/setup-verify', data)
            .then(function (response) {
                const res = response.data || {};
                if (!res.status) throw new Error(res.message || 'Verification failed.');
                setupConfirmed = true;
                toggle.checked = true;
                message.className = 'mt-3 text-success';
                message.textContent = res.message;
                setTimeout(function () { modal.hide(); }, 600);
            })
            .catch(function (error) {
                message.className = 'mt-3 text-danger';
                message.textContent = (error.response && error.response.data && error.response.data.message)
                    || error.message || 'Invalid authenticator code.';
                confirmBtn.disabled = false;
            })
            .finally(function () {
                confirmBtn.removeAttribute('data-kt-indicator');
            });
    });
})();
