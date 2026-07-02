<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Elliptic\EC;
use kornrunner\Keccak;
use Web3p\EthereumTx\Transaction;

/**
 * Web3bman — BEP-20 / BNB wallet + transfer helper for the BMAN platform.
 * -------------------------------------------------------------------
 * Reads ALL blockchain values (RPC URL, chain id, token contract, decimals,
 * gas limit / price) from the ACTIVE row of `token_settings` — nothing is
 * hardcoded. Uses the isolated web3 stack in
 * application/third_party/web3bman/vendor (web3p/ethereum-tx for offline
 * secp256k1 + EIP-155 signing, kornrunner/keccak, simplito/elliptic-php);
 * JSON-RPC is plain curl so we add no runtime coupling to the RPC client.
 *
 * SECURITY
 *  - Private keys are NEVER stored by this library. A sending key (gas /
 *    treasury wallet) is passed in per call; store it encrypted and decrypt
 *    only at the moment of sending. encryptKey()/decryptKey() are provided
 *    (AES-256 via CI's ENCRYPTION_KEY) for that purpose.
 *  - Wallet addresses are public and live in `token_settings`; private keys
 *    do not belong in that table.
 *
 * Typical use (future payout / withdrawal engine):
 *   $this->load->library('web3bman');
 *   $bal = $this->web3bman->getTokenBalance($userAddress);      // read
 *   $tx  = $this->web3bman->sendToken($treasuryPk, $to, '25');  // signed send
 */
class Web3bman
{
    /** @var CI_Controller */
    private $CI;
    /** @var array|null active token_settings row */
    private $cfg;
    /** transfer(address,uint256) selector */
    const SELECTOR_TRANSFER = '0xa9059cbb';
    /** balanceOf(address) selector */
    const SELECTOR_BALANCEOF = '0x70a08231';

    public function __construct()
    {
        $this->CI =& get_instance();
        $auto = APPPATH.'third_party/web3bman/vendor/autoload.php';
        if (!is_file($auto)) {
            throw new RuntimeException('web3bman vendor missing — run composer install in application/third_party/web3bman.');
        }
        require_once $auto;
        $this->CI->load->model('Tokenmaster_model', 'tokens');
        $this->cfg = $this->CI->tokens->activeSettings();
    }

    /* ============================ config ============================ */

    /** The active token settings row (throws if none is active). */
    private function cfg()
    {
        if (!$this->cfg) {
            throw new RuntimeException('No active Token Settings configuration — set one in Master → Token Settings.');
        }
        return $this->cfg;
    }

    public function chainId()  { return (int)$this->cfg()['chain_id']; }
    public function rpcUrl()   { return $this->cfg()['rpc_url']; }
    public function contract() { return $this->cfg()['bman_contract']; }
    public function decimals() { return (int)$this->cfg()['bman_decimals']; }

    /* ============================ JSON-RPC ============================ */

    /** One JSON-RPC call over curl. Returns the decoded `result` or throws. */
    private function rpc($method, array $params = [])
    {
        $ch = curl_init($this->rpcUrl());
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('RPC transport error: '.$err);
        $j = json_decode($raw, true);
        if (isset($j['error'])) {
            throw new RuntimeException('RPC error: '.($j['error']['message'] ?? json_encode($j['error'])));
        }
        if (!array_key_exists('result', $j)) throw new RuntimeException('RPC gave no result.');
        return $j['result'];
    }

    /* ======================= amount conversions ======================= */

    /** Human amount ("25.5") → smallest-unit integer string, per decimals. */
    public function toUnits($amount, $decimals = null)
    {
        $decimals = $decimals === null ? $this->decimals() : (int)$decimals;
        $factor = bcpow('10', (string)$decimals, 0);
        return bcmul((string)$amount, $factor, 0); // truncates sub-unit dust
    }

    /** Smallest-unit integer string → human decimal string. */
    public function fromUnits($units, $decimals = null)
    {
        $decimals = $decimals === null ? $this->decimals() : (int)$decimals;
        $factor = bcpow('10', (string)$decimals, 0);
        $val = bcdiv((string)$units, $factor, $decimals);
        // trim trailing zeros / dot
        return $decimals > 0 ? rtrim(rtrim($val, '0'), '.') : $val;
    }

    private function decToHex($dec)   { return '0x'.gmp_strval(gmp_init((string)$dec, 10), 16); }
    private function hexToDec($hex)   { return gmp_strval(gmp_init(ltrim((string)$hex, '0x') ?: '0', 16), 10); }
    private function pad32($hexNoPrefix) { return str_pad(ltrim($hexNoPrefix, '0x'), 64, '0', STR_PAD_LEFT); }

    /* ========================= wallet generation ========================= */

    /**
     * Generate a fresh EVM wallet. Returns ['address'=>0x…, 'private_key'=>0x…].
     * The caller is responsible for storing the private key securely
     * (see encryptKey()). Nothing is persisted here.
     */
    public function generateWallet()
    {
        $ec = new EC('secp256k1');
        $kp = $ec->genKeyPair();
        $priv = str_pad($kp->getPrivate('hex'), 64, '0', STR_PAD_LEFT);
        return array_merge(['private_key' => '0x'.$priv], $this->addressFromPrivate($priv));
    }

    /** Derive the checksummed address from a private key (hex, with/without 0x). */
    public function addressFromPrivate($privateKey)
    {
        $priv = strtolower(preg_replace('/^0x/', '', $privateKey));
        if (!preg_match('/^[a-f0-9]{64}$/', $priv)) throw new InvalidArgumentException('Invalid private key.');
        $ec = new EC('secp256k1');
        $kp = $ec->keyFromPrivate($priv, 'hex');
        // uncompressed public key without the 0x04 prefix
        $pub = $kp->getPublic(false, 'hex');
        $pub = substr($pub, 2);
        $hash = Keccak::hash(hex2bin($pub), 256);
        $addr = substr($hash, -40);
        return ['address' => $this->toChecksum($addr)];
    }

    /** EIP-55 checksum address. */
    public function toChecksum($address)
    {
        $addr = strtolower(preg_replace('/^0x/', '', $address));
        $hash = Keccak::hash($addr, 256);
        $out = '0x';
        for ($i = 0; $i < 40; $i++) {
            $out .= (intval($hash[$i], 16) >= 8) ? strtoupper($addr[$i]) : $addr[$i];
        }
        return $out;
    }

    /* ============================ balances ============================ */

    /** Native BNB balance (human string). */
    public function getBnbBalance($address)
    {
        $wei = $this->hexToDec($this->rpc('eth_getBalance', [$address, 'latest']));
        return $this->fromUnits($wei, 18);
    }

    /** BEP-20 token balance (defaults to the BMAN contract). Human string. */
    public function getTokenBalance($address, $contract = null)
    {
        $contract = $contract ?: $this->contract();
        if (!$contract) throw new RuntimeException('No token contract configured.');
        $data = self::SELECTOR_BALANCEOF.$this->pad32(strtolower(preg_replace('/^0x/', '', $address)));
        $hex  = $this->rpc('eth_call', [['to' => $contract, 'data' => $data], 'latest']);
        return $this->fromUnits($this->hexToDec($hex), $this->decimals());
    }

    /* ======================= signed transactions ======================= */

    /**
     * Send BEP-20 tokens (default BMAN). Builds, signs offline with the given
     * private key (EIP-155 with the configured chain id) and broadcasts.
     * Returns ['tx_hash'=>0x…, 'from'=>…, 'to'=>…, 'amount'=>…].
     *
     * @param string $fromPrivateKey sender key (decrypt just-in-time)
     * @param string $to             recipient 0x address
     * @param string $amount         human amount, e.g. "25" or "25.5"
     * @param string|null $contract  token contract (defaults to BMAN)
     */
    public function sendToken($fromPrivateKey, $to, $amount, $contract = null)
    {
        $cfg = $this->cfg();
        $contract = $contract ?: $this->contract();
        if (!$contract) throw new RuntimeException('No token contract configured.');
        $from = $this->addressFromPrivate($fromPrivateKey)['address'];

        $units = $this->toUnits($amount, $this->decimals());
        if (bccomp($units, '0', 0) <= 0) throw new InvalidArgumentException('Amount must be greater than 0.');

        $data = self::SELECTOR_TRANSFER
              . $this->pad32(strtolower(preg_replace('/^0x/', '', $to)))
              . $this->pad32(gmp_strval(gmp_init($units, 10), 16));

        $txHash = $this->buildSignSend($fromPrivateKey, [
            'to'       => $contract,          // token contract
            'value'    => '0x0',              // no native value on a token transfer
            'data'     => $data,
            'gasLimit' => $this->decToHex($cfg['gas_limit'] ?: 210000),
        ], $from);

        return ['tx_hash' => $txHash, 'from' => $from, 'to' => $this->toChecksum($to), 'amount' => (string)$amount];
    }

    /** Send native BNB (e.g. to fund gas). $amount is human BNB. */
    public function sendBnb($fromPrivateKey, $to, $amount)
    {
        $cfg = $this->cfg();
        $from = $this->addressFromPrivate($fromPrivateKey)['address'];
        $wei = $this->toUnits($amount, 18);
        if (bccomp($wei, '0', 0) <= 0) throw new InvalidArgumentException('Amount must be greater than 0.');

        $txHash = $this->buildSignSend($fromPrivateKey, [
            'to'       => $this->toChecksum($to),
            'value'    => $this->decToHex($wei),
            'data'     => '0x',
            'gasLimit' => $this->decToHex(21000),
        ], $from);

        return ['tx_hash' => $txHash, 'from' => $from, 'to' => $this->toChecksum($to), 'amount' => (string)$amount];
    }

    /**
     * Common path: fetch nonce + gas price, sign (EIP-155) and broadcast.
     * Returns the transaction hash. Signing is fully offline; only the final
     * eth_sendRawTransaction touches the network.
     */
    private function buildSignSend($privateKey, array $tx, $from)
    {
        $cfg = $this->cfg();
        $nonce = $this->rpc('eth_getTransactionCount', [$from, 'pending']); // hex
        // gas price: configured gwei → wei, else network suggestion
        if (!empty($cfg['gas_price']) && (float)$cfg['gas_price'] > 0) {
            $gasWei = bcmul((string)$cfg['gas_price'], bcpow('10', '9', 0), 0);
            $gasPrice = $this->decToHex($gasWei);
        } else {
            $gasPrice = $this->rpc('eth_gasPrice', []);
        }

        $transaction = new Transaction([
            'nonce'    => $nonce,
            'gasPrice' => $gasPrice,
            'gas'      => $tx['gasLimit'],
            'to'       => $tx['to'],
            'value'    => $tx['value'],
            'data'     => $tx['data'],
            'chainId'  => $this->chainId(),
        ]);
        $signed = '0x'.$transaction->sign(preg_replace('/^0x/', '', $privateKey));
        return $this->rpc('eth_sendRawTransaction', [$signed]);
    }

    /* ==================== secure key storage helpers ==================== */

    /** AES-256-CBC encrypt a private key with CI's ENCRYPTION_KEY. */
    public function encryptKey($plainKey)
    {
        $secret = $this->encSecret();
        $iv = openssl_random_pseudo_bytes(16);
        $ct = openssl_encrypt($plainKey, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv.$ct);
    }

    /** Decrypt a key produced by encryptKey(). */
    public function decryptKey($stored)
    {
        $secret = $this->encSecret();
        $raw = base64_decode($stored);
        $iv = substr($raw, 0, 16);
        $ct = substr($raw, 16);
        $out = openssl_decrypt($ct, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv);
        if ($out === false) throw new RuntimeException('Unable to decrypt key.');
        return $out;
    }

    private function encSecret()
    {
        $this->CI->config->load('config', true);
        $key = config_item('encryption_key');
        if (!$key) throw new RuntimeException('Set $config["encryption_key"] before storing wallet keys.');
        return hash('sha256', $key, true);
    }
}
