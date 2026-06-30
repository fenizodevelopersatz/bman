<?php
class Commission_model extends CI_Model {

  
    private $table = 'commission_config';
    public function __construct() {
        $this->load->database();
    }
    /*
    |--------------------------------------------------------------------------
    | Direct Commission Check
    |--------------------------------------------------------------------------
    */
    public function direct_commission_get() {
        $check_commission = $this->db->query("SELECT * FROM commission_config where id = '1' ")->row();
        if($check_commission->direct_commission_status){
        return $check_commission->direct_commission;
        }  else {
        return 0;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Direct Commission Send
    |--------------------------------------------------------------------------
    */
    public function direct_commission_send($user_id,$bonus_amount,$invest_id,$token_amount,$earn_type,$invest_date){

          $sponser_info = $this->db->query("SELECT * FROM users where id = '".$user_id."' ")->row();
          $sponsor_id = $sponser_info->sponser;
          
          $token_info = token_info();
          $currency_info = currency_info();

          if($sponsor_id > 0){

            $direct_commission_get = $this->direct_commission_get();
            $description = "Direct commission from ( ".$sponser_info->referral_id." )";
            $check_sponser_active = $this->db->query("SELECT * FROM user_investment where user_id = '".$sponsor_id."' and status = '1' ")->num_rows();
  
            if($check_sponser_active){
  
              if ($direct_commission_get > 0 && $sponsor_id > 0) {

                if($bonus_amount > 0){
                $direct_commission_amount_usd = ($bonus_amount * $direct_commission_get) / 100;  
                } else {
                $direct_commission_amount_usd ='0';
                }

                if($token_amount > 0){
                $direct_commission_amount_token = ($token_amount * $direct_commission_get) / 100;  
                } else {
                $direct_commission_amount_token = '0';
                }
  
                  $wallet_data = array(
                  "user_id" => $sponsor_id,
                  "amount" => $direct_commission_amount_usd,
                  "type" => "direct_commission",
                  "status"  => '1',
                  "invest_id" => $invest_id,
                  "description" => $description,
                  "from_id" => $user_id,
                  "token_amount" => $direct_commission_amount_token,
                  "coin_type" => $earn_type,
                  "date" => date('Y-m-d  H:i:s',strtotime($invest_date)),
                  "coin_id" => $currency_info->id,
                  "token_id" => $token_info->id,
                  );
                  $this->db->insert("history", $wallet_data);
  
              }
  
  
            }

          }
          
          $this->multi_level_commission_send($user_id, $bonus_amount, $invest_id, $token_amount, $earn_type, $invest_date);
    }
    /*
    |--------------------------------------------------------------------------
    | Level Commission Send
    |--------------------------------------------------------------------------
    */
    public function multi_level_commission_send($user_id, $bonus_amount, $invest_id, $token_amount, $earn_type, $invest_date) {
      $commission_config = $this->db->query("SELECT * FROM commission_config WHERE id = '1' ")->row();
      $level_commission_status = $commission_config->level_commission_status;

      if($level_commission_status){

      $level_commissions = explode(',', $commission_config->level_commission);
      $total_levels = $commission_config->total_levels;
  
      $sponsor_id = $user_id;
      
      $sponser_info = $this->db->query("SELECT * FROM users where id = '".$user_id."' ")->row();
      $description = " ( ".$sponser_info->referral_id." )";

      for ($level = 1; $level <= $total_levels; $level++) {
          
         $sponsor_info = $this->db->query("SELECT sponsor_id FROM binary_placement WHERE user_id = '".$sponsor_id."'")->row();
  
          if (!$sponsor_info || $sponsor_info->sponsor_id == 0) {
              break; 
          }
  
          $sponsor_id = $sponsor_info->sponsor_id;

          $check_sponser_active = $this->db->query("SELECT * FROM user_investment where user_id = '".$sponsor_id."' and status = '1' ")->num_rows();
          if($check_sponser_active){
            
          $commission_percentage = isset($level_commissions[$level - 2]) ? $level_commissions[$level - 2] : 0;
  
          if ($commission_percentage > 0 && $level !='1') {
              $commission_usd = ($bonus_amount * $commission_percentage) / 100;
              $commission_token = ($token_amount * $commission_percentage) / 100;
  
              $wallet_data = array(
                  "user_id"       => $sponsor_id,
                  "amount"        => $commission_usd,
                  "type"          => "level_commission",
                  "status"        => '1',
                  "invest_id"     => $invest_id,
                  "description"   => "Level $level commission from : $description",
                  "from_id"       => $user_id,
                  "token_amount"  => $commission_token,
                  "coin_type"     => $earn_type,
                  "date"          => date('Y-m-d H:i:s', strtotime($invest_date)),
                  "coin_id"       => currency_info()->id,
                  "token_id"      => token_info()->id,
                  "level_count"   => $level
              );
              $this->db->insert("history", $wallet_data);
          }
      }
    }

    }

  }


  public function get_settings(): stdClass {
        $row = $this->db->get_where($this->table, ['id' => 1])->row();
        if (!$row) {
            $this->db->insert($this->table, ['id' => 1, 'update_date' => date('Y-m-d H:i:s')]);
            $row = $this->db->get_where($this->table, ['id' => 1])->row();
        }
        return $row;
    }

    public function save_settings(array $data): bool {
        $data['update_date'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, ['id' => 1]);
    }
    
    
}
?>
