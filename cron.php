<?php
/**
 * Public cron entry point - the "poor man's crontab" for Windows.
 *
 * Windows Task Scheduler fires this once a minute (see scheduler/task/).
 * This script then checks every registered job's cron expression against
 * the current time and runs only the ones that are due - exactly like a
 * real Linux crontab, just driven from the outside by Task Scheduler
 * instead of the OS's own cron daemon.
 *
 * Usage:
 *   HTTP: http://localhost/<project>/cron.php?token=YOUR_SECRET
 *         http://localhost/<project>/cron.php?token=YOUR_SECRET&job=clear_cache   (force one job, ignore its schedule)
 *   CLI:  php cron.php
 *         php cron.php job=clear_cache
 *
 * Configuration (token, allowed IPs, job list, schedules) lives in
 * scheduler/Config.php. Full setup/debugging guide: scheduler/README.md
 */

declare(strict_types=1);

// Marks every scheduler/*.php file as "loaded from the real entry point" -
// each of those files refuses to run if hit directly over the web.
define('CRON_SYSTEM', true);

$start = microtime(true);
$isCli = PHP_SAPI === 'cli';

// ---------------------------------------------------------------------------
// Autoload the scheduler's classes. No Composer needed - it's a two-folder
// convention: scheduler/{Class}.php and scheduler/jobs/{Class}.php. This also
// sidesteps any require-order pitfalls (e.g. a job class implementing
// JobInterface before JobInterface itself has loaded).
// ---------------------------------------------------------------------------
$schedulerDir = __DIR__ . '/scheduler';
spl_autoload_register(static function (string $class) use ($schedulerDir): void {
    foreach ([$schedulerDir . '/' . $class . '.php', $schedulerDir . '/jobs/' . $class . '.php'] as $file) {
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

// Convert PHP warnings/notices into exceptions so a stray warning from a job
// can never leak into the JSON body and corrupt it - it gets caught below,
// logged, and reported as a clean error response instead.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false; // respects the @ error-suppression operator
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

error_reporting(E_ALL);
ini_set('display_errors', '0'); // never let raw PHP errors corrupt the JSON response

date_default_timezone_set(Config::timezone());

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

// ---------------------------------------------------------------------------
// Everything below returns [httpStatus, payloadArray] and never calls exit()
// itself, so the single try/finally around job execution is guaranteed to
// release the lock before the script's one and only exit point at the bottom.
// ---------------------------------------------------------------------------
[$httpStatus, $payload] = (static function () use ($isCli): array {

    // 1) Figure out who's asking and what they want to run.
    $requestedJob = null;

    if ($isCli) {
        // $argv only exists under the CLI SAPI (and even there, only when
        // register_argc_argv is on - the CLI default). Reading it via
        // $GLOBALS avoids an "undefined variable" trip through the strict
        // error handler above when this closure is analyzed under HTTP.
        $cliArgs = $GLOBALS['argv'] ?? [];
        foreach (array_slice($cliArgs, 1) as $arg) {
            if (preg_match('/^job=(.+)$/', $arg, $m)) {
                $requestedJob = $m[1];
            }
        }
    } else {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $allowedIps = Config::allowedIps();

        if ($allowedIps && !in_array($clientIp, $allowedIps, true)) {
            Logger::warning('Blocked cron request from disallowed IP', ['ip' => $clientIp]);
            return [403, ['status' => 'error', 'message' => 'Forbidden: IP not allowed']];
        }

        $providedToken = (string) ($_GET['token'] ?? '');
        if ($providedToken === '' || !hash_equals(Config::token(), $providedToken)) {
            Logger::warning('Blocked cron request with invalid token', ['ip' => $clientIp]);
            return [401, ['status' => 'error', 'message' => 'Unauthorized: invalid or missing token']];
        }

        if (strpos(Config::token(), 'CHANGE_ME_') === 0) {
            Logger::warning('cron.php is using the default placeholder token - set CRON_TOKEN or scheduler/config.local.php before exposing this beyond localhost');
        }

        $requestedJob = isset($_GET['job']) ? (string) $_GET['job'] : null;
    }

    // 2) Run it, guarded by an exclusive lock so a slow run (or an overlapping
    //    Task Scheduler trigger) can never execute jobs twice at once.
    $runner = new CronRunner();

    if (!$runner->acquireLock()) {
        Logger::warning('Cron run skipped: another run is already in progress');
        return [423, ['status' => 'locked', 'message' => 'Another cron run is already in progress']];
    }

    try {
        $now = new DateTimeImmutable('now');

        $results = $requestedJob !== null
            ? $runner->runByName($requestedJob)
            : $runner->runDue($now);

        $failed = array_values(array_filter($results, static fn (array $r): bool => $r['status'] === 'error'));

        Logger::info('Cron run finished', [
            'mode' => $requestedJob !== null ? "manual:$requestedJob" : 'scheduled',
            'jobs_run' => count($results),
            'jobs_failed' => count($failed),
        ]);

        $status = empty($results) ? 'no_jobs_due' : (empty($failed) ? 'ok' : 'partial_failure');

        return [empty($failed) ? 200 : 500, [
            'status' => $status,
            'mode' => $requestedJob !== null ? 'manual' : 'scheduled',
            'jobs' => array_values($results),
        ]];
    } catch (InvalidArgumentException $e) {
        // e.g. ?job=some_typo_name
        Logger::warning('Cron run rejected: ' . $e->getMessage());
        return [400, ['status' => 'error', 'message' => $e->getMessage()]];
    } catch (Throwable $e) {
        Logger::error('Cron run crashed: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        return [500, ['status' => 'error', 'message' => $e->getMessage()]];
    } finally {
        $runner->releaseLock();
    }
})();

// ---------------------------------------------------------------------------
// Single exit point: attach timing, send the status code + JSON, and exit
// with a code the .bat/.ps1 wrapper (and Task Scheduler) can act on.
// ---------------------------------------------------------------------------
$payload['execution_time_ms'] = round((microtime(true) - $start) * 1000, 2);
$payload['timestamp'] = date('Y-m-d H:i:s');

if (!$isCli) {
    http_response_code($httpStatus);
}

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($httpStatus >= 200 && $httpStatus < 300 ? 0 : 1);
