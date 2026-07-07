<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Processes a simple file-based mail queue: scheduler/storage/mail_queue/*.json
 *
 * Each queued file looks like:
 *   {"to": "user@example.com", "subject": "...", "body": "..."}
 *
 * Enqueue from anywhere in the app with:
 *   file_put_contents(
 *       $schedulerStorageDir . '/mail_queue/' . uniqid('mail_', true) . '.json',
 *       json_encode(['to' => $email, 'subject' => $subject, 'body' => $body])
 *   );
 */
final class SendEmailsJob implements JobInterface
{
    private const MAX_ATTEMPTS = 3;
    private const BATCH_SIZE = 20; // cap per run so a huge backlog can't blow the job timeout

    public function handle(): array
    {
        $queueDir = Config::storageDir() . '/mail_queue';
        $sentDir = Config::storageDir() . '/mail_sent';
        $failedDir = Config::storageDir() . '/mail_failed';

        foreach ([$queueDir, $sentDir, $failedDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $files = glob($queueDir . '/*.json') ?: [];
        sort($files); // uniqid() filenames sort oldest-first
        $files = array_slice($files, 0, self::BATCH_SIZE);

        $sent = 0;
        $failed = 0;
        $retrying = 0;

        foreach ($files as $path) {
            $mail = json_decode((string) file_get_contents($path), true);

            if (!is_array($mail) || empty($mail['to']) || empty($mail['subject'])) {
                @rename($path, $failedDir . '/' . basename($path));
                $failed++;
                continue;
            }

            if ($this->deliver($mail['to'], $mail['subject'], $mail['body'] ?? '')) {
                @rename($path, $sentDir . '/' . basename($path));
                $sent++;
                continue;
            }

            $mail['attempts'] = (int)($mail['attempts'] ?? 0) + 1;
            if ($mail['attempts'] >= self::MAX_ATTEMPTS) {
                @rename($path, $failedDir . '/' . basename($path));
                $failed++;
            } else {
                file_put_contents($path, json_encode($mail));
                $retrying++;
            }
        }

        return [
            'queued_remaining' => count(glob($queueDir . '/*.json') ?: []),
            'sent' => $sent,
            'failed' => $failed,
            'retrying' => $retrying,
        ];
    }

    /** Swap this for PHPMailer/SMTP/a transactional API - keep the boolean return. */
    private function deliver(string $to, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // PHP's mail() needs a working local MTA, which XAMPP/Laragon rarely has
        // configured. It returns false when none is set up, which this job
        // already treats as a retryable failure - a safe default for local dev.
        return @mail($to, $subject, $body, "Content-Type: text/plain; charset=UTF-8");
    }
}
