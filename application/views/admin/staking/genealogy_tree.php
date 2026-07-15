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

                <div class="alert alert-secondary d-flex align-items-center mb-4">
                  <i class="ki-outline ki-information fs-2 me-3"></i>
                  <div class="fw-semibold">Every figure here is what the matching engine itself reads — <code>binary_carry.left_carry</code>/<code>right_carry</code> (not the member-facing tree's Exchange Wallet subtree totals, which are a <b>different, disconnected</b> figure — see docs/17). "Potential Match" = min(left,right); a node only actually gets paid if <b>Matching Eligible</b> is true (has an active stake of their own) — see the Level Cascade test for why an ineligible node's carry is still preserved, not lost.</div>
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
  <?php $this->load->view('admin/Layout/common_script'); ?>
  <script>
  (function () {
    const TREE_URL = "<?php echo base_url('admin/staking/genealogy-tree/tree-json'); ?>";
    const SEARCH_URL = "<?php echo base_url('admin/staking/genealogy-tree/search-users'); ?>";
    let currentId = <?php echo (int)$start_id; ?>;

    function fmt(n) { n = parseFloat(n) || 0; return n.toLocaleString(undefined, { maximumFractionDigits: 4 }); }
    function esc(s) { const d = document.createElement('div'); d.innerText = (s === undefined || s === null) ? '' : s; return d.innerHTML; }

    function cardHtml(n, isRoot) {
      if (!n || !n.id) return '';
      const eligible = !!n.matching_eligible;
      const held = parseFloat(n.ceiling_wallet_held || 0);
      return `<div class="gt-card ${isRoot ? 'gt-root' : ''}" onclick="gtSelect(${n.id}, '${esc(n.name)}', '${esc(n.uid)}')">
        <div class="gt-name">${esc(n.name)} <span class="badge badge-light-${n.status === 'ACTIVE' ? 'success' : 'danger'} fs-9 ms-1">${esc(n.status)}</span></div>
        <div class="gt-uid">${esc(n.uid)}</div>
        <div class="gt-row"><span>Own Stake</span><b>${fmt(n.own_stake_amount)}</b></div>
        <div class="gt-row"><span>Left / Right Carry</span><b>${fmt(n.left_carry)} / ${fmt(n.right_carry)}</b></div>
        <div class="gt-row"><span>Potential Match</span><b>${fmt(n.potential_match)}</b></div>
        <div class="gt-row"><span>Ceiling (remain/total)</span><b>${fmt(n.ceiling_remaining)} / ${fmt(n.ceiling_amount)}</b></div>
        <div class="mt-2">
          <span class="badge badge-light-${eligible ? 'success' : 'warning'} fs-9">${eligible ? 'Eligible' : 'Needs Stake'}</span>
          ${held > 0 ? `<span class="badge badge-light-info fs-9 ms-1">Held ${fmt(held)}</span>` : ''}
        </div>
      </div>`;
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
      try {
        const res = await fetch(TREE_URL + '?root_id=' + currentId + '&depth=' + depth, { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status !== true || !json.data) {
          canvas.innerHTML = '<div class="text-center text-danger py-10">' + esc(json.message || 'Failed to load tree') + '</div>';
          return;
        }
        currentParent = json.parent || null;
        const upBtn = document.getElementById('gt-up');
        upBtn.disabled = !currentParent;
        upBtn.title = currentParent ? ('Go up to ' + currentParent.name) : 'Already at the top of the tree';
        canvas.innerHTML = '<ul class="otree"><li>' + buildList(json.data, true) + '</li></ul>';
      } catch (e) {
        canvas.innerHTML = '<div class="text-center text-danger py-10">Network error loading tree.</div>';
      }
    }

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
  })();
  </script>
</body>
</html>
