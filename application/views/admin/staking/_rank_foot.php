<?php
/**
 * Shared chrome for the Rank Management pages (close half). Pair with _rank_head.php.
 * Optional: $pager = ['total'=>int,'page'=>int,'per_page'=>int]
 */
?>
                                        <?php if (!empty($pager) && $pager['total'] > $pager['per_page']):
                                            $rk_pages = (int)ceil($pager['total'] / $pager['per_page']);
                                            $rk_cur   = (int)$pager['page'];
                                            $rk_from  = max(1, $rk_cur - 2);
                                            $rk_to    = min($rk_pages, $rk_cur + 2);
                                            // Preserve every active filter when paging.
                                            $rk_qs = $_GET; unset($rk_qs['page']);
                                            $rk_base = uri_string() . '?' . http_build_query($rk_qs);
                                            $rk_base .= (count($rk_qs) ? '&' : '') . 'page=';
                                        ?>
                                        <div class="d-flex flex-stack flex-wrap pt-8 rk-noprint">
                                            <div class="fs-7 text-muted">
                                                Showing <?php echo number_format(($rk_cur - 1) * $pager['per_page'] + 1); ?>–<?php
                                                    echo number_format(min($rk_cur * $pager['per_page'], $pager['total'])); ?>
                                                of <?php echo number_format($pager['total']); ?>
                                            </div>
                                            <ul class="pagination">
                                                <li class="page-item previous <?php echo $rk_cur <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo base_url($rk_base . max(1, $rk_cur - 1)); ?>">
                                                        <i class="previous"></i></a>
                                                </li>
                                                <?php if ($rk_from > 1): ?>
                                                    <li class="page-item"><a class="page-link" href="<?php echo base_url($rk_base . 1); ?>">1</a></li>
                                                    <?php if ($rk_from > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                                <?php endif; ?>
                                                <?php for ($rk_i = $rk_from; $rk_i <= $rk_to; $rk_i++): ?>
                                                    <li class="page-item <?php echo $rk_i === $rk_cur ? 'active' : ''; ?>">
                                                        <a class="page-link" href="<?php echo base_url($rk_base . $rk_i); ?>"><?php echo $rk_i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <?php if ($rk_to < $rk_pages): ?>
                                                    <?php if ($rk_to < $rk_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                                    <li class="page-item"><a class="page-link" href="<?php echo base_url($rk_base . $rk_pages); ?>"><?php echo $rk_pages; ?></a></li>
                                                <?php endif; ?>
                                                <li class="page-item next <?php echo $rk_cur >= $rk_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo base_url($rk_base . min($rk_pages, $rk_cur + 1)); ?>">
                                                        <i class="next"></i></a>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php endif; ?>

                                    </div><!--/card-body-->
                                </div><!--/card-->

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
    // Small shared helpers for the rank pages.
    window.rkToast = function (msg, ok) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: ok ? 'success' : 'error',
                        buttonsStyling: false, confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-primary' } });
        } else { alert(msg); }
    };
    window.rkPost = async function (url, fd) {
        try {
            const res = await fetch('<?php echo base_url(); ?>' + url, {
                method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const j = await res.json();
            return { ok: res.ok && j.status === 'success', msg: j.message || '', data: j.data || j };
        } catch (e) { return { ok: false, msg: 'Network error: ' + e.message }; }
    };
    </script>
</body>

</html>
