<?php
$intro = 'Upload any image, PDF or document members can download from their '
       . '<b>Rank &amp; Rewards</b> page. Tie a file to a specific rank (only members who hold '
       . 'that rank see it) or leave it on <b>All ranks</b> to show it to everyone.';
$this->load->view('admin/staking/_rank_head', ['title' => $title, 'card_tilte' => $card_tilte, 'intro' => $intro]);
?>

<style>
  .rf-drop{ border:1px dashed #c9cee0; border-radius:12px; padding:16px; background:#fafbff; }
  .rf-prev{ margin-top:12px; display:none; }
  .rf-prev img{ max-width:260px; max-height:180px; border-radius:8px; border:1px solid #e6e8ef; }
  .rf-prev .rf-fileicon{ display:inline-flex; align-items:center; gap:10px; padding:12px 16px;
    border:1px solid #e6e8ef; border-radius:10px; background:#fff; font-weight:700; color:#334155; }
  .rf-thumb{ width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid #e6e8ef; background:#fff; }
  .rf-thumb-icon{ width:54px; height:54px; border-radius:8px; border:1px solid #e6e8ef; background:#f8fafc;
    display:flex; align-items:center; justify-content:center; font-size:22px; color:#64748b; }
</style>

<div class="row g-6 mb-8">
  <!-- Upload form -->
  <div class="col-lg-5">
    <form id="rfForm" class="rf-drop" onsubmit="return false;">
      <div class="mb-4">
        <label class="form-label fw-semibold fs-7">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Gold Rank Certificate" required>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold fs-7">Show to</label>
        <select name="rank_id" class="form-select form-select-sm">
          <option value="">All ranks (everyone)</option>
          <?php foreach ($ranks as $r): ?>
            <option value="<?php echo (int) $r['id']; ?>"><?php echo html_escape($r['name']); ?> only</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold fs-7">File <span class="text-danger">*</span></label>
        <input type="file" id="rfFile" name="file" class="form-control form-control-sm"
               accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx">
        <div class="text-muted fs-8 mt-1">Image, PDF or document. Max 10 MB.</div>
      </div>

      <div id="rfPreview" class="rf-prev"></div>

      <button type="button" id="rfUploadBtn" class="btn btn-sm btn-primary mt-4">
        <i class="ki-duotone ki-cloud-add fs-5"><span class="path1"></span><span class="path2"></span></i>
        Upload
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
  var fileInput = document.getElementById('rfFile');
  var preview = document.getElementById('rfPreview');

  fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    preview.style.display = 'none';
    preview.innerHTML = '';
    if (!f) return;
    preview.style.display = 'block';
    if (f.type.indexOf('image/') === 0) {
      var reader = new FileReader();
      reader.onload = function (e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="preview">'; };
      reader.readAsDataURL(f);
    } else {
      var isPdf = f.type === 'application/pdf' || /\.pdf$/i.test(f.name);
      preview.innerHTML = '<div class="rf-fileicon"><i class="ki-duotone ki-file fs-2x"><span class="path1"></span><span class="path2"></span></i> '
        + (isPdf ? 'PDF' : 'File') + ': ' + (f.name || '') + '</div>';
    }
  });

  document.getElementById('rfUploadBtn').addEventListener('click', async function () {
    var form = document.getElementById('rfForm');
    var title = form.querySelector('[name="title"]').value.trim();
    if (!title) { rkToast('Please enter a title.', false); return; }
    if (!fileInput.files || !fileInput.files[0]) { rkToast('Please choose a file.', false); return; }

    var btn = this; btn.disabled = true;
    var fd = new FormData(form);
    var res = await rkPost('admin/staking/rank-files/upload', fd);
    btn.disabled = false;
    if (!res.ok) { rkToast(res.msg || 'Upload failed.', false); return; }
    rkToast('File uploaded.', true);
    setTimeout(function () { location.reload(); }, 700);
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
