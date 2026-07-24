<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Specialroi_model — year-wise ROI structure for Special Offer packages.
 *
 * A Special Offer package defines, per year (1..7), a Monthly ROI % and a
 * Maturity %. A member who buys a special stake for N years earns that year's
 * Monthly ROI each month for the term, plus the year-N Maturity % as a lump at
 * maturity. Normal packages are untouched and keep using staking_roi_structure.
 *
 * Every edit is written to staking_special_roi_audit.
 */
class Specialroi_model extends CI_Model
{
    const MAX_YEARS = 7;

    /* ------------------------------- reads ------------------------------- */

    /** IDs of every package currently flagged Special Offer. */
    public function specialPackageIds()
    {
        $rows = $this->db->select('id')->where('is_special', 1)->get('staking_packages')->result_array();
        return array_map(function ($r) { return (int) $r['id']; }, $rows);
    }

    /** All special packages (id, name, stake_amount, bonus_percent). */
    public function specialPackages()
    {
        return $this->db->select('id, name, stake_amount, bonus_percent, is_active')
                        ->where('is_special', 1)
                        ->order_by('sort_order', 'ASC')->order_by('stake_amount', 'ASC')
                        ->get('staking_packages')->result_array();
    }

    /**
     * Year map for one package: [year => [monthly_roi_percent, maturity_percent,
     * is_active]] for years 1..MAX_YEARS, dense (missing years default to 0/off).
     */
    public function forPackage($packageId)
    {
        $rows = $this->db->where('package_id', (int) $packageId)
                         ->get('staking_special_roi')->result_array();
        $byYear = [];
        foreach ($rows as $r) $byYear[(int) $r['year_number']] = $r;

        $out = [];
        for ($y = 1; $y <= self::MAX_YEARS; $y++) {
            $r = $byYear[$y] ?? null;
            $out[$y] = [
                'monthly_roi_percent' => (float) ($r['monthly_roi_percent'] ?? 0),
                'maturity_percent'    => (float) ($r['maturity_percent'] ?? 0),
                'is_active'           => (int) ($r['is_active'] ?? 0),
            ];
        }
        return $out;
    }

    /** Only the ACTIVE, non-zero year rows a member may actually pick. */
    public function offeredYears($packageId)
    {
        $out = [];
        foreach ($this->forPackage($packageId) as $y => $r) {
            if ($r['is_active'] && ($r['monthly_roi_percent'] > 0 || $r['maturity_percent'] > 0)) {
                $out[$y] = $r;
            }
        }
        return $out;
    }

    /** The active row for a specific (package, year), or null. */
    public function rate($packageId, $year)
    {
        $r = $this->db->where(['package_id' => (int) $packageId, 'year_number' => (int) $year, 'is_active' => 1])
                      ->get('staking_special_roi')->row_array();
        if (!$r) return null;
        return [
            'monthly_roi_percent' => (float) $r['monthly_roi_percent'],
            'maturity_percent'    => (float) $r['maturity_percent'],
        ];
    }

    /* ------------------------------- writes ------------------------------ */

    /**
     * Upsert one year row for a package and audit whatever actually changed.
     * $data = ['monthly_roi_percent'=>, 'maturity_percent'=>, 'is_active'=>].
     */
    public function saveYear($packageId, $year, array $data, $adminId)
    {
        $packageId = (int) $packageId;
        $year = (int) $year;
        if ($year < 1 || $year > self::MAX_YEARS) return [false, 'Invalid year.'];

        $monthly  = max(0, (float) ($data['monthly_roi_percent'] ?? 0));
        $maturity = max(0, (float) ($data['maturity_percent'] ?? 0));
        $active   = (int) !!($data['is_active'] ?? 0);

        $old = $this->db->where(['package_id' => $packageId, 'year_number' => $year])
                        ->get('staking_special_roi')->row_array();

        $now = date('Y-m-d H:i:s');
        if ($old) {
            $this->db->where('id', (int) $old['id'])->update('staking_special_roi', [
                'monthly_roi_percent' => $monthly,
                'maturity_percent'    => $maturity,
                'is_active'           => $active,
                'updated_at'          => $now,
            ]);
            $this->_auditChange($packageId, $year, 'monthly_roi_percent', $old['monthly_roi_percent'], $monthly, $adminId);
            $this->_auditChange($packageId, $year, 'maturity_percent',    $old['maturity_percent'],    $maturity, $adminId);
            $this->_auditChange($packageId, $year, 'is_active',           $old['is_active'],           $active,   $adminId);
        } else {
            $this->db->insert('staking_special_roi', [
                'package_id'          => $packageId,
                'year_number'         => $year,
                'monthly_roi_percent' => $monthly,
                'maturity_percent'    => $maturity,
                'is_active'           => $active,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $this->_audit($packageId, $year, 'monthly_roi_percent', null, $monthly, $adminId);
            $this->_audit($packageId, $year, 'maturity_percent',    null, $maturity, $adminId);
            $this->_audit($packageId, $year, 'is_active',           null, $active,   $adminId);
        }
        return [true, 'Saved.'];
    }

    /** Audit only if the value actually changed (avoids noise). */
    private function _auditChange($packageId, $year, $field, $old, $new, $adminId)
    {
        if ((string) (float) $old === (string) (float) $new && $field !== 'is_active') return;
        if ((string) (int) $old === (string) (int) $new && $field === 'is_active') return;
        $this->_audit($packageId, $year, $field, $old, $new, $adminId);
    }

    private function _audit($packageId, $year, $field, $old, $new, $adminId)
    {
        $this->db->insert('staking_special_roi_audit', [
            'package_id'  => (int) $packageId,
            'year_number' => (int) $year,
            'field'       => $field,
            'old_value'   => $old === null ? null : (string) $old,
            'new_value'   => $new === null ? null : (string) $new,
            'changed_by'  => (int) $adminId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /** Record a package special on/off flip (called from the package toggle). */
    public function auditPackageFlag($packageId, $on, $adminId)
    {
        $this->_audit($packageId, 0, 'package_special', $on ? 0 : 1, $on ? 1 : 0, $adminId);
    }

    public function auditLog($limit = 200)
    {
        return $this->db->select('a.*, p.name AS package_name')
                        ->from('staking_special_roi_audit a')
                        ->join('staking_packages p', 'p.id = a.package_id', 'left')
                        ->order_by('a.id', 'DESC')->limit((int) $limit)
                        ->get()->result_array();
    }
}
