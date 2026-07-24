<?php
$intro = 'Upload any image, PDF or document members can download from their '
       . '<b>Rank &amp; Rewards</b> page. Tie a file to a specific rank (only members who hold '
       . 'that rank see it) or leave it on <b>All ranks</b> to show it to everyone.';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte, 'intro' => $intro]);
?>

<style>
  .rf-drop{ border:1px dashed #c9cee0; border-radius:12px; padding:16px; background:#fafbff; }
  .rf-item-row{ border:1px solid #e6e8ef; border-radius:12px; padding:12px 12px 10px; margin-bottom:12px;
    background:#fff; position:relative; }
  .rf-item-row .rf-row-del{ position:absolute; top:8px; right:8px; border:0; background:#fff1f2; color:#e11d48;
    width:26px; height:26px; border-radius:8px; cursor:pointer; font-weight:900; line-height:1; font-size:15px; }
  .rf-row-prev{ margin-top:10px; display:none; }
  .rf-row-prev img{ max-width:220px; max-height:150px; border-radius:8px; border:1px solid #e6e8ef; }
  .rf-row-prev .rf-fileicon{ display:inline-flex; align-items:center; gap:10px; padding:10px 14px;
    border:1px solid #e6e8ef; border-radius:10px; background:#f8fafc; font-weight:700; color:#334155; }
  .rf-thumb{ width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid #e6e8ef; background:#fff; }
  .rf-thumb-icon{ width:54px; height:54px; border-radius:8px; border:1px solid #e6e8ef; background:#f8fafc;
    display:flex; align-items:center; justify-content:center; font-size:22px; color:#64748b; }
</style>

<div class="row g-6 mb-8">
  <!-- Upload form (multiple files, add/remove rows) -->
  <div class="col-lg-5">
    <form id="rfForm" class="rf-drop" onsubmit="return false;">
      <div id="rfRows"></div>

      <button type="button" id="rfAddRow" class="btn btn-sm btn-light-primary">
        <i class="ki-duotone ki-plus fs-6"><span class="path1"></span><span class="path2"></span></i> Add another file
      </button>
      <div class="text-muted fs-8 mt-2">Image, PDF, Word, Excel or PowerPoint. Max 10 MB each.</div>

      <div class="separator my-5"></div>

      <button type="button" id="rfUploadBtn" class="btn btn-sm btn-primary">
        <i class="ki-duotone ki-cloud-add fs-5"><span class="path1"></span><span class="path2"></span></i>
        Upload all
      </button>
    </form>
  </div>

  <!-- Uploaded list -->
  <div class="col-lg-7">
    <div class="table-responsive">
      <table class="table align-middle table-row-dashed fs-7 gy-3" id="rfTable">
        <thead>
          <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
            <th></th><th>Title</th><th>Shows to</th><th>Size</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr id="rfEmpty"><td colspan="5" class="text-center text-muted py-8">No files uploaded yet.</td></tr>
          <?php else: foreach ($rows as $f):
            $url = media_url($f['file_path']);
            $isImg = (int) $f['is_image'] === 1;
          ?>
            <tr data-id="<?php echo (int) $f['id']; ?>">
              <td>
                <?php if ($isImg): ?>
                  <img class="rf-thumb" src="<?php echo html_escape($url); ?>" alt="">
                <?php else: ?>
                  <div class="rf-thumb-icon"><i class="ki-duotone ki-file fs-2"><span class="path1"></span><span class="path2"></span></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-bold text-gray-900"><?php echo html_escape($f['title']); ?></div>
                <div class="text-muted fs-8"><?php echo html_escape($f['file_name']); ?></div>
              </td>
              <td><?php echo $f['rank_name'] ? html_escape($f['rank_name']) : '<span class="badge badge-light-primary">All ranks</span>'; ?></td>
              <td><?php echo $f['file_size'] ? number_format($f['file_size'] / 1024, 0) . ' KB' : '—'; ?></td>
              <td class="text-end">
                <a href="<?php echo html_escape($url); ?>" target="_blank" class="btn btn-sm btn-light-primary">View</a>
                <a href="<?php echo html_escape($url); ?>" download="<?php echo html_escape($f['file_name']); ?>" class="btn btn-sm btn-light">Download</a>
                <button type="button" class="btn btn-sm btn-light-danger rf-del" data-id="<?php echo (int) $f['id']; ?>">Delete</button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  // Rank <option> list, built once server-side and reused for every new row.
  var RANK_OPTIONS = '<option value="">All ranks (everyone)</option>'
    <?php foreach ($ranks as $r): ?>
    + '<option value="<?php echo (int) $r['id']; ?>"><?php echo html_escape(addslashes($r['name'])); ?> only</option>'
    <?php endforeach; ?>;

  var ACCEPT = '.jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
  var rowsWrap = document.getElementById('rfRows');

  function addRow() {
    var row = document.createElement('div');
    row.className = 'rf-item-row';
    row.innerHTML =
      '<button type="button" class="rf-row-del" title="Remove">&times;</button>' +
      '<div class="mb-3"><label class="form-label fw-semibold fs-8">Title <span class="text-danger">*</span></label>' +
        '<input type="text" name="title[]" class="form-control form-control-sm" placeholder="e.g. Gold Rank Certificate"></div>' +
      '<div class="mb-3"><label class="form-label fw-semibold fs-8">Show to</label>' +
        '<select name="rank_id[]" class="form-select form-select-sm">' + RANK_OPTIONS + '</select></div>' +
      '<div><label class="form-label fw-semibold fs-8">File <span class="text-danger">*</span></label>' +
        '<input type="file" name="file[]" class="form-control form-control-sm rf-file" accept="' + ACCEPT + '"></div>' +
      '<div class="rf-row-prev"></div>';
    rowsWrap.appendChild(row);
  }

  // First row on load.
  addRow();
  document.getElementById('rfAddRow').addEventListener('click', addRow);

  // Per-row: remove + file preview (event delegation).
  rowsWrap.addEventListener('click', function (e) {
    var del = e.target.closest('.rf-row-del');
    if (!del) return;
    var rows = rowsWrap.querySelectorAll('.rf-item-row');
    if (rows.length <= 1) { rkToast('Keep at least one row.', false); return; }
    del.closest('.rf-item-row').remove();
  });

  rowsWrap.addEventListener('change', function (e) {
    var input = e.target.closest('.rf-file');
    if (!input) return;
    var row = input.closest('.rf-item-row');
    var prev = row.querySelector('.rf-row-prev');
    var f = input.files && input.files[0];
    prev.style.display = 'none';
    prev.innerHTML = '';
    if (!f) return;
    prev.style.display = 'block';
    if (f.type.indexOf('image/') === 0) {
      var reader = new FileReader();
      reader.onload = function (ev) { prev.innerHTML = '<img src="' + ev.target.result + '" alt="preview">'; };
      reader.readAsDataURL(f);
    } else {
      var ext = (f.name.split('.').pop() || 'file').toUpperCase();
      prev.innerHTML = '<div class="rf-fileicon"><i class="ki-duotone ki-file fs-2x"><span class="path1"></span><span class="path2"></span></i> '
        + ext + ': ' + (f.name || '') + '</div>';
    }
  });

  document.getElementById('rfUploadBtn').addEventListener('click', async function () {
    var form = document.getElementById('rfForm');
    var rows = Array.prototype.slice.call(rowsWrap.querySelectorAll('.rf-item-row'));
    // Validate: every row that has a file must have a title; at least one file overall.
    var anyFile = false, bad = false;
    rows.forEach(function (row) {
      var file = row.querySelector('.rf-file');
      var title = row.querySelector('[name="title[]"]');
      var hasFile = file && file.files && file.files.length > 0;
      if (hasFile) {
        anyFile = true;
        if (!title.value.trim()) bad = true;
      }
    });
    if (!anyFile) { rkToast('Please choose at least one file.', false); return; }
    if (bad) { rkToast('Every file needs a title.', false); return; }

    var btn = this; btn.disabled = true;
    var res = await rkPost('admin/staking/rank-files/upload', new FormData(form));
    btn.disabled = false;
    if (!res.ok) { rkToast(res.msg || 'Upload failed.', false); return; }
    rkToast(res.msg || 'Files uploaded.', true);
    setTimeout(function () { location.reload(); }, 800);
  });

  document.getElementById('rfTable').addEventListener('click', async function (e) {
    var del = e.target.closest('.rf-del');
    if (!del) return;
    if (!confirm('Delete this file? Members will no longer be able to download it.')) return;
    var id = del.getAttribute('data-id');
    var res = await rkPost('admin/staking/rank-files/delete/' + id, new FormData());
    if (!res.ok) { rkToast(res.msg || 'Delete failed.', false); return; }
    var row = del.closest('tr');
    if (row) row.remove();
    rkToast('File deleted.', true);
  });
})();
</script>

<?php $this->load->view('admin/staking/_rank_foot'); ?>
