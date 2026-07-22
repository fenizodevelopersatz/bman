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

<script>
    // Site-wide notification bell — no socket/push infra exists in this app;
    // "auto update" is the same 60s poll used for the sidebar badges above.
    (function () {
        const list = document.getElementById('dash-bell-list');
        const countEl = document.getElementById('dash-bell-count');
        if (!list || !countEl) return;

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        function typeIcon(t) {
            if (t === 'kyc') return 'ki-verify text-primary';
            if (t === 'withdrawal') return 'ki-dollar text-warning';
            if (t === 'support') return 'ki-message-text-2 text-info';
            return 'ki-information text-muted';
        }
        function refresh() {
            fetch(hostUrl + 'admin/dashboard/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(j => {
                    if (!j.status) return;
                    const items = j.items || [];
                    countEl.textContent = items.length > 99 ? '99+' : items.length;
                    countEl.classList.toggle('d-none', items.length === 0);
                    list.innerHTML = items.length
                        ? items.map(function (it) {
                            return '<a href="' + esc(it.href) + '" class="menu-item menu-link d-flex align-items-center px-3 py-2">' +
                                '<i class="ki-duotone ' + typeIcon(it.type) + ' fs-3 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                                '<div class="d-flex flex-column"><span>' + esc(it.text) + '</span>' +
                                '<span class="fs-9 text-muted">' + esc(it.at) + '</span></div></a>';
                        }).join('')
                        : '<div class="menu-item px-3 py-4 text-muted">No new notifications.</div>';
                })
                .catch(function () {});
        }
        refresh();
        setInterval(refresh, 60000);
    })();
</script>
