document.addEventListener("DOMContentLoaded", function () {


    /************ SELECT USER  */
    var selectedOption = $(this).find(':selected');
    var value = selectedOption.val();
    fetchBalance(value);

    /************ SELECT USER  */
    $('#receiver_memberSelect').on('change', function() {
        var selectedOption = $(this).find(':selected');
        var value = selectedOption.val();
        fetchBalanceReceiver(value);
    });


     /********** FETCH BALANCE */
     function fetchBalanceReceiver(userid){

        $('#main_balance_reciver').html('0');
        $('#token_balance_receiver').html('0');

        $.ajax({
            url: base_url + "user-wallet-balance/"+userid,
            type: 'GET',
            success: function(datas) {
                data = JSON.parse(datas);
                $('#main_balance_reciver').html(data.currency_balance);
                $('#token_balance_receiver').html(data.token_balance);
                $('#investment_balance_receiver').html(data.investment_balance);
            },
            error: function() {
                console.log('error')
            }
        });

    }

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
                        selected_members_receiver: {
                            validators: {
                                notEmpty: {
                                    message: "Please select a receiver member"
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
                                        var selected_coin = document.querySelector('select[name="selected_coin"]').value;
                                        var selectedSender = document.querySelector('select[name="selected_members"]').value;
                                        var selectedReceiver = document.querySelector('select[name="selected_members_receiver"]').value;
                                        var selectedCurrency = document.querySelector('select[name="selected_coin"]').value;
                                        var enteredAmount = parseFloat(input.value);
        
                                        if (!selected_coin || !selectedSender || !selectedReceiver || !selectedCurrency) {
                                            return {
                                                valid: false,
                                                message: "Please select all required fields"
                                            };
                                        }
        
                                        return new Promise(function (resolve, reject) {
                                            $.ajax({
                                                url: base_url + "validate-transfer-balance",
                                                type: "POST",
                                                dataType: "json",
                                                data: {
                                                    selected_coin: selected_coin,
                                                    sender_id: selectedSender,
                                                    receiver_id: selectedReceiver,
                                                    currency: selectedCurrency,
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
