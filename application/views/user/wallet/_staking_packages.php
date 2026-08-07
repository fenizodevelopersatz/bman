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
  /* Full-width rule closing the Special group. grid-column:1/-1 makes it span
     every column whatever auto-fill resolved to, so it stays a clean break at
     any width instead of sitting in one cell. */
  .stk-sep{grid-column:1/-1;display:flex;align-items:center;gap:12px;margin:8px 0 0;}
  .stk-sep::before,.stk-sep::after{content:"";flex:1;height:1px;background:rgba(15,23,42,.08);}
  .stk-sep span{font-size:11px;font-weight:1000;text-transform:uppercase;letter-spacing:.5px;
    color:var(--muted,#6b7280);white-space:nowrap;}
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
    <?php $prevSpecial = null; ?>
    <?php foreach ($staking_packages as $p):
      $roi = $p['roi'] ?? [];
      $owned = in_array((int)$p['id'], $owned_stake_ids, true);
      // Badge only — a special package offers the same Fixed/Regular terms as
      // any other, so the card body below is identical for both.
      $isSpecialPkg = !empty($p['is_special']);
    ?>
    <?php /* The controller hands the list over special-first, so the one place
             the flag flips is the boundary between the two groups. Drawing the
             rule off that transition means it appears only when both groups are
             actually present — no stray bar when there are no special packages,
             and none trailing the list when every package is special. */ ?>
    <?php if ($prevSpecial === true && !$isSpecialPkg): ?>
    <div class="stk-sep"><span>Standard Packages</span></div>
    <?php endif; ?>
    <?php $prevSpecial = $isSpecialPkg; ?>
    <div class="stk-card<?= $owned ? ' owned' : '' ?><?= $isSpecialPkg ? ' special' : '' ?>">
      <?php if ($owned): ?><span class="owned-rib">OWNED</span><?php endif; ?>
      <div class="amt"><?= number_format((float)$p['stake_amount']) ?> <small>BMAN</small></div>
      <div class="stk-badges">
        <span class="stk-b bonus"><i class="ph-fill ph-gift"></i> <?= rtrim(rtrim(number_format((float)$p['bonus_percent'], 2), '0'), '.') ?>% Bonus</span>
        <?php if ($isSpecialPkg): ?><span class="stk-b special-chip"><i class="ph-fill ph-star"></i> Special Offer</span><?php endif; ?>
      </div>

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

      <button type="button" class="stk-buy" onclick="stkOpen(<?= (int)$p['id'] ?>)">
        <i class="ph ph-lock-key"></i> SELECT
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===================== SPECIAL OFFER badge styling ===================== -->
<?php /* A special package is highlighted, not priced differently. It uses the
         same ROI table, the same SELECT modal and the same purchase endpoint as
         every other package — only the gold border and chip below set it
         apart. The dedicated escalating-ROI card, modal and JS that used to
         live here were removed with the Special ROI engine. */ ?>
<style>
  .stk-card.special{ border:1.5px solid #f5c451 !important; box-shadow:0 12px 32px rgba(245,158,11,.20) !important;
    background:linear-gradient(180deg,#fffdf6 0%,#ffffff 46%) !important; }
  .stk-b.special-chip{ background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:900;
    box-shadow:0 4px 12px rgba(245,158,11,.35); }
</style>
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
        <div class="stkm-steps" id="stkm-steps"><div class="stkm-step active">Package</div><div class="stkm-step">Plan</div><div class="stkm-step">Allocation</div><div class="stkm-step">Preview</div></div>
        <div class="stkm-pane active" data-step="1"><label>Select Package</label><div class="stkm-packages" id="stkm-packages"></div><div class="stkm-note">Choose a package to continue to plan selection.</div></div>
        <div class="stkm-pane" data-step="2"><label>ROI Plan Type</label><div class="stkm-seg" id="stkm-roi-plans"></div><div class="stkm-note">Choose how you want to receive your ROI returns</div><label>Term</label><div class="stkm-seg" id="stkm-terms"></div></div>
      <div class="stkm-pane" data-step="3">
        <label>Coin Allocation Options</label>
        <div class="stkm-seg" id="stkm-distributions"></div>
        <div style="background:#f8fafc;border:1px solid rgba(15,23,42,.08);border-radius:14px;padding:14px 16px;margin:12px 0;margin-top:16px;">
          <div style="font-size:13px;font-weight:800;color:#334155;line-height:1.6;" id="stkm-distribution-desc">Select an option above</div>
        </div>
        <div class="stkm-warn" id="stkm-dist-warn"></div>
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
        <?php /* The USDT rows below only apply to Option 1 (the real USDT->BMAN
                 on-chain purchase). Options 2-7 re-stake BMAN the user already
                 holds, so they are hidden by renderFunding() and replaced with
                 the per-wallet debit breakdown. */ ?>
        <div class="stkm-quote">
          <div class="stkm-row roi"><span>ROI (this plan/term)</span><b id="stkm-roi">?</b></div>
          <div class="stkm-row" data-usdt-only><span>Cost</span><b id="stkm-cost">? USDT</b></div>
          <div class="stkm-row"><span><?= $isSwap ? 'BMAN ? Exchange Wallet' : 'Locked into Staking Wallet' ?></span><b id="stkm-lock">? BMAN</b></div>
          <div class="stkm-row"><span>Allocation Bonus Wallet (<span id="stkm-bonus-pct">0</span>%)</span><b id="stkm-bonus">? BMAN</b></div>
          <div class="stkm-row"><span>Instant Bonus (25%)</span><b id="stkm-instant">? BMAN</b></div>
          <div class="stkm-row" data-usdt-only><span>Your USDT Balance</span><b id="stkm-bal">? USDT</b></div>
          <div class="stkm-warn" id="stkm-warn">Insufficient USDT balance ? deposit USDT first.</div>
          <div id="stkm-funding" style="display:none;border-top:1px dashed rgba(15,23,42,.12);margin-top:8px;padding-top:8px;">
            <div style="font-size:10.5px;font-weight:1000;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:4px;">Paid from your wallets (no USDT needed)</div>
            <div id="stkm-funding-rows"></div>
            <div class="stkm-warn" id="stkm-fund-warn"></div>
          </div>
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
  // The server already returns options in display order (Coindistribution_
  // model::activeOptions() — default first, then sort_order). That order is
  // NOT preserved by RAW_DISTS itself: every key in it is a numeric-looking
  // string ("1","24","2",...), and JS objects always enumerate integer-index
  // keys in ascending NUMERIC order regardless of insertion order — so
  // Object.entries(DISTS)/Object.keys(DISTS) silently reshuffled a freshly
  // added option (e.g. id 24, sort_order 20 — meant to render 2nd) to the
  // END of the button list. DIST_ORDER carries the real order separately;
  // anything that renders/iterates options must walk DIST_ORDER, not DISTS
  // directly — DISTS itself stays just an id->config lookup map.
  const DIST_ORDER = <?= json_encode(array_values(array_map(function($opt){ return (int)$opt['id']; }, $coin_distribution_options ?? []))) ?>;
  const FALLBACK_DISTS = {
    1:{name:'Option 1',exchange:100,earning:0,staking:0,bonus:0,is_default:1},
    2:{name:'Option 2',exchange:90,earning:0,staking:0,bonus:10,is_default:0},
    3:{name:'Option 3',exchange:80,earning:10,staking:0,bonus:10,is_default:0},
    7:{name:'Option 7',exchange:70,earning:10,staking:10,bonus:10,is_default:0}
  };
  const FALLBACK_ORDER = [1,2,3,7];
  // Every active option the admin has configured is selectable here — this
  // flow used to hide anything without exactly 10% Bonus Wallet, which
  // silently dropped Options 1/4/5/6 even though nothing server-side
  // restricts them (Lendingcontroller::swap_purchase() only range-checks
  // 1-7 and reads each option's real percentages, never assumes 10%).
  const DISTS = Object.keys(RAW_DISTS).length ? RAW_DISTS : FALLBACK_DISTS;
  const ORDER = DIST_ORDER.length ? DIST_ORDER : FALLBACK_ORDER;
  function distDefaultKey(){
    const def = ORDER.find(id => DISTS[id] && DISTS[id].is_default);
    return def !== undefined ? Number(def) : Number(ORDER[0] ?? 7);
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
  // Richer confirmation for a successful re-stake (Options 2-7) — reuses the
  // same .stkm-feedback box/classes as stkMessage() but renders the full
  // breakdown the spec calls for: Package, Plan, Distribution, per-wallet
  // deductions, Bonus, new Lock Wallet total, Maturity date.
  function stkRestakeSummary(d){
    const box = $('stkm-feedback');
    if(!box) return;
    const rows = (d.wallet_deductions||[]).map(function(w){
      return '<tr><td>'+esc(w.label)+'</td><td style="text-align:right;white-space:nowrap;">-'+
        Number(w.amount).toLocaleString(undefined,{maximumFractionDigits:4})+' BMAN</td></tr>';
    }).join('');
    box.className = 'stkm-feedback show success';
    box.innerHTML =
      '<div class="stkm-feedback-ico">OK</div>' +
      '<div style="width:100%;">' +
        '<strong>Re-stake Successful</strong>' +
        '<div style="margin-top:6px;font-size:12px;line-height:1.7;font-weight:700;">' +
          '<div><b>Package:</b> '+esc(d.package_name)+'</div>' +
          '<div><b>Plan:</b> '+esc(d.plan_label)+' &middot; '+esc(String(d.duration_years))+' Years</div>' +
          '<div><b>Allocation:</b> '+esc(d.distribution_option_name)+'</div>' +
          (rows ? '<table style="width:100%;margin-top:6px;border-collapse:collapse;">'+rows+'</table>' : '') +
          (Number(d.bonus)>0 ? '<div style="margin-top:6px;"><b>Bonus credited:</b> '+Number(d.bonus).toLocaleString(undefined,{maximumFractionDigits:4})+' BMAN</div>' : '') +
          '<div style="margin-top:6px;"><b>Lock Wallet total now:</b> '+Number(d.lock_wallet_balance).toLocaleString(undefined,{maximumFractionDigits:4})+' BMAN</div>' +
          '<div><b>Maturity date:</b> '+esc(d.maturity_date)+'</div>' +
        '</div>' +
      '</div>';
    requestAnimationFrame(function(){ box.scrollIntoView({block:'nearest', behavior:'smooth'}); });
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
    // Landing on the confirmation step re-quotes, so the balances Confirm is
    // judged against are the ones live RIGHT NOW — not whatever they were when
    // the package was picked several steps ago. quote() ends in syncGoButton(),
    // which re-runs the affordability gate and re-renders the funding rows.
    if(cur.step===4 && cur.pkg) quote();
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
  // Live pre-check mirroring the server-side gate in Lendingcontroller::swap_purchase():
  // options other than 1 (100% Exchange) also draw on EXISTING Earning/Staking/Bonus
  // balance (this purchase itself only ever funds Exchange), so warn — and block
  // Confirm — before the user even submits if that balance isn't there. Uses the
  // same withdrawable figures already fetched into cur.quote.bman_wallets by quote(),
  // so this never disagrees with what the server checks. Returns true/false only —
  // does not touch stkm-go itself, since that button also depends on the independent
  // USDT-balance check quote() runs; syncGoButton() combines both.
  // True when the selected option re-stakes existing BMAN (Options 2-7) rather
  // than buying new BMAN with USDT (Option 1). Drives BOTH which endpoint
  // stkConfirm() posts to and which balance actually gates Confirm.
  function isRestakeMode(){ return +cur.dist !== 1; }
  // The per-wallet requirement for the selected option, e.g. Option 2 (90/10)
  // on a 1 BMAN package => [{wallet:'exchange',required:0.9,...},
  // {wallet:'bonus',required:0.1,...}]. Same percentages the server reads out
  // of coin_distribution_options in Staking_model::restakeFromWallets().
  const WALLET_LABELS={exchange:'Exchange Wallet', earning:'Earning Wallet', staking:'Staking Wallet', bonus:'Bonus Wallet'};
  function fundingPlan(){
    const selected=DISTS[cur.dist];
    if(!selected || !cur.pkg) return [];
    const amount=+cur.pkg.stake||0;
    // Balance the SERVER can actually debit (raw wallet balance minus pending
    // withdrawal holds). Not `bman_wallets`, which is the maturity-gated
    // withdrawable figure and only governs taking BMAN off-platform.
    const bw=(cur.quote && (cur.quote.bman_spendable || cur.quote.bman_wallets)) || null;
    return ['exchange','earning','staking','bonus'].filter(function(w){ return (+selected[w]||0) > 0; })
      .map(function(w){
        const pct=+selected[w]||0;
        return {
          wallet: w,
          label: WALLET_LABELS[w],
          pct: pct,
          required: amount*pct/100,
          available: bw ? (+bw[w]||0) : null,
        };
      });
  }
  function checkDistBalance(){
    const warnEls=[$('stkm-dist-warn'), $('stkm-fund-warn')].filter(Boolean);
    if(!warnEls.length) return true;
    const hide=function(){ warnEls.forEach(function(el){ el.style.display='none'; el.textContent=''; }); };
    // Option 1 (100% Exchange) is a new USDT->BMAN purchase — it funds
    // Exchange itself, so it needs no pre-existing wallet balance at all.
    // Options 2-7 re-stake EXISTING balance instead, so every wallet they
    // draw on (Exchange included) must already hold enough.
    if(!isRestakeMode()){ hide(); return true; }
    const plan=fundingPlan();
    // No quote yet => no balances to judge against; don't block on unknowns
    // (the server re-checks every debit anyway and is the real authority).
    if(!plan.length || plan[0].available === null){ hide(); return true; }
    const shortfalls=plan.filter(function(p){ return p.available + 1e-8 < p.required; })
      .map(function(p){ return p.label+' needs '+p.required.toLocaleString(undefined,{maximumFractionDigits:4})+' BMAN (has '+p.available.toLocaleString(undefined,{maximumFractionDigits:4})+')'; });
    if(shortfalls.length){
      warnEls.forEach(function(el){
        el.textContent='Insufficient balance for this option — '+shortfalls.join('; ')+'.';
        el.style.display='block';
      });
      return false;
    }
    hide();
    return true;
  }
  // Renders the Preview step's funding section and hides the USDT-only rows
  // when the selected option is wallet-funded.
  function renderFunding(){
    const restake=isRestakeMode();
    document.querySelectorAll('#stkm [data-usdt-only]').forEach(function(el){ el.style.display = restake ? 'none' : ''; });
    const box=$('stkm-funding'), rows=$('stkm-funding-rows');
    if(!box || !rows) return;
    if(!restake){ box.style.display='none'; rows.innerHTML=''; return; }
    box.style.display='block';
    rows.innerHTML = fundingPlan().map(function(p){
      const have = p.available === null ? '' :
        '<span style="font-weight:800;color:#64748b;"> (balance '+p.available.toLocaleString(undefined,{maximumFractionDigits:4})+')</span>';
      return '<div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>'+esc(p.label)+' ('+fmtPct(p.pct)+'%)'+have+'</span>'+
             '<b>-'+p.required.toLocaleString(undefined,{maximumFractionDigits:4})+' BMAN</b></div>';
    }).join('');
  }
  // Combines the USDT-balance check (quote() sets cur.usdtShort) with the
  // distribution-wallet check above — Confirm stays disabled if either fails.
  function syncGoButton(){
    const goBtn=$('stkm-go');
    // checkDistBalance() must always run — it has a side effect (updates the
    // warn boxes) — so it can't sit on the right of || where a truthy
    // usdtShort would short-circuit past it and leave those boxes stale.
    const distOk=checkDistBalance();
    renderFunding();
    // The USDT balance is IRRELEVANT to Options 2-7: they pay entirely out of
    // BMAN the user already holds and never touch the chain. Gating them on it
    // is what left Confirm dead for a user with 0 USDT but plenty of Exchange +
    // Bonus BMAN. Only Option 1 spends USDT, so only Option 1 is gated on it.
    const usdtBlocks = !isRestakeMode() && !!cur.usdtShort;
    // The USDT shortfall warning is likewise Option-1-only.
    const warnEl=$('stkm-warn');
    if(warnEl) warnEl.style.display = usdtBlocks ? 'block' : 'none';
    if(goBtn) goBtn.disabled = usdtBlocks || !distOk;
  }
  function renderDistButtons(){ $('stkm-distributions').innerHTML=''; ORDER.filter(k => DISTS[k]).forEach(k => { const v = DISTS[k]; const b=document.createElement('button'); b.type='button'; b.textContent=v.name; b.dataset.dist=k; b.onclick=()=>{ cur.dist=+k; document.querySelectorAll('#stkm-distributions button').forEach(x=>x.classList.toggle('active', +x.dataset.dist===+k)); renderDistDescription(); renderLive(); syncGoButton(); renderStep(3); }; $('stkm-distributions').appendChild(b); }); document.querySelectorAll('#stkm-distributions button').forEach(b=>b.classList.toggle('active', +b.dataset.dist===cur.dist)); renderDistDescription(); }
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
  function quote(){ const fd=new FormData(); fd.append('package_id',cur.pkg.id); fetch(BASE+'user/lending/stake_quote',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{ if(!j.status){ $('stkm-cost').textContent=j.message||'?'; return; } cur.usdt=j.usdt; cur.bal=j.usdt_balance; cur.quote=j; $('stkm-cost').textContent = Number(j.usdt).toLocaleString(undefined,{maximumFractionDigits:4})+' USDT'; $('stkm-lock').textContent = Number(j.bman).toLocaleString()+' BMAN'; $('stkm-bal').textContent  = Number(j.usdt_balance).toLocaleString(undefined,{maximumFractionDigits:2})+' USDT'; const bw = j.bman_wallets||{}; ['exchange','staking','bonus','earning'].forEach(function(w){ const el=$('stkm-bw-'+w); if(el) el.textContent=Number(bw[w]||0).toLocaleString()+' BMAN'; }); cur.usdtShort = j.usdt_balance + 1e-8 < j.usdt; renderLive(); syncGoButton(); }).catch(()=>{ $('stkm-cost').textContent='Quote failed'; }); }
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

    // Option 1 (100% Exchange) is a new USDT->BMAN purchase — on-chain flow.
    // Options 2-7 re-stake EXISTING wallet balances — no USDT/blockchain leg.
    const isRestake = isRestakeMode();
    const endpoint = isRestake ? 'user/lending/restake_purchase' : (SWAP_ON ? 'user/lending/swap_purchase' : 'user/lending/purchase_stake');
    fetch(BASE+endpoint,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{
      if(j.status){
        if(isRestake && j.data){
          stkRestakeSummary(j.data);
        } else {
          stkMessage('success', 'Staking Successful', j.message || 'Your staking request has been submitted successfully.');
        }
        // Timer + confirm button both resolve .then() — whichever comes first,
        // the page reloads with no extra click required (refreshes portfolio,
        // wallet balances, active staking table and package availability, all
        // server-rendered on load).
        setTimeout(function(){ location.reload(); }, isRestake ? 2600 : 1500);
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
