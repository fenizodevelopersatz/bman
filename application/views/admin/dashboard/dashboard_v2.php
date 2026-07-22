<?php $this->load->view('admin/Layout/common_style'); ?>

<style>
    .chart-container{ position:relative; }
    #dash-hotwallet-refresh.spin i{ animation: dash-spin 0.8s linear infinite; display:inline-block; }
    @keyframes dash-spin{ to{ transform: rotate(360deg); } }
    .dash-stat-card .card-body{ padding:1.5rem; }

    /* Glassmorphism pass — scoped to #dash-glass-scope only, so it never
       affects any other admin page. No precedent existed for this style
       anywhere else in the codebase; kept deliberately self-contained. */
    #dash-glass-scope .card{
        background: rgba(var(--bs-body-bg-rgb, 255,255,255), 0.72);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(var(--bs-gray-500-rgb, 148,163,184), .18);
        box-shadow: 0 8px 28px rgba(17, 24, 39, .06);
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    #dash-glass-scope .card::before{
        content: "";
        position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, #4F46E5, #10B981);
        opacity: .9;
    }
    #dash-glass-scope .card:hover{
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(17, 24, 39, .10);
    }
    #dash-glass-scope .dash-stat-card{
        background: linear-gradient(160deg, rgba(79,70,229,.06), rgba(16,185,129,.05));
    }
    [data-bs-theme="dark"] #dash-glass-scope .card{
        background: rgba(var(--bs-dark-rgb, 17,24,39), 0.55);
        border-color: rgba(255,255,255,.08);
        box-shadow: 0 8px 28px rgba(0,0,0,.35);
    }
    [data-bs-theme="dark"] #dash-glass-scope .dash-stat-card{
        background: linear-gradient(160deg, rgba(79,70,229,.14), rgba(16,185,129,.10));
    }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

            <?php $this->load->view('admin/Layout/admin_topbar'); ?>

            <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                            <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">Admin</a>
                                        </li>
                                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                        <li class="breadcrumb-item text-muted"><?php echo $title; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-5">
                            <div id="kt_app_content_container" class="app-container  container-xxl ">

                                <?php $this->load->view('notification'); ?>

                                <div id="dash-glass-scope">

                                <?php $this->load->view('admin/dashboard/partials/_hot_wallet'); ?>

                                <?php $this->load->view('admin/dashboard/partials/_admin_alerts'); ?>

                                <?php $this->load->view('admin/dashboard/partials/_stat_cards'); ?>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_wallet_summary'); ?>
                                    <?php $this->load->view('admin/dashboard/partials/_staking_analytics'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_package_distribution'); ?>
                                    <?php $this->load->view('admin/dashboard/partials/_binary_summary'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_binary_growth_chart'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_active_users_chart'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_roi_liability'); ?>
                                    <?php $this->load->view('admin/dashboard/partials/_bonus_reduction'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_rank_summary'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_withdrawal_center'); ?>
                                    <?php $this->load->view('admin/dashboard/partials/_kyc_support'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_quick_actions'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_recent_transactions'); ?>
                                </div>

                                <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                                    <?php $this->load->view('admin/dashboard/partials/_activity_feed'); ?>
                                    <?php $this->load->view('admin/dashboard/partials/_system_health'); ?>
                                </div>

                                </div><!-- /#dash-glass-scope -->

                            </div>
                        </div>

                    </div>

                    <?php $this->load->view('admin/Layout/admin_footer'); ?>

                </div>
            </div>

        </div>
    </div>

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up"><span class="path1"></span><span class="path2"></span></i>
    </div>

    <?php $this->load->view('admin/Layout/common_script'); ?>

    <script>
    (function () {
        const base = '<?php echo base_url(); ?>';
        const DEFAULT_AVATAR = '<?php echo default_avatar_url(); ?>';
        const fmt = n => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
        const count = (id, value) => { try { new countUp.CountUp(id, parseFloat(value) || 0).start(); } catch (e) { const el = document.getElementById(id); if (el) el.textContent = fmt(value); } };
        const fetchJson = (url) => fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());

        function loadStats() {
            fetchJson(base + 'admin/dashboard/stats').then(j => {
                if (!j.status) return;
                const h = j.header, w = j.wallets;
                count('dash-members-total', h.members_total);
                count('dash-members-active', h.members_active);
                count('dash-members-inactive', h.members_inactive);
                count('dash-total-staking', h.total_staking_bman);
                count('dash-total-deposits', h.total_deposits_usdt);
                count('dash-total-withdrawals-usdt', h.total_withdrawals_usdt);
                count('dash-total-withdrawals-bman', h.total_withdrawals_bman);
                count('dash-total-bonus', h.total_bonus_paid_bman);
                count('dash-online-chat', j.online_in_chat);
                count('dash-members-online', j.online_in_chat);

                count('dash-wallet-usdt', w.usdt);
                count('dash-wallet-exchange', w.exchange);
                count('dash-wallet-earning', w.earning);
                count('dash-wallet-staking', w.staking);
                count('dash-wallet-bonus', w.bonus);
                count('dash-wallet-total-bman', (parseFloat(w.exchange || 0) + parseFloat(w.earning || 0) + parseFloat(w.staking || 0) + parseFloat(w.bonus || 0)));

                document.getElementById('dash-platform-in').textContent = fmt(h.total_deposits_usdt) + ' USDT';
                document.getElementById('dash-platform-out').textContent = fmt(h.total_withdrawals_usdt) + ' USDT / ' + fmt(h.total_withdrawals_bman) + ' BMAN';
            });
        }

        function loadStakingAnalytics() {
            fetchJson(base + 'admin/dashboard/staking-analytics').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-stakes-active', d.active);
                count('dash-stakes-reached-maturity', d.reached_maturity);
                count('dash-stakes-withdrawn', d.withdrawn);
                count('dash-stakes-avg', d.average_stake);
            });
        }

        function loadPackageDonut() {
            fetchJson(base + 'admin/dashboard/package-distribution').then(j => {
                if (!j.status) return;
                const rows = (j.rows || []).filter(r => +r.stakes > 0);
                const el = document.querySelector('#dash-package-donut');
                if (!el) return;
                if (!rows.length) { el.innerHTML = '<div class="text-muted text-center pt-10">No stakes yet.</div>'; return; }
                const chart = new ApexCharts(el, {
                    chart: { type: 'donut', height: 320 },
                    series: rows.map(r => +r.stakes),
                    labels: rows.map(r => r.name),
                    legend: { position: 'bottom' },
                });
                chart.render();
            });
        }

        function loadBinarySummary() {
            fetchJson(base + 'admin/dashboard/binary-summary').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-binary-left-volume', d.left_volume);
                count('dash-binary-right-volume', d.right_volume);
                count('dash-binary-matching', d.total_matching);
                count('dash-binary-carry', (parseFloat(d.left_carry || 0) + parseFloat(d.right_carry || 0)));
                count('dash-binary-today', d.today_matching);
                count('dash-binary-weekly', d.weekly_matching);
            });
        }

        let dashGrowthChart = null;
        function loadGrowthChart(days) {
            days = days || 30;
            fetchJson(base + 'admin/dashboard/binary-growth?days=' + encodeURIComponent(days)).then(j => {
                if (!j.status) return;
                const d = j.data;
                const el = document.querySelector('#dash-growth-chart');
                if (!el) return;
                if (dashGrowthChart) { dashGrowthChart.destroy(); dashGrowthChart = null; }
                dashGrowthChart = new ApexCharts(el, {
                    chart: { type: 'area', height: 340, toolbar: { show: false } },
                    series: [
                        { name: 'New Registrations', data: d.registrations },
                        { name: 'Staking Purchases', data: d.stakes_purchased },
                        { name: 'Withdraw Requests', data: d.withdrawals },
                    ],
                    colors: ['#3B82F6', '#22C55E', '#EF4444'],
                    xaxis: { categories: d.labels, labels: { rotate: -45 } },
                    stroke: { curve: 'smooth', width: 2 },
                    dataLabels: { enabled: false },
                });
                dashGrowthChart.render();
            });
        }
        (function () {
            const sel = document.getElementById('dash-growth-range');
            const label = document.getElementById('dash-growth-range-label');
            if (!sel) return;
            const labels = { '7': 'Last 7 Days', '30': 'Last 30 Days', '90': 'Last 90 Days', '365': 'Last Year' };
            sel.addEventListener('change', function () {
                if (label) label.textContent = labels[sel.value] || (sel.value + ' Days');
                loadGrowthChart(parseInt(sel.value, 10));
            });
        })();

        let dashActiveUsersChart = null;
        function loadActiveUsersChart(days) {
            days = days || 30;
            fetchJson(base + 'admin/dashboard/active-user-trend?days=' + encodeURIComponent(days)).then(j => {
                if (!j.status) return;
                const d = j.data;
                const el = document.querySelector('#dash-activeusers-chart');
                if (!el) return;
                if (dashActiveUsersChart) { dashActiveUsersChart.destroy(); dashActiveUsersChart = null; }
                dashActiveUsersChart = new ApexCharts(el, {
                    chart: { type: 'line', height: 280, toolbar: { show: false } },
                    series: [{ name: 'Active Users', data: d.active_users }],
                    colors: ['#8B5CF6'],
                    xaxis: { categories: d.labels, labels: { rotate: -45 } },
                    stroke: { curve: 'smooth', width: 3 },
                    markers: { size: 3 },
                    dataLabels: { enabled: false },
                });
                dashActiveUsersChart.render();
            });
        }
        (function () {
            const sel = document.getElementById('dash-activeusers-range');
            const label = document.getElementById('dash-activeusers-range-label');
            if (!sel) return;
            const labels = { '7': 'Last 7 Days', '30': 'Last 30 Days', '90': 'Last 90 Days', '365': 'Last Year' };
            sel.addEventListener('change', function () {
                if (label) label.textContent = labels[sel.value] || (sel.value + ' Days');
                loadActiveUsersChart(parseInt(sel.value, 10));
            });
        })();

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function loadRankSummary() {
            fetchJson(base + 'admin/dashboard/rank-summary').then(j => {
                if (!j.status) return;
                const h = j.data.headline, dist = j.data.distribution || [];
                count('dash-rank-ranked-members', h.ranked_members);
                count('dash-rank-promotions-24h', h.promotions_24h);
                count('dash-rank-rewards-paid', h.rewards_paid);
                count('dash-rank-rewards-failed', h.rewards_failed);
                const body = document.getElementById('dash-rank-distribution-body');
                body.innerHTML = dist.map(r => {
                    var icon = r.badge_image
                        ? '<img src="' + base + r.badge_image + '" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;" class="me-2">'
                        : '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + esc(r.badge_color || '#ccc') + ';margin-right:8px;"></span>';
                    var clickable = (+r.members > 0) ? ' style="cursor:pointer;" data-rank-id="' + esc(r.id) + '" data-rank-name="' + esc(r.rank_name) + '"' : '';
                    return '<tr class="' + ((+r.members > 0) ? 'dash-rank-row' : '') + '"' + clickable + '><td>' + icon + esc(r.rank_name) + '</td><td>' + fmt(r.members) + '</td><td>' + fmt(r.percent) + '%</td></tr>';
                }).join('') || '<tr><td colspan="3" class="text-muted">No rank data.</td></tr>';
            });
        }

        function fmtDate(s) {
            if (!s) return '—';
            var d = new Date(s.replace(' ', 'T'));
            return isNaN(d.getTime()) ? esc(s) : d.toLocaleDateString();
        }

        document.addEventListener('click', function (e) {
            const row = e.target.closest('.dash-rank-row');
            if (!row) return;
            const rankId = row.getAttribute('data-rank-id');
            const rankName = row.getAttribute('data-rank-name');
            document.getElementById('dashRankMembersTitle').textContent = rankName + ' — Members';
            const body = document.getElementById('dash-rank-members-body');
            body.innerHTML = '<tr><td colspan="4" class="text-muted">Loading…</td></tr>';
            const modalEl = document.getElementById('dashRankMembersModal');
            if (modalEl && window.bootstrap) new bootstrap.Modal(modalEl).show();
            fetchJson(base + 'admin/dashboard/rank-members/' + encodeURIComponent(rankId)).then(j => {
                if (!j.status) return;
                const rows = j.rows || [];
                body.innerHTML = rows.map(m => {
                    var avatar = m.profile_img
                        ? '<img src="' + base + m.profile_img + '" style="width:24px;height:24px;border-radius:50%;object-fit:cover;margin-right:8px;">'
                        : '';
                    return '<tr><td>' + avatar + esc(m.username) + '</td><td>' + esc(m.email) + '</td><td>' + fmt(m.group_volume) + '</td><td>' + fmtDate(m.achieved_at) + '</td></tr>';
                }).join('') || '<tr><td colspan="4" class="text-muted">No members found.</td></tr>';
            });
        });

        function loadWithdrawalCenter() {
            fetchJson(base + 'admin/dashboard/withdrawal-center').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-wd-bman-pending', d.bman.pending);
                count('dash-wd-bman-completed', d.bman.completed);
                count('dash-wd-bman-rejected', d.bman.rejected);
                count('dash-wd-usdt-pending', d.usdt.pending);
                count('dash-wd-usdt-approved', d.usdt.approved);
                count('dash-wd-usdt-rejected', d.usdt.rejected);
                count('dash-wd-today', d.today_requests);
            });
        }

        function loadKycSupport() {
            fetchJson(base + 'admin/dashboard/kyc-monitor').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-kyc-pending', d.pending);
                count('dash-kyc-approved', d.approved);
                count('dash-kyc-rejected', d.rejected);
                count('dash-kyc-expired', d.expired);
                count('dash-members-kyc-verified', d.approved);
            });
            fetchJson(base + 'admin/dashboard/support-center').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-support-pending', d.pending);
                count('dash-support-open', d.open);
                count('dash-support-closed', d.closed);
                count('dash-support-today', d.today);
            });
        }

        function loadRecentTransactions() {
            fetchJson(base + 'admin/all-transaction/list?limit=10').then(j => {
                const body = document.getElementById('dash-recent-tx-body');
                if (!j.status || !(j.rows || []).length) { body.innerHTML = '<tr><td colspan="6" class="text-muted">No transactions yet.</td></tr>'; return; }
                body.innerHTML = j.rows.map(r => {
                    const amt = (r.direction === 'credit' ? '+' : '−') + fmt(r.amount) + ' ' + esc((r.wallet_type || '').toUpperCase());
                    const amtCls = r.direction === 'credit' ? 'text-success' : 'text-danger';
                    const chain = r.onchain ? '<span class="badge badge-light-info fs-8">' + esc(r.onchain.status || 'onchain') + '</span>' : '<span class="badge badge-light fs-8">internal</span>';
                    const avatarSrc = r.avatar || DEFAULT_AVATAR;
                    const avatar = '<img src="' + esc(avatarSrc) + '" style="width:24px;height:24px;border-radius:50%;object-fit:cover;vertical-align:middle;" class="me-2" ' +
                        'onerror="this.onerror=null;this.src=\'' + DEFAULT_AVATAR + '\';">';
                    return '<tr class="dash-tx-row" data-id="' + r.ledger_id + '" style="cursor:pointer;">' +
                        '<td class="text-muted">' + esc(r.created_at) + '</td>' +
                        '<td>' + avatar + '#' + esc(r.user_id) + '</td>' +
                        '<td class="text-muted">#' + esc(r.ledger_id) + '</td>' +
                        '<td>' + esc(r.type_label) + '</td>' +
                        '<td class="fw-bold ' + amtCls + '">' + amt + '</td>' +
                        '<td>' + chain + '</td></tr>';
                }).join('');
                body.querySelectorAll('.dash-tx-row').forEach(function (tr) {
                    tr.addEventListener('click', function () { openTxDetail(tr.getAttribute('data-id')); });
                });
            });
        }

        let dashTxDetailModal = null;
        function openTxDetail(id) {
            const modalEl = document.getElementById('dashTxDetailModal');
            const body = document.getElementById('dash-tx-detail-body');
            if (!modalEl || !body) return;
            if (!dashTxDetailModal && window.bootstrap) dashTxDetailModal = new bootstrap.Modal(modalEl);
            body.innerHTML = '<tr><td class="text-muted">Loading…</td></tr>';
            if (dashTxDetailModal) dashTxDetailModal.show();
            fetchJson(base + 'admin/all-transaction/detail?id=' + encodeURIComponent(id)).then(j => {
                if (!j.status) { body.innerHTML = '<tr><td class="text-danger">' + esc(j.message || 'Not found') + '</td></tr>'; return; }
                const r = j.row;
                const rows = [
                    ['Ledger ID', esc(r.ledger_id)],
                    ['User', '#' + esc(r.user_id)],
                    ['Type', esc(r.type_label)],
                    ['Amount', (r.direction === 'credit' ? '+' : '−') + fmt(r.amount)],
                    ['Balance After', fmt(r.balance_after)],
                    ['Description', esc(r.description || '—')],
                    ['When', esc(r.created_at)],
                ];
                if (r.onchain) {
                    rows.push(['Tx Hash', esc(r.onchain.tx_hash || '—')]);
                    rows.push(['Chain Status', esc(r.onchain.status || '—')]);
                    rows.push(['From', esc(r.onchain.from_address || '—')]);
                    rows.push(['To', esc(r.onchain.to_address || '—')]);
                    rows.push(['Gas Fee', r.onchain.gas_fee_total != null ? fmt(r.onchain.gas_fee_total) + ' BNB' : '—']);
                    if (r.onchain.tx_hash && j.explorer_url) {
                        rows.push(['Explorer', '<a href="' + esc(j.explorer_url) + '/tx/' + esc(r.onchain.tx_hash) + '" target="_blank" rel="noopener">View on explorer ↗</a>']);
                    }
                }
                body.innerHTML = rows.map(function (pair) {
                    return '<tr><td class="fw-bold text-muted" style="width:140px;">' + pair[0] + '</td><td>' + pair[1] + '</td></tr>';
                }).join('');
            });
        }

        let dashStakingPopup = null;
        function loadStakingPopup(months) {
            const body = document.getElementById('dash-staking-popup-body');
            body.innerHTML = '<tr><td colspan="4" class="text-muted">Loading…</td></tr>';
            const qs = months ? ('?months=' + encodeURIComponent(months)) : '';
            fetchJson(base + 'admin/dashboard/package-distribution-detail' + qs).then(j => {
                if (!j.status || !(j.rows || []).length) { body.innerHTML = '<tr><td colspan="4" class="text-muted">No stakes in this period.</td></tr>'; return; }
                body.innerHTML = j.rows.map(r => '<tr><td>' + esc(r.name) + '</td><td>' + fmt(r.duration_years) + ' yr</td><td>' + fmt(r.stakes) + '</td><td>' + fmt(r.total_staked) + '</td></tr>').join('');
            });
        }
        (function () {
            const card = document.getElementById('dash-total-staking-card');
            const modalEl = document.getElementById('dashStakingPopup');
            if (!card || !modalEl) return;
            card.addEventListener('click', function () {
                if (!dashStakingPopup && window.bootstrap) dashStakingPopup = new bootstrap.Modal(modalEl);
                if (dashStakingPopup) dashStakingPopup.show();
                loadStakingPopup('');
            });
            modalEl.querySelectorAll('[data-months]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modalEl.querySelectorAll('[data-months]').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    loadStakingPopup(btn.getAttribute('data-months'));
                });
            });
        })();

        // Single load on page open only — this hits a live blockchain RPC,
        // unlike everything else on the dashboard (cheap DB queries), so it
        // does NOT sit on a recurring timer. Refresh button re-fetches on demand.
        // BNB balances are typically small (gas-scale) — the generic fmt()/count()
        // helpers cap at 2 decimals, which would show "0.00" for real gas-wallet
        // amounts. This keeps full floating precision (trimmed of trailing zeros).
        function fmtFloat(n) {
            const v = parseFloat(n);
            if (isNaN(v)) return '0';
            return v.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 8 });
        }
        function loadHotWallet() {
            const btn = document.getElementById('dash-hotwallet-refresh');
            if (btn) btn.classList.add('spin');
            fetchJson(base + 'admin/dashboard/hot-wallet').then(j => {
                if (btn) btn.classList.remove('spin');
                if (!j.status) return;
                const d = j.data;
                const wrap = document.getElementById('dash-hotwallet-wrap');
                if (!d.configured) { wrap.style.display = 'none'; return; }
                const addrEl = document.getElementById('dash-hotwallet-address');
                if (d.error) {
                    addrEl.textContent = d.error;
                    return;
                }
                if (d.treasury) {
                    const link = d.explorer_url ? (d.explorer_url + '/address/' + d.treasury.address) : null;
                    addrEl.innerHTML = 'Treasury: ' +
                        (link ? '<a href="' + esc(link) + '" target="_blank" rel="noopener" class="text-muted text-decoration-underline">' + esc(d.treasury.address) + ' ↗</a>' : esc(d.treasury.address)) +
                        ' · updated ' + new Date().toLocaleTimeString();
                    document.getElementById('dash-hotwallet-bnb').textContent = fmtFloat(d.treasury.bnb);
                    document.getElementById('dash-hotwallet-bman').textContent = fmtFloat(d.treasury.bman);
                }
                if (d.gas) {
                    document.getElementById('dash-hotwallet-gas-wrap').style.display = '';
                    document.getElementById('dash-hotwallet-gas-bnb').textContent = fmtFloat(d.gas.bnb);
                }
            }).catch(function () { if (btn) btn.classList.remove('spin'); });
        }

        function loadRoiLiability() {
            fetchJson(base + 'admin/dashboard/roi-liability').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-roi-paid', d.paid);
                count('dash-roi-pending', d.pending);
                count('dash-roi-future', d.future);
            });
        }

        function loadBonusReduction() {
            fetchJson(base + 'admin/dashboard/bonus-reduction').then(j => {
                if (!j.status) return;
                const d = j.data;
                count('dash-bonus-lifetime', d.lifetime_total);
                count('dash-bonus-recent', d.recent_total);
                count('dash-bonus-wallet-balance', d.admin_wallet_balance);
                count('dash-bonus-count', d.reduction_count);
                document.getElementById('dash-bonus-percent').textContent = fmt(d.percent);
                document.getElementById('dash-bonus-interval').textContent = fmt(d.interval_days);
                document.getElementById('dash-bonus-interval-2').textContent = fmt(d.interval_days);
            });
        }

        // Admin Alerts is now populated by the single site-wide poller in
        // common_script.php (admin/dashboard/poll) — no separate fetch/timer
        // here, see "Do not send more repeated requests" feedback.

        function loadActivityFeed() {
            fetchJson(base + 'admin/dashboard/activity-feed?limit=20').then(j => {
                const el = document.getElementById('dash-activity-feed');
                if (!j.status || !(j.rows || []).length) { el.innerHTML = '<div class="text-muted">No recent activity.</div>'; return; }
                el.innerHTML = j.rows.map(r => '<div class="d-flex justify-content-between border-bottom border-gray-200 pb-2"><span class="fs-7">' + esc(r.text) + '</span><span class="fs-8 text-muted text-nowrap ms-2">' + esc(r.at) + '</span></div>').join('');
            });
        }

        // Rendered from the shared poller's dispatched event (see
        // common_script.php) instead of its own fetch/timer — one fewer
        // repeated request per minute.
        window.addEventListener('dashPollSystemHealth', function (e) { renderSystemHealth(e.detail); });
        function renderSystemHealth(d) {
                if (!d) return;
                document.getElementById('dash-health-db').innerHTML = d.database.ok
                    ? '<span class="badge badge-light-success">Connected</span>' : '<span class="badge badge-light-danger">Down</span>';
                document.getElementById('dash-health-rpc').innerHTML = d.rpc.success_rate === null
                    ? '<span class="badge badge-light">No data (24h)</span>'
                    : '<span class="badge badge-light-' + (d.rpc.success_rate >= 95 ? 'success' : 'warning') + '">' + d.rpc.success_rate + '%</span>';
                document.getElementById('dash-health-smtp').innerHTML = d.smtp.configured
                    ? '<span class="badge badge-light-success">Configured</span>' : '<span class="badge badge-light-danger">Not Configured</span>';
                document.getElementById('dash-health-storage').innerHTML = d.storage.used_percent === null
                    ? '<span class="badge badge-light">Unknown</span>'
                    : '<span class="badge badge-light-' + (d.storage.used_percent < 85 ? 'success' : 'warning') + '">' + d.storage.used_percent + '%</span>';
                const cronEl = document.getElementById('dash-health-cron');
                cronEl.innerHTML = (d.cron || []).length
                    ? d.cron.map(c => '<div class="d-flex justify-content-between fs-8"><span>' + esc(c.name) + '</span><span class="badge badge-light-' + (c.status === 'success' || c.status === 'ok' ? 'success' : 'danger') + '">' + esc(c.status) + ' · ' + c.minutes_ago + 'm ago</span></div>').join('')
                    : '<div class="text-muted fs-8">No cron runs logged yet.</div>';
        }

        loadStats();
        loadStakingAnalytics();
        loadPackageDonut();
        loadBinarySummary();
        loadGrowthChart();
        loadActiveUsersChart();
        loadRankSummary();
        loadWithdrawalCenter();
        loadKycSupport();
        loadRecentTransactions();
        loadActivityFeed();
        loadHotWallet();
        loadRoiLiability();
        loadBonusReduction();
        const hotWalletRefreshBtn = document.getElementById('dash-hotwallet-refresh');
        if (hotWalletRefreshBtn) hotWalletRefreshBtn.addEventListener('click', loadHotWallet);
        setInterval(loadActivityFeed, 30000);
        // Alerts + System Health: driven by common_script.php's single shared
        // poll (60s), not a separate timer here.
        // Hot Wallet: single load only — it's a live blockchain RPC call, not
        // a cheap DB query, so it does NOT auto-poll. Use the Refresh button.
    })();
    </script>
</body>
</html>
