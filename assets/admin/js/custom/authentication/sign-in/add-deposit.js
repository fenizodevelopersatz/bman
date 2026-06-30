// document.addEventListener("DOMContentLoaded", function () {


//     /************ SELECT USER  */
//     $('#memberSelect').on('change', function () {
//         var selectedOption = $(this).find(':selected');
//         var value = selectedOption.val();
//         fetchBalance(value);
//     });

//     /********** FETCH BALANCE */
//     function fetchBalance(userid) {

//         $('#main_balance').html('0');
//         $('#token_balance').html('0');

//         $.ajax({
//             url: base_url + "user-wallet-balance/" + userid,
//             type: 'GET',
//             success: function (datas) {
//                 data = JSON.parse(datas);
//                 $('#main_balance').html(data.currency_balance);
//                 $('#token_balance').html(data.token_balance);
//                 $('#investment_balance').html(data.investment_balance);
//             },
//             error: function () {
//                 console.log('error')
//             }
//         });

//     }

//     /****** AFTER SUBMIT */
//     var KTSigninGeneral = function () {
//         var t, e, r;
//         return {
//             init: function () {
//                 t = document.querySelector("#kt_account_meta_details_form");
//                 e = document.querySelector("#kt_account_meta_details_submit");

//                 r = FormValidation.formValidation(t, {
//                     fields: {
//                         selected_package: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "Please select package type"
//                                 }
//                             }
//                         },
//                         selected_members: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "Please select a member"
//                                 }
//                             }
//                         },
//                         invest_date: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "Please select a investment date"
//                                 }
//                             }
//                         },
//                         bonus_amount: {
//                             validators: {
//                                 notEmpty: {
//                                     message: "Please enter an amount"
//                                 },
//                                 numeric: {
//                                     message: "Only numbers and decimal values are allowed"
//                                 },
//                                 callback: {
//                                     message: "Amount is not within the package range",
//                                     callback: function (input) {
//                                         var selectedPackageId = document.querySelector('select[name="selected_package"]').value;
//                                         var enteredAmount = parseFloat(input.value);

//                                         if (!selectedPackageId) {
//                                             return {
//                                                 valid: false,
//                                                 message: "Please select a package first"
//                                             };
//                                         }

//                                         return new Promise(function (resolve, reject) {
//                                             $.ajax({
//                                                 url: base_url + "validate-package-amount",
//                                                 type: "POST",
//                                                 dataType: "json",
//                                                 data: {
//                                                     package_id: selectedPackageId,
//                                                     amount: enteredAmount
//                                                 },
//                                                 success: function (response) {
//                                                     if (response.status) {
//                                                         resolve({
//                                                             valid: true
//                                                         });
//                                                     } else {
//                                                         resolve({
//                                                             valid: false,
//                                                             message: response.message
//                                                         });
//                                                     }
//                                                 },
//                                                 error: function () {
//                                                     resolve({
//                                                         valid: false,
//                                                         message: "Error in validation, please try again"
//                                                     });
//                                                 }
//                                             });
//                                         });
//                                     }
//                                 }
//                             }
//                         }
//                     },
//                     plugins: {
//                         trigger: new FormValidation.plugins.Trigger(),
//                         bootstrap: new FormValidation.plugins.Bootstrap5({
//                             rowSelector: ".fv-row",
//                             eleInvalidClass: "",
//                             eleValidClass: ""
//                         })
//                     }
//                 });


//                 e.addEventListener("click", function (i) {
//                     i.preventDefault();

//                     r.validate().then(function (status) {
//                         if (status === "Valid") {
//                             e.setAttribute("data-kt-indicator", "on");
//                             e.disabled = true;
//                             var formData = new FormData(t);

//                             axios.post(t.getAttribute("action"), formData)
//                                 .then(function (response) {
//                                     var res = response.data;

//                                     // Check the response status
//                                     if (res.status) {
//                                         Swal.fire({
//                                             text: res.message,
//                                             icon: "success",
//                                             buttonsStyling: false,
//                                             confirmButtonText: "Ok, got it!",
//                                             customClass: {
//                                                 confirmButton: "btn btn-primary"
//                                             }
//                                         }).then(function (e) {
//                                             var redirectUrl = t.getAttribute("data-kt-redirect-url");
//                                             if (e.isConfirmed) {
//                                                 if (redirectUrl) {
//                                                     location.href = redirectUrl;
//                                                 }
//                                             }
//                                         });
//                                     } else {
//                                         Swal.fire({
//                                             text: "Sorry, " + res.message,
//                                             icon: "error",
//                                             buttonsStyling: false,
//                                             confirmButtonText: "Ok, got it!",
//                                             customClass: {
//                                                 confirmButton: "btn btn-primary"
//                                             }
//                                         });
//                                     }
//                                 })
//                                 .catch(function (error) {
//                                     Swal.fire({
//                                         text: error.message || error,
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });
//                                 })
//                                 .finally(function () {
//                                     e.removeAttribute("data-kt-indicator");
//                                     e.disabled = false;
//                                 });
//                         } else {
//                             Swal.fire({
//                                 text: "Please correct the errors in the form.",
//                                 icon: "warning",
//                                 buttonsStyling: false,
//                                 confirmButtonText: "Ok, got it!",
//                                 customClass: {
//                                     confirmButton: "btn btn-warning"
//                                 }
//                             });
//                         }
//                     });
//                 });
//             }
//         }
//     }();

//     KTUtil.onDOMContentLoaded(function () {
//         KTSigninGeneral.init();
//     });
// });



"use strict";

document.addEventListener("DOMContentLoaded", function () {
    // ---------------------------
    // ✅ DEMO MODE HELPERS (optional)
    // ---------------------------
    function isDemoMode() {
        return !!(
            (window.APP_CONFIG && window.APP_CONFIG.DEMO === true) ||
            window.DEMOVERSION === true
        );
    }

    function demoBlockAlert(msg) {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: msg || "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false,
        });
    }

    /************ SELECT USER  */
    $("#memberSelect").on("change", function () {
        var selectedOption = $(this).find(":selected");
        var value = selectedOption.val();
        fetchBalance(value);
    });

    /********** FETCH BALANCE */
    function fetchBalance(userid) {
        $("#main_balance").html("0");
        $("#token_balance").html("0");
        $("#investment_balance").html("0");

        $.ajax({
            url: base_url + "user-wallet-balance/" + userid,
            type: "GET",
            success: function (datas) {
                var data = JSON.parse(datas);
                $("#main_balance").html(data.currency_balance);
                $("#token_balance").html(data.token_balance);
                $("#investment_balance").html(data.investment_balance);
            },
            error: function () {
                console.log("error");
            },
        });
    }

    /****** AFTER SUBMIT */
    var KTSigninGeneral = (function () {
        var t, e, r;

        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");

                r = FormValidation.formValidation(t, {
                    fields: {
                        selected_package: {
                            validators: {
                                notEmpty: {
                                    message: "Please select package type",
                                },
                            },
                        },
                        selected_members: {
                            validators: {
                                notEmpty: {
                                    message: "Please select a member",
                                },
                            },
                        },
                        invest_date: {
                            validators: {
                                notEmpty: {
                                    message: "Please select a investment date",
                                },
                            },
                        },
                        bonus_amount: {
                            validators: {
                                notEmpty: {
                                    message: "Please enter an amount",
                                },
                                numeric: {
                                    message: "Only numbers and decimal values are allowed",
                                },
                                callback: {
                                    message: "Amount is not within the package range",
                                    callback: function (input) {
                                        var selectedPackageId = document.querySelector(
                                            'select[name="selected_package"]'
                                        ).value;

                                        var enteredAmount = parseFloat(input.value);

                                        if (!selectedPackageId) {
                                            return {
                                                valid: false,
                                                message: "Please select a package first",
                                            };
                                        }

                                        return new Promise(function (resolve) {
                                            $.ajax({
                                                url: base_url + "validate-package-amount",
                                                type: "POST",
                                                dataType: "json",
                                                data: {
                                                    package_id: selectedPackageId,
                                                    amount: enteredAmount,
                                                },
                                                success: function (response) {
                                                    if (response.status) {
                                                        resolve({ valid: true });
                                                    } else {
                                                        resolve({
                                                            valid: false,
                                                            message: response.message,
                                                        });
                                                    }
                                                },
                                                error: function () {
                                                    resolve({
                                                        valid: false,
                                                        message: "Error in validation, please try again",
                                                    });
                                                },
                                            });
                                        });
                                    },
                                },
                            },
                        },
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: ".fv-row",
                            eleInvalidClass: "",
                            eleValidClass: "",
                        }),
                    },
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
                                customClass: { confirmButton: "btn btn-warning" },
                            });
                            return;
                        }

                        // ✅ DEMO MODE STOP (before submit)
                        if (isDemoMode()) {
                            demoBlockAlert("You Can not change record.");
                            return;
                        }

                        e.setAttribute("data-kt-indicator", "on");
                        e.disabled = true;

                        var formData = new FormData(t);

                        axios
                            .post(t.getAttribute("action"), formData)
                            .then(function (response) {
                                var res = response.data;

                                if (res.status) {
                                    Swal.fire({
                                        text: res.message,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    }).then(function (e2) {
                                        var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                        if (e2.isConfirmed && redirectUrl) {
                                            location.href = redirectUrl;
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        text: "Sorry, " + res.message,
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: { confirmButton: "btn btn-primary" },
                                    });
                                }
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    text: error.message || error,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });
                            })
                            .finally(function () {
                                e.removeAttribute("data-kt-indicator");
                                e.disabled = false;
                            });
                    });
                });
            },
        };
    })();

    KTUtil.onDOMContentLoaded(function () {
        KTSigninGeneral.init();
    });
});
