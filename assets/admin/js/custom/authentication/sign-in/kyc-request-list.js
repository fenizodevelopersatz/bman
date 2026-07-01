/* global axios, Swal, base_url */
(function () {
  const tableEl = document.getElementById('kt-client-follow-table');
  if (!tableEl) return;

  let csrfName = document.querySelector('meta[name="csrf-name"]')?.content || '';
  let csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content || '';

  function setCSRF(data) {
    if (data && data.csrf && data.csrf.hash) {
      csrfHash = data.csrf.hash;
    }
  }
  function withCSRF(fd) { if (!csrfName) csrfName = window.csrfName || '<?= $this->security->get_csrf_token_name(); ?>'; fd.append(csrfName, csrfHash); return fd; }

  // NEW: filter + search controls (applied server-side)
  const statusEl = document.getElementById('kyc-filter-status');
  const docTypeEl = document.getElementById('kyc-filter-doctype');
  const searchEl = document.querySelector('[data-kt-docs-table-filter="search"]');

  // DataTable
  const dt = $(tableEl).DataTable({
    // NEW: send Status / Document Type / search (q) with every request so filtering happens in SQL
    ajax: {
      url: base_url + 'admin/kyc/list',
      data: function (d) {
        d.status = statusEl ? statusEl.value : '';
        d.doc_type = docTypeEl ? docTypeEl.value : '';
        d.q = searchEl ? searchEl.value.trim() : '';
      },
      dataSrc: function (json) { setCSRF(json); return json.data || []; }
    },
    columns: [
      { title: '# / User' },
      { title: 'Email' },
      { title: 'View', orderable: false, searchable: false },
      { title: 'KYC Details', orderable: false }
    ],
    order: [],
    pageLength: 25,
    responsive: true,
    searching: false, // NEW: searching is server-side via the q parameter
    language: { emptyTable: 'No KYC requests found.' }
  });

  // NEW: reload from server when filters change or the user types (debounced)
  let _searchTimer;
  function reloadTable() { if (dt && dt.ajax) dt.ajax.reload(null, false); }
  if (statusEl) statusEl.addEventListener('change', reloadTable);
  if (docTypeEl) docTypeEl.addEventListener('change', reloadTable);
  if (searchEl) searchEl.addEventListener('keyup', () => { clearTimeout(_searchTimer); _searchTimer = setTimeout(reloadTable, 350); });

  // open modal on View
  $(tableEl).on('click', '.btn-view', async function () {
    const id = this.getAttribute('data-id');
    try {
      const res = await axios.get(base_url + 'admin/kyc/show/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      setCSRF(res.data);
      if (res.data.status !== 'success') return Swal.fire('Error', res.data.message || 'Not found', 'error');
      renderPreview(res.data.kyc, res.data.user, res.data.history || []);
    } catch (e) {
      Swal.fire('Error', 'Could not load KYC', 'error');
    }
  });

  // NEW: map the DB status enum to the canonical state-machine name.
  function canonicalState(db) {
    switch (db) {
      case 'pending': return 'PENDING';
      case 'under_review': return 'UNDER_REVIEW';
      case 'approved': return 'APPROVED';
      case 'rejected':
      case 'resubmitted': return 'RESUBMIT_REQUIRED';
      default: return 'NOT_SUBMITTED';
    }
  }

  // Badge shows the canonical state (so admins see RESUBMIT_REQUIRED, not the legacy "rejected").
  function badge(status) {
    const map = {
      approved: 'badge-light-success',
      rejected: 'badge-light-danger',
      resubmitted: 'badge-light-danger',
      under_review: 'badge-light-warning',
      pending: 'badge-light-info'
    };
    const cls = map[status] || 'badge-light';
    return `<span class="badge ${cls}">${canonicalState(status)}</span>`;
  }

  function row(label, value) {
    return `<div class="d-flex justify-content-between py-2 border-bottom">
              <div class="text-muted">${label}</div>
              <div class="fw-semibold text-gray-900 text-end">${value || '-'}</div>
            </div>`;
  }

  function docBlock(label, url) {
    if (!url) return '';
    const safe = url.replace(/"/g, '&quot;');
    return `<div class="col-md-6 mb-4">
              <div class="fw-semibold mb-2">${label}</div>
              <a href="${safe}" target="_blank" class="d-inline-block rounded border">
                <img src="${safe}" class="img-fluid" style="max-height:180px;object-fit:contain" onerror="this.src='${base_url}assets/admin/media/misc/file.jpg'">
              </a>
            </div>`;
  }

  // NEW: map internal doc_type enum values to the spec's display labels.
  function docLabel(t) {
    const map = { national_id: 'Aadhaar Id', driver_license: 'Driving License', passport: 'Passport' };
    return map[t] || String(t || '');
  }

  // NEW: render the KYC status history (reviewer, action, remarks, date) from kyc_audit_logs.
  function renderHistory(list) {
    if (!Array.isArray(list) || !list.length) return '<div class="text-muted">No history yet.</div>';
    return list.map(function (h) {
      return `<div class="d-flex justify-content-between py-2 border-bottom">
                <div>
                  <span class="badge badge-light-primary">${escapeHtml(String(h.action || '').toUpperCase())}</span>
                  ${h.notes ? `<div class="text-muted fs-7 mt-1">${escapeHtml(h.notes)}</div>` : ''}
                </div>
                <div class="text-end text-muted fs-7">${escapeHtml(h.created_at || '')}<br>by #${escapeHtml(h.actor_user_id || '-')}</div>
              </div>`;
    }).join('');
  }

  function renderPreview(k, u, history) {
    const cont = document.getElementById('ai-summary-content');
    const head =
      `<div class="mb-5">
         <div class="fs-4 fw-bold">User #${u.id} — ${escapeHtml(u.name || u.username || '')}</div>
         <div class="text-muted">${escapeHtml(u.email || '')}${u.phone ? ' • ' + escapeHtml(u.phone) : ''}</div>
         <div class="mt-2">Current user status: ${badge(u.kyc_status || 'none')}</div>
         <div class="mt-1">Application status: ${badge(k.status)}</div>
       </div>`;

    // NEW: simplified manual-KYC review — Document Type/Number + the three images.
    const identity =
      row('Document Type', escapeHtml(docLabel(k.doc_type))) +
      row('Document Number', escapeHtml(k.doc_number));

    const docs =
      `<div class="row">
        ${docBlock('Front Image', k.doc_front_url)}
        ${docBlock('Back Image', k.doc_back_url)}
        ${docBlock('Selfie with ID', k.selfie_url)}
      </div>`;

    // NEW: resubmission reason is required only for "Request Resubmission".
    const notes =
      `<div class="mb-4">
         <label class="form-label">Resubmission Reason / Remarks <span class="text-muted">(required to request resubmission)</span></label>
         <textarea id="adm_notes" class="form-control" rows="3" placeholder="Explain what the user must fix...">${escapeHtml(k.review_notes || '')}</textarea>
       </div>`;

    // NEW: state-machine driven action buttons — only the transitions valid from the
    // current state are shown; all transitions are re-validated on the backend.
    const st = canonicalState(k.status);
    let actionsInner = '';
    if (st === 'PENDING') {
      actionsInner = `<button class="btn btn-warning" data-action="start_review" data-id="${k.id}">Start Review</button>`;
    } else if (st === 'UNDER_REVIEW') {
      actionsInner =
        `<button class="btn btn-success" data-action="approve" data-id="${k.id}">Approve</button>
         <button class="btn btn-danger" data-action="request_resubmission" data-id="${k.id}">Request Resubmission</button>`;
    } else if (st === 'APPROVED') {
      actionsInner = `<div class="text-success fw-semibold"><i class="ki-outline ki-check-circle fs-3 me-1"></i>Approved — no further action.</div>`;
    } else if (st === 'RESUBMIT_REQUIRED') {
      actionsInner = `<div class="text-muted">Waiting for the user to resubmit documents.</div>`;
    } else {
      actionsInner = `<div class="text-muted">User has not submitted KYC yet.</div>`;
    }
    const actions = `<div class="d-flex gap-3 flex-wrap">${actionsInner}</div>`;

    const historyHtml = renderHistory(history);

    cont.innerHTML =
      head +
      `<div class="row">
         <div class="col-lg-6">
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Identity</div></div><div class="card-body">${identity}</div></div>
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Documents</div></div><div class="card-body">${docs}</div></div>
         </div>
         <div class="col-lg-6">
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Review Decision</div></div><div class="card-body">${notes}${actions}</div></div>
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Status History</div></div><div class="card-body">${historyHtml}</div></div>
         </div>
       </div>`;

    // wire action buttons
    // cont.querySelectorAll('button[data-action]').forEach(btn => {
    //   btn.addEventListener('click', async () => {
    //     const status = btn.getAttribute('data-action');
    //     const id = btn.getAttribute('data-id');
    //     const fd = new FormData();
    //     fd.append('status', status);
    //     fd.append('notes', document.getElementById('adm_notes')?.value || '');
    //     fd.append('rejection_code', document.getElementById('adm_rej')?.value || '');
    //     withCSRF(fd);

    //     try {
    //       const res = await axios.post(base_url + 'admin/kyc/decision/' + id, fd, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    //       setCSRF(res.data);
    //       if (res.data.status === 'success') {
    //         Swal.fire('Saved', 'Decision recorded', 'success');
    //         $('#kt_modal_view_summary').modal('hide');
    //         dt.ajax.reload(null, false);
    //       } else {
    //         Swal.fire('Error', res.data.message || 'Failed', 'error');
    //       }
    //     } catch (e) {
    //       Swal.fire('Error', 'Unexpected error', 'error');
    //     }
    //   });
    // });

    cont.querySelectorAll('button[data-action]').forEach((btn) => {
      // ---------------------------
      // ✅ DEMO MODE HELPERS (optional)
      // ---------------------------
      function isDemoMode() {
        return !!(
          (window.APP_CONFIG && window.APP_CONFIG.DEMO === true) ||
          window.DEMOVERSION === true
        );
      }

      function demoBlockAlert(msg) {
        Swal.fire({
          icon: "info",
          title: "Demo Version",
          text: msg || "You Can not change record.",
          confirmButtonText: "Ok, got it!",
          customClass: { confirmButton: "btn btn-primary" },
          buttonsStyling: false,
        });
      }

      btn.addEventListener("click", async () => {
        // ✅ DEMO MODE STOP
        if (isDemoMode()) {
          demoBlockAlert("You Can not change KYC decision in demo mode.");
          return;
        }

        const action = btn.getAttribute("data-action");
        const id = btn.getAttribute("data-id");

        if (!id || !action) {
          Swal.fire({
            text: "Missing id or action",
            icon: "warning",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-warning" },
          });
          return;
        }

        // NEW: a resubmission reason is mandatory for "Request Resubmission".
        const notesVal = (document.getElementById("adm_notes")?.value || "").trim();
        if (action === "request_resubmission" && !notesVal) {
          Swal.fire({
            text: "Please enter a resubmission reason first.",
            icon: "warning",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-warning" },
          });
          return;
        }

        const fd = new FormData();
        fd.append("action", action); // NEW: send the state-machine action, validated on the backend
        fd.append("notes", notesVal);
        withCSRF(fd);

        // optional: loading state
        btn.disabled = true;
        btn.setAttribute("data-kt-indicator", "on");

        try {
          const res = await axios.post(
            base_url + "admin/kyc/decision/" + encodeURIComponent(id),
            fd,
            { headers: { "X-Requested-With": "XMLHttpRequest" } }
          );

          // keep csrf fresh
          setCSRF(res.data);

          if (res.data.status === "success") {
            Swal.fire({
              title: "Saved",
              text: "Decision recorded",
              icon: "success",
              buttonsStyling: false,
              confirmButtonText: "Ok, got it!",
              customClass: { confirmButton: "btn btn-primary" },
            }).then(() => {
              $("#kt_modal_view_summary").modal("hide");
              if (dt && dt.ajax) dt.ajax.reload(null, false);
            });
          } else {
            Swal.fire({
              title: "Error",
              text: res.data.message || "Failed",
              icon: "error",
              buttonsStyling: false,
              confirmButtonText: "Ok, got it!",
              customClass: { confirmButton: "btn btn-primary" },
            });
          }
        } catch (e) {
          Swal.fire({
            title: "Error",
            text: e?.response?.data?.message || e?.message || "Unexpected error",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          });
        } finally {
          btn.removeAttribute("data-kt-indicator");
          btn.disabled = false;
        }
      });
    });


    // show modal
    $('#kt_modal_view_summary').modal('show');
  }

  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"'`=\/]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;' }[c];
    });
  }
})();
