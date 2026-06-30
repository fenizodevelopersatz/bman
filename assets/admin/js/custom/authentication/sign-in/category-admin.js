
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
//                     category_image: {
//                         validators: {
//                             file: {
//                                 extension: 'jpeg,jpg,png',
//                                 type: 'image/jpeg,image/png,image/jpg',
//                                 message: 'Only JPG or PNG images are allowed'
//                             }
//                         }
//                     },
//                     category_name: {
//                         validators: {
//                             notEmpty: {
//                                 message: 'Category name is required'
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
//                 i.preventDefault();

//                 r.validate().then(function (status) {
//                     if (status === "Valid") {
//                         e.setAttribute("data-kt-indicator", "on");
//                         e.disabled = true;

//                         var formData = new FormData(t);

//                         // ✅ Inject CKEditor content
//                         if (descriptionEditor) {
//                             formData.set("description", descriptionEditor.getData());
//                         }

//                         axios.post(t.getAttribute("action"), formData)
//                             .then(function (response) {
//                                 var res = response.data;

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
//                                         text: "Sorry, " + res.message,
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
//                                 Swal.fire({
//                                     text: error.message || error,
//                                     icon: "error",
//                                     buttonsStyling: false,
//                                     confirmButtonText: "Ok, got it!",
//                                     customClass: {
//                                         confirmButton: "btn btn-primary"
//                                     }
//                                 });
//                             })
//                             .finally(function () {
//                                 e.removeAttribute("data-kt-indicator");
//                                 e.disabled = false;
//                             });
//                     } else {
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

var KTSigninGeneral = (function () {
    var t, e, r;

    return {
        init: function () {
            t = document.querySelector("#kt_account_meta_details_form");
            e = document.querySelector("#kt_account_meta_details_submit");
            if (!t || !e) return;

            // ✅ fix: define redirectUrl properly (your old code used inside swal)
            var redirectUrl = t.getAttribute("data-kt-redirect-url") || "";

            // ✅ CKEditor
            let descriptionEditor = null;
            const descEl = document.querySelector("#description");
            if (descEl && window.ClassicEditor) {
                ClassicEditor.create(descEl)
                    .then((editor) => {
                        descriptionEditor = editor;
                    })
                    .catch((error) => console.error(error));
            }

            // ✅ Validation
            r = FormValidation.formValidation(t, {
                fields: {
                    category_image: {
                        validators: {
                            file: {
                                extension: "jpeg,jpg,png",
                                type: "image/jpeg,image/png,image/jpg",
                                message: "Only JPG or PNG images are allowed",
                            },
                        },
                    },
                    category_name: {
                        validators: {
                            notEmpty: { message: "Category name is required" },
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

                    // ✅ DEMO BLOCK (STOP here)
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
                                    if (x.isConfirmed && redirectUrl) {
                                        location.href = redirectUrl;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    text:
                                        "Sorry, " +
                                        ((res && res.message) ? res.message : "Something went wrong!"),
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
   DELETE IMAGE (DEMO SAFE)
======================= */
$(document).on("click", ".delete_user", function (e) {
    e.preventDefault();

    const btn = $(this);
    const deleteUrl = btn.data("delete_user-url");

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

        // ✅ DEMO BLOCK (STOP here)
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
                        text: response.message || "Record deleted successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: { confirmButton: "btn btn-primary" },
                    }).then(() => {
                        location.reload(); // or remove element preview
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
