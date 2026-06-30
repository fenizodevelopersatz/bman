<?php defined('BASEPATH') OR exit('No direct script access allowed');

// class Users_model extends CI_Model
// {

//     public function __construct()
//     {
//         $this->load->database();
//     }


//     public function get_info($limit, $start, $client_filter, $from_date, $to_date)
//     {

//         if (!empty($from_date) && !empty($to_date)) {
//             $this->db->where('date(register_date) >=', $from_date);
//             $this->db->where('date(register_date) <=', $to_date);
//         }

//         if (!empty($client_filter) && is_array($client_filter)) {
//             $this->db->where_in('id', $client_filter);
//         } elseif (!empty($client_filter)) {
//             $this->db->where('id', $client_filter);
//         }

//         $this->db->limit($limit, $start);
//         $query = $this->db->get('users');
//         return $query->result_array();
//     }

//     public function get_count($client_filter, $from_date, $to_date)
//     {


//         if (!empty($from_date) && !empty($to_date)) {
//             $this->db->where('date(register_date) >=', $from_date);
//             $this->db->where('date(register_date) <=', $to_date);
//         }

//         if (!empty($client_filter) && is_array($client_filter)) {
//             $this->db->where_in('id', $client_filter);
//         } elseif (!empty($client_filter)) {
//             $this->db->where('id', $client_filter);
//         }

//         $query = $this->db->get('users');
//         return $query->num_rows();
//     }


//     public function setKycStatus($user_id, $status)
//     {
//         return $this->db->where('id', (int) $user_id)->update('users', ['kyc_status' => $status]);
//     }

//     // -------------------- PROFILE --------------------
//     public function get_user($userId)
//     {
//         return $this->db->get_where('users', ['id' => (int) $userId])->row_array();
//     }

//     public function update_user($userId, $data)
//     {
//         $this->db->where('id', (int) $userId);
//         return $this->db->update('users', $data);
//     }

//     // -------------------- EMAIL PREFS (already in users table) --------------------
//     public function get_email_prefs($userId)
//     {
//         $u = $this->db->select('success_payments,payouts,product_commission,refund_alerts,invoice_payments')
//             ->get_where('users', ['id' => (int) $userId])->row_array();
//         return $u ?: null;
//     }

//     public function update_email_prefs($userId, $data)
//     {
//         $this->db->where('id', (int) $userId);
//         return $this->db->update('users', $data);
//     }

//     // -------------------- KYC --------------------
//     public function get_kyc($userId)
//     {
//         return $this->db->get_where('user_kyc', ['user_id' => (int) $userId])->row_array();
//     }

//     public function upsert_kyc($userId, $data)
//     {
//         $exists = $this->db->get_where('user_kyc', ['user_id' => (int) $userId])->row_array();
//         if ($exists) {
//             $data['updated_at'] = date('Y-m-d H:i:s');
//             $this->db->where('user_id', (int) $userId);
//             return $this->db->update('user_kyc', $data);
//         } else {
//             $data['user_id'] = (int) $userId;
//             $data['created_at'] = date('Y-m-d H:i:s');
//             return $this->db->insert('user_kyc', $data);
//         }
//     }

//     // -------------------- BANK --------------------
//     public function get_bank($userId)
//     {
//         return $this->db->get_where('user_bank', ['user_id' => (int) $userId])->row_array();
//     }

//     public function upsert_bank($userId, $data)
//     {
//         $exists = $this->db->get_where('user_bank', ['user_id' => (int) $userId])->row_array();
//         if ($exists) {
//             $data['updated_at'] = date('Y-m-d H:i:s');
//             $this->db->where('user_id', (int) $userId);
//             return $this->db->update('user_bank', $data);
//         } else {
//             $data['user_id'] = (int) $userId;
//             $data['created_at'] = date('Y-m-d H:i:s');
//             return $this->db->insert('user_bank', $data);
//         }
//     }

//     // -------------------- DANGER ACTIONS --------------------
//     public function create_action($userId, $action, $reason = null)
//     {
//         return $this->db->insert('user_account_actions', [
//             'user_id' => (int) $userId,
//             'action' => $action,
//             'reason' => $reason,
//             'status' => 'pending',
//             'created_at' => date('Y-m-d H:i:s')
//         ]);
//     }

//     public function has_pending_action($userId, $action)
//     {
//         return $this->db->where('user_id', (int) $userId)
//             ->where('action', $action)
//             ->where('status', 'pending')
//             ->count_all_results('user_account_actions') > 0;
//     }
// }




class Users_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    // -------------------- USERS LIST --------------------
    public function get_info($limit, $start, $client_filter, $from_date, $to_date)
    {
        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('DATE(register_date) >=', $from_date);
            $this->db->where('DATE(register_date) <=', $to_date);
        }

        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('id', $client_filter);
        } elseif (!empty($client_filter)) {
            $this->db->where('id', $client_filter);
        }

        $this->db->limit($limit, $start);
        return $this->db->get('users')->result_array();
    }

    public function get_count($client_filter, $from_date, $to_date)
    {
        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('DATE(register_date) >=', $from_date);
            $this->db->where('DATE(register_date) <=', $to_date);
        }

        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('id', $client_filter);
        } elseif (!empty($client_filter)) {
            $this->db->where('id', $client_filter);
        }

        return $this->db->count_all_results('users');
    }

    // -------------------- USER PROFILE --------------------
    public function get_user($userId)
    {
        return $this->db->get_where('users', ['id' => (int) $userId])->row_array();
    }

    public function update_user($userId, $data)
    {
        return $this->db->where('id', (int) $userId)->update('users', $data);
    }

    public function setKycStatus($userId, $status)
    {
        return $this->db->where('id', (int) $userId)
            ->update('users', ['kyc_status' => $status]);
    }

    // -------------------- EMAIL PREFS --------------------
    public function get_email_prefs($userId)
    {
        return $this->db->select('success_payments,payouts,product_commission,refund_alerts,invoice_payments')
            ->get_where('users', ['id' => (int) $userId])
            ->row_array();
    }

    public function update_email_prefs($userId, $data)
    {
        return $this->db->where('id', (int) $userId)->update('users', $data);
    }

    // -------------------- BASIC KYC (LEGACY) --------------------
    public function get_kyc($userId)
    {
        return $this->db->get_where('user_kyc', ['user_id' => (int) $userId])->row_array();
    }

    public function upsert_kyc($userId, $data)
    {
        $exists = $this->get_kyc($userId);

        if ($exists) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->where('user_id', (int) $userId)
                ->update('user_kyc', $data);
        }

        $data['user_id'] = (int) $userId;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('user_kyc', $data);
    }

    // -------------------- KYC APPLICATION (FULL FLOW) --------------------
    public function get_kyc_application($userId)
    {
        return $this->db
            ->get_where('kyc_applications', ['user_id' => (int) $userId])
            ->row_array();
    }

    public function upsert_kyc_application($userId, $data)
    {
        $exists = $this->get_kyc_application($userId);

        if ($exists) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->where('user_id', (int) $userId)
                ->update('kyc_applications', $data);
        }

        $data['user_id'] = (int) $userId;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert('kyc_applications', $data);
    }

    public function update_kyc_application_status(
        $userId,
        $status,
        $reviewNotes = null,
        $rejectionCode = null,
        $reviewedBy = null
    ) {
        $data = [
            'status' => $status,
            'review_notes' => $reviewNotes,
            'rejection_code' => $rejectionCode,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('user_id', (int) $userId)
            ->update('kyc_applications', $data);

        // sync users table
        return $this->setKycStatus($userId, $status);
    }

    // -------------------- ADMIN KYC LIST --------------------
    public function get_kyc_applications($limit, $start, $status = null)
    {
        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $start);

        return $this->db->get('kyc_applications')->result_array();
    }

    public function get_kyc_applications_count($status = null)
    {
        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        return $this->db->count_all_results('kyc_applications');
    }

    // -------------------- BANK --------------------
    public function get_bank($userId)
    {
        return $this->db->get_where('user_bank', ['user_id' => (int) $userId])->row_array();
    }

    public function upsert_bank($userId, $data)
    {
        $exists = $this->get_bank($userId);

        if ($exists) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->where('user_id', (int) $userId)
                ->update('user_bank', $data);
        }

        $data['user_id'] = (int) $userId;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('user_bank', $data);
    }

    // -------------------- ACCOUNT ACTIONS --------------------
    public function create_action($userId, $action, $reason = null)
    {
        return $this->db->insert('user_account_actions', [
            'user_id' => (int) $userId,
            'action' => $action,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function has_pending_action($userId, $action)
    {
        return $this->db->where([
            'user_id' => (int) $userId,
            'action' => $action,
            'status' => 'pending'
        ])->count_all_results('user_account_actions') > 0;
    }
}