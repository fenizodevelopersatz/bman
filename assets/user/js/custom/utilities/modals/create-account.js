"use strict";

// Store selected package data
let selectedPackageData = {};

// Package Selection Handler
document.querySelectorAll('input[name="package_id"]').forEach((input) => {
    input.addEventListener('change', function () {
        const label = this.nextElementSibling;
        const name = label.querySelector('.text-gray-900')?.textContent.trim() || '';
        const roiText = label.querySelectorAll('.text-muted')[0]?.textContent.trim() || '';
        const minText = label.querySelectorAll('.text-muted')[1]?.textContent.trim() || '';
        const maxText = label.querySelectorAll('.text-muted')[2]?.textContent.trim() || '';

        selectedPackageData = {
            name,
            roi: roiText,
            minimum: minText,
            maximum: maxText
        };


      document.getElementById('selected_package_details').innerHTML = `
        <strong>Name:</strong> ${selectedPackageData.name}<br>
        <strong>ROI:</strong> ${selectedPackageData.roi}<br>
        <strong>Minimum:</strong> ${selectedPackageData.minimum}<br>
        <strong>Maximum:</strong> ${selectedPackageData.maximum}
        `;

    });
});

// KTCreateAccount Stepper Logic
var KTCreateAccount = function () {
    let modal, stepperEl, form, submitBtn, backBtn, nextBtn, stepper, validations = [];

    return {
        init: function () {
            modal = document.querySelector("#kt_modal_create_account");
            stepperEl = document.querySelector("#kt_create_account_stepper");

            if (!stepperEl) return;

            form = stepperEl.querySelector("#kt_create_account_form");
            submitBtn = stepperEl.querySelector('[data-kt-stepper-action="submit"]');
            nextBtn = stepperEl.querySelector('[data-kt-stepper-action="next"]');
            backBtn = stepperEl.querySelector('[data-kt-stepper-action="previous"]');
            stepper = new KTStepper(stepperEl);

            // Stepper change event
            stepper.on("kt.stepper.changed", function () {
                const step = stepper.getCurrentStepIndex();
                if (step === 3) {
                    submitBtn.classList.remove("d-none");
                    submitBtn.classList.add("d-inline-block");
                    nextBtn.classList.add("d-none");

                    const payment = document.querySelector('input[name="payment_option"]:checked')?.nextElementSibling;
                    const paymentMethod = payment?.querySelector('.text-gray-900')?.textContent.trim() || 'Not selected';

                    const amount = document.querySelector('input[name="lending_amount"]').value;

                    const summaryHtml = `
                        <strong>Package Name:</strong> ${selectedPackageData.name}<br>
                        <strong>ROI:</strong> ${selectedPackageData.roi}<br>
                        <strong>Min:</strong> ${selectedPackageData.minimum}<br>
                        <strong>Max:</strong> ${selectedPackageData.maximum}<br>
                        <strong>Lending Amount:</strong> ${amount}<br>
                        <strong>Payment Method:</strong> ${paymentMethod}
                    `;

                    document.getElementById("summary_section").innerHTML = summaryHtml;
                    
                } else if (step === 4) {
                    submitBtn.classList.add("d-none");
                    nextBtn.classList.add("d-none");
                    backBtn.classList.add("d-none");
                } else {
                    submitBtn.classList.remove("d-inline-block", "d-none");
                    nextBtn.classList.remove("d-none");
                }
            });

            // Stepper next event
            stepper.on("kt.stepper.next", function (stepObj) {
                console.log("stepper.next");
                const currentValidation = validations[stepObj.getCurrentStepIndex() - 1];
                if (currentValidation) {
                    currentValidation.validate().then(function (status) {
                        if (status === "Valid") {
                            stepObj.goNext();
                            KTUtil.scrollTop();
                        } else {
                            Swal.fire({
                                text: "Sorry, looks like there are some errors detected, please try again.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: { confirmButton: "btn btn-light" }
                            }).then(() => KTUtil.scrollTop());
                        }
                    });
                } else {
                    stepObj.goNext();
                    KTUtil.scrollTop();
                }
            });

            // Stepper previous event
            stepper.on("kt.stepper.previous", function (stepObj) {
                console.log("stepper.previous");
                stepObj.goPrevious();
                KTUtil.scrollTop();
            });

            // Validations
            validations.push(FormValidation.formValidation(form, {
                fields: {
                    package_id: {
                        validators: {
                            notEmpty: {
                                message: "Please choose a package"
                            }
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
            }));

          validations.push(
            FormValidation.formValidation(form, {
                fields: {
                    // Step 2 field: lending_amount
                   lending_amount: {
                            validators: {
                                notEmpty: {
                                    message: "Please enter an amount"
                                },
                                numeric: {
                                    message: "Only numbers and decimal values are allowed"
                                },
                                callback: {
                                    message: "Amount is not within the package range",
                                    callback: function (input) {
                                        const selectedPackageRadio = document.querySelector('input[name="package_id"]:checked');
                                        const selectedPackageId = selectedPackageRadio ? selectedPackageRadio.value : null;
                                        const enteredAmount = parseFloat(input.value);

                                        if (!selectedPackageId) {
                                            return {
                                                valid: false,
                                                message: "Please select a package first"
                                            };
                                        }

                                        return new Promise(function (resolve) {
                                            $.ajax({
                                                url: base_url + "validate-package-amount",
                                                type: "POST",
                                                dataType: "json",
                                                data: {
                                                    package_id: selectedPackageId,
                                                    amount: enteredAmount
                                                },
                                                success: function (response) {
                                                    if (response.status) {
                                                        resolve({ valid: true });
                                                    } else {
                                                        resolve({
                                                            valid: false,
                                                            message: response.message
                                                        });
                                                    }
                                                },
                                                error: function () {
                                                    resolve({
                                                        valid: false,
                                                        message: "Error in validation, please try again"
                                                    });
                                                }
                                            });
                                        });
                                    }
                                }
                            }
                        },
                         payment_option: {
                            validators: {
                                notEmpty: {
                                    message: "Please choose a payment options"
                                }
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
            })
        );


            // Final submit
            // submitBtn.addEventListener("click", function (e) {
            //     console.log(validations);
            //     validations[1].validate().then(function (status) {
            //         if (status === "Valid") {
            //             e.preventDefault();
            //             submitBtn.disabled = true;
            //             submitBtn.setAttribute("data-kt-indicator", "on");
            //             setTimeout(function () {
            //                 submitBtn.removeAttribute("data-kt-indicator");
            //                 submitBtn.disabled = false;
            //                 stepper.goNext();
            //             }, 2000);
            //         } else {
            //             Swal.fire({
            //                 text: "Sorry, looks like there are some errors detected, please try again.",
            //                 icon: "error",
            //                 buttonsStyling: false,
            //                 confirmButtonText: "Ok, got it!",
            //                 customClass: { confirmButton: "btn btn-light" }
            //             }).then(() => KTUtil.scrollTop());
            //         }
            //     });
            // });
            const t = document.querySelector('#kt_create_account_form');
            submitBtn.addEventListener("click", function (e) {
            e.preventDefault();
            validations[1].validate().then(function (status) {
                if (status === "Valid") {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute("data-kt-indicator", "on");

             
                    var formData = new FormData(t); 
                    axios.post(t.getAttribute("action"), formData)
                        .then(function (response) {
                            var res = response.data;

                            if (res.status) {

                            // Case: Stripe or PayPal redirect
                            if (res.redirect_url) {
                                window.location.href = res.redirect_url;
                                return;
                            }

                            // Case: PayPal returns form HTML
                            if (res.paypal_html) {
                                const paypalWrapper = document.createElement('div');
                                paypalWrapper.innerHTML = res.paypal_html;
                                document.body.appendChild(paypalWrapper);

                                const form = document.getElementById("paypal_form");
                                if (form) {
                                    form.submit();
                                }

                                return;
                            }

                            // Optional: Stepper flow (if not external redirect)
                            Swal.fire({
                                text: res.message,
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            }).then(function () {
                                submitBtn.removeAttribute("data-kt-indicator");
                                submitBtn.disabled = false;
                                stepper.goNext();
                            });
                               

                                setTimeout(function () {
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                                }, 2000);

                                    
                            } else {

                                    setTimeout(function () {
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                                }, 2000);

                                Swal.fire({
                                    text: "Sorry, " + res.message,
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

                                setTimeout(function () {
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                                }, 2000);

                            Swal.fire({
                                text: error.message || "An unexpected error occurred.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        })
                        .finally(function () {
                            submitBtn.removeAttribute("data-kt-indicator");
                            submitBtn.disabled = false;
                        });

                } else {
                    setTimeout(function () {
                                submitBtn.removeAttribute("data-kt-indicator");
                                submitBtn.disabled = false;
                            }, 2000);
                    Swal.fire({
                        text: "Sorry, looks like there are some errors detected, please try again.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-light"
                        }
                    }).then(() => KTUtil.scrollTop());
                }
            });
        });


            if (modal) new bootstrap.Modal(modal);
        }
    };
}();

// Init on DOM Ready
KTUtil.onDOMContentLoaded(() => {
    KTCreateAccount.init();
});
