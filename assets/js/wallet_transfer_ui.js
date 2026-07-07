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
  var MEMBER_RULE = { exchange: 'downline', earning: 'downline', staking: 'downline', bonus: 'direct_sponsor' };
  var MEMBER_RULE_TEXT = {
    downline: "Recipient must be in the source user's downline.",
    direct_sponsor: "Bonus can only be transferred to the source user's direct sponsor."
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
      '@media (max-width:560px){.wtx-sec .wtx-grid{grid-template-columns:1fr}}'
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

    // close handlers (buttons + backdrop)
    root.addEventListener('click', function (e) {
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
  function sec(title, icon, inner) { return '<div class="wtx-sec"><h5>' + icon + ' ' + esc(title) + '</h5><div class="wtx-grid">' + inner + '</div></div>'; }
  function userCell(u) { return u ? (esc(u.name) + ' <span class="wtx-mono">(' + esc(u.referral_id || ('#' + u.id)) + ')</span>') : null; }

  function renderDetail(d) {
    var h = d.header || {}, U = d.users || {}, ledger = d.ledger || [], audit = d.audit || [];
    var isMember = h.txn_type === 'member';
    var statusChip = '<span class="wtx-chip ' + (h.status === 'completed' ? 'ok' : (h.status === 'failed' ? 'bad' : 'warn')) + '">' + esc(String(h.status || '').toUpperCase()) + '</span>';
    var viaChip = '<span class="wtx-chip ' + (h.via === 'admin' ? 'warn' : 'neutral') + '">' + esc(String(h.via || 'user').toUpperCase()) + '</span>';
    var typeChip = '<span class="wtx-chip ' + (isMember ? 'neutral' : 'exchange') + '">' + (isMember ? 'MEMBER' : 'INTERNAL') + '</span>';

    // General
    var general = kv('Transaction ID', esc(h.txn_uid || h.id)) +
      kv('Reference', '<span class="wtx-mono">' + esc(h.ref) + '</span>') +
      kv('Status', statusChip) +
      kv('Type / Via', typeChip + ' ' + viaChip) +
      kv('Created', esc(h.created_at)) +
      kv('Completed', h.status === 'completed' ? esc(h.updated_at || h.created_at) : null);

    // Users
    var users = kv('Sender', userCell(U.sender)) +
      kv('Recipient', isMember ? userCell(U.recipient) : '<span class="wtx-empty">Self (own wallets)</span>') +
      kv('Direct Sponsor', userCell(U.sponsor)) +
      kv('Upline', userCell(U.upline));

    // Wallet
    var wallet = kv('From Wallet', '<span class="wtx-chip ' + esc(h.from_wallet) + '">' + esc(wl(h.from_wallet)) + '</span>') +
      kv('To Wallet', '<span class="wtx-chip ' + esc(h.to_wallet) + '">' + esc(wl(h.to_wallet)) + '</span>') +
      kv('Amount', '<b>' + fmt(h.amount) + '</b> BMAN') +
      kv('Fee', fmt(h.fee)) +
      kv('Sender Balance', fmt(h.from_before) + ' &rarr; <b>' + fmt(h.from_after) + '</b>') +
      kv('Recipient Balance', isMember ? (fmt(h.to_before) + ' &rarr; <b>' + fmt(h.to_after) + '</b>') : (fmt(h.to_before) + ' &rarr; <b>' + fmt(h.to_after) + '</b>'));

    // Ledger (double entry)
    var deb = null, cre = null;
    ledger.forEach(function (l) { if (parseFloat(l.debit) > 0) deb = l; else if (parseFloat(l.credit) > 0) cre = l; });
    var ledgerHtml = kv('Debit Entry', deb ? (wl(deb.wallet_type) + ' &minus;<b>' + fmt(deb.debit) + '</b> → bal ' + fmt(deb.balance_after)) : null) +
      kv('Credit Entry', cre ? (wl(cre.wallet_type) + ' +<b>' + fmt(cre.credit) + '</b> → bal ' + fmt(cre.balance_after)) : null) +
      kv('Ledger Ref', '<span class="wtx-mono">' + esc(h.ref) + '</span>', true);

    // Blockchain (only when present)
    var blockchain = '';
    if (has(h.tx_hash)) {
      var link = cfg.explorerTxUrl + encodeURIComponent(h.tx_hash);
      blockchain = sec('Blockchain', '&#9741;',
        kv('Tx Hash', '<a class="wtx-a wtx-mono" target="_blank" rel="noopener" href="' + esc(link) + '">' + esc(h.tx_hash) + '</a>', true) +
        kv('Block Number', esc(h.block_number)) +
        kv('Confirmations', esc(h.confirmations)) +
        kv('Gas Used', esc(h.gas_used)) +
        kv('Gas Fee', has(h.gas_fee) ? esc(h.gas_fee) : null) +
        kv('Network', esc((h.network || 'BSC').toUpperCase())) +
        kv('Explorer', '<a class="wtx-a" target="_blank" rel="noopener" href="' + esc(link) + '">View on BscScan &#8599;</a>'));
    }

    // Audit — prefer the last audit row, fall back to header fields
    var last = audit.length ? audit[audit.length - 1] : {};
    var ua = h.user_agent || last.user_agent || '';
    var trail = audit.length
      ? '<div class="wtx-kv full"><span class="kk">Trail</span><span class="vv">' +
        audit.map(function (a) { return '<div>' + esc(a.action) + ' · <span class="wtx-mono">' + esc(a.result_code) + '</span> · ' + esc(a.created_at) + '</div>'; }).join('') +
        '</span></div>'
      : '';
    var auditHtml = kv('Created By', esc(String(h.via || 'user').toUpperCase()) + (has(h.created_by) ? ' #' + esc(h.created_by) : '')) +
      kv('IP Address', esc(h.ip_address || last.ip_address)) +
      kv('Browser / Device', ua ? esc(deviceFromUA(ua)) : null) +
      kv('User Agent', ua ? '<span class="wtx-mono">' + esc(ua) + '</span>' : null, true) +
      kv('Request ID', esc(last.request_id)) +
      kv('Timestamp', esc(last.created_at || h.created_at)) +
      trail;

    return sec('General', '&#128203;', general) +
      sec('Users', '&#128100;', users) +
      sec('Wallet', '&#128176;', wallet) +
      sec('Ledger (Double Entry)', '&#8645;', ledgerHtml) +
      blockchain +
      sec('Audit', '&#128737;', auditHtml);
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
