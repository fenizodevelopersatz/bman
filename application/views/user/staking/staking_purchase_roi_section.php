<?php
/**
 * ROI Details Section for Staking Purchase Modal
 * Add this section to the existing staking purchase details modal
 */
?>

<!-- ROI DETAILS SECTION -->
<div class="card mb-4 border-success">
    <div class="card-header bg-success text-white">
        <h6 class="m-0">
            <i class="fas fa-chart-line"></i> ROI Details & Returns
        </h6>
    </div>
    <div class="card-body">

        <!-- ROI Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <small class="text-muted d-block mb-2">PRINCIPAL INVESTMENT</small>
                        <h4 class="text-primary mb-0">
                            <span id="roi_principal_display">100,000</span> BMAN
                        </h4>
                        <small class="text-danger">
                            <i class="fas fa-lock"></i> LOCKED Until Maturity
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <small class="text-muted d-block mb-2">EXPECTED ROI RETURN</small>
                        <h4 class="text-success mb-0">
                            <span id="roi_total_display">150,000</span> BMAN
                        </h4>
                        <small class="text-success">
                            <i class="fas fa-unlock"></i> LIQUID - Earned Hourly
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROI Rate & Timeline -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="text-center p-2 bg-info bg-opacity-10 rounded">
                    <strong class="d-block mb-1">ROI Rate</strong>
                    <h5 class="m-0 text-info" id="roi_rate_display">150%</h5>
                    <small class="text-muted">Total over term</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                    <strong class="d-block mb-1">Duration</strong>
                    <h5 class="m-0 text-warning" id="roi_duration_display">2 Years</h5>
                    <small class="text-muted">730 days</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                    <strong class="d-block mb-1">Bonus Received</strong>
                    <h5 class="m-0 text-danger" id="roi_bonus_display">25,000</h5>
                    <small class="text-muted">Extra BMAN</small>
                </div>
            </div>
        </div>

        <!-- Key Benefits -->
        <div class="alert alert-info mb-3" role="alert">
            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Key Points</h6>
            <ul class="mb-0 small">
                <li class="mb-2">
                    <strong>Principal is LOCKED</strong>
                    <span class="badge badge-danger">Cannot withdraw until maturity</span>
                </li>
                <li class="mb-2">
                    <strong>ROI is LIQUID</strong>
                    <span class="badge badge-success">Accumulates & can withdraw anytime</span>
                </li>
                <li class="mb-2">
                    <strong>At Maturity</strong>
                    <span class="badge badge-primary" id="roi_total_at_maturity">250,000 BMAN</span>
                </li>
                <li>
                    <strong>Bonus 25,000 BMAN</strong>
                    <span class="badge badge-warning">Yours to keep (not part of ROI)</span>
                </li>
            </ul>
        </div>

        <!-- ROI Breakdown Table -->
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Period</th>
                        <th class="text-right">ROI Earned</th>
                        <th class="text-right">Cumulative</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Yearly</td>
                        <td class="text-right"><strong id="roi_yearly">75,000</strong> BMAN</td>
                        <td class="text-right"><strong id="roi_yearly_cum">75,000</strong> BMAN</td>
                        <td><span class="badge badge-info">Active</span></td>
                    </tr>
                    <tr>
                        <td>Year 2</td>
                        <td class="text-right"><strong id="roi_year2">75,000</strong> BMAN</td>
                        <td class="text-right"><strong id="roi_year2_cum">150,000</strong> BMAN</td>
                        <td><span class="badge badge-success">Maturity</span></td>
                    </tr>
                    <tr class="border-top-2 font-weight-bold">
                        <td>TOTAL VALUE</td>
                        <td class="text-right text-success">
                            <i class="fas fa-plus"></i>
                            <span id="roi_principal_display2">100,000</span> +
                            <span id="roi_total_display2">150,000</span>
                        </td>
                        <td class="text-right text-success" style="font-size: 1.1rem;">
                            <strong id="roi_total_value">250,000 BMAN</strong>
                        </td>
                        <td><span class="badge badge-success">Unlocked</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Timeline Info -->
        <div class="alert alert-light mt-3 mb-0" role="alert">
            <small>
                <strong>Purchase Date:</strong> <span id="roi_purchase_date">2026-07-10</span> |
                <strong>Maturity Date:</strong> <span id="roi_maturity_date">2028-07-10</span> |
                <strong>Status:</strong> <span class="badge badge-success">ACTIVE & EARNING</span>
            </small>
        </div>

    </div>
</div>

<!-- STYLES -->
<style>
    .border-success {
        border-left: 4px solid #28a745 !important;
    }

    .bg-opacity-10 {
        opacity: 0.1;
    }

    .table-responsive {
        max-height: 300px;
        overflow-y: auto;
    }

    .border-top-2 {
        border-top: 2px solid #dee2e6 !important;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        margin-left: 0.25rem;
    }

    .card-body {
        padding: 1.25rem;
    }

    h5 {
        margin: 0;
    }
</style>

<!-- JAVASCRIPT: Populate with dynamic data -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // This function will be called when the modal loads
    // Pass the staking order data to populate all fields
    populateROIDetails({
        principal: 100000,
        roi_total: 150000,
        roi_rate: 150,
        duration_years: 2,
        bonus: 25000,
        purchase_date: '2026-07-10',
        maturity_date: '2028-07-10',
        annual_roi: 75000
    });
});

function populateROIDetails(data) {
    // Principal
    $('#roi_principal_display').text(formatNumber(data.principal));
    $('#roi_principal_display2').text(formatNumber(data.principal));

    // ROI Total
    $('#roi_total_display').text(formatNumber(data.roi_total));
    $('#roi_total_display2').text(formatNumber(data.roi_total));

    // ROI Rate
    $('#roi_rate_display').text(data.roi_rate + '%');

    // Duration
    $('#roi_duration_display').text(data.duration_years + ' Years');

    // Bonus
    $('#roi_bonus_display').text(formatNumber(data.bonus));

    // Total at Maturity
    const totalAtMaturity = data.principal + data.roi_total;
    $('#roi_total_at_maturity').text(formatNumber(totalAtMaturity) + ' BMAN');
    $('#roi_total_value').text(formatNumber(totalAtMaturity) + ' BMAN');

    // Yearly breakdown
    const yearlyROI = data.annual_roi;
    $('#roi_yearly').text(formatNumber(yearlyROI));
    $('#roi_yearly_cum').text(formatNumber(yearlyROI));
    $('#roi_year2').text(formatNumber(yearlyROI));
    $('#roi_year2_cum').text(formatNumber(data.roi_total));

    // Dates
    $('#roi_purchase_date').text(data.purchase_date);
    $('#roi_maturity_date').text(data.maturity_date);
}

// Format numbers with commas
function formatNumber(num) {
    return num.toLocaleString();
}
</script>
