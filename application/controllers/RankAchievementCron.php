<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RankAchievementCron — hourly permanent-rank evaluation (§10).
 *
 * Run hourly:
 *   CLI  : php index.php rankachievementcron run
 *   HTTP : /rank-achievement-cron?token=<cron_token>
 *   All  : /rank-achievement-cron?token=<t>&all=1   (sweep everyone in one go)
 *
 * A thin transport wrapper — all logic lives in Rankcron_model::runAchievement()
 * so Cron Lab and the admin pages drive the identical code path without HTTP.
 *
 * Idempotent. Re-running is free: no new qualification means no write at all,
 * and unique indexes on rank_rewards / rank_certificates make a repeated pass
 * incapable of paying or issuing twice.
 */
class RankAchievementCron extends CI_Controller
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

        $res = $this->rankcron->runAchievement((bool)$this->input->get('all'));

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($res));
    }
}
