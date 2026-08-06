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
<script src="<?php echo base_url(); ?>assets/admin/plugins/global/plugins.bundle.js"></script>
<script src="<?php echo base_url(); ?>assets/admin/js/scripts.bundle.js"></script>
<?php $this->load->view("partials/browser_controls"); ?>

<script>
    // Single site-wide poll — one HTTP round trip instead of four separate
    // ones (sidebar badges, bell notifications, admin alerts, system health).
    // Runs on every admin page; each piece of UI just reads its own field off
    // the shared response and no-ops if it isn't present on this page. No
    // socket/push infra exists anywhere in this app — this is the one timer
    // everything shares instead of each widget polling independently.
    (function () {
        const badges = document.querySelectorAll('[data-dashboard-badge]');
        const bellList = document.getElementById('dash-bell-list');
        const bellCount = document.getElementById('dash-bell-count');
        const alertsWrap = document.getElementById('dash-alerts-wrap');
        const alertsBody = document.getElementById('dash-alerts-body');
        const needsPoll = badges.length || bellList || alertsWrap;
        if (!needsPoll) return;

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        function typeIcon(t) {
            if (t === 'kyc') return 'ki-verify text-primary';
            if (t === 'withdrawal') return 'ki-dollar text-warning';
            if (t === 'support') return 'ki-message-text-2 text-info';
            return 'ki-information text-muted';
        }

        function applyBadges(counts) {
            if (!badges.length) return;
            badges.forEach(function (el) {
                const key = el.getAttribute('data-dashboard-badge');
                const n = parseInt((counts || {})[key] || 0, 10);
                el.textContent = n;
                el.classList.toggle('d-none', n <= 0);
            });
        }

        function applyBell(items) {
            if (!bellList || !bellCount) return;
            items = items || [];
            bellCount.textContent = items.length > 99 ? '99+' : items.length;
            bellCount.classList.toggle('d-none', items.length === 0);
            bellList.innerHTML = items.length
                ? items.map(function (it) {
                    return '<a href="' + esc(it.href) + '" class="menu-item menu-link d-flex align-items-center px-3 py-2">' +
                        '<i class="ki-duotone ' + typeIcon(it.type) + ' fs-3 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                        '<div class="d-flex flex-column"><span>' + esc(it.text) + '</span>' +
                        '<span class="fs-9 text-muted">' + esc(it.at) + '</span></div></a>';
                }).join('')
                : '<div class="menu-item px-3 py-4 text-muted">No new notifications.</div>';
        }

        function applyAlerts(alerts) {
            if (!alertsWrap || !alertsBody) return;
            alerts = alerts || [];
            if (!alerts.length) { alertsWrap.style.display = 'none'; return; }
            alertsWrap.style.display = '';
            alertsBody.innerHTML = alerts.map(function (a) {
                const cls = a.level === 'danger' ? 'danger' : 'warning';
                const inner = '<i class="ki-duotone ki-information fs-2 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' + esc(a.text);
                return '<div class="alert alert-' + cls + ' d-flex align-items-center py-3 mb-0">' +
                    (a.href ? '<a href="' + esc(a.href) + '" class="text-' + cls + ' d-flex align-items-center text-decoration-none">' + inner + '</a>' : inner) +
                    '</div>';
            }).join('');
        }

        // Dashboard-only widgets (System Health) that also want this same
        // payload without a second timer — dashboard_v2.php listens for this.
        function applySystemHealth(health) {
            if (!health) return;
            window.dispatchEvent(new CustomEvent('dashPollSystemHealth', { detail: health }));
        }

        function refresh() {
            fetch(hostUrl + 'admin/dashboard/poll', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(j => {
                    if (!j.status) return;
                    applyBadges(j.sidebar_counts);
                    applyBell(j.notifications);
                    applyAlerts(j.alerts);
                    applySystemHealth(j.system_health);
                })
                .catch(function () {});
        }
        refresh();
        setInterval(refresh, 60000);
    })();
</script>
