<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<style>
  /* Pure-CSS org-chart connectors (standard recursive technique) — a binary
     tree only ever has 0-2 <li> per <ul>, so the classic first/last-child
     border trick lines up cleanly without needing a canvas/zoom/pan engine. */
  .otree, .otree ul { list-style: none; margin: 0; padding: 0; text-align: center; }
  .otree { padding-top: 8px; }
  .otree ul { display: flex; padding-top: 28px; position: relative; }
  .otree li { display: flex; flex-direction: column; align-items: center; padding: 28px 10px 0 10px; position: relative; }
  .otree li::before, .otree li::after {
    content: ''; position: absolute; top: 0; right: 50%;
    border-top: 2px solid var(--bs-gray-400, #b5b5c3); width: 50%; height: 28px;
  }
  .otree li::after { right: auto; left: 50%; border-left: 2px solid var(--bs-gray-400, #b5b5c3); }
  .otree li:only-child::after, .otree li:only-child::before { display: none; }
  .otree li:only-child { padding-top: 0; }
  .otree li:first-child::before, .otree li:last-child::after { border: 0 none; }
  .otree li:last-child::before { border-right: 2px solid var(--bs-gray-400, #b5b5c3); border-radius: 0 6px 0 0; }
  .otree li:first-child::after { border-radius: 6px 0 0 0; }
  .otree > ul > li { padding-top: 0; }
  .otree > ul > li::before, .otree > ul > li::after { display: none; }

  .gt-card {
    display: inline-block; min-width: 200px; background: var(--bs-body-bg, #fff);
    border: 1px solid #e4e6ef; border-radius: 12px; padding: 10px 12px; text-align: left;
    box-shadow: 0 2px 8px rgba(0,0,0,.04); cursor: pointer;
  }
  .gt-card:hover { border-color: #7239ea; }
  .gt-card.gt-root { border-color: #7239ea; border-width: 2px; }
  .gt-name { font-weight: 700; font-size: .85rem; }
  .gt-uid { font-size: .68rem; color: #99a1b7; }
  .gt-row { display: flex; justify-content: space-between; font-size: .68rem; color: #78829d; margin-top: 4px; }
  .gt-row b { color: #1e2129; }
  .gt-more { border: 1px dashed #b5b5c3; background: transparent; color: #78829d; font-size: .72rem; }
  .gt-search-results {
    position: absolute; z-index: 20; background: #fff; border: 1px solid #e4e6ef; border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.08); width: 320px; max-height: 260px; overflow-y: auto; display: none;
  }
  .gt-search-results .item { padding: .5rem .75rem; font-size: .8rem; cursor: pointer; }
  .gt-search-results .item:hover { background: #f5f8fa; }
  .gt-canvas { overflow: auto; padding: 1.5rem; }
  .gt-crumb { cursor: pointer; color: #7239ea; font-size: .82rem; }
  .gt-crumb:hover { text-decoration: underline; }
  .gt-crumb-current { color: #1e2129; font-weight: 700; cursor: default; }
  .gt-crumb-current:hover { text-decoration: none; }
</style>
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
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-3">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Staking</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Genealogy Tree</li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2 position-relative">
                  <input type="text" id="gt-search" class="form-control form-control-sm" style="width:240px"
                         placeholder="Switch member: username, UID, or ID" autocomplete="off">
                  <div id="gt-search-results" class="gt-search-results"></div>
                  <select id="gt-depth" class="form-select form-select-sm w-auto">
                    <?php foreach ([3,4,5,6,7,8] as $d): ?>
                      <option value="<?php echo $d; ?>" <?php echo $d === 6 ? 'selected' : ''; ?>>Depth: <?php echo $d; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button id="gt-refresh" class="btn btn-sm btn-light-primary">Refresh</button>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-4">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-start mb-4">
                  <i class="ki-outline ki-information fs-2 me-3 mt-1"></i>
                  <div class="fw-semibold fs-7">
                    Every figure comes from the live level engine
                    (<code>Binarylevelmatching_model</code>) — the same code that pays. Volumes are
                    <b>cumulative eligible Lock Wallet BMAN, levels 1..N per leg</b>; matured stake never counts.
                    Completed levels are shown <b>as they were paid</b> (from <code>staking_matching_payouts</code>);
                    the current level is a read-only projection through the engine's own
                    <code>projectLevel()</code>. Nothing on this page is calculated in the browser, and nothing here
                    pays, credits or queues anything.
                  </div>
                </div>

                <!-- Matching Summary KPIs -->
                <div class="card mb-4">
                  <div class="card-header pt-5 flex-wrap gap-2">
                    <div class="card-title"><span class="fw-bold fs-5">Matching Summary</span></div>
                    <div class="card-toolbar gap-2 align-items-center">
                      <span class="text-muted fs-8" id="gt-updated">—</span>
                      <select id="gt-auto" class="form-select form-select-sm form-select-solid w-130px" title="Auto refresh (read-only)">
                        <option value="0">Auto: Off</option>
                        <option value="30">Auto: 30 sec</option>
                        <option value="60">Auto: 1 min</option>
                        <option value="300">Auto: 5 min</option>
                      </select>
                      <a id="gt-export" class="btn btn-sm btn-light-primary" href="#">Export CSV</a>
                    </div>
                  </div>
                  <div class="card-body pt-3 pb-6"><div class="row g-3" id="gt-kpis">
                    <div class="col-12 text-muted fs-7">Loading…</div>
                  </div></div>
                </div>

                <!-- Level summary -->
                <div class="card mb-4" id="gt-summary-card">
                  <div class="card-header pt-5 flex-wrap gap-2">
                    <div class="card-title"><span class="fw-bold fs-5" id="gt-summary-title">Binary Matching</span></div>
                    <div class="card-toolbar gap-2">
                      <select id="gt-level" class="form-select form-select-sm form-select-solid w-150px">
                        <option value="0">Current Level</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?php echo $i; ?>">Level <?php echo $i; ?></option><?php endfor; ?>
                      </select>
                      <select id="gt-filter" class="form-select form-select-sm form-select-solid w-170px">
                        <option value="">All nodes</option>
                        <option value="ELIGIBLE">Eligible</option>
                        <option value="NEEDS_STAKE">Needs Stake</option>
                        <option value="PENDING">Pending</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CONFIG_ERROR">Configuration Error</option>
                        <option value="NO_VOLUME">No eligible volume</option>
                      </select>
                      <button id="gt-refresh" class="btn btn-sm btn-light-primary">Refresh Matching Data</button>
                    </div>
                  </div>
                  <div class="card-body pt-3 pb-6">
                    <div id="gt-timeline" class="d-flex align-items-center flex-wrap gap-1 mb-4"></div>
                    <div id="gt-summary-body"><div class="text-muted fs-7">Loading…</div></div>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header pt-6 flex-wrap gap-2">
                    <div class="card-title">
                      <button id="gt-up" class="btn btn-sm btn-light-primary me-3" title="Go to this member's parent" disabled>
                        <i class="ph ph-arrow-up me-1"></i>Up
                      </button>
                      <span class="fw-bold fs-5">Viewing: </span>&nbsp;<span id="gt-current-name" class="fw-bold text-primary"><?php echo html_escape($start_name); ?></span>
                      <span class="text-muted fs-8 ms-2" id="gt-current-uid"><?php echo html_escape($start_uid); ?></span>
                    </div>
                    <div class="card-toolbar">
                      <span class="badge badge-light-success me-1"><i class="ph ph-check-circle me-1"></i>Eligible</span>
                      <span class="badge badge-light-warning"><i class="ph ph-warning-circle me-1"></i>Needs Stake</span>
                    </div>
                  </div>
                  <div class="card-body pt-0 pb-2 border-bottom" id="gt-breadcrumb" style="display:none;"></div>
                  <div class="card-body gt-canvas" id="gt-canvas">
                    <div class="text-center text-muted py-10">Loading tree…</div>
                  </div>
                </div>

              </div>
            </div>
          </div>
          <?php $this->load->view('admin/Layout/admin_footer'); ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Level-by-level matching audit drawer -->
  <div class="modal fade" id="gt-lv-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header py-4">
          <h3 class="modal-title fs-5 fw-bold" id="gt-lv-title">Matching Details</h3>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="gt-lv-body">Loading…</div>
      </div>
    </div>
  </div>

  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
  (function () {
    const TREE_URL = "<?php echo base_url('admin/staking/genealogy-tree/tree-json'); ?>";
    const SEARCH_URL = "<?php echo base_url('admin/staking/genealogy-tree/search-users'); ?>";
    let currentId = <?php echo (int)$start_id; ?>;

    function fmt(n) { n = parseFloat(n) || 0; return n.toLocaleString(undefined, { maximumFractionDigits: 4 }); }
    function esc(s) { const d = document.createElement('div'); d.innerText = (s === undefined || s === null) ? '' : s; return d.innerHTML; }

    // Backend-provided state -> badge. The label/colour mapping lives here;
    // the STATE itself is decided in PHP by the engine, never inferred in JS.
    const GT_STATE = {
      ELIGIBLE:     { cls: 'success', label: 'Eligible' },
      NEEDS_STAKE:  { cls: 'warning', label: 'Needs Stake' },
      PENDING:      { cls: 'warning', label: 'Pending' },
      NO_VOLUME:    { cls: 'secondary', label: 'No Volume' },
      COMPLETED:    { cls: 'primary', label: 'Level Completed' },
      CONFIG_ERROR: { cls: 'danger',  label: 'Config Error' }
    };

    /* Left/right balance bars + a ceiling-usage bar. Purely proportional
       rendering of numbers the backend already computed — no matching math. */
    function volumeBars(n) {
      const l = parseFloat(n.left_volume) || 0, r = parseFloat(n.right_volume) || 0;
      const m = parseFloat(n.matched_volume) || 0;
      const max = Math.max(l, r, 1);
      const bar = (v, cls) => `<div style="height:6px;border-radius:3px;background:var(--bs-gray-200);overflow:hidden;">
          <div class="bg-${cls}" style="height:100%;width:${Math.min(100, v / max * 100).toFixed(1)}%"></div></div>`;
      const ceilAmt = parseFloat(n.ceiling_amount) || 0;
      const used = parseFloat(n.user_payout) || 0;
      const pct = ceilAmt > 0 ? Math.min(100, used / ceilAmt * 100) : 0;
      const ceilBar = (n.ceiling_status === 'ok' && ceilAmt > 0)
        ? `<div class="mt-2">
             <div class="d-flex justify-content-between fs-9 text-muted"><span>Ceiling used</span>
               <span>${fmt(used)} / ${fmt(ceilAmt)} · ${pct.toFixed(0)}%</span></div>
             ${bar(pct / 100 * max, pct >= 100 ? 'warning' : 'success')}
           </div>` : '';
      return `<div class="mt-2">
          <div class="d-flex justify-content-between fs-9 text-muted"><span>Left</span><span>${fmt(l)}</span></div>
          ${bar(l, 'primary')}
          <div class="d-flex justify-content-between fs-9 text-muted mt-1"><span>Right</span><span>${fmt(r)}</span></div>
          ${bar(r, 'info')}
          <div class="fs-9 text-muted mt-1">Matched <b>${fmt(m)}</b>
            · excess L ${fmt(Math.max(0, l - m))} / R ${fmt(Math.max(0, r - m))}</div>
        </div>${ceilBar}`;
    }

    function cardHtml(n, isRoot) {
      if (!n || !n.id) return '';
      const st = GT_STATE[n.node_state] || GT_STATE.PENDING;
      const noStake = n.ceiling_status === 'no_stake';
      const overflow = parseFloat(n.admin_overflow || 0);
      const matured = parseFloat(n.purchased_total || 0) - parseFloat(n.lock_wallet || 0);

      // Never render a meaningless "0 / 0" ceiling for someone with no stake —
      // say what is actually wrong instead.
      const ceilingRow = noStake
        ? `<div class="gt-row"><span>Group Ceiling</span><b class="text-warning">Needs Stake</b></div>`
        : (n.ceiling_status !== 'ok'
            ? `<div class="gt-row"><span>Group Ceiling</span><b class="text-danger">Config Error</b></div>`
            : `<div class="gt-row"><span>Group Ceiling</span><b>${fmt(n.ceiling_amount)}</b></div>`);

      return `<div class="gt-card ${isRoot ? 'gt-root' : ''}" data-state="${esc(n.node_state)}"
                   onclick="gtSelect(${n.id}, '${esc(n.name)}', '${esc(n.uid)}')">
        <div class="gt-name">${esc(n.name)}
          <span class="badge badge-light-${n.status === 'ACTIVE' ? 'success' : 'danger'} fs-9 ms-1">${esc(n.status)}</span></div>
        <div class="gt-uid">${esc(n.uid)}</div>

        <div class="gt-row"><span>Lock Wallet</span><b>${fmt(n.lock_wallet)}</b></div>
        ${matured > 0.00005 ? `<div class="gt-row" title="Purchased Total includes matured staking. Eligible matching volume uses currently eligible Lock Wallet volume only.">
            <span class="text-muted">Matured (excluded)</span><b class="text-muted">${fmt(matured)}</b></div>` : ''}
        <div class="gt-row"><span>Highest Eligible Stake</span><b>${noStake ? '—' : fmt(n.highest_stake)}</b></div>
        ${ceilingRow}
        ${volumeBars(n)}
        <div class="gt-row"><span>Potential Match</span><b>${fmt(n.matched_volume)}</b></div>
        <div class="gt-row"><span>Current Level</span><b>Level ${n.shown_level}${n.shown_level !== n.current_level ? ` <small class="text-muted">(now L${n.current_level})</small>` : ''}</b></div>
        <div class="gt-row"><span>Level Status</span><b>${st.label}</b></div>
        <div class="gt-row"><span>${n.historical ? 'Matching Bonus' : 'Projected Bonus'}</span><b>${fmt(n.raw_bonus)}</b></div>
        <div class="gt-row"><span>Earning Coin</span><b class="text-success">${fmt(n.earning_amount)}</b></div>
        <div class="gt-row"><span>Staking Coin</span><b class="text-info">${fmt(n.staking_amount)}</b></div>
        <div class="gt-row"><span>Admin Overflow</span><b class="${overflow > 0 ? 'text-warning' : 'text-muted'}">${fmt(overflow)}</b></div>

        <div class="mt-2 d-flex align-items-center justify-content-between gap-1">
          <span>
            <span class="badge badge-light-${st.cls} fs-9">${st.label}</span>
            ${!n.historical ? '<span class="badge badge-light fs-9 ms-1" title="Read-only projection — not yet paid">projection</span>' : ''}
          </span>
          <span class="d-flex gap-1">
            <button class="btn btn-sm btn-light-primary py-1 px-2 fs-9"
                    onclick="event.stopPropagation(); gtAudit(${n.id});"
                    title="Why did this member receive / not receive?">🔍 Audit</button>
            <button class="btn btn-sm btn-light py-1 px-2 fs-9"
                    onclick="event.stopPropagation(); gtOpenLevels(${n.id});"
                    title="Level-by-level matching history">Levels</button>
          </span>
        </div>
      </div>`;
    }

    /** Dim nodes that do not match the state filter (never remove them —
     *  the tree must stay structurally intact and readable). */
    function applyFilter() {
      const want = document.getElementById('gt-filter').value;
      document.querySelectorAll('.gt-card[data-state]').forEach(function (el) {
        const hit = !want || el.dataset.state === want;
        el.style.opacity = hit ? '' : '.28';
        el.style.filter = hit ? '' : 'grayscale(1)';
      });
    }

    function renderSummary(s, levelSel) {
      const body = document.getElementById('gt-summary-body');
      const title = document.getElementById('gt-summary-title');
      if (!s) { body.innerHTML = '<div class="text-muted fs-7">No data.</div>'; return; }
      title.textContent = 'Binary Matching — Level ' + s.shown_level + ' · ' + s.username;

      const noStake = s.ceiling_status === 'no_stake';
      const cfgErr = s.ceiling_status !== 'ok' && !noStake;
      const tiles = [
        ['Eligible Left Volume',  fmt(s.left_volume),  'info'],
        ['Eligible Right Volume', fmt(s.right_volume), 'info'],
        ['Matched Volume',        fmt(s.matched_volume), 'primary'],
        ['Matching Rate',         fmt(s.total_percent) + '%', 'secondary'],
        ['Raw Matching Bonus',    fmt(s.raw_bonus), 'primary'],
        ['Highest Eligible Stake', noStake ? '—' : fmt(s.highest_stake), 'secondary'],
        ['Group Ceiling',         noStake ? 'Needs Stake' : (cfgErr ? 'Config Error' : fmt(s.ceiling_used)), cfgErr ? 'danger' : 'secondary'],
        ['User Payout',           fmt(s.user_payout), 'success'],
        ['Earning Coin — ' + fmt(s.earn_share_pct) + '%', fmt(s.earning_amount), 'success'],
        ['Staking Coin — ' + fmt(s.stk_share_pct) + '%', fmt(s.staking_amount), 'info'],
        ['Admin Overflow',        fmt(s.admin_overflow), 'warning'],
        ['Status',                (GT_STATE[s.node_state] || {}).label || s.level_status, 'secondary']
      ];
      let h = '<div class="row g-3">';
      tiles.forEach(function (t) {
        h += `<div class="col-6 col-md-3 col-xl-2"><div class="bg-light-${t[2]} rounded p-3 h-100">
                <div class="text-gray-600 fw-semibold fs-8 text-uppercase">${t[0]}</div>
                <div class="fw-bold fs-5 mt-1">${t[1]}</div></div></div>`;
      });
      h += '</div>';
      if (s.level_reason) h += `<div class="text-muted fs-8 mt-3">${esc(s.level_reason)}</div>`;
      if (cfgErr) h += `<div class="alert alert-danger py-2 mt-3 mb-0 fs-8">${esc(s.ceiling_detail || '')}</div>`;
      if (!s.historical) h += '<div class="alert alert-info py-2 mt-3 mb-0 fs-8">This level has not been paid yet — the figures above are a read-only projection from the engine.</div>';
      body.innerHTML = h;
    }

    function buildList(n, isRoot) {
      if (!n || !n.id) return '';
      let childrenHtml = '';
      const hasLeft = n.left && n.left.id;
      const hasRight = n.right && n.right.id;
      const leftMore = !hasLeft && n.left_has_more;
      const rightMore = !hasRight && n.right_has_more;
      if (hasLeft || hasRight || leftMore || rightMore) {
        childrenHtml = '<ul>';
        childrenHtml += hasLeft ? ('<li>' + buildList(n.left, false) + '</li>') : (leftMore ? `<li><div class="gt-card gt-more" onclick="gtDrill(${n.id})">+ More (left)<br><small>expand</small></div></li>` : '');
        childrenHtml += hasRight ? ('<li>' + buildList(n.right, false) + '</li>') : (rightMore ? `<li><div class="gt-card gt-more" onclick="gtDrill(${n.id})">+ More (right)<br><small>expand</small></div></li>` : '');
        childrenHtml += '</ul>';
      }
      return cardHtml(n, isRoot) + childrenHtml;
    }

    // Drill-down history — the path of nodes visited so far, so the admin can
    // always get back rather than being stuck wherever they last clicked.
    let history = [{ id: currentId, name: <?php echo json_encode($start_name); ?>, uid: <?php echo json_encode($start_uid); ?> }];
    let currentParent = null; // {id,name,uid} of the CURRENT root's own parent, or null at the top of the tree

    function renderBreadcrumb() {
      const bar = document.getElementById('gt-breadcrumb');
      if (history.length <= 1) { bar.style.display = 'none'; bar.innerHTML = ''; return; }
      bar.style.display = 'block';
      bar.innerHTML = history.map((h, i) => {
        const isLast = i === history.length - 1;
        const link = isLast
          ? `<span class="gt-crumb gt-crumb-current">${esc(h.name)}</span>`
          : `<span class="gt-crumb" onclick="gtJumpTo(${i})">${esc(h.name)}</span>`;
        return link + (isLast ? '' : ' <i class="ph ph-caret-right text-muted"></i> ');
      }).join('');
    }

    async function loadTree() {
      const canvas = document.getElementById('gt-canvas');
      canvas.innerHTML = '<div class="text-center text-muted py-10">Loading tree…</div>';
      const depth = document.getElementById('gt-depth').value;
      const level = document.getElementById('gt-level').value;
      document.getElementById('gt-summary-body').innerHTML = '<div class="text-muted fs-7">Loading…</div>';
      try {
        const res = await fetch(TREE_URL + '?root_id=' + currentId + '&depth=' + depth + '&level=' + level,
                                { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status !== true || !json.data) {
          canvas.innerHTML = '<div class="text-center text-danger py-10">' + esc(json.message || 'Failed to load tree') + '</div>';
          document.getElementById('gt-summary-body').innerHTML = '<div class="text-muted fs-7">—</div>';
          return;
        }
        currentParent = json.parent || null;
        const upBtn = document.getElementById('gt-up');
        upBtn.disabled = !currentParent;
        upBtn.title = currentParent ? ('Go up to ' + currentParent.name) : 'Already at the top of the tree';
        canvas.innerHTML = '<ul class="otree"><li>' + buildList(json.data, true) + '</li></ul>';
        renderSummary(json.summary, json.level);
        renderTimeline(json.timeline);
        applyFilter();
      } catch (e) {
        canvas.innerHTML = '<div class="text-center text-danger py-10">Network error loading tree.</div>';
        document.getElementById('gt-summary-body').innerHTML = '<div class="text-danger fs-7">Network error.</div>';
      }
    }

    document.getElementById('gt-level').addEventListener('change', function () { loadTree(); loadKpis(); });
    document.getElementById('gt-refresh').addEventListener('click', function () { loadTree(); loadKpis(); });
    document.getElementById('gt-filter').addEventListener('change', applyFilter);

    /* ------------------- header KPIs + timeline + refresh ------------------ */
    const KPI_URL   = "<?php echo base_url('admin/staking/genealogy-tree/map-summary'); ?>";
    const AUDIT_URL = "<?php echo base_url('admin/staking/genealogy-tree/audit/'); ?>";
    const CONTRIB_URL = "<?php echo base_url('admin/staking/genealogy-tree/contributors/'); ?>";
    const EXPORT_URL  = "<?php echo base_url('admin/staking/genealogy-tree/export'); ?>";

    function kpiTile(label, value, tone, sub) {
      return `<div class="col-6 col-md-4 col-xl-2"><div class="bg-light-${tone} rounded p-3 h-100">
          <div class="text-gray-600 fw-semibold fs-8 text-uppercase">${label}</div>
          <div class="fw-bold fs-4 mt-1">${value}</div>
          ${sub ? `<div class="text-muted fs-8">${sub}</div>` : ''}</div></div>`;
    }

    function loadKpis() {
      fetch(KPI_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(function (j) {
          if (!j || j.status !== true) return;
          const s = j.summary;
          document.getElementById('gt-kpis').innerHTML =
            kpiTile('Total Members', fmt(s.total_members), 'secondary') +
            kpiTile('Active Members', fmt(s.active_members), 'success') +
            kpiTile('Staked Members', fmt(s.staked_members), 'primary', 'eligible Lock Wallet') +
            kpiTile('Levels Completed', fmt(s.levels_completed), 'info') +
            kpiTile('Total Matched', fmt(s.total_matched), 'info', 'BMAN') +
            kpiTile('User Bonus', fmt(s.user_bonus), 'success', 'BMAN') +
            kpiTile('Admin Overflow', fmt(s.admin_overflow), 'warning', 'BMAN') +
            kpiTile('Pending On-Chain', fmt(s.pending_onchain), s.pending_onchain > 0 ? 'danger' : 'secondary') +
            kpiTile('Config Errors', fmt(s.config_errors), s.config_errors > 0 ? 'danger' : 'secondary',
                    s.config_errors > 0 ? 'blocking payouts' : 'none');
          document.getElementById('gt-updated').textContent = 'Last updated: ' + s.generated_at;
        }).catch(function () {});
    }

    const TL_STATE = { PAID: ['🟢', 'success', 'Paid'], CURRENT: ['🟡', 'warning', 'Current'],
                       CONFIG_ERROR: ['🔴', 'danger', 'Config Error'], NOT_COMPLETED: ['⚪', 'secondary', 'Not completed'] };

    function renderTimeline(tl) {
      const el = document.getElementById('gt-timeline');
      if (!tl || !tl.length) { el.innerHTML = ''; return; }
      const sel = parseInt(document.getElementById('gt-level').value, 10) || 0;
      el.innerHTML = tl.map(function (t, i) {
        const s = TL_STATE[t.status] || TL_STATE.NOT_COMPLETED;
        const active = sel === t.level ? 'border border-primary' : '';
        return (i ? '<span class="text-muted mx-1">───</span>' : '') +
          `<span class="badge badge-light-${s[1]} ${active} cursor-pointer px-3 py-2"
                 title="${s[2]}" onclick="gtPickLevel(${t.level})">${s[0]} L${t.level}</span>`;
      }).join('');
    }

    window.gtPickLevel = function (lvl) {
      document.getElementById('gt-level').value = String(lvl);
      loadTree();
    };

    document.getElementById('gt-export').addEventListener('click', function (e) {
      e.preventDefault();
      const depth = document.getElementById('gt-depth').value;
      const level = document.getElementById('gt-level').value;
      window.location = EXPORT_URL + '?root_id=' + currentId + '&depth=' + depth + '&level=' + level;
    });

    let autoTimer = null;
    document.getElementById('gt-auto').addEventListener('change', function () {
      if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
      const secs = parseInt(this.value, 10) || 0;
      // Read-only refresh: re-fetches the same GET endpoints, nothing more.
      if (secs > 0) autoTimer = setInterval(function () { loadTree(); loadKpis(); }, secs * 1000);
    });

    /* --------------------------- Matching Audit --------------------------- */
    window.gtAudit = function (id) {
      const body = document.getElementById('gt-lv-body');
      const title = document.getElementById('gt-lv-title');
      body.innerHTML = 'Loading…';
      title.textContent = 'Matching Audit';
      if (window.bootstrap) new bootstrap.Modal(document.getElementById('gt-lv-modal')).show();
      const lvl = document.getElementById('gt-level').value;
      fetch(AUDIT_URL + id + '?level=' + lvl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(function (j) {
          if (j.status !== true) { body.innerHTML = '<span class="text-danger">' + esc(j.message || 'Not found') + '</span>'; return; }
          const m = j.member, r = j.result;
          title.textContent = 'Matching Audit — ' + m.name;
          const failed = j.checks.filter(c => !c.pass);

          let h = '';
          if (m.ceiling_status !== 'ok' && m.ceiling_status !== 'no_stake') {
            h += `<div class="alert alert-danger py-3 fs-7"><b>⚠ CONFIGURATION ${esc(m.ceiling_status.replace('config_', '').toUpperCase())}</b>
                    <div class="mt-1">${esc(m.ceiling_detail || '')}</div>
                    <div class="mt-1">Matching payout is <b>blocked</b>. No fallback ceiling is ever substituted, and the level stays open.</div>
                    <a class="btn btn-sm btn-light-danger mt-2" href="<?php echo base_url('admin/staking/rank-power'); ?>">Open Ceiling Settings</a>
                  </div>`;
          }

          h += '<div class="text-gray-500 fw-bold fs-8 text-uppercase mb-2">' +
               (failed.length ? 'Why not fully paid?' : 'Matching Eligibility') + '</div>';
          h += '<div class="mb-4">' + j.checks.map(c =>
                `<div class="d-flex justify-content-between py-2 border-bottom">
                   <span class="fs-7">${c.pass ? '<span class="text-success">✓</span>' : '<span class="text-danger">✕</span>'} ${esc(c.label)}</span>
                   <span class="text-muted fs-8">${esc(c.note || '')}</span></div>`).join('') + '</div>';

          h += '<div class="text-gray-500 fw-bold fs-8 text-uppercase mb-2">Ceiling</div><div class="mb-4">';
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Highest eligible package</span><b>${m.ceiling_status === 'no_stake' ? '—' : fmt(m.highest_stake)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Group ceiling (live config)</span><b>${m.ceiling_status === 'ok' ? fmt(m.ceiling) : esc(m.ceiling_status)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Applied at this level</span><b>${fmt(m.ceiling_used)}</b></div>`;
          if (m.ceiling > 0) {
            h += `<div class="mt-2"><div style="height:8px;border-radius:4px;background:var(--bs-gray-200);overflow:hidden;">
                    <div class="bg-${m.ceiling_pct >= 100 ? 'warning' : 'success'}" style="height:100%;width:${m.ceiling_pct}%"></div></div>
                  <div class="text-muted fs-8 mt-1">Used ${fmt(m.ceiling_used)} · Remaining ${fmt(m.ceiling_remaining)} · ${m.ceiling_pct}%
                  <span class="ms-1">(the ceiling resets every level — this is not a lifetime budget)</span></div></div>`;
          }
          h += '</div>';

          h += `<div class="text-gray-500 fw-bold fs-8 text-uppercase mb-2">Distribution Flow — Level ${j.level}</div>`;
          h += `<div class="bg-light rounded p-3 mb-4 fs-7">
             <div class="d-flex justify-content-between"><span>LEFT LEG</span><b>${fmt(r.left)}</b></div>
             <div class="d-flex justify-content-between"><span>RIGHT LEG</span><b>${fmt(r.right)}</b></div>
             <div class="text-muted fs-8">unmatched — left ${fmt(j.unmatched_left)} · right ${fmt(j.unmatched_right)}</div>
             <div class="text-center text-muted my-1">▼</div>
             <div class="d-flex justify-content-between"><span>MIN (matched)</span><b>${fmt(r.matched)}</b></div>
             <div class="text-center text-muted my-1">▼</div>
             <div class="d-flex justify-content-between"><span>${fmt(j.pct.total)}% raw bonus</span><b>${fmt(r.raw)}</b></div>
             <div class="text-center text-muted my-1">▼</div>
             <div class="d-flex justify-content-between"><span class="ms-3">├─ User</span><b class="text-success">${fmt(r.user)}</b></div>
             <div class="d-flex justify-content-between"><span class="ms-5">├─ Earning ${fmt(j.pct.earning)}%</span><b class="text-success">${fmt(r.earning)}</b></div>
             <div class="d-flex justify-content-between"><span class="ms-5">└─ Staking ${fmt(j.pct.staking)}%</span><b class="text-info">${fmt(r.staking)}</b></div>
             <div class="d-flex justify-content-between"><span class="ms-3">└─ Admin overflow</span><b class="${parseFloat(r.admin) > 0 ? 'text-warning' : 'text-muted'}">${fmt(r.admin)}</b></div>
           </div>`;

          h += '<div class="text-gray-500 fw-bold fs-8 text-uppercase mb-2">Volume &amp; On-Chain</div><div class="mb-4">';
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Lock Wallet (eligible)</span><b>${fmt(m.lock_wallet)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom" title="Purchased Total includes matured staking. Eligible matching volume uses currently eligible Lock Wallet volume only."><span class="text-muted fs-7">Purchased total</span><b>${fmt(m.purchased_total)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Matured (excluded)</span><b class="text-muted">${fmt(m.matured_total)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Level status</span><b>${esc(r.status)}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted fs-7">Internal credit</span><b>${parseFloat(r.user) > 0 ? (r.historical ? '✓ Credited' : 'Not yet — projection') : '—'}</b></div>`;
          h += `<div class="d-flex justify-content-between py-2"><span class="text-muted fs-7">On-chain</span><b>${r.chain_status ? esc(r.chain_status) : (r.historical && parseFloat(r.user) > 0 ? 'Queued' : '—')}</b></div>`;
          h += '</div>';

          h += `<button class="btn btn-sm btn-light-primary" onclick="gtContributors(${id}, ${j.level})">View Contributors</button>
                <div id="gt-contrib" class="mt-3"></div>`;
          if (!r.historical) h += '<div class="alert alert-info py-2 mt-3 mb-0 fs-8">This level is not paid yet — figures are a read-only projection from the engine.</div>';
          body.innerHTML = h;
        })
        .catch(function () { body.innerHTML = '<span class="text-danger">Network error.</span>'; });
    };

    window.gtContributors = function (id, level) {
      const box = document.getElementById('gt-contrib');
      box.innerHTML = 'Loading…';
      fetch(CONTRIB_URL + id + '?level=' + level, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(function (j) {
          if (j.status !== true) { box.innerHTML = '<span class="text-danger">Failed to load.</span>'; return; }
          const c = j.contributors;
          const side = (rows, total, label, tone) =>
            `<div class="col-md-6"><div class="border rounded p-3">
               <div class="fw-bold fs-7 mb-2 text-${tone}">${label}</div>
               ${rows.length ? rows.map(x =>
                 `<div class="d-flex justify-content-between fs-8 py-1 border-bottom">
                    <span class="cursor-pointer text-primary" onclick="gtSelect(${x.user_id}, '${esc(x.name)}', '${esc(x.uid)}')">${esc(x.name)} <span class="text-muted">L${x.depth}</span></span>
                    <b>${fmt(x.volume)}</b></div>`).join('')
                 : '<div class="text-muted fs-8">No members at this depth.</div>'}
               <div class="d-flex justify-content-between fs-7 pt-2 fw-bold"><span>Total</span><span>${fmt(total)}</span></div>
             </div></div>`;
          box.innerHTML = `<div class="row g-3">
              ${side(c.left, c.left_total, 'LEFT LEG', 'primary')}
              ${side(c.right, c.right_total, 'RIGHT LEG', 'info')}
            </div>
            <div class="text-muted fs-8 mt-2">Cumulative levels 1–${c.level}, from the engine's own leg query — the same rows it matched on.</div>`;
        })
        .catch(function () { box.innerHTML = '<span class="text-danger">Network error.</span>'; });
    };

    /* ---------------- level-by-level audit drawer ---------------- */
    const LEVELS_URL = "<?php echo base_url('admin/staking/genealogy-tree/member-levels/'); ?>";

    window.gtOpenLevels = function (id) {
      const body = document.getElementById('gt-lv-body');
      const title = document.getElementById('gt-lv-title');
      body.innerHTML = 'Loading…';
      title.textContent = 'Matching Details';
      if (window.bootstrap) new bootstrap.Modal(document.getElementById('gt-lv-modal')).show();
      fetch(LEVELS_URL + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(function (j) {
          if (j.status !== true) { body.innerHTML = '<span class="text-danger">' + esc(j.message || 'Not found') + '</span>'; return; }
          const m = j.member;
          title.textContent = m.name + ' — Matching Details';
          const noStake = m.ceiling_status === 'no_stake';
          let h = '<div class="row g-3 mb-4">';
          [['User', esc(m.name) + ' <span class="text-muted fs-8">' + esc(m.uid) + '</span>'],
           ['Lock Wallet (eligible)', fmt(m.lock_wallet) + ' BMAN'],
           ['Purchased (incl. matured)', fmt(m.purchased_total) + ' BMAN'],
           ['Highest Eligible Package', noStake ? '—' : fmt(m.highest_stake) + ' BMAN'],
           ['Dynamic Group Ceiling', noStake ? 'Needs Stake' : (m.ceiling_status !== 'ok' ? 'Config Error' : fmt(m.ceiling) + ' BMAN')],
           ['Current Level', 'Level ' + m.current_level]
          ].forEach(function (t) {
            h += `<div class="col-md-4"><div class="bg-light rounded p-3 h-100">
                    <div class="text-gray-600 fw-semibold fs-8 text-uppercase">${t[0]}</div>
                    <div class="fw-bold fs-6 mt-1">${t[1]}</div></div></div>`;
          });
          h += '</div>';
          if (m.ceiling_status !== 'ok' && !noStake) {
            h += `<div class="alert alert-danger py-2 fs-8">${esc(m.ceiling_detail || '')}</div>`;
          }

          h += '<div class="text-gray-500 fw-bold fs-8 text-uppercase mb-2">Level History</div>';
          if (!j.levels.length) { h += '<div class="text-muted fs-7">No levels recorded yet.</div>'; }
          j.levels.forEach(function (l) {
            const done = l.status === 'COMPLETED';
            const badge = done ? 'success' : (l.status === 'CONFIG_ERROR' ? 'danger' : (l.status === 'NOT_ELIGIBLE' ? 'warning' : 'secondary'));
            const mark = done ? '✓' : (l.status === 'CONFIG_ERROR' ? '⚠' : (l.status === 'NOT_ELIGIBLE' ? '—' : '⏳'));
            h += `<div class="border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <b class="fs-6">Level ${l.level}</b>
                <span class="badge badge-light-${badge}">${mark} ${esc(l.status)}</span>
              </div>
              <div class="row fs-7 g-2">
                <div class="col-md-4">Left Volume: <b>${fmt(l.left_volume)}</b></div>
                <div class="col-md-4">Right Volume: <b>${fmt(l.right_volume)}</b></div>
                <div class="col-md-4">Matched: <b>${fmt(l.matched_volume)}</b></div>
                <div class="col-md-4">Raw Bonus: <b>${fmt(l.raw_bonus)}</b></div>
                <div class="col-md-4">Ceiling: <b>${fmt(l.ceiling_used)}</b></div>
                <div class="col-md-4">Paid: <b>${fmt(l.user_payout)}</b></div>
                <div class="col-md-4">Earning: <b class="text-success">${fmt(l.earning_amount)}</b></div>
                <div class="col-md-4">Staking: <b class="text-info">${fmt(l.staking_amount)}</b></div>
                <div class="col-md-4">Admin Overflow: <b class="${parseFloat(l.admin_overflow) > 0 ? 'text-warning' : 'text-muted'}">${fmt(l.admin_overflow)}</b></div>
              </div>
              ${l.completed_at ? `<div class="text-muted fs-8 mt-2">Completed ${esc(l.completed_at)} · ref <span class="font-monospace">${esc(l.run_ref || '')}</span> · payout #${l.payout_id}</div>` : ''}
              ${l.reason ? `<div class="text-muted fs-8 mt-2">${esc(l.reason)}</div>` : ''}
              ${!done ? '<div class="text-muted fs-8 mt-1"><i>Projection — not yet paid.</i></div>' : ''}
            </div>`;
          });
          body.innerHTML = h;
        })
        .catch(function () { body.innerHTML = '<span class="text-danger">Network error.</span>'; });
    };

    /** Re-root the view at a node and record it in the breadcrumb (unless it's the same node we're already on). */
    window.gtSelect = function (id, name, uid) {
      currentId = id;
      const last = history[history.length - 1];
      if (!last || last.id !== id) history.push({ id, name, uid });
      document.getElementById('gt-current-name').innerText = name;
      document.getElementById('gt-current-uid').innerText = uid;
      renderBreadcrumb();
      loadTree();
    };
    window.gtDrill = function (id) { window.gtSelect(id, document.getElementById('gt-current-name').innerText, document.getElementById('gt-current-uid').innerText); };

    /** Breadcrumb click: jump back to that point, discarding everything drilled past it. */
    window.gtJumpTo = function (index) {
      const target = history[index];
      history = history.slice(0, index + 1);
      currentId = target.id;
      document.getElementById('gt-current-name').innerText = target.name;
      document.getElementById('gt-current-uid').innerText = target.uid;
      renderBreadcrumb();
      loadTree();
    };

    document.getElementById('gt-up').addEventListener('click', function () {
      if (currentParent) window.gtSelect(currentParent.id, currentParent.name, currentParent.uid);
    });
    document.getElementById('gt-refresh').addEventListener('click', loadTree);
    document.getElementById('gt-depth').addEventListener('change', loadTree);

    // Switch-member search
    let searchTimer = null;
    const searchBox = document.getElementById('gt-search');
    const resultsBox = document.getElementById('gt-search-results');
    searchBox.addEventListener('input', function () {
      clearTimeout(searchTimer);
      const q = this.value.trim();
      if (q.length < 1) { resultsBox.style.display = 'none'; return; }
      searchTimer = setTimeout(async () => {
        try {
          const res = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
          const json = await res.json();
          const rows = (json.status === true) ? json.data : [];
          if (!rows.length) { resultsBox.innerHTML = '<div class="item text-muted">No matches</div>'; resultsBox.style.display = 'block'; return; }
          resultsBox.innerHTML = rows.map(r => `<div class="item" onclick='gtPick(${r.id}, ${JSON.stringify(r.username || ("#" + r.id))}, ${JSON.stringify(r.referral_id || ("#" + r.id))})'>${esc(r.username || '(no name)')} <span class="text-muted">${esc(r.referral_id || '')}</span></div>`).join('');
          resultsBox.style.display = 'block';
        } catch (e) { /* ignore */ }
      }, 250);
    });
    window.gtPick = function (id, name, uid) {
      resultsBox.style.display = 'none';
      searchBox.value = '';
      history = []; // switching member via search starts a fresh trail, not a continuation of the old one
      gtSelect(id, name, uid);
    };
    document.addEventListener('click', (e) => {
      if (!resultsBox.contains(e.target) && e.target !== searchBox) resultsBox.style.display = 'none';
    });

    loadTree();
    loadKpis();
  })();
  </script>
</body>
</html>
