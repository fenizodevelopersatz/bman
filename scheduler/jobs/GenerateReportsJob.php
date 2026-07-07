<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Appends a daily summary row to scheduler/storage/reports/report-YYYY-MM-DD.csv
 * Swap collectStats() for whatever your project actually needs to report on.
 */
final class GenerateReportsJob implements JobInterface
{
    public function handle(): array
    {
        $dir = Config::storageDir() . '/reports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $stats = $this->collectStats();
        $path = $dir . '/report-' . date('Y-m-d') . '.csv';
        $isNewFile = !is_file($path);

        $handle = fopen($path, 'a');
        if ($handle === false) {
            throw new RuntimeException("Could not open report file for writing: $path");
        }

        if ($isNewFile) {
            fputcsv($handle, array_keys($stats));
        }
        fputcsv($handle, array_values($stats));
        fclose($handle);

        return ['report_file' => basename($path), 'row_written' => $stats];
    }

    private function collectStats(): array
    {
        $mailSentDir = Config::storageDir() . '/mail_sent';
        $mailQueueDir = Config::storageDir() . '/mail_queue';
        $logFile = Config::logFile();

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'emails_sent_total' => count(glob($mailSentDir . '/*.json') ?: []),
            'emails_queued' => count(glob($mailQueueDir . '/*.json') ?: []),
            'cron_log_size_bytes' => is_file($logFile) ? filesize($logFile) : 0,
        ];
    }
}
