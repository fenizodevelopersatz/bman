<?php $this->load->view('admin/Layout/common_style'); ?>
<link rel="stylesheet" href="<?php echo base_url('assets/admin/plugins/global/plugins.bundle.css'); ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* --- Pro look & feel --- */
.report-shell{max-width:1280px;margin-inline:auto}
.report-card{border:1px solid #eef0f4;border-radius:14px;box-shadow:0 8px 24px rgba(30, 41, 59, .06)}
.section-title{font-weight:700;letter-spacing:.3px;color:#101828}
.subtle{color:#667085}
.form-label{font-weight:600}
.help-icon{cursor:pointer; font-size:12px; border:1px solid #d0d5dd; color:#667085; border-radius:999px; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; margin-left:6px;}
.summary-grid .row{padding:.35rem 0}
.summary-grid .row + .row{border-top:1px dashed #e5eaee}
.summary-label{color:#667085}
.summary-val{font-weight:700}
.kpi{display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:18px}
@media(max-width:992px){.kpi{grid-template-columns:1fr}}
.btn-pill{border-radius:999px}
hr.soft{border-top:1px dashed #e5eaee}
.badge-soft{background:#f2f4f7;color:#344054;border-radius:999px;padding:.25rem .5rem;font-size:.75rem}
.small-muted{font-size:.825rem;color:#6c757d}
.justify-content-centers {
    display: flex;
    flex-wrap: wrap;
}
</style>


    <?php $this->load->view('admin/Layout/admin_topbar'); ?>
    
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
<?php $this->load->view('admin/Layout/admin_sidebar'); ?>


  <div class="app-container container-xxl py-6 report-shell">

     
    <div class="row g-6">
      <!-- LEFT: FORM -->
      <div class="col-lg-6">
        <div class="card p-4 report-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="section-title m-0">Binary MLM Business Report</h4>
            <span class="badge-soft">Planning Tool</span>
          </div>
          <p class="subtle mb-4">
            Use this to estimate payouts and company profit using your current plan. 
            It doesn’t write to the database.
          </p>

          <form id="reportForm">
            <!-- PACKAGE DETAILS -->
            <div class="mb-3 d-flex align-items-center">
              <h6 class="section-title m-0">PACKAGE DETAILS</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Package PV"
                data-bs-content="Joining Package PV is the PV for the signup pack. Additional Product PV is any add-on PV per member.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Base Currency</label>
                <select name="currency" class="form-select">
                  <option value="USD" selected>USD</option>
                </select>
              </div>

              <div class="mb-3 d-flex align-items-center">
                <h6 class="section-title m-0">PACKAGE</h6>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Select Package</label>
                  <select name="package_id" id="package_id" class="form-select">
                    <option value="0">— Manual (no package) —</option>
                    <?php foreach ($packages as $p): ?>
                      <option value="<?php echo (int)$p->id; ?>"><?php echo htmlentities($p->package_name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="small-muted mt-1" id="pkgHint"></div>
                </div>
              </div>
              <hr class="soft my-3"/>
              <div class="col-md-4">
                <label class="form-label">Joining Package PV</label>
                <input type="number" step="0.01" min="0" name="join_pv" class="form-control" value="100">
              </div>
              <div class="col-md-4">
                <label class="form-label">Additional Product PV</label>
                <input type="number" step="0.01" min="0" name="add_pv" class="form-control" value="0">
              </div>
            </div>

            <!-- BINARY TREE DEPTH -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">BINARY TREE DEPTH</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Levels"
                data-bs-content="Number of full binary levels under the root (excludes the root). A complete tree at L=3 contains 14 members.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Number of Levels</label>
                <select name="levels" class="form-select">
                  <?php for($i=1;$i<=12;$i++): ?>
                    <option value="<?php echo $i;?>" <?php echo $i==3?'selected':'';?>><?php echo $i;?></option>
                  <?php endfor;?>
                </select>
              </div>
            </div>

            <!-- COMPENSATIONS -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">COMPENSATIONS</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Sponsor Bonus"
                data-bs-content="Direct bonus on your personal recruits’ package PV. In this planner it assumes two directs (left & right).">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Sponsor Bonus %</label>
                <input type="number" step="0.01" min="0" name="sponsor_pct" class="form-control" value="10">
              </div>
            </div>

            <!-- BINARY BONUS -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">BINARY BONUS</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Binary Engine"
                data-bs-content="Pairs are formed from left/right members using the ratio (e.g., 1:1). Percent pays on weak units × PV. Fixed pays a flat amount per pair.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Pairing Type</label>
                <select name="pair_ratio" class="form-select">
                  <option value="1:1" selected>1:1</option>
                  <option value="1:2">1:2</option>
                  <option value="2:1">2:1</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Binary Type</label>
                <select name="binary_type" class="form-select">
                  <option value="percent" selected>Percent</option>
                  <option value="amount">Fixed / Pair</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Binary %</label>
                <input type="number" step="0.01" min="0" name="binary_percent" class="form-control" value="20">
              </div>
              <div class="col-md-4">
                <label class="form-label">Fixed/Pair (if type = amount)</label>
                <input type="number" step="0.01" min="0" name="binary_amount" class="form-control" value="20">
              </div>
            </div>

            <!-- MATCHING BONUS -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">MATCHING BONUS</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Matching"
                data-bs-content="Applies a % for each matching level on the binary bonus. In this planner we use the same % per level for simplicity.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Matching % (per level)</label>
                <input type="number" step="0.01" min="0" name="match_percent" class="form-control" value="10">
              </div>
              <div class="col-md-4">
                <label class="form-label">Matching Bonus Levels</label>
                <select name="match_levels" class="form-select">
                  <?php for($i=0;$i<=10;$i++): ?>
                    <option value="<?php echo $i;?>" <?php echo $i==2?'selected':'';?>><?php echo $i;?></option>
                  <?php endfor;?>
                </select>
              </div>
            </div>

            <!-- CAPPING -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">CAPPING / MAXIMUM PAYOUT</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Capping"
                data-bs-content="Limits the payout on selected components (sponsor/binary/matching). Anything above the cap is shown as flushed.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Capping (Max Payout)</label>
                <input type="number" step="0.01" min="0" name="cap_value" class="form-control" value="10">
                <div class="small-muted">0 = unlimited</div>
              </div>
              <div class="col-md-8">
                <label class="form-label d-block">Capping Scope</label>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="cap_sponsor" value="1" checked>
                  <label class="form-check-label">Sponsor Bonus</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="cap_binary" value="1" checked>
                  <label class="form-check-label">Binary Bonus</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="cap_match" value="1">
                  <label class="form-check-label">Matching Bonus</label>
                </div>
              </div>
            </div>

            <!-- EXPENSES -->
            <div class="mt-4 mb-2 d-flex align-items-center mb-5">
              <h6 class="section-title m-0">EXPENSES</h6>
              <span class="help-icon" data-bs-toggle="popover" data-bs-trigger="hover focus"
                data-bs-title="Admin & Tax"
                data-bs-content="Business/Admin charges and TDS are applied on the paid bonus after capping.">
                ?
              </span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Business Expense/Admin Charges %</label>
                <input type="number" step="0.01" min="0" name="admin_charges" class="form-control" value="10">
              </div>
              <div class="col-md-4">
                <label class="form-label">TDS %</label>
                <input type="number" step="0.01" min="0" name="tds_pct" class="form-control" value="11">
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="reset" class="btn btn-light btn-pill">Reset</button>
              <button type="submit" class="btn btn-primary btn-pill px-4">Simulate</button>
            </div>

            <!-- CALCULATION REFERENCE -->
            <div class="accordion mt-4" id="calcRef">
              <div class="accordion-item">
                <h2 class="accordion-header" id="h1">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c1">
                    How this is calculated (admin note)
                  </button>
                </h2>
                <div id="c1" class="accordion-collapse collapse" data-bs-parent="#calcRef">
                  <div class="accordion-body small">
                    <ul class="mb-2">
                      <li><b>Total members</b> in full binary at L: <code>2^(L+1) - 2</code></li>
                      <li><b>Pairs</b> from members using ratio a:b: <code>floor(min(L/a, R/b))</code></li>
                      <li><b>Percent Model</b>: Binary = <code>pairs × min(a,b) × PV_per_member × (binary%/100)</code></li>
                      <li><b>Fixed Model</b>: Binary = <code>pairs × fixed_per_pair</code></li>
                      <li><b>Matching</b>: <code>binary × (levels × matching%)/100</code></li>
                      <li><b>Capping</b>: apply max to selected components; remainder is flushed</li>
                      <li><b>Expenses</b>: Admin% + TDS% of paid bonus</li>
                      <li><b>Company Profit</b>: <code>Revenue − (Paid Bonus + Expenses)</code></li>
                    </ul>
                    <div class="small-muted">Tip: For PV vs BV clarification, see Commission Settings → Binary Pair On.</div>
                  </div>
                </div>
              </div>
            </div>
            <!-- /CALCULATION REFERENCE -->

          </form>
        </div>
      </div>

      <!-- RIGHT: SUMMARY -->
      <div class="col-lg-6">
        <div class="card p-4 report-card">
          <h6 class="section-title mb-3">SUMMARY</h6>
          <div id="summary" class="summary-grid"></div>
          <hr class="soft my-4 d-flex ">
          <div class=" col-lg-12 justify-content-centers">
            <div class="col-lg-6"><canvas id="chartPayoutVsExpenses"></canvas></div>
            <div class="col-lg-6"><canvas id="chartRevenue"></canvas></div>
            <div class="col-lg-6"><canvas id="chartBonuses"></canvas></div>
          </div>
        </div>
      </div>
    </div>

  </div>
<?php $this->load->view('admin/Layout/admin_footer'); ?>
<?php $this->load->view('admin/Layout/common_script');?>

<script src="<?php echo base_url('assets/admin/plugins/global/plugins.bundle.js');?>"></script>

<script>
const RUN_URL = "<?php echo site_url('admin/binary-business-report/run');?>";
let ch1,ch2,ch3;

// expose package data to JS
const packages = <?php
  $out = [];
  foreach ($packages as $p) {
    $out[$p->id] = [
      'name' => $p->package_name,
      'bv'   => (float)$p->bv,
      'direct_commission' => (float)$p->direct_commission,
      'pair_commission'   => (float)$p->pair_commission,
      'pair_commission_type' => $p->pair_commission_type ?: 'percent',
      'daily_max_pairs'   => (int)$p->daily_max_pairs,
      'matching'          => $p->matching_bonus_json ? json_decode($p->matching_bonus_json,true) : []
    ];
  }
  echo json_encode($out);
?>;

function lock(el, locked){ el.disabled = locked; el.classList.toggle('bg-light', locked); }

function applyPackage(pkgId){
  const hint = document.getElementById('pkgHint');
  const f = document.getElementById('reportForm');
  const joinPV = f.querySelector('[name="join_pv"]');
  const addPV  = f.querySelector('[name="add_pv"]');
  const sponsor = f.querySelector('[name="sponsor_pct"]');
  const binType = f.querySelector('[name="binary_type"]');
  const binPct  = f.querySelector('[name="binary_percent"]');
  const binAmt  = f.querySelector('[name="binary_amount"]');
  const matchPct= f.querySelector('[name="match_percent"]');
  const matchLvl= f.querySelector('[name="match_levels"]');

  if (!pkgId || !packages[pkgId]) {
    hint.innerHTML = 'Manual mode: set values below.';
    [joinPV, addPV, sponsor, binType, binPct, binAmt, matchPct, matchLvl].forEach(el=>lock(el,false));
    return;
  }
  const p = packages[pkgId];

  // BV drives "PV per member" in simulator
  joinPV.value = p.bv || 0;
  addPV.value  = 0;

  // direct commission (percent assumed in planner; type is taken from commission_config)
  sponsor.value = p.direct_commission || 0;

  // binary from package
  binType.value = p.pair_commission_type || 'percent';
  if (binType.value === 'percent') {
    binPct.value = p.pair_commission || 0;
    binAmt.value = 0;
  } else {
    binAmt.value = p.pair_commission || 0;
    binPct.value = 0;
  }

  // matching: show schema (we still submit dummy inputs; backend uses package JSON when package_id > 0)
  const schema = Array.isArray(p.matching) ? p.matching : [];
  hint.innerHTML = `
    <span class="badge-soft">Package:</span> <b>${p.name}</b>
    <span class="ms-2 badge-soft">BV</span> ${p.bv||0}
    <span class="ms-2 badge-soft">Direct</span> ${p.direct_commission||0}
    <span class="ms-2 badge-soft">Pair</span> ${p.pair_commission_type||'percent'} = ${p.pair_commission||0}
    <span class="ms-2 badge-soft">Daily Cap</span> ${p.daily_max_pairs||0}
    <div class="small-muted mt-1">Matching schema: [${schema.join(', ')}]</div>
  `;

  // Lock package-controlled inputs
  [joinPV, addPV, sponsor, binType, binPct, binAmt].forEach(el=>lock(el,true));

  // matching inputs are ignored when package selected; lock them
  [matchPct, matchLvl].forEach(el=>lock(el,true));
}

document.getElementById('package_id').addEventListener('change', e=>applyPackage(parseInt(e.target.value||'0')));

// Keep your existing render/drawDonut functions…
function money(v,c){ return Number(v).toLocaleString(undefined,{maximumFractionDigits:2})+' '+c; }
function unit(v){ return Number(v).toLocaleString(undefined,{maximumFractionDigits:2}); }
function row(label, value, subtle=false){ return `
  <div class="row align-items-center">
    <div class="col-7 summary-label ${subtle?'small-muted':''}">${label}</div>
    <div class="col-5 text-end summary-val">${value}</div>
  </div>`; }

function render(out){
  if(!out.status){ 
    document.getElementById('summary').innerHTML = '<div class="alert alert-danger">'+out.message+'</div>'; 
    return; 
  }
  const s = out.data.summary, c = s.currency;
  let html = '';
  if (s.package_name) html += row('Package', `<b>${s.package_name}</b>`);
  html += row('No. of Levels', `${s.levels} <span class="subtle">Levels</span>`);
  html += row('Total Members', `<span>${s.total_members}</span> <span class="subtle">Members</span>`);
  html += row('PV per Member (BV)', money(s.pv_per_member, c));
  html += row('Revenue from Product Purchase', money(s.revenue, c));

  html += '<hr class="soft">';
  html += row('Sponsor Bonus', money(s.sponsor_bonus,c));
  html += row('Eligible Pairs', `${s.eligible_pairs} <span class="subtle">Pair(s)</span>`);
  const binMeta = (s.binary_type==='percent') ? `${unit(s.binary_percent)}% @ ${s.pair_ratio}` : `${unit(s.binary_amount)}/pair @ ${s.pair_ratio}`;
  html += row('Binary Bonus', `${money(s.binary_bonus,c)} <span class="subtle">( ${binMeta} )</span>`);
  if (s.matching_levels>0) {
    html += row('Matching Bonus', `${money(s.matching_bonus,c)} <span class="subtle">( levels: ${s.matching_levels}, schema: [${(s.matching_schema||[]).join(', ')}] )</span>`);
  } else {
    html += row('Matching Bonus', money(s.matching_bonus,c), true);
  }
  if (s.own_bonus>0) html += row('Own Commission', money(s.own_bonus,c));

  html += '<hr class="soft">';
  html += row('Flushed Amount (after capping)', money(s.flushed_amount,c));
  html += row('Total Bonus (after capping)', money(s.total_bonus_after_capping,c));

  html += '<hr class="soft">';
  html += row('Business Expense/Admin Charges', `${money(s.admin_charges,c)} <span class="subtle">( ${unit(s.admin_charges_pct)}% )</span>`);
  html += row('TDS', `${money(s.tds,c)} <span class="subtle">( ${unit(s.tds_pct)}% )</span>`);
  html += row('Expenses From Business', money(s.expenses,c));

  html += '<hr class="soft">';
  html += row('Company Profit', money(s.company_profit,c));

  document.getElementById('summary').innerHTML = html;

  drawDonut('chartPayoutVsExpenses', out.data.charts.payoutVsExpenses.labels, out.data.charts.payoutVsExpenses.data);
  drawDonut('chartRevenue',          out.data.charts.revenueBreakdown.labels, out.data.charts.revenueBreakdown.data);
  drawDonut('chartBonuses',          out.data.charts.payoutBreakdown.labels,  out.data.charts.payoutBreakdown.data);
}

function drawDonut(id, labels, data){
  const ctx = document.getElementById(id);
  if (ctx._chart) ctx._chart.destroy();
  ctx._chart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, borderWidth:0 }] },
    options: { plugins:{ legend:{ position:'bottom' } }, cutout:'62%', layout:{ padding:10 } }
  });
}

document.getElementById('reportForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fetch(RUN_URL, { method:'POST', body:fd })
    .then(r=>r.json()).then(render)
    .catch(()=>{ document.getElementById('summary').innerHTML = '<div class="alert alert-danger">Failed to simulate</div>'; });
});

// init
applyPackage(0);
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el=> new bootstrap.Popover(el));
</script>

</div>
</div>
</body>
</html>
