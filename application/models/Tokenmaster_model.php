<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tokenmaster_model — Master ▸ Token Settings
 * -------------------------------------------------------------------
 * Single source of truth for blockchain configuration: network, BMAN/USDT
 * tokens, exchange rate, platform wallets, smart contracts and chain
 * parameters. Multiple rows (Mainnet / Testnet) but only ONE active.
 *
 * Business rules enforced here:
 *  - required: RPC URL, Explorer URL, decimals; exchange rate > 0
 *  - only one active row (activating one deactivates the rest)
 *  - the active row cannot be disabled (activate another config instead)
 *  - every change → token_settings_audit (old/new JSON, admin, IP, date)
 *  - rate bridge: the ACTIVE row's exchange_rate is mirrored into the legacy
 *    token_config.currency_value, so every existing purchase flow uses the
 *    latest active rate WITHOUT code changes. Old purchases are safe: the
 *    rate is snapshotted at purchase time (user_investment.csq_price).
 */
class Tokenmaster_model extends CI_Model
{
    /** editable columns (everything except id/audit columns) */
    private $fields = [
        'network','blockchain','chain_id','rpc_url','explorer_url',
        'bman_name','bman_symbol','bman_decimals','bman_contract','bman_logo',
        'bman_min_transfer','bman_max_transfer','bman_enabled',
        'usdt_name','usdt_symbol','usdt_decimals','usdt_contract',
        'minimum_deposit','minimum_withdrawal','maximum_withdrawal','usdt_enabled',
        'exchange_rate','exchange_type','rate_effective_from',
        'treasury_wallet','deposit_wallet','gas_wallet','bonus_wallet','reserve_wallet','cold_wallet',
        'staking_contract','bonus_contract','referral_contract','roi_contract',
        'minimum_confirmations','gas_limit','gas_price','transaction_timeout','retry_count',
    ];

    /* ------------------------------- reads ------------------------------- */

    public function settingsList()
    {
        return $this->db->select('t.*, adm.admin_name AS updated_by_name')
                        ->from('token_settings t')
                        ->join('admin_members adm', 'adm.id = t.updated_by', 'left')
                        ->order_by('t.status', 'DESC')->order_by('t.id', 'ASC')
                        ->get()->result_array();
    }

    public function setting($id)
    {
        return $this->db->get_where('token_settings', ['id' => (int)$id])->row_array();
    }

    /** The single active configuration — what every engine must read. */
    public function activeSettings()
    {
        return $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: null;
    }

    /** USDT → BMAN conversion using the active settings (purchase flow §). */
    public function convertUsdtToBman($usdt_amount)
    {
        $s = $this->activeSettings();
        if (!$s || (float)$s['exchange_rate'] <= 0) return null;
        $rate = (float)$s['exchange_rate'];
        // usdt_to_bman: 1 USDT = rate BMAN · bman_to_usdt: 1 BMAN = rate USDT
        return $s['exchange_type'] === 'usdt_to_bman'
            ? (float)$usdt_amount * $rate
            : (float)$usdt_amount / $rate;
    }

    /* ---------------------------- audit helper --------------------------- */

    private function audit($setting_id, $action, $old, $new, $admin_id, $ip)
    {
        $this->db->insert('token_settings_audit', [
            'setting_id' => $setting_id ? (int)$setting_id : null,
            'action'     => $action,
            'old_value'  => $old === null ? null : json_encode($old),
            'new_value'  => $new === null ? null : json_encode($new),
            'changed_by' => (int)$admin_id,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
        ]);
    }

    public function auditLog($limit = 200)
    {
        return $this->db->select('a.*, adm.admin_name')
                        ->from('token_settings_audit a')
                        ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                        ->order_by('a.created_at', 'DESC')->limit((int)$limit)
                        ->get()->result_array();
    }

    /* ------------------------------- writes ------------------------------ */

    /** Insert or update a configuration. Returns [ok, message|id]. */
    public function saveSetting($data, $admin_id, $ip, $id = 0)
    {
        $row = [];
        foreach ($this->fields as $f) {
            if (array_key_exists($f, $data)) $row[$f] = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
        }

        // ---- validation (spec) ----
        if (isset($row['network']) && !in_array($row['network'], ['mainnet','testnet'], true)) {
            return [false, 'Network must be mainnet or testnet.'];
        }
        foreach (['rpc_url' => 'RPC URL', 'explorer_url' => 'Explorer URL'] as $f => $label) {
            if (array_key_exists($f, $row) && $row[$f] === '') return [false, $label.' is required.'];
        }
        foreach (['bman_decimals' => 'BMAN decimals', 'usdt_decimals' => 'USDT decimals'] as $f => $label) {
            if (array_key_exists($f, $row)) {
                if ($row[$f] === '' || (int)$row[$f] < 0 || (int)$row[$f] > 36) return [false, $label.' must be 0–36.'];
                $row[$f] = (int)$row[$f];
            }
        }
        if (array_key_exists('chain_id', $row)) {
            if ((int)$row['chain_id'] <= 0) return [false, 'Chain ID must be a positive number.'];
            $row['chain_id'] = (int)$row['chain_id'];
        }
        if (array_key_exists('exchange_rate', $row)) {
            if ((float)$row['exchange_rate'] <= 0) return [false, 'Exchange rate must be greater than 0.'];
            $row['exchange_rate'] = (float)$row['exchange_rate'];
        }
        if (isset($row['exchange_type']) && !in_array($row['exchange_type'], ['usdt_to_bman','bman_to_usdt'], true)) {
            return [false, 'Invalid exchange calculation method.'];
        }
        // EVM address shape for any provided contract / wallet
        foreach (['bman_contract','usdt_contract','staking_contract','bonus_contract','referral_contract','roi_contract',
                  'treasury_wallet','deposit_wallet','gas_wallet','bonus_wallet','reserve_wallet','cold_wallet'] as $f) {
            if (!empty($row[$f]) && !preg_match('/^0x[a-fA-F0-9]{40}$/', $row[$f])) {
                return [false, str_replace('_', ' ', $f).' must be a valid 0x… address (40 hex chars).'];
            }
        }
        foreach (['bman_min_transfer','bman_max_transfer','minimum_deposit','minimum_withdrawal','maximum_withdrawal'] as $f) {
            if (array_key_exists($f, $row)) {
                if ($row[$f] === '' || $row[$f] === null) { unset($row[$f]); continue; }
                if ((float)$row[$f] < 0) return [false, str_replace('_', ' ', $f).' cannot be negative.'];
                $row[$f] = (float)$row[$f];
            }
        }
        foreach (['minimum_confirmations','gas_limit','transaction_timeout','retry_count'] as $f) {
            if (array_key_exists($f, $row)) {
                if ((int)$row[$f] < 0) return [false, str_replace('_', ' ', $f).' cannot be negative.'];
                $row[$f] = (int)$row[$f];
            }
        }
        foreach (['bman_enabled','usdt_enabled'] as $f) {
            if (array_key_exists($f, $row)) $row[$f] = (int)!!$row[$f];
        }
        if (array_key_exists('rate_effective_from', $row) && $row['rate_effective_from'] !== '') {
            if (strtotime($row['rate_effective_from']) === false) return [false, 'Invalid rate effective-from date.'];
            $row['rate_effective_from'] = date('Y-m-d', strtotime($row['rate_effective_from']));
        }

        // unique network + chain
        if (isset($row['network'], $row['chain_id'])) {
            $this->db->where(['network' => $row['network'], 'chain_id' => $row['chain_id']]);
            if ($id) $this->db->where('id !=', (int)$id);
            if ($this->db->count_all_results('token_settings') > 0) {
                return [false, 'A configuration for this network + chain id already exists.'];
            }
        }

        if ($id) {
            $old = $this->setting($id);
            if (!$old) return [false, 'Configuration not found.'];
            $row['updated_by'] = (int)$admin_id;
            $this->db->where('id', (int)$id)->update('token_settings', $row);
            $new = $this->setting($id);

            $rate_changed = isset($row['exchange_rate'])
                && abs((float)$old['exchange_rate'] - (float)$new['exchange_rate']) > 0.00000001;
            $this->audit($id, $rate_changed ? 'rate_changed' : 'edit', $old, $new, $admin_id, $ip);

            if ((int)$new['status'] === 1) $this->syncLegacyRate($new);
            return [true, (int)$id];
        }

        $row['status']     = 0; // activate explicitly via setActive()
        $row['created_by'] = (int)$admin_id;
        $row['updated_by'] = (int)$admin_id;
        $this->db->insert('token_settings', $row);
        $new_id = (int)$this->db->insert_id();
        $this->audit($new_id, 'create', null, $this->setting($new_id), $admin_id, $ip);
        return [true, $new_id];
    }

    /** Make $id the single active configuration (one active network + rate). */
    public function setActive($id, $admin_id, $ip)
    {
        $cfg = $this->setting($id);
        if (!$cfg) return [false, 'Configuration not found.'];
        if ((int)$cfg['status'] === 1) return [true, 'Already the active configuration.'];

        $prev = $this->activeSettings();
        $this->db->trans_start();
        $this->db->where('status', 1)->update('token_settings', ['status' => 0, 'updated_by' => (int)$admin_id]);
        $this->db->where('id', (int)$id)->update('token_settings', ['status' => 1, 'updated_by' => (int)$admin_id]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];

        $this->audit($id, 'activate',
            $prev ? ['previous_active' => $prev['id'], 'network' => $prev['network']] : null,
            ['new_active' => (int)$id, 'network' => $cfg['network']], $admin_id, $ip);
        $this->syncLegacyRate($this->setting($id));
        return [true, ucfirst($cfg['network']).' (chain '.$cfg['chain_id'].') is now the active configuration.'];
    }

    /** Enable/disable a row. The active configuration cannot be disabled. */
    public function toggleSetting($id, $active, $admin_id, $ip)
    {
        $cfg = $this->setting($id);
        if (!$cfg) return [false, 'Configuration not found.'];
        if ($active) return $this->setActive($id, $admin_id, $ip);
        if ((int)$cfg['status'] === 1) {
            return [false, 'The active configuration cannot be disabled — activate another one first.'];
        }
        return [true, 'Configuration is already inactive.'];
    }

    /**
     * Mirror the active rate into the legacy token_config.currency_value so
     * all existing flows (token_info()) read the latest active rate. New
     * purchases pick it up immediately; old ones keep their csq_price snapshot.
     */
    private function syncLegacyRate($cfg)
    {
        if (!$cfg) return;
        $this->db->where('currency_status', 1)
                 ->update('token_config', ['currency_value' => (string)(float)$cfg['exchange_rate']]);
    }
}
