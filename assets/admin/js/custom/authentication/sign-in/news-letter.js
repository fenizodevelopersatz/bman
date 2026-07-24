document.addEventListener("DOMContentLoaded", function () {
    var memberSelect = $("#mySelect2");
    var defaultAvatar = base_url + "assets/default-user.png";

    function memberTemplate(member) {
        if (member.loading) {
            return member.text;
        }

        var wrapper = document.createElement("span");
        wrapper.className = "d-flex align-items-center gap-3";

        var avatar = document.createElement("img");
        avatar.src = member.avatar || defaultAvatar;
        avatar.alt = "";
        avatar.className = "rounded-circle flex-shrink-0";
        avatar.style.width = "36px";
        avatar.style.height = "36px";
        avatar.style.objectFit = "cover";
        avatar.onerror = function () {
            this.onerror = null;
            this.src = defaultAvatar;
        };

        var details = document.createElement("span");
        details.className = "d-flex flex-column";

        var name = document.createElement("strong");
        name.textContent = member.name || member.text || "";

        var meta = document.createElement("small");
        meta.className = "text-muted";
        meta.textContent = [member.email, member.referral_id].filter(Boolean).join(" · ");

        details.appendChild(name);
        details.appendChild(meta);
        wrapper.appendChild(avatar);
        wrapper.appendChild(details);

        return $(wrapper);
    }

    function memberSelectionTemplate(member) {
        if (!member.id) {
            return member.text;
        }

        var wrapper = document.createElement("span");
        wrapper.className = "d-inline-flex align-items-center gap-2";

        var avatar = document.createElement("img");
        avatar.src = member.avatar || defaultAvatar;
        avatar.alt = "";
        avatar.className = "rounded-circle";
        avatar.style.width = "24px";
        avatar.style.height = "24px";
        avatar.style.objectFit = "cover";
        avatar.onerror = function () {
            this.onerror = null;
            this.src = defaultAvatar;
        };

        var label = document.createElement("span");
        label.textContent = member.text || "";

        wrapper.appendChild(avatar);
        wrapper.appendChild(label);
        return $(wrapper);
    }

    memberSelect.select2({
        ajax: {
            url: memberSelect.data("search-url"),
            dataType: "json",
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || "",
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || [],
                    pagination: data.pagination || { more: false }
                };
            },
            cache: true
        },
        placeholder: memberSelect.data("placeholder"),
        allowClear: true,
        closeOnSelect: false,
        minimumInputLength: 0,
        templateResult: memberTemplate,
        templateSelection: memberSelectionTemplate,
        escapeMarkup: function (markup) {
            return markup;
        },
        width: "100%"
    });

    var KTSigninGeneral = function () {
        var t, e, r;
        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");

                r = FormValidation.formValidation(t, {
                    fields: {
                        mail_subject: {
                            validators: {
                                notEmpty: {
                                    message: "The mail subject is Required"
                                },
                                regexp: {
                                    regexp: /^[A-Za-z\s]+$/,
                                    message: "The mail subject must only contain letters"
                                }
                            }
                        },
                        "selected_members[]": {
                            validators: {
                                notEmpty: {
                                    message: "Please select members"
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
                            if (window.CKEDITOR && CKEDITOR.instances.mail_content) {
                                CKEDITOR.instances.mail_content.updateElement();
                            }

                            e.setAttribute("data-kt-indicator", "on"); 
                            e.disabled = true; 
                            var formData = new FormData(t);

                            axios.post(t.getAttribute("action"), formData)
                                .then(function (response) {
                                    var res = response.data;

                                    // Check the response status
                                    if (res.status) {
                                        Swal.fire({
                                            text: "Email Template Update",
                                            icon: "success",
                                            buttonsStyling: false,
                                            confirmButtonText: "Ok, got it!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then(function (e) {
                                            var redirectUrl = t.getAttribute("data-kt-redirect-url");
                                            if (e.isConfirmed) {
                                                if (redirectUrl) {
                                                    location.href = redirectUrl; 
                                                }
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
