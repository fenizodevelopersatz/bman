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
<?php $this->load->view("partials/browser_controls"); ?>

<script>
    // Site-wide sidebar "new" count badges (pending withdrawals/KYC/support) —
    // runs on every admin page so the counts stay visible while navigating,
    // not just on the dashboard itself.
    (function () {
        const badges = document.querySelectorAll('[data-dashboard-badge]');
        if (!badges.length) return;
        function refresh() {
            fetch(hostUrl + 'admin/dashboard/sidebar-counts', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(j => {
                    if (!j.status) return;
                    badges.forEach(function (el) {
                        const key = el.getAttribute('data-dashboard-badge');
                        const n = parseInt(j.data[key] || 0, 10);
                        el.textContent = n;
                        el.classList.toggle('d-none', n <= 0);
                    });
                })
                .catch(function () {});
        }
        refresh();
        setInterval(refresh, 60000);
    })();
</script>
