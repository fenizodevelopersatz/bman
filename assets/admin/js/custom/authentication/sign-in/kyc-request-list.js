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

  // DataTable
  const dt = $(tableEl).DataTable({
    ajax: { url: base_url + 'admin/kyc/list', dataSrc: function (json) { setCSRF(json); return json.data || []; } },
    columns: [
      { title: '# / User' },
      { title: 'Email' },
      { title: 'View', orderable: false, searchable: false },
      { title: 'KYC Details', orderable: false }
    ],
    order: [],
    pageLength: 25,
    responsive: true,
    language: { emptyTable: 'No KYC requests in queue.' }
  });

  // top search box
  const searchEl = document.querySelector('[data-kt-docs-table-filter="search"]');
  if (searchEl) {
    searchEl.addEventListener('keyup', () => dt.search(searchEl.value).draw());
  }

  // open modal on View
  $(tableEl).on('click', '.btn-view', async function () {
    const id = this.getAttribute('data-id');
    try {
      const res = await axios.get(base_url + 'admin/kyc/show/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      setCSRF(res.data);
      if (res.data.status !== 'success') return Swal.fire('Error', res.data.message || 'Not found', 'error');
      renderPreview(res.data.kyc, res.data.user);
    } catch (e) {
      Swal.fire('Error', 'Could not load KYC', 'error');
    }
  });

  function badge(status) {
    const map = {
      approved: 'badge-light-success',
      rejected: 'badge-light-danger',
      under_review: 'badge-light-warning',
      resubmitted: 'badge-light-primary',
      pending: 'badge-light-info'
    };
    const cls = map[status] || 'badge-light';
    return `<span class="badge ${cls}">${String(status || '').toUpperCase()}</span>`;
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

  function renderPreview(k, u) {
    const cont = document.getElementById('ai-summary-content');
    const head =
      `<div class="mb-5">
         <div class="fs-4 fw-bold">User #${u.id} — ${escapeHtml(u.name || u.username || '')}</div>
         <div class="text-muted">${escapeHtml(u.email || '')}</div>
         <div class="mt-2">Current user status: ${badge(u.kyc_status || 'none')}</div>
         <div class="mt-1">Application status: ${badge(k.status)}</div>
       </div>`;

    const personal =
      row('Full name', escapeHtml(k.full_name)) +
      row('DOB', escapeHtml(k.dob)) +
      row('Gender', escapeHtml(k.gender)) +
      row('Country (residence)', escapeHtml(k.country_iso2)) +
      row('Nationality', escapeHtml(k.nationality_iso2));

    const address =
      row('Address 1', escapeHtml(k.addr_line1)) +
      row('Address 2', escapeHtml(k.addr_line2)) +
      row('City', escapeHtml(k.addr_city)) +
      row('Region/State', escapeHtml(k.addr_region)) +
      row('Postal', escapeHtml(k.addr_postal));

    const identity =
      row('Document Type', escapeHtml(k.doc_type)) +
      row('Document #', escapeHtml(k.doc_number)) +
      row('Issuing Country', escapeHtml(k.doc_issue_country)) +
      row('Issued', escapeHtml(k.doc_issue_date)) +
      row('Expiry', escapeHtml(k.doc_expiry_date));

    const docs =
      `<div class="row">
        ${docBlock('ID Front', k.doc_front_url)}
        ${docBlock('ID Back', k.doc_back_url)}
        ${docBlock('Selfie', k.selfie_url)}
        ${docBlock('Proof of Address', k.proof_address_url)}
      </div>`;

    const notes =
      `<div class="mb-3">
         <label class="form-label">Reviewer Notes</label>
         <textarea id="adm_notes" class="form-control" rows="3" placeholder="Optional notes...">${escapeHtml(k.review_notes || '')}</textarea>
       </div>
       <div class="mb-5">
         <label class="form-label">Rejection Code (optional)</label>
         <input id="adm_rej" class="form-control" placeholder="e.g. PHOTO_BLURRY">
       </div>`;

    const actions =
      `<div class="d-flex gap-3">
         <button class="btn btn-success" data-action="approved"  data-id="${k.id}">Approve</button>
         <button class="btn btn-warning" data-action="under_review" data-id="${k.id}">Mark Under Review</button>
         <button class="btn btn-danger"  data-action="rejected"  data-id="${k.id}">Reject</button>
       </div>`;

    cont.innerHTML =
      head +
      `<div class="row">
         <div class="col-lg-6">
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Personal</div></div><div class="card-body">${personal}</div></div>
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Address</div></div><div class="card-body">${address}</div></div>
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Identity</div></div><div class="card-body">${identity}</div></div>
         </div>
         <div class="col-lg-6">
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Documents</div></div><div class="card-body">${docs}</div></div>
           <div class="card card-bordered mb-5"><div class="card-header"><div class="card-title">Review</div></div><div class="card-body">${notes}${actions}</div></div>
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

        const status = btn.getAttribute("data-action");
        const id = btn.getAttribute("data-id");

        if (!id || !status) {
          Swal.fire({
            text: "Missing id or action",
            icon: "warning",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-warning" },
          });
          return;
        }

        const fd = new FormData();
        fd.append("status", status);
        fd.append("notes", document.getElementById("adm_notes")?.value || "");
        fd.append("rejection_code", document.getElementById("adm_rej")?.value || "");
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
