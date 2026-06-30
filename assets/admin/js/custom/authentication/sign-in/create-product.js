
// var KTSigninGeneral = function () {
//     var t, e, r;
//     return {
//         init: function () {
//             t = document.querySelector("#kt_account_meta_details_form");
//             e = document.querySelector("#kt_account_meta_details_submit");


//             let descriptionEditor;
//             ClassicEditor
//                 .create(document.querySelector('#description'))
//                 .then(editor => {
//                     descriptionEditor = editor;
//                 })
//                 .catch(error => {
//                     console.error(error);
//                 });

//             r = FormValidation.formValidation(t, {
//                 fields: {
//                     name: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Product name is required"
//                             },
//                         }
//                     },
//                     sku: {
//                         validators: {
//                             notEmpty: {
//                                 message: "SKU is required"
//                             }
//                         }
//                     },
//                     price: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Price is required"
//                             },
//                             numeric: {
//                                 message: "Enter a valid price"
//                             }
//                         }
//                     },
//                     offer_price: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Offer price is required"
//                             },
//                             numeric: {
//                                 message: "Enter a valid offer price"
//                             }
//                         }
//                     },
//                     stock: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Stock is required"
//                             },
//                             integer: {
//                                 message: "Stock must be an integer"
//                             }
//                         }
//                     },
//                     product_category: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Brand is required"
//                             }
//                         }
//                     },
//                     product_brand: {
//                         validators: {
//                             notEmpty: {
//                                 message: "Brand is required"
//                             }
//                         }
//                     },
//                     commission: {
//                         validators: {
//                             numeric: {
//                                 message: "Commission must be a valid number"
//                             }
//                         }
//                     },
//                     product_log: {
//                         validators: {
//                             file: {
//                                 extension: 'jpeg,jpg,png',
//                                 type: 'image/jpeg,image/png,image/jpg',
//                                 message: 'Only JPG or PNG images are allowed'
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


//             e.addEventListener("click", function (i) {
//                 i.preventDefault(); // Prevent the default form submission

//                 // Validate the form
//                 r.validate().then(function (status) {
//                     if (status === "Valid") {
//                         e.setAttribute("data-kt-indicator", "on"); // Show loading indicator
//                         e.disabled = true; // Disable the button to prevent multiple submissions


//                         // Create a FormData object and append the CKEditor content
//                         var formData = new FormData(t);

//                         // ✅ Inject CKEditor content
//                         if (descriptionEditor) {
//                             formData.set("description", descriptionEditor.getData());
//                         }

//                         // Send the form data via axios
//                         axios.post(t.getAttribute("action"), formData)
//                             .then(function (response) {
//                                 var res = response.data;

//                                 // Check the response status
//                                 if (res.status) {
//                                     Swal.fire({
//                                         text: res.message,
//                                         icon: "success",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     }).then(function (e) {
//                                         if (e.isConfirmed) {
//                                             var redirectUrl = t.getAttribute("data-kt-redirect-url");
//                                             if (redirectUrl) {
//                                                 location.href = redirectUrl;
//                                             }
//                                         }
//                                     });
//                                 } else {
//                                     Swal.fire({
//                                         text: "Sorry, " + res.message, // Display error message
//                                         icon: "error",
//                                         buttonsStyling: false,
//                                         confirmButtonText: "Ok, got it!",
//                                         customClass: {
//                                             confirmButton: "btn btn-primary"
//                                         }
//                                     });
//                                 }
//                             })
//                             .catch(function (error) {
//                                 // Handle errors in case of request failure
//                                 Swal.fire({
//                                     text: error.message || error, // Show the error message
//                                     icon: "error",
//                                     buttonsStyling: false,
//                                     confirmButtonText: "Ok, got it!",
//                                     customClass: {
//                                         confirmButton: "btn btn-primary"
//                                     }
//                                 });
//                             })
//                             .finally(function () {
//                                 // Remove the loading indicator and re-enable the button
//                                 e.removeAttribute("data-kt-indicator");
//                                 e.disabled = false;
//                             });
//                     } else {
//                         // Handle invalid form
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



// $(document).on("click", ".delete_user", function (e) {
//     e.preventDefault();

//     var checkbox = $(this);
//     var isChecked = checkbox.prop("checked");
//     var changestatusUrl = checkbox.data("delete_user-url");

//     checkbox.prop("checked", !isChecked);

//     Swal.fire({
//         title: "Are you sure?",
//         text: "You want to delete this image?",
//         icon: "warning",
//         showCancelButton: true,
//         confirmButtonText: "Yes, Change it!",
//         cancelButtonText: "No, cancel!",
//         buttonsStyling: false,
//         customClass: {
//             confirmButton: "btn btn-danger",
//             cancelButton: "btn btn-secondary"
//         }
//     }).then((result) => {
//         if (result.isConfirmed) {

//             Swal.fire({
//                 icon: 'info',
//                 title: 'Demo Version',
//                 text: 'You Can not delete image.',
//                 confirmButtonText: 'Ok, got it!',
//                 customClass: {
//                     confirmButton: 'btn btn-primary'
//                 },
//                 buttonsStyling: false
//             });

//             $.ajax({
//                 url: changestatusUrl,
//                 type: "POST",
//                 data: { template_status: isChecked ? 1 : 0 },
//                 dataType: "json",
//                 success: function (response) {
//                     if (response.status === "success") {
//                         // Uncheck all other checkboxes except the current one
//                         checkbox.prop("checked", false);
//                         checkbox.prop("checked", isChecked); // Keep the selected one checked

//                         Swal.fire({
//                             text: response.message || "Something went wrong!",
//                             icon: "success",
//                             buttonsStyling: false,
//                             confirmButtonText: "Ok, got it!",
//                             customClass: {
//                                 confirmButton: "btn btn-primary"
//                             }
//                         });
//                     } else {
//                         Swal.fire({
//                             text: response.message || "Something went wrong!",
//                             icon: "error",
//                             confirmButtonText: "Ok, got it!"
//                         });
//                     }
//                 },
//                 error: function () {
//                     Swal.fire({
//                         text: "Failed to change the record!",
//                         icon: "error",
//                         confirmButtonText: "Ok, got it!"
//                     });
//                 }
//             });

//         }
//     });
// });


// KTUtil.onDOMContentLoaded(function () {
//     KTSigninGeneral.init();
// });




"use strict";

/* =======================
   DEMO MODE HELPERS
======================= */
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

/* =======================
   FORM SUBMIT (ADD/EDIT PRODUCT)
======================= */
var KTSigninGeneral = (function () {
    var t, e, r;
    var descriptionEditor = null;

    return {
        init: function () {
            t = document.querySelector("#kt_account_meta_details_form");
            e = document.querySelector("#kt_account_meta_details_submit");
            if (!t || !e) return;

            // ✅ CKEditor
            var descEl = document.querySelector("#description");
            if (descEl && typeof ClassicEditor !== "undefined") {
                ClassicEditor.create(descEl)
                    .then((editor) => (descriptionEditor = editor))
                    .catch((error) => console.error(error));
            }

            // ✅ Validation
            r = FormValidation.formValidation(t, {
                fields: {
                    name: {
                        validators: {
                            notEmpty: { message: "Product name is required" },
                        },
                    },
                    sku: {
                        validators: {
                            notEmpty: { message: "SKU is required" },
                        },
                    },
                    price: {
                        validators: {
                            notEmpty: { message: "Price is required" },
                            numeric: { message: "Enter a valid price" },
                        },
                    },
                    offer_price: {
                        validators: {
                            notEmpty: { message: "Offer price is required" },
                            numeric: { message: "Enter a valid offer price" },
                        },
                    },
                    stock: {
                        validators: {
                            notEmpty: { message: "Stock is required" },
                            integer: { message: "Stock must be an integer" },
                        },
                    },
                    product_category: {
                        validators: {
                            notEmpty: { message: "Category is required" },
                        },
                    },
                    product_brand: {
                        validators: {
                            notEmpty: { message: "Brand is required" },
                        },
                    },
                    commission: {
                        validators: {
                            numeric: { message: "Commission must be a valid number" },
                        },
                    },
                    product_log: {
                        validators: {
                            file: {
                                extension: "jpeg,jpg,png",
                                type: "image/jpeg,image/png,image/jpg",
                                message: "Only JPG or PNG images are allowed",
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

            // ✅ Submit
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

                    // ✅ DEMO BLOCK
                    if (isDemoMode()) {
                        demoBlockAlert("You Can not change record.");
                        return;
                    }

                    e.setAttribute("data-kt-indicator", "on");
                    e.disabled = true;

                    var formData = new FormData(t);

                    // ✅ Inject CKEditor content
                    if (descriptionEditor) {
                        formData.set("description", descriptionEditor.getData());
                    }

                    axios
                        .post(t.getAttribute("action"), formData)
                        .then(function (response) {
                            var res = response.data;

                            if (res && res.status) {
                                Swal.fire({
                                    text: res.message || "Saved successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                }).then(function (x) {
                                    if (!x.isConfirmed) return;
                                    var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                    if (redirectUrl) location.href = redirectUrl;
                                });
                            } else {
                                Swal.fire({
                                    text: "Sorry, " + ((res && res.message) ? res.message : "Something went wrong!"),
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: { confirmButton: "btn btn-primary" },
                                });
                            }
                        })
                        .catch(function (error) {
                            Swal.fire({
                                text: (error && error.message) ? error.message : String(error),
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

/* =======================
   DELETE IMAGE (PRODUCT IMAGE)
======================= */
$(document).on("click", ".delete_user", function (e) {
    e.preventDefault();

    var btn = $(this);
    var deleteUrl = btn.data("delete_user-url");
    if (!deleteUrl) return;

    Swal.fire({
        title: "Are you sure?",
        text: "You want to delete this image?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-secondary",
        },
    }).then((result) => {
        if (!result.isConfirmed) return;

        // ✅ DEMO BLOCK
        if (isDemoMode()) {
            demoBlockAlert("You Can not delete image.");
            return;
        }

        $.ajax({
            url: deleteUrl,
            type: "POST",
            dataType: "json",
            success: function (response) {
                if (response && response.status === "success") {
                    Swal.fire({
                        text: response.message || "Deleted successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: { confirmButton: "btn btn-primary" },
                    }).then(() => {
                        // optional: remove preview container if you have one
                        // btn.closest(".image-item").remove();
                    });
                } else {
                    Swal.fire({
                        text: (response && response.message) ? response.message : "Something went wrong!",
                        icon: "error",
                        confirmButtonText: "Ok, got it!",
                    });
                }
            },
            error: function () {
                Swal.fire({
                    text: "Failed to delete the record!",
                    icon: "error",
                    confirmButtonText: "Ok, got it!",
                });
            },
        });
    });
});

KTUtil.onDOMContentLoaded(function () {
    KTSigninGeneral.init();
});
