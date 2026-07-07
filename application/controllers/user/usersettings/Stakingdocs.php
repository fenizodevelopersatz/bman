<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stakingdocs — investment document generation (Phase 5, docs/15).
 * Renders branded, printable HTML documents from LIVE data (no mock, no stored
 * files): Purchase Receipt, Investment Agreement, ROI Schedule, Summary Report.
 * Access: the investment owner OR an authenticated admin only. Every generation
 * is recorded in staking_documents (metadata, deduped per invest+type) and
 * staking_document_log (audit). Print / save-as-PDF via the browser; QR codes
 * link to the blockchain explorer / receipt verification.
 */
class Stakingdocs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper(['url']);
    }

    /* ------------------------------ helpers ------------------------------ */

    /** Resolve the investment + enforce owner-or-admin access. */
    private function _ctx($investId)
    {
        $inv = $this->db->select('ui.*, pc.package_name, pc.roi AS pkg_roi, pc.days_duration, pc.period, pc.retrun_principle')
            ->from('user_investment ui')->join('package_config pc', 'pc.id = ui.package_id', 'left')
            ->where('ui.id', (int)$investId)->get()->row_array();
        if (!$inv) show_404();
        $uid     = (int)$this->session->userdata('user_userid');
        $isAdmin = (bool)$this->session->userdata('admin_logged_in');
        if (!$isAdmin && (!$uid || (int)$inv['user_id'] !== $uid)) show_404();  // owner or admin only
        return $inv;
    }

    private function _actor()
    {
        if ($this->session->userdata('admin_logged_in')) return ['admin', (int)$this->session->userdata('admin_userid')];
        return ['user', (int)$this->session->userdata('user_userid')];
    }

    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    private function _brand()
    {
        $name = 'BMAN Staking'; $logo = '';
        if (function_exists('site_settings')) {
            foreach (['site_name','site_title','company_name'] as $k) { $v = @site_settings('general', $k); if ($v) { $name = $v; break; } }
            $l = @site_settings('general', 'logo'); if ($l) $logo = base_url('assets/images/'.$l);
        }
        return [$name, $logo];
    }

    /** Upsert document metadata (deduped per invest+type) + write an audit row. */
    private function _record($type, $inv, $tx)
    {
        $prefix = ['receipt'=>'RCP','agreement'=>'AGR','roi_schedule'=>'ROI','summary'=>'SUM'][$type];
        $row = $this->db->get_where('staking_documents', ['invest_id'=>(int)$inv['id'],'doc_type'=>$type])->row_array();
        if ($row) {
            $docNo = $row['doc_no']; $docId = (int)$row['id'];
            $this->db->where('id', $docId)->set('download_count', 'download_count+1', false)
                     ->set('last_access_at', date('Y-m-d H:i:s'))->update('staking_documents');
        } else {
            $docNo = $prefix.'-'.date('Y').'-'.str_pad((int)$inv['id'], 6, '0', STR_PAD_LEFT);
            $this->db->insert('staking_documents', ['doc_no'=>$docNo,'doc_type'=>$type,'invest_id'=>(int)$inv['id'],
                'user_id'=>(int)$inv['user_id'],'tx_hash'=>$tx,'last_access_at'=>date('Y-m-d H:i:s')]);
            $docId = (int)$this->db->insert_id();
        }
        list($atype,$aid) = $this->_actor();
        $this->db->insert('staking_document_log', ['document_id'=>$docId,'invest_id'=>(int)$inv['id'],
            'user_id'=>(int)$inv['user_id'],'doc_type'=>$type,'action'=>'generated','actor_type'=>$atype,
            'actor_id'=>$aid,'ip_address'=>$this->input->ip_address(),
            'user_agent'=>substr((string)$this->input->user_agent(),0,250)]);
        return $docNo;
    }

    private function _row($k, $v) { return '<tr><td class="k">'.html_escape($k).'</td><td class="v">'.($v===''||$v===null?'—':$v).'</td></tr>'; }

    /** Branded printable HTML shell. $qrText → QR (explorer/verify). */
    private function _shell($title, $docNo, $bodyHtml, $qrText, $qrLabel = 'Scan to verify')
    {
        list($brand, $logo) = $this->_brand();
        $logoHtml = $logo ? '<img src="'.html_escape($logo).'" style="height:44px;">' : '<span class="brand">'.html_escape($brand).'</span>';
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>'.html_escape($title).' — '.html_escape($docNo).'</title>';
        echo '<script src="'.base_url('assets/js/vendor/qrcode.min.js').'"></script>';
        echo '<style>
          *{box-sizing:border-box;} body{font-family:Segoe UI,Arial,sans-serif;color:#0b1220;margin:0;background:#eef2f7;}
          .doc{max-width:820px;margin:24px auto;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(15,23,42,.1);padding:34px 40px;}
          .top{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #4338ca;padding-bottom:16px;margin-bottom:8px;}
          .brand{font-size:22px;font-weight:900;color:#4338ca;}
          .docno{font-size:12px;color:#64748b;font-weight:800;text-align:right;}
          h1{font-size:20px;margin:18px 0 4px;} .sub{color:#64748b;font-size:13px;margin-bottom:18px;font-weight:700;}
          table.kv{width:100%;border-collapse:collapse;margin:6px 0 16px;} table.kv td{padding:8px 4px;border-bottom:1px solid #eef;font-size:13.5px;vertical-align:top;}
          table.kv td.k{color:#64748b;font-weight:800;width:40%;} table.kv td.v{font-weight:700;word-break:break-all;}
          table.grid{width:100%;border-collapse:collapse;font-size:12.5px;margin:6px 0 16px;} table.grid th,table.grid td{padding:7px 9px;border:1px solid #e5e7eb;text-align:left;}
          table.grid th{background:#f8fafc;font-weight:900;color:#475569;} h2{font-size:15px;margin:20px 0 6px;color:#334155;border-left:4px solid #4338ca;padding-left:10px;}
          .terms{font-size:13px;line-height:1.7;color:#334155;} .terms li{margin-bottom:7px;}
          .foot{display:flex;justify-content:space-between;align-items:flex-end;margin-top:26px;border-top:1px solid #eef;padding-top:16px;}
          .sign{font-size:12px;color:#64748b;} .sign .line{border-top:1.5px solid #94a3b8;width:200px;margin-top:34px;padding-top:4px;}
          #qr{padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;} .qrwrap{text-align:center;font-size:10.5px;color:#64748b;font-weight:800;}
          .toolbar{max-width:820px;margin:14px auto -6px;text-align:right;} .btn{background:#4338ca;color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;font-size:13px;cursor:pointer;text-decoration:none;}
          .btn.light{background:#e2e8f0;color:#0b1220;margin-right:8px;}
          @media print{body{background:#fff;} .toolbar{display:none;} .doc{box-shadow:none;margin:0;border-radius:0;max-width:100%;}}
        </style></head><body>';
        echo '<div class="toolbar"><a href="#" class="btn light" onclick="history.back();return false;">Back</a>'
            .'<button class="btn" onclick="window.print()">Download / Print</button></div>';
        echo '<div class="doc"><div class="top"><div>'.$logoHtml.'</div><div class="docno">Document No.<br><b style="font-size:14px;color:#0b1220;">'.html_escape($docNo).'</b><br>'.date('Y-m-d H:i').'</div></div>';
        echo $bodyHtml;
        echo '<div class="foot"><div class="sign"><div class="line">Authorized Signature</div></div>'
            .'<div class="qrwrap"><div id="qr"></div>'.html_escape($qrLabel).'</div></div>';
        echo '<p style="font-size:10.5px;color:#94a3b8;margin-top:18px;text-align:center;">This document was generated from live records of '.html_escape($brand).'. ROI, ledger and blockchain data are immutable and independently verifiable on-chain.</p>';
        echo '</div>';
        echo '<script>try{new QRCode(document.getElementById("qr"),'.json_encode($qrText).');}catch(e){document.getElementById("qr").textContent="QR";}</script>';
        echo '</body></html>';
    }

    private function _user($uid)
    {
        return $this->db->select('username, email, name, mobile')->get_where('users', ['id' => (int)$uid])->row_array() ?: [];
    }

    private function _roiRows($inv)
    {
        return $this->db->select('amount, history_date')->where('user_id',(int)$inv['user_id'])
            ->where('invest_id',(int)$inv['id'])->where('hash_id','roi-made')->order_by('history_date','ASC')
            ->get('history')->result_array();
    }

    /* ------------------------------ documents ---------------------------- */

    public function receipt($id)
    {
        $inv = $this->_ctx($id); $u = $this->_user($inv['user_id']); $ex = $this->_explorer();
        $tx = (!empty($inv['hash_id']) && strlen($inv['hash_id']) > 40) ? $inv['hash_id'] : null;
        $docNo = $this->_record('receipt', $inv, $tx);
        $roiPct = (float)$inv['pkg_roi']; $dur = (int)$inv['days_duration'];

        $b  = '<h1>Investment Purchase Receipt</h1><div class="sub">Official proof of staking purchase</div>';
        $b .= '<table class="kv">';
        $b .= $this->_row('Receipt Number', '<b>'.html_escape($docNo).'</b>');
        $b .= $this->_row('Package ID', (int)$inv['id']);
        $b .= $this->_row('Package Name', html_escape($inv['package_name'] ?: ('PKG-'.$inv['package_id'])));
        $b .= $this->_row('Stake Amount', number_format((float)$inv['invest_amount'], 2));
        $b .= $this->_row('Plan Type', ucfirst($inv['period'] ?: 'daily'));
        $b .= $this->_row('Duration', $dur.' days');
        $b .= $this->_row('ROI Structure', $roiPct.'% '.($inv['period'] ?: 'daily').' × '.$dur.' days');
        $b .= $this->_row('Purchase Date', html_escape($inv['created_date'] ?? $inv['starting_date']));
        $b .= $this->_row('Maturity Date', html_escape($inv['mature_date']));
        $b .= $this->_row('Wallet Used', 'USDT Wallet');
        $b .= $this->_row('Blockchain Tx Hash', $tx ? ('<a href="'.$ex.'/tx/'.html_escape($tx).'">'.html_escape($tx).'</a>') : 'Internal (no on-chain hash)');
        $b .= $this->_row('User', html_escape(($u['name'] ?? $u['username'] ?? 'User').' ('.($u['email'] ?? '').')'));
        $b .= '</table>';

        $qr = $tx ? ($ex.'/tx/'.$tx) : base_url('user/stakings/receipt/'.(int)$inv['id']);
        $this->_shell('Investment Purchase Receipt', $docNo, $b, $qr, $tx ? 'Scan → blockchain explorer' : 'Scan to verify');
    }

    public function agreement($id)
    {
        $inv = $this->_ctx($id); $u = $this->_user($inv['user_id']);
        $docNo = $this->_record('agreement', $inv, null);
        $dur = (int)$inv['days_duration']; $roiPct = (float)$inv['pkg_roi'];
        list($brand,) = $this->_brand();

        $b  = '<h1>Investment Agreement</h1><div class="sub">Between '.html_escape($brand).' and the Investor</div>';
        $b .= '<table class="kv">'.$this->_row('Agreement No.', '<b>'.html_escape($docNo).'</b>')
            . $this->_row('Investor', html_escape($u['name'] ?? $u['username'] ?? 'User'))
            . $this->_row('Package', html_escape($inv['package_name']).' (#'.$inv['id'].')')
            . $this->_row('Stake Amount', number_format((float)$inv['invest_amount'],2)).'</table>';
        $b .= '<h2>1. Lock Period</h2><div class="terms">The staked amount is locked for the full term of '.$dur.' days, from '.html_escape($inv['starting_date']).' to '.html_escape($inv['mature_date']).'.</div>';
        $b .= '<h2>2. ROI Rules</h2><div class="terms">ROI accrues at '.$roiPct.'% ('.($inv['period'] ?: 'daily').') on the staked principal and is credited to the Earning wallet per the platform schedule. ROI records are immutable.</div>';
        $b .= '<h2>3. Early Withdrawal Policy</h2><div class="terms">Principal is locked until maturity. '.((int)($inv['retrun_principle'] ?? 1) === 1 ? 'Principal is returned at maturity.' : 'Principal treatment follows the package rule.').' Early withdrawal of the locked principal before maturity is not permitted.</div>';
        $b .= '<h2>4. Risk Disclaimer</h2><div class="terms">Staking involves market and blockchain risk. Returns are not guaranteed against protocol, network, or regulatory events. The Investor accepts these risks.</div>';
        $b .= '<h2>5. Terms &amp; Conditions</h2><ul class="terms"><li>ROI, ledger and blockchain records are immutable and auditable on-chain.</li><li>The one-time bonus and any group incentive follow the platform policy and ceilings.</li><li>The platform may process ROI via scheduled jobs; duplicate ROI is prevented.</li></ul>';
        $b .= '<h2>6. Company Information</h2><div class="terms">'.html_escape($brand).' — staking services provider. This agreement is generated electronically and is valid without a physical signature.</div>';
        $b .= '<h2>7. User Acceptance</h2><div class="terms">By purchasing this stake on '.html_escape($inv['created_date'] ?? $inv['starting_date']).', the Investor (#'.$inv['user_id'].') acknowledges and accepts all terms above.</div>';

        $this->_shell('Investment Agreement', $docNo, $b, base_url('user/stakings/agreement/'.(int)$inv['id']), 'Scan to verify agreement');
    }

    public function roi_schedule($id)
    {
        $inv = $this->_ctx($id);
        $docNo = $this->_record('roi_schedule', $inv, null);
        $amount = (float)$inv['invest_amount']; $roiPct = (float)$inv['pkg_roi']; $dur = (int)$inv['days_duration'];
        $roiRows = $this->_roiRows($inv);
        $earned = 0; foreach ($roiRows as $r) $earned += (float)$r['amount'];
        $perCycle = $amount * ($roiPct/100);
        $expected = $perCycle * $dur;
        $isFixed = strtolower($inv['period'] ?: '') === 'maturity' || strtolower($inv['period'] ?: '') === 'fixed';

        $b  = '<h1>ROI Schedule</h1><div class="sub">Document '.html_escape($docNo).'</div>';
        $b .= '<h2>Investment Summary</h2><table class="kv">'
            . $this->_row('Package', html_escape($inv['package_name']).' (#'.$inv['id'].')')
            . $this->_row('Stake Amount', number_format($amount,2))
            . $this->_row('ROI', $roiPct.'% '.($inv['period'] ?: 'daily'))
            . $this->_row('Duration', $dur.' cycles')
            . $this->_row('Per-cycle ROI', number_format($perCycle,4))
            . $this->_row('Next ROI Date', html_escape($inv['run_date'] ?? '—'))
            . $this->_row('Completed ROI', number_format($earned,4))
            . $this->_row('Pending ROI', number_format(max(0,$expected-$earned),4))
            . $this->_row('Total Expected Return', '<b>'.number_format($expected,4).'</b>').'</table>';

        if ($isFixed) {
            $b .= '<h2>Maturity Payout (Fixed Plan)</h2><table class="grid"><tr><th>Maturity Date</th><th>Principal</th><th>Total ROI</th><th>Payout</th></tr>'
                . '<tr><td>'.html_escape($inv['mature_date']).'</td><td>'.number_format($amount,2).'</td><td>'.number_format($expected,4).'</td><td><b>'.number_format($amount+$expected,4).'</b></td></tr></table>';
        } else {
            $b .= '<h2>ROI Schedule (per cycle)</h2><table class="grid"><tr><th>#</th><th>Expected Date</th><th>ROI Amount</th><th>Status</th></tr>';
            $start = strtotime($inv['starting_date'] ?: ($inv['created_date'] ?? 'now'));
            $shown = min($dur, 60);
            for ($i = 1; $i <= $shown; $i++) {
                $d = date('Y-m-d', strtotime('+'.$i.' day', $start));
                $done = $i <= count($roiRows);
                $b .= '<tr><td>'.$i.'</td><td>'.$d.'</td><td>'.number_format($perCycle,4).'</td><td>'.($done?'<b style="color:#16a34a;">Paid</b>':'Pending').'</td></tr>';
            }
            $b .= '</table>';
            if ($dur > $shown) $b .= '<div class="sub">Showing first '.$shown.' of '.$dur.' cycles.</div>';
        }

        $this->_shell('ROI Schedule', $docNo, $b, base_url('user/stakings/roi-schedule/'.(int)$inv['id']), 'Scan to verify');
    }

    public function summary($id)
    {
        $inv = $this->_ctx($id); $ex = $this->_explorer();
        $docNo = $this->_record('summary', $inv, null);
        $amount = (float)$inv['invest_amount']; $roiPct = (float)$inv['pkg_roi']; $dur = (int)$inv['days_duration'];
        $roiRows = $this->_roiRows($inv); $earned = 0; foreach ($roiRows as $r) $earned += (float)$r['amount'];
        $expected = $amount * ($roiPct/100) * $dur;

        $tx  = $this->db->select("COUNT(*) c, COALESCE(SUM(gas_fee_total),0) gas")->where('user_id',(int)$inv['user_id'])->get('onchain_transactions')->row_array();
        $led = $this->db->select("COUNT(*) c, COALESCE(SUM(credit),0) cr, COALESCE(SUM(debit),0) db")->where('user_id',(int)$inv['user_id'])->get('wallet_ledger')->row_array();
        $statusMap = ['0'=>'Pending','1'=>'Active','2'=>'Matured','3'=>'Cancelled'];
        $st = $statusMap[(string)$inv['status']] ?? 'Active';
        if ((string)$inv['status'] === '2' && (int)($inv['recived_status'] ?? 0) === 1) $st = 'Completed';

        $b  = '<h1>Investment Summary Report</h1><div class="sub">Document '.html_escape($docNo).'</div>';
        $b .= '<h2>Package Details</h2><table class="kv">'
            . $this->_row('Package', html_escape($inv['package_name']).' (#'.$inv['id'].')')
            . $this->_row('Stake Amount', number_format($amount,2))
            . $this->_row('ROI / Duration', $roiPct.'% '.($inv['period'] ?: 'daily').' × '.$dur.' days')
            . $this->_row('Purchase → Maturity', html_escape(($inv['created_date'] ?? $inv['starting_date']).' → '.$inv['mature_date']))
            . $this->_row('Current Status', '<b>'.$st.'</b>').'</table>';
        $b .= '<h2>ROI</h2><table class="kv">'
            . $this->_row('ROI Earned', number_format($earned,4))
            . $this->_row('Pending ROI', number_format(max(0,$expected-$earned),4))
            . $this->_row('Expected Final', '<b>'.number_format($expected,4).'</b>').'</table>';
        $b .= '<h2>Transaction History Summary</h2><table class="kv">'
            . $this->_row('On-chain Transactions', (int)$tx['c'])
            . $this->_row('Total Gas Fee (BNB)', number_format((float)$tx['gas'],8)).'</table>';
        $b .= '<h2>Wallet Ledger Summary</h2><table class="kv">'
            . $this->_row('Ledger Movements', (int)$led['c'])
            . $this->_row('Total Credited', number_format((float)$led['cr'],4))
            . $this->_row('Total Debited', number_format((float)$led['db'],4)).'</table>';

        $this->_shell('Investment Summary Report', $docNo, $b, base_url('user/stakings/summary/'.(int)$inv['id']), 'Scan to verify');
    }
}
