<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matchingoverflow_model — the Admin's share of binary matching.
 * ---------------------------------------------------------------------------
 * Every BMAN the level engine calculates but does NOT pay to the sponsor ends
 * up here. There are exactly two ways that happens, and they are very
 * different things, so this model never merges them into one "admin income"
 * number:
 *
 *   over_ceiling  the sponsor was paid, but their level bonus exceeded the
 *                 Group Incentive Ceiling of their highest eligible package.
 *                 Only the excess is Admin's. This is the designed outcome of
 *                 the ceiling rule.
 *   forfeited     the sponsor held NO eligible staking package when the level
 *                 completed, so the whole bonus is Admin's. This is a real
 *                 business rule, but a rising number here means members are
 *                 completing levels while unstaked — worth an admin's
 *                 attention, not just an income line.
 *
 * Deliberately NOT counted as overflow: a level blocked by a broken ceiling
 * configuration. Those pay nobody — not the member, not Admin — and stay open
 * until the config is fixed (see Binarylevelmatching_model::_payLevel). They
 * appear on the Blocked Levels panel of Distribution History instead.
 *
 * TWO INDEPENDENT RECORDS OF THE SAME MONEY, deliberately:
 *   staking_matching_payouts.admin_overflow — what the engine CALCULATED,
 *       per level, with full context (volumes, raw bonus, ceiling, package).
 *   admin_wallet_ledger (binary_matching_overflow) — what actually LANDED in
 *       the admin wallet, with a running balance_after.
 * Both are written inside the same transaction, so they must always agree.
 * reconciliation() compares them precisely because they are written
 * separately: a divergence means a partial commit or an out-of-band edit, and
 * an admin should see that immediately rather than trust a single figure.
 *
 * Read-only. Nothing here moves money.
 */
class Matchingoverflow_model extends CI_Model
{
    /** Where the overflow physically sits — it never leaves the treasury. */
    public function custodyNote()
    {
        $cfg = $this->db->select("COALESCE(NULLIF(bonus_wallet,''), treasury_wallet) AS admin_addr, treasury_wallet", false)
                        ->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        return [
            'admin_address'    => $cfg['admin_addr'] ?? null,
            'treasury_address' => $cfg['treasury_wallet'] ?? null,
            'same_wallet'      => !empty($cfg['admin_addr']) && !empty($cfg['treasury_wallet'])
                                  && strtolower($cfg['admin_addr']) === strtolower($cfg['treasury_wallet']),
        ];
    }

    /** Headline figures, split by reason and by period. */
    public function summary()
    {
        $r = $this->db->query(
            "SELECT
               COALESCE(SUM(admin_overflow),0)                                            AS total,
               COALESCE(SUM(CASE WHEN sponsor_eligible = 1 THEN admin_overflow END),0)     AS over_ceiling,
               COALESCE(SUM(CASE WHEN sponsor_eligible = 0 THEN admin_overflow END),0)     AS forfeited,
               COUNT(CASE WHEN admin_overflow > 0 THEN 1 END)                              AS events,
               COUNT(DISTINCT CASE WHEN admin_overflow > 0 THEN user_id END)               AS sponsors,
               COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN admin_overflow END),0) AS today,
               COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01') THEN admin_overflow END),0) AS this_month,
               COALESCE(SUM(raw_bonus),0)                                                  AS raw_bonus_all,
               COALESCE(SUM(earning_amount + staking_amount),0)                            AS members_paid_all
             FROM staking_matching_payouts"
        )->row_array();

        $total = (float)$r['total'];
        $raw   = (float)$r['raw_bonus_all'];

        return [
            'total'          => round($total, 4),
            'over_ceiling'   => round((float)$r['over_ceiling'], 4),
            'forfeited'      => round((float)$r['forfeited'], 4),
            'events'         => (int)$r['events'],
            'sponsors'       => (int)$r['sponsors'],
            'today'          => round((float)$r['today'], 4),
            'this_month'     => round((float)$r['this_month'], 4),
            'raw_bonus_all'  => round($raw, 4),
            'members_paid'   => round((float)$r['members_paid_all'], 4),
            // What share of everything the engine generated went to Admin
            // rather than to members. A number creeping upward usually means
            // ceilings are too low for the volume being built, not that the
            // business is earning more.
            'admin_share_pct' => $raw > 0 ? round($total / $raw * 100, 2) : 0.0,
        ];
    }

    /**
     * Do the engine's calculation and the admin wallet agree?
     *
     * Compares SUM(staking_matching_payouts.admin_overflow) against
     * SUM(admin_wallet_ledger.credit) for binary_matching_overflow. They are
     * written in the same transaction, so any gap is a genuine anomaly.
     * Also reports the admin wallet's TOTAL balance, which legitimately
     * includes other sources (e.g. bonus reduction) — labelling that clearly
     * stops the difference being mistaken for missing matching money.
     */
    public function reconciliation()
    {
        $calc = (float)($this->db->query(
            "SELECT COALESCE(SUM(admin_overflow),0) s FROM staking_matching_payouts"
        )->row_array()['s'] ?? 0);

        $led = $this->db->query(
            "SELECT COALESCE(SUM(credit),0) s, COUNT(*) n FROM admin_wallet_ledger
              WHERE reference_type = 'binary_matching_overflow'"
        )->row_array();

        $wallet = $this->db->query(
            "SELECT COALESCE(balance,0) b, COALESCE(lifetime_bonus_reduction_total,0) r
               FROM admin_wallet WHERE id = 1"
        )->row_array();

        $credited = (float)($led['s'] ?? 0);
        $diff = round($calc - $credited, 4);

        return [
            'calculated'      => round($calc, 4),
            'credited'        => round($credited, 4),
            'ledger_rows'     => (int)($led['n'] ?? 0),
            'difference'      => $diff,
            'in_sync'         => abs($diff) < 0.00005,
            'wallet_balance'  => round((float)($wallet['b'] ?? 0), 4),
            'bonus_reduction' => round((float)($wallet['r'] ?? 0), 4),
            'wallet_row_missing' => $wallet === null,
        ];
    }

    /**
     * Per-level overflow detail. Driven from staking_matching_payouts because
     * that is the only place carrying WHY (volumes, raw bonus, the ceiling in
     * force, the package that set it) — admin_wallet_ledger only records the
     * amount and the running balance.
     *
     * @param array $opts reason: over_ceiling|forfeited, user_id, q (username /
     *                    referral search), from, to, limit, offset
     */
    public function ledger($opts = [])
    {
        $this->db->select(
                'smp.id, smp.user_id, smp.level, smp.matched_volume, smp.left_before, smp.right_before, '
              . 'smp.total_percent, smp.raw_bonus, smp.earning_amount, smp.staking_amount, '
              . 'smp.ceiling_applied, smp.admin_overflow, smp.sponsor_eligible, smp.highest_package_id, '
              . 'smp.run_ref, smp.created_at, u.username, u.referral_id, u.email, '
              . 'sp.name AS package_name, sp.stake_amount AS package_stake, sp.group_ceiling AS package_ceiling'
            )
            ->from('staking_matching_payouts smp')
            ->join('users u', 'u.id = smp.user_id', 'left')
            ->join('staking_packages sp', 'sp.id = smp.highest_package_id', 'left')
            ->where('smp.admin_overflow >', 0);

        if (!empty($opts['reason']) && $opts['reason'] === 'over_ceiling') $this->db->where('smp.sponsor_eligible', 1);
        if (!empty($opts['reason']) && $opts['reason'] === 'forfeited')    $this->db->where('smp.sponsor_eligible', 0);
        if (!empty($opts['user_id'])) $this->db->where('smp.user_id', (int)$opts['user_id']);
        if (!empty($opts['from']))    $this->db->where('smp.created_at >=', $opts['from'] . ' 00:00:00');
        if (!empty($opts['to']))      $this->db->where('smp.created_at <=', $opts['to'] . ' 23:59:59');
        if (!empty($opts['q'])) {
            $q = $this->db->escape_like_str($opts['q']);
            $this->db->where("(u.username LIKE '%{$q}%' OR u.referral_id LIKE '%{$q}%' OR u.email LIKE '%{$q}%')", null, false);
        }

        $this->db->order_by('smp.id', 'DESC')
                 ->limit((int)($opts['limit'] ?? 300), (int)($opts['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    /** Sponsors generating the most overflow — where the ceiling bites hardest. */
    public function topSponsors($limit = 10)
    {
        return $this->db->query(
            "SELECT smp.user_id, u.username, u.referral_id,
                    COUNT(*) AS events,
                    COALESCE(SUM(smp.admin_overflow),0) AS overflow,
                    COALESCE(SUM(smp.earning_amount + smp.staking_amount),0) AS member_paid,
                    MAX(smp.ceiling_applied) AS ceiling
               FROM staking_matching_payouts smp
               LEFT JOIN users u ON u.id = smp.user_id
              WHERE smp.admin_overflow > 0
              GROUP BY smp.user_id, u.username, u.referral_id
              ORDER BY overflow DESC
              LIMIT " . (int)$limit
        )->result_array();
    }

    /** The raw admin wallet credit trail, for the balance-after audit view. */
    public function walletLedger($limit = 200)
    {
        return $this->db->where('reference_type', 'binary_matching_overflow')
                        ->order_by('id', 'DESC')->limit((int)$limit)
                        ->get('admin_wallet_ledger')->result_array();
    }
}
