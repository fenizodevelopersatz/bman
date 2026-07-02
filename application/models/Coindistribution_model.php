<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Coindistribution_model — Coin Distribution Master (proposal §3A)
 * -------------------------------------------------------------------
 * Wallet-allocation options for BMAN purchases (Exchange / Earning /
 * Staking / Bonus). Business rules enforced here:
 *  - every percentage >= 0 and the total must equal exactly 100
 *  - only one option may be the default (changing it clears the old one)
 *  - the default option cannot be disabled or deleted
 *  - an option referenced by purchase history cannot be deleted
 *  - every change is written to coin_distribution_audit (create / edit /
 *    percentage_changed / enable / disable / default_changed / delete)
 *  - purchases snapshot option id + percentages into
 *    coin_distribution_histories, so later edits never affect old purchases
 */
class Coindistribution_model extends CI_Model
{
    /* ------------------------------- reads ------------------------------- */

    public function options($filters = [])
    {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('status', (int)$filters['status']);
        }
        if (isset($filters['is_default']) && $filters['is_default'] !== '') {
            $this->db->where('is_default', (int)$filters['is_default']);
        }
        if (!empty($filters['q'])) {
            $this->db->group_start()
                     ->like('option_name', $filters['q'])
                     ->or_like('description', $filters['q'])
                     ->group_end();
        }
        return $this->db->order_by('id', 'ASC')->get('coin_distribution_options')->result_array();
    }

    public function option($id)
    {
        return $this->db->get_where('coin_distribution_options', ['id' => (int)$id])->row_array();
    }

    /** Active options for the purchase screen (default first). */
    public function activeOptions()
    {
        return $this->db->where('status', 1)
                        ->order_by('is_default', 'DESC')->order_by('id', 'ASC')
                        ->get('coin_distribution_options')->result_array();
    }

    public function defaultOption()
    {
        return $this->db->get_where('coin_distribution_options',
                    ['is_default' => 1, 'status' => 1])->row_array() ?: null;
    }

    /* ---------------------------- audit helper --------------------------- */

    private function audit($option_id, $action, $old, $new, $admin_id)
    {
        $this->db->insert('coin_distribution_audit', [
            'option_id'  => $option_id ? (int)$option_id : null,
            'action'     => $action,
            'old_value'  => $old === null ? null : json_encode($old),
            'new_value'  => $new === null ? null : json_encode($new),
            'changed_by' => (int)$admin_id,
        ]);
    }

    public function auditLog($limit = 200)
    {
        return $this->db->select('a.*, o.option_name, adm.admin_name')
                        ->from('coin_distribution_audit a')
                        ->join('coin_distribution_options o', 'o.id = a.option_id', 'left')
                        ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                        ->order_by('a.created_at', 'DESC')->limit((int)$limit)
                        ->get()->result_array();
    }

    /* ------------------------------- writes ------------------------------ */

    /** Insert or update. Returns [ok, message|id]. Super-Admin only (controller-gated). */
    public function saveOption($data, $admin_id, $id = 0)
    {
        $row = [
            'option_name' => trim((string)($data['option_name'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')) ?: null,
        ];
        foreach (['exchange_percentage','earning_percentage','staking_percentage','bonus_percentage'] as $k) {
            $v = isset($data[$k]) ? (float)$data[$k] : 0;
            if ($v < 0) return [false, str_replace('_', ' ', $k).' must be >= 0.'];
            $row[$k] = $v;
        }
        if ($row['option_name'] === '') return [false, 'Option name is required.'];

        // Total must equal exactly 100% (proposal business rule)
        $total = $row['exchange_percentage'] + $row['earning_percentage']
               + $row['staking_percentage'] + $row['bonus_percentage'];
        if (abs($total - 100) > 0.001) {
            return [false, 'Total allocation must equal exactly 100% (currently '.$total.'%).'];
        }

        // unique name
        $this->db->where('option_name', $row['option_name']);
        if ($id) $this->db->where('id !=', (int)$id);
        if ($this->db->count_all_results('coin_distribution_options') > 0) {
            return [false, 'An option with this name already exists.'];
        }

        $want_default = (int)!!($data['is_default'] ?? 0);

        if ($id) {
            $old = $this->option($id);
            if (!$old) return [false, 'Option not found.'];
            // default option must stay active; status handled via toggle endpoint
            $row['updated_by'] = (int)$admin_id;
            $this->db->trans_start();
            $this->db->where('id', (int)$id)->update('coin_distribution_options', $row);
            if ($want_default && !(int)$old['is_default']) {
                $this->setDefaultInternal((int)$id, $admin_id);
            }
            $this->db->trans_complete();
            if (!$this->db->trans_status()) return [false, 'Database error.'];

            $new = $this->option($id);
            $pct_changed = false;
            foreach (['exchange_percentage','earning_percentage','staking_percentage','bonus_percentage'] as $k) {
                if (abs((float)$old[$k] - (float)$new[$k]) > 0.001) { $pct_changed = true; break; }
            }
            $this->audit($id, $pct_changed ? 'percentage_changed' : 'edit', $old, $new, $admin_id);
            return [true, (int)$id];
        }

        // insert
        $row['status']     = isset($data['status']) ? (int)!!$data['status'] : 1;
        $row['is_default'] = 0; // set below via the single-default path
        $row['created_by'] = (int)$admin_id;
        $this->db->trans_start();
        $this->db->insert('coin_distribution_options', $row);
        $new_id = (int)$this->db->insert_id();
        if ($want_default) $this->setDefaultInternal($new_id, $admin_id);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];

        $this->audit($new_id, 'create', null, $this->option($new_id), $admin_id);
        return [true, $new_id];
    }

    /** Make $id the single default (clears the previous one). Audited. */
    public function setDefault($id, $admin_id)
    {
        $opt = $this->option($id);
        if (!$opt)               return [false, 'Option not found.'];
        if (!(int)$opt['status']) return [false, 'A disabled option cannot be the default — enable it first.'];
        if ((int)$opt['is_default']) return [true, 'Already the default.'];

        $prev = $this->db->get_where('coin_distribution_options', ['is_default' => 1])->row_array();
        $this->db->trans_start();
        $this->setDefaultInternal((int)$id, $admin_id);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];

        $this->audit($id, 'default_changed',
            $prev ? ['previous_default' => $prev['id'], 'name' => $prev['option_name']] : null,
            ['new_default' => (int)$id, 'name' => $opt['option_name']], $admin_id);
        return [true, $opt['option_name'].' is now the default option.'];
    }

    /** Unconditional single-default write (callers wrap in a transaction). */
    private function setDefaultInternal($id, $admin_id)
    {
        $this->db->where('is_default', 1)
                 ->update('coin_distribution_options', ['is_default' => 0, 'updated_by' => (int)$admin_id]);
        $this->db->where('id', (int)$id)
                 ->update('coin_distribution_options', ['is_default' => 1, 'status' => 1, 'updated_by' => (int)$admin_id]);
    }

    /** Enable/disable. The default option cannot be disabled. Audited. */
    public function toggleOption($id, $active, $admin_id)
    {
        $opt = $this->option($id);
        if (!$opt) return [false, 'Option not found.'];
        $active = (int)!!$active;
        if (!$active && (int)$opt['is_default']) {
            return [false, 'The default option cannot be disabled — set another default first.'];
        }
        if ((int)$opt['status'] === $active) return [true, 'No change.'];
        $this->db->where('id', (int)$id)
                 ->update('coin_distribution_options', ['status' => $active, 'updated_by' => (int)$admin_id]);
        $this->audit($id, $active ? 'enable' : 'disable',
            ['status' => (int)$opt['status']], ['status' => $active], $admin_id);
        return [true, $opt['option_name'].($active ? ' enabled.' : ' disabled.')];
    }

    /** Delete. Blocked for the default option and for options already used. */
    public function deleteOption($id, $admin_id)
    {
        $opt = $this->option($id);
        if (!$opt) return [false, 'Option not found.'];
        if ((int)$opt['is_default']) return [false, 'The default option cannot be deleted.'];
        $used = $this->db->where('option_id', (int)$id)->count_all_results('coin_distribution_histories');
        if ($used > 0) {
            return [false, 'Option used by '.$used.' purchase(s) — disable it instead (history must stay accurate).'];
        }
        $this->db->where('id', (int)$id)->delete('coin_distribution_options');
        $this->audit($id, 'delete', $opt, null, $admin_id);
        return [true, 'Option deleted.'];
    }

    /* ===================== PURCHASE-FLOW INTEGRATION ====================== */

    /**
     * Split $amount by an option's percentages (pure computation — used for
     * the live preview and by apply()). Returns null when the option is
     * missing or inactive.
     */
    public function preview($option_id, $amount)
    {
        $opt = $this->option($option_id);
        if (!$opt || !(int)$opt['status']) return null;
        $amount = (float)$amount;
        return [
            'option_id'           => (int)$opt['id'],
            'option_name'         => $opt['option_name'],
            'exchange_percentage' => (float)$opt['exchange_percentage'],
            'earning_percentage'  => (float)$opt['earning_percentage'],
            'staking_percentage'  => (float)$opt['staking_percentage'],
            'bonus_percentage'    => (float)$opt['bonus_percentage'],
            'exchange_amount'     => round($amount * $opt['exchange_percentage'] / 100, 4),
            'earning_amount'      => round($amount * $opt['earning_percentage'] / 100, 4),
            'staking_amount'      => round($amount * $opt['staking_percentage'] / 100, 4),
            'bonus_amount'        => round($amount * $opt['bonus_percentage'] / 100, 4),
            'total_amount'        => $amount,
        ];
    }

    /**
     * Permanent history snapshot for a confirmed purchase (option id AND the
     * percentages used — proposal: locked after transaction confirmation).
     * Returns the history row id, or [false, message] shape via null on error.
     */
    public function writeHistory($user_id, $purchase_id, array $split)
    {
        $this->db->insert('coin_distribution_histories', [
            'user_id'             => (int)$user_id,
            'purchase_id'         => $purchase_id ? (int)$purchase_id : null,
            'option_id'           => (int)$split['option_id'],
            'exchange_percentage' => $split['exchange_percentage'],
            'earning_percentage'  => $split['earning_percentage'],
            'staking_percentage'  => $split['staking_percentage'],
            'bonus_percentage'    => $split['bonus_percentage'],
            'exchange_amount'     => $split['exchange_amount'],
            'earning_amount'      => $split['earning_amount'],
            'staking_amount'      => $split['staking_amount'],
            'bonus_amount'        => $split['bonus_amount'],
            'total_amount'        => $split['total_amount'],
        ]);
        return (int)$this->db->insert_id();
    }

    /**
     * Credit the four wallets from a computed split via the existing
     * wallet_transactions ledger (tx_type per wallet, source marks origin).
     * Zero amounts are skipped. Bonus credits flow into the existing
     * Wallet_model::getBonusBalance() automatically.
     */
    public function creditWallets($user_id, array $split)
    {
        $map = [
            'exchange' => 'exchange_amount',
            'earning'  => 'earning_amount',
            'staking'  => 'staking_amount',
            'bonus'    => 'bonus_amount',
        ];
        $rows = [];
        foreach ($map as $tx_type => $key) {
            $amount = (float)$split[$key];
            if ($amount <= 0) continue;
            $rows[] = [
                'user_id'    => (int)$user_id,
                'tx_type'    => $tx_type,
                'source'     => 'coin_distribution',
                'amount'     => $amount,
                'status'     => 'completed',
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        if ($rows) $this->db->insert_batch('wallet_transactions', $rows);
        return count($rows);
    }

    /** Allocation history (admin report). */
    public function histories($user_id = 0, $limit = 200)
    {
        $this->db->select('h.*, o.option_name, u.username, u.email')
                 ->from('coin_distribution_histories h')
                 ->join('coin_distribution_options o', 'o.id = h.option_id', 'left')
                 ->join('users u', 'u.id = h.user_id', 'left');
        if ($user_id) $this->db->where('h.user_id', (int)$user_id);
        return $this->db->order_by('h.created_at', 'DESC')->limit((int)$limit)->get()->result_array();
    }
}
