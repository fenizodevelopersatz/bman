<script>
    var hostUrl = "<?php echo base_url(); ?>";
    document.addEventListener('DOMContentLoaded', function () {
        const toolbarContainer = document.querySelector('#kt_app_toolbar_container');

        if (toolbarContainer) {
            const backDiv = document.createElement('div');
            backDiv.className = 'd-flex align-items-center mt-3';

            const backBtn = document.createElement('button');
            backBtn.type = 'button';
            backBtn.className = 'btn btn-light-danger btn-sm';
            backBtn.id = 'goBackBtn';
            backBtn.innerHTML = '<i class="ki-duotone ki-left fs-2"></i> Back';

            backBtn.addEventListener('click', function () {
                if (document.referrer !== '') {
                    window.history.back();
                } else {
                    window.location.href = "<?= base_url('admin/dashboard') ?>"; // fallback
                }
            });

            backDiv.appendChild(backBtn);
            toolbarContainer.appendChild(backDiv);
        }
    });


</script>
<script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
<script src="<?php echo base_url(); ?>/assets/admin/js/scripts.bundle.js"></script>