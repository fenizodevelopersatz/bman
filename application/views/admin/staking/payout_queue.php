<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
  data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
  data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
  data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
  <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
      <?php $this->load->view('admin/Layout/admin_topbar'); ?>
      <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
        <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
          <div class="d-flex flex-column flex-column-fluid">
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Staking</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Payout Queue</li>
                  </ul>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-center mb-6">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">On-chain transfers for binary matching payouts. Every broadcast is
                    gated on a treasury BNB (gas) + BMAN balance precheck; a shortfall or broadcast failure lands
                    a row here as <b>RETRY</b>/<b>FAILED</b> for manual retry. Retrying never re-credits any
                    wallet — the internal ledger credit already happened when the matching engine ran.</div>
                </div>

                <!-- KPIs -->
                <div class="row g-5 g-xl-8 mb-6">
                  <div class="col-md-3">
                    <div class="card bg-light-primary">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Pending</div>
                        <div class="text-primary fw-bold fs-2x mt-2"><?php echo (int)$summary['by_status']['PENDING']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-info">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Processing</div>
                        <div class="text-info fw-bold fs-2x mt-2"><?php echo (int)$summary['by_status']['PROCESSING']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card <?php echo $summary['needs_attention'] > 0 ? 'bg-light-danger' : 'bg-light-success'; ?>">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Needs Attention</div>
                        <div class="<?php echo $summary['needs_attention'] > 0 ? 'text-danger' : 'text-success'; ?> fw-bold fs-2x mt-2">
                          <?php echo (int)$summary['needs_attention']; ?></div>
                        <div class="text-muted fs-8">Failed: <?php echo (int)$summary['by_status']['FAILED']; ?> · Retry: <?php echo (int)$summary['by_status']['RETRY']; ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-success">
                      <div class="card-body">
                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Confirmed Today</div>
                        <div class="text-success fw-bold fs-2x mt-2"><?php echo number_format($summary['confirmed_today_amt'], 4); ?></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Treasury funding: can we actually pay what is queued? -->
                <div class="card mb-6" id="pq-treasury-card">
                  <div class="card-header pt-6 d-flex align-items-center justify-content-between">
                    <h3 class="card-title fw-bold mb-0">Treasury Funding</h3>
                    <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-light" id="pq-treasury-refresh">Refresh</button>
                      <button class="btn btn-sm btn-warning" id="pq-retry-all"
                              <?php echo $summary['needs_attention'] > 0 ? '' : 'disabled'; ?>>
                        Retry all failed / held (<?php echo (int)$summary['needs_attention']; ?>)
                      </button>
                    </div>
                  </div>
                  <div class="card-body pt-3 pb-8">
                    <div id="pq-treasury-body" class="text-muted fs-7">Checking treasury balance…</div>
                    <div class="separator my-5"></div>
                    <div class="fs-8 text-muted">
                      A shortfall <b>never loses money and never costs a member anything</b> — the internal wallet
                      credit already happened inside the matching engine, in the same transaction that closed the
                      level. This queue is only the on-chain delivery leg, so an empty treasury <i>delays</i> a
                      transfer; it cannot reverse, forfeit or duplicate it. Top the treasury up, then press
                      <b>Retry all</b> and the next cron run drains the queue oldest-first.
                    </div>
                  </div>
                </div>

                <!-- Filter -->
                <div class="d-flex align-items-center gap-2 mb-4">
                  <?php $statuses = ['' => 'All', 'PENDING' => 'Pending', 'PROCESSING' => 'Processing', 'CONFIRMED' => 'Confirmed', 'RETRY' => 'Retry', 'FAILED' => 'Failed'];
                  foreach ($statuses as $val => $label):
                    $active = ($status_filter ?: '') === $val; ?>
                    <a href="<?php echo base_url('admin/staking/payout-queue') . ($val ? '?status='.$val : ''); ?>"
                       class="btn btn-sm <?php echo $active ? 'btn-primary' : 'btn-light'; ?>"><?php echo $label; ?></a>
                  <?php endforeach; ?>
                </div>

                <!-- Queue table -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Payouts (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>User</th><th class="text-center">Level</th><th class="text-end">Amount</th>
                          <th>Treasury → Member</th>
                          <th>Status</th><th>Tx Hash</th><th class="text-end">Confirmations</th>
                          <th class="text-end">Retries</th><th>Last Error</th><th>Last Attempt</th><th>Actions</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($rows)): ?>
                          <tr><td colspan="11" class="text-center text-muted py-6">No payouts yet.</td></tr>
                        <?php else: foreach ($rows as $r):
                          $badge = ['PENDING'=>'light','PROCESSING'=>'info','CONFIRMED'=>'success','FAILED'=>'danger','RETRY'=>'warning'][$r['status']] ?? 'light';
                          $canRetry = in_array($r['status'], ['FAILED', 'RETRY'], true);
                        ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$r['id']; ?></td>
                            <td>
                              <div class="d-flex align-items-center">
                                <?php
                                  // profile_img is the current upload field; image is the older one.
                                  // Both live under assets/images/ (same convention as Profile /
                                  // Genealogy). Falls back to an initial when neither is set, and
                                  // onerror covers a row whose file has since been deleted.
                                  $img = trim((string)($r['profile_img'] ?? '')) ?: trim((string)($r['image'] ?? ''));
                                  $initial = html_escape(strtoupper(substr((string)($r['username'] ?: 'U'), 0, 1)));
                                ?>
                                <div class="symbol symbol-35px me-3">
                                  <?php if ($img !== ''): ?>
                                    <img src="<?php echo base_url('assets/images/') . rawurlencode($img); ?>" alt=""
                                         class="rounded-circle" style="object-fit:cover;"
                                         onerror="this.outerHTML='<span class=&quot;symbol-label bg-light-primary text-primary fw-bold&quot;><?php echo $initial; ?></span>';">
                                  <?php else: ?>
                                    <span class="symbol-label bg-light-primary text-primary fw-bold"><?php echo $initial; ?></span>
                                  <?php endif; ?>
                                </div>
                                <div>
                                  <?php echo html_escape(($r['username'] ?? '') ?: ('#'.$r['user_id'])); ?>
                                  <div class="text-muted fs-8"><?php echo html_escape($r['referral_id'] ?? ''); ?></div>
                                </div>
                              </div>
                            </td>
                            <td class="text-center">
                              <?php if ($r['match_level'] !== null): ?>
                                <span class="badge badge-light-primary">L<?php echo (int)$r['match_level']; ?></span>
                              <?php else: ?><span class="text-muted fs-8">—</span><?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?php echo number_format((float)$r['amount'], 4); ?> <span class="text-muted fs-8"><?php echo html_escape($r['token']); ?></span>
                              <?php if ($r['earning_amount'] !== null): ?>
                                <div class="text-muted fs-9"><?php echo number_format((float)$r['earning_amount'], 2); ?> earn + <?php echo number_format((float)$r['staking_amount'], 2); ?> stk</div>
                              <?php endif; ?>
                            </td>
                            <td class="fs-9 text-muted">
                              <?php $from = $r['from_address'] ?: ($treasury_wallet ?? ''); ?>
                              <div title="Treasury (source): <?php echo html_escape($from); ?>">
                                <span class="text-warning">TR</span>
                                <?php echo $from ? html_escape(substr($from, 0, 8)).'…' : '<span class="text-muted">treasury</span>'; ?>
                              </div>
                              <div title="Member (destination): <?php echo html_escape($r['to_address']); ?>">
                                ↳ <?php echo html_escape(substr($r['to_address'], 0, 8)).'…'.html_escape(substr($r['to_address'], -4)); ?>
                              </div>
                            </td>
                            <td><span class="badge badge-light-<?php echo $badge; ?>"><?php echo html_escape($r['status']); ?></span></td>
                            <td class="fs-8">
                              <?php if (!empty($r['tx_hash'])): ?>
                                <a href="<?php echo $explorer_url.'/tx/'.$r['tx_hash']; ?>" target="_blank" rel="noopener">
                                  <?php echo html_escape(substr($r['tx_hash'], 0, 10)).'…'; ?></a>
                              <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end fs-8"><?php echo (int)$r['confirmations']; ?> / <?php echo (int)$r['required_confs']; ?></td>
                            <td class="text-end fs-8"><?php echo (int)$r['retry_count']; ?> / <?php echo (int)$r['max_retries']; ?></td>
                            <td class="fs-8 text-danger" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                title="<?php echo html_escape($r['last_error'] ?? ''); ?>"><?php echo html_escape($r['last_error'] ?? ''); ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['last_attempt_at'] ?? ''); ?></td>
                            <td class="text-nowrap">
                              <button class="btn btn-sm btn-light py-1 px-2 fs-9 pq-detail" data-id="<?php echo (int)$r['id']; ?>">History</button>
                              <?php if ($canRetry): ?>
                                <button class="btn btn-sm btn-light-warning py-1 px-2 fs-9 pq-retry" data-id="<?php echo (int)$r['id']; ?>">Retry</button>
                              <?php endif; /* No greyed-out Retry: a CONFIRMED transfer is final and
                                               PENDING/PROCESSING rows are already in the cron's hands. */ ?>
                            </td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Per-payout history drawer -->
  <div class="modal fade" id="pq-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header py-4">
          <h3 class="modal-title fs-5 fw-bold" id="pq-modal-title">Payout History</h3>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="pq-modal-body">Loading…</div>
      </div>
    </div>
  </div>

  <script>
    var PQ_DETAIL_URL_BASE = "<?php echo base_url('admin/staking/payout-queue/detail/'); ?>";
    var PQ_RETRY_URL_BASE = "<?php echo base_url('admin/staking/payout-queue/retry/'); ?>";
    var PQ_TREASURY_URL   = "<?php echo base_url('admin/staking/payout-queue/treasury'); ?>";
    var PQ_RETRY_ALL_URL  = "<?php echo base_url('admin/staking/payout-queue/retry-all'); ?>";
  </script>
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
    (function () {
      function post(url, done) {
        fetch(url, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(done).catch(function () { done({ status: 'error', message: 'Network error' }); });
      }
      document.querySelectorAll('.pq-retry').forEach(function (b) {
        b.addEventListener('click', function () {
          var id = this.dataset.id;
          post(PQ_RETRY_URL_BASE + id, function (r) {
            if (window.Swal) Swal.fire(r.status === 'success' ? 'Queued for Retry' : 'Error', r.message || '', r.status === 'success' ? 'success' : 'error');
            if (r.status === 'success') setTimeout(function () { location.reload(); }, 900);
          });
        });
      });

      /* ---------------- per-payout history drawer ---------------- */
      document.querySelectorAll('.pq-detail').forEach(function (b) {
        b.addEventListener('click', function () {
          var mb = document.getElementById('pq-modal-body');
          var mt = document.getElementById('pq-modal-title');
          mb.innerHTML = 'Loading…'; mt.textContent = 'Payout History';
          if (window.bootstrap) new bootstrap.Modal(document.getElementById('pq-modal')).show();
          fetch(PQ_DETAIL_URL_BASE + this.dataset.id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
          }).then(function (r) { return r.json(); }).then(function (j) {
            if (j.status !== 'success') { mb.innerHTML = '<span class="text-danger">' + esc(j.message || 'Not found') + '</span>'; return; }
            var p = j.payout, pc = p.precheck || {}, g = p.gas || {}, mp = p.payout;
            mt.textContent = 'Payout #' + p.id + ' — ' + (p.username || ('#' + p.user_id));

            function row(k, v, cls) {
              return '<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">' + k +
                     '</span><span class="fw-bold ' + (cls || '') + '">' + v + '</span></div>';
            }
            function head(t) { return '<div class="text-gray-500 fw-bold fs-8 text-uppercase mt-4 mb-2">' + t + '</div>'; }

            var h = head('Transfer — Treasury to Member');
            h += row('From (admin treasury)', '<span class="font-monospace fs-8">' + esc(p.from_address || p.treasury_wallet || 'resolved at send') + '</span>');
            h += row('To (member wallet)', '<span class="font-monospace fs-8">' + esc(p.to_address) + '</span>');
            h += row('Amount', n(p.amount, 4) + ' ' + esc(p.token));
            h += row('Payout reference', '<span class="font-monospace fs-8">' + esc(p.payout_ref) + '</span>');
            h += row('Status', '<span class="badge badge-light-info">' + esc(p.status) + '</span>');

            if (mp) {
              h += head('Binary matching level being settled');
              // Legacy carry-engine rows carry no level — 'L' + null renders "L".
              h += row('Level', mp.level === null ? 'legacy (pre level-wise engine)' : ('L' + mp.level));
              h += row('Matched volume', n(mp.matched_volume, 4));
              h += row('Raw bonus', n(mp.raw_bonus, 4));
              h += row('Ceiling applied', n(mp.ceiling_applied, 4));
              h += row('Earning + Staking', n(mp.earning_amount, 4) + ' + ' + n(mp.staking_amount, 4), 'text-success');
              h += row('Admin overflow', n(mp.admin_overflow, 4), parseFloat(mp.admin_overflow) > 0 ? 'text-warning' : 'text-muted');
              h += row('Run ref', '<span class="font-monospace fs-8">' + esc(mp.run_ref || '') + '</span>');
              h += '<div class="alert alert-secondary py-2 mt-2 mb-0 fs-8">The member\'s wallet was already credited when this level closed. This row is only the on-chain transfer of the same amount out of the treasury.</div>';
            }

            h += head('Delivery attempts');
            h += row('Retries used', p.retry_count + ' / ' + p.max_retries);
            h += row('Last attempt', esc(p.last_attempt_at || '—'));
            if (p.last_error) h += '<div class="alert alert-danger py-2 mt-2 fs-8">' + esc(p.last_error) + '</div>';

            if (pc && Object.keys(pc).length) {
              h += head('Treasury balance at last attempt');
              if (pc.dry_run) {
                h += '<div class="alert alert-info py-2 fs-8">Dry run — nothing was broadcast.</div>';
              } else {
                h += row('Treasury BMAN', pc.treasury_bman_balance !== undefined ? n(pc.treasury_bman_balance, 4) : '—');
                h += row('Treasury BNB (gas)', pc.treasury_bnb_balance !== undefined ? n(pc.treasury_bnb_balance, 6) : '—');
                h += row('Needed', (pc.amount_needed_bman !== undefined ? n(pc.amount_needed_bman, 4) + ' BMAN' : '—') +
                                   (pc.gas_needed_bnb !== undefined ? ' + ' + n(pc.gas_needed_bnb, 6) + ' BNB' : ''));
                if (pc.result) h += row('Precheck result', esc(pc.result), pc.result === 'ok' ? 'text-success' : 'text-danger');
                if (pc.rpc_ok === false) h += '<div class="alert alert-warning py-2 mt-2 fs-8">RPC was unreachable at that attempt — the balance above is unknown, not zero.</div>';
              }
              h += '<div class="text-muted fs-8 mt-1">Recorded by the cron at the moment it tried, which is why a held row can explain its own shortfall.</div>';
            }

            h += head('On-chain');
            if (p.tx_hash) {
              h += row('Tx hash', '<a href="' + p.explorer_url + '/tx/' + esc(p.tx_hash) + '" target="_blank" rel="noopener" class="fs-8 font-monospace">' + esc(String(p.tx_hash).slice(0, 20)) + '…</a>');
              h += row('Confirmations', p.confirmations + ' / ' + p.required_confs);
              h += row('Gas used', g.gas_used ? n(g.gas_used, 0) : '<span class="text-muted">not yet synced</span>');
              h += row('Gas fee', g.gas_fee_total ? n(g.gas_fee_total, 8) + ' BNB' : '<span class="text-muted">not yet synced</span>');
              if (g.block_number) h += row('Block', n(g.block_number, 0));
            } else {
              h += '<div class="text-muted fs-7">Not broadcast yet — no transaction hash.</div>';
            }
            mb.innerHTML = h;
          }).catch(function () { mb.innerHTML = '<span class="text-danger">Network error.</span>'; });
        });
      });

      /* ---------------- treasury funding panel ---------------- */
      var body = document.getElementById('pq-treasury-body');

      function n(v, d) { return (v === null || v === undefined) ? '—' : Number(v).toLocaleString(undefined, { maximumFractionDigits: d || 4 }); }
      function esc(s) { var e = document.createElement('div'); e.textContent = s == null ? '' : s; return e.innerHTML; }

      function tile(label, value, tone) {
        return '<div class="col-md-3 mb-4"><div class="bg-light-' + (tone || 'secondary') +
               ' rounded p-4 h-100"><div class="text-gray-600 fw-semibold fs-8 text-uppercase">' + label +
               '</div><div class="fw-bold fs-4 mt-1">' + value + '</div></div></div>';
      }

      function renderTreasury(t) {
        if (!t) { body.innerHTML = '<span class="text-danger">Could not read treasury status.</span>'; return; }

        // rpc_ok === false means the node did not answer. Show "unknown", never
        // a 0 — a fake zero reads as "treasury empty" and invites a wrong call.
        var unknown = (t.rpc_ok === false);
        var bnb  = unknown ? '<span class="text-muted">unknown</span>' : n(t.bnb_balance, 6);
        var bman = unknown ? '<span class="text-muted">unknown</span>' : n(t.bman_balance, 4);

        var fully = t.queued_count > 0 && t.covers_count === t.queued_count;
        var tone  = t.queued_count === 0 ? 'success' : (fully ? 'success' : 'danger');

        var html = '<div class="row">';
        html += tile('Treasury BMAN', bman, unknown ? 'warning' : 'primary');
        html += tile('Treasury BNB (gas)', bnb, unknown ? 'warning' : 'info');
        html += tile('Queued to send', n(t.outstanding_bman, 4) + ' <span class="fs-8 text-muted">BMAN in ' + t.queued_count + '</span>', 'secondary');
        html += tile('Covered right now', t.queued_count === 0 ? '—' : (t.covers_count + ' / ' + t.queued_count), tone);
        html += '</div>';

        if (t.queued_count === 0) {
          html += '<div class="alert alert-success py-3 mb-0 fs-7">Nothing waiting to broadcast.</div>';
        } else if (fully) {
          html += '<div class="alert alert-success py-3 mb-0 fs-7">Treasury covers the whole queue (needs ~' +
                  n(t.gas_needed_bnb, 6) + ' BNB gas for ' + t.queued_count + ' transfer(s)).</div>';
        } else {
          html += '<div class="alert alert-danger py-3 mb-0 fs-7"><b>Top up required.</b> ' + esc(t.blocked_reason || '') +
                  '<div class="mt-2">Add at least <b>' + n(t.shortfall_bman, 4) + ' BMAN</b> and <b>' +
                  n(t.shortfall_bnb, 6) + ' BNB</b> to clear the next payout. Full queue needs ~' +
                  n(t.outstanding_bman, 4) + ' BMAN + ' + n(t.gas_needed_bnb, 6) + ' BNB.</div></div>';
        }

        if (t.blocked_reason && (t.queued_count === 0 || fully)) {
          html += '<div class="alert alert-warning py-3 mt-3 mb-0 fs-7">' + esc(t.blocked_reason) + '</div>';
        }
        if (t.dry_run) {
          html += '<div class="alert alert-info py-3 mt-3 mb-0 fs-7">DRY RUN is on (token settings <code>swap_dry_run=1</code>) — payouts are simulated, nothing leaves the treasury.</div>';
        }
        html += '<div class="fs-8 text-muted mt-3">Treasury: <span class="font-monospace">' + esc(t.treasury_address || 'not configured') +
                '</span> · gas per transfer ≈ ' + n(t.gas_per_send_bnb, 6) + ' BNB</div>';
        body.innerHTML = html;
      }

      function loadTreasury() {
        body.innerHTML = 'Checking treasury balance…';
        fetch(PQ_TREASURY_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (r) { renderTreasury(r.treasury); })
          .catch(function () { body.innerHTML = '<span class="text-danger">Network error reading treasury status.</span>'; });
      }

      document.getElementById('pq-treasury-refresh').addEventListener('click', loadTreasury);

      document.getElementById('pq-retry-all').addEventListener('click', function () {
        var btn = this;
        function go() {
          btn.disabled = true; btn.textContent = 'Queueing…';
          post(PQ_RETRY_ALL_URL, function (r) {
            if (window.Swal) Swal.fire(r.status === 'success' ? 'Queued for Retry' : 'Error', r.message || '', r.status === 'success' ? 'success' : 'error');
            if (r.status === 'success') setTimeout(function () { location.reload(); }, 900);
            else { btn.disabled = false; btn.textContent = 'Retry all failed / held'; }
          });
        }
        if (window.Swal) {
          Swal.fire({
            title: 'Retry all failed / held payouts?',
            text: 'Resets them to PENDING so the next cron run rebroadcasts. No wallet is credited or debited.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, retry all'
          }).then(function (res) { if (res.isConfirmed) go(); });
        } else if (confirm('Retry all failed / held payouts?')) { go(); }
      });

      loadTreasury();
    })();
  </script>
</body>
</html>
