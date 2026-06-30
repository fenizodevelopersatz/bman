<?php
Class Common_model extends CI_Model {



    /*
    |--------------------------------------------------------------------------
    | Login Verification
    |--------------------------------------------------------------------------
    */
		
		public function loginVerify($username,$password){

            $user_id = $this->db->query("SELECT * FROM `admin_members` where 
            admin_email = '".$username."'  ")->row();

            if (password_verify($password, $user_id->admin_password)) {
             
			 if($user_id) {
  
				if($user_id->admin_status == '1'){
					
				if($user_id->get_status == '1'){
					
                    return array(
                        'status' => false,
                        'message' =>"Account is deactivate!!"
                        );
					
				} else { 
				 
                  return array(
                    'status' => TRUE,
                    'data' => $user_id
                  );
					
				}

			}

        }


		} else {

            return array(
            'status' => false,
            'message' =>"Password doesn't match!!"
            );
            
		}

	}


      
      /*
      |--------------------------------------------------------------------------
      | Login Verification
      |--------------------------------------------------------------------------
      */
                  
		public function userloginVerify($username,$password){

            $user_id = $this->db->query("SELECT * FROM `users` where 
            email = '".$username."'  ")->row();

          

            if (password_verify($password, $user_id->password)) {
             
			 if($user_id) {
  
				if($user_id->status == '1'){
					
				if($user_id->get_status == '1'){
					
                    return array(
                        'status' => false,
                        'message' =>"Account is deactivate!!"
                        );
					
				} else { 
				 
                  return array(
                    'status' => TRUE,
                    'data' => $user_id
                  );
					
				}

			}

        }


		} else {

            return array(
            'status' => false,
            'message' =>"Password doesn't match!!"
            );
            
		}

	}


}