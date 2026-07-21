"use strict";
var KTSigninGeneral = function () {
    var t, e, r;
    return {
        init: function () {
            t = document.querySelector("#kt_sign_in_form"),
                e = document.querySelector("#kt_sign_in_submit"),
                r = FormValidation.formValidation(t, {
                    fields: {
                        useremail: {
                            validators: {
                                regexp: {
                                    regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                    message: "The value is not a valid email address"
                                },
                                notEmpty: {
                                    message: "Email address is required"
                                }
                            }
                        },
                        password: {
                            validators: {
                                notEmpty: {
                                    message: "The password is required"
                                }
                            }
                        }
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger,
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: ".fv-row",
                            eleInvalidClass: "",
                            eleValidClass: ""
                        })
                    }
                }),

                e.addEventListener("click", (function (i) {
                    i.preventDefault();

                    // Clear any server-side errors from a previous attempt so a new
                    // submit never stacks a stale message on top of the current one.
                    t.querySelectorAll(".invalid-feedback.server-error").forEach(function (el) { el.remove(); });
                    t.querySelectorAll(".is-invalid").forEach(function (el) { el.classList.remove("is-invalid"); });
                    var _oldGeneral = t.querySelector("#login-error-general");
                    if (_oldGeneral) _oldGeneral.remove();

                    r.validate().then((function (r) {
                            if ("Valid" == r) {
                                e.setAttribute("data-kt-indicator", "on"),
                                    e.disabled = !0;

                                axios.post(e.closest("form").getAttribute("action"), new FormData(t))
                                    .then((function (response) {
                                        if (response.data.status === false) {
                                            // Show errors ON THE FORM ONLY — no popup.
                                            let errors = response.data.errors;

                                            // Clear previous server-side error markers first.
                                            t.querySelectorAll(".invalid-feedback.server-error").forEach(function (el) { el.remove(); });
                                            t.querySelectorAll(".is-invalid").forEach(function (el) { el.classList.remove("is-invalid"); });
                                            let oldGeneral = t.querySelector("#login-error-general");
                                            if (oldGeneral) oldGeneral.remove();

                                            let generalMessages = [];

                                            if (typeof errors === "string") {
                                                // Single message, e.g. wrong password / deactivated account.
                                                generalMessages.push(errors);
                                            } else if (errors && typeof errors === "object") {
                                                Object.keys(errors).forEach(function (field) {
                                                    let errorMessage = errors[field];
                                                    let inputField = t.querySelector('[name="' + field + '"]');
                                                    if (inputField) {
                                                        let errorDiv = document.createElement("div");
                                                        errorDiv.classList.add("invalid-feedback", "server-error");
                                                        errorDiv.style.display = "block";
                                                        errorDiv.textContent = errorMessage;
                                                        inputField.classList.add("is-invalid");
                                                        inputField.parentNode.appendChild(errorDiv);
                                                    } else {
                                                        generalMessages.push(errorMessage);
                                                    }
                                                });
                                            }

                                            // Field-less messages go into a small inline banner at the
                                            // top of the form (still on the form, not a popup).
                                            if (generalMessages.length) {
                                                let box = document.createElement("div");
                                                box.id = "login-error-general";
                                                box.className = "alert alert-danger py-2 px-3 mb-4";
                                                box.style.display = "block";
                                                box.textContent = generalMessages.join(" ");
                                                t.prepend(box);
                                            }

                                        } else {
                                            t.reset();

                                            // The server always decides where a successful login
                                            // goes next: /user/verify when a second factor is
                                            // required, /user/main otherwise. There is no valid
                                            // "success but stay on this page" outcome, so a missing
                                            // redirect is a bug — log it loudly instead of silently
                                            // reloading the login form (which looks like nothing
                                            // happened).
                                            const redirectUrl = response.data.redirect;

                                            if (!redirectUrl) {
                                                console.error("Login succeeded but the server did not return a redirect URL.", response.data);
                                                Swal.fire({
                                                    text: "Logged in, but couldn't determine where to send you. Please contact support.",
                                                    icon: "warning",
                                                    buttonsStyling: !1,
                                                    confirmButtonText: "Ok, got it!",
                                                    customClass: {
                                                        confirmButton: "btn btn-primary"
                                                    }
                                                });
                                                return;
                                            }

                                            location.href = redirectUrl;
                                        }
                                    }))
                                    .catch((function () {
                                        Swal.fire({
                                            text: "Sorry, looks like there are some errors detected, please try again.",
                                            icon: "error",
                                            buttonsStyling: !1,
                                            confirmButtonText: "Ok, got it!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        });
                                    }))
                                    .finally(() => {
                                        e.removeAttribute("data-kt-indicator");
                                        e.disabled = !1;
                                    });
                            } else {
                                // Client-side validation already shows inline messages
                                // under each field — no popup needed.
                            }
                        }));
                }));
        }
    }
}();

KTUtil.onDOMContentLoaded((function () {


    let emailvalidestep = 0;
    let twovalidestep = 0;

    let emailOTP = 0;
    let twofaOTP = 0;

    document.querySelectorAll(".otp-container").forEach((otpContainer) => {
        const inputs = otpContainer.querySelectorAll(".fa-code");
        const loader = otpContainer.querySelector(".otp-loader");
        const messageBox = otpContainer.querySelector(".otp-message");

        inputs.forEach((input, index) => {
            input.addEventListener("paste", function (event) {
                event.preventDefault();
                const pasteData = (event.clipboardData || window.clipboardData).getData("text");

                const digits = pasteData.replace(/\D/g, "").slice(0, inputs.length);

                if (digits.length) {
                    digits.split("").forEach((digit, idx) => {
                        if (inputs[idx]) {
                            inputs[idx].value = digit;
                        }
                    });

                    if (inputs[digits.length]) {
                        inputs[digits.length].focus();
                    } else {
                        inputs[inputs.length - 1].focus();
                    }
                    verifyOTP(otpContainer, inputs, 'twofa');
                }
            });

            input.addEventListener("copy", function (event) {
                event.preventDefault();
            });

            input.addEventListener("input", function () {
                this.value = this.value.replace(/\D/g, "").slice(0, 1);
                const nextInput = inputs[index + 1];

                if (this.value.length === 1 && nextInput) {
                    nextInput.focus();
                } else if (!nextInput) {
                    verifyOTP(otpContainer, inputs, 'twofa');
                }
            });

            input.addEventListener("keydown", function (event) {
                if (event.key === "Backspace" && this.value.length === 0) {
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                    }
                }

                if (event.key === "Enter") {
                    verifyOTP(otpContainer, inputs, 'twofa');
                }
            });
        });

        function verifyOTP(otpContainer, inputs, otpmethod) {
            const otpValue = Array.from(inputs).map(input => input.value).join("");
            if (otpValue.length < inputs.length) return;

            const loader = otpContainer.querySelector(".otp-loader");
            const messageBox = otpContainer.querySelector(".otp-message");

            loader.classList.remove("d-none");
            messageBox.textContent = "";

            fetch(base_url + "/user/login-otp-verify", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: JSON.stringify({ otp: otpValue, method: otpmethod })
            })
                .then(response => response.json())
                .then(data => {
                    loader.classList.add("d-none");
                    if (data.status) {
                        twovalidestep++
                        messageBox.textContent = "✅ Valid OTP!";
                        messageBox.style.color = "green";
                        check_button();
                        twofaOTP = otpValue;
                    } else {
                        if (twovalidestep > 0) {
                            twovalidestep--
                        }
                        messageBox.textContent = "❌ Wrong Two-Factor Code!";
                        messageBox.style.color = "red";
                        twofaOTP = 0;
                    }
                })
                .catch(error => {
                    loader.classList.add("d-none");
                    messageBox.textContent = "⚠️ Error verifying Two-Factor Code!";
                    messageBox.style.color = "orange";
                    console.error("OTP Verification Error:", error);
                });
        }

    });


    document.querySelectorAll(".otp-container").forEach((otpContainer) => {
        const inputs = otpContainer.querySelectorAll(".email-code");
        const loader = otpContainer.querySelector(".otp-loader");
        const messageBox = otpContainer.querySelector(".otp-message");

        inputs.forEach((input, index) => {
            // Handle Pasting OTP
            input.addEventListener("paste", function (event) {
                event.preventDefault();
                const pasteData = (event.clipboardData || window.clipboardData).getData("text");

                const digits = pasteData.replace(/\D/g, "").slice(0, inputs.length);

                if (digits.length) {
                    digits.split("").forEach((digit, idx) => {
                        if (inputs[idx]) {
                            inputs[idx].value = digit;
                        }
                    });

                    if (inputs[digits.length]) {
                        inputs[digits.length].focus();
                    } else {
                        inputs[inputs.length - 1].focus();
                    }
                    verifyOTP(otpContainer, inputs, 'email_otp');
                }
            });

            // Prevent Copying OTP
            input.addEventListener("copy", function (event) {
                event.preventDefault();
            });

            // Handle Manual Input (Move Focus)
            input.addEventListener("input", function () {
                this.value = this.value.replace(/\D/g, "").slice(0, 1); // Allow only one digit
                const nextInput = inputs[index + 1];

                if (this.value.length === 1 && nextInput) {
                    nextInput.focus();
                } else if (!nextInput) {
                    verifyOTP(otpContainer, inputs, 'email_otp');
                }
            });

            // Handle Backspace (Move Focus Back)
            input.addEventListener("keydown", function (event) {
                if (event.key === "Backspace" && this.value.length === 0) {
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                    }
                }

                // **🔥 Handle Enter Key Press**
                if (event.key === "Enter") {
                    verifyOTP(otpContainer, inputs, 'email_otp');
                }
            });
        });

        function verifyOTP(otpContainer, inputs, otpmethod) {
            const otpValue = Array.from(inputs).map(input => input.value).join("");
            if (otpValue.length < inputs.length) return;

            const loader = otpContainer.querySelector(".otp-loader");
            const messageBox = otpContainer.querySelector(".otp-message");

            loader.classList.remove("d-none");
            messageBox.textContent = "";

            fetch(base_url + "/user/login-otp-verify", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: JSON.stringify({ otp: otpValue, method: otpmethod })
            })
                .then(response => response.json())
                .then(data => {
                    loader.classList.add("d-none");
                    if (data.status) {
                        emailvalidestep++
                        messageBox.textContent = "✅ Valid OTP!";
                        messageBox.style.color = "green";
                        check_button();
                        emailOTP = otpValue;
                    } else {
                        if (emailvalidestep > 0) {
                            emailvalidestep--
                        }
                        messageBox.textContent = "❌ Wrong OTP!";
                        messageBox.style.color = "red";
                        emailOTP = 0;
                    }
                })
                .catch(error => {
                    loader.classList.add("d-none");
                    messageBox.textContent = "⚠️ Error verifying OTP!";
                    messageBox.style.color = "orange";
                    console.error("OTP Verification Error:", error);
                });
        }

    });

    function check_button() {
        const needEmail = !!document.querySelector('.email-code');
        const needTwofa = !!document.querySelector('.fa-code');
        const emailOk = !needEmail || emailvalidestep > 0;
        const twofaOk = !needTwofa || twovalidestep > 0;
        if (emailOk && twofaOk) {
            $('#kt_sing_in_two_factor_submit').removeAttr('disabled');
        }
    }


    $('#kt_sing_in_two_factor_form').on('submit', function (e) {
        e.preventDefault();

        let form = e.target;
        let submitButton = $('#kt_sing_in_two_factor_submit');

        const needEmail = !!document.querySelector('.email-code');
        const needTwofa = !!document.querySelector('.fa-code');

        // Prevent submission if validations fail
        if ((needEmail && emailvalidestep === 0) || (needTwofa && twovalidestep === 0)) {
            Swal.fire({
                text: "Please complete all verification steps!",
                icon: "warning",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-warning"
                }
            });
            return;
        }

        submitButton.attr("data-kt-indicator", "on").prop("disabled", true);

        var button = document.getElementById("kt_sing_in_two_factor_submit");

        let data = new FormData();
        data.append('emailOTP', emailOTP);
        data.append('twofaOTP', twofaOTP);

        axios.post(form.getAttribute("action"), data)
            .then(function (responses) {
                let response = responses.data;

                if (response.status) {
                    Swal.fire({
                        text: "Login Verification Successfully",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (e) {
                        var redirectUrl = button.getAttribute("data-kt-redirect-url");
                        if (e.isConfirmed && typeof redirectUrl !== "undefined") {
                            location.href = redirectUrl;
                        }
                    });
                } else if (response.max_attempts_exceeded) {
                    Swal.fire({
                        text: response.message,
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (e) {
                        if (e.isConfirmed && typeof response.redirect !== "undefined") {
                            location.href = response.redirect;
                        }
                    });
                } else {
                    Swal.fire({
                        text: "Sorry, " + response.message,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    text: "An error occurred: " + error.message,
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            })
            .finally(function () {
                submitButton.removeAttr("data-kt-indicator").prop("disabled", false);
            });
    });

    KTSigninGeneral.init()
}));
