<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Gas Stats partial — loaded by dashboard_v2.php -->
<div class="row g-4 mb-5" id="dash-gas-scope">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="bullet bullet-dot bg-warning h-10px w-10px"></span>
                <span class="fw-bold text-gray-700 fs-6">Gas Fee Overview</span>
                <span class="text-muted fs-8">(blockchain transaction costs)</span>
            </div>
            <div class="d-flex align-items-center gap-2" id="dash-gas-live-ticker">
                <span class="bullet bullet-dot bg-success h-6px w-6px" style="animation:gas-pulse 2s infinite"></span>
                <span class="text-muted fs-9">Live gas:</span>
                <span id="dash-gas-slow" class="fs-9 text-info fw-bold">—</span>
                <span class="text-muted fs-9">|</span>
                <span id="dash-gas-std" class="fs-9 text-success fw-bold">—</span>
                <span class="text-muted fs-9">|</span>
                <span id="dash-gas-fast" class="fs-9 fw-bold" style="color:#f59e0b">—</span>
                <span class="text-muted fs-9">Gwei (Slow|Std|Fast)</span>
                <a href="<?php echo base_url('admin/finance/gas-fee-transactions'); ?>" class="btn btn-sm btn-light-warning py-1 px-3 fs-9 ms-2">View All Gas Txs</a>
            </div>
        </div>
    </div>

    <!-- Gas Today -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-flush h-100 mb-5 mb-xl-5" style="border-top:3px solid #f3ba2f">
            <div class="card-body py-4 px-5">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="ki-duotone ki-dollar fs-2" style="color:#f3ba2f"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Gas Spent Today</span>
                </div>
                <div class="d-flex align-items-end gap-2">
                    <span class="fs-3 fw-bold text-gray-900" id="dash-gas-today-bnb">—</span>
                    <span class="text-muted fs-8 mb-1">BNB</span>
                </div>
                <div class="text-muted fs-9" id="dash-gas-today-count">— transactions</div>
            </div>
        </div>
    </div>

    <!-- Gas This Month -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-flush h-100 mb-5 mb-xl-5" style="border-top:3px solid #10b981">
            <div class="card-body py-4 px-5">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="ki-duotone ki-chart-simple fs-2" style="color:#10b981"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Gas This Month</span>
                </div>
                <div class="d-flex align-items-end gap-2">
                    <span class="fs-3 fw-bold text-gray-900" id="dash-gas-month-bnb">—</span>
                    <span class="text-muted fs-8 mb-1">BNB</span>
                </div>
                <div class="text-muted fs-9" id="dash-gas-month-count">— transactions</div>
            </div>
        </div>
    </div>

    <!-- Avg Gas Price -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-flush h-100 mb-5 mb-xl-5" style="border-top:3px solid #7239ea">
            <div class="card-body py-4 px-5">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="ki-duotone ki-graph-up fs-2" style="color:#7239ea"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Avg Gas Price (MTD)</span>
                </div>
                <div class="d-flex align-items-end gap-2">
                    <span class="fs-3 fw-bold text-gray-900" id="dash-gas-avg-gwei">—</span>
                    <span class="text-muted fs-8 mb-1">Gwei</span>
                </div>
                <div class="text-muted fs-9">30-day rolling average</div>
            </div>
        </div>
    </div>

    <!-- Failed Txs Today -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-flush h-100 mb-5 mb-xl-5" style="border-top:3px solid #f1416c">
            <div class="card-body py-4 px-5">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="ki-duotone ki-cross-circle fs-2" style="color:#f1416c"><span class="path1"></span><span class="path2"></span></i>
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Failed Txs Today</span>
                </div>
                <div class="d-flex align-items-end gap-2">
                    <span class="fs-3 fw-bold text-gray-900" id="dash-gas-failed">—</span>
                    <span class="text-muted fs-8 mb-1">failed/reverted</span>
                </div>
                <div class="text-muted fs-9">On-chain only</div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes gas-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
</style>

<script>
(function () {
    const base = '<?php echo base_url(); ?>';

    function loadGasStats() {
        fetch(base + 'admin/dashboard/gas-stats', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(j => {
                if (!j.status || !j.data) return;
                const d = j.data;
                const el = id => document.getElementById(id);
                if (el('dash-gas-today-bnb'))  el('dash-gas-today-bnb').textContent  = parseFloat(d.gas_today_bnb).toFixed(6);
                if (el('dash-gas-today-count')) el('dash-gas-today-count').textContent = (d.gas_today_count || 0) + ' transaction(s)';
                if (el('dash-gas-month-bnb'))  el('dash-gas-month-bnb').textContent  = parseFloat(d.gas_month_bnb).toFixed(6);
                if (el('dash-gas-month-count')) el('dash-gas-month-count').textContent = (d.gas_month_count || 0) + ' transaction(s)';
                if (el('dash-gas-avg-gwei'))   el('dash-gas-avg-gwei').textContent   = parseFloat(d.avg_gas_gwei).toFixed(2);
                if (el('dash-gas-failed'))     el('dash-gas-failed').textContent     = d.failed_today || 0;
            }).catch(() => {});
    }

    function loadLiveGas() {
        fetch(base + 'admin/all-transaction/live-gas', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(j => {
                if (!j.status || !j.data || !j.data.available) return;
                const d = j.data;
                const el = id => document.getElementById(id);
                if (el('dash-gas-slow')) el('dash-gas-slow').textContent = d.slow;
                if (el('dash-gas-std'))  el('dash-gas-std').textContent  = d.standard;
                if (el('dash-gas-fast')) el('dash-gas-fast').textContent = d.fast;
            }).catch(() => {});
    }

    loadGasStats();
    loadLiveGas();
    // Refresh live gas every 30s, stats every 5min
    setInterval(loadLiveGas, 30000);
    setInterval(loadGasStats, 300000);
})();
</script>
