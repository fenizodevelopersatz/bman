<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Landing_model
 * -------------------------------------------------------------------
 * Single model for the whole dynamic landing page.
 *  - Singleton fields  -> landing_settings (section, skey, svalue)
 *  - Repeater sections -> dedicated row tables
 * Mirrors the existing site_settings key/value approach.
 */
class Landing_model extends CI_Model
{
    /** Repeater tables the admin/public layers iterate over */
    public $repeaters = array(
        'menu'           => 'landing_menu',
        'brands'         => 'landing_brands',
        'features'       => 'landing_features',
        'work'           => 'landing_work',
        'exchange_logos' => 'landing_exchange_logos',
        'crypto'         => 'landing_crypto',
        'faq'            => 'landing_faq',
        'roadmap'        => 'landing_roadmap',
        'team'           => 'landing_team',
    );

    public function __construct()
    {
        parent::__construct();
    }

    /* ============================ SINGLETON KV ======================== */

    /** Get one value */
    public function get($section, $key, $default = '')
    {
        $row = $this->db->get_where('landing_settings',
            array('section' => $section, 'skey' => $key))->row();
        return $row ? $row->svalue : $default;
    }

    /** Get every key of a section as an associative array */
    public function get_section($section)
    {
        $rows = $this->db->get_where('landing_settings',
            array('section' => $section))->result();
        $out = array();
        foreach ($rows as $r) {
            $out[$r->skey] = $r->svalue;
        }
        return $out;
    }

    /** Upsert one value */
    public function set($section, $key, $value)
    {
        $exists = $this->db->get_where('landing_settings',
            array('section' => $section, 'skey' => $key))->row();

        $data = array('svalue' => $value, 'update_date' => date('Y-m-d H:i:s'));

        if ($exists) {
            $this->db->where('id', $exists->id)->update('landing_settings', $data);
        } else {
            $data['section'] = $section;
            $data['skey']    = $key;
            $this->db->insert('landing_settings', $data);
        }
        return true;
    }

    /** Upsert a whole section from an assoc array of key=>value */
    public function save_section($section, array $fields)
    {
        foreach ($fields as $key => $value) {
            $this->set($section, $key, $value);
        }
        return true;
    }

    /* ============================ REPEATERS ========================== */

    private function table_for($repeater)
    {
        return isset($this->repeaters[$repeater]) ? $this->repeaters[$repeater] : false;
    }

    /** List rows of a repeater (optionally only active, ordered by sort) */
    public function items($repeater, $only_active = false)
    {
        $table = $this->table_for($repeater);
        if (!$table) return array();

        if ($only_active) $this->db->where('status', 1);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        return $this->db->get($table)->result();
    }

    public function item($repeater, $id)
    {
        $table = $this->table_for($repeater);
        if (!$table) return null;
        return $this->db->get_where($table, array('id' => $id))->row();
    }

    /** Insert or update a repeater row. $data already whitelisted by controller */
    public function save_item($repeater, array $data, $id = 0)
    {
        $table = $this->table_for($repeater);
        if (!$table) return false;

        if ($id) {
            $this->db->where('id', $id)->update($table, $data);
            return $id;
        }
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    public function delete_item($repeater, $id)
    {
        $table = $this->table_for($repeater);
        if (!$table) return false;
        return $this->db->where('id', $id)->delete($table);
    }

    public function toggle_status($repeater, $id)
    {
        $table = $this->table_for($repeater);
        if (!$table) return false;
        $row = $this->item($repeater, $id);
        if (!$row) return false;
        $new = $row->status ? 0 : 1;
        $this->db->where('id', $id)->update($table, array('status' => $new));
        return $new;
    }

    /** Persist a new sort order: $order = [id1, id2, ...] */
    public function reorder($repeater, array $order)
    {
        $table = $this->table_for($repeater);
        if (!$table) return false;
        $i = 1;
        foreach ($order as $id) {
            $this->db->where('id', (int)$id)->update($table, array('sort_order' => $i++));
        }
        return true;
    }

    /* ====================== SNAPSHOT / VERSIONS ====================== */

    /** Build a full JSON snapshot of every section + repeater */
    public function snapshot()
    {
        $data = array('settings' => array());
        foreach ($this->db->get('landing_settings')->result() as $r) {
            $data['settings'][$r->section][$r->skey] = $r->svalue;
        }
        foreach ($this->repeaters as $key => $table) {
            $data[$key] = $this->db->get($table)->result_array();
        }
        return $data;
    }

    public function save_version($label, $created_by = null)
    {
        $this->db->insert('landing_versions', array(
            'label'      => $label,
            'snapshot'   => json_encode($this->snapshot()),
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return $this->db->insert_id();
    }

    public function versions($limit = 30)
    {
        $this->db->order_by('id', 'DESC')->limit($limit);
        return $this->db->get('landing_versions')->result();
    }

    public function version($id)
    {
        return $this->db->get_where('landing_versions', array('id' => $id))->row();
    }

    /** Restore (import) a snapshot array into the live tables */
    public function restore(array $data)
    {
        if (!empty($data['settings'])) {
            foreach ($data['settings'] as $section => $kv) {
                foreach ($kv as $k => $v) {
                    $this->set($section, $k, $v);
                }
            }
        }
        foreach ($this->repeaters as $key => $table) {
            if (!isset($data[$key]) || !is_array($data[$key])) continue;
            $this->db->truncate($table);
            foreach ($data[$key] as $row) {
                unset($row['id']);          // let AUTO_INCREMENT assign fresh ids
                if (!empty($row)) $this->db->insert($table, $row);
            }
        }
        return true;
    }
}
