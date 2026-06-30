document.addEventListener("DOMContentLoaded", function () {
    var KTSigninGeneral = function () {
        var t, e, r;
        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");

                r = FormValidation.formValidation(t, {
                    fields: {
                        announcement_content: {
                            validators: {
                                notEmpty: {
                                    message: "The Announcement Content is Required"
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
                });

                e.addEventListener("click", function (i) {
                    i.preventDefault();

                    r.validate().then(function (status) {
                        if (status === "Valid") {
                            e.setAttribute("data-kt-indicator", "on"); 
                            e.disabled = true; 
                            var formData = new FormData(t);

                                 Swal.fire({
                                icon: 'info',
                                title: 'Demo Version',
                                text: 'You Can not change record.',
                                confirmButtonText: 'Ok, got it!',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                    buttonsStyling: false
                                });
                            // axios.post(t.getAttribute("action"), formData)
                            //     .then(function (response) {
                            //         var res = response.data;

                            //         // Check the response status
                            //         if (res.status) {
                            //             Swal.fire({
                            //                 text: res.message,
                            //                 icon: "success",
                            //                 buttonsStyling: false,
                            //                 confirmButtonText: "Ok, got it!",
                            //                 customClass: {
                            //                     confirmButton: "btn btn-primary"
                            //                 }
                            //             }).then(function (e) {

                            //                 var redirectUrl = t.getAttribute("data-kt-redirect-url");
                            //                 if (e.isConfirmed) {
                            //                     if (redirectUrl) {
                            //                         location.href = redirectUrl; 
                            //                     }
                            //                 }

                            //             });
                            //         } else {
                            //             Swal.fire({
                            //                 text: "Sorry, " + res.message,
                            //                 icon: "error",
                            //                 buttonsStyling: false,
                            //                 confirmButtonText: "Ok, got it!",
                            //                 customClass: {
                            //                     confirmButton: "btn btn-primary"
                            //                 }
                            //             });
                            //         }
                            //     })
                            //     .catch(function (error) {
                            //         Swal.fire({
                            //             text: error.message || error,
                            //             icon: "error",
                            //             buttonsStyling: false,
                            //             confirmButtonText: "Ok, got it!",
                            //             customClass: {
                            //                 confirmButton: "btn btn-primary"
                            //             }
                            //         });
                            //     })
                            //     .finally(function () {
                            //         e.removeAttribute("data-kt-indicator");
                            //         e.disabled = false;
                            //     });

                        } else {
                            Swal.fire({
                                text: "Please correct the errors in the form.",
                                icon: "warning",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-warning"
                                }
                            });
                        }
                    });
                });
            }
        }
    }();

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
