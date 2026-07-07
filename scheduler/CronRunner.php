<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Orchestrates job execution: locking (no overlapping runs), schedule matching,
 * per-job exception isolation, timing, and logging.
 */
final class CronRunner
{
    /** @var resource|null */
    private $lockHandle = null;

    /**
     * Try to become the only running instance. Returns false if another
     * cron.php invocation currently holds the lock (e.g. a previous minute's
     * run is still going). Non-blocking - callers should fail fast, not wait.
     */
    public function acquireLock(): bool
    {
        $lockFile = Config::lockFile();
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $handle = @fopen($lockFile, 'c');
        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $this->lockHandle = $handle;
        ftruncate($this->lockHandle, 0);
        fwrite($this->lockHandle, getmypid() . ' ' . date('Y-m-d H:i:s'));
        fflush($this->lockHandle);
        return true;
    }

    public function releaseLock(): void
    {
        if ($this->lockHandle !== null) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    /**
     * Run every registered job whose cron expression matches $now.
     * @return array<int, array<string, mixed>>
     */
    public function runDue(DateTimeInterface $now): array
    {
        $results = [];
        foreach (Config::jobs() as $key => $definition) {
            if (CronExpression::isDue($definition['schedule'], $now)) {
                $results[] = $this->runOne($key, $definition);
            }
        }
        return $results;
    }

    /** Force-run one named job regardless of its schedule (manual/CLI testing). */
    public function runByName(string $key): array
    {
        $jobs = Config::jobs();
        if (!isset($jobs[$key])) {
            throw new InvalidArgumentException(
                "Unknown job \"$key\". Known jobs: " . implode(', ', array_keys($jobs))
            );
        }
        return [$this->runOne($key, $jobs[$key])];
    }

    private function runOne(string $key, array $definition): array
    {
        $class = $definition['class'];
        /** @var JobInterface $job */
        $job = new $class();

        // Best-effort cap; the real hard stop is Task Scheduler's execution time limit (see task/CronJobTask.xml).
        set_time_limit(Config::jobTimeoutSeconds());

        $start = microtime(true);
        try {
            $details = $job->handle();
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            Logger::info("Job \"$key\" completed", ['duration_ms' => $durationMs, 'details' => $details]);

            return [
                'job' => $key,
                'status' => 'success',
                'duration_ms' => $durationMs,
                'details' => $details,
            ];
        } catch (Throwable $e) {
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            Logger::error("Job \"$key\" failed: {$e->getMessage()}", [
                'duration_ms' => $durationMs,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'job' => $key,
                'status' => 'error',
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
            ];
        }
    }
}
