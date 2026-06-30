document.addEventListener("DOMContentLoaded", function () {


    /************ SELECT USER  */
        var selectedOption = $(this).find(':selected');
        var value = selectedOption.val();
        fetchBalance(value);


    /********** FETCH BALANCE */
    function fetchBalance(userid){

        $('#main_balance').html('0');
        $('#token_balance').html('0');

        $.ajax({
            url: base_url + "user-wallet-balance/"+userid,
            type: 'GET',
            success: function(datas) {
                data = JSON.parse(datas);
                $('#main_balance').html(data.currency_balance);
                $('#token_balance').html(data.token_balance);
                $('#investment_balance').html(data.investment_balance);
            },
            error: function() {
                console.log('error')
            }
        });

    }

    /****** AFTER SUBMIT */
    var KTSigninGeneral = function () {
        var t, e, r;
        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");
        
                r = FormValidation.formValidation(t, {
                    fields: {
                        selected_coin: {
                            validators: {
                                notEmpty: {
                                    message: "Please select currency type"
                                }
                            }
                        },
                        selected_members: {
                            validators: {
                                notEmpty: {
                                    message: "Please select a sender member"
                                }
                            }
                        },
                        bonus_amount: {
                            validators: {
                                notEmpty: {
                                    message: "Please enter an amount"
                                },
                                numeric: {
                                    message: "Only numbers and decimal values are allowed"
                                },
                                callback: {
                                    message: "Invalid amount",
                                    callback: function (input) {
                                        var selectedSender = document.querySelector('select[name="selected_members"]').value;
                                        var enteredAmount = parseFloat(input.value);
        
                                        if (!enteredAmount || !selectedSender) {
                                            return {
                                                valid: false,
                                                message: "Please select all required fields"
                                            };
                                        }
        
                                        return new Promise(function (resolve, reject) {
                                            $.ajax({
                                                url: base_url + "validate-swap-balance",
                                                type: "POST",
                                                dataType: "json",
                                                data: {
                                                    sender_id: selectedSender,
                                                    amount: enteredAmount
                                                },
                                                success: function (response) {
                                                    if (response.status) {
                                                        $('#received_amount').val(response.message)
                                                        resolve({ valid: true });
                                                    } else {
                                                        resolve({
                                                            valid: false,
                                                            message: response.message
                                                        });
                                                        $('#received_amount').val(0)
                                                    }
                                                },
                                                error: function () {
                                                    resolve({
                                                        valid: false,
                                                        message: "Error in validation, please try again"
                                                    });
                                                    $('#received_amount').val(0)
                                                }
                                            });
                                        });
                                    }
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
                });
        
                e.addEventListener("click", function (i) {
                    i.preventDefault();
        
                    r.validate().then(function (status) {
                        if (status === "Valid") {
                            e.setAttribute("data-kt-indicator", "on");
                            e.disabled = true;
                            var formData = new FormData(t);
        
                            axios.post(t.getAttribute("action"), formData)
                                .then(function (response) {
                                    var res = response.data;
        
                                    if (res.status) {
                                        Swal.fire({
                                            text: res.message,
                                            icon: "success",
                                            buttonsStyling: false,
                                            confirmButtonText: "Ok, got it!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then(function (e) {
                                            var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                            if (e.isConfirmed && redirectUrl) {
                                                location.href = redirectUrl;
                                            }
                                        });
                                    } else {
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
                                    Swal.fire({
                                        text: error.message || error,
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });
                                })
                                .finally(function () {
                                    e.removeAttribute("data-kt-indicator");
                                    e.disabled = false;
                                });
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
