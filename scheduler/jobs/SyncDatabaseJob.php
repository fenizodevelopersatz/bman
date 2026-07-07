<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Template for a periodic database sync/maintenance task.
 *
 * Reuses this project's own DB credentials (DB_HOST/DB_USERNAME/DB_PASS/DB_NAME,
 * defined in config.php at the project root) so there's nothing extra to
 * configure locally. Replace syncStep() with real sync logic - e.g. push
 * local writes to a reporting replica, reconcile a staging table, etc.
 */
final class SyncDatabaseJob implements JobInterface
{
    public function handle(): array
    {
        $rootConfig = Config::rootDir() . '/config.php';
        if (is_file($rootConfig)) {
            require_once $rootConfig;
        }

        if (!defined('DB_HOST') || !defined('DB_NAME')) {
            return ['status' => 'skipped', 'reason' => 'DB_HOST/DB_NAME not defined - nothing to sync against'];
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

        try {
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (PDOException $e) {
            // Re-thrown so CronRunner logs it and marks this job "error" for the run.
            throw new RuntimeException('DB connection failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->syncStep($pdo);
    }

    /**
     * Example step: a lightweight connectivity/consistency check. Replace
     * with your real sync (e.g. INSERT ... SELECT into a reporting table).
     */
    private function syncStep(PDO $pdo): array
    {
        $tableCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchColumn();

        return [
            'connected_to' => DB_NAME,
            'tables_visible' => $tableCount,
            'note' => 'Replace syncStep() with real sync logic - this only verifies connectivity.',
        ];
    }
}
