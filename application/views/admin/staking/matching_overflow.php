<?php $this->load->view('admin/Layout/common_style'); ?>
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
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
              <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                  <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0"><?php echo $title; ?></h1>
                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Binary Matching</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Admin Overflow</li>
                  </ul>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <a class="btn btn-sm btn-light-primary"
                     href="<?php echo base_url('admin/staking/matching-overflow/export') . '?' . http_build_query(array_filter([
                       'reason' => $filters['reason'], 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to'],
                     ])); ?>">Export CSV</a>
                  <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/matching-history'); ?>">Distribution History</a>
                </div>
              </div>
            </div>
            <div id="kt_app_content" class="app-content flex-column-fluid mt-8">
              <div id="kt_app_content_container" class="app-container container-xxl">
                <?php $this->load->view('notification'); ?>

                <div class="alert alert-secondary d-flex align-items-start mb-6">
                  <i class="ki-outline ki-information fs-2 me-3 mt-1"></i>
                  <div class="fw-semibold fs-7">
                    BMAN the binary matching engine calculated but did <b>not</b> pay to the sponsor. Two causes,
                    kept separate on purpose:
                    <b>Over Ceiling</b> — the sponsor was paid, but their level bonus exceeded the Group Incentive
                    Ceiling of their highest eligible package, so only the excess is Admin's.
                    <b>Forfeited</b> — the sponsor held no eligible staking package when the level completed, so the
                    whole bonus is Admin's.
                    <div class="mt-2 text-muted">
                      Levels blocked by a broken ceiling configuration are <b>not</b> counted here — they pay nobody
                      and stay open until fixed. They are listed on
                      <a href="<?php echo base_url('admin/staking/matching-history'); ?>">Distribution History ▸ Blocked Levels</a>.
                    </div>
                  </div>
                </div>

                <!-- KPIs -->
                <div class="row g-5 g-xl-8 mb-6">
                  <div class="col-md-3">
                    <div class="card bg-light-warning"><div class="card-body">
                      <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Total Admin Overflow</div>
                      <div class="text-warning fw-bold fs-2x mt-2"><?php echo number_format($summary['total'], 4); ?></div>
                      <div class="text-muted fs-8 mt-1"><?php echo (int)$summary['events']; ?> level(s) · <?php echo (int)$summary['sponsors']; ?> sponsor(s)</div>
                      <div class="text-muted fs-8">today <?php echo number_format($summary['today'], 2); ?> · this month <?php echo number_format($summary['this_month'], 2); ?></div>
                    </div></div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-primary"><div class="card-body">
                      <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Over Ceiling</div>
                      <div class="text-primary fw-bold fs-2x mt-2"><?php echo number_format($summary['over_ceiling'], 4); ?></div>
                      <div class="text-muted fs-8 mt-1">excess above the member's cap</div>
                    </div></div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-danger"><div class="card-body">
                      <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Forfeited (No Stake)</div>
                      <div class="text-danger fw-bold fs-2x mt-2"><?php echo number_format($summary['forfeited'], 4); ?></div>
                      <div class="text-muted fs-8 mt-1">sponsor had no eligible package</div>
                    </div></div>
                  </div>
                  <div class="col-md-3">
                    <div class="card bg-light-success"><div class="card-body">
                      <div class="text-gray-600 fw-semibold fs-7 text-uppercase">Admin Share of All Bonus</div>
                      <div class="text-success fw-bold fs-2x mt-2"><?php echo number_format($summary['admin_share_pct'], 2); ?>%</div>
                      <div class="text-muted fs-8 mt-1">
                        <?php echo number_format($summary['members_paid'], 2); ?> to members ·
                        <?php echo number_format($summary['total'], 2); ?> to admin
                      </div>
                    </div></div>
                  </div>
                </div>

                <!-- Reconciliation + custody -->
                <div class="row g-5 mb-6">
                  <div class="col-lg-7">
                    <div class="card h-100 <?php echo $reconciliation['in_sync'] ? '' : 'border border-danger'; ?>">
                      <div class="card-header pt-6"><h3 class="card-title fw-bold mb-0">Reconciliation</h3></div>
                      <div class="card-body pt-3 pb-8">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                          <span class="text-muted fs-7">Calculated by the engine <span class="fs-8">(staking_matching_payouts)</span></span>
                          <b><?php echo number_format($reconciliation['calculated'], 4); ?></b>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                          <span class="text-muted fs-7">Credited to admin wallet <span class="fs-8">(<?php echo (int)$reconciliation['ledger_rows']; ?> ledger row(s))</span></span>
                          <b><?php echo number_format($reconciliation['credited'], 4); ?></b>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                          <span class="fw-semibold fs-7">Difference</span>
                          <?php if ($reconciliation['in_sync']): ?>
                            <span class="badge badge-light-success">In sync (0.0000)</span>
                          <?php else: ?>
                            <span class="badge badge-danger"><?php echo number_format($reconciliation['difference'], 4); ?> BMAN</span>
                          <?php endif; ?>
                        </div>
                        <?php if (!$reconciliation['in_sync']): ?>
                          <div class="alert alert-danger py-3 mt-3 mb-0 fs-8">
                            Both records are written inside the same database transaction, so they should never
                            diverge. A gap means a partial commit or an out-of-band edit — investigate before
                            trusting either figure.
                          </div>
                        <?php endif; ?>
                        <div class="separator my-4"></div>
                        <div class="d-flex justify-content-between py-1">
                          <span class="text-muted fs-7">Admin wallet balance <span class="fs-8">(all sources)</span></span>
                          <b><?php echo number_format($reconciliation['wallet_balance'], 4); ?></b>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                          <span class="text-muted fs-8">…of which lifetime bonus reduction</span>
                          <span class="text-muted fs-8"><?php echo number_format($reconciliation['bonus_reduction'], 4); ?></span>
                        </div>
                        <div class="text-muted fs-8 mt-2">
                          The wallet balance legitimately exceeds matching overflow — bonus reduction credits the
                          same wallet. The difference is not missing matching money.
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-5">
                    <div class="card h-100">
                      <div class="card-header pt-6"><h3 class="card-title fw-bold mb-0">Where the BMAN Is</h3></div>
                      <div class="card-body pt-3 pb-8 fs-7">
                        <p class="text-muted">
                          Admin overflow is an <b>internal accounting entry</b>, not a transfer. Member payouts leave
                          the treasury on-chain; the admin share simply never leaves it. Sending it treasury→treasury
                          would burn real gas to move nothing, so no on-chain transaction is created.
                        </p>
                        <div class="d-flex justify-content-between py-2 border-top">
                          <span class="text-muted">Treasury</span>
                          <span class="font-monospace fs-8"><?php echo html_escape($custody['treasury_address'] ?: 'not configured'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-top">
                          <span class="text-muted">Admin bonus wallet</span>
                          <span class="font-monospace fs-8"><?php echo html_escape($custody['admin_address'] ?: 'not configured'); ?></span>
                        </div>
                        <?php if ($custody['same_wallet']): ?>
                          <div class="alert alert-info py-2 mt-3 mb-0 fs-8">
                            Both are the same address, so the tokens are already where they belong — nothing to sweep.
                          </div>
                        <?php elseif ($custody['admin_address'] && $custody['treasury_address']): ?>
                          <div class="alert alert-warning py-2 mt-3 mb-0 fs-8">
                            These differ. The overflow BMAN is held in the treasury, not the admin bonus wallet. No
                            automatic sweep exists — ask if you want one built.
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Top sponsors -->
                <?php if (!empty($top)): ?>
                <div class="card mb-6">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Highest Overflow by Sponsor</h3></div>
                  <div class="card-body pt-3 pb-8">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>Sponsor</th><th class="text-end">Levels</th><th class="text-end">Paid to Member</th>
                          <th class="text-end">To Admin</th><th class="text-end">Cap</th><th></th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php foreach ($top as $t): ?>
                          <tr>
                            <td><?php echo html_escape($t['username'] ?: ('#'.$t['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($t['referral_id']); ?></div></td>
                            <td class="text-end"><?php echo (int)$t['events']; ?></td>
                            <td class="text-end text-success"><?php echo number_format((float)$t['member_paid'], 4); ?></td>
                            <td class="text-end text-warning fw-bold"><?php echo number_format((float)$t['overflow'], 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$t['ceiling'], 2); ?></td>
                            <td class="text-end">
                              <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/matching-overflow?q=') . urlencode($t['username'] ?: ''); ?>">View</a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <form class="card mb-6" method="get" action="<?php echo base_url('admin/staking/matching-overflow'); ?>">
                  <div class="card-body py-5">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-3">
                        <label class="form-label fs-8 text-muted">Search sponsor</label>
                        <input type="text" name="q" value="<?php echo html_escape($filters['q']); ?>"
                               class="form-control form-control-sm form-control-solid" placeholder="username / referral / email">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fs-8 text-muted">Reason</label>
                        <select name="reason" class="form-select form-select-sm form-select-solid">
                          <option value="">All</option>
                          <option value="over_ceiling" <?php echo $filters['reason'] === 'over_ceiling' ? 'selected' : ''; ?>>Over ceiling</option>
                          <option value="forfeited" <?php echo $filters['reason'] === 'forfeited' ? 'selected' : ''; ?>>Forfeited (no stake)</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">From</label>
                        <input type="date" name="from" value="<?php echo html_escape($filters['from']); ?>" class="form-control form-control-sm form-control-solid">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label fs-8 text-muted">To</label>
                        <input type="date" name="to" value="<?php echo html_escape($filters['to']); ?>" class="form-control form-control-sm form-control-solid">
                      </div>
                      <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit">Filter</button>
                        <a class="btn btn-sm btn-light" href="<?php echo base_url('admin/staking/matching-overflow'); ?>">Reset</a>
                      </div>
                    </div>
                  </div>
                </form>

                <!-- Detail -->
                <div class="card mb-6">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Overflow Detail (latest 300)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>When</th><th>Sponsor</th><th class="text-center">Level</th>
                          <th class="text-end">Left / Right</th><th class="text-end">Matched</th>
                          <th class="text-end">Raw Bonus</th><th class="text-end">Member Paid</th>
                          <th class="text-end">Cap</th><th class="text-end">→ Admin</th>
                          <th>Reason</th><th>Package</th><th>Run Ref</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($rows)): ?>
                          <tr><td colspan="12" class="text-center text-muted py-6">No admin overflow recorded for this filter.</td></tr>
                        <?php else: foreach ($rows as $r):
                          $forfeited = (int)$r['sponsor_eligible'] === 0;
                          $memberPaid = (float)$r['earning_amount'] + (float)$r['staking_amount'];
                        ?>
                          <tr>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['created_at']); ?></td>
                            <td><?php echo html_escape($r['username'] ?: ('#'.$r['user_id'])); ?>
                              <div class="text-muted fs-8"><?php echo html_escape($r['referral_id'] ?? ''); ?></div></td>
                            <td class="text-center">
                              <?php if ($r['level'] !== null): ?>
                                <span class="badge badge-light-primary">L<?php echo (int)$r['level']; ?></span>
                              <?php else: ?><span class="badge badge-light">legacy</span><?php endif; ?>
                            </td>
                            <td class="text-end fs-8 text-muted"><?php echo number_format((float)$r['left_before'], 2); ?> / <?php echo number_format((float)$r['right_before'], 2); ?></td>
                            <td class="text-end"><?php echo number_format((float)$r['matched_volume'], 4); ?></td>
                            <td class="text-end"><?php echo number_format((float)$r['raw_bonus'], 4); ?>
                              <div class="text-muted fs-8"><?php echo number_format((float)$r['total_percent'], 2); ?>%</div></td>
                            <td class="text-end text-success"><?php echo number_format($memberPaid, 4); ?></td>
                            <td class="text-end text-muted"><?php echo number_format((float)$r['ceiling_applied'], 2); ?></td>
                            <td class="text-end text-warning fw-bold"><?php echo number_format((float)$r['admin_overflow'], 4); ?></td>
                            <td>
                              <?php if ($forfeited): ?>
                                <span class="badge badge-light-danger" title="Sponsor held no eligible staking package when this level completed">No stake</span>
                              <?php else: ?>
                                <span class="badge badge-light-primary" title="Level bonus exceeded the member's Group Incentive Ceiling">Over ceiling</span>
                              <?php endif; ?>
                            </td>
                            <td class="fs-8 text-muted">
                              <?php if (!empty($r['package_name'])): ?>
                                <?php echo html_escape($r['package_name']); ?>
                                <div>cap now <?php echo number_format((float)$r['package_ceiling'], 0); ?></div>
                              <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="fs-8 text-muted"><?php echo html_escape($r['run_ref'] ?? ''); ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Admin wallet credit trail -->
                <div class="card">
                  <div class="card-header pt-6"><h3 class="card-title fw-bold">Admin Wallet Credit Trail (latest 50)</h3></div>
                  <div class="card-body pt-3 pb-9">
                    <div class="table-responsive">
                      <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                          <th>#</th><th>When</th><th class="text-end">Credit</th><th class="text-end">Balance After</th>
                          <th>Sponsor</th><th>Description</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                        <?php if (empty($wallet_ledger)): ?>
                          <tr><td colspan="6" class="text-center text-muted py-6">No admin wallet credits from binary matching yet.</td></tr>
                        <?php else: foreach ($wallet_ledger as $w): ?>
                          <tr>
                            <td class="text-muted fs-8"><?php echo (int)$w['id']; ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($w['created_at']); ?></td>
                            <td class="text-end text-warning fw-bold"><?php echo number_format((float)$w['credit'], 4); ?></td>
                            <td class="text-end"><?php echo number_format((float)$w['balance_after'], 4); ?></td>
                            <td class="fs-8">#<?php echo (int)$w['reference_user_id']; ?></td>
                            <td class="fs-8 text-muted"><?php echo html_escape($w['description'] ?? ''); ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('admin/Layout/common_script'); ?>
</body>
</html>
