<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Memberbulkupload_model — Admin ▸ Members ▸ Bulk Upload.
 *
 * Turns one Excel/CSV sheet into many members. Deliberately TWO-PHASE:
 *
 *   stage()  parses + validates the whole file and writes only to
 *            member_bulk_upload_batches / _rows. Not one `users` row is
 *            touched, so the admin reviews the exact outcome first.
 *   import() creates the accounts for the rows that came back valid.
 *
 * Each imported row goes through Mlm_model::registerUser() rather than its own
 * INSERT — binary placement (the auto-placement counter, the last-leg walk) is
 * subtle enough that a second copy would drift from the signup path within a
 * release. Nothing in Mlm_model is modified to support this. The bulk-only work
 * is what happens either side of it: resolving the sponsor from the sheet's
 * reference_id, generating the on-chain address, and queueing the BMAN send.
 *
 * The `bman` column is NOT sent here. Import is a synchronous web request;
 * broadcasting N on-chain transfers inside one is how a batch ends up
 * half-finished on a timeout. Rows land with bman_status='pending' and
 * Memberbulkbmancron_model drains that queue from the Treasury wallet.
 *
 * Plaintext passwords never touch disk. The upload is read straight from PHP's
 * temp path (never moved under uploads/), each row's effective password is
 * bcrypt-hashed during stage(), and only that hash is stored. import() then
 * has nothing left to hash — which is also why a staged batch can be imported
 * later, from any browser, with no plaintext still lying around.
 */
class Memberbulkupload_model extends CI_Model
{
    /** Sheet header → canonical field. Matched case/space/punctuation-insensitively. */
    private $headerAliases = [
        'username'     => ['username', 'user name', 'user', 'name', 'membername', 'member', 'login', 'loginid', 'userid'],
        'email'        => ['email', 'emailid', 'emailaddress', 'mail', 'useremail', 'mailid'],
        'password'     => ['password', 'defaultpassword', 'pass', 'pwd', 'loginpassword'],
        'reference_id' => ['referenceid', 'reference', 'referralid', 'referral', 'refid', 'ref', 'sponsor', 'sponsorid', 'sponsorreferral', 'sponsorreferenceid', 'placementid', 'uplineid', 'upline'],
        'leg'          => ['leg', 'position', 'side', 'placement', 'selectleg', 'placementleg'],
        'bman'         => ['bman', 'bmanbalance', 'bmanamount', 'bmantoken', 'balance', 'amount', 'token', 'tokens', 'coin'],
    ];

    /** Column order of the downloadable template, and of the header row we expect. */
    public static $templateColumns = ['username', 'email', 'password', 'reference_id', 'leg', 'bman'];

    const MIN_PASSWORD = 6;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/Mlm_model');
        $this->load->model('Custodialwallet_model', 'custodial');
    }

    /* ============================== settings ============================== */

    public function settings()
    {
        $row = $this->db->get_where('member_bulk_upload_settings', ['id' => 1])->row_array();
        return $row ?: [
            'enabled' => 0, 'dry_run' => 1, 'min_treasury_reserve' => '0',
            'max_batch_size' => 20, 'max_rows_per_file' => 1000,
        ];
    }

    public function updateSettings(array $data, $adminId = null)
    {
        $allowed = array_intersect_key($data, array_flip([
            'enabled', 'dry_run', 'min_treasury_reserve', 'max_batch_size', 'max_rows_per_file',
        ]));
        if (empty($allowed)) return [false, 'No valid fields to update.'];
        $allowed['updated_by'] = $adminId;
        $this->db->where('id', 1)->update('member_bulk_upload_settings', $allowed);
        return [true, 'Settings updated.'];
    }

    /* ================================ read =============================== */

    public function batches($limit = 50, $offset = 0)
    {
        return $this->db->select('b.*, a.admin_name, a.admin_email')
            ->from('member_bulk_upload_batches b')
            ->join('admin_members a', 'a.id = b.admin_id', 'left')
            ->order_by('b.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function batch($batchId)
    {
        return $this->db->get_where('member_bulk_upload_batches', ['id' => (int)$batchId])->row_array() ?: null;
    }

    /**
     * Rows of a batch for display/export.
     *
     * The column list is explicit and deliberately OMITS password_hash: the
     * stage endpoint JSON-encodes this straight back to the browser, and a
     * bcrypt digest handed to the client is an offline-cracking target for
     * nothing in return. import() reads the hash through its own query, which
     * never leaves the server.
     */
    public function rows($batchId, $limit = null, $offset = 0)
    {
        $this->db->select('id, batch_id, row_number, username, email, reference_id, sponsor_id, leg,
                           bman_amount, status, error_message, user_id, referral_id, wallet_address,
                           bman_status, bman_attempts, bman_tx_hash, bman_network, bman_error,
                           bman_sent_at, created_at')
                 ->where('batch_id', (int)$batchId)->order_by('row_number', 'ASC');
        if ($limit !== null) $this->db->limit((int)$limit, (int)$offset);
        return $this->db->get('member_bulk_upload_rows')->result_array();
    }

    /* =============================== stage =============================== */

    /**
     * Parse + validate an uploaded sheet into a staged batch. Writes nothing to
     * `users`.
     *
     * @param  string $path        Path of the upload (PHP's temp path — the file is never moved).
     * @param  array  $opts        original_name, extension, default_password, default_leg, send_bman
     * @param  int    $adminId
     * @return array  ['ok'=>bool, 'message'=>string, 'batch_id'=>int|null, 'summary'=>array]
     */
    public function stage($path, array $opts, $adminId)
    {
        $settings = $this->settings();
        $this->load->library('sheetreader');

        try {
            $raw = $this->sheetreader->read($path, $opts['extension'] ?? null);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $raw = $this->dropTrailingBlankRows($raw);
        if (count($raw) < 2) {
            return ['ok' => false, 'message' => 'The sheet needs a header row plus at least one member row.'];
        }

        $map = $this->mapHeader(array_shift($raw));
        foreach (['username', 'email', 'reference_id'] as $required) {
            if (!isset($map[$required])) {
                return ['ok' => false, 'message' => 'The sheet has no "'.$required.'" column. Download the template to see the expected header row.'];
            }
        }

        $maxRows = (int)$settings['max_rows_per_file'];
        if ($maxRows > 0 && count($raw) > $maxRows) {
            return ['ok' => false, 'message' => 'That file has '.count($raw).' rows — the current limit is '.$maxRows.' per file. Split it, or raise the limit in Settings.'];
        }

        $defaultPassword = (string)($opts['default_password'] ?? '');
        $defaultLeg      = in_array($opts['default_leg'] ?? 'auto', ['left', 'right', 'auto'], true) ? $opts['default_leg'] : 'auto';
        $sendBman        = !empty($opts['send_bman']) ? 1 : 0;

        // Duplicate detection has to cover the file itself, not just the DB —
        // two rows claiming the same email would otherwise both pass validation
        // and the second would blow up mid-import.
        $seenUsernames = [];
        $seenEmails    = [];
        $sponsorCache  = [];

        $parsed = [];
        $valid = 0; $invalid = 0; $bmanQueued = 0; $bmanTotal = '0';

        foreach ($raw as $i => $cells) {
            $rowNo = $i + 1;                                       // header already shifted off
            $get = function ($field) use ($map, $cells) {
                return isset($map[$field]) && isset($cells[$map[$field]]) ? trim((string)$cells[$map[$field]]) : '';
            };

            if (trim(implode('', $cells)) === '') continue;         // fully blank row — silently ignored

            $username = $get('username');
            $email    = $get('email');
            $password = $get('password') !== '' ? $get('password') : $defaultPassword;
            $refRaw   = $get('reference_id');
            $legRaw   = $get('leg');
            $bmanRaw  = $get('bman');

            $errors = [];

            /* ---- username ---- */
            if ($username === '') {
                $errors[] = 'Username is empty';
            } elseif (mb_strlen($username) > 255) {
                $errors[] = 'Username is longer than 255 characters';
            } elseif (isset($seenUsernames[mb_strtolower($username)])) {
                $errors[] = 'Duplicate username — also on row '.$seenUsernames[mb_strtolower($username)];
            } elseif ($this->Mlm_model->usernameExists($username)) {
                $errors[] = 'Username already taken';
            }

            /* ---- email (this is the LOGIN identity, so it must be unique) ---- */
            if ($email === '') {
                $errors[] = 'Email is empty';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email is not a valid address';
            } elseif (isset($seenEmails[mb_strtolower($email)])) {
                $errors[] = 'Duplicate email — also on row '.$seenEmails[mb_strtolower($email)];
            } elseif ($this->db->where('email', $email)->count_all_results('users') > 0) {
                $errors[] = 'Email already registered';
            }

            /* ---- password ---- */
            if ($password === '') {
                $errors[] = 'No password in the sheet and no default password given';
            } elseif (mb_strlen($password) < self::MIN_PASSWORD) {
                $errors[] = 'Password is shorter than '.self::MIN_PASSWORD.' characters';
            }

            /* ---- sponsor + leg from reference_id ---- */
            list($refCode, $legFromPrefix) = $this->parseReferenceLeg($refRaw);
            $leg = $this->normaliseLeg($legRaw !== '' ? $legRaw : ($legFromPrefix ?: $defaultLeg));
            $sponsorId = null;

            if ($refCode === '') {
                $errors[] = 'Reference ID is empty';
            } else {
                $key = mb_strtolower($refCode);
                if (!array_key_exists($key, $sponsorCache)) {
                    $sponsor = $this->db->select('id, status')->where('referral_id', $refCode)
                        ->limit(1)->get('users')->row_array();
                    $sponsorCache[$key] = $sponsor ?: false;
                }
                $sponsor = $sponsorCache[$key];
                if ($sponsor === false) {
                    $errors[] = 'Reference ID "'.$refCode.'" does not match any member';
                } elseif ((string)$sponsor['status'] !== '1') {
                    $errors[] = 'Sponsor "'.$refCode.'" is inactive';
                } else {
                    $sponsorId = (int)$sponsor['id'];
                }
            }

            /* ---- bman ---- */
            $bman = '0';
            if ($sendBman && $bmanRaw !== '') {
                $clean = str_replace([',', ' '], '', $bmanRaw);
                if (!is_numeric($clean)) {
                    $errors[] = 'BMAN "'.$bmanRaw.'" is not a number';
                } elseif (bccomp($clean, '0', 8) < 0) {
                    $errors[] = 'BMAN cannot be negative';
                } else {
                    $bman = bcadd($clean, '0', 8);
                }
            }

            if (!$errors) {
                $seenUsernames[mb_strtolower($username)] = $rowNo;
                $seenEmails[mb_strtolower($email)] = $rowNo;
                $valid++;
                if (bccomp($bman, '0', 8) > 0) { $bmanQueued++; $bmanTotal = bcadd($bmanTotal, $bman, 8); }
            } else {
                $invalid++;
            }

            $parsed[] = [
                'row_number'    => $rowNo,
                'username'      => $username !== '' ? mb_substr($username, 0, 255) : null,
                'email'         => $email !== '' ? mb_substr($email, 0, 255) : null,
                // Hash NOW, while the plaintext is still only in this request's
                // memory. Nothing downstream ever sees the original string.
                'password_hash' => $errors ? null : password_hash($password, PASSWORD_DEFAULT),
                'reference_id'  => $refCode !== '' ? mb_substr($refCode, 0, 250) : null,
                'sponsor_id'    => $sponsorId,
                'leg'           => $leg,
                'bman_amount'   => $bman,
                'status'        => $errors ? 'invalid' : 'valid',
                'error_message' => $errors ? mb_substr(implode('; ', $errors), 0, 255) : null,
            ];
        }

        if (!$parsed) {
            return ['ok' => false, 'message' => 'The sheet has a header row but no data rows.'];
        }

        $batchId = $this->persistBatch($parsed, [
            'admin_id'      => (int)$adminId,
            'original_name' => mb_substr((string)($opts['original_name'] ?? 'upload'), 0, 255),
            'total_rows'    => count($parsed),
            'valid_rows'    => $valid,
            'invalid_rows'  => $invalid,
            'bman_queued'   => $bmanQueued,
            'bman_total'    => $bmanTotal,
            'default_leg'   => $defaultLeg,
            'send_bman'     => $sendBman,
        ]);

        return [
            'ok' => true,
            'batch_id' => $batchId,
            'message' => $valid.' of '.count($parsed).' row(s) are ready to import.',
            'summary' => ['total' => count($parsed), 'valid' => $valid, 'invalid' => $invalid,
                          'bman_queued' => $bmanQueued, 'bman_total' => $bmanTotal],
        ];
    }

    private function persistBatch(array $parsed, array $batch)
    {
        $batch['ref'] = 'MBU-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
        $batch['status'] = 'staged';
        $this->db->insert('member_bulk_upload_batches', $batch);
        $batchId = (int)$this->db->insert_id();

        $insert = [];
        foreach ($parsed as $row) {
            $row['batch_id'] = $batchId;
            $insert[] = $row;
        }
        foreach (array_chunk($insert, 200) as $chunk) {
            $this->db->insert_batch('member_bulk_upload_rows', $chunk);
        }
        return $batchId;
    }

    /* =============================== import ============================== */

    /**
     * Create the accounts for every 'valid' row of a staged batch.
     *
     * Each row is its own transaction: one bad row is recorded as failed and
     * the rest of the batch still lands. An all-or-nothing transaction across
     * hundreds of rows would mean one malformed sponsor throws away a
     * successful import of everything else.
     *
     * @return array ['ok'=>bool,'message'=>string,'imported'=>int,'failed'=>int,'queued'=>int]
     */
    public function import($batchId, $adminId)
    {
        $batch = $this->batch($batchId);
        if (!$batch) return ['ok' => false, 'message' => 'Batch not found.'];
        if ($batch['status'] !== 'staged') {
            return ['ok' => false, 'message' => 'This batch has already been '.$batch['status'].'.'];
        }

        $rows = $this->db->where('batch_id', (int)$batchId)->where('status', 'valid')
            ->order_by('row_number', 'ASC')->get('member_bulk_upload_rows')->result_array();
        if (!$rows) return ['ok' => false, 'message' => 'This batch has no valid rows to import.'];

        $this->db->where('id', (int)$batchId)->update('member_bulk_upload_batches', ['status' => 'importing']);

        $sendBman = (int)$batch['send_bman'] === 1;
        $imported = 0; $failed = 0; $queued = 0; $queuedTotal = '0';

        foreach ($rows as $row) {
            if (empty($row['password_hash'])) {
                $this->failRow($row['id'], 'No password hash was staged for this row.');
                $failed++;
                continue;
            }

            $res = $this->importRow($row, $sendBman);
            if ($res['ok']) {
                $imported++;
                if ($res['queued']) { $queued++; $queuedTotal = bcadd($queuedTotal, (string)$row['bman_amount'], 8); }
            } else {
                $failed++;
            }
        }

        // The hashes now live in `users` where they belong; drop this module's
        // copy so credential material is not duplicated in the audit trail.
        $this->db->where('batch_id', (int)$batchId)->update('member_bulk_upload_rows', ['password_hash' => null]);

        $this->db->where('id', (int)$batchId)->update('member_bulk_upload_batches', [
            'status'        => $imported > 0 ? 'completed' : 'failed',
            'imported_rows' => $imported,
            'failed_rows'   => $failed,
            'bman_queued'   => $queued,
            'bman_total'    => $queuedTotal,
            'imported_at'   => date('Y-m-d H:i:s'),
        ]);

        $msg = $imported.' member(s) created, '.$failed.' failed';
        if ($sendBman && $queued) $msg .= '. '.$queued.' BMAN transfer(s) queued for the cron';
        return ['ok' => $imported > 0, 'message' => $msg.'.',
                'imported' => $imported, 'failed' => $failed, 'queued' => $queued];
    }

    /** One member: account + placement (via Mlm_model) → address → BMAN queue. */
    private function importRow(array $row, $sendBman)
    {
        // Re-check right before writing: a batch can sit staged for hours, and
        // the same username/email may have been registered in the meantime.
        if ($this->Mlm_model->usernameExists($row['username'])) {
            $this->failRow($row['id'], 'Username was taken after this file was staged.');
            return ['ok' => false, 'queued' => false];
        }
        if ($this->db->where('email', $row['email'])->count_all_results('users') > 0) {
            $this->failRow($row['id'], 'Email was registered after this file was staged.');
            return ['ok' => false, 'queued' => false];
        }

        $this->db->trans_begin();
        try {
            // 'auto' means "let the binary engine decide", which registerUser
            // expresses as a null leg.
            $leg = in_array($row['leg'], ['left', 'right'], true) ? $row['leg'] : null;

            // registerUser() hashes whatever it is given, and its signature is
            // deliberately NOT extended to accept a pre-hashed password: an
            // existing caller (admin/withdraw/Withdraw.php) already passes six
            // arguments to this five-parameter method, so a sixth parameter
            // here would silently capture that stray argument and change how
            // that endpoint stores passwords. Instead this passes a throwaway
            // secret and overwrites the hash below, inside the same
            // transaction — the throwaway never reaches a committed row.
            $throwaway = bin2hex(random_bytes(16));

            $userId = $this->Mlm_model->registerUser(
                $row['username'], $row['email'], (int)$row['sponsor_id'], $leg, $throwaway
            );

            if (is_array($userId) || !$userId) {
                throw new RuntimeException(is_array($userId) ? ($userId['error'] ?? 'Registration failed') : 'Registration failed');
            }
            $userId = (int)$userId;

            // Swap in the hash stage() produced from the sheet/default password.
            $this->db->where('id', $userId)->update('users', ['password' => $row['password_hash']]);

            // "Make dynamically wallet address" — a fresh BEP-20 address per
            // member, key AES-encrypted, generated locally by Web3bman.
            $wallet = $this->custodial->ensureAddress($userId);
            if (empty($wallet['wallet_address'])) {
                throw new RuntimeException('Could not generate an on-chain wallet address.');
            }

            $newUser = $this->db->select('referral_id')->where('id', $userId)->get('users')->row_array();

            $queue = $sendBman && bccomp((string)$row['bman_amount'], '0', 8) > 0;
            $this->db->where('id', $row['id'])->update('member_bulk_upload_rows', [
                'status'         => 'imported',
                'user_id'        => $userId,
                'referral_id'    => $newUser['referral_id'] ?? null,
                'wallet_address' => $wallet['wallet_address'],
                'bman_status'    => $queue ? 'pending' : 'none',
                'error_message'  => null,
            ]);

            if ($this->db->trans_status() === false) throw new RuntimeException('Database transaction failed.');
            $this->db->trans_commit();

            return ['ok' => true, 'queued' => $queue];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', '[member_bulk_upload] row '.$row['row_number'].' failed: '.$e->getMessage());
            $this->failRow($row['id'], $e->getMessage());
            return ['ok' => false, 'queued' => false];
        }
    }

    private function failRow($rowId, $reason)
    {
        $this->db->where('id', (int)$rowId)->update('member_bulk_upload_rows', [
            'status' => 'failed', 'error_message' => mb_substr((string)$reason, 0, 255),
        ]);
    }

    /** Discard a staged batch the admin decided not to import. */
    public function cancel($batchId)
    {
        $batch = $this->batch($batchId);
        if (!$batch) return [false, 'Batch not found.'];
        if ($batch['status'] !== 'staged') return [false, 'Only a staged batch can be discarded.'];
        $this->db->where('id', (int)$batchId)->update('member_bulk_upload_batches', ['status' => 'cancelled']);
        // The staged hashes are of no further use — clear them rather than
        // leaving credential material behind for a batch nobody will import.
        $this->db->where('batch_id', (int)$batchId)->update('member_bulk_upload_rows', ['password_hash' => null]);
        return [true, 'Staged batch discarded.'];
    }

    /** Put a failed BMAN row back in the cron's queue (admin escape hatch). */
    public function requeueBman($rowId)
    {
        $row = $this->db->get_where('member_bulk_upload_rows', ['id' => (int)$rowId])->row_array();
        if (!$row) return [false, 'Row not found.'];
        if ($row['bman_status'] !== 'failed') return [false, 'Only a failed transfer can be re-queued.'];
        if (empty($row['wallet_address'])) return [false, 'This row has no wallet address on file.'];
        $this->db->where('id', $row['id'])->update('member_bulk_upload_rows', [
            'bman_status' => 'pending', 'bman_error' => null,
        ]);
        return [true, 'Queued for retry.'];
    }

    /* ============================== helpers ============================== */

    /**
     * Header row → ['field' => column index]. Comparison strips case, spaces
     * and punctuation so "Reference ID", "reference_id" and "REFERENCE-ID" all
     * land on the same field.
     */
    private function mapHeader(array $header)
    {
        $map = [];
        foreach ($header as $index => $label) {
            $key = preg_replace('/[^a-z0-9]/', '', mb_strtolower((string)$label));
            if ($key === '') continue;
            foreach ($this->headerAliases as $field => $aliases) {
                if (isset($map[$field])) continue;                 // first match wins
                if (in_array($key, $aliases, true)) { $map[$field] = $index; break; }
            }
        }
        return $map;
    }

    /**
     * "L-NEXMAN123456" → ['NEXMAN123456', 'left']. Same prefix convention the
     * public signup link uses (see Register::parse_referral_leg), so a sponsor
     * can paste the referral code straight out of their share link.
     */
    private function parseReferenceLeg($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') return ['', null];
        if (preg_match('/^(L|R)\-(.+)$/i', $raw, $m)) {
            return [trim($m[2]), strtolower($m[1]) === 'l' ? 'left' : 'right'];
        }
        return [$raw, null];
    }

    private function normaliseLeg($leg)
    {
        $leg = mb_strtolower(trim((string)$leg));
        if ($leg === 'l' || $leg === 'left')  return 'left';
        if ($leg === 'r' || $leg === 'right') return 'right';
        return 'auto';
    }

    /** Excel loves to append hundreds of empty rows below the real data. */
    private function dropTrailingBlankRows(array $rows)
    {
        while ($rows) {
            $last = end($rows);
            if (is_array($last) && trim(implode('', $last)) === '') array_pop($rows);
            else break;
        }
        return $rows;
    }
}
