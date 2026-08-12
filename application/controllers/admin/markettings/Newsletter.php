<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Newsletter extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['newsletter_markettings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }

    /*
    |--------------------------------------------------------------------------
    | Mail Index
    |--------------------------------------------------------------------------
    */
       public function index(){

        $this->data['title'] = "News Letter";
        $this->data['card_title'] = "Send Your News Letter to members";
		$this->load->view('admin/newsletter/index',$this->data);

    }

    /**
     * Remote Select2 member lookup.
     *
     * The Select2 id is the users.id value posted by the newsletter form.
     */
    public function member_search()
    {
        $term = trim((string) ($this->input->get('q', true) ?: $this->input->get('term', true)));
        $page = max(1, (int) $this->input->get('page', true));
        $limit = 100;
        $offset = ($page - 1) * $limit;

        $this->db->select('id, username, name, first_name, last_name, email, referral_id, profile_img, image')
                 ->from('users')
                 ->where('status', 1);

        if ($term !== '') {
            $this->db->group_start()
                     ->like('username', $term)
                     ->or_like('name', $term)
                     ->or_like('first_name', $term)
                     ->or_like('last_name', $term)
                     ->or_like('email', $term)
                     ->or_like('referral_id', $term)
                     ->group_end();
        }

        $rows = $this->db->order_by('username', 'ASC')
                         ->limit($limit + 1, $offset)
                         ->get()
                         ->result_array();

        $more = count($rows) > $limit;
        if ($more) {
            array_pop($rows);
        }

        $results = [];
        foreach ($rows as $row) {
            $name = trim((string) $row['name']);
            if ($name === '') {
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            }
            if ($name === '') {
                $name = (string) $row['username'];
            }

            $profileImage = !empty($row['profile_img']) ? $row['profile_img'] : $row['image'];
            if (!$profileImage) {
                $avatar = default_avatar_url();
            } elseif (preg_match('~^(https?://|assets/|uploads/)~i', $profileImage)) {
                $avatar = media_url($profileImage);
            } else {
                $relativeImage = 'assets/images/' . ltrim($profileImage, '/');
                $avatar = is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeImage))
                    ? base_url($relativeImage)
                    : default_avatar_url();
            }

            $results[] = [
                'id'          => (int) $row['id'],
                'text'        => $name . ' (' . $row['email'] . ' · ' . $row['referral_id'] . ')',
                'name'        => $name,
                'email'       => (string) $row['email'],
                'referral_id' => (string) $row['referral_id'],
                'avatar'      => $avatar,
            ];
        }

        return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'results' => $results,
                        'pagination' => ['more' => $more],
                    ]));
    }
    /*
    |--------------------------------------------------------------------------
    | News Letter Send
    |--------------------------------------------------------------------------
    */
    public function send()
    {
        $selected_members = $this->input->post('selected_members'); 
        $mail_subject = $this->input->post('mail_subject'); 
        $mail_content = $this->input->post('mail_content'); 
        

        if (empty($selected_members) || empty($mail_subject) || empty($mail_content)) {
            $response = array(
                'status' => false,
                'message' => "Please enter all inputbox"
            );
            echo json_encode($response);
            exit();
        }

        $emails = $this->getEmailsByIds($selected_members);

        if (empty($emails)) {
            $response = array(
                'status' => false,
                'message' => "No valid email address"
            );
            echo json_encode($response);
            exit();
        }

        $email_recipients = implode(',', $emails);

        $mail_config = $this->db->query("SELECT * FROM `email_config` where id = '1' ")->row();
    
        if($mail_config->smtp_status > 0){
    
        $host = $mail_config->host;
        $smtp_auth = $mail_config->smtp_auth;
        $username = $mail_config->username;
        $password = $mail_config->password;
        $smtpsecure = $mail_config->smtpsecure;
        $port = $mail_config->port;
        $from_name = $mail_config->from_name;
        $from_mail = $mail_config->from_mail;
    
        $mail = new PHPMailer(true);
    
        try {
    
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = $smtp_auth;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $smtpsecure;
        $mail->Port = $port;
        $mail->setFrom($from_mail, $from_name);
        $mail->addAddress($email_recipients);
        $mail->isHTML(true);
        $mail->Subject = $mail_subject;
        $mail->Body    = $mail_content;
    
        $mail->send();
        return true;
        } catch (Exception $e) {
        return false;
        }
    
        } else {
    
        $admin_mail = $mail_config->php_mail;
        $headers = "From: $admin_mail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail($email_recipients, $mail_subject, $mail_content, $headers);
        return true;
    
        }
    


        if ($this->email->send()) {

            $response = array(
                'status' => true,
                'message' => "Newsletter sent successfully to all selected members"
            );
            echo json_encode($response);
            exit();

        } else {
            
            $response = array(
                'status' => false,
                'message' => "Newsletter send faild"
            );
            echo json_encode($response);
            exit();
        
        }

        redirect($_SERVER['HTTP_REFERER']);
    }


    /*
    |--------------------------------------------------------------------------
    | User Email Get
    |--------------------------------------------------------------------------
    */
    public function getEmailsByIds($ids){

        $this->db->select('email');
        $this->db->where_in('id', $ids);
        $query = $this->db->get('users'); 
        return array_column($query->result_array(), 'email');

    }

}
