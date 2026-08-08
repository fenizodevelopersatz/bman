<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GasFeeSettings_model — gas fee POLICY per operation profile
 * ('gas_funding' = native BNB send, 'token_transfer' = BEP-20 send).
 * resolve() is the single source callers should use instead of reading
 * token_settings.gas_limit/gas_price directly — it falls back to that exact
 * read only when no active policy row exists, so nothing regresses if the
 * gas_fee_settings table is ever empty.
 */
class GasFeeSettings_model extends CI_Model
{
    /**
     * Active policy for one gas profile. Returns:
     *   gas_limit (int), gas_price_gwei (float|null — null means "no fixed
     *   policy price, caller should fall back to a live eth_gasPrice RPC
     *   read"), buffer_multiplier (float), source ('gas_fee_settings'|
     *   'token_settings').
     */
    public function resolve($txType)
    {
        $row = $this->db->get_where('gas_fee_settings', ['tx_type' => $txType, 'is_active' => 1])->row_array();
        if ($row) {
            return [
                'gas_limit'         => (int) $row['gas_limit'],
                'gas_price_gwei'    => $row['gas_price_gwei'] !== null ? (float) $row['gas_price_gwei'] : null,
                'buffer_multiplier' => (float) $row['buffer_multiplier'],
                'source'            => 'gas_fee_settings',
            ];
        }

        // Fallback: no active policy row — use the same token_settings columns
        // every gas call already read before this table existed.
        $cfg = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        return [
            'gas_limit'         => (int) ($cfg['gas_limit'] ?: 210000),
            'gas_price_gwei'    => isset($cfg['gas_price']) && $cfg['gas_price'] !== '' ? (float) $cfg['gas_price'] : null,
            'buffer_multiplier' => 1.5,
            'source'            => 'token_settings',
        ];
    }

    /**
     * ESTIMATED BNB for one transaction of $txType — the single gas-cost
     * calculation in the system.
     *
     * gas_limit x gas_price_gwei x 1e-9 x buffer_multiplier, every term from
     * the resolved policy row. Callers must not re-implement this: the
     * treasury precheck and the actual broadcast have to agree, and they only
     * do if there is one formula. (They previously disagreed — the binary
     * matching path carried its own copy with hardcoded 210000/5/1.5.)
     *
     * gas_price_gwei may legitimately be NULL, meaning "no fixed policy price
     * — read the live network price". In that case pass $liveGwei from an
     * eth_gasPrice read. With neither, 'bnb' comes back NULL, which callers
     * must surface as UNKNOWN and treat as "cannot verify" — never as free,
     * and never by substituting a guessed price.
     *
     * @return array{bnb: float|null, gas_limit:int, gas_price_gwei: float|null,
     *               buffer_multiplier:float, source:string, price_source:string}
     */
    public function estimateBnb($txType, $liveGwei = null)
    {
        $p = $this->resolve($txType);

        $gwei = $p['gas_price_gwei'];
        $priceSource = 'policy';
        if ($gwei === null) {
            $gwei = ($liveGwei !== null && (float) $liveGwei > 0) ? (float) $liveGwei : null;
            $priceSource = $gwei === null ? 'unknown' : 'live_rpc';
        }

        return $p + [
            'bnb'          => $gwei === null ? null
                              : (float) $p['gas_limit'] * $gwei * 1e-9 * $p['buffer_multiplier'],
            'price_source' => $priceSource,
        ];
    }

    public function all()
    {
        return $this->db->order_by('tx_type', 'ASC')->get('gas_fee_settings')->result_array();
    }

    /**
     * Save one policy row's editable fields, auditing every changed field via
     * admin_settings_audit (module='gas_fee_settings') — same generic,
     * one-row-per-changed-field pattern Withdrawsettings.php already uses.
     */
    public function save($id, array $fields, $adminId)
    {
        $id = (int) $id;
        $current = $this->db->get_where('gas_fee_settings', ['id' => $id])->row_array();
        if (!$current) return false;

        $editable = ['gas_limit', 'gas_price_gwei', 'buffer_multiplier', 'is_active'];
        $numeric  = ['gas_limit' => true, 'gas_price_gwei' => true, 'buffer_multiplier' => true];
        $update = [];
        $auditRows = [];
        foreach ($editable as $key) {
            if (!array_key_exists($key, $fields)) continue;
            $new = $fields[$key];
            $old = $current[$key];
            // Numeric fields: compare by value, not string form — a number
            // <input>'s .value normalizes "1.500" to "1.5" client-side, which
            // would otherwise log every untouched numeric field as "changed"
            // on every save.
            if (isset($numeric[$key])) {
                if ($old === null && $new === null) continue;
                if ($old !== null && $new !== null && abs((float) $old - (float) $new) < 1e-9) continue;
            } elseif ((string) $old === (string) $new) {
                continue;
            }
            $update[$key] = $new;
            $auditRows[] = [
                'module'     => 'gas_fee_settings',
                'field_name' => $current['tx_type'] . '.' . $key,
                'old_value'  => $old,
                'new_value'  => $new,
                'changed_by' => $adminId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (!$update) return true; // nothing changed

        $update['updated_by'] = $adminId;
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('gas_fee_settings', $update);
        if ($auditRows) $this->db->insert_batch('admin_settings_audit', $auditRows);
        return true;
    }
}
