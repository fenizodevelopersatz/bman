<?php

require_once __DIR__ . '/ETH_MASTER/vendor/autoload.php';
// require_once __DIR__ . '/smtp/vendor/autoload.php';

// use Web3p\EthereumWallet\Wallet;
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/smtp/vendor/autoload.php';

class Mlm_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    public function usernameExists($username)
    {
        return $this->db->get_where('users', ['username' => $username])->num_rows() > 0;
    }
    /**
     * Get the auto placement count
     */
    private function getAutoPlacementCount()
    {
        return $this->db->where('placement_type', 'auto')->count_all_results('binary_placement');
    }
    /**
     * Get the referral code
     */
    public function generateReferralID()
    {
        do {
            $referral_id = "NEXMAN" . mt_rand(100000, 999999);
            $exists = $this->db->where('referral_id', $referral_id)->get('users')->num_rows();
        } while ($exists > 0);

        return $referral_id;
    }
    /**
     * Get placement code
     */
    private function findAvailablePlacement($sponsor_id, $is_direct = false)
    {
        $user = $this->db->get_where('users', ['id' => $sponsor_id])->row();
        if (!$user)
            return false;

        $left_user = $this->db->get_where('binary_placement', ['parent_id' => $sponsor_id, 'position' => 'left'])->row();
        $right_user = $this->db->get_where('binary_placement', ['parent_id' => $sponsor_id, 'position' => 'right'])->row();

        if ($is_direct) {
            if (!$left_user) {
                return ['parent_id' => $sponsor_id, 'position' => 'left', 'type' => 'direct'];
            }
            if (!$right_user) {
                return ['parent_id' => $sponsor_id, 'position' => 'right', 'type' => 'direct'];
            }
        }

        $autoPlacementCount = $this->getAutoPlacementCount();
        $preferredPosition = ($autoPlacementCount % 2 == 0) ? 'left' : 'right';

        if ($preferredPosition === 'left') {
            $lastLeftUser = $this->getLastLegUser($sponsor_id, 'left');
            return ['parent_id' => $lastLeftUser, 'position' => 'left', 'type' => 'auto'];
        } else {
            $lastRightUser = $this->getLastLegUser($sponsor_id, 'right');
            return ['parent_id' => $lastRightUser, 'position' => 'right', 'type' => 'auto'];
        }
    }
    /**
     * Get the last user in the left or right leg
     */
    private function getLastLegUser($parent_id, $position)
    {
        $current_user = $this->db->get_where('binary_placement', ['parent_id' => $parent_id, 'position' => $position])->row();

        while ($current_user) {
            $next_user = $this->db->get_where('binary_placement', ['parent_id' => $current_user->user_id, 'position' => $position])->row();
            if (!$next_user) {
                return $current_user->user_id;
            }
            $current_user = $next_user;
        }

        return $parent_id;
    }
    /**
     * Get register
     */
    public function registerUser($name, $email, $sponsor_id = null, $sponser_leg = null, $password)
    {


        // $this->load->library('google_authendicator');
        // $google2fa = new Google_authendicator();
        // $secret = $google2fa->createSecret();
        // $secret_img = $this->imageGenerate($secret);
        $referral_id = $this->generateReferralID();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $data = [
            'username' => $name,
            'email' => $email,
            'sponser' => $sponsor_id,
            'password' => $hashedPassword,
            'status' => '1',
            'referral_id' => $referral_id,
            // 'twofactorsecret' => $secret,
            // 'twofacode_path' => base_url().''.$secret_img,
        ];
        $this->db->insert('users', $data);
        $user_id = $this->db->insert_id();


        if (!$user_id) {
            return ['error' => 'User registration failed'];
        }

        $placement = null;

        if ($sponser_leg) {
            $placement = $this->findAvailablePlacementByLeg($sponsor_id, $sponser_leg);
        }

        if (!$placement) {

            $placement = $this->findAvailablePlacement($sponsor_id, true);

            if (!$placement) {
                $placement = $this->findAvailablePlacement($sponsor_id, false);
            }
        }

        if ($placement) {
            $placement_data = [
                'user_id' => $user_id,
                'sponsor_id' => $sponsor_id,
                'parent_id' => $placement['parent_id'],
                'position' => $placement['position'],
                'placement_type' => $placement['type'],
                'placed_at' => date('Y-m-d H:i:s'),
                'direct_placement' => ($placement['type'] === 'direct') ? 1 : 0
            ];
            $this->db->insert('binary_placement', $placement_data);

            //    $this->create_wallet($user_id,$email,$referral_id,$name,$secret);

        } else {
            return ['error' => 'No available placement found'];
        }

        return $user_id;
    }


    public function online_image_generate($image_code)
    {
        if ($image_code != "") {
            $secret_img = $this->imageGenerate($image_code);

            $image_path = base_url($secret_img);

            $array = array(
                "status" => true,
                "message" => $image_path
            );
        } else {
            $array = array(
                "status" => false,
                'message' => 'No image found'
            );
        }
        return $array;
    }

    public function image_save()
    {

        if (!empty($_FILES['images']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('mlm_image_save', 'Uploads disabled');
                return ['status' => false, 'message' => 'Uploads disabled'];
            }

            $originalName = $_FILES['images']['name'];
            $tempFile = $_FILES['images']['tmp_name'];

            $extension = pathinfo($originalName, PATHINFO_EXTENSION);

            $uniqueName = 'image_' . time() . '_' . uniqid() . '.' . $extension;

            $targetDir = './assets/images/support/';
            $targetFile = $targetDir . $uniqueName;

            if (move_uploaded_file($tempFile, $targetFile)) {

                $array = array(
                    "status" => true,
                    "message" => base_url($targetFile)
                );

            } else {
                $array = array(
                    "status" => false,
                    'message' => 'No image found'
                );
            }
        } else {

            $array = array(
                "status" => false,
                'message' => 'No image found'
            );
        }

        return $array;
    }



    private function findAvailablePlacementByLeg($sponsor_id, $sponser_leg)
    {

        $user = $this->db->get_where('users', ['id' => $sponsor_id])->row();
        if (!$user)
            return false;
        $position = strtolower($sponser_leg) === 'left' ? 'left' : 'right';
        $lastUser = $this->getLastLegUser($sponsor_id, $position);
        $type = ($sponsor_id == $lastUser) ? 'direct' : 'auto';

        return ['parent_id' => $lastUser, 'position' => $position, 'type' => $type];

    }


    public function create_wallet($user_id, $email, $referral_id, $name, $secret)
    {

        $wallet = new Wallet();
        $mnemonicLength = 12;
        $wallet->generate($mnemonicLength);

        $encryipt_account = $this->encrypt_account($wallet->mnemonic);
        $encryipt_key = $this->encrypt_account($wallet->privateKey);
        $wallet_qrimage = $this->imageGenerate($wallet->address);

        $query = $this->db->get_where('email_config', array('id' => 1));
        $config = $query->row_array();

        $wallet_insert = array(
            "mnemonic" => $encryipt_account,
            "wallet_address" => $wallet->address,
            "private_key" => $encryipt_key,
            "wallet_qrimage" => base_url() . '' . $wallet_qrimage,
            "user_id" => $user_id,
        );

        $this->db->insert('user_wallet', $wallet_insert);

        $pass_data = str_replace(" ", ',', $wallet->mnemonic);
        $mailid = '1';

        $mail_subject_data = $this->db->query("SELECT * FROM email_template where id = '" . $mailid . "' ")->row();
        $createddate = date('Y-m-d H:i:s');
        $subject = $mail_subject_data->subject;

        $message = str_replace('[FIRSTNAME]', $referral_id, $mail_subject_data->temp_content);
        $message = str_replace('[username]', $name, $message);
        $message = str_replace('[secret]', $secret, $message);
        $message = str_replace('[PHARSE]', $pass_data, $message);
        $message = str_replace('[email]', $email, $message);
        $message = str_replace('[WalletAddress]', $wallet->address, $message);
        $message = str_replace('[date]', $createddate, $message);

        $this->sendmail($email, $subject, $message);

    }
    /*
    |--------------------------------------------------------------------------
    | ENCRIPTION KEY
    |--------------------------------------------------------------------------
    */
    public function encrypt_account($mnemonic)
    {
        $url = "https://node.adrox.ai/api/user/encrypt-decrypt";

        $postData = array('data' => $mnemonic, 'isEncrypted' => true);

        $jsonData = json_encode($postData);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if (isset($responseData['status']) && $responseData['status'] === true && isset($responseData['result'])) {

            $dataToSave = array(
                'result' => $responseData['result'],
                'type' => $responseData['type'],
                'created_at' => date('Y-m-d H:i:s')
            );

            return $responseData['result'];

        } else {
            return "";
        }
    }
    /*
    |--------------------------------------------------------------------------
    | IMAGE Genarate
    |--------------------------------------------------------------------------
    */
    private function imageGenerate($imageName)
    {
        $this->load->library('gloabals');
        $outputDir = 'assets/images/qr_image/';
        $fileName = $outputDir . '' . $imageName . 'qr_code.png';
        $this->gloabals->generate($imageName, 'png', $fileName);
        return $fileName;
    }
    /*
    |--------------------------------------------------------------------------
    | PHP Email Sender
    |--------------------------------------------------------------------------
    */
    public function sendmail($useremail, $subject, $message)
    {

        $query = $this->db->get_where('email_config', array('id' => 1));
        $config = $query->row_array();

        if ($config) {
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                $mail->SMTPAuth = ($config['smtp_auth'] === 'true');
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = $config['smtpsecure'];
                $mail->Port = $config['port'];
                $mail->setFrom($config['from_mail'], $config['from_name']);
                $mail->addAddress($useremail);
                // Add CC
                $mail->addCC('ashokece68@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = $subject;

                // Only set the body once!
                $mail->Body = trim($message);

                if ($mail->send()) {
                    return true;
                } else {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }


    public function sendpopmail($useremail, $subject, $message)
    {
        $mail = new PHPMailer(true);

        try {
            // Server Settings
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'enquirie@nexmanmlmsoft.com';
            $mail->Password = 'tquRBj4~'; // ⚠ Change immediately
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('enquirie@nexmanmlmsoft.com', 'Nexman MLM Soft');
            $mail->addAddress($useremail);


            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = trim($message);
            $mail->AltBody = strip_tags($message);

            return $mail->send();

        } catch (Exception $e) {
            log_message('error', 'Mail Error: ' . $mail->ErrorInfo);
            return false;
        }
    }


    public function sendpopccmail($useremail = 'ashokece68@gmail.com', $subject, $message)
    {
        $mail = new PHPMailer(true);

        try {
            // Server Settings
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'enquirie@nexmanmlmsoft.com';
            $mail->Password = 'tquRBj4~'; // ⚠ Change immediately
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('enquirie@nexmanmlmsoft.com', 'Nexman MLM Soft');
            $mail->addAddress($useremail);


            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = trim($message);
            $mail->AltBody = strip_tags($message);

            return $mail->send();

        } catch (Exception $e) {
            log_message('error', 'Mail Error: ' . $mail->ErrorInfo);
            return false;
        }
    }




}
