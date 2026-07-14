<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Binary Matching Cron Logs</h1>
        <div class="page-meta">Execution history and diagnostics</div>
    </div>

    <!-- Cron Status Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Schedule</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Frequency:</strong> Every 3 minutes<br>
                        <code>*/3 * * * * php index.php cron binarymatchingcron_simple process</code>
                    </div>
                    <div class="mb-3">
                        <strong>Cycle Duration:</strong> 45 seconds<br>
                        <small class="text-muted">
                            Phase 1: 15s (calculate bonuses)<br>
                            Phase 2: 20s (process payouts)<br>
                            Phase 3: 10s (health check)
                        </small>
                    </div>
                    <div class="mb-3">
                        <strong>Next Run:</strong><br>
                        <code class="text-success"><?= $next_run ?></code>
                    </div>
                    <div>
                        <strong>Runs Today:</strong> <span class="badge badge-info"><?= $total_runs_today ?></span> / 480 expected
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stats = [
                        'COMPLETED' => count(array_filter($logs, fn($l) => $l['status'] === 'COMPLETED')),
                        'FAILED' => count(array_filter($logs, fn($l) => $l['status'] === 'FAILED')),
                        'RUNNING' => count(array_filter($logs, fn($l) => $l['status'] === 'RUNNING')),
                    ];
                    $total_logs = count($logs);
                    ?>
                    <div class="mb-3">
                        <strong>Completed:</strong> <span class="badge badge-success"><?= $stats['COMPLETED'] ?></span>
                        <br><small class="text-muted">Success rate: <?= $total_logs > 0 ? round(($stats['COMPLETED'] / $total_logs) * 100, 1) : 0 ?>%</small>
                    </div>
                    <div class="mb-3">
                        <strong>Failed:</strong> <span class="badge badge-danger"><?= $stats['FAILED'] ?></span>
                        <br><small class="text-muted">Review logs below</small>
                    </div>
                    <div>
                        <strong>Running:</strong> <span class="badge badge-warning"><?= $stats['RUNNING'] ?></span>
                        <br><small class="text-muted">Currently executing</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Execution Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Execution Logs (Last 50 Runs)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Execution Time</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Duration</th>
                            <th>Phase 1</th>
                            <th>Phase 2</th>
                            <th>Phase 3</th>
                            <th class="text-right">Bonuses</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <strong><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($log['status'] === 'COMPLETED'): ?>
                                        <span class="badge badge-success">✓ OK</span>
                                    <?php elseif ($log['status'] === 'RUNNING'): ?>
                                        <span class="badge badge-warning">⏳ Running</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">✗ Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <code><?php
                                    if ($log['status'] === 'COMPLETED' && $log['updated_at']) {
                                        $duration = (strtotime($log['updated_at']) - strtotime($log['created_at']));
                                        echo $duration . 's';
                                    } else {
                                        echo '—';
                                    }
                                    ?></code>
                                </td>
                                <td>
                                    <?php
                                    $phase1 = $log['phase1_status'] ?? 'pending';
                                    $badge = $phase1 === 'completed' ? 'success' : ($phase1 === 'running' ? 'warning' : 'light');
                                    ?>
                                    <small class="badge badge-<?= $badge ?>"><?= ucfirst($phase1) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $phase2 = $log['phase2_status'] ?? 'pending';
                                    $badge = $phase2 === 'completed' ? 'success' : ($phase2 === 'running' ? 'warning' : 'light');
                                    ?>
                                    <small class="badge badge-<?= $badge ?>"><?= ucfirst($phase2) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $phase3 = $log['phase3_status'] ?? 'pending';
                                    $badge = $phase3 === 'completed' ? 'success' : ($phase3 === 'running' ? 'warning' : 'light');
                                    ?>
                                    <small class="badge badge-<?= $badge ?>"><?= ucfirst($phase3) ?></small>
                                </td>
                                <td class="text-right">
                                    <strong><?= $log['levels_processed'] ?? 0 ?></strong>
                                </td>
                                <td>
                                    <?php if ($log['error_message']): ?>
                                        <small class="text-danger" title="<?= $log['error_message'] ?>">
                                            <i class="fas fa-exclamation-circle"></i> Error
                                        </small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Error Details -->
                            <?php if ($log['status'] === 'FAILED' && $log['error_message']): ?>
                            <tr class="table-danger">
                                <td colspan="8" class="py-2">
                                    <strong class="text-danger">Error:</strong> <code><?= htmlspecialchars($log['error_message']) ?></code>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No logs available yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <p class="text-muted">
                    <small>Showing last <?= count($logs) ?> executions. Oldest data: <?php
                    if (!empty($logs)) {
                        echo date('Y-m-d H:i:s', strtotime($logs[array_key_last($logs)]['created_at']));
                    }
                    ?></small>
                </p>
            </div>
        </div>
    </div>

    <!-- Timeline Visualization -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">3-Minute Cycle Breakdown</h5>
        </div>
        <div class="card-body">
            <div style="font-family: monospace; font-size: 13px; line-height: 1.6; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="color: #666;">00:00 ──┐</div>
                <div style="margin-left: 50px; color: #0066cc;">└─ <strong>Phase 1: Calculate Bonuses (15 sec)</strong></div>
                <div style="margin-left: 70px; color: #666;">├─ Query pending purchases</div>
                <div style="margin-left: 70px; color: #666;">├─ Calculate levels 1-10</div>
                <div style="margin-left: 70px; color: #666;">└─ Insert bonus_ledger rows</div>
                <div style="margin-top: 10px; color: #666;">00:15 ──┴─ <strong style="color: #28a745;">Phase 2: Process Payouts (20 sec)</strong></div>
                <div style="margin-left: 70px; color: #666;">├─ Check admin gas balance</div>
                <div style="margin-left: 70px; color: #666;">├─ Broadcast transactions</div>
                <div style="margin-left: 70px; color: #666;">├─ Verify confirmations (12 blocks)</div>
                <div style="margin-left: 70px; color: #666;">└─ Credit earning/staking wallets</div>
                <div style="margin-top: 10px; color: #666;">00:35 ──┐</div>
                <div style="margin-left: 50px; color: #fd7e14;"><strong>Phase 3: Health Check (10 sec)</strong></div>
                <div style="margin-left: 70px; color: #666;">├─ DB connection check</div>
                <div style="margin-left: 70px; color: #666;">├─ Queue status query</div>
                <div style="margin-left: 70px; color: #666;">└─ Log results</div>
                <div style="margin-top: 10px; color: #666;">00:45 ──┴─ <strong style="color: #6c757d;">Ready for next cycle</strong></div>
            </div>

            <div class="mt-3 alert alert-info">
                <strong>Key Metrics:</strong>
                <ul class="mb-0">
                    <li>Frequency: Every 3 minutes (480 runs/day)</li>
                    <li>Total cycle time: 45 seconds</li>
                    <li>Idle time: ~135 seconds before next execution</li>
                    <li>Target success rate: 99%+ (expect 1 failure per 100 runs)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Troubleshooting</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-exclamation-triangle text-danger"></i> If cron is not running:</h6>
                    <ol>
                        <li>Verify cron job in system (Linux: <code>crontab -l</code>)</li>
                        <li>Check PHP-CLI path: <code>which php</code></li>
                        <li>Test endpoint: <code>curl https://your-site/cron/binarymatchingcron_simple/process</code></li>
                        <li>Check server logs: <code>/var/log/binary-matching.log</code></li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-hourglass text-warning"></i> If phase is slow (>60s):</h6>
                    <ol>
                        <li>Check database indexes on binary_matching tables</li>
                        <li>Monitor server CPU/RAM during execution</li>
                        <li>Reduce per-cycle batch size if needed</li>
                        <li>Check blockchain network latency for Phase 2</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.badge-light {
    background-color: #e9ecef;
    color: #666;
}
</style>
