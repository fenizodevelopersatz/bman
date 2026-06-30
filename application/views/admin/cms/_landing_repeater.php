<?php
/**
 * Reusable repeater card for a landing list-section.
 * Vars: $rep (key), $title, $rows (objects), $cols (col=>label text),
 *       $images (col=>label, optional), $extra (col=>label switches, optional)
 */
$cols   = isset($cols) ? $cols : array();
$images = isset($images) ? $images : array();
$extra  = isset($extra) ? $extra : array();
$base   = base_url();
$allcols = array_merge(array_keys($cols), array_keys($images), array_keys($extra));
?>
<div class="accordion-item">
    <h2 class="accordion-header">
        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#col_rep_<?php echo $rep; ?>">
            <i class="fa fa-list me-2 text-primary"></i><?php echo $title; ?>
            <span class="badge badge-light-primary ms-2"><?php echo count($rows); ?></span>
        </button>
    </h2>
    <div id="col_rep_<?php echo $rep; ?>" class="accordion-collapse collapse" data-bs-parent="#lpAccordion">
        <div class="accordion-body">

            <!-- existing rows -->
            <div class="table-responsive mb-5">
                <table class="table table-row-bordered align-middle lp-rep-table" data-rep="<?php echo $rep; ?>">
                    <thead><tr class="fw-bold fs-8 text-muted">
                        <th width="30"></th>
                        <?php foreach ($images as $c=>$l) echo '<th>'.$l.'</th>'; ?>
                        <?php foreach ($cols as $c=>$l) echo '<th>'.$l.'</th>'; ?>
                        <th width="70">Status</th><th width="110" class="text-end">Action</th>
                    </tr></thead>
                    <tbody class="lp-sortable" data-rep="<?php echo $rep; ?>">
                    <?php foreach ($rows as $r): ?>
                        <tr data-id="<?php echo $r->id; ?>">
                            <td class="lp-handle text-muted"><i class="fa fa-grip-vertical"></i></td>
                            <?php foreach ($images as $c=>$l): ?>
                                <td><?php echo !empty($r->$c) ? '<img src="'.$base.htmlspecialchars($r->$c).'">' : '<span class="text-muted fs-8">—</span>'; ?></td>
                            <?php endforeach; ?>
                            <?php foreach ($cols as $c=>$l): ?>
                                <td class="fs-8"><?php echo htmlspecialchars(mb_strimwidth((string)$r->$c, 0, 40, '…')); ?></td>
                            <?php endforeach; ?>
                            <td>
                                <span class="badge badge-light-<?php echo $r->status ? 'success">Active' : 'danger">Off'; ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-icon btn-sm btn-light-primary lp-edit"
                                    data-row='<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>'><i class="fa fa-pen"></i></button>
                                <a href="<?php echo $base; ?>landing-item-status/<?php echo $rep; ?>/<?php echo $r->id; ?>" class="btn btn-icon btn-sm btn-light-warning lp-toggle"><i class="fa fa-power-off"></i></a>
                                <a href="<?php echo $base; ?>landing-item-delete/<?php echo $rep; ?>/<?php echo $r->id; ?>" class="btn btn-icon btn-sm btn-light-danger lp-del"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- add / edit form -->
            <form class="lp-rep-form border border-dashed rounded p-4" data-rep="<?php echo $rep; ?>" enctype="multipart/form-data">
                <input type="hidden" name="id" value="0">
                <div class="row">
                    <?php foreach ($cols as $c=>$l): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fs-8"><?php echo $l; ?></label>
                            <?php if ($c==='answer' || $c==='description'): ?>
                                <textarea name="<?php echo $c; ?>" rows="2" class="form-control form-control-sm form-control-solid"></textarea>
                            <?php else: ?>
                                <input type="text" name="<?php echo $c; ?>" class="form-control form-control-sm form-control-solid">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($images as $c=>$l): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fs-8"><?php echo $l; ?> (image)</label>
                            <input type="file" name="<?php echo $c; ?>" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" class="form-control form-control-sm form-control-solid">
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($extra as $c=>$l): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fs-8 d-block"><?php echo $l; ?></label>
                            <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input h-25px w-45px" type="checkbox" value="1" name="<?php echo $c; ?>" <?php echo $c==='status'?'checked':''; ?>>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-light lp-rep-reset">Clear</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Save Item</button>
                </div>
            </form>
            <div class="text-muted fs-8 mt-2">Drag rows by the handle to reorder, then changes save automatically.</div>

        </div>
    </div>
</div>
