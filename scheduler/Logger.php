<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Tiny append-only file logger for the cron system.
 * Writes to logs/cron.log (denied to web access via logs/.htaccess).
 */
final class Logger
{
    /** Rotate the log once it passes this size, so a stuck job can't fill the disk. */
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = Config::logDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = Config::logFile();
        self::rotateIfNeeded($file);

        $line = sprintf(
            '[%s] [%s] %s%s' . PHP_EOL,
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''
        );

        // LOCK_EX so two overlapping requests never interleave a half-written line.
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function rotateIfNeeded(string $file): void
    {
        if (is_file($file) && filesize($file) > self::MAX_BYTES) {
            @rename($file, $file . '.' . date('Ymd_His') . '.bak');
        }
    }

    /** Last N log lines - useful when debugging from a shell without opening the file. */
    public static function tail(int $lines = 100): array
    {
        $file = Config::logFile();
        if (!is_file($file)) {
            return [];
        }
        $all = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        return array_slice($all, -$lines);
    }
}
