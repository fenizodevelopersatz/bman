<div class=" d-flex flex-stack">
    <!--begin::Languages-->
    <div class="me-10">
        <!--begin::Toggle-->
        <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start" data-kt-menu-offset="0px, 0px">
            <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3"
                src="<?php echo base_url(); ?>assets/user/media/flags/united-states.svg" alt="" />
            <span data-kt-element="current-lang-name" class="me-1">English</span>
            <i class="ki-outline ki-down fs-5 text-muted rotate-180 m-0"></i> </button>
        <!--end::Toggle-->

        <!--begin::Menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7"
            data-kt-menu="true" id="kt_auth_lang_menu">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <a href="<?= base_url('switch_language/english'); ?>" class="menu-link d-flex px-5">
                    <span class="symbol symbol-20px me-4">
                        <img data-kt-element="lang-flag" class="rounded-1"
                            src="<?= base_url(); ?>assets/user/media/flags/united-states.svg" alt="" />
                    </span>
                    <span data-kt-element="lang-name">English</span>
                </a>
            </div>
            <!--end::Menu item-->

            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <a href="<?= base_url('switch_language/spanish'); ?>" class="menu-link d-flex px-5">
                    <span class="symbol symbol-20px me-4">
                        <img data-kt-element="lang-flag" class="rounded-1"
                            src="<?= base_url(); ?>assets/user/media/flags/spain.svg" alt="" />
                    </span>
                    <span data-kt-element="lang-name">Spanish</span>
                </a>
            </div>
            <!--end::Menu item-->

            <div class="menu-item px-3">
                <a href="<?= base_url('switch_language/german'); ?>" class="menu-link d-flex px-5">
                    <span class="symbol symbol-20px me-4">
                        <img data-kt-element="lang-flag" class="rounded-1"
                            src="<?= base_url(); ?>assets/user/media/flags/germany.svg" alt="" />
                    </span>
                    <span data-kt-element="lang-name">German</span>
                </a>
            </div>

        </div>

        <!--end::Menu-->
    </div>
    <!--end::Languages-->

    <!--begin::Links-->
    <div class="d-flex fw-semibold text-primary fs-base gap-5">
        <!-- <a href="<?php echo base_url(); ?>terms" target="_blank">Terms</a>
                        <a href="<?php echo base_url(); ?>prices" target="_blank">Plans</a>
                        <a href="<?php echo base_url(); ?>contact-us" target="_blank">Contact Us</a> -->
    </div>
    <!--end::Links-->
</div>


<script>
    document.getElementById('google-signin-btn').addEventListener('click', function (e) {
        e.preventDefault();

        Swal.fire({
            icon: 'info',
            title: 'Demo Version',
            text: 'Google Sign-in is not available in the demo version.',
            confirmButtonText: 'Ok, got it!',
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    });
</script>