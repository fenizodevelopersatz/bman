<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

// Job classes are resolved lazily via the autoloader registered in cron.php -
// `ClassName::class` below is just a string, it never triggers a load.

/**
 * Central configuration for the cron scheduler.
 *
 * Secrets (the token) are NOT hardcoded here. They are read from, in order:
 *   1. the CRON_TOKEN environment variable (set it in Apache vhost / .env loader / IIS)
 *   2. scheduler/config.local.php (gitignored - copy scheduler/config.local.example.php)
 *   3. a loud placeholder that intentionally fails closed until changed
 */
final class Config
{
    /** scheduler/ folder */
    public static function baseDir(): string
    {
        return __DIR__;
    }

    /** project root (one level above scheduler/) */
    public static function rootDir(): string
    {
        return dirname(__DIR__);
    }

    public static function logDir(): string
    {
        return self::rootDir() . '/logs';
    }

    public static function logFile(): string
    {
        return self::logDir() . '/cron.log';
    }

    public static function storageDir(): string
    {
        return self::baseDir() . '/storage';
    }

    public static function lockFile(): string
    {
        return self::storageDir() . '/cron.lock';
    }

    /** Best-effort per-job wall-clock cap (seconds). The hard backstop is the
     *  Task Scheduler "Stop task if it runs longer than" setting in the XML. */
    public static function jobTimeoutSeconds(): int
    {
        return 25;
    }

    /** Loads scheduler/config.local.php once, if present. */
    private static function loadLocalOverrides(): array
    {
        static $loaded = null;
        if ($loaded !== null) {
            return $loaded;
        }

        $file = self::baseDir() . '/config.local.php';
        $loaded = is_file($file) ? (require $file) : [];
        return is_array($loaded) ? $loaded : [];
    }

    /**
     * The shared secret every HTTP call to cron.php must present as ?token=...
     * CLI calls skip this check entirely (see cron.php).
     */
    public static function token(): string
    {
        $env = getenv('CRON_TOKEN');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        $local = self::loadLocalOverrides();
        if (!empty($local['token'])) {
            return $local['token'];
        }

        // Placeholder on purpose: run install_scheduler.php or copy
        // config.local.example.php -> config.local.php and set a real value.
        return 'CHANGE_ME_' . 'dcron_9f27ab5c3e8140d6';
    }

    /**
     * Client IPs allowed to trigger cron.php over HTTP. Empty array = allow any IP
     * (not recommended once this is reachable from outside localhost).
     * Defaults to loopback only, which is exactly what Task Scheduler hitting
     * http://localhost/... will present as REMOTE_ADDR.
     */
    public static function allowedIps(): array
    {
        $local = self::loadLocalOverrides();
        if (isset($local['allowed_ips']) && is_array($local['allowed_ips'])) {
            return $local['allowed_ips'];
        }
        return ['127.0.0.1', '::1'];
    }

    public static function timezone(): string
    {
        $local = self::loadLocalOverrides();
        return $local['timezone'] ?? 'Asia/Kolkata';
    }

    /**
     * Registered jobs: key => ['class' => JobInterface implementation, 'schedule' => 5-field cron expr].
     * Add new jobs here - CronRunner and cron.php need no changes.
     */
    public static function jobs(): array
    {
        return [
            'clear_cache' => [
                'class' => ClearCacheJob::class,
                'schedule' => '*/30 * * * *', // every 30 minutes
            ],
            'send_emails' => [
                'class' => SendEmailsJob::class,
                'schedule' => '*/5 * * * *', // every 5 minutes
            ],
            'sync_database' => [
                'class' => SyncDatabaseJob::class,
                'schedule' => '0 * * * *', // top of every hour
            ],
            'generate_reports' => [
                'class' => GenerateReportsJob::class,
                'schedule' => '0 0 * * *', // once a day at midnight
            ],
        ];
    }
}
