<?php
/**
 * ROI Details Modal - Show ROI calculation and distribution timeline
 * Triggered when user clicks "ROI Details" on staking purchase
 */
?>

<div class="modal fade" id="roiDetailsModal" tabindex="-1" role="dialog" aria-labelledby="roiDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="roiDetailsLabel">
                    <i class="fas fa-chart-line"></i> ROI Details & Calculation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <!-- Order Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0"><i class="fas fa-info-circle"></i> Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Order ID:</strong><br>
                                    <code id="roi_order_id">SWP-20260708-443055A3C</code>
                                </p>
                                <p class="mb-2">
                                    <strong>Created:</strong><br>
                                    <span id="roi_created">2026-07-09 20:12:08</span>
                                </p>
                                <p class="mb-0">
                                    <strong>Duration:</strong><br>
                                    <span id="roi_duration">1 Year</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>USDT Sent:</strong><br>
                                    <span class="badge badge-info" id="roi_usdt_sent">0.10 USDT</span>
                                </p>
                                <p class="mb-2">
                                    <strong>BMAN Purchased:</strong><br>
                                    <span class="badge badge-success" id="roi_bman_purchased">1 BMAN</span>
                                </p>
                                <p class="mb-0">
                                    <strong>Bonus BMAN:</strong><br>
                                    <span class="badge badge-warning" id="roi_bonus_bman">+0 BMAN</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROI Calculation -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0"><i class="fas fa-calculator"></i> ROI Calculation</h6>
                    </div>
                    <div class="card-body">
                        <!-- Formula Box -->
                        <div class="alert alert-light border" style="font-family: monospace; font-size: 0.95rem;">
                            <strong>Formula:</strong><br>
                            Annual ROI = Principal × Rate<br>
                            Hourly ROI = Annual ROI ÷ 365 ÷ 24<br>
                            <br>
                            <strong>Your Calculation:</strong><br>
                            <span id="roi_calculation_formula"></span>
                        </div>

                        <!-- Calculation Breakdown -->
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <td><strong>Principal Amount:</strong></td>
                                            <td class="text-right"><span id="roi_principal">1 BMAN</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Annual ROI Rate:</strong></td>
                                            <td class="text-right"><span id="roi_rate">10%</span></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td><strong>Annual ROI Amount:</strong></td>
                                            <td class="text-right text-primary"><strong><span id="roi_annual">0.10 BMAN</span></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <td><strong>Daily ROI:</strong></td>
                                            <td class="text-right"><span id="roi_daily">0.000274 BMAN</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Hourly ROI:</strong></td>
                                            <td class="text-right"><span id="roi_hourly">0.0000114 BMAN</span></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td><strong>Total ROI at Maturity:</strong></td>
                                            <td class="text-right text-success"><strong><span id="roi_total_maturity">0.10 BMAN</span></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Timeline -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0"><i class="fas fa-calendar-alt"></i> ROI Distribution Timeline</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Period</th>
                                    <th class="text-right">ROI Accrued</th>
                                    <th class="text-right">Cumulative ROI</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="roi_timeline">
                                <tr>
                                    <td>Day 1 (Jul 9)</td>
                                    <td class="text-right">0.000274 BMAN</td>
                                    <td class="text-right">0.000274 BMAN</td>
                                    <td><span class="badge badge-secondary">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Day 7 (Jul 15)</td>
                                    <td class="text-right">0.00192 BMAN</td>
                                    <td class="text-right">0.00192 BMAN</td>
                                    <td><span class="badge badge-secondary">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Day 30 (Aug 8)</td>
                                    <td class="text-right">0.00822 BMAN</td>
                                    <td class="text-right">0.00822 BMAN</td>
                                    <td><span class="badge badge-secondary">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Day 90 (Oct 7)</td>
                                    <td class="text-right">0.0247 BMAN</td>
                                    <td class="text-right">0.0247 BMAN</td>
                                    <td><span class="badge badge-secondary">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Day 180 (Jan 5)</td>
                                    <td class="text-right">0.0493 BMAN</td>
                                    <td class="text-right">0.0493 BMAN</td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Day 365 (Jul 9, 2027)</td>
                                    <td class="text-right">0.10 BMAN</td>
                                    <td class="text-right"><strong>0.10 BMAN</strong></td>
                                    <td><span class="badge badge-success">Matured</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Wallet Distribution -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0"><i class="fas fa-wallet"></i> Wallet Distribution (Option 1)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="mb-2">
                                    <i class="fas fa-exchange-alt" style="font-size: 2rem; color: #17a2b8;"></i>
                                </div>
                                <strong>Exchange Wallet</strong>
                                <p class="h5 text-primary">100%</p>
                                <p class="text-muted mb-0"><span id="roi_exchange_amount">1 BMAN</span></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="mb-2">
                                    <i class="fas fa-piggy-bank" style="font-size: 2rem; color: #28a745;"></i>
                                </div>
                                <strong>Staking Wallet</strong>
                                <p class="h5 text-success">0%</p>
                                <p class="text-muted mb-0"><span id="roi_staking_amount">0 BMAN</span></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="mb-2">
                                    <i class="fas fa-chart-line" style="font-size: 2rem; color: #ffc107;"></i>
                                </div>
                                <strong>Earning Wallet</strong>
                                <p class="h5 text-warning">0%</p>
                                <p class="text-muted mb-0"><span id="roi_earning_amount">0 BMAN</span></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="mb-2">
                                    <i class="fas fa-gift" style="font-size: 2rem; color: #dc3545;"></i>
                                </div>
                                <strong>Bonus Wallet</strong>
                                <p class="h5 text-danger">0%</p>
                                <p class="text-muted mb-0"><span id="roi_bonus_amount">0 BMAN</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> How ROI Works</h6>
                    <small>
                        Your <strong id="roi_summary_principal">1 BMAN</strong> will earn <strong id="roi_summary_rate">10%</strong>
                        annually, distributed to your <strong>Earning Wallet</strong> hourly. The ROI is calculated daily
                        and broadcast to blockchain after 24 hours for confirmation. You can withdraw your earned ROI
                        from your Earning Wallet anytime.
                    </small>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadROIReport()">
                    <i class="fas fa-download"></i> Download Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .roi-chart {
        height: 300px;
        margin: 20px 0;
    }

    .roi-stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .roi-stat-label {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .roi-stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 5px;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 20px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 3px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
    }

    .timeline-item.completed::before {
        background: #28a745;
    }

    .timeline-item.pending::before {
        background: #ffc107;
    }
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load ROI details from the staking order
    loadROIDetails();
});

function loadROIDetails(stakingOrderId) {
    // This will be populated with actual data from AJAX
    // Example data shown above

    // If you want to load specific order:
    if (stakingOrderId) {
        $.ajax({
            url: '<?php echo base_url("api/staking/roi-details"); ?>',
            method: 'GET',
            data: { order_id: stakingOrderId },
            success: function(response) {
                if (response.status === 'success') {
                    populateROIDetails(response.data);
                }
            }
        });
    }
}

function populateROIDetails(data) {
    // Update Order Summary
    $('#roi_order_id').text(data.order_id);
    $('#roi_created').text(data.created_at);
    $('#roi_duration').text(data.duration + ' Year(s)');
    $('#roi_usdt_sent').text(data.usdt_sent + ' USDT');
    $('#roi_bman_purchased').text(data.bman_amount + ' BMAN');
    $('#roi_bonus_bman').text((data.bonus_bman > 0 ? '+' : '') + data.bonus_bman + ' BMAN');

    // Update Calculation
    const principal = parseFloat(data.bman_amount);
    const rate = parseFloat(data.annual_roi_rate);
    const annualROI = principal * (rate / 100);
    const dailyROI = annualROI / 365;
    const hourlyROI = dailyROI / 24;
    const totalROI = principal * (rate / 100) * (data.maturity_days / 365);

    const formula = `Principal: ${principal} BMAN<br>
                     Rate: ${rate}%<br>
                     Annual ROI: ${principal} × ${rate}% = ${annualROI.toFixed(4)} BMAN<br>
                     Daily ROI: ${annualROI.toFixed(4)} ÷ 365 = ${dailyROI.toFixed(6)} BMAN<br>
                     Hourly ROI: ${dailyROI.toFixed(6)} ÷ 24 = ${hourlyROI.toFixed(8)} BMAN`;

    $('#roi_calculation_formula').html(formula);
    $('#roi_principal').text(principal + ' BMAN');
    $('#roi_rate').text(rate + '%');
    $('#roi_annual').text(annualROI.toFixed(4) + ' BMAN');
    $('#roi_daily').text(dailyROI.toFixed(6) + ' BMAN');
    $('#roi_hourly').text(hourlyROI.toFixed(8) + ' BMAN');
    $('#roi_total_maturity').text(totalROI.toFixed(4) + ' BMAN');

    // Update Distribution
    const distribution = data.coin_distribution_option || 1;
    const distOptions = {
        1: { exchange: 100, earning: 0, staking: 0, bonus: 0 },
        2: { exchange: 90, earning: 0, staking: 0, bonus: 10 },
        3: { exchange: 80, earning: 10, staking: 0, bonus: 10 },
        4: { exchange: 80, earning: 10, staking: 10, bonus: 0 },
        5: { exchange: 90, earning: 10, staking: 0, bonus: 0 },
        6: { exchange: 90, earning: 0, staking: 10, bonus: 0 },
        7: { exchange: 70, earning: 10, staking: 10, bonus: 10 }
    };

    const dist = distOptions[distribution] || distOptions[1];
    $('#roi_exchange_amount').text(((principal * dist.exchange) / 100).toFixed(4) + ' BMAN');
    $('#roi_earning_amount').text(((principal * dist.earning) / 100).toFixed(4) + ' BMAN');
    $('#roi_staking_amount').text(((principal * dist.staking) / 100).toFixed(4) + ' BMAN');
    $('#roi_bonus_amount').text(((principal * dist.bonus) / 100).toFixed(4) + ' BMAN');

    // Update Summary
    $('#roi_summary_principal').text(principal + ' BMAN');
    $('#roi_summary_rate').text(rate + '%');
}

function downloadROIReport() {
    const orderId = $('#roi_order_id').text();
    // Trigger PDF download
    window.location.href = '<?php echo base_url("api/staking/download-roi-report"); ?>?order_id=' + orderId;
}

// Trigger modal
function showROIDetails(orderId) {
    loadROIDetails(orderId);
    $('#roiDetailsModal').modal('show');
}
</script>
