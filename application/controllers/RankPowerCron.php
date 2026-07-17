<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RankPowerCron — daily Rank Power cycle roll + power recalculation (§11).
 *
 * Run daily:
 *   CLI  : php index.php rankpowercron run
 *   HTTP : /rank-power-cron?token=<cron_token>
 *   All  : /rank-power-cron?token=<t>&all=1        (sweep everyone in one go)
 *   Cycle: /rank-power-cron?token=<t>&cycle_only=1 (roll the cycle, skip the maths)
 *
 * Two jobs, in order:
 *   1. open the first cycle, or close an expired one and open the next starting
 *      the very next day — power resets implicitly, because a new cycle has no
 *      user_rank_power rows;
 *   2. recalculate every active member's power rank from THIS CYCLE's volume.
 *
 * This cron NEVER touches user_ranks. Power is dynamic and disposable; the
 * permanent achievement rank belongs to RankAchievementCron.
 *
 * A thin transport wrapper — logic lives in Rankcron_model::runPower().
 */
class RankPowerCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankcron_model', 'rankcron');
    }

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }
        @set_time_limit(0);

        $res = $this->rankcron->runPower(
            (bool)$this->input->get('all'),
            (bool)$this->input->get('cycle_only')
        );

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($res));
    }
}
