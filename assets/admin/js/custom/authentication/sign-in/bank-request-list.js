"use strict";

(function () {
  let table;
  let csrfName = null;
  let csrfHash = null;

  const elTable = document.getElementById("kt-bank-verify-table");

  function setCsrf(csrf) {
    if (!csrf) return;
    csrfName = csrf.name;
    csrfHash = csrf.hash;
  }

  function ajaxPost(url, data) {
    const fd = new FormData();
    Object.keys(data || {}).forEach(k => fd.append(k, data[k]));
    if (csrfName && csrfHash) fd.append(csrfName, csrfHash);

    return fetch(url, { method: "POST", body: fd })
      .then(r => r.json());
  }

  function initTable() {
    table = $(elTable).DataTable({
      processing: true,
      searching: true,
      paging: true,
      info: true,
      ajax: {
        url: base_url + "admin/AdminBankVerification/list",
        type: "GET",
        dataSrc: function (json) {
          setCsrf(json.csrf);
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
    fetch(base_url + "admin/bank-verification/show/" + encodeURIComponent(id), {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    })
      .then(r => r.json())
      .then(res => {
        if (!res || res.status !== "success") {
          alert(res.message || "Failed to load");
          return;
        }
        setCsrf(res.csrf);

        const b = res.bank || {};
        document.getElementById("bankRowId").value = b.id || "";

        const html = `
          <div class="card border">
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">User</div>
                  <div class="fw-bold">#${escapeHtml(b.user_id)} — ${escapeHtml(b.username || b.email || "")}</div>
                  <div class="text-muted">${escapeHtml(b.email || "")}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Current Status</div>
                  <div class="fw-bold">${escapeHtml((b.status || "").toUpperCase())}</div>
                  <div class="text-muted">Submitted: ${escapeHtml(b.submitted_at || b.created_at || "—")}</div>
                </div>

                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Holder Name</div>
                  <div class="fw-bold">${escapeHtml(b.holder_name || "—")}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Bank Name</div>
                  <div class="fw-bold">${escapeHtml(b.bank_name || "—")}</div>
                </div>

                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Account Number</div>
                  <div class="fw-bold">${escapeHtml(b.account_number || "—")}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">IFSC</div>
                  <div class="fw-bold">${escapeHtml(b.ifsc || "—")}</div>
                </div>

                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">UPI ID</div>
                  <div class="fw-bold">${escapeHtml(b.upi_id || "—")}</div>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold text-muted mb-1">Previous Note</div>
                  <div class="text-muted">${escapeHtml(b.note || "—")}</div>
                </div>
              </div>
            </div>
          </div>
        `;

        document.getElementById("bank-review-content").innerHTML = html;
        document.getElementById("bankDecisionStatus").value = (b.status || "under_review");
        document.getElementById("bankDecisionNote").value = "";

        const modal = new bootstrap.Modal(document.getElementById("kt_modal_bank_review"));
        modal.show();
      });
  }

  // function initDecision() {
  //   const btn = document.getElementById("btnSaveBankDecision");
  //   if (!btn) return;

  //   btn.addEventListener("click", function () {
  //     const id = document.getElementById("bankRowId").value;
  //     const status = document.getElementById("bankDecisionStatus").value;
  //     const notes = document.getElementById("bankDecisionNote").value;

  //     if (!id) return alert("Missing row id");

  //     ajaxPost(base_url + "admin/bank-verification/decision/" + encodeURIComponent(id), {
  //       status: status,
  //       notes: notes
  //     }).then(res => {
  //       setCsrf(res.csrf);

  //       if (!res || res.status !== "success") {
  //         alert(res.message || "Failed");
  //         return;
  //       }

  //       // close modal
  //       const modalEl = document.getElementById("kt_modal_bank_review");
  //       const modal = bootstrap.Modal.getInstance(modalEl);
  //       if (modal) modal.hide();

  //       // reload table
  //       if (table) table.ajax.reload(null, false);
  //     });
  //   });
  // }

  function initDecision() {
    const btn = document.getElementById("btnSaveBankDecision");
    if (!btn) return;

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

    btn.addEventListener("click", function () {
      // ✅ DEMO MODE STOP
      if (isDemoMode()) {
        demoBlockAlert("You Can not approve/reject bank verification in demo mode.");
        return;
      }

      const id = document.getElementById("bankRowId")?.value;
      const status = document.getElementById("bankDecisionStatus")?.value;
      const notes = document.getElementById("bankDecisionNote")?.value;

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

      // optional: button loading indicator
      btn.disabled = true;
      btn.setAttribute("data-kt-indicator", "on");

      ajaxPost(
        base_url + "admin/bank-verification/decision/" + encodeURIComponent(id),
        { status, notes }
      )
        .then((res) => {
          if (res?.csrf) setCsrf(res.csrf);

          if (!res || res.status !== "success") {
            Swal.fire({
              text: res?.message || "Failed",
              icon: "error",
              buttonsStyling: false,
              confirmButtonText: "Ok, got it!",
              customClass: { confirmButton: "btn btn-primary" },
            });
            return;
          }

          Swal.fire({
            text: res.message || "Decision saved successfully!",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          }).then(() => {
            // close modal
            const modalEl = document.getElementById("kt_modal_bank_review");
            const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
            if (modal) modal.hide();

            // reload table
            if (window.table && window.table.ajax) {
              window.table.ajax.reload(null, false);
            } else if (typeof table !== "undefined" && table?.ajax) {
              table.ajax.reload(null, false);
            }
          });
        })
        .catch((err) => {
          Swal.fire({
            text: err?.message || "Request failed",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
          });
        })
        .finally(() => {
          btn.removeAttribute("data-kt-indicator");
          btn.disabled = false;
        });
    });
  }


  function escapeHtml(s) {
    s = String(s ?? "");
    return s.replace(/[&<>"']/g, m => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    }[m]));
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!elTable) return;
    initTable();
    initDecision();
  });
})();
