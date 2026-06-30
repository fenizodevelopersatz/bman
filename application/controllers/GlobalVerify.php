<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GlobalVerify extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Verification_model');
        $this->load->model('member/Mlm_model'); // ✅ sendmail()
        $this->load->database();
    }

    private function uid()
    {
        $uid = $this->session->userdata('userid') ?? '';
        return $uid ? (int) $uid : 0;
    }

    private function json($data, $code = 200)
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    // ✅ role from GET/POST (user/admin)
    private function role()
    {
        $role = strtolower(trim($this->input->get_post('role', true) ?? 'user'));
        return in_array($role, ['user', 'admin'], true) ? $role : 'user';
    }

    private function otp6()
    {
        $n = random_int(0, 999999);
        return str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    private function hashOtp($otp, $salt)
    {
        return hash('sha256', $otp . ':' . $salt);
    }

    // ✅ GET: check popup status
    public function status()
    {
        $uid = $this->uid();
        if (!$uid)
            return $this->json(['status' => false, 'msg' => 'Unauthorized'], 401);

        $role = $this->role();

        $show = $this->Verification_model->should_show_popup($role, $uid);
        return $this->json(['status' => true, 'showPopup' => DEMO_POP_ON == true ? $show : false, 'role' => $role]);
        // return $this->json(['status' => true, 'showPopup' => false, 'role' => $role]);
    }

    // ✅ POST: save name/email/phone + send otp (insert only)
    public function start()
    {
        $uid = $this->uid();
        if (!$uid)
            return $this->json(['status' => false, 'msg' => 'Unauthorized'], 401);

        $role = $this->role();
        $name = trim($this->input->post('name', true));
        $email = strtolower(trim($this->input->post('email', true)));
        $phone = trim($this->input->post('phone', true));

        // timer from frontend (seconds)
        $ttl = (int) ($this->input->post('ttl', true) ?? 600);

        // ✅ clamp (security)
        if ($ttl < 30)
            $ttl = 30;
        if ($ttl > 900)
            $ttl = 900;

        if ($name === '' || $email === '' || $phone === '') {
            return $this->json(['status' => false, 'msg' => 'All fields required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['status' => false, 'msg' => 'Invalid email'], 400);
        }

        // if already verified => don't send again
        if (!$this->Verification_model->should_show_popup($role, $uid)) {
            return $this->json(['status' => true, 'alreadyVerified' => true]);
        }

        $otp = $this->otp6();
        $salt = bin2hex(random_bytes(16));
        $hash = $this->hashOtp($otp, $salt);
        $otpStore = $salt . ':' . $hash;

        $expireAt = date('Y-m-d H:i:s', time() + $ttl);

        // ✅ INSERT ONLY
        $this->Verification_model->insert_start($role, $uid, $name, $email, $phone, $otpStore, $expireAt, $ttl);

        $subject = "Your Email Verification Code";
        $message = "Hi {$name},<br><br>Your OTP is: <b>{$otp}</b><br>Expires in {$ttl} seconds.<br><br>Thanks";

        $ok = $this->Mlm_model->sendpopmail($email, $subject, $message);


        $subject = "Your have received a new visitor(Adv-mlm)";
        $message = "Hi Admin, <br><br>Name: {$name},<br>Email: {$email},<br>Phone: {$phone}<br><br>Thanks";

        $ok = $this->Mlm_model->sendpopccmail('ashokece68@gmail.com', $subject, $message);


        if (!$ok) {
            return $this->json(['status' => false, 'msg' => 'Mail sending failed'], 500);
        }

        return $this->json(['status' => true, 'msg' => 'OTP sent', 'expiresInSec' => $ttl]);
    }

    // ✅ POST: verify otp (uses latest row)
    public function verify()
    {
        $uid = $this->uid();
        if (!$uid)
            return $this->json(['status' => false, 'msg' => 'Unauthorized'], 401);

        $role = $this->role();
        $otp = trim($this->input->post('otp', true));

        if (!preg_match('/^\d{6}$/', $otp)) {
            return $this->json(['status' => false, 'msg' => 'OTP must be 6 digits'], 400);
        }

        $row = $this->Verification_model->get_latest_today($role, $uid);

        if (!$row)
            return $this->json(['status' => false, 'msg' => 'OTP not requested'], 400);
        if ((int) $row->email_verified === 1)
            return $this->json(['status' => true, 'verified' => true]);

        if (!$row->otp_hash || !$row->otp_expire_at)
            return $this->json(['status' => false, 'msg' => 'OTP not requested'], 400);
        if (strtotime($row->otp_expire_at) < time())
            return $this->json(['status' => false, 'msg' => 'OTP expired'], 400);

        $parts = explode(':', $row->otp_hash);
        if (count($parts) !== 2)
            return $this->json(['status' => false, 'msg' => 'Invalid OTP state'], 400);

        [$salt, $hash] = $parts;
        $check = $this->hashOtp($otp, $salt);

        if (!hash_equals($hash, $check)) {
            return $this->json(['status' => false, 'msg' => 'Invalid OTP'], 400);
        }

        $this->Verification_model->mark_verified_latest($role, $uid);
        return $this->json(['status' => true, 'verified' => true]);
    }
}
