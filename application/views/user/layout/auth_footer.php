<div class=" d-flex flex-stack">
    <!--begin::Languages-->   
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