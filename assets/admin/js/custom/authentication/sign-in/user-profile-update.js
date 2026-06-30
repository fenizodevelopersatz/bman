
var KTSigninGeneralcontact = function() {
    var t, e, r;
    return {
        init: function() {
            t = document.querySelector("#kt_account_profile_details_form");
            e = document.querySelector("#kt_account_profile_details_submit");

            r = FormValidation.formValidation(t, {
                fields: {
                    fname: {
                        validators: {
                            notEmpty: {
                                message: "The first name is required"
                            }
                        }
                    },
                    lname: {
                        validators: {
                            notEmpty: {
                                message: "The first name is required"
                            }
                        }
                    },
                    phone: {
                        validators: {
                            notEmpty: {
                                message: "The number is required"
                            }
                        }
                    },
                    country: {
                        validators: {
                            notEmpty: {
                                message: "The number is required"
                            }
                        }
                    },
                    language: {
                        validators: {
                            notEmpty: {
                                message: "The number is required"
                            }
                        }
                    },
                    timezone: {
                        validators: {
                            notEmpty: {
                                message: "The number is required"
                            }
                        }
                    },
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

            e.addEventListener("click", function(i) {
                i.preventDefault();
                
                r.validate().then(function(status) {
                    if (status === "Valid") {
                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                            // Swal.fire({
                            //     icon: 'info',
                            //     title: 'Demo Version',
                            //     text: 'You Can not change record.',
                            //     confirmButtonText: 'Ok, got it!',
                            //     customClass: {
                            //         confirmButton: 'btn btn-primary'
                            //     },
                            //         buttonsStyling: false
                            //     });
                                
                        axios.post(t.getAttribute("action"), new FormData(t))
                            .then(function(responses) {
                               // t.reset(); 
                                var response = responses.data;
                                
                                if(response.status){

                                    Swal.fire({
                                        text: "Profile details update successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(function(e) {
                                        if (e.isConfirmed) {
                                             var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                            if (redirectUrl) {
                                              location.href = redirectUrl;
                                            }
                                        }
                                    });
    
                                } else {

                                    Swal.fire({
                                    text: "Sorry, "+response.message,
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
                                    text: error,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });
                            })
                            .finally(function() {
                                e.removeAttribute("data-kt-indicator");
                                e.disabled = false;
                            });

                    } else {
                        
                    }
                });
            });
        }
    }
}();



KTUtil.onDOMContentLoaded(function() {
    KTSigninGeneralcontact.init();
});
