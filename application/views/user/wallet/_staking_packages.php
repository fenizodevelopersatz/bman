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
  .stk-card.owned{border-color:#22c55e;box-shadow:0 10px 28px rgba(34,197,94,.18);}
  .stk-card .owned-rib{position:absolute;top:14px;right:-32px;transform:rotate(45deg);background:#22c55e;color:#fff;
    font-size:10px;font-weight:1000;letter-spacing:.5px;padding:3px 36px;box-shadow:0 4px 10px rgba(34,197,94,.3);z-index:2;}
  .stk-terms{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;}
  .stk-terms .t{display:inline-flex;align-items:center;gap:5px;background:rgba(15,23,42,.05);border-radius:8px;
    padding:4px 9px;font-size:11px;font-weight:900;color:#334155;}
  .stk-terms .t.ok{background:rgba(34,197,94,.12);color:#15803d;}
  .stk-tc{margin-top:10px;text-align:center;}
  .stk-tc a{font-size:11.5px;font-weight:900;color:#4f46e5;cursor:pointer;text-decoration:underline;}
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
    <div class="stk-card<?= $owned ? ' owned' : '' ?>">
      <?php if ($owned): ?><span class="owned-rib">OWNED</span><?php endif; ?>
      <div class="amt"><?= number_format((float)$p['stake_amount']) ?> <small>BMAN</small></div>
      <div class="nm"><?= htmlspecialchars($p['name']) ?> Package</div>

      <div class="stk-badges">
        <span class="stk-b bonus"><i class="ph-fill ph-gift"></i> <?= rtrim(rtrim(number_format((float)$p['bonus_percent'], 2), '0'), '.') ?>% Bonus</span>        
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

      <div class="stk-terms">
        <span class="t"><i class="ph ph-lock-key"></i> <?= implode(' / ', $durations) ?> yr terms</span>
        <span class="t"><i class="ph ph-coins"></i> Min <?= number_format((float)$p['stake_amount']) ?> BMAN</span>
        <span class="t ok"><i class="ph ph-seal-check"></i> Available</span>
      </div>
      <div class="stk-tc"><a onclick="stkTerms()">Terms &amp; Conditions</a></div>

      <button type="button" class="stk-buy" onclick="stkOpen(<?= (int)$p['id'] ?>)">
        <i class="ph ph-lock-key"></i> <?= !empty($swap_enabled) ? 'Buy BMAN' : 'Stake Now' ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===================== STAKING PURCHASE MODAL ===================== -->
<style>
  .stk-buy{width:100%;margin-top:14px;border:0;border-radius:12px;padding:11px;cursor:pointer;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-weight:1000;font-size:13.5px;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;}
  .stk-buy:hover{opacity:.9;}
  .stkm-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);z-index:9999;display:none;align-items:center;justify-content:center;padding:18px;}
  .stkm-overlay.open{display:flex;}
  .stkm{background:#fff;border-radius:20px;max-width:560px;width:100%;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;}
  .stkm-h{padding:18px 20px;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;display:flex;justify-content:space-between;align-items:center;}
  .stkm-h h3{margin:0;font-size:17px;font-weight:1100;}
  .stkm-h .x{cursor:pointer;font-size:22px;line-height:1;opacity:.9;background:none;border:0;color:#fff;}
  .stkm-b{padding:20px;}
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
  .stkm-confirm{width:100%;border:0;border-radius:12px;padding:13px;cursor:pointer;font-weight:1100;font-size:14px;color:#fff;background:linear-gradient(135deg,#10b981,#059669);}
  .stkm-confirm:disabled{opacity:.5;cursor:not-allowed;}
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
    <div class="stkm-h"><h3><i class="ph-fill ph-stack"></i> <?= $isSwap ? 'Buy BMAN (Swap)' : 'Purchase Stake' ?></h3><button class="x" type="button" onclick="stkClose()">&times;</button></div>
    <div class="stkm-b">
      <div style="font-size:20px;font-weight:1200;color:#0b1220;margin-bottom:2px;" id="stkm-name">?</div>
      <div style="font-size:12px;font-weight:900;color:#6b7280;margin-bottom:16px;" id="stkm-amt">?</div>
      <div class="stkm-steps" id="stkm-steps"><div class="stkm-step active">Package</div><div class="stkm-step">Plan</div><div class="stkm-step">Distribution</div><div class="stkm-step">Preview</div><div class="stkm-step">Confirm</div></div>
      <div class="stkm-pane active" data-step="1"><label>Select Package</label><div class="stkm-packages" id="stkm-packages"></div><div class="stkm-note">Choose a package to continue to plan selection.</div></div>
      <div class="stkm-pane" data-step="2"><label>Plan</label><div class="stkm-seg" id="stkm-plans"></div><label>Term</label><div class="stkm-seg" id="stkm-terms"></div></div>
      <div class="stkm-pane" data-step="3"><label>Coin Distribution</label><div class="stkm-seg" id="stkm-distributions"></div><div class="stkm-note">Distribution preview allocates 100% of the purchased BMAN across wallets. Instant bonus is shown separately and remains the only liquid bonus balance.</div></div>
      <div class="stkm-pane" data-step="4"><div class="stkm-quote"><div class="stkm-row roi"><span>ROI (this plan/term)</span><b id="stkm-roi">?</b></div><div class="stkm-row"><span>Cost</span><b id="stkm-cost">? USDT</b></div><div class="stkm-row"><span><?= $isSwap ? 'BMAN ? Exchange Wallet' : 'Locked into Staking Wallet' ?></span><b id="stkm-lock">? BMAN</b></div><div class="stkm-row"><span>Bonus ? Bonus Wallet</span><b id="stkm-bonus">? BMAN</b></div><div class="stkm-row"><span>Instant Bonus (25%)</span><b id="stkm-instant">? BMAN</b></div><div class="stkm-row"><span>Your USDT Balance</span><b id="stkm-bal">? USDT</b></div><div class="stkm-warn" id="stkm-warn">Insufficient USDT balance ? deposit USDT first.</div><div style="border-top:1px dashed rgba(15,23,42,.12);margin-top:8px;padding-top:8px;"><div style="font-size:10.5px;font-weight:1000;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:4px;">Live allocation preview</div><div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Exchange</span><b id="stkm-bw-exchange">?</b></div><div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Staking (locked)</span><b id="stkm-bw-staking">?</b></div><div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Bonus allocation (locked)</span><b id="stkm-bw-bonus">?</b></div><div class="stkm-row" style="font-size:12px;padding:2px 0;"><span>Earning</span><b id="stkm-bw-earning">?</b></div></div></div></div>
      <div class="stkm-pane" data-step="5"><div class="stkm-summary"><h4>Final Purchase Summary</h4><div class="sumgrid"><span>Package</span><b id="stkm-sum-package">?</b><span>Plan</span><b id="stkm-sum-plan">?</b><span>Distribution</span><b id="stkm-sum-dist">?</b><span>Exchange</span><b id="stkm-sum-exchange">?</b><span>Earning</span><b id="stkm-sum-earning">?</b><span>Staking</span><b id="stkm-sum-staking">?</b><span>Bonus Allocation</span><b id="stkm-sum-bonus">?</b><span>Instant Bonus</span><b id="stkm-sum-instant">?</b><span>Total Bonus Balance</span><b id="stkm-sum-total-bonus">?</b></div></div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;"><span class="stkm-balance-pill locked">Locked Balance: Allocation, ROI, Binary, Staking</span><span class="stkm-balance-pill available">Available Balance: Instant Bonus only</span></div><div class="stkm-quote"><div class="stkm-row roi"><span>ROI (this plan/term)</span><b id="stkm-roi2">?</b></div><div class="stkm-row"><span>Cost</span><b id="stkm-cost2">? USDT</b></div><div class="stkm-row"><span>Selected Wallet Allocation</span><b id="stkm-lock2">? BMAN</b></div></div></div>
      <div class="stkm-nav"><button class="stkm-back" id="stkm-back" type="button">Back</button><button class="stkm-next" id="stkm-next" type="button">Next</button></div>
      <button class="stkm-confirm" id="stkm-go" type="button" onclick="stkConfirm()" style="margin-top:10px;display:none;"> <?= $isSwap ? 'Confirm &amp; Swap' : 'Confirm &amp; Stake' ?></button>
    </div>
  </div>
</div>
<script>
(function(){
  const BASE = '<?= base_url() ?>';
  const PKGS = <?= json_encode(array_map(function($p){ return ['id'=>(int)$p['id'],'name'=>$p['name'],'stake'=>(float)$p['stake_amount'],'bonus_pct'=>(float)$p['bonus_percent'],'roi'=>array_map(function($c){return ['pct'=>(float)$c['roi_percent'],'basis'=>$c['roi_basis']];}, $p['roi'] ?? [])]; }, $staking_packages)) ?>;
  const PLANS = <?= json_encode(array_map(function($pl){ return ['code'=>$pl['code'],'name'=>$pl['name'],'terms'=>array_values(array_map(function($t){return (int)$t['duration_years'];}, $pl['terms'] ?? []))]; }, $staking_plans)) ?>;
  const DISTS = {1:{name:'Option 1',exchange:100,earning:0,staking:0,bonus:0},2:{name:'Option 2',exchange:80,earning:10,staking:5,bonus:5},3:{name:'Option 3',exchange:70,earning:15,staking:10,bonus:5},4:{name:'Option 4',exchange:60,earning:20,staking:10,bonus:10},5:{name:'Option 5',exchange:50,earning:20,staking:20,bonus:10},6:{name:'Option 6',exchange:40,earning:30,staking:20,bonus:10},7:{name:'Option 7',exchange:70,earning:10,staking:10,bonus:10}};
  let cur = {pkg:null, plan:null, years:null, dist:7, usdt:0, bal:0, step:1, quote:null};
  const $ = id => document.getElementById(id);
  const SWAP_ON = <?= !empty($swap_enabled) ? 'true' : 'false' ?>;
  function renderStep(step){ if(step) cur.step = step; document.querySelectorAll('.stkm-step').forEach((el,i)=>{el.classList.toggle('active', i+1===cur.step); el.classList.toggle('done', i+1<cur.step);}); document.querySelectorAll('.stkm-pane').forEach(p=>p.classList.toggle('active', +p.dataset.step===cur.step)); $('stkm-back').style.display = cur.step===1 ? 'none' : 'block'; $('stkm-next').style.display = cur.step===5 ? 'none' : 'block'; $('stkm-go').style.display = cur.step===5 ? 'block' : 'none'; }
  function renderRoi(){ const roiMap=cur.pkg?.roi||{}; if(!cur.pkg){ $('stkm-roi').textContent='?'; return; } if(cur.plan==='combo'){ const f=roiMap['fixed_'+cur.years], r=roiMap['regular_'+cur.years]; $('stkm-roi').textContent=(f?f.pct+'% total':'?')+' + '+(r?r.pct+'%/mo':'?')+' (50/50)'; } else { const c=roiMap[cur.plan+'_'+cur.years]; $('stkm-roi').textContent=c?(c.pct+'%'+(c.basis==='monthly'?' /mo':' total')):'?'; } }
  function calcDist(amount){ const m=DISTS[cur.dist]||DISTS[7]; const exchange=amount*m.exchange/100, earning=amount*m.earning/100, staking=amount*m.staking/100, bonus=amount*m.bonus/100, instant=amount*0.25; return {m,exchange,earning,staking,bonus,instant,totalBonus:bonus+instant}; }
  function renderLive(){ if(!cur.pkg) return; const amount=+cur.pkg.stake||0; const dist=calcDist(amount); $('stkm-bw-exchange').textContent=Number(dist.exchange).toLocaleString()+' BMAN'; $('stkm-bw-staking').textContent=Number(dist.staking).toLocaleString()+' BMAN'; $('stkm-bw-bonus').textContent=Number(dist.bonus).toLocaleString()+' BMAN'; $('stkm-bw-earning').textContent=Number(dist.earning).toLocaleString()+' BMAN'; $('stkm-instant').textContent=Number(dist.instant).toLocaleString()+' BMAN'; $('stkm-sum-package').textContent=Number(amount).toLocaleString()+' BMAN'; $('stkm-sum-plan').textContent=(cur.plan ? cur.plan.charAt(0).toUpperCase()+cur.plan.slice(1) : '?')+' - '+(cur.years || '?')+' Years'; $('stkm-sum-dist').textContent=dist.m.name; $('stkm-sum-exchange').textContent=Number(dist.exchange).toLocaleString(); $('stkm-sum-earning').textContent=Number(dist.earning).toLocaleString(); $('stkm-sum-staking').textContent=Number(dist.staking).toLocaleString(); $('stkm-sum-bonus').textContent=Number(dist.bonus).toLocaleString(); $('stkm-sum-instant').textContent=Number(dist.instant).toLocaleString(); $('stkm-sum-total-bonus').textContent=Number(dist.totalBonus).toLocaleString(); $('stkm-cost2').textContent=$('stkm-cost').textContent; $('stkm-lock2').textContent=$('stkm-lock').textContent; $('stkm-roi2').textContent=$('stkm-roi').textContent; }
  function renderDistButtons(){ $('stkm-distributions').innerHTML=''; Object.entries(DISTS).forEach(([k,v])=>{ const b=document.createElement('button'); b.type='button'; b.textContent=v.name; b.dataset.dist=k; b.onclick=()=>{ cur.dist=+k; document.querySelectorAll('#stkm-distributions button').forEach(x=>x.classList.toggle('active', +x.dataset.dist===+k)); renderLive(); renderStep(4); }; $('stkm-distributions').appendChild(b); }); document.querySelectorAll('#stkm-distributions button').forEach(b=>b.classList.toggle('active', +b.dataset.dist===cur.dist)); }
  function stkPickPlan(code){ cur.plan=code; document.querySelectorAll('#stkm-plans button').forEach(b=>b.classList.toggle('active', b.dataset.code===code)); const pl=PLANS.find(p=>p.code===code)||{terms:[2,3,5]}; $('stkm-terms').innerHTML=''; pl.terms.forEach(y=>{ const b=document.createElement('button'); b.type='button'; b.textContent=y+'Y'; b.dataset.y=y; b.onclick=()=>stkPickTerm(y); $('stkm-terms').appendChild(b); }); stkPickTerm(pl.terms[0]); renderStep(2); }
  function stkPickTerm(y){ cur.years=y; document.querySelectorAll('#stkm-terms button').forEach(b=>b.classList.toggle('active', +b.dataset.y===+y)); renderRoi(); quote(); renderLive(); }
  function selectPackage(pkgId){ cur.pkg=PKGS.find(p=>p.id===pkgId)||cur.pkg; document.querySelectorAll('#stkm-packages button').forEach(b=>b.classList.toggle('active', +b.dataset.id===+pkgId)); stkPickPlan((PLANS[0]||{}).code); renderLive(); renderStep(2); }
  window.stkOpen = function(pkgId){ cur.step=1; cur.plan=null; cur.years=null; cur.dist=7; cur.quote=null; cur.pkg=PKGS.find(p=>p.id===pkgId); if(!cur.pkg) return; $('stkm-name').textContent=cur.pkg.name+' Package'; $('stkm-amt').textContent=cur.pkg.stake.toLocaleString()+' BMAN ? '+cur.pkg.bonus_pct+'% bonus'; $('stkm-packages').innerHTML=''; PKGS.forEach((p)=>{ const b=document.createElement('button'); b.type='button'; b.textContent=p.stake.toLocaleString()+' BMAN'; b.dataset.id=p.id; b.onclick=()=>selectPackage(p.id); $('stkm-packages').appendChild(b); }); $('stkm-plans').innerHTML=''; PLANS.forEach((pl)=>{ const b=document.createElement('button'); b.type='button'; b.textContent=pl.name.replace(' Plan',''); b.dataset.code=pl.code; b.onclick=()=>stkPickPlan(pl.code); $('stkm-plans').appendChild(b); }); renderDistButtons(); selectPackage(pkgId); $('stkm').classList.add('open'); renderStep(1); };
  window.stkClose = ()=> $('stkm').classList.remove('open');
  function quote(){ const fd=new FormData(); fd.append('package_id',cur.pkg.id); fetch(BASE+'user/lending/stake_quote',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{ if(!j.status){ $('stkm-cost').textContent=j.message||'?'; return; } cur.usdt=j.usdt; cur.bal=j.usdt_balance; cur.quote=j; $('stkm-cost').textContent = Number(j.usdt).toLocaleString(undefined,{maximumFractionDigits:4})+' USDT'; $('stkm-lock').textContent = Number(j.bman).toLocaleString()+' BMAN'; $('stkm-bonus').textContent= Number(j.bonus).toLocaleString()+' BMAN'; $('stkm-bal').textContent  = Number(j.usdt_balance).toLocaleString(undefined,{maximumFractionDigits:2})+' USDT'; const bw = j.bman_wallets||{}; ['exchange','staking','bonus','earning'].forEach(function(w){ const el=$('stkm-bw-'+w); if(el) el.textContent=Number(bw[w]||0).toLocaleString()+' BMAN'; }); const short = j.usdt_balance + 1e-8 < j.usdt; $('stkm-warn').style.display = short?'block':'none'; $('stkm-go').disabled = short; renderLive(); }).catch(()=>{ $('stkm-cost').textContent='Quote failed'; }); }
  $('stkm-back').onclick = function(){ if(cur.step>1) renderStep(cur.step-1); };
  $('stkm-next').onclick = function(){ if(cur.step<5) renderStep(cur.step+1); };
  window.stkConfirm = function(){ const go=$('stkm-go'); go.disabled=true; go.textContent='Processing?'; const fd=new FormData(); fd.append('package_id',cur.pkg.id); fd.append('plan_code',cur.plan); fd.append('duration_years',cur.years); const endpoint = SWAP_ON ? 'user/lending/swap_purchase' : 'user/lending/purchase_stake'; fetch(BASE+endpoint,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(j=>{ go.textContent='Confirm & Stake'; if(window.Swal){ Swal.fire({icon:j.status?'success':'error',text:j.message,confirmButtonText:'Ok'}).then(()=>{ if(j.status) location.reload(); }); } else { alert(j.message); if(j.status) location.reload(); } if(!j.status) go.disabled=false; }).catch(()=>{ go.textContent='Confirm & Stake'; go.disabled=false; alert('Request failed.'); }); };
})();
</script>
<?php endif; /* staking_packages */ ?>
