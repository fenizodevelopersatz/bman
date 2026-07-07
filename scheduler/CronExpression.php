<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Minimal 5-field cron expression matcher: "minute hour day-of-month month day-of-week".
 *
 * This is what lets a single Windows Task Scheduler trigger firing every
 * minute behave like a real Linux crontab: cron.php checks every job's
 * expression against "now" and only runs the ones that are due this minute.
 *
 * Supported syntax per field: *, N, N-M, * / N, N-M / N, and comma lists of any of those.
 * Not supported: names (JAN, MON), "L", "W", "#", step-less "?", or the
 * non-standard "7 = Sunday" alias (use 0).
 */
final class CronExpression
{
    public static function isDue(string $expression, DateTimeInterface $time): bool
    {
        $parts = preg_split('/\s+/', trim($expression));
        if ($parts === false || count($parts) !== 5) {
            throw new InvalidArgumentException("Invalid cron expression \"$expression\": expected 5 space-separated fields");
        }

        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

        return self::fieldMatches($minute, (int)$time->format('i'), 0, 59)
            && self::fieldMatches($hour, (int)$time->format('G'), 0, 23)
            && self::fieldMatches($dayOfMonth, (int)$time->format('j'), 1, 31)
            && self::fieldMatches($month, (int)$time->format('n'), 1, 12)
            && self::fieldMatches($dayOfWeek, (int)$time->format('w'), 0, 6); // 0 = Sunday
    }

    private static function fieldMatches(string $field, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $token) {
            if (self::tokenMatches(trim($token), $value, $min, $max)) {
                return true;
            }
        }
        return false;
    }

    private static function tokenMatches(string $token, int $value, int $min, int $max): bool
    {
        $step = 1;
        if (strpos($token, '/') !== false) {
            [$token, $stepPart] = explode('/', $token, 2);
            $step = max(1, (int)$stepPart);
        }

        if ($token === '*') {
            $rangeMin = $min;
            $rangeMax = $max;
        } elseif (strpos($token, '-') !== false) {
            [$rangeMin, $rangeMax] = array_map('intval', explode('-', $token, 2));
        } else {
            $rangeMin = $rangeMax = (int)$token;
        }

        if ($value < $rangeMin || $value > $rangeMax) {
            return false;
        }

        return (($value - $rangeMin) % $step) === 0;
    }
}
