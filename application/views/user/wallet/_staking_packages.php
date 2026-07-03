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

if (!empty($staking_packages)):

// which durations actually appear across the ROI matrix (fallback 2/3/5)
$durations = [];
foreach ($staking_packages as $p) {
    foreach (array_keys($p['roi'] ?? []) as $k) {
        $yr = (int) substr(strrchr($k, '_'), 1);
        if ($yr > 0) $durations[$yr] = true;
    }
}
$durations = array_keys($durations);
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
  .stk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;}
  .stk-card{position:relative;background:var(--card,#fff);border:1px solid rgba(15,23,42,.08);border-radius:18px;
    padding:18px;box-shadow:0 8px 24px rgba(15,23,42,.05);transition:transform .15s,box-shadow .15s;overflow:hidden;}
  .stk-card:hover{transform:translateY(-3px);box-shadow:0 14px 32px rgba(67,56,202,.14);border-color:rgba(99,102,241,.35);}
  .stk-card::before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,#6366f1,#22c55e);}
  .stk-card .amt{font-size:24px;font-weight:1200;color:var(--text,#0b1220);line-height:1;}
  .stk-card .amt small{font-size:12px;font-weight:900;color:var(--muted,#6b7280);}
  .stk-card .nm{font-size:12.5px;font-weight:900;color:var(--muted,#6b7280);margin-top:2px;}
  .stk-badges{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 14px;}
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
  .stk-foot{margin-top:12px;font-size:11px;line-height:1.5;color:var(--muted,#6b7280);font-weight:700;
    display:flex;gap:12px;flex-wrap:wrap;}
  .stk-foot b{color:#0b1220;}
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
    ?>
    <div class="stk-card">
      <div class="amt"><?= number_format((float)$p['stake_amount']) ?> <small>BMAN</small></div>
      <div class="nm"><?= htmlspecialchars($p['name']) ?> Package</div>

      <div class="stk-badges">
        <span class="stk-b bonus"><i class="ph-fill ph-gift"></i> <?= rtrim(rtrim(number_format((float)$p['bonus_percent'], 2), '0'), '.') ?>% Bonus</span>
        <span class="stk-b ceil"><i class="ph ph-shield-check"></i> Ceiling: <?= number_format((float)$p['group_ceiling']) ?> BMAN</span>
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
            $fx = $roi['fixed_'.$yr]['roi_percent']   ?? null;
            $rg = $roi['regular_'.$yr]['roi_percent'] ?? null;
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
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; /* staking_packages */ ?>
