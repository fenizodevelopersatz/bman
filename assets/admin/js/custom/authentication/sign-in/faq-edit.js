document.addEventListener("DOMContentLoaded", function () {
    var form = document.querySelector("#kt_account_meta_details_form");
    var submitButton = document.querySelector("#kt_account_meta_details_submit");

    if (!form || !submitButton) {
        return;
    }

    var validator = FormValidation.formValidation(form, {
        fields: {
            faq_question: {
                validators: {
                    notEmpty: { message: "The FAQ question is required" }
                }
            },
            faq_answer: {
                validators: {
                    notEmpty: { message: "The FAQ answer is required" }
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

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        validator.validate().then(function (status) {
            if (status !== "Valid") {
                Swal.fire({
                    text: "Please enter both the FAQ question and answer.",
                    icon: "warning",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: { confirmButton: "btn btn-warning" }
                });
                return;
            }

            submitButton.setAttribute("data-kt-indicator", "on");
            submitButton.disabled = true;

            axios.post(form.getAttribute("action"), new FormData(form))
                .then(function (response) {
                    var result = response.data;

                    if (!result.status) {
                        throw new Error(result.message || "FAQ could not be saved.");
                    }

                    return Swal.fire({
                        text: result.message,
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                })
                .then(function (dialogResult) {
                    var redirectUrl = form.getAttribute("data-kt-redirect-url");
                    if (dialogResult && dialogResult.isConfirmed && redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                })
                .catch(function (error) {
                    Swal.fire({
                        text: error.message || "FAQ could not be saved.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                })
                .finally(function () {
                    submitButton.removeAttribute("data-kt-indicator");
                    submitButton.disabled = false;
                });
        });
    });
});
