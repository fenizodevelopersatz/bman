<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-time correction — reverses the phantom Exchange-wallet balance caused
 * by Depositlistener_model::scanBman() crediting Exchange a second time for
 * three transactions that were ALREADY credited to Earning/Staking/Bonus via
 * Wallettransferservice_model::execute() (an internal wallet transfer that
 * settles on-chain from the treasury wallet). Root cause is fixed going
 * forward by the treasury-sender guard added in
 * Depositlistener_model::creditConfirmedBman() (2026-09-01) — this script
 * only corrects the balance that already existed before that fix landed.
 *
 * Hash-matched, not id-matched, so the identical script is safe to run
 * against any environment (local or live) where these exact three
 * transactions exist — row ids will differ between databases, tx_hash never
 * does.
 *
 * Idempotent: checks for its own correction reference first, so running it
 * twice (by accident) never double-debits.
 *
 * Adds a new reversing ledger entry only — never edits or deletes the
 * original (mistaken) credit rows, so the audit trail stays intact and
 * shows exactly what happened and why it was corrected.
 *
 * CLI only:  php index.php fixphantomexchangecredit run
 * Delete this controller after it has been run once on every environment
 * that needs it — it is a one-off, not a permanent feature.
 */
class FixPhantomExchangeCredit extends CI_Controller
{
    private $userId = 13;
    private $hashes = [
        '0x600a05e1bc0004959508f98772649bee65c2b30bb332c972ca7f41b1dbd59dee', // 500 BMAN — bonus wallet-transfer settlement, also mis-credited to Exchange
        '0xaddb6fd4d19e6f25693e23e48f0563f6e0ee91058b70d63838b9621b9bcf324f', // 500 BMAN — staking wallet-transfer settlement, also mis-credited to Exchange
        '0xd5e32f4e35afe95a3e91b1ff610503a90d207f733f0c944f7d4ccc445c0fd08d', // 500 BMAN — earning wallet-transfer settlement, also mis-credited to Exchange
    ];
    private $correctionRef = 'CORR-PHANTOM-EXCHANGE-2026-09-01-U13';

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) show_404();
        $this->load->database();
        $this->load->model('Walletledger_model', 'ledger');
    }

    public function run()
    {
        $already = $this->db->get_where('wallet_ledger', [
            'user_id' => $this->userId, 'reference_type' => 'correction', 'reference_id' => $this->correctionRef,
        ])->row_array();
        if ($already) {
            echo "Already applied (wallet_ledger id {$already['id']}, {$already['created_at']}). Nothing to do.\n";
            return;
        }

        // Verify every hash is exactly the deposit-credit we expect before
        // touching anything — refuse to run blind against data that doesn't
        // match what this script assumes.
        $total = '0';
        foreach ($this->hashes as $hash) {
            $row = $this->db->select('id, credit')
                ->where(['user_id' => $this->userId, 'wallet_type' => 'exchange', 'reference_type' => 'deposit', 'tx_hash' => $hash])
                ->get('wallet_ledger')->row_array();
            if (!$row) {
                echo "ABORT: expected deposit-credit ledger row for tx_hash {$hash} not found for user {$this->userId}. No changes made.\n";
                return;
            }
            $total = bcadd($total, (string) $row['credit'], 8);
        }

        echo "Found all 3 phantom credit rows. Total to reverse: {$total} BMAN.\n";

        list($ok, $result) = $this->ledger->debit($this->userId, 'exchange', $total, 'correction', [
            'reference_id' => $this->correctionRef,
            'description'  => 'Correction: reversing duplicate Exchange credit for 3 wallet-transfer settlements '
                . 'already credited to Earning/Staking/Bonus (tx: '
                . implode(', ', array_map(function ($h) { return substr($h, 0, 10) . '...'; }, $this->hashes))
                . ')',
        ]);

        if (!$ok) {
            echo "FAILED: {$result}\n";
            return;
        }

        echo "Done. Debited {$total} BMAN from user {$this->userId}'s Exchange wallet. Ledger id: {$result}\n";
    }
}
