<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WalletMaturity_model — ledger-based maturity for withdrawal eligibility.
 *
 * Source of truth: wallet_ledger credits (maturity_date, is_matured).
 * user_wallets columns are display caches only; withdrawable balance is derived
 * from matured credits minus debits and active withdrawal holds.
 */
class WalletMaturity_model extends CI_Model
{
    private $bman_wallets = ['exchange', 'earning', 'staking', 'bonus'];

    private $col = [
        'usdt'     => 'usd_balance',
        'exchange' => 'exchange_balance',
        'earning'  => 'earning_balance',
        'staking'  => 'staking_balance',
        'bonus'    => 'bonus_balance',
    ];

    /* --------------------------- admin rules --------------------------- */

    public function rules()
    {
        return [
            'enabled' => (int) (site_settings('wallet_maturity_settings', 'maturity_enabled') ?: 1),
            'exchange' => (int) (site_settings('wallet_maturity_settings', 'maturity_days_exchange') ?: 0),
            'earning'  => (int) (site_settings('wallet_maturity_settings', 'maturity_days_earning') ?: 30),
            'staking'  => (int) (site_settings('wallet_maturity_settings', 'maturity_days_staking') ?: 0),
            'bonus'    => (int) (site_settings('wallet_maturity_settings', 'maturity_days_bonus') ?: 60),
        ];
    }

    public function maturity_days($wallet)
    {
        $rules = $this->rules();
        if (empty($rules['enabled'])) return 0;
        return (int) ($rules[$wallet] ?? 0);
    }

    /**
     * Compute maturity_date for a new credit.
     * opts may override: maturity_date, is_matured, skip_maturity.
     */
    public function resolve_credit_maturity($wallet, $opts = [])
    {
        if (!empty($opts['skip_maturity']) || !empty($opts['is_matured'])) {
            $when = isset($opts['maturity_date']) ? $opts['maturity_date'] : date('Y-m-d H:i:s');
            return ['maturity_date' => $when, 'is_matured' => 1];
        }
        if (isset($opts['maturity_date'])) {
            $when = $opts['maturity_date'];
            return [
                'maturity_date' => $when,
                'is_matured'    => (strtotime($when) <= time()) ? 1 : 0,
            ];
        }

        $days = $this->maturity_days($wallet);
        $base = isset($opts['created_at']) ? $opts['created_at'] : date('Y-m-d H:i:s');
        if ($days <= 0) {
            return ['maturity_date' => $base, 'is_matured' => 1];
        }
        $maturity = date('Y-m-d H:i:s', strtotime($base . ' + ' . $days . ' days'));
        return [
            'maturity_date' => $maturity,
            'is_matured'    => (strtotime($maturity) <= time()) ? 1 : 0,
        ];
    }

    /* ------------------------- balance reads ------------------------- */

    /**
     * Per-wallet breakdown: total, locked, matured, withdrawable.
     * withdrawable = matured_balance - active holds (pending withdrawal requests).
     */
    public function wallet_breakdown($user_id, $wallet)
    {
        $user_id = (int) $user_id;
        $total = $this->_summary_balance($user_id, $wallet);
        $locked = $this->_locked_from_ledger($user_id, $wallet);
        $matured = bcsub((string) $total, (string) $locked, 8);
        if (bccomp($matured, '0', 8) < 0) $matured = '0';
        $holds = $this->_active_holds($user_id, $wallet);
        $withdrawable = bcsub($matured, (string) $holds, 8);
        if (bccomp($withdrawable, '0', 8) < 0) $withdrawable = '0';

        return [
            'wallet'         => $wallet,
            'total'          => (float) $total,
            'locked'         => (float) $locked,
            'matured'        => (float) $matured,
            'holds'          => (float) $holds,
            'withdrawable'   => (float) $withdrawable,
            'maturity_days'  => $this->maturity_days($wallet),
        ];
    }

    /** All BMAN withdraw wallets for a user. */
    public function all_breakdowns($user_id)
    {
        $out = [];
        foreach ($this->bman_wallets as $w) {
            $out[$w] = $this->wallet_breakdown($user_id, $w);
        }
        return $out;
    }

    public function withdrawable($user_id, $wallet)
    {
        $b = $this->wallet_breakdown($user_id, $wallet);
        return (float) $b['withdrawable'];
    }

    /**
     * Upcoming unlock schedule — immature credits grouped by maturity_date.
     */
    public function upcoming_unlocks($user_id, $wallet = null, $limit = 30)
    {
        if (!$this->db->field_exists('maturity_date', 'wallet_ledger')) {
            return [];
        }
        $user_id = (int) $user_id;
        $this->db->select('wallet_type, maturity_date, SUM(credit) AS amount', false)
                 ->from('wallet_ledger')
                 ->where('user_id', $user_id)
                 ->where('credit >', 0)
                 ->where('is_matured', 0)
                 ->where('maturity_date IS NOT NULL', null, false)
                 ->where('maturity_date >', date('Y-m-d H:i:s'))
                 ->group_by(['wallet_type', 'maturity_date'])
                 ->order_by('maturity_date', 'ASC');
        if ($wallet) $this->db->where('wallet_type', $wallet);
        return $this->db->limit((int) $limit)->get()->result_array();
    }

    /* ---------------------- admin release control -------------------- */

    /**
     * Admin override: set a credit lot's release (maturity) date.
     * Only applies to credit rows. Returns [ok, message|row].
     */
    public function set_credit_maturity($ledger_id, $maturity_date, $admin_id = null)
    {
        if (!$this->db->field_exists('maturity_date', 'wallet_ledger')) {
            return [false, 'Maturity columns not installed — run db/2026-07-15_wallet_maturity.sql'];
        }

        $row = $this->db->get_where('wallet_ledger', ['id' => (int) $ledger_id])->row_array();
        if (!$row) return [false, 'Ledger row not found.'];
        if (bccomp((string) $row['credit'], '0', 8) <= 0) {
            return [false, 'Only credit rows carry a release date.'];
        }

        $when = date('Y-m-d H:i:s', strtotime($maturity_date));
        if (!$when || $when === '1970-01-01 00:00:00') {
            return [false, 'Invalid maturity date.'];
        }

        $is_matured = (strtotime($when) <= time()) ? 1 : 0;
        $upd = [
            'maturity_date' => $when,
            'is_matured'    => $is_matured,
        ];
        if ($admin_id) {
            $note = 'Release date set by admin #' . (int) $admin_id;
            $upd['description'] = trim(($row['description'] ?? '') . ' [' . $note . ']');
        }

        $this->db->where('id', (int) $ledger_id)->update('wallet_ledger', $upd);
        $fresh = $this->db->get_where('wallet_ledger', ['id' => (int) $ledger_id])->row_array();
        return [true, $fresh];
    }

    /** Admin: immediately mark a credit lot as matured (withdrawable). */
    public function force_mature_credit($ledger_id, $admin_id = null)
    {
        if (!$this->db->field_exists('is_matured', 'wallet_ledger')) {
            return [false, 'Maturity columns not installed — run db/2026-07-15_wallet_maturity.sql'];
        }

        $row = $this->db->get_where('wallet_ledger', ['id' => (int) $ledger_id])->row_array();
        if (!$row) return [false, 'Ledger row not found.'];
        if (bccomp((string) $row['credit'], '0', 8) <= 0) {
            return [false, 'Only credit rows can be matured.'];
        }
        if ((int) ($row['is_matured'] ?? 0) === 1) {
            return [true, $row];
        }

        $now = date('Y-m-d H:i:s');
        $upd = ['is_matured' => 1, 'maturity_date' => $now];
        if ($admin_id) {
            $note = 'Force-matured by admin #' . (int) $admin_id;
            $upd['description'] = trim(($row['description'] ?? '') . ' [' . $note . ']');
        }

        $this->db->where('id', (int) $ledger_id)->update('wallet_ledger', $upd);
        return [true, $this->db->get_where('wallet_ledger', ['id' => (int) $ledger_id])->row_array()];
    }

    /* --------------------------- cron job ---------------------------- */

    /**
     * Flip is_matured=1 for credits whose maturity_date has passed.
     * Returns count of rows updated.
     */
    public function process_daily_maturity()
    {
        if (!$this->db->field_exists('is_matured', 'wallet_ledger')) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('credit >', 0)
                 ->where('is_matured', 0)
                 ->where('maturity_date IS NOT NULL', null, false)
                 ->where('maturity_date <=', $now);
        $count = (int) $this->db->count_all_results('wallet_ledger');

        if ($count > 0) {
            $this->db->where('credit >', 0)
                     ->where('is_matured', 0)
                     ->where('maturity_date IS NOT NULL', null, false)
                     ->where('maturity_date <=', $now)
                     ->update('wallet_ledger', ['is_matured' => 1]);
        }
        return $count;
    }

    /* ------------------------- admin settings ------------------------ */

    public function update_rules(array $data)
    {
        $map = [
            'maturity_enabled'       => !empty($data['maturity_enabled']) ? 1 : 0,
            'maturity_days_exchange' => max(0, (int) ($data['maturity_days_exchange'] ?? 0)),
            'maturity_days_earning'  => max(0, (int) ($data['maturity_days_earning'] ?? 0)),
            'maturity_days_staking'  => max(0, (int) ($data['maturity_days_staking'] ?? 0)),
            'maturity_days_bonus'    => max(0, (int) ($data['maturity_days_bonus'] ?? 0)),
        ];
        foreach ($map as $name => $value) {
            $exists = $this->db->get_where('site_settings', [
                'settings_type' => 'wallet_maturity_settings',
                'settings_name' => $name,
            ])->row_array();
            if ($exists) {
                $this->db->where('id', $exists['id'])->update('site_settings', ['settings_value' => $value]);
            } else {
                $this->db->insert('site_settings', [
                    'settings_type'  => 'wallet_maturity_settings',
                    'settings_name'  => $name,
                    'settings_value' => $value,
                ]);
            }
        }
        return true;
    }

    /* --------------------------- internals --------------------------- */

    private function _summary_balance($user_id, $wallet)
    {
        if (!isset($this->col[$wallet])) return '0';
        $row = $this->db->select($this->col[$wallet])
                        ->get_where('user_wallets', ['user_id' => (int) $user_id])->row_array();
        return $row ? (string) $row[$this->col[$wallet]] : '0';
    }

    /** Sum of immature credit lots still locked. */
    private function _locked_from_ledger($user_id, $wallet)
    {
        if (!$this->db->field_exists('is_matured', 'wallet_ledger')) {
            return '0';
        }
        $row = $this->db->select('COALESCE(SUM(credit),0) AS locked', false)
                        ->from('wallet_ledger')
                        ->where('user_id', (int) $user_id)
                        ->where('wallet_type', $wallet)
                        ->where('is_matured', 0)
                        ->where('credit >', 0)
                        ->get()->row_array();
        return (string) ($row['locked'] ?? '0');
    }

    private function _active_holds($user_id, $wallet)
    {
        if (!$this->db->table_exists('wallet_withdraw_holds')) return 0.0;
        $row = $this->db->select('COALESCE(SUM(hold_amount - released_amount),0) AS held', false)
                        ->from('wallet_withdraw_holds')
                        ->where('user_id', (int) $user_id)
                        ->where('wallet_type', $wallet)
                        ->where('status', 'held')
                        ->get()->row_array();
        return (float) ($row['held'] ?? 0);
    }
}
