<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankcalculator_model — RankCalculatorService.
 * ----------------------------------------------------------------------------
 * The measurement layer behind the rank systems. Answers three questions:
 *
 *   calculateGroupVolume($user_id [, $from, $to])
 *       → ['left_volume','right_volume','total_volume']
 *       Completed staking BMAN from the ENTIRE downline. Own staking excluded.
 *       Source: staking_swap_orders WHERE status IN ('swap_completed','completed')
 *                                     AND cron_status='completed'
 *       (the engine writes 'swap_completed' — see $done_status)
 *       Amount: bman_amount. Everything else (cancelled/failed/pending/
 *       rejected/expired/refunded) is excluded by the status pair above.
 *       Passing $from/$to windows the volume — that is how Rank Power (§11)
 *       counts current-cycle-only business off the same code path.
 *       USED ONLY BY Rank Power as of the rank-volume-source fix
 *       (db/2026-07-27_rank_volume_source_fix.sql) — the permanent
 *       Achievement Rank no longer calls this.
 *
 *   calculateBonusVolume($user_id [, $from, $to])
 *       → ['earning_volume','staking_volume','total_volume']
 *       THE PERMANENT ACHIEVEMENT RANK'S VOLUME GATE. A PERSONAL metric, not
 *       a downline/tree measurement: this member's own lifetime Binary
 *       Matching Bonus — SUM(earning_amount)+SUM(staking_amount) from
 *       staking_matching_payouts for this user_id. Written only by
 *       Stakingmatching_model::payMatching(), so it already reflects the
 *       matching conversion rate, the min(left,right) leg-balance
 *       requirement, and this member's own package-ceiling cap.
 *
 *   countQualifiedRanks($user_id, $side, $rank_tier)
 *       → how many members in that whole leg hold >= $rank_tier.
 *       Unlimited depth, not just direct referrals. Independent of volume —
 *       unaffected by the rank-volume-source fix.
 *
 * PERFORMANCE
 * The genealogy (binary_placement), the per-user volume totals and the per-user
 * achieved ranks are each loaded ONCE per run into memory maps, then every
 * user's legs are walked in PHP (BFS). A cron batch of 500 users therefore
 * costs 3 queries, not 500 recursive ones. Call prime() before a batch;
 * a single-user call primes lazily.
 *
 * Tier semantics: a member holding a HIGHER rank satisfies a lower-rank
 * requirement — "2 GOLD on the left" is met by 2 DIAMONDs. Counting is
 * therefore tier >= required_tier.
 */
class Rankcalculator_model extends CI_Model
{
    /**
     * Swap-order statuses that mean "the stake actually completed".
     *
     * CRITICAL — the engine writes 'swap_completed', NOT 'completed'.
     * Swapengine_model / StakingSwap_model only ever set: active,
     * failed_credit, pending_gas_fee, pending_usdt, pending_bman,
     * swap_completed. The live table contains only 'swap_completed' and
     * 'cancelled'. The comment at db/staking_swap.sql:47 promising
     * "created → usdt_sent → bman_sent → completed" is stale, and the original
     * spec inherited it — so filtering on status='completed' matched ZERO rows
     * and every member's group volume computed as 0, meaning the achievement
     * cron could never promote anyone.
     *
     * Both spellings are accepted so this survives the engine ever being
     * aligned to the documented state machine.
     */
    public static $done_status = ['swap_completed', 'completed'];

    /** @var array<int,array{left:int|null,right:int|null}> parent_id => leg roots */
    private $legs = null;
    /** @var array<int,int[]> parent_id => [child_id, …] */
    private $children = null;
    /** @var array<int,string> user_id => lifetime completed BMAN volume */
    private $volume = null;
    /** @var array<int,int> user_id => highest tier_level achieved */
    private $tier = null;
    /** @var array<int,int> user_id => depth from root */
    private $depth = null;
    /** cache key of the currently primed windowed volume map */
    private $volume_window = null;

    /* ====================== priming (one query each) ====================== */

    /**
     * Load the genealogy into memory. Idempotent.
     */
    public function primeTree()
    {
        if ($this->children !== null) return;

        $this->children = [];
        $this->legs     = [];

        $rows = $this->db->select('user_id, parent_id, position')
                         ->get('binary_placement')->result_array();
        foreach ($rows as $r) {
            $uid = (int)$r['user_id'];
            $pid = (int)$r['parent_id'];
            if ($uid <= 0 || $pid <= 0 || $uid === $pid) continue;

            $this->children[$pid][] = $uid;
            $side = ($r['position'] === 'right') ? 'right' : 'left';
            // First placement wins if the data ever holds two same-side children.
            if (!isset($this->legs[$pid][$side])) $this->legs[$pid][$side] = $uid;
        }
    }

    /**
     * Load completed staking volume per user. $from/$to (DATETIME strings, or
     * null) window it for Rank Power. Re-primes when the window changes.
     */
    public function primeVolume($from = null, $to = null)
    {
        $key = (string)$from . '|' . (string)$to;
        if ($this->volume !== null && $this->volume_window === $key) return;

        $this->volume        = [];
        $this->volume_window = $key;

        $this->db->select('user_id, SUM(bman_amount) AS vol')
                 ->from('staking_swap_orders')
                 ->where_in('status', self::$done_status)
                 ->where('cron_status', 'completed');
        // Window on updated_at — the moment the order reached completed.
        if ($from) $this->db->where('updated_at >=', $from);
        if ($to)   $this->db->where('updated_at <=', $to);
        $rows = $this->db->group_by('user_id')->get()->result_array();

        foreach ($rows as $r) {
            $this->volume[(int)$r['user_id']] = (string)$r['vol'];
        }
    }

    /**
     * Load each user's highest permanent tier. Users with no user_ranks row are
     * unranked and count as no tier at all (not tier 0 — UN RANK is an achieved
     * rank like any other and must be earned).
     */
    public function primeTiers()
    {
        if ($this->tier !== null) return;

        $this->tier = [];
        $rows = $this->db->select('ur.user_id, r.tier_level')
                         ->from('user_ranks ur')
                         ->join('staking_ranks r', 'r.id = ur.highest_rank_id', 'inner')
                         ->get()->result_array();
        foreach ($rows as $r) {
            $this->tier[(int)$r['user_id']] = (int)$r['tier_level'];
        }
    }

    /** Prime everything for a batch run. */
    public function prime($from = null, $to = null)
    {
        $this->primeTree();
        $this->primeVolume($from, $to);
        $this->primeTiers();
    }

    /**
     * Drop cached state. The achievement engine calls this after a promotion so
     * that an upline processed later in the same pass sees the new tier.
     */
    public function flushTiers()
    {
        $this->tier = null;
    }

    /** Force a full re-prime (used between cron batches). */
    public function reset()
    {
        $this->legs = $this->children = $this->volume = $this->tier = $this->depth = null;
        $this->volume_window = null;
    }

    /* ========================= tree traversal ============================ */

    /**
     * Every descendant of $root, unlimited depth, BFS. $root itself excluded.
     * Cycle-guarded: corrupt placement data can never loop forever.
     *
     * @return int[] user ids
     */
    public function descendants($root)
    {
        $this->primeTree();
        $root = (int)$root;
        if ($root <= 0) return [];

        $out   = [];
        $seen  = [$root => true];
        $queue = [$root];

        while ($queue) {
            $node = array_shift($queue);
            if (empty($this->children[$node])) continue;
            foreach ($this->children[$node] as $child) {
                if (isset($seen[$child])) continue;   // cycle / duplicate guard
                $seen[$child] = true;
                $out[]  = $child;
                $queue[] = $child;
            }
        }
        return $out;
    }

    /**
     * The whole leg on one side of $user_id: the direct $side child plus all of
     * ITS descendants. Empty when the leg is unfilled.
     *
     * @return int[]
     */
    public function legMembers($user_id, $side)
    {
        $this->primeTree();
        $user_id = (int)$user_id;
        $side    = ($side === 'right') ? 'right' : 'left';

        if (empty($this->legs[$user_id][$side])) return [];
        $root = (int)$this->legs[$user_id][$side];
        return array_merge([$root], $this->descendants($root));
    }

    /** Depth of every user from the tree root — used to order the cron bottom-up. */
    public function depthMap()
    {
        if ($this->depth !== null) return $this->depth;
        $this->primeTree();

        // Roots = users that are someone's parent but nobody's child, plus any
        // parent id never seen as a child.
        $isChild = [];
        foreach ($this->children as $pid => $kids) {
            foreach ($kids as $k) $isChild[$k] = true;
        }
        $roots = [];
        foreach (array_keys($this->children) as $pid) {
            if (!isset($isChild[$pid])) $roots[] = (int)$pid;
        }

        $this->depth = [];
        $queue = [];
        foreach ($roots as $r) { $this->depth[$r] = 0; $queue[] = $r; }

        while ($queue) {
            $node = array_shift($queue);
            if (empty($this->children[$node])) continue;
            foreach ($this->children[$node] as $child) {
                if (isset($this->depth[$child])) continue;
                $this->depth[$child] = $this->depth[$node] + 1;
                $queue[] = $child;
            }
        }
        return $this->depth;
    }

    /* ======================== group volume (§10) ========================= */

    /**
     * Downline-only completed staking volume, split by leg.
     * The user's OWN staking is never included — we sum the leg members only,
     * and $user_id is never a member of its own legs.
     *
     * @param int         $user_id
     * @param string|null $from  DATETIME lower bound (Rank Power cycle start)
     * @param string|null $to    DATETIME upper bound (Rank Power cycle end)
     * @return array{left_volume:string,right_volume:string,total_volume:string}
     */
    public function calculateGroupVolume($user_id, $from = null, $to = null)
    {
        $this->primeTree();
        $this->primeVolume($from, $to);

        $left  = $this->_sumVolume($this->legMembers($user_id, 'left'));
        $right = $this->_sumVolume($this->legMembers($user_id, 'right'));

        return [
            'left_volume'  => $left,
            'right_volume' => $right,
            'total_volume' => bcadd($left, $right, 8),
        ];
    }

    private function _sumVolume(array $members)
    {
        $sum = '0';
        foreach ($members as $uid) {
            if (isset($this->volume[$uid])) {
                $sum = bcadd($sum, $this->volume[$uid], 8);
            }
        }
        return $sum;
    }

    /* ===================== bonus volume (rank-volume fix) ================= */

    /**
     * Lifetime Binary Matching Bonus for a member's OWN user_id — the
     * REPLACEMENT volume source for the permanent Achievement Rank.
     *
     * Unlike calculateGroupVolume() this is NOT a downline/tree walk: it is a
     * PERSONAL metric — what this member has actually been paid by
     * Stakingmatching_model::payMatching(), which already encodes the
     * matching conversion rate, the min(left,right) leg-balance requirement,
     * and this member's own package-ceiling cap. staking_matching_payouts is
     * written ONLY by the matching engine (never by rank rewards or any other
     * credit source), so summing it can never double-count with anything
     * else that also touches the earning/staking wallets.
     *
     * $from/$to (DATETIME strings, or null) window on created_at — kept for
     * symmetry with calculateGroupVolume() and any future reporting need.
     * The achievement engine always calls this with no window (lifetime).
     *
     * @param int         $user_id
     * @param string|null $from  DATETIME lower bound (created_at >=)
     * @param string|null $to    DATETIME upper bound (created_at <=)
     * @return array{earning_volume:string,staking_volume:string,total_volume:string}
     */
    public function calculateBonusVolume($user_id, $from = null, $to = null)
    {
        $this->db->select(
                "COALESCE(SUM(earning_amount), 0) AS earning_volume,
                 COALESCE(SUM(staking_amount), 0) AS staking_volume", false
            )
            ->from('staking_matching_payouts')
            ->where('user_id', (int)$user_id);
        if ($from) $this->db->where('created_at >=', $from);
        if ($to)   $this->db->where('created_at <=', $to);
        $row = $this->db->get()->row_array();

        $earning = $row ? (string)$row['earning_volume'] : '0';
        $staking = $row ? (string)$row['staking_volume'] : '0';

        return [
            'earning_volume' => $earning,
            'staking_volume' => $staking,
            'total_volume'   => bcadd($earning, $staking, 8),
        ];
    }

    /* ===================== binary rank counting (§10) ==================== */

    /**
     * How many members of one leg hold at least $rank_tier. Whole leg,
     * unlimited depth. A higher rank satisfies a lower requirement.
     *
     * @param int $user_id
     * @param string $side  'left' | 'right'
     * @param int $rank_tier  tier_level required
     * @return int
     */
    public function countQualifiedRanks($user_id, $side, $rank_tier)
    {
        $this->primeTiers();
        $rank_tier = (int)$rank_tier;

        $n = 0;
        foreach ($this->legMembers($user_id, $side) as $uid) {
            if (isset($this->tier[$uid]) && $this->tier[$uid] >= $rank_tier) $n++;
        }
        return $n;
    }

    /**
     * Both legs at once — one walk per side instead of one per requirement row.
     * Returns ['left' => [tier => count, …], 'right' => [...]] as cumulative
     * counts: ['left'][4] is how many left-leg members are GOLD *or better*.
     */
    public function tierCounts($user_id)
    {
        $this->primeTiers();
        $out = ['left' => array_fill(0, 11, 0), 'right' => array_fill(0, 11, 0)];

        foreach (['left','right'] as $side) {
            foreach ($this->legMembers($user_id, $side) as $uid) {
                if (!isset($this->tier[$uid])) continue;
                $t = $this->tier[$uid];
                // cumulative: a tier-7 member counts toward every tier 0..7
                for ($i = 0; $i <= $t && $i <= 10; $i++) $out[$side][$i]++;
            }
        }
        return $out;
    }

    /** Volume total for a single user (their own staking) — reporting helper. */
    public function ownVolume($user_id, $from = null, $to = null)
    {
        $this->primeVolume($from, $to);
        return isset($this->volume[(int)$user_id]) ? $this->volume[(int)$user_id] : '0';
    }
}
