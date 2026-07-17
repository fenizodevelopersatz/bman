<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankreward_model — RankRewardService + certificate issuance.
 * ----------------------------------------------------------------------------
 * Turns an achieved rank into its benefits: reward payout, certificate, badge
 * (the badge is just staking_ranks.badge_image — nothing to issue) and the
 * member notification.
 *
 * PAY ONCE, EVER
 * `rank_rewards` carries UNIQUE (user_id, rank_id, reward_type) and
 * `rank_certificates` carries UNIQUE (user_id, rank_id). Those indexes — not
 * application logic — are what make a repeated cron pass harmless. The code
 * checks first for a clean skip; the index is the actual guarantee under
 * concurrency.
 *
 * RETRY, DON'T LOSE
 * A reward row is written `pending` BEFORE the money moves, then flipped to
 * `paid` with its wallet_ledger_id once the credit lands. If the process dies
 * in between, the row stays `pending` and the next pass retries the credit
 * rather than skipping it. `paid` rows are never touched again.
 *
 * Money never moves outside Walletledger_model — it owns balances, writes the
 * ledger row and updates user_wallets in one locked transaction.
 *   BMAN rewards → `earning` wallet   (same wallet as binary commission)
 *   USDT rewards → `usdt` wallet
 *   reference_type = 'rank_reward'    (already a documented ledger type)
 */
class Rankreward_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'ledger');
        $this->load->model('staking/Rankaudit_model', 'audit');
    }

    /**
     * Issue every benefit attached to $rank_id for $user_id: reward(s),
     * certificate, notification. Safe to call on every cron pass — it is the
     * self-healing path for a promotion whose payout previously failed.
     *
     * @return array{rewards:array, certificate:?string, notified:bool}
     */
    public function ensureBenefits($user_id, $rank_id)
    {
        $rank = $this->db->get_where('staking_ranks', ['id' => (int)$rank_id])->row_array();
        if (!$rank) return ['rewards' => [], 'certificate' => null, 'notified' => false];

        $out = ['rewards' => [], 'certificate' => null, 'notified' => false];

        // ---- rewards ----
        if (!empty($rank['benefit_reward'])) {
            if (bccomp((string)$rank['reward_bman'], '0', 8) > 0) {
                $out['rewards'][] = $this->_issueReward($user_id, $rank, 'bman', $rank['reward_bman']);
            }
            if (bccomp((string)$rank['reward_usdt'], '0', 8) > 0) {
                $out['rewards'][] = $this->_issueReward($user_id, $rank, 'usdt', $rank['reward_usdt']);
            }
            // A described non-cash reward (trip, gadget) is recorded pending for
            // an admin to fulfil by hand — there is nothing to credit.
            if (!empty($rank['reward_description'])) {
                $out['rewards'][] = $this->_issuePhysical($user_id, $rank);
            }
        }

        // ---- certificate ----
        if (!empty($rank['benefit_certificate'])) {
            $out['certificate'] = $this->issueCertificate($user_id, $rank);
        }

        // ---- notification (idempotent via UNIQUE ref) ----
        $out['notified'] = $this->audit->notify(
            $user_id, 'rank_achievement',
            'Rank achieved: ' . $rank['name'],
            'Congratulations! You have permanently achieved the rank of ' . $rank['name'] . '.',
            'rank', (int)$rank['id']
        );

        return $out;
    }

    /* ============================== rewards ============================= */

    /**
     * Credit one cash reward. Returns a short status array.
     * States: already_paid · paid · failed · pending
     */
    private function _issueReward($user_id, array $rank, $type, $amount)
    {
        $user_id = (int)$user_id;
        $rank_id = (int)$rank['id'];
        $amount  = (string)$amount;

        $existing = $this->db->get_where('rank_rewards', [
            'user_id' => $user_id, 'rank_id' => $rank_id, 'reward_type' => $type,
        ])->row_array();

        // Terminal states — never pay twice.
        if ($existing && in_array($existing['reward_status'], ['paid','skipped'], true)) {
            return ['type' => $type, 'status' => 'already_paid', 'id' => (int)$existing['id']];
        }

        if ($existing) {
            $reward_id = (int)$existing['id'];   // pending/failed → retry below
        } else {
            // Claim the slot BEFORE moving money. INSERT IGNORE (not try/catch —
            // CI3's driver does not throw on duplicate key) means a concurrent
            // batch racing us here inserts nothing and backs off.
            $this->db->query(
                'INSERT IGNORE INTO rank_rewards
                    (user_id, rank_id, rank_name, reward_type, reward_amount, reward_status)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$user_id, $rank_id, $rank['name'], $type, $amount, 'pending']
            );
            if ($this->db->affected_rows() < 1) {
                return ['type' => $type, 'status' => 'already_paid', 'id' => 0];
            }
            $reward_id = (int)$this->db->insert_id();
            if (!$reward_id) {
                return ['type' => $type, 'status' => 'already_paid', 'id' => 0];
            }
        }

        $wallet = ($type === 'usdt') ? 'usdt' : 'earning';
        list($ok, $res) = $this->ledger->credit($user_id, $wallet, $amount, 'rank_reward', [
            'reference_id' => $reward_id,
            'description'  => $rank['name'] . ' rank reward',
        ]);

        if (!$ok) {
            $this->db->where('id', $reward_id)->update('rank_rewards', [
                'reward_status' => 'failed',
                'note'          => substr((string)$res, 0, 255),
            ]);
            log_message('error', "[rank_reward] user {$user_id} rank {$rank_id} {$type}: {$res}");
            return ['type' => $type, 'status' => 'failed', 'id' => $reward_id, 'error' => $res];
        }

        $this->db->where('id', $reward_id)->update('rank_rewards', [
            'reward_status'    => 'paid',
            'wallet_ledger_id' => is_numeric($res) ? (int)$res : null,
            'rewarded_at'      => date('Y-m-d H:i:s'),
            'note'             => null,
        ]);

        $this->audit->log('reward_paid', [
            'user_id'   => $user_id,
            'rank_id'   => $rank_id,
            'new_value' => $amount . ' ' . strtoupper($type),
            'note'      => 'Credited to ' . $wallet . ' wallet · ledger #' . $res,
        ]);

        $this->audit->notify(
            $user_id, 'rank_reward',
            'Rank reward credited',
            $amount . ' ' . strtoupper($type) . ' credited to your ' . $wallet
                . ' wallet for achieving ' . $rank['name'] . '.',
            'rank_reward', $reward_id
        );

        return ['type' => $type, 'status' => 'paid', 'id' => $reward_id, 'amount' => $amount];
    }

    /** Record a non-cash reward for manual fulfilment. */
    private function _issuePhysical($user_id, array $rank)
    {
        $existing = $this->db->get_where('rank_rewards', [
            'user_id' => (int)$user_id, 'rank_id' => (int)$rank['id'], 'reward_type' => 'physical',
        ])->row_array();
        if ($existing) {
            return ['type' => 'physical', 'status' => 'already_paid', 'id' => (int)$existing['id']];
        }

        $this->db->query(
            'INSERT IGNORE INTO rank_rewards
                (user_id, rank_id, rank_name, reward_type, reward_amount, reward_status, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [(int)$user_id, (int)$rank['id'], $rank['name'], 'physical', 0, 'pending',
             substr((string)$rank['reward_description'], 0, 255)]
        );
        if ($this->db->affected_rows() < 1) {
            return ['type' => 'physical', 'status' => 'already_paid', 'id' => 0];
        }
        return ['type' => 'physical', 'status' => 'pending', 'id' => (int)$this->db->insert_id()];
    }

    /**
     * Admin marks a physical reward fulfilled. Cash rewards are settled by the
     * ledger and cannot be hand-flipped here.
     */
    public function markFulfilled($reward_id, $admin_id, $note = null)
    {
        $r = $this->db->get_where('rank_rewards', ['id' => (int)$reward_id])->row_array();
        if (!$r) return [false, 'Reward not found.'];
        if ($r['reward_type'] !== 'physical') {
            return [false, 'Only non-cash rewards can be marked fulfilled by hand.'];
        }
        if ($r['reward_status'] === 'paid') return [true, 'Already fulfilled.'];

        $this->db->where('id', (int)$reward_id)->update('rank_rewards', [
            'reward_status' => 'paid',
            'rewarded_at'   => date('Y-m-d H:i:s'),
            'note'          => $note !== null ? substr((string)$note, 0, 255) : $r['note'],
        ]);
        $this->audit->log('reward_paid', [
            'user_id' => (int)$r['user_id'], 'rank_id' => (int)$r['rank_id'],
            'new_value' => 'physical', 'changed_by' => (int)$admin_id,
            'note' => 'Marked fulfilled by admin.',
        ]);
        return [true, 'Reward marked fulfilled.'];
    }

    /**
     * Retry every failed cash reward. Exposed so an admin can re-run payouts
     * after topping up the treasury, without waiting for the cron.
     */
    public function retryFailed($limit = 100)
    {
        $rows = $this->db->select('rr.*, r.name, r.reward_bman, r.reward_usdt, r.benefit_reward, r.id AS rid')
                         ->from('rank_rewards rr')
                         ->join('staking_ranks r', 'r.id = rr.rank_id')
                         ->where_in('rr.reward_status', ['failed','pending'])
                         ->where_in('rr.reward_type', ['bman','usdt'])
                         ->limit((int)$limit)
                         ->get()->result_array();

        $done = 0;
        foreach ($rows as $row) {
            $rank = $this->db->get_where('staking_ranks', ['id' => (int)$row['rank_id']])->row_array();
            if (!$rank) continue;
            $res = $this->_issueReward((int)$row['user_id'], $rank, $row['reward_type'], $row['reward_amount']);
            if ($res['status'] === 'paid') $done++;
        }
        return $done;
    }

    /* =========================== certificates =========================== */

    /**
     * Issue the certificate for a rank. Returns its number, or the existing
     * number when one was already issued.
     *
     * Serials come from an atomic per-(rank, year) counter
     * (rank_certificate_series) rather than COUNT(*)+1, so the first GOLD of
     * 2026 is BMAN-GOLD-2026-000001 and two concurrent cron batches can never
     * mint the same number.
     */
    public function issueCertificate($user_id, $rank)
    {
        if (!is_array($rank)) {
            $rank = $this->db->get_where('staking_ranks', ['id' => (int)$rank])->row_array();
            if (!$rank) return null;
        }
        $user_id = (int)$user_id;
        $rank_id = (int)$rank['id'];

        $existing = $this->db->get_where('rank_certificates', [
            'user_id' => $user_id, 'rank_id' => $rank_id,
        ])->row_array();
        if ($existing) return $existing['certificate_no'];

        $year = (int)date('Y');
        $no   = $this->_nextSerial($year, $rank_id);
        if (!$no) return null;

        // BMAN-GOLD-2026-000001  (rank name squashed: "UN RANK" → "UNRANK")
        $slug = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $rank['name']));
        $cert = sprintf('BMAN-%s-%d-%06d', $slug, $year, $no);

        // INSERT IGNORE, not try/catch — CI3 does not throw on duplicate key.
        $this->db->query(
            'INSERT IGNORE INTO rank_certificates
                (certificate_no, user_id, rank_id, rank_name, generated_date)
             VALUES (?, ?, ?, ?, ?)',
            [$cert, $user_id, $rank_id, $rank['name'], date('Y-m-d')]
        );
        if ($this->db->affected_rows() < 1) {
            // Lost a race — return whatever the winner wrote. The serial we just
            // burned is simply skipped; numbers are unique, not gap-free.
            $row = $this->db->get_where('rank_certificates', [
                'user_id' => $user_id, 'rank_id' => $rank_id,
            ])->row_array();
            return $row ? $row['certificate_no'] : null;
        }
        $cert_row_id = (int)$this->db->insert_id();

        $this->audit->log('certificate_issued', [
            'user_id' => $user_id, 'rank_id' => $rank_id, 'new_value' => $cert,
        ]);
        $this->audit->notify(
            $user_id, 'rank_certificate', 'Certificate issued',
            'Your ' . $rank['name'] . ' certificate (' . $cert . ') is ready to view.',
            'rank_certificate', $cert_row_id
        );

        return $cert;
    }

    /**
     * Atomic sequence bump for one (year, rank). LAST_INSERT_ID(expr) makes the
     * new value readable on this connection without a second SELECT that another
     * session could interleave with.
     */
    private function _nextSerial($year, $rank_id)
    {
        $this->db->query(
            'INSERT INTO rank_certificate_series (`year`, `rank_id`, `last_no`)
             VALUES (?, ?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE `last_no` = LAST_INSERT_ID(`last_no` + 1)',
            [(int)$year, (int)$rank_id]
        );
        $row = $this->db->query('SELECT LAST_INSERT_ID() AS n')->row_array();
        return $row ? (int)$row['n'] : 0;
    }

    /* ============================== reads =============================== */

    public function rewards($filters = [], $limit = 100, $offset = 0)
    {
        $this->_rewardFilters($filters);
        return $this->db->select('rr.*, u.username, u.email')
                        ->from('rank_rewards rr')
                        ->join('users u', 'u.id = rr.user_id', 'left')
                        ->order_by('rr.id', 'DESC')
                        ->limit((int)$limit, (int)$offset)
                        ->get()->result_array();
    }

    public function rewardsCount($filters = [])
    {
        $this->_rewardFilters($filters);
        return $this->db->from('rank_rewards rr')->count_all_results();
    }

    private function _rewardFilters($f)
    {
        if (!empty($f['status']))  $this->db->where('rr.reward_status', $f['status']);
        if (!empty($f['type']))    $this->db->where('rr.reward_type', $f['type']);
        if (!empty($f['rank_id'])) $this->db->where('rr.rank_id', (int)$f['rank_id']);
        if (!empty($f['user_id'])) $this->db->where('rr.user_id', (int)$f['user_id']);
        if (!empty($f['from']))    $this->db->where('rr.created_at >=', $f['from'] . ' 00:00:00');
        if (!empty($f['to']))      $this->db->where('rr.created_at <=', $f['to'] . ' 23:59:59');
    }

    public function certificates($filters = [], $limit = 100, $offset = 0)
    {
        if (!empty($filters['user_id'])) $this->db->where('c.user_id', (int)$filters['user_id']);
        if (!empty($filters['rank_id'])) $this->db->where('c.rank_id', (int)$filters['rank_id']);
        if (!empty($filters['q']))       $this->db->like('c.certificate_no', $filters['q']);
        return $this->db->select('c.*, u.username, u.email, r.badge_color, r.badge_image')
                        ->from('rank_certificates c')
                        ->join('users u', 'u.id = c.user_id', 'left')
                        ->join('staking_ranks r', 'r.id = c.rank_id', 'left')
                        ->order_by('c.id', 'DESC')
                        ->limit((int)$limit, (int)$offset)
                        ->get()->result_array();
    }

    public function certificate($cert_no)
    {
        return $this->db->select('c.*, u.username, u.email, u.first_name, u.last_name,
                                  r.badge_color, r.badge_image, r.tier_level')
                        ->from('rank_certificates c')
                        ->join('users u', 'u.id = c.user_id', 'left')
                        ->join('staking_ranks r', 'r.id = c.rank_id', 'left')
                        ->where('c.certificate_no', $cert_no)
                        ->get()->row_array() ?: null;
    }

    public function certificatesCount($filters = [])
    {
        if (!empty($filters['user_id'])) $this->db->where('user_id', (int)$filters['user_id']);
        if (!empty($filters['rank_id'])) $this->db->where('rank_id', (int)$filters['rank_id']);
        return $this->db->count_all_results('rank_certificates');
    }
}
