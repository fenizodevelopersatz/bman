<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WalletTracker_model — unified backend for wallet_ledger + source tables.
 *
 * Every balance movement lives in wallet_ledger. This model resolves each row
 * back to its originating transaction table (deposits, swaps, ROI, matching,
 * transfers, ceiling, bonus reduction, withdrawals) and exposes maturity/release
 * metadata for withdrawal eligibility.
 */
class WalletTracker_model extends CI_Model
{
    /** reference_type → human label + source table hint */
    private $type_catalog = [
        'deposit'          => ['label' => 'USDT Deposit',           'category' => 'deposit',    'source' => 'wallet_deposits'],
        'withdrawal'       => ['label' => 'USDT Withdrawal',        'category' => 'withdrawal', 'source' => 'wallet_deposits'],
        'bman_withdrawal'  => ['label' => 'BMAN Withdrawal',        'category' => 'withdrawal', 'source' => 'bman_withdraw_requests'],
        'swap'             => ['label' => 'Staking Swap (USDT)',    'category' => 'purchase',   'source' => 'staking_swap_orders'],
        'stake_purchase'   => ['label' => 'Stake Purchase',         'category' => 'purchase',   'source' => 'user_stakes'],
        'bonus'            => ['label' => 'Staking Bonus',          'category' => 'purchase',   'source' => 'user_stakes'],
        'roi'              => ['label' => 'ROI Payment',            'category' => 'roi',        'source' => 'roi_staking_management'],
        'stake_maturity'   => ['label' => 'Principal Return',       'category' => 'roi',        'source' => 'roi_staking_management'],
        'binary_matching'  => ['label' => 'Binary Matching',        'category' => 'binary',     'source' => 'staking_matching_payouts'],
        'wallet_transfer'  => ['label' => 'Wallet Transfer',        'category' => 'transfer',   'source' => 'wallet_internal_transfer'],
        'ceiling_release'  => ['label' => 'Ceiling Release',        'category' => 'ceiling',    'source' => 'ceiling_wallet_ledger'],
        'bonus_reduction'  => ['label' => 'Bonus Reduction',        'category' => 'reduction',  'source' => 'bonus_reduction_log'],
        'admin_adjustment' => ['label' => 'Admin Adjustment',       'category' => 'admin',      'source' => null],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('WalletMaturity_model', 'maturity');
    }

    /* --------------------------- catalog --------------------------- */

    public function reference_types()
    {
        return $this->type_catalog;
    }

    public function categories()
    {
        return [
            'deposit'    => 'USDT Deposits',
            'purchase'   => 'Staking / Swaps',
            'roi'        => 'ROI & Maturity',
            'binary'     => 'Binary Matching',
            'transfer'   => 'Wallet Transfers',
            'ceiling'    => 'Ceiling Release',
            'reduction'  => 'Bonus Reduction',
            'withdrawal' => 'Withdrawals',
            'admin'      => 'Admin Adjustments',
        ];
    }

    /** Map a category slug to reference_type values. */
    public function types_for_category($category)
    {
        $out = [];
        foreach ($this->type_catalog as $type => $meta) {
            if (($meta['category'] ?? '') === $category) $out[] = $type;
        }
        return $out;
    }

    /* --------------------------- list / count ---------------------- */

    /**
     * Paginated ledger rows enriched with source + on-chain + maturity summary.
     *
     * Filters: user_id, wallet_type, reference_type, category, direction (credit|debit),
     * maturity (locked|matured|na), date_from, date_to, search, tx_hash.
     */
    public function list_transactions(array $filters = [], $limit = 50, $offset = 0)
    {
        $this->_apply_filters($filters);
        $rows = $this->db->order_by('wl.id', 'DESC')
                         ->limit((int) $limit, (int) $offset)
                         ->get()
                         ->result_array();

        return array_map([$this, 'enrich_row'], $rows);
    }

    public function count_transactions(array $filters = [])
    {
        $this->_apply_filters($filters);
        return (int) $this->db->count_all_results();
    }

    private function _apply_filters(array $filters)
    {
        $this->db->from('wallet_ledger wl');

        if (!empty($filters['user_id'])) {
            $this->db->where('wl.user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['wallet_type'])) {
            $this->db->where('wl.wallet_type', $filters['wallet_type']);
        }
        if (!empty($filters['reference_type'])) {
            $this->db->where('wl.reference_type', $filters['reference_type']);
        }
        if (!empty($filters['category'])) {
            $types = $this->types_for_category($filters['category']);
            if ($types) $this->db->where_in('wl.reference_type', $types);
        }
        if (!empty($filters['direction'])) {
            if ($filters['direction'] === 'credit') {
                $this->db->where('wl.credit >', 0);
            } elseif ($filters['direction'] === 'debit') {
                $this->db->where('wl.debit >', 0);
            }
        }
        if (!empty($filters['maturity']) && $this->db->field_exists('is_matured', 'wallet_ledger')) {
            if ($filters['maturity'] === 'locked') {
                $this->db->where('wl.credit >', 0)->where('wl.is_matured', 0);
            } elseif ($filters['maturity'] === 'matured') {
                $this->db->where('wl.credit >', 0)->where('wl.is_matured', 1);
            } elseif ($filters['maturity'] === 'na') {
                $this->db->where('wl.debit >', 0);
            }
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(wl.created_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(wl.created_at) <=', $filters['date_to']);
        }
        if (!empty($filters['tx_hash'])) {
            $this->db->like('wl.tx_hash', trim($filters['tx_hash']), 'after');
        }
        if (!empty($filters['search'])) {
            $q = trim($filters['search']);
            $this->db->group_start()
                     ->like('wl.reference_id', $q)
                     ->or_like('wl.description', $q)
                     ->or_like('wl.tx_hash', $q)
                     ->group_end();
        }
    }

    /* --------------------------- detail ---------------------------- */

    /**
     * Full detail for one ledger row: source record, on-chain mirror, transfer pair,
     * maturity breakdown. $restrict_user_id limits access to that member.
     */
    public function transaction_detail($ledger_id, $restrict_user_id = 0)
    {
        $row = $this->db->get_where('wallet_ledger', ['id' => (int) $ledger_id])->row_array();
        if (!$row) return null;
        if ($restrict_user_id && (int) $row['user_id'] !== (int) $restrict_user_id) return null;

        $detail = $this->enrich_row($row, true);
        $detail['related_ledger'] = $this->_related_ledger_rows($row);
        $detail['maturity_rules'] = $this->maturity->rules();
        if (bccomp((string) $row['credit'], '0', 8) > 0) {
            $detail['wallet_breakdown'] = $this->maturity->wallet_breakdown((int) $row['user_id'], $row['wallet_type']);
        }
        return $detail;
    }

    /* --------------------------- maturity lots --------------------- */

    /** Credit lots with maturity/release schedule (for admin or user). */
    public function maturity_credits($user_id, array $filters = [], $limit = 100, $offset = 0)
    {
        if (!$this->db->field_exists('maturity_date', 'wallet_ledger')) return [];

        $user_id = (int) $user_id;
        $this->db->from('wallet_ledger')
                 ->where('user_id', $user_id)
                 ->where('credit >', 0);

        if (!empty($filters['wallet_type'])) {
            $this->db->where('wallet_type', $filters['wallet_type']);
        }
        if (!empty($filters['maturity'])) {
            if ($filters['maturity'] === 'locked') $this->db->where('is_matured', 0);
            if ($filters['maturity'] === 'matured') $this->db->where('is_matured', 1);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(maturity_date) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(maturity_date) <=', $filters['date_to']);
        }

        $rows = $this->db->order_by('maturity_date', 'ASC')
                         ->order_by('id', 'ASC')
                         ->limit((int) $limit, (int) $offset)
                         ->get()->result_array();

        return array_map(function ($r) {
            $e = $this->enrich_row($r, false);
            $e['days_until_release'] = $this->_days_until($r['maturity_date'], (int) $r['is_matured']);
            return $e;
        }, $rows);
    }

    public function count_maturity_credits($user_id, array $filters = [])
    {
        if (!$this->db->field_exists('maturity_date', 'wallet_ledger')) return 0;

        $this->db->from('wallet_ledger')
                 ->where('user_id', (int) $user_id)
                 ->where('credit >', 0);

        if (!empty($filters['wallet_type'])) $this->db->where('wallet_type', $filters['wallet_type']);
        if (!empty($filters['maturity'])) {
            if ($filters['maturity'] === 'locked') $this->db->where('is_matured', 0);
            if ($filters['maturity'] === 'matured') $this->db->where('is_matured', 1);
        }
        return (int) $this->db->count_all_results();
    }

    /** Per-user wallet summary with maturity breakdown. */
    public function user_summary($user_id)
    {
        $user_id = (int) $user_id;
        $breakdowns = $this->maturity->all_breakdowns($user_id);
        $row = $this->db->get_where('user_wallets', ['user_id' => $user_id])->row_array();

        return [
            'user_id'    => $user_id,
            'balances'   => [
                'usdt'     => (float) ($row['usd_balance'] ?? 0),
                'exchange' => (float) ($row['exchange_balance'] ?? 0),
                'earning'  => (float) ($row['earning_balance'] ?? 0),
                'staking'  => (float) ($row['staking_balance'] ?? 0),
                'bonus'    => (float) ($row['bonus_balance'] ?? 0),
            ],
            'breakdowns' => $breakdowns,
            'upcoming'   => $this->maturity->upcoming_unlocks($user_id, null, 20),
            'rules'      => $this->maturity->rules(),
            'totals'     => $this->_user_ledger_totals($user_id),
        ];
    }

    /* --------------------------- enrich row ------------------------ */

    public function enrich_row(array $row, $full_source = false)
    {
        $type = $row['reference_type'] ?? '';
        $meta = $this->type_catalog[$type] ?? [
            'label' => ucfirst(str_replace('_', ' ', $type)),
            'category' => 'other',
            'source' => null,
        ];

        $out = [
            'ledger_id'      => (int) $row['id'],
            'user_id'        => (int) $row['user_id'],
            'wallet_type'    => $row['wallet_type'],
            'credit'         => (string) $row['credit'],
            'debit'          => (string) $row['debit'],
            'amount'         => bccomp((string) $row['credit'], '0', 8) > 0 ? (string) $row['credit'] : (string) $row['debit'],
            'direction'      => bccomp((string) $row['credit'], '0', 8) > 0 ? 'credit' : 'debit',
            'balance_after'  => (string) $row['balance_after'],
            'reference_type' => $type,
            'type_label'     => $meta['label'],
            'category'       => $meta['category'],
            'reference_id'   => $row['reference_id'],
            'description'    => $row['description'],
            'tx_hash'        => $row['tx_hash'],
            'created_by'     => isset($row['created_by']) ? (int) $row['created_by'] : null,
            'created_at'     => $row['created_at'],
            'maturity_date'  => $row['maturity_date'] ?? null,
            'is_matured'     => isset($row['is_matured']) ? (int) $row['is_matured'] : null,
            'days_until_release' => $this->_days_until($row['maturity_date'] ?? null, (int) ($row['is_matured'] ?? 0)),
            'source_table'   => $meta['source'],
            'source'         => null,
            'onchain'        => $this->_onchain_for_ledger((int) $row['id'], $row),
        ];

        $out['source'] = $full_source
            ? $this->resolve_source($row)
            : $this->summarize_source($row);

        return $out;
    }

    /** Compact source pointer for list views. */
    public function summarize_source(array $row)
    {
        $full = $this->resolve_source($row);
        if (!$full) return null;

        $summary = ['table' => $full['table'], 'id' => $full['id'] ?? null];
        foreach (['ref', 'status', 'request_no', 'run_ref', 'amount_usdt', 'usdt_amount', 'bman_amount', 'matched_volume', 'plan_type'] as $k) {
            if (isset($full['record'][$k])) $summary[$k] = $full['record'][$k];
        }
        return $summary;
    }

    /**
     * Resolve wallet_ledger row → originating transaction record.
     * Returns ['table' => ..., 'id' => ..., 'record' => [...]] or null.
     */
    public function resolve_source(array $row)
    {
        $type = $row['reference_type'] ?? '';
        $ref  = $row['reference_id'] ?? '';
        $uid  = (int) ($row['user_id'] ?? 0);
        $lid  = (int) ($row['id'] ?? 0);

        switch ($type) {
            case 'deposit':
            case 'withdrawal':
                return $this->_fetch_source('wallet_deposits', $ref, 'id', true)
                    ?: $this->_fetch_source_by_tx('wallet_deposits', $row['tx_hash'] ?? '');

            case 'swap':
            case 'stake_purchase':
            case 'bonus':
                if ($swap = $this->_fetch_source('staking_swap_orders', $ref, 'ref', false)) {
                    return $swap;
                }
                if ($type === 'stake_purchase' || $type === 'bonus') {
                    $stake = $this->_resolve_stake($ref, $uid);
                    if ($stake) return $stake;
                }
                return null;

            case 'roi':
            case 'stake_maturity':
                return $this->_fetch_source('roi_staking_management', $ref, 'ref', false);

            case 'binary_matching':
                return $this->_fetch_matching_payout($uid, $ref);

            case 'wallet_transfer':
                return $this->_resolve_transfer($ref, $lid);

            case 'ceiling_release':
                return $this->_resolve_ceiling_release($uid, $ref);

            case 'bonus_reduction':
                return $this->_fetch_source('bonus_reduction_log', (string) $lid, 'wallet_ledger_id', true);

            case 'bman_withdrawal':
                return $this->_resolve_bman_withdrawal($ref, $uid);

            default:
                return null;
        }
    }

    /* --------------------------- source resolvers ------------------ */

    private function _fetch_source($table, $ref, $col, $numeric)
    {
        if (!$this->db->table_exists($table) || $ref === '' || $ref === null) return null;
        if ($numeric && !ctype_digit((string) $ref)) return null;

        $rec = $this->db->get_where($table, [$col => $ref])->row_array();
        if (!$rec) return null;

        return ['table' => $table, 'id' => $rec['id'] ?? $ref, 'record' => $rec];
    }

    private function _fetch_source_by_tx($table, $tx_hash)
    {
        if (!$tx_hash || !$this->db->table_exists($table)) return null;
        $rec = $this->db->get_where($table, ['tx_hash' => $tx_hash])->row_array();
        return $rec ? ['table' => $table, 'id' => $rec['id'], 'record' => $rec] : null;
    }

    private function _resolve_stake($ref, $user_id)
    {
        if (!$this->db->table_exists('user_stakes')) return null;

        // Stakes created via direct purchase store ref in history.hash_id; match by user + recent.
        if ($this->db->table_exists('history')) {
            $h = $this->db->select('invest_id')
                          ->get_where('history', ['hash_id' => $ref, 'user_id' => $user_id])
                          ->row_array();
            if (!empty($h['invest_id'])) {
                $rec = $this->db->get_where('user_stakes', ['id' => (int) $h['invest_id']])->row_array();
                if ($rec) return ['table' => 'user_stakes', 'id' => $rec['id'], 'record' => $rec];
            }
        }

        // Fallback: treasury payment ref
        if ($this->db->table_exists('staking_treasury_payments')) {
            $tp = $this->db->get_where('staking_treasury_payments', ['ref' => $ref, 'user_id' => $user_id])->row_array();
            if ($tp && !empty($tp['stake_id'])) {
                $rec = $this->db->get_where('user_stakes', ['id' => (int) $tp['stake_id']])->row_array();
                if ($rec) return ['table' => 'user_stakes', 'id' => $rec['id'], 'record' => $rec];
            }
        }

        return null;
    }

    private function _fetch_matching_payout($user_id, $run_ref)
    {
        if (!$this->db->table_exists('staking_matching_payouts') || !$run_ref) return null;
        $rec = $this->db->get_where('staking_matching_payouts', [
            'user_id' => $user_id,
            'run_ref' => $run_ref,
        ])->row_array();
        return $rec ? ['table' => 'staking_matching_payouts', 'id' => $rec['id'], 'record' => $rec] : null;
    }

    private function _resolve_transfer($ref, $ledger_id)
    {
        if (!$this->db->table_exists('wallet_internal_transfer')) return null;

        if ($ref) {
            $rec = $this->db->get_where('wallet_internal_transfer', ['ref' => $ref])->row_array();
            if ($rec) return ['table' => 'wallet_internal_transfer', 'id' => $rec['id'], 'record' => $rec];
        }

        $rec = $this->db->group_start()
                        ->where('debit_ledger_id', $ledger_id)
                        ->or_where('credit_ledger_id', $ledger_id)
                        ->group_end()
                        ->get('wallet_internal_transfer')->row_array();
        return $rec ? ['table' => 'wallet_internal_transfer', 'id' => $rec['id'], 'record' => $rec] : null;
    }

    private function _resolve_ceiling_release($user_id, $ref)
    {
        if (!$this->db->table_exists('ceiling_wallet_ledger')) return null;

        $this->db->from('ceiling_wallet_ledger')
                 ->where('user_id', $user_id)
                 ->where('tx_type', 'CEILING_RELEASE');
        if ($ref) $this->db->where('reference_id', $ref);
        $rec = $this->db->order_by('id', 'DESC')->limit(1)->get()->row_array();
        return $rec ? ['table' => 'ceiling_wallet_ledger', 'id' => $rec['id'], 'record' => $rec] : null;
    }

    private function _resolve_bman_withdrawal($ref, $user_id)
    {
        if (!$this->db->table_exists('bman_withdraw_requests')) return null;

        if ($ref) {
            $rec = $this->db->get_where('bman_withdraw_requests', ['request_no' => $ref])->row_array()
                ?: $this->db->get_where('bman_withdraw_requests', ['id' => ctype_digit((string) $ref) ? (int) $ref : 0])->row_array();
            if ($rec) return ['table' => 'bman_withdraw_requests', 'id' => $rec['id'], 'record' => $rec];
        }

        // Match by hold row if reference_id absent
        if ($this->db->table_exists('wallet_withdraw_holds')) {
            $hold = $this->db->select('wwh.*, bwr.*')
                             ->from('wallet_withdraw_holds wwh')
                             ->join('bman_withdraw_requests bwr', 'bwr.id = wwh.request_id', 'inner')
                             ->where('wwh.user_id', $user_id)
                             ->order_by('wwh.id', 'DESC')
                             ->limit(1)->get()->row_array();
            if ($hold) return ['table' => 'bman_withdraw_requests', 'id' => $hold['request_id'], 'record' => $hold];
        }
        return null;
    }

    private function _onchain_for_ledger($ledger_id, array $row)
    {
        if (!$this->db->table_exists('onchain_transactions')) return null;

        $oc = $this->db->get_where('onchain_transactions', ['wallet_ledger_id' => $ledger_id])->row_array();
        if (!$oc && !empty($row['tx_hash'])) {
            $oc = $this->db->get_where('onchain_transactions', [
                'tx_hash'     => $row['tx_hash'],
                'wallet_type' => $row['wallet_type'],
            ])->row_array();
        }
        if (!$oc) return null;

        return [
            'id'          => (int) $oc['id'],
            'tx_hash'     => $oc['tx_hash'],
            'tx_type'     => $oc['tx_type'],
            'status'      => $oc['status'],
            'amount'      => $oc['amount'],
            'block_number'=> $oc['block_number'] ?? null,
            'network'     => $oc['network'] ?? null,
            'gas_used'    => $oc['gas_used'] ?? null,
            'gas_price_gwei' => $oc['gas_price_gwei'] ?? null,
            'gas_fee_total' => $oc['gas_fee_total'] ?? null,
            'from_address' => $oc['from_address'] ?? null,
            'to_address'   => $oc['to_address'] ?? null,
        ];
    }

    private function _related_ledger_rows(array $row)
    {
        $related = [];
        $type = $row['reference_type'] ?? '';
        $ref  = $row['reference_id'] ?? '';

        if ($type === 'wallet_transfer' && $ref) {
            $related = $this->db->where('reference_id', $ref)
                                ->where('reference_type', 'wallet_transfer')
                                ->where('id !=', (int) $row['id'])
                                ->get('wallet_ledger')->result_array();
        } elseif (in_array($type, ['stake_purchase', 'bonus', 'swap'], true) && $ref) {
            $related = $this->db->where('reference_id', $ref)
                                ->where('user_id', (int) $row['user_id'])
                                ->where('id !=', (int) $row['id'])
                                ->get('wallet_ledger')->result_array();
        }

        return array_map(function ($r) {
            return $this->enrich_row($r, false);
        }, $related);
    }

    private function _user_ledger_totals($user_id)
    {
        $rows = $this->db->select('reference_type, COUNT(*) AS cnt, SUM(credit) AS credits, SUM(debit) AS debits', false)
                         ->from('wallet_ledger')
                         ->where('user_id', (int) $user_id)
                         ->group_by('reference_type')
                         ->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['reference_type']] = [
                'count'   => (int) $r['cnt'],
                'credits' => (string) $r['credits'],
                'debits'  => (string) $r['debits'],
            ];
        }
        return $out;
    }

    private function _days_until($maturity_date, $is_matured)
    {
        if ($is_matured || empty($maturity_date)) return 0;
        $diff = strtotime($maturity_date) - time();
        return $diff <= 0 ? 0 : (int) ceil($diff / 86400);
    }
}
