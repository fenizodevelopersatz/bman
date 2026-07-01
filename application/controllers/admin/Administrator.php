<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Administrator extends CI_Controller {

	public function __construct() {
		parent::__construct();

		if($this->session->userdata('admin_logged_in') && $this->session->userdata('admin_login')) {
			$this->lang->load('common',$this->session->userdata('language'));
		} else {
			redirect('admin/login');
		}
	}
	

	public function index()
	{
		
		if($this->session->userdata('admin_logged_in')) {

			$this->data['title'] = "Dashboard";
			$this->data['card_tilte'] = "Dashboard";
			$this->data['currency_info'] = currency_info();
			$this->data['token_info'] = token_info();
			
			$this->data['currency_info'] = currency_info();
			$this->data['token_info'] = token_info();
		

			$this->load->view('admin/dashboard/index', $this->data);
	    } else {
	    	redirect('admin/login');
	    }
 		
	}
	

	public function balance_info(){

		$bsc_lending_info = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE 
		type = 'mining' 
		AND (transaction_id IS NULL OR transaction_id != 'reinvestment')
		AND hash_id NOT IN ('user-wallet', 'admin-made')
		")->row();

		$wallet_lending_info = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE 
		type = 'mining' 
		AND (transaction_id IS NULL OR transaction_id != 'reinvestment')
		AND hash_id IN ('user-wallet')
		")->row();

		$admin_lending_info = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE 
		type = 'mining' 
		AND (transaction_id IS NULL OR transaction_id != 'reinvestment')
		AND hash_id IN ('admin-made')
		")->row();

		$lending_info = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE 
		type = 'mining' 
		AND (transaction_id IS NULL OR transaction_id != 'reinvestment')
		")->row();


		$level_commissions_2 = get_transaction_level_commission('level_commission','2');
		$level_commissions_3 = get_transaction_level_commission('level_commission','3');
		$level_commissions_4 = get_transaction_level_commission('level_commission','4');
		$level_commissions_5 = get_transaction_level_commission('level_commission','5');


		$direc_commission_info = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE 
		type = 'direct_commission' 
		")->row();


        $active_lending = $this->db->query("SELECT * FROM `user_investment` where status = '1' ")->num_rows();
		$inactive_lending = $this->db->query("SELECT * FROM `user_investment` where status = '2' ")->num_rows();
		$reinvest_lending = $this->db->query("SELECT * FROM `user_investment` where status = '1' and reinvest_id !='0' ")->num_rows();

		$total_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('direct_commission', 'level_commission')
		")->row();

				
		$total_rank_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('rank_commission')
		")->row();


		$total_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('direct_commission', 'level_commission')
		")->row();

				
		$total_bonus_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('profit', 'binary_commission')
		")->row();


		$total_binary_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('binary_commission')
		")->row();

		$total_profit_commission = $this->db->query("SELECT 
		SUM(amount) AS total_amount,  
		SUM(token_amount) AS total_token_amount  
		FROM history 
		WHERE  type IN ('profit')
		")->row();

		$rank_achived = $this->db->query("SELECT * FROM users where rank_id > 0 ")->num_rows();

		$this->data['bsc_lending_currency'] = str_replace(',', '', $bsc_lending_info->total_amount);
		$this->data['bsc_lending_token'] = str_replace(',', '', $bsc_lending_info->total_token_amount);
		$this->data['wallet_lending_token'] = str_replace(',', '', $wallet_lending_info->total_token_amount);
		$this->data['wallet_lending_currency'] = str_replace(',', '', $wallet_lending_info->total_amount);
		$this->data['admin_lending_token'] = str_replace(',', '', $admin_lending_info->total_token_amount);
		$this->data['admin_lending_currency'] =  str_replace(',', '', $admin_lending_info->total_amount);
		$this->data['lending_token'] = str_replace(',', '', $lending_info->total_amount);
		$this->data['lending_currency'] =  str_replace(',', '', $lending_info->total_token_amount);
		$this->data['active_lending'] = $active_lending;
		$this->data['inactive_lending'] = $inactive_lending;
		$this->data['reinvest_lending'] = $reinvest_lending;

		$this->data['direct_currency'] = str_replace(',', '', $direc_commission_info->total_amount);
		$this->data['direct_token'] =  str_replace(',', '', $direc_commission_info->total_token_amount);

		$this->data['level_2_token'] = str_replace(',', '', $level_commissions_2['token_amount']);
		$this->data['level_2_currency'] =  str_replace(',', '', $level_commissions_2['mybalance']);

		$this->data['level_3_token'] = str_replace(',', '', $level_commissions_3['token_amount']);
		$this->data['level_3_currency'] =  str_replace(',', '', $level_commissions_3['mybalance']);

		$this->data['level_4_token'] = str_replace(',', '', $level_commissions_4['token_amount']);
		$this->data['level_4_currency'] =  str_replace(',', '', $level_commissions_4['mybalance']);

		$this->data['level_5_token'] = str_replace(',', '', $level_commissions_5['token_amount']);
		$this->data['level_5_currency'] =  str_replace(',', '', $level_commissions_5['mybalance']);

		$this->data['commission_token'] = str_replace(',', '', $total_commission->total_token_amount);
		$this->data['commission_currency'] =  str_replace(',', '', $total_commission->total_amount);
		$this->data['rank_token'] = str_replace(',', '', $total_rank_commission->total_token_amount);
		$this->data['rank_currency'] =  str_replace(',', '', $total_rank_commission->total_amount);
		$this->data['profit_token'] = str_replace(',', '', $total_bonus_commission->total_token_amount);
		$this->data['profit_currency'] =  str_replace(',', '', $total_bonus_commission->total_amount);
		$this->data['binary_token'] = str_replace(',', '', $total_binary_commission->total_token_amount);
		$this->data['binary_currency'] =  str_replace(',', '', $total_binary_commission->total_amount);
		$this->data['daily_token'] = str_replace(',', '', $total_profit_commission->total_token_amount);
		$this->data['daily_currency'] =  str_replace(',', '', $total_profit_commission->total_amount);
		$this->data['rank_achived'] = $rank_achived;

				// ---------- monthly chart payload (last 12 months) ----------
		list($mkeys, $mlabels) = $this->_months_last_n(12);

		// legacy binary commission (if present in your data)
		$series_binary_legacy = $this->_series_sum_by_month('binary_commission', $mkeys);

		// new pair commission (your new engine writes this)
		$series_pair          = $this->_series_sum_by_month('pair_commission', $mkeys);

		// PV/BV (we logged them as pv_volume / bv_volume with units in `amount`)
		$series_pv            = $this->_series_sum_by_month('pv_volume', $mkeys);
		$series_bv            = $this->_series_sum_by_month('bv_volume', $mkeys);

		// attach to result
		$this->data['chart'] = [
			'labels'            => $mlabels,
			'binary_legacy'     => $series_binary_legacy,
			'pair_commission'   => $series_pair,
			'pv'                => $series_pv,
			'bv'                => $series_bv,
		];


		$return = array(
			"status" => true,
			"result" => $this->data
		);

		echo json_encode($return);

	}


		// --- helpers for monthly chart data --- //
	private function _months_last_n($n = 12)
	{
		$keys = [];
		$labels = [];
		// oldest -> newest
		for ($i = $n-1; $i >= 0; $i--) {
			$ts = strtotime(date('Y-m-01') . " -{$i} months");
			$keys[]   = date('Y-m', $ts);       // e.g., 2025-02
			$labels[] = date('M Y', $ts);       // e.g., Feb 2025
		}
		return [$keys, $labels];
	}

	/**
	 * Sum `amount` by month for a given type in history table.
	 * Fills in zeros for months with no rows.
	 */
	private function _series_sum_by_month($type, array $keys)
	{
		$start = $keys[0] . '-01 00:00:00';
		$rows = $this->db->select("DATE_FORMAT(`date`,'%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total", false)
						->from('history')
						->where('type', $type)
						->where('date >=', $start)
						->group_by('ym')
						->order_by('ym','ASC')
						->get()->result();

		$map = [];
		foreach ($rows as $r) $map[$r->ym] = (float)$r->total;

		$out = [];
		foreach ($keys as $k) $out[] = isset($map[$k]) ? (float)$map[$k] : 0.0;
		return $out;
	}


	public function getMlmCommissionData()
{
    $type      = $this->input->post('type') ?: 'daily';
    $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
    $to_date   = $this->input->post('to_date')   ? date('Y-m-d', strtotime($this->input->post('to_date')))   : '';

    // build select + grouping
    // bucket = label we’ll return (e.g. "27 Aug", "2025-W35", "Aug-2025", "2025")
    if ($from_date && $to_date) {
        // force daily buckets when custom range is used
        $type = 'daily';
        $this->db->select("
            DATE(`date`) AS bucket,
            SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_legacy,
            SUM(CASE WHEN type='pair_commission'   THEN amount ELSE 0 END) AS pair_commission,
            SUM(CASE WHEN type='pv_volume'         THEN amount ELSE 0 END) AS pv,
            SUM(CASE WHEN type='bv_volume'         THEN amount ELSE 0 END) AS bv
        ", false);
        $this->db->where('DATE(`date`) >=', $from_date);
        $this->db->where('DATE(`date`) <=', $to_date);
        $this->db->group_by('DATE(`date`)');
        $label_fmt = 'daily';
    } else {
        switch ($type) {
            case 'weekly':
                $this->db->select("
                    DATE_FORMAT(`date`, '%x-W%v') AS bucket, -- ISO week
                    SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_legacy,
                    SUM(CASE WHEN type='pair_commission'   THEN amount ELSE 0 END) AS pair_commission,
                    SUM(CASE WHEN type='pv_volume'         THEN amount ELSE 0 END) AS pv,
                    SUM(CASE WHEN type='bv_volume'         THEN amount ELSE 0 END) AS bv
                ", false);
                $this->db->where('`date` >=', date('Y-m-d', strtotime('-3 months')));
                $this->db->group_by('YEARWEEK(`date`, 1)');
                $label_fmt = 'weekly';
                break;

            case 'monthly':
                $this->db->select("
                    DATE_FORMAT(`date`, '%b-%Y') AS bucket,
                    SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_legacy,
                    SUM(CASE WHEN type='pair_commission'   THEN amount ELSE 0 END) AS pair_commission,
                    SUM(CASE WHEN type='pv_volume'         THEN amount ELSE 0 END) AS pv,
                    SUM(CASE WHEN type='bv_volume'         THEN amount ELSE 0 END) AS bv
                ", false);
                $this->db->where('`date` >=', date('Y-m-d', strtotime('-12 months')));
                $this->db->group_by('YEAR(`date`), MONTH(`date`)');
                $label_fmt = 'monthly';
                break;

            case 'yearly':
                $this->db->select("
                    DATE_FORMAT(`date`, '%Y') AS bucket,
                    SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_legacy,
                    SUM(CASE WHEN type='pair_commission'   THEN amount ELSE 0 END) AS pair_commission,
                    SUM(CASE WHEN type='pv_volume'         THEN amount ELSE 0 END) AS pv,
                    SUM(CASE WHEN type='bv_volume'         THEN amount ELSE 0 END) AS bv
                ", false);
                $this->db->group_by('YEAR(`date`)');
                $label_fmt = 'yearly';
                break;

            default: // daily (last 30d)
                $type = 'daily';
                $this->db->select("
                    DATE(`date`) AS bucket,
                    SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_legacy,
                    SUM(CASE WHEN type='pair_commission'   THEN amount ELSE 0 END) AS pair_commission,
                    SUM(CASE WHEN type='pv_volume'         THEN amount ELSE 0 END) AS pv,
                    SUM(CASE WHEN type='bv_volume'         THEN amount ELSE 0 END) AS bv
                ", false);
                $this->db->where('DATE(`date`) >=', date('Y-m-d', strtotime('-30 days')));
                $this->db->group_by('DATE(`date`)');
                $label_fmt = 'daily';
                break;
        }
    }

    $this->db->order_by('MIN(`date`)', 'ASC', false); // safe for all buckets
    $q = $this->db->get('history');

    $labels = $bin = $pair = $pv = $bv = [];
    foreach ($q->result() as $r) {
        // pretty day labels when daily
        if ($label_fmt === 'daily') {
            $labels[] = date('d M', strtotime($r->bucket));
        } else {
            $labels[] = $r->bucket;
        }
        $bin[]  = (float)$r->binary_legacy;
        $pair[] = (float)$r->pair_commission;
        $pv[]   = (float)$r->pv;
        $bv[]   = (float)$r->bv;
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'status'  => true,
            'message' => 'Commission chart',
            'data'    => [
                'labels'        => $labels,
                'binary_legacy' => $bin,
                'pair_commission'=> $pair,
                'pv'            => $pv,
                'bv'            => $bv,
            ]
        ]));
}


}
