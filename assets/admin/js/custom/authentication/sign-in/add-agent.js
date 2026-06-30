var KTSigninGeneralcontact = function() {
    var t, e, r;
    return {
        init: function() {
            t = document.querySelector("#kt_account_addagent_form");
            e = document.querySelector("#kt_account_addagent_submit");

            r = FormValidation.formValidation(t, {
                fields: {
                    agent_name: {
                        validators: {
                            notEmpty: {
                                message: "agent name is required"
                            }
                        }
                    },
                    join_date:{
                        validators: {
                            notEmpty: {
                                message: "agent join date is required"
                            }
                        }
                    },
                    agent_email: {
                        validators: {
                            notEmpty: {
                                message: "agent email is required"
                            },
                            emailAddress: {
                                message: "The value is not a valid email address"
                            },
                            remote: {
                                message: "The email is already used",
                                method: 'POST',
                                url: base_url+'/admin/agent/checkEmail',
                                data: function() {
                                    return {
                                        email: t.querySelector('[name="agent_email"]').value,
                                        agent_id :t.querySelector('[name="agent_id"]').value
                                    };
                                },
                                delay: 2000
                            }
                        }
                    },
                    agent_phone: {
                        validators: {
                            notEmpty: {
                                message: "agent phone is required"
                            },
                            regexp: {
                                regexp: /^[0-9]{10}$/,
                                message: "The phone number can only contain digits and must be 10 digits long"
                            },
                            remote: {
                                message: "The phone is already used",
                                method: 'POST',
                                url: base_url+'/admin/agent/checkPhone',
                                data: function() {
                                    return {
                                        email: t.querySelector('[name="agent_phone"]').value,
                                        agent_id :t.querySelector('[name="agent_id"]').value
                                    };
                                },
                                delay: 2000
                            }
                        }
                    },
                    agent_roll: {
                        validators: {
                            notEmpty: {
                                message: "agent roll is required"
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

            e.addEventListener("click", function(i) {
                i.preventDefault();
                
                r.validate().then(function(status) {
                    if (status === "Valid") {
                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        axios.post(t.getAttribute("action"), new FormData(t))
                            .then(function(responses) {
                                var response = responses.data;
                                
                                if(response.status){

                                    Swal.fire({
                                        text: "Contact details update successfully!",
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
                                            var r = t.getAttribute("data-kt-redirect-url");
                                            r && (location.href = r)
                                            console.log(r);
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
