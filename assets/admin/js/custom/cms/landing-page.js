/* Landing Page Settings — admin behaviour (vanilla JS, no jQuery dep) */
(function () {
    "use strict";

    var BASE = window.LP_BASE || "/";

    function notify(ok, msg) {
        if (window.Swal) {
            Swal.fire({ icon: ok ? "success" : "error", title: msg, timer: ok ? 1600 : 3000, showConfirmButton: !ok });
        } else { alert(msg); }
    }
    var previewTheme = "light";
    function previewUrl() {
        return BASE + "landing?theme=" + previewTheme + "&t=" + Date.now();
    }
    function refreshPreview() {
        var f = document.getElementById("lpPreview");
        if (f) f.src = previewUrl();
        var nt = document.querySelector(".lp-newtab");
        if (nt) nt.href = BASE + "landing?theme=" + previewTheme;
    }
    function post(url, formData) {
        return fetch(url, { method: "POST", body: formData, headers: { "X-Requested-With": "XMLHttpRequest" } })
            .then(function (r) { return r.json(); });
    }

    /* ---------- singleton section forms ---------- */
    document.querySelectorAll(".lp-form").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            fd.append("section", form.dataset.section);
            var btn = form.querySelector("button[type=submit]");
            btn.disabled = true;
            post(BASE + "landing-save-section", fd).then(function (res) {
                notify(res.status, res.message);
                if (res.status) refreshPreview();
            }).catch(function () { notify(false, "Request failed"); })
              .finally(function () { btn.disabled = false; });
        });
    });

    /* ---------- color palette pickers <-> hex/rgba text ---------- */
    document.querySelectorAll(".lp-color-pick").forEach(function (pick) {
        var text = pick.parentElement.querySelector(".lp-color-text");
        if (!text) return;
        pick.addEventListener("input", function () { text.value = pick.value; });
        text.addEventListener("input", function () {
            if (/^#[0-9a-fA-F]{6}$/.test(text.value.trim())) pick.value = text.value.trim();
        });
    });

    /* ---------- live image preview before upload ---------- */
    document.querySelectorAll(".lp-file").forEach(function (inp) {
        inp.addEventListener("change", function () {
            var img = inp.parentElement.querySelector(".lp-preview-target");
            if (img && inp.files[0]) { img.src = URL.createObjectURL(inp.files[0]); img.style.display = ""; }
        });
    });

    /* ---------- repeater add / edit ---------- */
    document.querySelectorAll(".lp-rep-form").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            var rep = form.dataset.rep;
            var fd = new FormData(form);
            // unchecked switches must still post 0
            form.querySelectorAll("input[type=checkbox]").forEach(function (c) {
                if (!c.checked) fd.set(c.name, "0");
            });
            post(BASE + "landing-item-save/" + rep, fd).then(function (res) {
                notify(res.status, res.message);
                if (res.status) { location.reload(); }
            }).catch(function () { notify(false, "Request failed"); });
        });
    });

    // edit -> populate form
    document.querySelectorAll(".lp-edit").forEach(function (b) {
        b.addEventListener("click", function () {
            var row = JSON.parse(b.dataset.row);
            var form = b.closest(".accordion-body").querySelector(".lp-rep-form");
            form.querySelector("[name=id]").value = row.id;
            Object.keys(row).forEach(function (k) {
                var el = form.querySelector("[name=" + k + "]");
                if (!el) return;
                if (el.type === "checkbox") el.checked = (row[k] == 1);
                else if (el.type !== "file") el.value = row[k] == null ? "" : row[k];
            });
            form.scrollIntoView({ behavior: "smooth", block: "center" });
        });
    });

    // clear
    document.querySelectorAll(".lp-rep-reset").forEach(function (b) {
        b.addEventListener("click", function () {
            var form = b.closest(".lp-rep-form");
            form.reset(); form.querySelector("[name=id]").value = "0";
        });
    });

    /* ---------- delete / status / restore (confirm + go) ---------- */
    function confirmGo(sel, msg) {
        document.querySelectorAll(sel).forEach(function (a) {
            a.addEventListener("click", function (e) {
                e.preventDefault();
                var run = function () {
                    post(a.getAttribute("href"), new FormData()).then(function (res) {
                        notify(res.status, res.message); if (res.status) location.reload();
                    });
                };
                if (window.Swal) {
                    Swal.fire({ icon: "warning", title: msg, showCancelButton: true })
                        .then(function (r) { if (r.isConfirmed) run(); });
                } else if (confirm(msg)) { run(); }
            });
        });
    }
    confirmGo(".lp-del", "Delete this item?");
    confirmGo(".lp-restore", "Restore this version? Current content will be replaced.");
    // status toggle = no confirm
    document.querySelectorAll(".lp-toggle").forEach(function (a) {
        a.addEventListener("click", function (e) {
            e.preventDefault();
            post(a.getAttribute("href"), new FormData()).then(function (res) {
                notify(res.status, res.message); if (res.status) location.reload();
            });
        });
    });

    /* ---------- native drag reorder ---------- */
    document.querySelectorAll(".lp-sortable").forEach(function (tbody) {
        var dragEl = null;
        tbody.querySelectorAll("tr").forEach(function (tr) {
            tr.setAttribute("draggable", "true");
            tr.addEventListener("dragstart", function () { dragEl = tr; tr.style.opacity = ".4"; });
            tr.addEventListener("dragend", function () { tr.style.opacity = ""; saveOrder(tbody); });
            tr.addEventListener("dragover", function (e) {
                e.preventDefault();
                var t = e.currentTarget;
                if (t === dragEl) return;
                var rect = t.getBoundingClientRect();
                var after = (e.clientY - rect.top) / rect.height > 0.5;
                tbody.insertBefore(dragEl, after ? t.nextSibling : t);
            });
        });
    });
    function saveOrder(tbody) {
        var rep = tbody.dataset.rep;
        var ids = Array.prototype.map.call(tbody.querySelectorAll("tr"), function (tr) { return tr.dataset.id; });
        var fd = new FormData(); fd.append("order", JSON.stringify(ids));
        post(BASE + "landing-item-reorder/" + rep, fd).then(function (res) { notify(res.status, res.message); });
    }

    /* ---------- device preview ---------- */
    document.querySelectorAll(".lp-device").forEach(function (b) {
        b.addEventListener("click", function () {
            document.querySelectorAll(".lp-device").forEach(function (x) { x.classList.remove("active"); });
            b.classList.add("active");
            var wrap = document.getElementById("lpPreviewWrap");
            wrap.className = "card-body lp-preview-wrap lp-device-" + b.dataset.device;
        });
    });
    var rf = document.querySelector(".lp-refresh");
    if (rf) rf.addEventListener("click", refreshPreview);

    /* ---------- preview light / dark ---------- */
    document.querySelectorAll(".lp-theme").forEach(function (b) {
        b.addEventListener("click", function () {
            document.querySelectorAll(".lp-theme").forEach(function (x) { x.classList.remove("active"); });
            b.classList.add("active");
            previewTheme = b.dataset.theme;
            refreshPreview();
        });
    });
    // initialise the new-tab link
    var nt0 = document.querySelector(".lp-newtab");
    if (nt0) nt0.href = BASE + "landing?theme=" + previewTheme;

    /* ---------- import ---------- */
    var impBtn = document.getElementById("lpImportBtn");
    var impFile = document.getElementById("lpImportFile");
    if (impBtn) impBtn.addEventListener("click", function () { impFile.click(); });
    if (impFile) impFile.addEventListener("change", function () {
        if (!impFile.files[0]) return;
        var fd = new FormData(); fd.append("import_file", impFile.files[0]);
        post(BASE + "landing-import", fd).then(function (res) {
            notify(res.status, res.message); if (res.status) location.reload();
        });
    });

    /* ---------- save version ---------- */
    var sv = document.getElementById("lpSaveVersion");
    if (sv) sv.addEventListener("click", function () {
        var go = function (label) {
            var fd = new FormData(); fd.append("label", label || "");
            post(BASE + "landing-save-version", fd).then(function (res) {
                notify(res.status, res.message); if (res.status) location.reload();
            });
        };
        if (window.Swal) {
            Swal.fire({ title: "Version label", input: "text", inputPlaceholder: "e.g. Before launch", showCancelButton: true })
                .then(function (r) { if (r.isConfirmed) go(r.value); });
        } else { go(prompt("Version label:") || ""); }
    });

})();
