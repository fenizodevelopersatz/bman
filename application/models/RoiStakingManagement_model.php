<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RoiStakingManagement_model extends CI_Model
{
    private $table = 'roi_staking_management';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * The admin-configured combo split, as [fixed_pct, regular_pct].
     *
     * Falls back to 50/50 if the plan row is missing or the pair does not total
     * 100 — a bad split must never silently mis-price a stake. Staking_model
     * already validates the sum on save; this is the belt-and-braces read.
     *
     * @return array{0:float,1:float}
     */
    private function _comboSplit()
    {
        $row = $this->db->select('combo_fixed_pct, combo_regular_pct')
                        ->get_where('staking_plans', ['code' => 'combo'])->row_array();

        $f = $row !== null ? (float)$row['combo_fixed_pct'] : 0;
        $r = $row !== null ? (float)$row['combo_regular_pct'] : 0;

        if ($f <= 0 || $r <= 0 || abs(($f + $r) - 100) > 0.001) {
            log_message('error', '[combo_split] staking_plans combo split is unusable ('
                . $f . '/' . $r . ') — falling back to 50/50.');
            return [50.0, 50.0];
        }
        return [$f, $r];
    }

    /**
     * Create ROI staking record for purchase
     */
    public function createROIRecord($stakingOrderId, $userId, $orderRef, $planType, $data)
    {
        // Monthly-for-full-term model:
        //   Fixed   = one lump of (principal × fixed%) at maturity (fixed% is a TOTAL rate).
        //   Regular = (principal × monthly%) credited every month for duration_years×12 months;
        //             principal returned to staking wallet at maturity.
        //   Combo   = regular monthly ROI for the full term + a fixed lump at maturity.
        $principal      = (float)$data['principal_amount'];
        $fixedPct       = (float)($data['fixed_percent']  ?? $data['roi_rate_percent'] ?? 0);
        $monthlyPct     = (float)($data['monthly_percent'] ?? 0);
        $durationYears  = (int)$data['duration_years'];
        $months         = max(1, $durationYears * 12);
        $createdAt      = $data['created_at'] ?? date('Y-m-d H:i:s');
        $maturityDate   = $data['maturity_date'];
        $firstMonthDate = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($createdAt)));

        // ------------------------------------------------------------------
        // COMBO SPLITS THE PRINCIPAL.
        // Each rate applies to its OWN HALF, never to the whole stake. Applying
        // both to the full principal promised exactly 2x on every combo stake:
        // a 100,000 / 5y at 400% + 3%/mo paid 680,000 instead of 340,000.
        //
        // The split comes from staking_plans.combo_fixed_pct / combo_regular_pct
        // (admin-configurable, validated to total 100%) and is SNAPSHOTTED onto
        // the record, so a later admin edit can never re-price a live stake.
        // ------------------------------------------------------------------
        list($comboFixedPct, $comboRegularPct) = $this->_comboSplit();

        if ($planType === 'combo') {
            $fixedPrincipal   = $principal * ($comboFixedPct / 100);
            $regularPrincipal = $principal * ($comboRegularPct / 100);
        } elseif ($planType === 'fixed') {
            $fixedPrincipal = $principal; $regularPrincipal = 0;
        } else { // regular
            $fixedPrincipal = 0; $regularPrincipal = $principal;
        }

        // Each component earns off its own principal.
        $monthlyAmount = $regularPrincipal * ($monthlyPct / 100);
        $fixedAmount   = $fixedPrincipal * ($fixedPct / 100);

        if ($planType === 'fixed') {
            // Unchanged: fixed% is ROI, and the principal is returned on top.
            $roiRate  = $fixedPct;
            $totalROI = $fixedAmount;
            $principalReturn = $principal;
        } elseif ($planType === 'regular') {
            // Unchanged: monthly ROI for the term, principal returned at maturity.
            $roiRate  = $monthlyPct;
            $totalROI = $monthlyAmount * $months;
            $principalReturn = $principal;
        } else { // combo
            $roiRate  = $monthlyPct;
            $totalROI = $monthlyAmount * $months + $fixedAmount;
            // The fixed half's payout is GROSS — principal x fixed% is the whole
            // return for that half (400% = 4x your money back, principal
            // included), so only the REGULAR half's principal comes back.
            // 90,000 + 200,000 + 50,000 = 340,000 on the 100,000 example.
            $principalReturn = $regularPrincipal;
        }

        $recordData = [
            'staking_swap_orders_id' => $stakingOrderId ?: null,
            'user_id' => $userId,
            'ref' => $orderRef . '-ROI',
            'plan_type' => $planType,
            'principal_amount' => $principal,
            'fixed_principal' => $fixedPrincipal,
            'regular_principal' => $regularPrincipal,
            'principal_return_amount' => $principalReturn,
            'roi_rate_percent' => $roiRate,
            'total_roi_amount' => $totalROI,
            'duration_years' => $durationYears,
            'remaining_to_pay' => $totalROI,
            'total_paid_amount' => 0,
            'overall_status' => 'active',
            // Presentation badge only (see Lendingcontroller::getRecentStakingActivityForView()'s
            // docblock on why user_stakes.is_special is the honest source for
            // history) — this mirror is what the order-detail popup reads.
            // Caller is responsible for passing the package's CURRENT flag in.
            'is_special' => (int)!!($data['is_special'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        switch ($planType) {
            case 'fixed':
                $recordData['fixed_payment_amount'] = $fixedAmount;
                $recordData['fixed_maturity_date']  = $maturityDate;
                $recordData['fixed_status']         = 'pending';
                $recordData['next_payment_date']    = $maturityDate;
                break;

            case 'regular':
                $recordData['regular_payment_amount']     = $monthlyAmount;
                $recordData['regular_payment_count']      = $months;   // total monthly credits
                $recordData['regular_payments_completed'] = 0;
                $recordData['fixed_maturity_date']        = $maturityDate; // principal return at maturity
                $recordData['next_payment_date']          = $firstMonthDate;
                break;

            case 'combo':
                $recordData['combo_fixed_pct']            = $comboFixedPct;
                $recordData['combo_regular_pct']          = $comboRegularPct;
                $recordData['regular_payment_amount']     = $monthlyAmount;
                $recordData['regular_payment_count']      = $months;
                $recordData['regular_payments_completed'] = 0;
                $recordData['fixed_payment_amount']       = $fixedAmount; // gross lump at maturity
                $recordData['fixed_maturity_date']        = $maturityDate;
                $recordData['fixed_status']               = 'pending';
                $recordData['next_payment_date']          = $firstMonthDate;
                break;
        }

        // Per-day crediting opt-in: only when the plan has real configured
        // credit days AND the column exists (defensive — matches the
        // field_exists guard pattern used elsewhere for a pending migration).
        // Falls back to 'flat' (the only behavior that existed before this
        // shipped) whenever a plan has no days configured, so an admin who
        // hasn't set credit_days yet never accidentally breaks new purchases.
        if (in_array($planType, ['regular', 'combo'], true) && $this->db->field_exists('credit_mode', $this->table)) {
            $creditDays = $this->creditDaysFor($planType);
            $recordData['credit_mode'] = $creditDays ? 'per_day' : 'flat';
            if ($creditDays) {
                // Freeze the exact days in effect RIGHT NOW onto the record —
                // every cycle for this stake's whole life reads THIS snapshot,
                // never staking_plans.credit_days again. A later admin edit
                // must never reshape an already-active stake mid-term (same
                // rule already enforced for combo split %, ROI rate, etc.).
                if ($this->db->field_exists('credit_days_snapshot', $this->table)) {
                    $recordData['credit_days_snapshot'] = implode(',', $creditDays);
                }
                // Buying on/before the earliest configured day means that
                // day hasn't happened yet THIS month — cycle 1 opens now,
                // not a month from now. See cycleAnchorMonth()'s docblock.
                $cycle1Anchor = $this->cycleAnchorMonth($createdAt, 1, $creditDays);
                $recordData['next_payment_date'] = $this->dayInMonth($cycle1Anchor, $creditDays[0]);
            }
        }

        if ($this->db->insert($this->table, $recordData)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /* =============== Dynamic per-day ROI crediting (Regular/Combo) ===============
     * Splits a plan's monthly rate evenly across however many days the admin
     * configures (staking_plans.credit_days, e.g. "7,8,9" — any count, not
     * hardcoded), crediting on each real calendar day instead of once a month.
     * Days are snapshotted per cycle so a later admin edit never reshapes an
     * in-flight schedule. Used by RoiMonthlyDistribution_cron for records
     * whose credit_mode = 'per_day'.
     */

    /**
     * staking_plans.credit_days for 'regular'/'combo' — the LIVE, currently-
     * configured value. Only ever call this at PURCHASE time (to decide
     * credit_mode and build the one-time credit_days_snapshot) or from admin
     * preview UI. Never call this while processing an existing stake's
     * cycles — use parseCreditDays($record['credit_days_snapshot']) instead,
     * or a later admin edit here would reshape an already-active stake.
     */
    public function creditDaysFor($planType)
    {
        $row = $this->db->select('credit_days')->get_where('staking_plans', ['code' => $planType])->row_array();
        return $this->parseCreditDays($row['credit_days'] ?? null);
    }

    /** Parse a raw "7,8,9" CSV (live config OR a record's frozen credit_days_snapshot) into a sorted, deduped list of valid days (1-31). */
    public function parseCreditDays($csv)
    {
        $days = [];
        if ($csv !== null && $csv !== '') {
            foreach (explode(',', $csv) as $d) {
                $d = (int)trim($d);
                if ($d >= 1 && $d <= 31) $days[$d] = true;
            }
        }
        $days = array_keys($days);
        sort($days);
        return $days;
    }

    /** $day within $monthAnchor's month, clamped to that month's real last day (e.g. day 31 in a 30-day month -> the 30th). */
    public function dayInMonth($monthAnchor, $day)
    {
        $ts = strtotime($monthAnchor);
        $lastDay = (int)date('t', $ts);
        $day = max(1, min((int)$day, $lastDay));
        return date('Y-m-', $ts) . str_pad((string)$day, 2, '0', STR_PAD_LEFT) . ' ' . date('H:i:s', $ts);
    }

    /**
     * Which month cycle #$cycleNo's day-rows belong to.
     *
     * If the purchase happened ON OR BEFORE the plan's earliest configured
     * credit day (e.g. buy on the 5th or 7th, days=7,8,9 — the 7th hasn't
     * passed yet), cycle 1 opens in the PURCHASE month itself: buy on the
     * 7th and you get day 7's credit the SAME DAY, not a month later.
     * Otherwise (bought after the earliest day already passed this month —
     * including bought partway through the set, e.g. on the 8th, or well
     * after, e.g. the 20th) cycle 1 waits for next month, same as before
     * this existed — there is no partial/prorated first cycle; a day that's
     * already passed this month is never retroactively credited, and the
     * next cycle always uses the FULL configured day set, never a subset.
     * Every cycle after the first advances by exactly one month from
     * whichever month cycle 1 landed in.
     */
    public function cycleAnchorMonth($createdAt, $cycleNo, array $days)
    {
        $cycleNo = (int)$cycleNo;
        if (!$days) return date('Y-m-d H:i:s', strtotime('+' . $cycleNo . ' months', strtotime($createdAt)));
        $ts = strtotime($createdAt);
        $purchaseDay = (int)date('j', $ts);
        $startsSameMonth = $purchaseDay <= min($days);
        $monthOffset = $startsSameMonth ? ($cycleNo - 1) : $cycleNo;
        // Step months from the 1st, never from the purchase's own day-of-month:
        // strtotime('+1 month') on Aug 31 lands on Oct 1 ("Sep 31" overflows),
        // silently skipping September for month-end purchases. The returned
        // DAY is meaningless anyway — every caller feeds this to dayInMonth(),
        // which keeps only the month + time and re-applies the real configured
        // day, clamped to that month's length.
        $base = strtotime(date('Y-m-', $ts) . '01 ' . date('H:i:s', $ts));
        return date('Y-m-d H:i:s', strtotime('+' . $monthOffset . ' months', $base));
    }

    /**
     * The month cycle #$cycleNo should actually OPEN in, honoring the rows the
     * cycle before it really has. cycleAnchorMonth() recomputes every cycle
     * independently from created_at — correct only while every cycle on disk
     * was seeded under the same rule. A cycle 1 seeded under the retired
     * "always next month" rule (or hand-moved in testing) breaks that: e.g.
     * bought Aug 7 with days 7,8,9, legacy cycle 1 sits in September, and the
     * moment it completes cycleAnchorMonth() puts cycle 2 in September TOO —
     * two full months of ROI credited in one calendar month. Anchoring cycle N
     * to (cycle N-1's real month) + 1 enforces the contract cycleAnchorMonth()
     * already documents — "every cycle after the first advances by exactly one
     * month from whichever month cycle 1 landed in" — against the rows that
     * actually exist. No prior-cycle rows (cycle 1, or a gap) → falls back to
     * cycleAnchorMonth(), i.e. behaves exactly as before.
     */
    public function cycleOpenAnchor($roiRecordId, $cycleNo, $createdAt, array $days)
    {
        $cycleNo = (int)$cycleNo;
        if ($cycleNo > 1) {
            $prev = $this->db->select('scheduled_date')
                ->where('roi_staking_management_id', (int)$roiRecordId)
                ->where('cycle_no', $cycleNo - 1)
                ->order_by('day_of_month', 'ASC')->limit(1)
                ->get('roi_regular_payment_days')->row_array();
            if ($prev && !empty($prev['scheduled_date'])) {
                $ts = strtotime($prev['scheduled_date']);
                $base = strtotime(date('Y-m-', $ts) . '01 ' . date('H:i:s', $ts));
                return date('Y-m-d H:i:s', strtotime('+1 month', $base));
            }
        }
        return $this->cycleAnchorMonth($createdAt, $cycleNo, $days);
    }

    /**
     * Ensure roi_regular_payment_days rows exist for one cycle of a per_day
     * record — creates them the first time this cycle is touched, using the
     * days FROZEN onto the record at purchase time ($creditDaysCsv, i.e.
     * $record['credit_days_snapshot']) — NEVER a live staking_plans lookup,
     * or an admin editing credit_days mid-term would reshape an already-
     * active stake's remaining cycles. INSERT IGNORE + the table's
     * UNIQUE(record,cycle,day) key make this safe under a concurrent/retried
     * call — matches the idempotency pattern used elsewhere in this codebase
     * (rank_rewards, user_notifications) rather than relying on a run-lock.
     * No-ops (silently) if the snapshot is empty — shouldn't happen for a
     * credit_mode='per_day' record, but failing open (no crash, no payment)
     * is safer than failing closed on live money code.
     */
    public function openCycleDays($roiRecordId, $cycleNo, $createdAt, $creditDaysCsv, $totalAmount)
    {
        $exists = $this->db->where('roi_staking_management_id', (int)$roiRecordId)
            ->where('cycle_no', (int)$cycleNo)->count_all_results('roi_regular_payment_days');
        if ($exists > 0) return;

        $days = $this->parseCreditDays($creditDaysCsv);
        if (!$days) return;

        // Continuity-aware: cycle N opens one month after cycle N-1's REAL
        // rows, so two cycles can never share a calendar month even if cycle 1
        // was seeded under the retired next-month rule (see cycleOpenAnchor).
        $monthAnchor = $this->cycleOpenAnchor($roiRecordId, $cycleNo, $createdAt, $days);
        $n = count($days);
        $each = bcdiv((string)$totalAmount, (string)$n, 8);
        $running = '0';
        $rows = [];
        foreach ($days as $i => $day) {
            // Last day absorbs the rounding remainder so the cycle's rows
            // always sum EXACTLY to $totalAmount — never a fraction short.
            $amt = ($i === $n - 1) ? bcsub((string)$totalAmount, $running, 8) : $each;
            $running = bcadd($running, $amt, 8);
            $rows[] = [
                (int)$roiRecordId, (int)$cycleNo, (int)$day,
                $this->db->escape($this->dayInMonth($monthAnchor, $day)), $amt,
            ];
        }
        $values = implode(',', array_map(function ($r) {
            return '(' . $r[0] . ',' . $r[1] . ',' . $r[2] . ',' . $r[3] . ',' . $r[4] . ",'pending')";
        }, $rows));
        $this->db->query(
            "INSERT IGNORE INTO roi_regular_payment_days
                (roi_staking_management_id, cycle_no, day_of_month, scheduled_date, amount, status)
             VALUES {$values}"
        );
    }

    /** Pending day-rows for one cycle whose scheduled_date has arrived. */
    public function pendingDueDays($roiRecordId, $cycleNo, $now)
    {
        return $this->db->where('roi_staking_management_id', (int)$roiRecordId)
            ->where('cycle_no', (int)$cycleNo)->where('status', 'pending')
            ->where('scheduled_date <=', $now)
            ->order_by('day_of_month', 'ASC')->get('roi_regular_payment_days')->result_array();
    }

    /** Whether any pending (not-yet-due-or-not-yet-credited) rows remain for a cycle. */
    public function remainingInCycle($roiRecordId, $cycleNo)
    {
        return (int)$this->db->where('roi_staking_management_id', (int)$roiRecordId)
            ->where('cycle_no', (int)$cycleNo)->where('status', 'pending')
            ->count_all_results('roi_regular_payment_days');
    }

    public function markDayPaid($dayRowId, $txHash)
    {
        $this->db->where('id', (int)$dayRowId)->update('roi_regular_payment_days', [
            'status' => 'completed', 'paid_date' => date('Y-m-d H:i:s'), 'tx_hash' => $txHash,
        ]);
    }

    /**
     * Create a SPECIAL OFFER ROI record — escalating monthly ROI + a maturity
     * bonus at term end.
     *
     * Money model (confirmed):
     *   - Each month during staking-year k (k = 1..N) credits
     *     principal × monthly_roi[k]% to the Exchange wallet.
     *   - At maturity (end of year N): a lump of principal × maturity[N]% +
     *     the principal is returned to the Staking wallet.
     *   - The 25% instant package bonus is handled elsewhere and is unchanged.
     *
     * The per-year ramp is SNAPSHOTTED (special_schedule_json) so a later admin
     * edit to the Special ROI structure can never re-price a live stake. It is
     * stored as plan_type='regular' + is_special=1 so the existing monthly and
     * maturity crons pick it up; the monthly cron reads the snapshot to vary the
     * amount per year, and the maturity cron pays fixed_payment_amount (the
     * maturity lump) + principal exactly as it does for a fixed/combo lump.
     *
     * @param array $data principal_amount, duration_years, maturity_date,
     *                     created_at, schedule (['1'=>pct,...] year→monthly %),
     *                     maturity_percent
     *
     * LEGACY — NO LONGER CALLED. The Special ROI engine was retired: `is_special`
     * is now only a badge on a package, and every purchase goes through
     * createROIRecord() on the normal fixed/regular/combo matrix. This method is
     * kept because the rows it already wrote are still live — the monthly cron
     * continues to read their special_schedule_json snapshot, so this is the
     * definition of a record shape that is still in production. Do not delete it
     * while any is_special=1 row remains in roi_staking_management.
     */
    public function createSpecialROIRecord($stakingOrderId, $userId, $orderRef, $data)
    {
        $principal     = (float) $data['principal_amount'];
        $durationYears = max(1, (int) $data['duration_years']);
        $months        = $durationYears * 12;
        $createdAt     = $data['created_at'] ?? date('Y-m-d H:i:s');
        $maturityDate  = $data['maturity_date'];
        $firstMonth    = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($createdAt)));

        // Normalise the year→monthly-% ramp to exactly the chosen term (1..N),
        // filling any gap with the nearest lower configured year so a month can
        // never fall back to 0.
        $schedule = [];
        $lastPct = 0.0;
        for ($y = 1; $y <= $durationYears; $y++) {
            $pct = isset($data['schedule'][$y]) ? (float) $data['schedule'][$y]
                 : (isset($data['schedule'][(string) $y]) ? (float) $data['schedule'][(string) $y] : $lastPct);
            $schedule[$y] = $pct;
            $lastPct = $pct;
        }

        // Total ROI = every month's escalating credit + the maturity lump.
        $totalMonthly = 0.0;
        foreach ($schedule as $pct) {
            $totalMonthly += $principal * ($pct / 100) * 12;
        }
        $maturityPct = (float) ($data['maturity_percent'] ?? 0);
        $maturityAmt = $principal * ($maturityPct / 100);
        $totalROI    = $totalMonthly + $maturityAmt;

        // Year-1 monthly (first credit + a sensible default for any legacy reader).
        $firstMonthlyAmt = $principal * (($schedule[1] ?? 0) / 100);

        $recordData = [
            'staking_swap_orders_id'     => $stakingOrderId,
            'user_id'                    => $userId,
            'ref'                        => $orderRef . '-ROI',
            'plan_type'                  => 'regular',   // monthly-driven; is_special toggles the ramp
            'is_special'                 => 1,
            'special_maturity_percent'   => $maturityPct,
            'special_schedule_json'      => json_encode($schedule),
            'principal_amount'           => $principal,
            'regular_principal'          => $principal,
            'principal_return_amount'    => $principal,
            'roi_rate_percent'           => $schedule[1] ?? 0,
            'total_roi_amount'           => $totalROI,
            'duration_years'             => $durationYears,
            'remaining_to_pay'           => $totalROI,
            'total_paid_amount'          => 0,
            'overall_status'             => 'active',
            'regular_payment_amount'     => $firstMonthlyAmt,
            'regular_payment_count'      => $months,
            'regular_payments_completed' => 0,
            'next_payment_date'          => $firstMonth,
            // Maturity lump paid by the maturity cron once all months are done.
            'fixed_payment_amount'       => $maturityAmt,
            'fixed_maturity_date'        => $maturityDate,
            'fixed_status'               => 'pending',
            'created_at'                 => date('Y-m-d H:i:s'),
        ];

        if ($this->db->insert($this->table, $recordData)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Get next payment date (5th, 15th, or 25th)
     */
    private function getNextPaymentDate($day)
    {
        $today = date('d');
        $currentMonth = date('Y-m-');

        if ($today < $day) {
            return $currentMonth . str_pad($day, 2, '0', STR_PAD_LEFT);
        } else {
            return date('Y-m-', strtotime('+1 month')) . str_pad($day, 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get ROI record by staking order ID
     */
    public function getByStakingOrderId($stakingOrderId)
    {
        return $this->db->where('staking_swap_orders_id', $stakingOrderId)
                        ->get($this->table)
                        ->row_array();
    }

    /**
     * Get ROI record by user and date range
     */
    public function getByUserAndDate($userId, $fromDate, $toDate)
    {
        return $this->db->where('user_id', $userId)
                        ->where('created_at >=', $fromDate)
                        ->where('created_at <=', $toDate)
                        ->get($this->table)
                        ->result_array();
    }

    /**
     * Get pending monthly payments for today
     */
    public function getPendingMonthlyPayments($day)
    {
        $this->db->where('plan_type !=', 'fixed')
                 ->where('overall_status !=', 'completed');

        $dayColumn = 'payment_day_' . $day . '_status';

        $this->db->where($dayColumn, 'pending')
                 ->where($dayColumn . ' IS NOT NULL', NULL, FALSE);

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get pending maturity payments
     */
    public function getPendingMaturityPayments()
    {
        return $this->db->where('plan_type !=', 'regular')
                        ->where('fixed_status', 'pending')
                        ->where('fixed_maturity_date <=', date('Y-m-d H:i:s'))
                        ->get($this->table)
                        ->result_array();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($recordId, $paymentDay, $status, $txHash = null)
    {
        $updateData = [
            'payment_day_' . $paymentDay . '_status' => $status,
            'payment_day_' . $paymentDay . '_date' => date('Y-m-d H:i:s'),
        ];

        if ($txHash) {
            $updateData['payment_day_' . $paymentDay . '_tx_hash'] = $txHash;
        }

        return $this->db->where('id', $recordId)
                        ->update($this->table, $updateData);
    }

    /**
     * Update fixed maturity payment status
     */
    public function updateMaturityStatus($recordId, $status, $txHash = null)
    {
        $updateData = [
            'fixed_status' => $status,
            'fixed_paid_date' => date('Y-m-d H:i:s'),
        ];

        if ($txHash) {
            $updateData['fixed_tx_hash'] = $txHash;
        }

        // Check if all payments done
        if ($status === 'completed') {
            $record = $this->db->where('id', $recordId)->get($this->table)->row_array();

            $allMonthlyDone = true;
            if ($record['plan_type'] !== 'fixed') {
                $allMonthlyDone = ($record['payment_day_5_status'] === 'completed' &&
                                  $record['payment_day_15_status'] === 'completed' &&
                                  $record['payment_day_25_status'] === 'completed');
            }

            if ($allMonthlyDone) {
                $updateData['overall_status'] = 'completed';
            }
        }

        return $this->db->where('id', $recordId)
                        ->update($this->table, $updateData);
    }

    /**
     * Update total paid amount
     */
    public function updateTotalPaid($recordId, $amount)
    {
        return $this->db->where('id', $recordId)
                        ->update($this->table, [
                            'total_paid_amount' => $this->db->raw('total_paid_amount + ' . $amount),
                            'remaining_to_pay' => $this->db->raw('remaining_to_pay - ' . $amount),
                        ]);
    }

    /**
     * Calculate next payment date
     */
    public function calculateNextPayment($recordId, $planType)
    {
        $record = $this->db->where('id', $recordId)->get($this->table)->row_array();

        $today = (int)date('d');
        $daysToCheck = [5, 15, 25];

        foreach ($daysToCheck as $day) {
            $statusColumn = 'payment_day_' . $day . '_status';
            if ($record[$statusColumn] === 'pending') {
                $nextDate = $this->getNextPaymentDate($day);
                $this->db->where('id', $recordId)
                         ->update($this->table, ['next_payment_date' => $nextDate]);
                return $nextDate;
            }
        }

        // All monthly done, check maturity
        if ($record['plan_type'] !== 'fixed' && $record['fixed_status'] === 'pending') {
            return $record['fixed_maturity_date'];
        }

        return null;
    }
}
?>
