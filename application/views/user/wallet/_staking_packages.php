<?php
/**
 * BMAN Staking Packages — read-only explainer block for /user/lending.
 * Expects:
 *   $staking_packages : array of ['id','name','stake_amount','bonus_percent',
 *                       'group_ceiling','roi'=>['fixed_2'=>['roi_percent','roi_basis'],…]]
 *   $staking_plans    : array of ['name','code','roi_credit_mode','credit_days',
 *                       'withdraw_after_maturity','withdraw_frequency_days','terms'=>[…]]
 * Both are optional; the block hides itself when there are no active packages.
 */
$staking_packages = isset($staking_packages) && is_array($staking_packages) ? $staking_packages : [];
$staking_plans    = isset($staking_plans) && is_array($staking_plans) ? $staking_plans : [];
$owned_stake_ids  = isset($owned_stake_ids) && is_array($owned_stake_ids) ? $owned_stake_ids : [];

if (!empty($staking_packages)):

// Which durations each plan type actually offers — admin's "Durations offered"
// checkboxes (staking_plan_terms), same source the plan-explainer badge above uses.
$hasPlanTerms = !empty($staking_plans);
$fixedYears   = [];
$regularYears = [];
foreach ($staking_plans as $pl) {
    $yrs = array_map(function ($t) { return (int)$t['duration_years']; }, $pl['terms'] ?? []);
    if (($pl['code'] ?? '') === 'fixed')   $fixedYears   = $yrs;
    if (($pl['code'] ?? '') === 'regular') $regularYears = $yrs;
}

// which durations actually appear across the ROI matrix (fallback 2/3/5)
$durations = [];
foreach ($staking_packages as $p) {
    foreach (array_keys($p['roi'] ?? []) as $k) {
        $yr = (int) substr(strrchr($k, '_'), 1);
        if ($yr > 0) $durations[$yr] = true;
    }
}
$durations = array_keys($durations);
if ($hasPlanTerms) {
    // A row only makes sense if Fixed or Regular actually offers that term.
    $durations = array_intersect($durations, array_unique(array_merge($fixedYears, $regularYears)));
}
$durations = array_values($durations);
sort($durations);
if (empty($durations)) $durations = [2, 3, 5];

$plan_icon = ['fixed' => 'ph-lock-key', 'regular' => 'ph-calendar-dots', 'combo' => 'ph-shuffle'];
?>
<style>
  .stk-wrap{margin:8px 0 26px;}
  .stk-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
  .stk-head h3{font-size:19px;font-weight:1100;margin:0;display:flex;align-items:center;gap:8px;color:var(--text,#0b1220);}
  .stk-head .stk-sub{color:var(--muted,#6b7280);font-size:13px;font-weight:800;max-width:640px;}
  .stk-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,.10);color:#4f46e5;
    border:1px solid rgba(99,102,241,.22);border-radius:999px;padding:6px 12px;font-size:12px;font-weight:900;}
  /* plan explainer */
  .stk-plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:20px;}
  .stk-plan{background:var(--card,#fff);border:1px solid rgba(15,23,42,.08);border-radius:16px;padding:16px 18px;
    box-shadow:0 6px 18px rgba(15,23,42,.04);}
  .stk-plan .pi{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;font-size:20px;
    background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;margin-bottom:10px;}
  .stk-plan h4{margin:0 0 4px;font-size:15px;font-weight:1100;color:var(--text,#0b1220);}
  .stk-plan p{margin:0;font-size:12.5px;line-height:1.55;color:var(--muted,#6b7280);font-weight:700;}
  .stk-plan .pmeta{margin-top:10px;font-size:11.5px;font-weight:900;color:#0b1220;display:flex;flex-wrap:wrap;gap:6px;}
  .stk-plan .pmeta span{background:rgba(15,23,42,.05);border-radius:8px;padding:3px 8px;}
  /* package cards */
  .stk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;}
  .stk-card{position:relative;background:var(--card,#fff);border:1px solid rgba(15,23,42,.08);border-radius:18px;
    padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.05);transition:transform .15s,box-shadow .15s;overflow:hidden;}
  .stk-card:hover{transform:translateY(-3px);box-shadow:0 14px 32px rgba(67,56,202,.14);border-color:rgba(99,102,241,.35);}
  .stk-card::before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,#6366f1,#22c55e);}
  .stk-card .amt{font-size:22px;font-weight:1200;color:var(--text,#0b1220);line-height:1;}
  .stk-card .amt small{font-size:12px;font-weight:900;color:var(--muted,#6b7280);}
  .stk-card .nm{font-size:12.5px;font-weight:900;color:var(--muted,#6b7280);margin-top:2px;}
  .stk-badges{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 12px;}
  .stk-b{display:inline-flex;align-items:center;gap:5px;border-radius:9px;padding:5px 9px;font-size:11.5px;font-weight:900;}
  .stk-b.bonus{background:rgba(34,197,94,.12);color:#15803d;}
  .stk-b.ceil{background:rgba(234,179,8,.14);color:#a16207;}
  .stk-roi{width:100%;border-collapse:collapse;font-size:12.5px;}
  .stk-roi th,.stk-roi td{padding:7px 8px;text-align:center;border-bottom:1px solid rgba(15,23,42,.06);}
  .stk-roi thead th{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted,#6b7280);font-weight:1000;}
  .stk-roi tbody td:first-child,.stk-roi thead th:first-child{text-align:left;font-weight:1000;color:#0b1220;}
  .stk-roi .fx{color:#4338ca;font-weight:1100;}
  .stk-roi .rg{color:#0f766e;font-weight:1100;}
  .stk-roi .na{color:#cbd5e1;font-weight:900;}
  .stk-foot{margin-top:10px;font-size:11px;line-height:1.5;color:var(--muted,#6b7280);font-weight:700;
    display:flex;gap:12px;flex-wrap:wrap;}
  .stk-foot b{color:#0b1220;}
  .stk-card.owned{border-color:#22c55e;box-shadow:0 10px 28px rgba(34,197,94,.18);}
  .stk-card .owned-rib{position:absolute;top:14px;right:-32px;transform:rotate(45deg);background:#22c55e;color:#fff;
    font-size:10px;font-weight:1000;letter-spacing:.5px;padding:3px 36px;box-shadow:0 4px 10px rgba(34,197,94,.3);z-index:2;}
  .stkm-table-wrap{margin-top:10px;overflow:auto;border:1px solid rgba(15,23,42,.10);border-radius:14px;background:#fff;}
  .stkm-table{width:100%;border-collapse:collapse;font-size:12px;min-width:520px;}
  .stkm-table th,.stkm-table td{padding:10px 12px;border-bottom:1px solid rgba(15,23,42,.08);white-space:nowrap;}
  .stkm-table th{font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc;}
  .stkm-table tr:last-child td{border-bottom:0;}
  .stkm-table .is-active{background:rgba(99,102,241,.08);}
  .stkm-table .option-name{font-weight:900;color:#0b1220;}
  @media (max-width:520px){.stk-card .amt{font-size:21px;}}
</style>

<section class="stk-wrap">
  <div class="stk-head">
    <div>
      <h3><i class="ph-fill ph-stack"></i> BMAN Staking Packages</h3>
      <div class="stk-sub">Stake a fixed amount of BMAN and earn ROI over a chosen term. Each package pays a
        one-time <b>Bonus</b> and is subject to a <b>Group Ceiling</b>. ROI depends on the <b>plan</b> (Fixed / Regular)
        and the <b>term</b> (2 / 3 / 5 years) you pick.</div>
    </div>
    <span class="stk-chip"><i class="ph ph-seal-check"></i> <?= count($staking_packages) ?> Active Packages</span>
  </div>

  <?php if (!empty($staking_plans)): ?>
  <div class="stk-plans">
    <?php foreach ($staking_plans as $pl):
      $code = $pl['code'] ?? '';
      $mode = $pl['roi_credit_mode'] ?? '';
      $days = trim((string)($pl['credit_days'] ?? ''));
      $terms = array_map(function ($t) { return (int)$t['duration_years']; }, $pl['terms'] ?? []);
      sort($terms);
      if ($code === 'fixed') {
        $desc = 'ROI accrues as one total percentage and is credited at the end of the term (maturity). Principal + ROI become withdrawable once the term matures.';
      } elseif ($code === 'regular') {
        $desc = 'ROI is credited every month'.($days ? ' on day(s) '.$days : '').'. You receive a steady monthly percentage across the whole term.';
      } else {
        $desc = 'A blend of Fixed and Regular: part of your ROI pays monthly while the rest is settled at maturity.';
      }
    ?>
    <div class="stk-plan">
      <div class="pi"><i class="ph <?= $plan_icon[$code] ?? 'ph-cube' ?>"></i></div>
      <h4><?= htmlspecialchars($pl['name'] ?? ucfirst($code)) ?></h4>
      <p><?= htmlspecialchars($desc) ?></p>
      <div class="pmeta">
        <span><i class="ph ph-clock"></i> Credit: <?= htmlspecialchars(ucfirst($mode ?: '—')) ?></span>
        <?php if (!empty($terms)): ?><span><i class="ph ph-calendar"></i> <?= implode(' / ', $terms) ?> yrs</span><?php endif; ?>
        <?php if ($code === 'fixed'): ?><span><i class="ph ph-lock-simple-open"></i> Withdraw after maturity</span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="stk-grid">
    <?php foreach ($staking_packages as $p):
      $roi = $p['roi'] ?? [];
      $owned = in_array((int)$p['id'], $owned_stake_ids, true);
    ?>
    <?php $isSpecialPkg = !empty($p['is_special']) && !empty($p['special_roi']); ?>
    <div class="stk-card<?= $owned ? ' owned' : '' ?><?= $isSpecialPkg ? ' special' : '' ?>">
      <?php if ($owned): ?><span class="owned-rib">OWNED</span><?php endif; ?>
      <div class="amt"><?= number_format((float)$p['stake_amount']) ?> <small>BMAN</small></div>
      <div class="stk-badges">
        <span class="stk-b bonus"><i class="ph-fill ph-gift"></i> <?= rtrim(rtrim(number_format((float)$p['bonus_percent'], 2), '0'), '.') ?>% Bonus</span>
        <?php if ($isSpecialPkg): ?><span class="stk-b special-chip"><i class="ph-fill ph-star"></i> Special Offer</span><?php endif; ?>
      </div>

      <?php if ($isSpecialPkg): ?>
      <table class="stk-roi">
        <thead>
          <tr>
            <th>Term</th>
            <th>Monthly <span style="font-weight:700;text-transform:none;">(ROI)</span></th>
            <th>Maturity <span style="font-weight:700;text-transform:none;">(bonus)</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($p['special_roi'] as $yr => $sr): ?>
          <tr>
            <td><?= (int)$yr ?> Year<?= $yr > 1 ? 's' : '' ?></td>
            <td><span class="rg"><?= rtrim(rtrim(number_format((float)$sr['monthly_roi_percent'], 3), '0'), '.') ?>% /mo</span></td>
            <td><span class="fx"><?= rtrim(rtrim(number_format((float)$sr['maturity_percent'], 3), '0'), '.') ?>%</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="stk-foot">
        <span><b>Monthly:</b> credited each month</span>
        <span><b>Maturity:</b> bonus at term end</span>
      </div>
      <?php /* No "View ROI Structure" button here: the table directly above is
               that structure, from the same $p['special_roi'] data. The modal it
               used to open repeated those exact three columns and added nothing.
               The per-term BMAN figures (monthly credit, maturity bonus,
               principal, total return) live in the SELECT purchase modal, where
               a term has actually been chosen and they can be computed. */ ?>
      <?php else: ?>
      <table class="stk-roi">
        <thead>
          <tr>
            <th>Term</th>
            <th>Fixed <span style="font-weight:700;text-transform:none;">(total)</span></th>
            <th>Regular <span style="font-weight:700;text-transform:none;">(monthly)</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($durations as $yr):
            $fx = (!$hasPlanTerms || in_array($yr, $fixedYears, true))   ? ($roi['fixed_'.$yr]['roi_percent']   ?? null) : null;
            $rg = (!$hasPlanTerms || in_array($yr, $regularYears, true)) ? ($roi['regular_'.$yr]['roi_percent'] ?? null) : null;
          ?>
          <tr>
            <td><?= (int)$yr ?> Years</td>
            <td><?= $fx !== null ? '<span class="fx">'.rtrim(rtrim(number_format((float)$fx, 3), '0'), '.').'%</span>' : '<span class="na">—</span>' ?></td>
            <td><?= $rg !== null ? '<span class="rg">'.rtrim(rtrim(number_format((float)$rg, 3), '0'), '.').'% /mo</span>' : '<span class="na">—</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="stk-foot">
        <span><b>Fixed:</b> total ROI at maturity</span>
        <span><b>Regular:</b> % credited monthly</span>
      </div>
      <?php endif; ?>

      <button type="button" class="stk-buy" onclick="<?= $isSpecialPkg ? 'stkSpecialOpen' : 'stkOpen' ?>(<?= (int)$p['id'] ?>)">
        <i class="ph ph-lock-key"></i> SELECT
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===================== SPECIAL OFFER styling + ROI popup ===================== -->
<?php
$specialRoiMap = [];   // offered (selectable) years: id => [{year, monthly, maturity}]
$specialRoiName = [];  // id => package name
$specialRoiMeta = [];  // id => {stake, bonus}
$specialRoiFull = [];  // id => {year => monthly% (float)} for every configured year
foreach ($staking_packages as $__p) {
  if (!empty($__p['is_special']) && !empty($__p['special_roi'])) {
    $__id = (int) $__p['id'];
    $__rows = [];
    foreach ($__p['special_roi'] as $__yr => $__sr) {
      $__rows[] = [
        'year'     => (int) $__yr,
        'monthly'  => (float) $__sr['monthly_roi_percent'],
        'maturity' => (float) $__sr['maturity_percent'],
      ];
    }
    $specialRoiMap[$__id]  = $__rows;
    $specialRoiName[$__id] = $__p['name'];
    $specialRoiMeta[$__id] = ['stake' => (float) $__p['stake_amount'], 'bonus' => (float) $__p['bonus_percent']];
    $__full = [];
    foreach (($__p['special_roi_full'] ?? []) as $__y => $__r) {
      if ((float) $__r['monthly_roi_percent'] > 0) $__full[(int) $__y] = (float) $__r['monthly_roi_percent'];
    }
    $specialRoiFull[$__id] = $__full;
  }
}
?>
<style>
  .stk-card.special{ border:1.5px solid #f5c451 !important; box-shadow:0 12px 32px rgba(245,158,11,.20) !important;
    background:linear-gradient(180deg,#fffdf6 0%,#ffffff 46%) !important; }
  /* .special-rib kept for the Special-Offer purchase modal header (.stksp-h) */
  .special-rib{ position:absolute; top:10px; left:10px; z-index:3;
    background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:900; font-size:10px;
    letter-spacing:.5px; padding:4px 11px; border-radius:999px; box-shadow:0 4px 12px rgba(245,158,11,.45); }
  .stk-b.special-chip{ background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:900;
    box-shadow:0 4px 12px rgba(245,158,11,.35); }
</style>
<?php /* The .stk-viewroi button styles and the .stkroi-* modal styles were
         removed along with the button itself — that modal only repeated the
         ROI table already printed on the card. Kept as a PHP comment so the
         note does not ship to every visitor inside <style>. */ ?>
<style>
  .stksp-overlay{ position:fixed; inset:0; background:rgba(10,10,20,.6); z-index:100001; display:none;
    align-items:center; justify-content:center; padding:16px; }
  .stksp-overlay.open{ display:flex; }
  .stksp-box{ width:min(520px,95vw); max-height:90vh; overflow:auto; background:#fff; border-radius:20px; padding:0; }
  .stksp-h{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:18px 20px;
    background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
  .stksp-h .special-rib{ position:static; background:rgba(255,255,255,.22); }
  .stksp-h button{ border:0; background:rgba(255,255,255,.2); color:#fff; width:30px; height:30px; border-radius:9px; cursor:pointer; font-size:20px; line-height:1; flex:0 0 auto; }
  .stksp-body{ padding:18px 20px; }
  .stksp-label{ display:block; font-size:12px; font-weight:1000; color:#334155; margin:4px 0 8px; }
  .stksp-years{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
  .stksp-year{ border:1px solid #e6e8ef; background:#fff; color:#334155; font-weight:900; font-size:12.5px;
    border-radius:12px; padding:9px 14px; cursor:pointer; }
  .stksp-year.active{ border-color:#d97706; background:#fffbeb; color:#92400e; }
  .stksp-sched-title{ font-size:12px; font-weight:1000; color:#0b1220; margin:6px 0 8px; }
  .stksp-table{ width:100%; border-collapse:collapse; font-size:12.5px; margin-bottom:12px; }
  .stksp-table th{ text-align:left; color:#64748b; font-weight:800; font-size:10.5px; text-transform:uppercase; padding:6px; border-bottom:1px solid #eef0f4; }
  .stksp-table td{ padding:7px 6px; border-bottom:1px dashed #eef0f4; font-weight:700; color:#0b1220; }
  .stksp-table td.rg{ color:#15803d; }
  .stksp-summary{ background:#f8fafc; border:1px solid #eef0f4; border-radius:12px; padding:10px 12px; margin-bottom:12px; }
  .stksp-sum-row{ display:flex; justify-content:space-between; font-size:12.5px; font-weight:800; color:#334155; padding:3px 0; }
  .stksp-sum-row.total{ border-top:1px dashed #cbd5e1; margin-top:5px; padding-top:7px; color:#0b1220; font-size:14px; }
  .stksp-sum-row.total b{ color:#15803d; }
  .stksp-feedback{ font-size:12.5px; font-weight:800; min-height:18px; margin-bottom:10px; }
  .stksp-confirm{ width:100%; border:0; border-radius:14px; padding:13px; cursor:pointer; font-weight:1100; font-size:14px;
    color:#fff; background:linear-gradient(135deg,#f59e0b,#d97706); }
  .stksp-confirm:disabled{ opacity:.7; cursor:progress; }
</style>

<!-- Dedicated SPECIAL OFFER purchase modal (escalating year-wise ROI) -->
<div class="stksp-overlay" id="stkSpecialPop" onclick="if(event.target===this)stkSpecialClose()">
  <div class="stksp-box">
    <div class="stksp-h">
      <div>
        <span class="special-rib" style="display:inline-block;">★ SPECIAL OFFER</span>
        <div id="stkSpTitle" style="font-size:18px;font-weight:1200;margin-top:8px;"></div>
        <div id="stkSpSub" style="font-size:12px;font-weight:800;opacity:.92;"></div>
      </div>
      <button type="button" onclick="stkSpecialClose()">&times;</button>
    </div>
    <div class="stksp-body">
      <label class="stksp-label">Select Term</label>
      <div class="stksp-years" id="stkSpYears"></div>
      <div id="stkSpSchedule"></div>
      <div class="stksp-summary" id="stkSpSummary"></div>
      <div class="stksp-feedback" id="stkSpFeedback"></div>
      <button type="button" class="stksp-confirm" id="stkSpConfirm" onclick="stkSpecialConfirm()">Confirm Special Stake</button>
    </div>
  </div>
</div>
<?php $spDefaultDist = 1; foreach (($coin_distribution_options ?? []) as $__o) { if (!empty($__o['is_default'])) { $spDefaultDist = (int) $__o['id']; break; } } ?>
<script>
  var SPECIAL_ROI = <?= json_encode($specialRoiMap) ?>;
  var SPECIAL_ROI_NAME = <?= json_encode($specialRoiName) ?>;
  var SPECIAL_META = <?= json_encode($specialRoiMeta) ?>;
  var SPECIAL_FULL = <?= json_encode($specialRoiFull) ?>;
  var SP_BASE = '<?= base_url() ?>';
  var SP_SWAP_ON = <?= !empty($swap_enabled) ? 'true' : 'false' ?>;
  var SP_DEFAULT_DIST = <?= (int) $spDefaultDist ?>;
  function spFmt(v){ v = Math.round((Number(v) || 0) * 1000) / 1000; return String(v); }

  var spCur = { pid: null, year: null };
  function stkSpecialOpen(pid) {
    spCur.pid = pid; spCur.year = null;
    var meta = SPECIAL_META[pid] || { stake: 0, bonus: 25 };
    document.getElementById('stkSpTitle').textContent = (SPECIAL_ROI_NAME[pid] || 'Special Offer');
    document.getElementById('stkSpSub').textContent = Number(meta.stake).toLocaleString() + ' BMAN · ' + spFmt(meta.bonus) + '% instant bonus';
    var years = (SPECIAL_ROI[pid] || []).map(function (r) { return r.year; });
    document.getElementById('stkSpYears').innerHTML = years.map(function (y) {
      return '<button type="button" class="stksp-year" data-y="' + y + '" onclick="stkSpecialPickYear(' + y + ')">' + y + ' Year' + (y > 1 ? 's' : '') + '</button>';
    }).join('');
    document.getElementById('stkSpSchedule').innerHTML = '';
    document.getElementById('stkSpSummary').innerHTML = '';
    var fb = document.getElementById('stkSpFeedback'); fb.textContent = ''; fb.style.color = '';
    document.getElementById('stkSpConfirm').disabled = false;
    document.getElementById('stkSpConfirm').textContent = 'Confirm Special Stake';
    document.getElementById('stkSpecialPop').classList.add('open');
    if (years.length) stkSpecialPickYear(years[0]);
  }
  function stkSpecialPickYear(y) {
    spCur.year = y;
    document.querySelectorAll('#stkSpYears .stksp-year').forEach(function (b) { b.classList.toggle('active', +b.dataset.y === +y); });
    var pid = spCur.pid;
    var principal = Number((SPECIAL_META[pid] || {}).stake) || 0;
    var full = SPECIAL_FULL[pid] || {};
    // Ramp years 1..y with gap-fill (nearest lower) — mirrors the backend engine.
    var rows = ''; var totalMonthly = 0; var last = 0;
    for (var k = 1; k <= y; k++) {
      var pct = (full[k] !== undefined) ? Number(full[k]) : last; last = pct;
      var amt = principal * pct / 100;
      totalMonthly += amt * 12;
      rows += '<tr><td>Year ' + k + '</td><td class="rg">' + spFmt(pct) + '% /mo</td><td>' + Math.round(amt).toLocaleString() + ' BMAN/mo</td></tr>';
    }
    var offered = (SPECIAL_ROI[pid] || []).find(function (r) { return r.year === y; });
    var matPct = offered ? Number(offered.maturity) : 0;
    var matAmt = principal * matPct / 100;
    document.getElementById('stkSpSchedule').innerHTML =
      '<div class="stksp-sched-title">Escalating monthly ROI over ' + y + ' year' + (y > 1 ? 's' : '') + '</div>' +
      '<table class="stksp-table"><thead><tr><th>Year</th><th>Rate</th><th>Monthly credit</th></tr></thead><tbody>' + rows + '</tbody></table>';
    document.getElementById('stkSpSummary').innerHTML =
      '<div class="stksp-sum-row"><span>Total monthly ROI over term</span><b>' + Math.round(totalMonthly).toLocaleString() + ' BMAN</b></div>' +
      '<div class="stksp-sum-row"><span>Maturity bonus (' + spFmt(matPct) + '%)</span><b>' + Math.round(matAmt).toLocaleString() + ' BMAN</b></div>' +
      '<div class="stksp-sum-row"><span>Principal returned at maturity</span><b>' + Math.round(principal).toLocaleString() + ' BMAN</b></div>' +
      '<div class="stksp-sum-row total"><span>Total return</span><b>' + Math.round(totalMonthly + matAmt + principal).toLocaleString() + ' BMAN</b></div>';
  }
  function stkSpecialClose() { document.getElementById('stkSpecialPop').classList.remove('open'); }
  function stkSpecialConfirm() {
    var fb = document.getElementById('stkSpFeedback');
    if (!spCur.pid || !spCur.year) { fb.style.color = '#dc2626'; fb.textContent = 'Please pick a term.'; return; }
    var btn = document.getElementById('stkSpConfirm'); btn.disabled = true; btn.textContent = 'Processing…';
    var fd = new FormData();
    fd.append('package_id', spCur.pid);
    fd.append('plan_code', 'regular');
    fd.append('plan_type', 'regular');
    fd.append('duration_years', spCur.year);
    fd.append('coin_distribution_option_id', SP_DEFAULT_DIST);
    fd.append('plan_id', 0);
    var endpoint = SP_SWAP_ON ? 'user/lending/swap_purchase' : 'user/lending/purchase_stake';
    fetch(SP_BASE + endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.status) { fb.style.color = '#15803d'; fb.textContent = j.message || 'Special stake submitted.'; setTimeout(function () { location.reload(); }, 1400); }
        else { btn.disabled = false; btn.textContent = 'Confirm Special Stake'; fb.style.color = '#dc2626'; fb.textContent = j.message || 'Failed.'; }
      })
      .catch(function () { btn.disabled = false; btn.textContent = 'Confirm Special Stake'; fb.style.color = '#dc2626'; fb.textContent = 'Could not reach the server.'; });
  }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { stkSpecialClose(); } });
</script>

<!-- ===================== STAKING PURCHASE MODAL ===================== -->
<style>
  .stk-buy{width:100%;margin-top:12px;border:0;border-radius:12px;padding:10px;cursor:pointer;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-weight:1000;font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;}
  .stk-buy:hover{opacity:.9;}
  .stkm-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);z-index:9999;display:none;align-items:center;justify-content:center;padding:18px;}
  .stkm-overlay.open{display:flex;}
  .stkm{background:#fff;border-radius:20px;max-width:560px;width:100%;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;}
  .stkm-h{padding:18px 20px;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;display:flex;justify-content:space-between;align-items:center;}
  .stkm-h h3{margin:0;font-size:17px;font-weight:1100;}
  .stkm-h .x{cursor:pointer;font-size:22px;line-height:1;opacity:.9;background:none;border:0;color:#fff;}
  .stkm-b{padding:20px;display:grid;grid-template-columns:1fr;gap:20px;max-height:700px;overflow-y:auto;}
  .stkm-left{min-width:0;}
  .stkm-b label{display:block;font-size:11.5px;font-weight:1000;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;margin:0 0 6px;}
  .stkm-steps{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;}
  .stkm-step{flex:1;min-width:72px;border:1px solid rgba(15,23,42,.10);border-radius:999px;padding:7px 9px;font-size:10.5px;font-weight:1000;text-transform:uppercase;letter-spacing:.4px;color:#64748b;text-align:center;background:#f8fafc;}
  .stkm-step.active{background:rgba(99,102,241,.12);border-color:#6366f1;color:#4338ca;}
  .stkm-step.done{background:rgba(34,197,94,.10);border-color:#22c55e;color:#15803d;}
  .stkm-pane{display:none;}
  .stkm-pane.active{display:block;}
  .stkm-packages{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin-bottom:12px;}
  .stkm-packages button,.stkm-seg button{border:1.5px solid rgba(15,23,42,.12);background:#fff;border-radius:12px;padding:10px 9px;font-weight:1000;font-size:13px;cursor:pointer;color:#334155;transition:all .12s;}
  .stkm-packages button.active,.stkm-seg button.active{border-color:#4338ca;background:rgba(99,102,241,.10);color:#4338ca;}
  .stkm-seg{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
  .stkm-note{font-size:12px;font-weight:800;color:#64748b;line-height:1.5;margin-top:-4px;margin-bottom:12px;}
  .stkm-quote,.stkm-summary{background:#f8fafc;border:1px solid rgba(15,23,42,.08);border-radius:14px;padding:14px 16px;margin:4px 0 16px;}
  .stkm-row{display:flex;justify-content:space-between;font-size:13px;font-weight:800;color:#334155;padding:4px 0;}
  .stkm-row b{color:#0b1220;font-weight:1100;}
  .stkm-row.roi b{color:#4338ca;}
  .stkm-warn{color:#c0392b;font-size:12px;font-weight:900;margin-top:6px;display:none;}
  .stkm-feedback{display:none;margin:0 0 12px;border-radius:14px;padding:12px 14px;border:1px solid;font-size:12.5px;font-weight:800;line-height:1.45;}
  .stkm-feedback.show{display:flex;gap:10px;align-items:flex-start;}
  .stkm-feedback strong{display:block;font-size:13px;font-weight:1100;margin-bottom:2px;}
  .stkm-feedback span{display:block;}
  .stkm-feedback .stkm-feedback-ico{width:24px;height:24px;border-radius:999px;display:grid;place-items:center;flex:0 0 24px;font-size:10px;font-weight:1100;}
  .stkm-feedback.error{background:#fef2f2;border-color:#fecaca;color:#991b1b;}
  .stkm-feedback.error .stkm-feedback-ico{background:#fee2e2;color:#b91c1c;}
  .stkm-feedback.success{background:#ecfdf5;border-color:#bbf7d0;color:#166534;}
  .stkm-feedback.success .stkm-feedback-ico{background:#dcfce7;color:#15803d;}
  .stkm-feedback.info{background:#eff6ff;border-color:#bfdbfe;color:#1e40af;}
  .stkm-feedback.info .stkm-feedback-ico{background:#dbeafe;color:#1d4ed8;}
  .stkm-confirm{width:100%;border:0;border-radius:12px;padding:13px;cursor:pointer;font-weight:1100;font-size:14px;color:#fff;background:linear-gradient(135deg,#10b981,#059669);}
  .stkm-confirm:disabled{opacity:.5;cursor:not-allowed;}
  .stkm-spinner{display:inline-block;width:14px;height:14px;margin-right:8px;vertical-align:-2px;
    border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:stkm-spin .7s linear infinite;}
  @keyframes stkm-spin{to{transform:rotate(360deg);}}
  .stkm-nav{display:flex;gap:10px;margin-top:12px;}
  .stkm-nav button{flex:1;border:0;border-radius:12px;padding:12px 14px;cursor:pointer;font-weight:1100;font-size:13.5px;}
  .stkm-back{background:#eef2ff;color:#4338ca;}
  .stkm-next{background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;}
  .stkm-summary h4{margin:0 0 10px;font-size:13px;font-weight:1100;color:#0b1220;}
  .stkm-summary .sumgrid{display:grid;grid-template-columns:1fr auto;gap:8px;font-size:13px;font-weight:800;color:#334155;}
  .stkm-summary .sumgrid b{color:#0b1220;}
  .stkm-balance-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:1000;}
  .stkm-balance-pill.locked{background:rgba(239,68,68,.10);color:#b91c1c;}
  .stkm-balance-pill.available{background:rgba(34,197,94,.10);color:#15803d;}
</style>
<div class="stkm-overlay" id="stkm">
  <div class="stkm">
    <?php $isSwap = !empty($swap_enabled); ?>
    <div class="stkm-h"><h3><i class="ph-fill ph-stack"></i> <?= $isSwap ? 'Select Package' : 'Purchase Stake' ?></h3><button class="x" type="button" onclick="stkClose()">&times;</button></div>
    <div class="stkm-b">
      <!-- LEFT: Staking Setup Flow -->
      <div class="stkm-left">
        <div style="font-size:20px;font-weight:1200;color:#0b1220;margin-bottom:2px;" id="stkm-name">?</div>
        <div style="font-size:12px;font-weight:900;color:#6b7280;margin-bottom:16px;" id="stkm-amt">?</div>
        <div class="stkm-steps" id="stkm-steps"><div class="stkm-step active">Package</div><div class="stkm-step">Plan</div><div class="stkm-step">Distribution</div><div class="stkm-step">Preview</div></div>
        <div class="stkm-pane active" data-step="1"><label>Select Package</label><div class="stkm-packages" id="stkm-packages"></div><div class="stkm-note">Choose a package to continue to plan selection.</div></div>
        <div class="stkm-pane" data-step="2"><label>ROI Plan Type</label><div class="stkm-seg" id="stkm-roi-plans"></div><div class="stkm-note">Choose how you want to receive your ROI returns</div><label>Term</label><div class="stkm-seg" id="stkm-terms"></div></div>
      <div class="stkm-pane" data-step="3">
        <label>Coin Distribution Options</label>
        <div class="stkm-seg" id="stkm-distributions"></div>
        <div style="background:#f8fafc;border:1px solid rgba(15,23,42,.08);border-radius:14px;padding:14px 16px;margin:12px 0;margin-top:16px;">
          <div style="font-size:13px;font-weight:800;color:#334155;line-height:1.6;" id="stkm-distribution-desc">Select an option above</div>
        </div>
        <div class="stkm-note">Choose how your principal is split across wallets. The 25% package bonus is separate and is shown as Instant Bonus.</div>
      </div>
      <div class="stkm-pane" data-step="4">
        <!-- ROI Details Section -->
        <div style="background:#f8fafc;border:1px solid rgba(15,23,42,.08);border-radius:14px;padding:16px;margin-bottom:16px;">
          <div style="font-size:13px;font-weight:1100;color:#0b1220;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
            <i class="ph ph-chart-line"></i> ROI Details & Returns
          </div>

          <!-- Principal vs ROI Cards -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
            <div style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:12px;text-align:center;">
              <div style="font-size:11px;color:#6b7280;margin-bottom:6px;font-weight:1000;">PRINCIPAL</div>
              <div style="font-size:18px;font-weight:1100;color:#4338ca;margin-bottom:2px;" id="stkm-roi-principal">?</div>
              <div style="font-size:11px;color:#334155;">BMAN</div>
              <div style="font-size:10px;color:#ef4444;margin-top:4px;">🔒 LOCKED</div>
            </div>
            <div style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:12px;text-align:center;">
              <div style="font-size:11px;color:#6b7280;margin-bottom:6px;font-weight:1000;">EXPECTED ROI</div>
              <div style="font-size:18px;font-weight:1100;color:#22c55e;margin-bottom:2px;" id="stkm-roi-return">?</div>
              <div style="font-size:11px;color:#334155;">BMAN</div>
              <div style="font-size:10px;color:#22c55e;margin-top:4px;">🔓 LIQUID</div>
            </div>
          </div>

          <!-- ROI Key Info -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;">
            <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:8px;text-align:center;">
              <div style="font-size:10px;color:#4338ca;font-weight:1000;">ROI RATE</div>
              <div style="font-size:14px;font-weight:1100;color:#4338ca;margin-top:2px;" id="stkm-roi-rate">?</div>
            </div>
            <div style="background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.2);border-radius:10px;padding:8px;text-align:center;">
              <div style="font-size:10px;color:#a16207;font-weight:1000;">DURATION</div>
              <div style="font-size:14px;font-weight:1100;color:#a16207;margin-top:2px;" id="stkm-roi-duration">?</div>
            </div>
            <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:8px;text-align:center;">
              <div style="font-size:10px;color:#b91c1c;font-weight:1000;">BONUS</div>
              <div style="font-size:14px;font-weight:1100;color:#b91c1c;margin-top:2px;" id="stkm-roi-bonus">?</div>
            </div>
          </div>

        </div>

        <!-- Original Quote Section -->
        <div class="stkm-quote">
          <div class="stkm-row roi"><span>ROI (this plan/term)</span><b id="stkm-roi">?</b></div>
          <div class="stkm-row"><span>Cost</span><b id="stkm-cost">? USDT</b></div>
          <div class="stkm-row"><span><?= $isSwap ? 'BMAN ? Exchange Wallet' : 'Locked into Staking Wallet' ?></span><b id="stkm-lock">? BMAN</b></div>
          <div class="stkm-row"><span>Distribution Bonus Wallet (<span id="stkm-bonus-pct">0</span>%)</span><b id="stkm-bonus">? BMAN</b></div>
          <div class="stkm-row"><span>Instant Bonus (25%)</span><b id="stkm-instant">? BMAN</b></div>
          <div class="stkm-row"><span>Your USDT Balance</span><b id="stkm-bal">? USDT</b></div>
          <div class="stkm-warn" id="stkm-warn">Insufficient USDT balance ? deposit USDT first.</div>
          <div style="border-top:1px dashed rgba(15,23,42,.12);margin-top:8px;padding-top:8px;">
            <div style="font-size:10.5px;font-weight:1000;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:4px;">Live allocation preview</div>
            <div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Exchange</span><b id="stkm-bw-exchange">?</b></div>
            <div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Staking (locked)</span><b id="stkm-bw-staking">?</b></div>
            <div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Bonus allocation (locked)</span><b id="stkm-bw-bonus">?</b></div>
            <div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Earning</span><b id="stkm-bw-earning">?</b></div>
          </div>
        </div>
      </div>
      <div class="stkm-feedback" id="stkm-feedback" role="alert" aria-live="polite"></div>
      <div class="stkm-nav"><button class="stkm-back" id="stkm-back" type="button">Back</button><button class="stkm-next" id="stkm-next" type="button">Next</button></div>
      <button class="stkm-confirm" id="stkm-go" type="button" onclick="stkConfirm()" style="margin-top:10px;display:none;"> Confirm</button>
      </div>

    </div>
  </div>
</div>
<script>
(function(){
  const BASE = '<?= base_url() ?>';
  const PKGS = <?= json_encode(array_map(function($p){ return ['id'=>(int)$p['id'],'name'=>$p['name'],'stake'=>(float)$p['stake_amount'],'bonus_pct'=>(float)$p['bonus_percent'],'roi'=>array_map(function($c){return ['pct'=>(float)$c['roi_percent'],'basis'=>$c['roi_basis']];}, $p['roi'] ?? [])]; }, $staking_packages)) ?>;
  const PLANS = <?= json_encode(array_map(function($pl){ return ['code'=>$pl['code'],'name'=>$pl['name'],'combo_fixed_pct'=>isset($pl['combo_fixed_pct'])?(float)$pl['combo_fixed_pct']:null,'combo_regular_pct'=>isset($pl['combo_regular_pct'])?(float)$pl['combo_regular_pct']:null,'terms'=>array_values(array_map(function($t){return (int)$t['duration_years'];}, $pl['terms'] ?? []))]; }, $staking_plans)) ?>;

  /* ------------------------------------------------------------------
     COMBO SPLITS THE PRINCIPAL. Each rate applies to its OWN half, never
     to the whole stake. Showing both against the full principal quoted
     exactly 2x: a 100,000 / 5y at 400% + 3%/mo read 580,000 ROI when the
     real figure is 290,000.

     Split comes from staking_plans.combo_fixed_pct / combo_regular_pct so
     the admin's Combo split fields actually drive the maths. Falls back to
     50/50 if the pair is unusable — must match RoiStakingManagement_model.
     ------------------------------------------------------------------ */
  function comboSplit(){
    const p = PLANS.find(x => x.code === 'combo') || {};
    const f = +p.combo_fixed_pct || 0, r = +p.combo_regular_pct || 0;
    if (f <= 0 || r <= 0 || Math.abs((f + r) - 100) > 0.001) return { fixed: 50, regular: 50 };
    return { fixed: f, regular: r };
  }
  const RAW_DISTS = <?= json_encode(array_reduce($coin_distribution_options ?? [], function($carry, $opt){
    $carry[(int)$opt['id']] = [
      'name' => $opt['option_name'],
      'exchange' => (float)$opt['exchange_percentage'],
      'earning' => (float)$opt['earning_percentage'],
      'staking' => (float)$opt['staking_percentage'],
      'bonus' => (float)$opt['bonus_percentage'],
      'is_default' => (int)($opt['is_default'] ?? 0),
    ];
    return $carry;
  }, [])) ?> || {};
  const FALLBACK_DISTS = {
    1:{name:'Option 1',exchange:100,earning:0,staking:0,bonus:0,is_default:1},
    2:{name:'Option 2',exchange:90,earning:0,staking:0,bonus:10,is_default:0},
    3:{name:'Option 3',exchange:80,earning:10,staking:0,bonus:10,is_default:0},
    7:{name:'Option 7',exchange:70,earning:10,staking:10,bonus:10,is_default:0}
  };
  // Every active option the admin has configured is selectable here — this
  // flow used to hide anything without exactly 10% Bonus Wallet, which
  // silently dropped Options 1/4/5/6 even though nothing server-side
  // restricts them (Lendingcontroller::swap_purchase() only range-checks
  // 1-7 and reads each option's real percentages, never assumes 10%).
  const DISTS = Object.keys(RAW_DISTS).length ? RAW_DISTS : FALLBACK_DISTS;
  function distDefaultKey(){
    const entries = Object.entries(DISTS);
    const def = entries.find(([, v]) => v.is_default);
    return Number((def || entries[0] || [7])[0]);
  }
  // Plan descriptions only (which plans are actually offered — and what
  // durations each one offers — comes from PLANS, server-filtered to
  // is_active=1 so a plan the admin deactivates on Staking Plans stops
  // appearing here immediately, with no separate hardcoded list to miss).
  const PLAN_DESCRIPTIONS = {
    fixed: 'ROI accrues as one total percentage and is credited at the end of the term (maturity). All ROI paid at once.',
    regular: 'ROI is credited every month on days 5, 15, and 25. You receive a steady monthly percentage across the whole term.',
    combo: 'A blend of Fixed and Regular: part of your ROI pays monthly while the rest is settled at maturity.'
  };
  let cur = {pkg:null, plan:null, years:null, roi_plan:null, dist:distDefaultKey(), usdt:0, bal:0, step:1, quote:null};
  const $ = id => document.getElementById(id);
  const SWAP_ON = <?= !empty($swap_enabled) ? 'true' : 'false' ?>;
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function stkMessage(type, title, text){
    const box = $('stkm-feedback');
    if(!box) return;
    const tone = ['success', 'error', 'info'].includes(type) ? type : 'info';
    const icon = tone === 'success' ? 'OK' : (tone === 'error' ? '!' : 'i');
    box.className = 'stkm-feedback show ' + tone;
    box.innerHTML =
      '<div class="stkm-feedback-ico">' + icon + '</div>' +
      '<div><strong>' + esc(title || '') + '</strong><span>' + esc(text || '') + '</span></div>';
    requestAnimationFrame(function(){
      box.scrollIntoView({block:'nearest', behavior:'smooth'});
    });
  }
  function clearStkMessage(){
    const box = $('stkm-feedback');
    if(!box) return;
    box.className = 'stkm-feedback';
    box.innerHTML = '';
  }
  function stkPickROIPlan(code){
    cur.roi_plan = code;
    document.querySelectorAll('#stkm-roi-plans button').forEach(b=>b.classList.toggle('active', b.dataset.code===code));
    // Render term buttons for the durations THIS plan actually offers
    // (staking_plan_terms, via PLANS — admin's "Durations offered" checkboxes).
    const plan = PLANS.find(p=>p.code===code);
    if(plan) {
      $('stkm-terms').innerHTML='';
      const terms = (plan.terms && plan.terms.length) ? plan.terms : [2, 3, 5];
      terms.forEach(y=>{
        const b=document.createElement('button');
        b.type='button';
        b.textContent=y+'Y';
        b.dataset.y=y;
        b.onclick=()=>stkPickTerm(y);
        $('stkm-terms').appendChild(b);
      });
      stkPickTerm(terms[0]);  // Select first offered term by default
    }
  }
  function renderStep(step){
    if(step) cur.step = step;
    clearStkMessage();
    document.querySelectorAll('.stkm-step').forEach((el,i)=>{el.classList.toggle('active', i+1===cur.step); el.classList.toggle('done', i+1<cur.step);});
    document.querySelectorAll('.stkm-pane').forEach(p=>p.classList.toggle('active', +p.dataset.step===cur.step));
    $('stkm-back').style.display = cur.step===1 ? 'none' : 'block';
    $('stkm-next').style.display = cur.step===4 ? 'none' : 'block';
    $('stkm-go').style.display = cur.step===4 ? 'block' : 'none';
  }
  function renderRoi(){
    const roiMap=cur.pkg?.roi||{};
    if(!cur.pkg){ $('stkm-roi').textContent='?'; return; }
    const principal = +cur.pkg.stake||0;
    const years = +cur.years||1;

    if(cur.roi_plan==='combo'){
      const f=roiMap['fixed_'+years], r=roiMap['regular_'+years];
      const sp = comboSplit();
      // Each rate earns off its OWN half of the principal.
      const fixedHalf   = principal * (sp.fixed / 100);
      const regularHalf = principal * (sp.regular / 100);
      const fixedROI    = f ? fixedHalf * (f.pct / 100) : 0;
      const regularROI  = r ? regularHalf * (r.pct / 100) * 12 * years : 0;
      const totalROI    = fixedROI + regularROI;
      $('stkm-roi').textContent =
        (f ? sp.fixed+'% @ '+f.pct+'% fixed' : '?') + ' + ' +
        (r ? sp.regular+'% @ '+r.pct+'% /mo' : '?') + ' = ' +
        Number(totalROI).toLocaleString() + ' BMAN';
    } else {
      const c=roiMap[cur.roi_plan+'_'+years];
      if(c){
        let totalROI = 0;
        if(c.basis === 'monthly'){
          // Regular: monthly rate (2.3%) paid 3x per month, for 12 months per year
          totalROI = principal * (c.pct / 100) * 12 * years;
        } else {
          // Fixed: total for the term
          totalROI = principal * (c.pct / 100);
        }
        const displayRate = c.basis === 'monthly' ? c.pct+'% /mo' : c.pct+'% total';
        $('stkm-roi').textContent = Number(totalROI).toLocaleString()+' BMAN ('+displayRate+')';
      } else {
        $('stkm-roi').textContent='?';
      }
    }
  }
  function calcDist(amount){ const m=DISTS[cur.dist]||DISTS[7]; const exchange=amount*m.exchange/100, earning=amount*m.earning/100, staking=amount*m.staking/100, bonus=amount*m.bonus/100, instant=amount*0.25; return {m,exchange,earning,staking,bonus,instant,totalBonus:bonus+instant}; }
  function renderROIDetails(){
    if(!cur.pkg || !cur.roi_plan || !cur.years) return;
    const principal = +cur.pkg.stake||0;
    const roi = cur.pkg.roi || {};
    const roiData = roi[cur.roi_plan+'_'+cur.years] || {pct:0, basis:'total'};
    const ratePercent = +roiData.pct||0;
    const basis = roiData.basis || 'total';
    const years = +cur.years||1;

    // Calculate total ROI based on plan type
    // Fixed plan: rate is TOTAL % over entire term (e.g., 150% for 2 years)
    // Regular plan: rate is % PER MONTH (e.g., 2.3% monthly)
    // Combo plan: the principal is SPLIT — each rate earns off its own half.
    let totalROI, annualROI;
    // How much principal comes back at maturity. Combo returns only the REGULAR
    // half: the fixed half's payout is gross (principal x fixed% = 4x your money,
    // principal included), so returning it again would pay that half twice.
    let principalBack = principal;

    if(cur.roi_plan === 'combo'){
      const fixedData = roi['fixed_'+years] || {pct:0};
      const regularData = roi['regular_'+years] || {pct:0};
      const fixedRate = +fixedData.pct || 0;
      const regularRate = +regularData.pct || 0;

      const sp = comboSplit();
      const fixedHalf   = principal * (sp.fixed / 100);
      const regularHalf = principal * (sp.regular / 100);

      // Fixed half: total % over the term, GROSS (principal included).
      const fixedROI = fixedHalf * (fixedRate / 100);
      // Regular half: monthly rate x 12 x years.
      const regularROI = regularHalf * (regularRate / 100) * 12 * years;

      totalROI = fixedROI + regularROI;
      annualROI = totalROI / years;
      principalBack = regularHalf;
    } else if(basis === 'monthly'){
      // Regular plan: monthly rate (2.3%) paid 3x per month (5th, 15th, 25th)
      // 2.3% is the total monthly rate, divided into 3 equal payments
      totalROI = principal * (ratePercent / 100) * 12 * years;
      annualROI = totalROI / years;
    } else {
      // Fixed plan: ratePercent is already the total for the entire term
      totalROI = principal * (ratePercent / 100);
      annualROI = totalROI / years;
    }
    // Update preview tab elements
    $('stkm-roi-principal').textContent = Number(principal).toLocaleString();
    $('stkm-roi-return').textContent = Number(totalROI).toLocaleString();
    $('stkm-roi-rate').textContent = ratePercent + '%';
    $('stkm-roi-duration').textContent = years + ' Year' + (years>1?'s':'');
    $('stkm-roi-bonus').textContent = Number(principal*0.25).toLocaleString();

  }
  function renderLive(){
    if(!cur.pkg) return;
    const amount=+cur.pkg.stake||0;
    const dist=calcDist(amount);
    if($('stkm-bw-exchange')) $('stkm-bw-exchange').textContent=Number(dist.exchange).toLocaleString()+' BMAN';
    if($('stkm-bw-staking')) $('stkm-bw-staking').textContent=Number(dist.staking).toLocaleString()+' BMAN';
    if($('stkm-bw-bonus')) $('stkm-bw-bonus').textContent=Number(dist.bonus).toLocaleString()+' BMAN';
    if($('stkm-bw-earning')) $('stkm-bw-earning').textContent=Number(dist.earning).toLocaleString()+' BMAN';
    if($('stkm-bonus')) $('stkm-bonus').textContent=Number(dist.bonus).toLocaleString()+' BMAN';
    if($('stkm-bonus-pct')) $('stkm-bonus-pct').textContent=fmtPct(dist.m.bonus);
    if($('stkm-instant')) $('stkm-instant').textContent=Number(dist.instant).toLocaleString()+' BMAN';
    renderROIDetails();
  }
  function fmtPct(v){ v = Math.round((Number(v)||0) * 100) / 100; return String(v); }
  function getDistDescription(dist){
    const parts = [];
    if((Number(dist.exchange) || 0) > 0) parts.push(fmtPct(dist.exchange) + '% Exchange');
    if((Number(dist.earning) || 0) > 0) parts.push(fmtPct(dist.earning) + '% Earning');
    if((Number(dist.staking) || 0) > 0) parts.push(fmtPct(dist.staking) + '% Staking');
    if((Number(dist.bonus) || 0) > 0) parts.push(fmtPct(dist.bonus) + '% Bonus');
    return parts.length > 0 ? parts.join(' + ') : 'No wallet allocation';
  }
  function renderDistDescription(){
    const desc=$('stkm-distribution-desc');
    if(!desc) return;
    const selected = DISTS[cur.dist];
    if(selected) {
      desc.innerHTML = getDistDescription(selected) +
        '<div style="font-size:11px;font-weight:800;color:#64748b;margin-top:6px;">Remaining principal stays in Exchange Wallet. Instant 25% package bonus is shown separately.</div>';
    }
  }
  function renderDistButtons(){ $('stkm-distributions').innerHTML=''; Object.entries(DISTS).forEach(([k,v])=>{ const b=document.createElement('button'); b.type='button'; b.textContent=v.name; b.dataset.dist=k; b.onclick=()=>{ cur.dist=+k; document.querySelectorAll('#stkm-distributions button').forEach(x=>x.classList.toggle('active', +x.dataset.dist===+k)); renderDistDescription(); renderLive(); renderStep(3); }; $('stkm-distributions').appendChild(b); }); document.querySelectorAll('#stkm-distributions button').forEach(b=>b.classList.toggle('active', +b.dataset.dist===cur.dist)); renderDistDescription(); }
  function stkPickTerm(y){
    cur.years=y;
    document.querySelectorAll('#stkm-terms button').forEach(b=>b.classList.toggle('active', +b.dataset.y===+y));
    renderRoi();
    quote();
    renderLive();
    renderROIDetails();
  }
  function selectPackage(pkgId){
    cur.pkg=PKGS.find(p=>p.id===pkgId)||cur.pkg;
    document.querySelectorAll('#stkm-packages button').forEach(b=>b.classList.toggle('active', +b.dataset.id===+pkgId));
    renderLive();
    renderStep(2);
  }
  window.stkOpen = function(pkgId){
    cur.step=1;
    cur.plan=null;
    cur.years=null;
    cur.roi_plan=null;
    cur.dist=distDefaultKey();
    cur.quote=null;
    cur.pkg=PKGS.find(p=>p.id===pkgId);
    if(!cur.pkg) return;
    $('stkm-name').textContent=cur.pkg.name+' Package';
    $('stkm-amt').textContent=cur.pkg.stake.toLocaleString()+' BMAN ? '+cur.pkg.bonus_pct+'% bonus';
    $('stkm-packages').innerHTML='';
    PKGS.forEach((p)=>{
      const b=document.createElement('button');
      b.type='button';
      b.textContent=p.stake.toLocaleString()+' BMAN';
      b.dataset.id=p.id;
      b.onclick=()=>selectPackage(p.id);
      $('stkm-packages').appendChild(b);
    });
    // ROI Plans (Step 2) — only plans the admin has active (Master → Staking
    // Plans); a deactivated plan (e.g. Combo) simply isn't in PLANS.
    $('stkm-roi-plans').innerHTML='';
    PLANS.forEach((pl)=>{
      const b=document.createElement('button');
      b.type='button';
      b.textContent=pl.name;
      b.dataset.code=pl.code;
      b.title=PLAN_DESCRIPTIONS[pl.code] || '';
      b.onclick=()=>stkPickROIPlan(pl.code);
      $('stkm-roi-plans').appendChild(b);
    });
    // Default to Fixed Plan when it's active; otherwise the first active plan.
    if (PLANS.length) {
      const def = PLANS.find(p => p.code === 'fixed') || PLANS[0];
      stkPickROIPlan(def.code);
    }
    // Terms are rendered dynamically when ROI plan is selected, so no need to populate here
    renderDistButtons();
    selectPackage(pkgId);
    $('stkm').classList.add('open');
    renderStep(1);
  };
  window.stkClose = ()=> $('stkm').classList.remove('open');
  function quote(){ const fd=new FormData(); fd.append('package_id',cur.pkg.id); fetch(BASE+'user/lending/stake_quote',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{ if(!j.status){ $('stkm-cost').textContent=j.message||'?'; return; } cur.usdt=j.usdt; cur.bal=j.usdt_balance; cur.quote=j; $('stkm-cost').textContent = Number(j.usdt).toLocaleString(undefined,{maximumFractionDigits:4})+' USDT'; $('stkm-lock').textContent = Number(j.bman).toLocaleString()+' BMAN'; $('stkm-bal').textContent  = Number(j.usdt_balance).toLocaleString(undefined,{maximumFractionDigits:2})+' USDT'; const bw = j.bman_wallets||{}; ['exchange','staking','bonus','earning'].forEach(function(w){ const el=$('stkm-bw-'+w); if(el) el.textContent=Number(bw[w]||0).toLocaleString()+' BMAN'; }); const short = j.usdt_balance + 1e-8 < j.usdt; $('stkm-warn').style.display = short?'block':'none'; $('stkm-go').disabled = short; renderLive(); }).catch(()=>{ $('stkm-cost').textContent='Quote failed'; }); }
  $('stkm-back').onclick = function(){ if(cur.step>1) renderStep(cur.step-1); };
  $('stkm-next').onclick = function(){ if(cur.step<4) renderStep(cur.step+1); };
  window.stkConfirm = function(){
    const go=$('stkm-go');
    go.disabled=true;
    clearStkMessage();
    go.innerHTML='<span class="stkm-spinner"></span>Processing…';
    const fd=new FormData();

    // Append ALL required fields for backend
    fd.append('package_id', cur.pkg.id);
    fd.append('plan_code', cur.roi_plan || 'fixed');  // plan_code == selected ROI plan (fixed/regular/combo)
    fd.append('duration_years', cur.years);
    fd.append('plan_type', cur.roi_plan);  // ✅ ROI Plan Type (fixed|regular|combo)
    fd.append('coin_distribution_option_id', cur.dist);  // ✅ Distribution option (1-7)
    fd.append('plan_id', 0);  // ✅ Plan ID

    const endpoint = SWAP_ON ? 'user/lending/swap_purchase' : 'user/lending/purchase_stake';
    fetch(BASE+endpoint,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{
      if(j.status){
        // Timer + confirm button both resolve .then() — whichever comes first,
        // the page reloads with no extra click required (refreshes portfolio,
        // wallet balances, active staking table and package availability, all
        // server-rendered on load).
        stkMessage('success', 'Staking Successful', j.message || 'Your staking request has been submitted successfully.');
        setTimeout(function(){ location.reload(); }, 1500);
      } else {
        go.disabled=false;
        go.textContent='Confirm';
        stkMessage('error', 'Staking Failed', j.message || 'Something went wrong. Please try again.');
      }
    }).catch(function(){
      go.disabled=false;
      go.textContent='Confirm';
      stkMessage('error', 'Request Failed', 'Could not reach the server. Please try again.');
    });
  };
})();
</script>
<?php endif; /* staking_packages */ ?>
