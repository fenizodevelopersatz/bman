<?php
// ===================== WALLET TRANSFER PAGE (USER • ADVANCED UI) =====================
// Expected vars (examples):
// $wallet = (object)['main'=>12450.00,'commission'=>3980.00,'tcoin'=>250.00];
// $user = (object)['uid'=>'NEXMAN123','name'=>'Lucas'];
// $receivers = [
//   (object)['id'=>1,'uid'=>'FENI001','name'=>'Arun Kumar'],
//   (object)['id'=>2,'uid'=>'FENI002','name'=>'Priya S'],
// ];
// $limits = (object)['min'=>1,'max'=>5000,'fee'=>0,'daily_limit'=>20000,'daily_used'=>3200];
// $recent_transfers = [
//   (object)['tx'=>'TRX-10021','to_uid'=>'FENI001','to_name'=>'Arun Kumar','wallet'=>'MAIN','amount'=>50,'status'=>'SUCCESS','date'=>'2026-01-27 10:15'],
//   (object)['tx'=>'TRX-10018','to_uid'=>'FENI002','to_name'=>'Priya S','wallet'=>'MAIN','amount'=>120,'status'=>'PENDING','date'=>'2026-01-26 18:40'],
// ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php $this->load->view('user/layout/v2/user_style'); ?>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    /* ===================== WALLET TRANSFER ===================== */
    .titlebar{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin:8px 0 18px;}
    .titlebar h2{margin:0;font-size:18px;font-weight:1100;color:var(--text-main);display:flex;gap:10px;align-items:center;}
    .titlebar h2 i{color:var(--primary);font-size:20px;}
    .titlebar .sub{margin-top:4px;font-size:12px;color:var(--text-muted);font-weight:900;}

    .actions{display:flex;gap:10px;flex-wrap:wrap;}
    .btn-soft{border:1px solid #f1f1f6;background:#fff;border-radius:14px;padding:10px 12px;font-weight:1100;cursor:pointer;font-size:12px;display:inline-flex;align-items:center;gap:8px;}
    .btn-main{border:none;background:var(--primary);color:#fff;border-radius:14px;padding:10px 12px;font-weight:1100;cursor:pointer;font-size:12px;display:inline-flex;align-items:center;gap:8px;}
    .btn-dark{border:none;background:#111;color:#fff;border-radius:14px;padding:10px 12px;font-weight:1100;cursor:pointer;font-size:12px;display:inline-flex;align-items:center;gap:8px;}

    .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;}
    .kpi{border:1px solid #f5f5f7;background:#fff;border-radius:20px;padding:14px;display:flex;gap:12px;align-items:center;}
    .kpi .ic{width:44px;height:44px;border-radius:16px;display:grid;place-items:center;font-size:20px;}
    .kpi small{display:block;font-size:10px;color:var(--text-muted);font-weight:1100;}
    .kpi strong{display:block;font-size:14px;font-weight:1200;margin-top:2px;}
    .kpi span{display:block;font-size:11px;color:var(--text-muted);font-weight:900;margin-top:3px;}

    .grid-2{display:grid;grid-template-columns:1.05fr .95fr;gap:14px;}
    .card{background:#fff;border:1px solid #f5f5f7;border-radius:22px;padding:14px;box-shadow:0 12px 30px rgba(0,0,0,0.04);}
    .card-h{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;}
    .card-h h3{margin:0;font-size:14px;font-weight:1100;}
    .chip{display:inline-flex;align-items:center;gap:8px;padding:7px 10px;border-radius:999px;border:1px solid #eeecff;background:#efedfb;color:var(--primary);font-size:10px;font-weight:1100;}

    .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .field{display:flex;flex-direction:column;}
    label{font-size:11px;color:var(--text-muted);font-weight:1100;display:block;margin-bottom:6px;}

    .inp, .sel{
      width:100%; border:1px solid #f1f1f6; background:#f7f7fb;
      border-radius:14px; padding:12px; outline:none; font-size:12px; font-weight:900;
    }
    .inp:focus,.sel:focus{background:#fff;border-color:#dcd7ff;box-shadow:0 0 0 4px rgba(110,86,207,0.10);}
    .hint{font-size:11px;color:var(--text-muted);font-weight:900;line-height:1.4;margin-top:6px;}

    /* Balance cards (like screenshot) */
    .balbox{
      border:1px dashed #e7e7f3;background:#fbfbff;border-radius:18px;padding:14px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;
    }
    .balbox .l b{display:block;font-size:16px;font-weight:1200;color:#111;}
    .balbox .l small{display:block;font-size:11px;font-weight:1100;color:#0f9d58;margin-top:2px;}
    .balbox .r{width:44px;height:44px;border-radius:16px;background:#ecfdf3;color:#0f9d58;display:grid;place-items:center;font-size:20px;}

    .btn-full{width:100%;border:none;border-radius:16px;padding:12px 14px;cursor:pointer;font-weight:1200;background:#efedfb;color:var(--primary);display:flex;align-items:center;justify-content:center;gap:8px;}
    .btn-full.primary{background:var(--primary);color:#fff;}
    .btn-full.dark{background:#111;color:#fff;}
    .btn-inline{border:1px solid #f1f1f6;background:#fff;border-radius:12px;padding:10px 12px;font-weight:1100;cursor:pointer;font-size:12px;display:inline-flex;align-items:center;gap:8px;}

    /* Limits */
    .pillx{
      display:flex;justify-content:space-between;align-items:center;gap:10px;
      border:1px solid #f1f1f6;background:#f7f7fb;border-radius:16px;padding:12px;
      font-size:12px;font-weight:1100;color:#111;
    }
    .pillx span{font-size:11px;color:var(--text-muted);font-weight:900;}
    .pillx b{font-size:12px;}

    /* Table */
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:8px;}
    .table{width:100%;border-collapse:separate;border-spacing:0 10px;}
    .table th{font-size:11px;color:var(--text-muted);text-align:left;font-weight:1100;padding:0 10px;}
    .tr{background:#fff;border:1px solid #f5f5f7;border-radius:18px;box-shadow:0 12px 25px rgba(0,0,0,0.03);}
    .tr td{padding:12px 10px;font-size:12px;font-weight:900;color:#111;}
    .td-title b{display:block;font-size:12px;font-weight:1200;}
    .td-title small{display:block;font-size:11px;color:var(--text-muted);font-weight:900;margin-top:2px;}
    .badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;font-size:10px;font-weight:1200;border:1px solid #f1f1f6;background:#fff;}
    .b-ok{border-color:#dcfce7;background:#ecfdf3;color:#0f9d58;}
    .b-warn{border-color:#ffedd5;background:#fff7ed;color:#c2410c;}
    .b-bad{border-color:#fee2e2;background:#fef2f2;color:#b91c1c;}
    .amt{font-size:13px;font-weight:1200;text-align:right;}
    .empty{border:1px dashed #e7e7f3;background:#fbfbff;border-radius:18px;padding:18px;text-align:center;color:var(--text-muted);font-weight:900;font-size:12px;}

    @media(max-width:1100px){.kpis{grid-template-columns:repeat(2,1fr);} .grid-2{grid-template-columns:1fr;} .row2{grid-template-columns:1fr;}}
  </style>
</head>

<body>
<div class="app-container">
  <?php $this->load->view('user/layout/v2/user_sidebar'); ?>

  <main class="main-content">
    <?php $this->load->view('user/layout/v2/user_header'); ?>

    <div class="titlebar">
      <div>
        <h2><i class="ph ph-arrows-left-right"></i> Wallet Transfer</h2>
        <div class="sub">Transfer wallet balance to another member securely (internal transfer).</div>
      </div>
      <div class="actions">
        <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/wallet'); ?>'"><i class="ph ph-wallet"></i> Wallet</button>
        <button class="btn-soft" type="button" onclick="location.href='<?= base_url('user/commissions'); ?>'"><i class="ph ph-coins"></i> Commissions</button>
        <button class="btn-main" type="button" onclick="location.href='<?= base_url('user/payouts'); ?>'"><i class="ph ph-bank"></i> Payouts</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpis">
      <div class="kpi">
        <div class="ic" style="background:#ecfdf3;color:#0f9d58;"><i class="ph ph-wallet"></i></div>
        <div>
          <small>Main Balance</small>
          <strong><?= currency_info()->currency_symbol; ?> <?= number_format((float)($wallet->main ?? 0), 2); ?></strong>
          <span>Available to transfer</span>
        </div>
      </div>

      <div class="kpi">
        <div class="ic" style="background:#fff7ed;color:#c2410c;"><i class="ph ph-hourglass"></i></div>
        <div>
          <small>Commission Wallet</small>
          <strong><?= currency_info()->currency_symbol; ?> <?= number_format((float)($wallet->commission ?? 0), 2); ?></strong>
          <span>May have restrictions</span>
        </div>
      </div>

      <div class="kpi">
        <div class="ic" style="background:#efedfb;color:var(--primary);"><i class="ph ph-coin"></i></div>
        <div>
          <small>Bonus / Coins</small>
          <strong><?= number_format((float)($wallet->tcoin ?? 0), 2); ?></strong>
          <span>Reward balance</span>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <!-- LEFT: Transfer Form -->
      <div class="card">
        <div class="card-h">
          <h3>Make a Transfer</h3>
          <span class="chip"><i class="ph ph-lock-key"></i> Verified</span>
        </div>

        <!-- Balance box like your old layout -->
        <div class="row2" style="margin-bottom:12px;">
          <div class="field">
            <label>Your Balance Info *</label>
            <div class="balbox">
              <div class="l">
                <b><?= currency_info()->currency_symbol; ?> <?= number_format((float)($wallet->main ?? 0), 2); ?></b>
                <small>Main Balance</small>
              </div>
              <div class="r"><i class="ph ph-wallet"></i></div>
            </div>
          </div>

          <div class="field">
            <label>Receiver Balance Info *</label>
            <div class="balbox" style="background:#fff;">
              <div class="l">
                <b id="receiverBal"><?= currency_info()->currency_symbol; ?> 0.00</b>
                <small id="receiverBalLbl">Main Balance</small>
              </div>
              <div class="r" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-user-circle"></i></div>
            </div>
          </div>
        </div>

        <form method="post" action="<?= base_url('user/wallet/transfer'); ?>" id="transferForm">
          <div class="row2">
            <div class="field">
              <label>Select Receiver *</label>
              <select class="sel" name="receiver_id" id="receiver" required>
                <option value="">Select Receiver</option>
                <?php if(!empty($receivers)): foreach($receivers as $r): ?>
                  <option value="<?= (int)$r->id; ?>" data-uid="<?= htmlspecialchars($r->uid); ?>" data-name="<?= htmlspecialchars($r->name); ?>">
                    <?= htmlspecialchars($r->name); ?> (<?= htmlspecialchars($r->uid); ?>)
                  </option>
                <?php endforeach; endif; ?>
              </select>
              <div class="hint">Tip: Search and pick a valid member. Transfers are irreversible.</div>
            </div>

            <div class="field">
              <label>Wallet Type *</label>
              <select class="sel" name="wallet_type" id="walletType" required>
                <option value="MAIN">Main Balance</option>
                <option value="COMMISSION">Commission Wallet</option>
                <option value="TCOIN">Bonus / Coins</option>
              </select>
              <div class="hint">Some wallet types may be restricted by admin rules.</div>
            </div>
          </div>

          <div class="row2" style="margin-top:12px;">
            <div class="field">
              <label>Amount *</label>
              <input class="inp" type="number" step="0.01" min="0" name="amount" id="amount" placeholder="Enter Amount" required>
              <div class="hint">
                Min: <?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->min ?? 0), 2); ?> •
                Max: <?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->max ?? 0), 2); ?>
              </div>
            </div>

            <div class="field">
              <label>Note (optional)</label>
              <input class="inp" type="text" name="note" placeholder="e.g., Help / purchase / settlement">
              <div class="hint">Visible in transfer history.</div>
            </div>
          </div>

          <!-- Fee + Total -->
          <div class="row2" style="margin-top:12px;">
            <div class="field">
              <label>Transfer Fee</label>
              <input class="inp" type="text" value="<?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->fee ?? 0), 2); ?>" readonly>
            </div>
            <div class="field">
              <label>Total Deduction</label>
              <input class="inp" type="text" id="totalDeduct" value="—" readonly>
            </div>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
            <button class="btn-full primary" type="submit"><i class="ph ph-paper-plane-tilt"></i> Submit Transfer</button>
            <button class="btn-full" type="button" onclick="clearForm()"><i class="ph ph-x-circle"></i> Reset</button>
          </div>

          <div class="hint" style="margin-top:10px;">
            Daily Limit: <?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->daily_limit ?? 0), 2); ?> •
            Used: <?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->daily_used ?? 0), 2); ?>
          </div>
        </form>
      </div>

      <!-- RIGHT: Rules + Quick Tools -->
      <div class="card">
        <div class="card-h">
          <h3>Transfer Rules</h3>
          <span class="chip"><i class="ph ph-info"></i> Read</span>
        </div>

        <div class="pillx">
          <div>
            <b>Minimum Transfer</b>
            <div><span><?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->min ?? 0), 2); ?></span></div>
          </div>
          <i class="ph ph-receipt" style="color:var(--primary);font-size:18px;"></i>
        </div>

        <div class="pillx" style="margin-top:10px;">
          <div>
            <b>Maximum Transfer</b>
            <div><span><?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->max ?? 0), 2); ?></span></div>
          </div>
          <i class="ph ph-trend-up" style="color:var(--primary);font-size:18px;"></i>
        </div>

        <div class="pillx" style="margin-top:10px;">
          <div>
            <b>Daily Limit</b>
            <div><span><?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->daily_limit ?? 0), 2); ?></span></div>
          </div>
          <i class="ph ph-calendar-check" style="color:var(--primary);font-size:18px;"></i>
        </div>

        <div class="pillx" style="margin-top:10px;">
          <div>
            <b>Transfer Fee</b>
            <div><span><?= currency_info()->currency_symbol; ?> <?= number_format((float)($limits->fee ?? 0), 2); ?></span></div>
          </div>
          <i class="ph ph-percent" style="color:var(--primary);font-size:18px;"></i>
        </div>

        <div class="hint" style="margin-top:12px;">
          Tip: Always verify the receiver UID. If you transfer to the wrong member, it cannot be reversed.
        </div>

        <div style="display:grid;gap:10px;margin-top:12px;">
          <button class="btn-full dark" type="button" onclick="location.href='<?= base_url('user/referrals'); ?>'">
            Share Referral <i class="ph ph-share-network"></i>
          </button>
          <button class="btn-full primary" type="button" onclick="location.href='<?= base_url('user/support'); ?>'">
            Create Support Ticket <i class="ph ph-headset"></i>
          </button>
        </div>
      </div>

      <!-- FULL WIDTH: Transfer History -->
      <div class="card" style="grid-column:1/-1;">
        <div class="card-h">
          <h3>Recent Transfers</h3>
          <span class="chip"><i class="ph ph-clock-counter-clockwise"></i> History</span>
        </div>

        <div class="filters">
          <input class="inp" id="q" placeholder="Search: tx id, receiver, uid..." />
          <select class="sel" id="status">
            <option value="">All Status</option>
            <option value="SUCCESS">Success</option>
            <option value="PENDING">Pending</option>
            <option value="FAILED">Failed</option>
          </select>
          <select class="sel" id="wtype">
            <option value="">All Wallet</option>
            <option value="main">Main Balance</option>
            <option value="commission">Commission Wallet</option>
            <option value="bonus">Bonus Wallet</option>
          </select>
          <button class="btn-soft" type="button" onclick="resetFilters()"><i class="ph ph-x-circle"></i> Reset</button>
        </div>

        <div style="margin-top:10px; overflow:auto;">
          <table class="table" id="tTable">
            <thead>
              <tr>
                <th style="width:34%;">Transfer</th>
                <th>Wallet</th>
                <th>Date</th>
                <th style="text-align:right;">Amount</th>
              </tr>
            </thead>
            <tbody>
            <?php if(!empty($recent_transfers)): foreach($recent_transfers as $t): ?>
              <?php
                $st = strtoupper($t->status ?? 'PENDING');
                $badge = $st==='SUCCESS' ? 'b-ok' : (($st==='PENDING') ? 'b-warn' : 'b-bad');
                $wt = strtoupper($t->wallet ?? 'MAIN');
              ?>
              <tr class="tr"
                  data-q="<?= htmlspecialchars(strtolower(($t->tx ?? '').' '.($t->to_uid ?? '').' '.($t->to_name ?? ''))); ?>"
                  data-status="<?= htmlspecialchars($st); ?>"
                  data-wtype="<?= htmlspecialchars($wt); ?>">
                <td class="td-title">
                  <b><?= htmlspecialchars($t->tx ?? '—'); ?></b>
                  <small>To: <?= htmlspecialchars($t->to_name ?? '—'); ?> (<?= htmlspecialchars($t->to_uid ?? '—'); ?>)</small>
                </td>
                <td><span class="badge"><i class="ph ph-wallet"></i> <?= $wt; ?></span></td>
                <td>
                  <?= htmlspecialchars($t->date ?? '—'); ?>
                  <div style="margin-top:6px;"><span class="badge <?= $badge; ?>"><i class="ph ph-seal-check"></i> <?= $st; ?></span></div>
                </td>
                <td class="amt"><?= currency_info()->currency_symbol; ?> <?= number_format((float)($t->amount ?? 0), 2); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="4"><div class="empty">No transfers found.</div></td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <aside class="right-panel">
    <?php $this->load->view('user/layout/v2/user_inner_right_panle');?>
  </aside>
</div>

<script src="<?php echo base_url();?>/assets/user_v2/js/script.js?ver=2.9"></script>
<script>
  // ===== Receiver balance fetch (connect to your API) =====
  // Replace with real endpoint: /user/wallet/receiver_balance?id=...
  async function loadReceiverBalance(receiverId, walletType){
    // UI only demo:
    // - You can return JSON: {balance: 120.50, label:'Main Balance'}
    // Example integration:
    // const res = await fetch("<?= base_url('user/wallet/receiver_balance'); ?>?id="+receiverId+"&wallet="+walletType);
    // return await res.json();

    return { balance: 0, label: (walletType === 'COMMISSION' ? 'Commission Wallet' : (walletType === 'TCOIN' ? 'Bonus / Coins' : 'Main Balance')) };
  }

  const sym = <?= json_encode(currency_info()->currency_symbol); ?>;
  const fee = <?= json_encode((float)($limits->fee ?? 0)); ?>;

  const receiver = document.getElementById('receiver');
  const walletType = document.getElementById('walletType');
  const receiverBal = document.getElementById('receiverBal');
  const receiverBalLbl = document.getElementById('receiverBalLbl');
  const amount = document.getElementById('amount');
  const totalDeduct = document.getElementById('totalDeduct');

  async function refreshReceiverBal(){
    if(!receiver.value){
      receiverBal.textContent = sym + " 0.00";
      receiverBalLbl.textContent = "Main Balance";
      return;
    }
    const data = await loadReceiverBalance(receiver.value, walletType.value);
    receiverBal.textContent = sym + " " + (parseFloat(data.balance||0).toFixed(2));
    receiverBalLbl.textContent = data.label || "Main Balance";
  }

  function calcTotal(){
    const v = parseFloat(amount.value || "0") || 0;
    const tot = Math.max(0, v + fee);
    totalDeduct.value = sym + " " + tot.toFixed(2);
  }

  if(receiver) receiver.addEventListener('change', refreshReceiverBal);
  if(walletType) walletType.addEventListener('change', ()=>{ refreshReceiverBal(); calcTotal(); });
  if(amount) amount.addEventListener('input', calcTotal);
  refreshReceiverBal(); calcTotal();

  function clearForm(){
    document.getElementById('transferForm').reset();
    refreshReceiverBal();
    calcTotal();
  }

  // ===== History filters =====
  const q = document.getElementById('q');
  const st = document.getElementById('status');
  const wt = document.getElementById('wtype');
  const rows = () => Array.from(document.querySelectorAll('#tTable tbody .tr'));

  function applyFilters(){
    const s = (q.value||"").trim().toLowerCase();
    const ss = (st.value||"").trim();
    const ww = (wt.value||"").trim();
    rows().forEach(r=>{
      const okQ = !s || (r.dataset.q||"").includes(s);
      const okS = !ss || r.dataset.status === ss;
      const okW = !ww || r.dataset.wtype === ww;
      r.style.display = (okQ && okS && okW) ? "" : "none";
    });
  }
  if(q) q.addEventListener('input', applyFilters);
  if(st) st.addEventListener('change', applyFilters);
  if(wt) wt.addEventListener('change', applyFilters);

  function resetFilters(){
    if(q) q.value="";
    if(st) st.value="";
    if(wt) wt.value="";
    applyFilters();
  }
</script>
</body>
</html>
