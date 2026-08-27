/* =============================================================================
 * wallet_transfer_ui.js — SHARED Wallet Transfer UI layer
 * -----------------------------------------------------------------------------
 * Used identically by BOTH the User Panel (user/transfer_wallet) and the Admin
 * Panel (admin/finance/internal-transfers). It does NOT contain business logic:
 * the single source of truth is the server engine (Wallettransferservice_model).
 * This layer only:
 *   1. Mirrors the direction matrix to DISABLE invalid wallet combinations
 *      (so users cannot pick something that would later error).
 *   2. Calls the shared `preview` endpoint for live rule + balance validation.
 *   3. Renders the shared Confirmation Dialog (identical markup in both panels).
 *   4. Renders the shared Transaction Details modal (identical in both panels).
 * All dialog/modal DOM + CSS is injected from here so the two panels are pixel
 * identical regardless of their host framework (vanilla vs. Metronic/Bootstrap).
 * ========================================================================== */
(function () {
  'use strict';

  /* ----- rule mirror (display/disable only; server re-enforces everything) --- */
  var WALLET_LABEL = { exchange: 'Exchange Wallet', earning: 'Earning Wallet', staking: 'Staking Wallet', bonus: 'Bonus Wallet' };
  var INTERNAL_PAIRS = { exchange: ['bonus', 'earning', 'staking'] };            // Exchange is source-only
  var MEMBER_RULE = { exchange: 'downline', earning: 'downline', staking: 'downline', bonus: 'direct_legs' };
  var MEMBER_RULE_TEXT = {
    downline: "Recipient must be in the source user's downline.",
    direct_legs: "Bonus can only be transferred to the source user's direct left or direct right leg member."
  };

  var cfg = {
    panel: 'user',                 // 'user' | 'admin'
    baseUrl: '',
    previewUrl: '',                // POST → {ok,code,message,from_balance,balance_after,recipient,kyc_ok,has_transfer_password,to_wallet}
    detailUrl: '',                 // GET  ?ref= → {ok,data:{header,ledger,audit,users}}
    csrfName: '',
    csrfHash: '',
    explorerTxUrl: 'https://bscscan.com/tx/'
  };

  /* --------------------------------- utils --------------------------------- */
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmt(n, dp) { dp = dp == null ? 4 : dp; var v = parseFloat(n); return isNaN(v) ? '—' : v.toFixed(dp); }
  function cap(s) { s = String(s || ''); return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
  function wl(w) { return WALLET_LABEL[w] || cap(w) || '—'; }
  function has(v) { return v !== null && v !== undefined && v !== '' && v !== '0' && v !== 0; }

  /* --------------------------- direction matrix ---------------------------- */
  function selfSourceAllowed(from) { return from === 'exchange'; }
  function allowedDestsSelf(from) { return INTERNAL_PAIRS[from] || []; }
  function memberRule(from) { return MEMBER_RULE[from] || null; }
  function memberRuleText(from) { return MEMBER_RULE_TEXT[memberRule(from)] || ''; }

  /**
   * Disable invalid <option>s on a native <select>.
   *   role 'from', mode 'self'  → only Exchange selectable (source-only rule)
   *   role 'from', mode 'member'→ all four selectable
   *   role 'to',   mode 'self'  → only allowedDestsSelf(from) selectable
   * Returns true if the current selection is still valid.
   */
  function applyMatrixToSelect(sel, role, mode, from) {
    if (!sel) return true;
    var opts = sel.options, curOk = true, firstEnabled = null;
    for (var i = 0; i < opts.length; i++) {
      var val = opts[i].value;
      if (val === '') { continue; }
      var ok;
      if (role === 'from') {
        ok = (mode === 'self') ? selfSourceAllowed(val) : (MEMBER_RULE[val] != null);
      } else { // to
        ok = (mode === 'self') ? (allowedDestsSelf(from).indexOf(val) !== -1) : false;
      }
      opts[i].disabled = !ok;
      opts[i].hidden = !ok && role === 'to'; // hide impossible destinations in self-mode
      if (ok && firstEnabled === null) firstEnabled = val;
      if (opts[i].selected && !ok) curOk = false;
    }
    if (!curOk) { sel.value = firstEnabled || ''; }
    return curOk;
  }

  /* ------------------------------- preview --------------------------------- */
  function preview(fields) {
    var fd = new FormData();
    Object.keys(fields).forEach(function (k) { if (fields[k] != null) fd.append(k, fields[k]); });
    if (cfg.csrfName) fd.append(cfg.csrfName, cfg.csrfHash);
    return fetch(cfg.baseUrl + cfg.previewUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); });
  }

  /* ----------------------------- DOM injection ----------------------------- */
  function ensureDom() {
    if (document.getElementById('wtxRoot')) return;

    var css = document.createElement('style');
    css.id = 'wtxStyle';
    css.textContent = [
      '.wtx-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:100000;display:none;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px)}',
      '.wtx-overlay.show{display:flex}',
      '.wtx-dialog{background:#fff;color:#0f172a;border-radius:18px;width:min(520px,100%);max-height:92vh;display:flex;flex-direction:column;box-shadow:0 30px 80px rgba(0,0,0,.35);overflow:hidden;font-family:inherit;animation:wtxUp .2s ease}',
      '.wtx-dialog.wtx-lg{width:min(760px,100%)}',
      '@keyframes wtxUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}',
      '.wtx-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 20px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff}',
      '.wtx-title{font-size:15px;font-weight:800;display:flex;align-items:center;gap:9px;margin:0}',
      '.wtx-ic{width:26px;height:26px;border-radius:8px;background:rgba(255,255,255,.18);display:grid;place-items:center;font-size:14px}',
      '.wtx-x{background:rgba(255,255,255,.16);border:0;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;line-height:1;display:grid;place-items:center}',
      '.wtx-x:hover{background:rgba(255,255,255,.3)}',
      '.wtx-body{padding:18px 20px;overflow-y:auto}',
      '.wtx-foot{display:flex;gap:10px;justify-content:flex-end;padding:14px 20px;border-top:1px solid #eef0f6;background:#fafbff}',
      '.wtx-btn{border:0;border-radius:10px;font-size:13px;font-weight:800;padding:10px 18px;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:opacity .15s,transform .12s}',
      '.wtx-btn:hover{transform:translateY(-1px)}',
      '.wtx-btn-primary{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff}',
      '.wtx-btn-primary:disabled{opacity:.45;cursor:not-allowed;transform:none}',
      '.wtx-btn-ghost{background:#fff;border:1.5px solid #e5e7f0;color:#334155}',
      '.wtx-rows{width:100%;border-collapse:collapse;font-size:13px}',
      '.wtx-rows td{padding:9px 4px;border-bottom:1px solid #f1f3f9;vertical-align:top}',
      '.wtx-rows td.k{color:#64748b;font-weight:700;white-space:nowrap;width:46%}',
      '.wtx-rows td.v{color:#0f172a;font-weight:800;text-align:right}',
      '.wtx-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em}',
      '.wtx-chip.exchange{background:rgba(109,74,255,.12);color:#6d4aff}.wtx-chip.earning{background:rgba(14,165,233,.12);color:#0284c7}.wtx-chip.staking{background:rgba(16,185,129,.12);color:#059669}.wtx-chip.bonus{background:rgba(245,158,11,.12);color:#d97706}',
      '.wtx-chip.ok{background:#ecfdf3;color:#0f9d58}.wtx-chip.bad{background:#fef2f2;color:#dc2626}.wtx-chip.neutral{background:#eef2ff;color:#3730a3}.wtx-chip.warn{background:#fff7ed;color:#c2410c}',
      '.wtx-validation{margin-top:14px;border-radius:12px;padding:12px 14px;font-size:13px;font-weight:800;display:flex;align-items:center;gap:9px}',
      '.wtx-validation.pending{background:#f1f5f9;color:#475569}',
      '.wtx-validation.ok{background:#ecfdf3;color:#0f9d58;border:1.5px solid #b7f0cf}',
      '.wtx-validation.bad{background:#fef2f2;color:#dc2626;border:1.5px solid #fbc5c5}',
      '.wtx-gates{margin-top:10px;font-size:12px;color:#475569}',
      '.wtx-gate{display:flex;align-items:center;gap:8px;padding:4px 0;font-weight:700}',
      '.wtx-gate .dot{width:8px;height:8px;border-radius:50%}',
      '.wtx-gate .dot.ok{background:#0f9d58}.wtx-gate .dot.bad{background:#dc2626}',
      '.wtx-spin{width:15px;height:15px;border:2px solid rgba(0,0,0,.15);border-top-color:currentColor;border-radius:50%;animation:wtxspin .7s linear infinite;display:inline-block}',
      '@keyframes wtxspin{to{transform:rotate(360deg)}}',
      '.wtx-sec{margin:0 0 16px}',
      '.wtx-sec h5{margin:0 0 8px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#6d4aff;display:flex;align-items:center;gap:7px}',
      '.wtx-sec .wtx-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 18px}',
      '.wtx-kv{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;padding:5px 0;border-bottom:1px dashed #eef0f6}',
      '.wtx-kv .kk{color:#64748b;font-weight:700}.wtx-kv .vv{color:#0f172a;font-weight:800;text-align:right;word-break:break-word}',
      '.wtx-kv.full{grid-column:1 / -1}',
      '.wtx-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px}',
      '.wtx-a{color:#4f46e5;text-decoration:none;font-weight:800}.wtx-a:hover{text-decoration:underline}',
      '.wtx-empty{color:#94a3b8;font-weight:700;font-size:12.5px;padding:4px 0}',
      '@media (max-width:560px){.wtx-sec .wtx-grid,.wtx-grid{grid-template-columns:1fr}}',
      // detail modal header + tabs
      '.wtx-dhead{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:0 0 12px}',
      '.wtx-dhead-ref{font-size:12px;color:#475569;font-weight:800}',
      '.wtx-dhead-chips{display:flex;gap:6px;flex-wrap:wrap}',
      '.wtx-tabs{display:flex;gap:4px;overflow-x:auto;border-bottom:1.5px solid #eef0f6;margin:0 0 14px;-webkit-overflow-scrolling:touch}',
      '.wtx-tab{flex:0 0 auto;background:transparent;border:0;border-bottom:2.5px solid transparent;color:#64748b;font-weight:800;font-size:12.5px;padding:9px 12px;cursor:pointer;white-space:nowrap}',
      '.wtx-tab:hover{color:#4f46e5}',
      '.wtx-tab.active{color:#4f46e5;border-bottom-color:#4f46e5}',
      '.wtx-pane{display:none;animation:wtxFade .18s ease}',
      '.wtx-pane.active{display:block}',
      '@keyframes wtxFade{from{opacity:0}to{opacity:1}}',
      '.wtx-grid{display:grid;grid-template-columns:1fr 1fr;gap:2px 18px}',
      '.wtx-subh{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#6d4aff;margin:14px 0 6px}',
      // flow box (summary)
      '.wtx-flow{display:flex;align-items:stretch;gap:10px;margin:16px 0 4px}',
      '.wtx-flow-card{flex:1;border:1.5px solid #eef0f6;border-radius:12px;padding:11px 13px;background:#fafbff}',
      '.wtx-flow-card .lbl{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8}',
      '.wtx-flow-card .nm{font-size:13px;font-weight:800;color:#0f172a;margin:3px 0 2px;word-break:break-word}',
      '.wtx-flow-card .sub{font-size:11px;color:#64748b;font-weight:700}',
      '.wtx-flow-arrow{display:flex;flex-direction:column;align-items:center;justify-content:center;color:#4f46e5;font-weight:900;font-size:12px;min-width:56px}',
      '.wtx-flow-arrow .amt{font-size:12px;color:#0f172a}',
      // ledger cards
      '.wtx-lcard{border:1.5px solid #eef0f6;border-radius:12px;padding:12px 14px;margin:0 0 10px}',
      '.wtx-lcard.debit{border-left:4px solid #dc2626}.wtx-lcard.credit{border-left:4px solid #0f9d58}',
      '.wtx-lcard .lc-top{display:flex;justify-content:space-between;font-size:12px;font-weight:900;margin-bottom:5px}',
      '.wtx-lcard .lc-amt.debit{color:#dc2626}.wtx-lcard .lc-amt.credit{color:#0f9d58}',
      '.wtx-tblwrap{overflow-x:auto;margin-top:6px}',
      '.wtx-tbl{width:100%;border-collapse:collapse;font-size:11.5px}',
      '.wtx-tbl th{text-align:left;color:#94a3b8;font-weight:900;text-transform:uppercase;letter-spacing:.04em;padding:6px 8px;border-bottom:1.5px solid #eef0f6;white-space:nowrap}',
      '.wtx-tbl td{padding:7px 8px;border-bottom:1px solid #f3f4f9;white-space:nowrap}',
      // validation checks
      '.wtx-vrow{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px dashed #eef0f6}',
      '.wtx-vrow .vico{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;font-size:12px;flex:0 0 auto;margin-top:1px}',
      '.wtx-vico.pass{background:#ecfdf3;color:#0f9d58}.wtx-vico.fail{background:#fef2f2;color:#dc2626}.wtx-vico.skip{background:#f1f5f9;color:#94a3b8}.wtx-vico.warn{background:#fff7ed;color:#c2410c}',
      '.wtx-vrow .vbody .vt{font-size:13px;font-weight:800;color:#0f172a}',
      '.wtx-vrow .vbody .vd{font-size:11.5px;color:#64748b;font-weight:600}',
      // audit timeline
      '.wtx-tl{position:relative;margin:6px 0 0;padding-left:22px}',
      '.wtx-tl:before{content:"";position:absolute;left:6px;top:4px;bottom:4px;width:2px;background:#eef0f6}',
      '.wtx-tli{position:relative;padding:0 0 14px}',
      '.wtx-tli:before{content:"";position:absolute;left:-19px;top:3px;width:10px;height:10px;border-radius:50%;background:#4f46e5;box-shadow:0 0 0 3px #eef2ff}',
      '.wtx-tli.ok:before{background:#0f9d58;box-shadow:0 0 0 3px #ecfdf3}.wtx-tli.bad:before{background:#dc2626;box-shadow:0 0 0 3px #fef2f2}',
      '.wtx-tli .ta{font-size:12.5px;font-weight:800;color:#0f172a}',
      '.wtx-tli .tm{font-size:11.5px;color:#64748b;font-weight:600}',
      '.wtx-tli .tt{font-size:11px;color:#94a3b8;font-weight:700;margin-top:1px}'
    ].join('');
    document.head.appendChild(css);

    var root = document.createElement('div');
    root.id = 'wtxRoot';
    root.innerHTML =
      '<div class="wtx-overlay" id="wtxConfirmOverlay">' +
        '<div class="wtx-dialog">' +
          '<div class="wtx-head"><h4 class="wtx-title"><span class="wtx-ic">&#8646;</span> Confirm Transfer</h4><button class="wtx-x" data-wtx-close>&times;</button></div>' +
          '<div class="wtx-body">' +
            '<table class="wtx-rows"><tbody id="wtxConfirmRows"></tbody></table>' +
            '<div class="wtx-validation pending" id="wtxValidation"><span class="wtx-spin"></span> Validating against all business rules…</div>' +
            '<div class="wtx-gates" id="wtxGates"></div>' +
          '</div>' +
          '<div class="wtx-foot"><button class="wtx-btn wtx-btn-ghost" data-wtx-close>Cancel</button><button class="wtx-btn wtx-btn-primary" id="wtxConfirmBtn" disabled>Confirm Transfer</button></div>' +
        '</div>' +
      '</div>' +
      '<div class="wtx-overlay" id="wtxDetailOverlay">' +
        '<div class="wtx-dialog wtx-lg">' +
          '<div class="wtx-head"><h4 class="wtx-title"><span class="wtx-ic">&#128203;</span> Transaction Details</h4><button class="wtx-x" data-wtx-close>&times;</button></div>' +
          '<div class="wtx-body" id="wtxDetailBody"></div>' +
          '<div class="wtx-foot"><button class="wtx-btn wtx-btn-ghost" data-wtx-close>Close</button></div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);

    // close handlers (buttons + backdrop) + tab switching
    root.addEventListener('click', function (e) {
      var tab = e.target.closest('.wtx-tab');
      if (tab) {
        var name = tab.getAttribute('data-tab');
        var scope = tab.closest('.wtx-dialog');
        scope.querySelectorAll('.wtx-tab').forEach(function (t) { t.classList.toggle('active', t === tab); });
        scope.querySelectorAll('.wtx-pane').forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-pane') === name); });
        return;
      }
      if (e.target.hasAttribute('data-wtx-close') || e.target.classList.contains('wtx-overlay')) {
        closeOverlay(e.target.closest('.wtx-overlay') || e.target);
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeOverlay(document.getElementById('wtxConfirmOverlay')); closeOverlay(document.getElementById('wtxDetailOverlay')); }
    });
  }

  function openOverlay(id) { ensureDom(); var o = document.getElementById(id); if (o) o.classList.add('show'); }
  function closeOverlay(o) { if (o && o.classList) o.classList.remove('show'); }

  /* -------------------------- confirmation dialog -------------------------- */
  /**
   * opts = {
   *   fields:  {...}  POSTed to the preview endpoint (panel-specific field names),
   *   mode:    'self' | 'member',
   *   sourceUser:   'John Doe (#247)',
   *   fromWallet:   'exchange',
   *   toWallet:     'bonus'  (self mode; for member it equals fromWallet),
   *   amount:       '12.5',
   *   recipientFallback: 'sameera (SAT123)'  (shown until server confirms),
   *   onConfirm: function(){...}  // panel runs its real submit here
   * }
   */
  function openConfirm(opts) {
    ensureDom();
    var isMember = opts.mode === 'member';
    var toWallet = isMember ? opts.fromWallet : opts.toWallet;
    var rowsEl = document.getElementById('wtxConfirmRows');
    var valEl = document.getElementById('wtxValidation');
    var gatesEl = document.getElementById('wtxGates');
    var btn = document.getElementById('wtxConfirmBtn');

    function row(k, v) { return '<tr><td class="k">' + esc(k) + '</td><td class="v">' + v + '</td></tr>'; }
    function chip(w) { return '<span class="wtx-chip ' + esc(w) + '">' + esc(wl(w)) + '</span>'; }

    rowsEl.innerHTML =
      row('Source User', esc(opts.sourceUser || '—')) +
      row('Recipient', '<span id="wtxCfRecipient">' + esc(opts.recipientFallback || (isMember ? '—' : 'Self (own wallets)')) + '</span>') +
      row('Transfer Type', '<span class="wtx-chip ' + (isMember ? 'neutral' : 'neutral') + '">' + (isMember ? 'Member Transfer' : 'Internal Transfer') + '</span>') +
      row('From Wallet', chip(opts.fromWallet)) +
      row('To Wallet', chip(toWallet)) +
      row('Amount', '<b>' + fmt(opts.amount) + '</b> BMAN') +
      row('Available Balance', '<span id="wtxCfAvail">…</span>') +
      row('Balance After Transfer', '<span id="wtxCfAfter">…</span>');

    valEl.className = 'wtx-validation pending';
    valEl.innerHTML = '<span class="wtx-spin"></span> Validating against all business rules…';
    gatesEl.innerHTML = '';
    btn.disabled = true;
    // rebind confirm (fresh, avoids stacking listeners)
    var fresh = btn.cloneNode(true); btn.parentNode.replaceChild(fresh, btn); btn = fresh;

    openOverlay('wtxConfirmOverlay');

    preview(opts.fields).then(function (res) {
      res = res || {};
      var avail = document.getElementById('wtxCfAvail');
      var after = document.getElementById('wtxCfAfter');
      var rcp = document.getElementById('wtxCfRecipient');
      if (avail) avail.innerHTML = '<b>' + fmt(res.from_balance) + '</b> BMAN';
      if (after) after.innerHTML = (res.balance_after == null ? '<span class="wtx-chip bad">—</span>' : ('<b>' + fmt(res.balance_after) + '</b> BMAN'));
      if (rcp && res.recipient) rcp.innerHTML = esc(res.recipient.name) + ' <span class="wtx-mono">(' + esc(res.recipient.referral_id || ('#' + res.recipient.id)) + ')</span>';

      // User-Panel gates (surfaced, not enforced by preview which runs via=admin)
      var gatesOk = true;
      if (cfg.panel === 'user') {
        var g = '';
        g += gate('KYC approved', !!res.kyc_ok);
        g += gate('Transfer password set', !!res.has_transfer_password);
        gatesEl.innerHTML = g;
        gatesOk = !!res.kyc_ok && !!res.has_transfer_password;
      }

      if (res.ok && gatesOk) {
        valEl.className = 'wtx-validation ok';
        valEl.innerHTML = '&#10004; All business rules passed.';
        btn.disabled = false;
        btn.addEventListener('click', function () {
          closeOverlay(document.getElementById('wtxConfirmOverlay'));
          if (typeof opts.onConfirm === 'function') opts.onConfirm();
        });
      } else {
        valEl.className = 'wtx-validation bad';
        valEl.innerHTML = '&#10006; ' + esc(res.message || (!gatesOk ? 'Complete KYC and set a transfer password first.' : 'Validation failed.'));
        btn.disabled = true;
      }
    }).catch(function () {
      valEl.className = 'wtx-validation bad';
      valEl.innerHTML = '&#10006; Could not validate. Please try again.';
      btn.disabled = true;
    });

    function gate(label, ok) {
      return '<div class="wtx-gate"><span class="dot ' + (ok ? 'ok' : 'bad') + '"></span>' + esc(label) + ' — ' + (ok ? 'Yes' : 'No') + '</div>';
    }
  }

  /* ----------------------------- detail modal ------------------------------ */
  function openDetail(ref) {
    ensureDom();
    var body = document.getElementById('wtxDetailBody');
    body.innerHTML = '<div class="wtx-validation pending"><span class="wtx-spin"></span> Loading transaction…</div>';
    openOverlay('wtxDetailOverlay');
    var url = cfg.baseUrl + cfg.detailUrl + (cfg.detailUrl.indexOf('?') === -1 ? '?' : '&') + 'ref=' + encodeURIComponent(ref);
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.ok || !j.data || !j.data.header) { body.innerHTML = '<div class="wtx-empty">Transaction not found.</div>'; return; }
        body.innerHTML = renderDetail(j.data);
      })
      .catch(function () { body.innerHTML = '<div class="wtx-empty">Could not load the transaction.</div>'; });
  }

  function kv(k, v, full) { return '<div class="wtx-kv' + (full ? ' full' : '') + '"><span class="kk">' + esc(k) + '</span><span class="vv">' + (v == null || v === '' ? '<span class="wtx-empty">—</span>' : v) + '</span></div>'; }
  function grid(inner) { return '<div class="wtx-grid">' + inner + '</div>'; }
  function pane(name, active, inner) { return '<div class="wtx-pane' + (active ? ' active' : '') + '" data-pane="' + name + '">' + inner + '</div>'; }
  function chip(cls, txt) { return '<span class="wtx-chip ' + cls + '">' + esc(txt) + '</span>'; }
  function walletChip(w) { return w ? '<span class="wtx-chip ' + esc(w) + '">' + esc(wl(w)) + '</span>' : '<span class="wtx-empty">—</span>'; }
  function userCell(u) { return u ? (esc(u.name) + ' <span class="wtx-mono">(' + esc(u.referral_id || ('#' + u.id)) + ')</span>') : null; }
  function kycChip(s) { s = String(s || ''); var c = s.toLowerCase() === 'approved' ? 'ok' : (s.toLowerCase() === 'rejected' ? 'bad' : 'warn'); return chip(c, s || '—'); }

  // status → validation icon class + glyph
  function vIcon(status) {
    if (status === 'passed' || status === 'yes') return { c: 'pass', g: '&#10003;' };
    if (status === 'failed' || status === 'no') return { c: status === 'failed' ? 'fail' : 'skip', g: status === 'failed' ? '&#10007;' : '&#8211;' };
    if (status === 'overridden') return { c: 'warn', g: '&#9888;' };
    return { c: 'skip', g: '&#8211;' }; // n/a
  }

  function userTab(u, walletLabel) {
    if (!u) return '<div class="wtx-empty">No user record.</div>';
    return grid(
      kv('User ID', '#' + esc(u.id)) +
      kv('Referral ID', esc(u.referral_id)) +
      kv('Username', esc(u.username)) +
      kv('Full Name', esc(u.full_name || u.name)) +
      kv('Email', esc(u.email)) +
      (u.kyc_status ? kv('KYC Status', kycChip(u.kyc_status)) : '') +
      kv(walletLabel, walletChip(u.wallet)) +
      kv('Balance Before', fmt(u.balance_before)) +
      kv('Balance After', '<b>' + fmt(u.balance_after) + '</b>')
    );
  }

  function renderDetail(d) {
    var h = d.header || {}, t = d.transaction || {}, S = d.sender || null, R = d.receiver || {},
        L = d.ledger_entries || {}, B = d.blockchain, SET = d.settlement || null, V = d.validation || {}, U = d.users || {}, audit = d.audit || [];
    var isMember = t.type === 'member';
    var statusCls = t.status === 'completed' ? 'ok' : (t.status === 'failed' ? 'bad' : 'warn');

    // ---- header ----
    var head = '<div class="wtx-dhead"><div class="wtx-dhead-ref"><span class="wtx-mono">' + esc(t.reference) + '</span> · TXN ' + esc(t.txn_id) + '</div>' +
      '<div class="wtx-dhead-chips">' + chip(statusCls, String(t.status).toUpperCase()) +
      chip(isMember ? 'neutral' : 'exchange', isMember ? 'MEMBER' : 'INTERNAL') +
      chip(t.via === 'admin' ? 'warn' : 'neutral', String(t.via).toUpperCase()) +
      chip('neutral', t.token) + '</div></div>';

    // ---- tab nav ----
    var TABS = [['summary', 'Summary'], ['sender', 'Sender'], ['receiver', 'Receiver'], ['ledger', 'Ledger'], ['blockchain', 'Blockchain'], ['validation', 'Validation'], ['audit', 'Audit Timeline']];
    var nav = '<div class="wtx-tabs">' + TABS.map(function (x, i) { return '<button class="wtx-tab' + (i === 0 ? ' active' : '') + '" data-tab="' + x[0] + '">' + esc(x[1]) + '</button>'; }).join('') + '</div>';

    // ---- Summary ----
    var recvName = R.self ? 'Self (own wallets)' : (d.users && U.recipient ? U.recipient.name : (S ? '—' : '—'));
    var flow = '<div class="wtx-flow">' +
      '<div class="wtx-flow-card"><div class="lbl">Sender</div><div class="nm">' + esc(S ? S.name : '—') + '</div><div class="sub">' + esc(wl(t && S ? S.wallet : '')) + ' · ' + fmt(S ? S.balance_before : 0) + ' &rarr; ' + fmt(S ? S.balance_after : 0) + '</div></div>' +
      '<div class="wtx-flow-arrow">&#8594;<div class="amt"><b>' + fmt(t.amount) + '</b></div>' + esc(t.token) + '</div>' +
      '<div class="wtx-flow-card"><div class="lbl">Receiver</div><div class="nm">' + esc(recvName) + '</div><div class="sub">' + esc(wl(R.wallet)) + ' · ' + fmt(R.balance_before) + ' &rarr; ' + fmt(R.balance_after) + '</div></div></div>';
    var pSummary = pane('summary', true, flow + grid(
      kv('Transaction ID', esc(t.txn_id)) +
      kv('Reference No', '<span class="wtx-mono">' + esc(t.reference) + '</span>') +
      kv('Transaction Type', chip('neutral', t.type_label)) +
      kv('Token', chip('neutral', t.token)) +
      kv('Amount', '<b>' + fmt(t.amount) + '</b> ' + esc(t.token)) +
      kv('Fee', fmt(t.fee)) +
      kv('Net Amount', fmt(t.net_amount)) +
      kv('Status', chip(statusCls, String(t.status).toUpperCase())) +
      kv('Initiated Via', chip(t.via === 'admin' ? 'warn' : 'neutral', String(t.via).toUpperCase())) +
      kv('Created Time', esc(t.created_at)) +
      kv('Completed Time', t.completed_at ? esc(t.completed_at) : null) +
      kv('Notes / Remarks', t.note ? esc(t.note) : null, true) +
      (t.failure_reason ? kv('Failure Reason', esc(t.failure_reason), true) : '')
    ));

    // ---- Sender ----
    var ctx = '';
    if (U.sponsor || U.upline) {
      ctx = '<div class="wtx-subh">Sponsor tree</div>' + grid(
        kv('Direct Sponsor', userCell(U.sponsor)) + kv('Upline', userCell(U.upline)));
    }
    var pSender = pane('sender', false, userTab(S, 'From Wallet') + ctx);

    // ---- Receiver ----
    var pReceiver;
    if (R.self) {
      pReceiver = pane('receiver', false,
        '<div class="wtx-subh">Self transfer — funds move between the sender\'s own wallets</div>' +
        grid(kv('Recipient', 'Self (' + esc(S ? S.name : '—') + ')') +
          kv('To Wallet', walletChip(R.wallet)) +
          kv('Balance Before', fmt(R.balance_before)) +
          kv('Balance After', '<b>' + fmt(R.balance_after) + '</b>')));
    } else {
      pReceiver = pane('receiver', false, userTab(R, 'To Wallet'));
    }

    // ---- Ledger ----
    var deb = L.debit || {}, cre = L.credit || {};
    var rawRows = (L.rows || []).map(function (r) {
      var isDeb = parseFloat(r.debit) > 0;
      return '<tr><td>' + esc(r.id) + '</td><td>' + esc(wl(r.wallet_type)) + '</td>' +
        '<td style="color:' + (isDeb ? '#dc2626' : '#0f9d58') + ';font-weight:800">' + (isDeb ? '&minus;' + fmt(r.debit) : '+' + fmt(r.credit)) + '</td>' +
        '<td>' + fmt(r.balance_after) + '</td><td>' + esc(r.created_at) + '</td></tr>';
    }).join('');
    var pLedger = pane('ledger', false,
      '<div class="wtx-lcard debit"><div class="lc-top"><span>Debit Entry</span><span class="lc-amt debit">&minus;' + fmt(deb.amount) + ' ' + esc(t.token) + '</span></div>' +
      grid(kv('Debit Wallet', walletChip(deb.wallet)) + kv('Ledger ID', esc(deb.ledger_id)) +
        kv('Before Balance', fmt(deb.balance_before)) + kv('After Balance', '<b>' + fmt(deb.balance_after) + '</b>')) + '</div>' +
      '<div class="wtx-lcard credit"><div class="lc-top"><span>Credit Entry</span><span class="lc-amt credit">+' + fmt(cre.amount) + ' ' + esc(t.token) + '</span></div>' +
      grid(kv('Credit Wallet', walletChip(cre.wallet)) + kv('Ledger ID', esc(cre.ledger_id)) +
        kv('Before Balance', fmt(cre.balance_before)) + kv('After Balance', '<b>' + fmt(cre.balance_after) + '</b>')) + '</div>' +
      grid(kv('Ledger Reference', '<span class="wtx-mono">' + esc(L.reference) + '</span>', true)) +
      (rawRows ? '<div class="wtx-subh">Ledger rows (wallet_ledger)</div><div class="wtx-tblwrap"><table class="wtx-tbl"><thead><tr><th>ID</th><th>Wallet</th><th>Change</th><th>Balance After</th><th>Time</th></tr></thead><tbody>' + rawRows + '</tbody></table></div>' : ''));

    // ---- Blockchain ----
    var pBlockchain;
    if (B) {
      var link = B.tx_hash ? (cfg.explorerTxUrl + encodeURIComponent(B.tx_hash)) : null;
      pBlockchain = pane('blockchain', false, grid(
        kv('Transaction Hash', link ? '<a class="wtx-a wtx-mono" target="_blank" rel="noopener" href="' + esc(link) + '">' + esc(B.tx_hash) + '</a>' : (B.tx_hash ? '<span class="wtx-mono">' + esc(B.tx_hash) + '</span>' : null), true) +
        kv('Block Number', esc(B.block_number)) +
        kv('Confirmations', esc(B.confirmations)) +
        kv('Gas Used', esc(B.gas_used)) +
        kv('Gas Fee', has(B.gas_fee) ? esc(B.gas_fee) : null) +
        kv('Network', esc(String(B.network || 'BSC').toUpperCase())) +
        kv('Token', esc(B.token_symbol || t.token)) +
        (B.status ? kv('On-chain Status', chip('neutral', String(B.status).toUpperCase())) : '') +
        (B.from_address ? kv('From Address', '<span class="wtx-mono">' + esc(B.from_address) + '</span>', true) : '') +
        (B.to_address ? kv('To Address', '<span class="wtx-mono">' + esc(B.to_address) + '</span>', true) : '') +
        (link ? kv('Explorer', '<a class="wtx-a" target="_blank" rel="noopener" href="' + esc(link) + '">View on BscScan &#8599;</a>', true) : '')));
    } else if (SET && SET.status && SET.status !== 'skipped') {
      // Queued for on-chain settlement but no tx_hash yet (or the broadcast failed) —
      // show the queue state instead of the misleading "off-chain, no chain tx" message.
      var setMap = {
        pending:    ['warn', 'Queued for on-chain settlement — a scheduled job will broadcast it shortly.'],
        processing: ['warn', 'Currently being broadcast to the blockchain…'],
        failed:     ['bad', 'On-chain settlement failed' + (SET.error ? ': ' + esc(SET.error) : '.') + ' An admin can retry it.'],
      };
      var info = setMap[SET.status] || ['neutral', 'Settlement status: ' + esc(SET.status)];
      pBlockchain = pane('blockchain', false,
        '<div class="wtx-empty">' + chip(info[0], String(SET.status).toUpperCase()) + ' ' + info[1] + '</div>' +
        grid(kv('Settlement Address', SET.address ? '<span class="wtx-mono">' + esc(SET.address) + '</span>' : null, true) +
          kv('Attempts', has(SET.attempts) ? esc(SET.attempts) : null)));
    } else {
      pBlockchain = pane('blockchain', false, '<div class="wtx-empty">This transfer was settled <b>off-chain</b> on the internal ledger — no blockchain transaction is associated. BMAN internal moves do not touch the chain.</div>');
    }

    // ---- Validation ----
    var checks = (V.checks || []).map(function (c) {
      var ic = vIcon(c.status);
      var badge = c.status === 'n/a' ? 'Not applicable' : (c.status === 'overridden' ? 'Overridden' : (c.status === 'passed' ? 'Passed' : (c.status === 'failed' ? 'Failed' : cap(c.status))));
      return '<div class="wtx-vrow"><span class="vico wtx-vico ' + ic.c + '">' + ic.g + '</span><div class="vbody"><div class="vt">' + esc(c.label) + ' — ' + esc(badge) + '</div><div class="vd">' + esc(c.detail) + '</div></div></div>';
    }).join('');
    var vOverall = V.overall === 'passed'
      ? '<div class="wtx-validation ok">&#10004; All applicable validations passed.</div>'
      : '<div class="wtx-validation bad">&#10006; ' + esc(String(V.overall || 'unknown').toUpperCase()) + '</div>';
    var pValidation = pane('validation', false, vOverall + checks);

    // ---- Audit Timeline ----
    var last = audit.length ? audit[audit.length - 1] : {};
    var ua = h.user_agent || last.user_agent || '';
    var meta = grid(
      kv('Initiated By', esc(String(t.via || 'user').toUpperCase()) + (has(h.created_by) ? ' #' + esc(h.created_by) : '')) +
      kv('IP Address', esc(h.ip_address || last.ip_address)) +
      kv('Browser / Device', ua ? esc(deviceFromUA(ua)) : null) +
      kv('Request ID', esc(last.request_id)) +
      kv('User Agent', ua ? '<span class="wtx-mono">' + esc(ua) + '</span>' : null, true));
    var tl = audit.length
      ? '<div class="wtx-tl">' + audit.map(function (a) {
          var good = String(a.result_code) === 'ok';
          var bad = a.action === 'failed' || a.action === 'rejected';
          return '<div class="wtx-tli ' + (bad ? 'bad' : (good ? 'ok' : '')) + '"><div class="ta">' + esc(cap(a.action)) + ' · <span class="wtx-mono">' + esc(a.result_code) + '</span></div>' +
            (a.message ? '<div class="tm">' + esc(a.message) + '</div>' : '') +
            '<div class="tt">' + esc(a.created_at) + (a.actor_type ? ' · ' + esc(String(a.actor_type).toUpperCase()) + (has(a.actor_id) ? ' #' + esc(a.actor_id) : '') : '') + '</div></div>';
        }).join('') + '</div>'
      : '<div class="wtx-empty">No audit rows recorded.</div>';
    var pAudit = pane('audit', false, meta + '<div class="wtx-subh">Timeline</div>' + tl);

    return head + nav + pSummary + pSender + pReceiver + pLedger + pBlockchain + pValidation + pAudit;
  }

  function deviceFromUA(ua) {
    ua = String(ua);
    var device = /Mobi|Android|iPhone|iPad/i.test(ua) ? 'Mobile' : 'Desktop';
    var os = /Windows/i.test(ua) ? 'Windows' : /Mac OS X|Macintosh/i.test(ua) ? 'macOS' : /Android/i.test(ua) ? 'Android' : /iPhone|iPad|iOS/i.test(ua) ? 'iOS' : /Linux/i.test(ua) ? 'Linux' : 'Unknown OS';
    var br = /Edg\//i.test(ua) ? 'Edge' : /OPR\/|Opera/i.test(ua) ? 'Opera' : /Chrome\//i.test(ua) ? 'Chrome' : /Firefox\//i.test(ua) ? 'Firefox' : /Safari\//i.test(ua) ? 'Safari' : 'Browser';
    return br + ' · ' + os + ' · ' + device;
  }

  /* --------------------------------- init ---------------------------------- */
  function init(options) { cfg = Object.assign(cfg, options || {}); ensureDom(); }

  window.WalletTransferUI = {
    init: init,
    preview: preview,
    openConfirm: openConfirm,
    openDetail: openDetail,
    applyMatrixToSelect: applyMatrixToSelect,
    allowedDestsSelf: allowedDestsSelf,
    selfSourceAllowed: selfSourceAllowed,
    memberRule: memberRule,
    memberRuleText: memberRuleText,
    WALLET_LABEL: WALLET_LABEL,
    INTERNAL_PAIRS: INTERNAL_PAIRS,
    MEMBER_RULE: MEMBER_RULE,
    esc: esc, fmt: fmt, wl: wl
  };
})();
