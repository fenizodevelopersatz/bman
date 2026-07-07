<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Contract every scheduled job must implement. Register new jobs in Config::jobs().
 */
interface JobInterface
{
    /** Do the work. Return JSON-encodable details for the log/response. Throw on failure - CronRunner catches it per-job. */
    public function handle(): array;
}
