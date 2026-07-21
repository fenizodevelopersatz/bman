"use strict";

(function () {
  let table;

  const elTable = document.getElementById("kt-contact-request-table");

  function ajaxPost(url, data) {
    const fd = new FormData();
    Object.keys(data || {}).forEach(k => fd.append(k, data[k]));
    return fetch(url, { method: "POST", body: fd }).then(r => r.json());
  }

  function escapeHtml(s) {
    s = String(s ?? "");
    return s.replace(/[&<>"']/g, m => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    }[m]));
  }

  function initTable() {
    table = $(elTable).DataTable({
      processing: true,
      searching: true,
      paging: true,
      info: true,
      order: [],
      ajax: {
        url: base_url + "admin/contact-requests/list",
        type: "GET",
        dataSrc: function (json) {
          return json.data || [];
        }
      }
    });

    const searchInput = document.querySelector('[data-kt-docs-table-filter="search"]');
    if (searchInput) {
      searchInput.addEventListener("keyup", function (e) {
        table.search(e.target.value).draw();
      });
    }

    $(document).on("click", ".btn-view", function () {
      const id = $(this).data("id");
      openReview(id);
    });
  }

  function openReview(id) {
    fetch(base_url + "admin/contact-requests/show/" + encodeURIComponent(id), {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    })
      .then(r => r.json())
      .then(res => {
        if (!res || res.status !== "success") {
          Swal.fire({
            text: (res && res.message) || "Failed to load",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          });
          return;
        }

        const r = res.request || {};
        document.getElementById("contactRowId").value = r.id || "";

        const frozen = Number(r.account_frozen) === 1;
        const userLine = r.user_id
          ? `#${escapeHtml(r.user_id)} — ${escapeHtml(r.username || r.email || "")} `
            + (frozen
              ? '<span class="badge badge-light-danger">Frozen</span>'
              : '<span class="badge badge-light-success">Active</span>')
          : '<span class="text-muted">No matching account</span>';

        const attachment = r.attachment_path
          ? `<a href="${base_url}assets/images/contact/${encodeURIComponent(r.attachment_path)}" target="_blank">View attachment</a>`
          : '<span class="text-muted">—</span>';

        const html = `
          <div class="card border">
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">User</div>
                  <div class="fw-bold">${userLine}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Email</div>
                  <div class="fw-bold">${escapeHtml(r.email || "")}</div>
                </div>

                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Status</div>
                  <div class="fw-bold">${escapeHtml((r.status || "").toUpperCase())}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Submitted</div>
                  <div class="text-muted">${escapeHtml(r.created_at || "—")}</div>
                </div>

                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Attachment</div>
                  <div>${attachment}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Admin Notes (previous)</div>
                  <div class="text-muted">${escapeHtml(r.admin_notes || "—")}</div>
                </div>

                <div class="col-md-12">
                  <div class="fw-bold text-muted mb-1">Message</div>
                  <div class="fw-bold" style="white-space:pre-wrap;">${escapeHtml(r.message || "")}</div>
                </div>
              </div>
            </div>
          </div>
        `;

        document.getElementById("contact-review-content").innerHTML = html;
        document.getElementById("contactDecisionNote").value = "";

        const unlockBtn = document.getElementById("btnUnlockContact");
        unlockBtn.style.display = r.user_id ? "" : "none";

        const modal = new bootstrap.Modal(document.getElementById("kt_modal_contact_review"));
        modal.show();
      });
  }

  function sendDecision(action) {
    const id = document.getElementById("contactRowId")?.value;
    const notes = document.getElementById("contactDecisionNote")?.value;

    if (!id) {
      Swal.fire({
        text: "Missing row id",
        icon: "warning",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-warning" },
      });
      return;
    }

    ajaxPost(base_url + "admin/contact-requests/decision/" + encodeURIComponent(id), { action, notes })
      .then((res) => {
        if (!res || res.status !== "success") {
          Swal.fire({
            text: (res && res.message) || "Failed",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          });
          return;
        }

        Swal.fire({
          text: res.message || "Saved successfully!",
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok, got it!",
          customClass: { confirmButton: "btn btn-primary" },
        }).then(() => {
          const modalEl = document.getElementById("kt_modal_contact_review");
          const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
          if (modal) modal.hide();
          if (table && table.ajax) table.ajax.reload(null, false);
        });
      })
      .catch((err) => {
        Swal.fire({
          text: (err && err.message) || "Request failed",
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Ok, got it!",
          customClass: { confirmButton: "btn btn-primary" },
        });
      });
  }

  function initDecisionButtons() {
    const unlockBtn = document.getElementById("btnUnlockContact");
    const resolveBtn = document.getElementById("btnResolveContact");
    const rejectBtn = document.getElementById("btnRejectContact");

    if (unlockBtn) unlockBtn.addEventListener("click", function () {
      Swal.fire({
        text: "This will immediately unlock the account and let the user log in again. Continue?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, unlock",
        cancelButtonText: "Cancel",
        buttonsStyling: false,
        customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-secondary" },
      }).then((r) => { if (r.isConfirmed) sendDecision("unlock"); });
    });

    if (resolveBtn) resolveBtn.addEventListener("click", function () { sendDecision("resolve"); });
    if (rejectBtn) rejectBtn.addEventListener("click", function () { sendDecision("reject"); });
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!elTable) return;
    initTable();
    initDecisionButtons();
  });
})();
