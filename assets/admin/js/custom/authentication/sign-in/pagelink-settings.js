    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('whitepaper_status');
        const showOnlyWhenOn = document.getElementById('whitepaper_extra_input');
        const hideWhenOn = document.querySelectorAll('.whitepaper-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });


    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('project_status');
        const showOnlyWhenOn = document.getElementById('project_extra_input');
        const hideWhenOn = document.querySelectorAll('.project-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });


    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('roadmap_status');
        const showOnlyWhenOn = document.getElementById('roadmap_extra_input');
        const hideWhenOn = document.querySelectorAll('.roadmap-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });

    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('airobotics_status');
        const showOnlyWhenOn = document.getElementById('airobotics_extra_input');
        const hideWhenOn = document.querySelectorAll('.airobotics-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });

    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('ecommerce_status');
        const showOnlyWhenOn = document.getElementById('e-commerce_extra_input');
        const hideWhenOn = document.querySelectorAll('.e-commerce-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });


    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('games_status');
        const showOnlyWhenOn = document.getElementById('games_extra_input');
        const hideWhenOn = document.querySelectorAll('.games-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });

    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('education_status');
        const showOnlyWhenOn = document.getElementById('education_extra_input');
        const hideWhenOn = document.querySelectorAll('.education-toggle-target');

        function updateVisibility() {
            if (toggle.checked) {
                // Hide title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'none');

                // Show extra input
                showOnlyWhenOn.style.display = 'flex';
            } else {
                // Show title/content/file blocks
                hideWhenOn.forEach(el => el.style.display = 'flex');

                // Hide extra input
                showOnlyWhenOn.style.display = 'none';
            }
        }

        // Initialize on page load
        updateVisibility();

        // Toggle on checkbox change
        toggle.addEventListener('change', updateVisibility);
    });

    var KTSigninGeneral = function () {
        var t, e, r;
        return {
            init: function () {
                t = document.querySelector("#kt_account_meta_details_form");
                e = document.querySelector("#kt_account_meta_details_submit");
    
                r = FormValidation.formValidation(t, {
                    fields: {}, // Dynamic fields
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
    
                    const sectionPrefixes = [
                        'whitepaper', 'project', 'roadmap',
                        'airobotics', 'ecommerce', 'games', 'education'
                    ];
    
                    sectionPrefixes.forEach(prefix => {
                        ['title', 'content', 'image', 'document'].forEach(field => {
                            const fieldName = `${prefix}_${field}`;
                            if (r.getFields().hasOwnProperty(fieldName)) {
                                r.removeField(fieldName);
                            }
                        });
                    });
    
                    sectionPrefixes.forEach(prefix => {
                        const statusCheckbox = document.querySelector(`#${prefix}_status`);
                        const isChecked = statusCheckbox && statusCheckbox.checked;
    
                        if (isChecked) {
                            r.addField(`${prefix}_document`, {
                                validators: {
                                    file: {
                                        extension: 'pdf,doc,docx,txt',
                                        type: 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain',
                                        message: 'Please upload a valid document.'
                                    }
                                }
                            });
                        } else {
                            ['title', 'content', 'image'].forEach(field => {
                                const fieldName = `${prefix}_${field}`;
                            
                                const validators = {};
                            
                                if (field === 'image') {
                                    validators.file = {
                                        extension: 'jpg,jpeg,png',
                                        type: 'image/jpeg,image/png',
                                        message: 'Please upload a valid image (JPG, JPEG, or PNG).'
                                    };
                                }
                            
                                r.addField(fieldName, { validators });
                            });
                            
                        }   
                    });
    
                    r.validate().then(function(status) {
                        if (status === "Valid") {
                            e.setAttribute("data-kt-indicator", "on");
                            e.disabled = true;
                
                            var formData = new FormData(t);
                
                            axios.post(t.getAttribute("action"), formData)
                                .then(function(response) {
                                    const res = response.data;
                                    Swal.fire({
                                        text: res.message,
                                        icon: res.status ? "success" : "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });
                                })
                                .catch(function(error) {
                                    Swal.fire({
                                        text: error.message || error,
                                        icon: "error",
                                        confirmButtonText: "Ok, got it!"
                                    });
                                })
                                .finally(function() {
                                    e.removeAttribute("data-kt-indicator");
                                    e.disabled = false;
                                });
                        } else {
                            Swal.fire({
                                text: "Please correct the errors in the form.",
                                icon: "warning",
                                confirmButtonText: "Ok, got it!"
                            });
                        }
                    });
                    
                });
            }
        }
    }();
    
    
    
    KTUtil.onDOMContentLoaded(function() {
        KTSigninGeneral.init();
    });
    
    
