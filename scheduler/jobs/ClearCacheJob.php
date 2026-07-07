<?php
defined('CRON_SYSTEM') OR exit('Direct access forbidden');

/**
 * Deletes stale files from the cache directory.
 *
 * Targets scheduler/storage/cache/ (a self-contained demo directory) by
 * default so a fresh install never risks deleting anything real. Add your
 * actual cache folder(s) - e.g. application/cache - to $directories once
 * you're ready to wire this into the live app.
 */
final class ClearCacheJob implements JobInterface
{
    /** How old a file must be (seconds) before it's considered stale. */
    private const MAX_AGE_SECONDS = 3600; // 1 hour

    public function handle(): array
    {
        $directories = [
            Config::storageDir() . '/cache',
            // Config::rootDir() . '/application/cache', // uncomment to also sweep the CI app cache
        ];

        $deleted = 0;
        $scanned = 0;
        $bytesFreed = 0;
        $now = time();

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() || !$file->isFile() || $file->getFilename() === '.gitkeep') {
                    continue;
                }

                $scanned++;
                if (($now - $file->getMTime()) >= self::MAX_AGE_SECONDS) {
                    $size = $file->getSize();
                    if (@unlink($file->getPathname())) {
                        $deleted++;
                        $bytesFreed += $size;
                    }
                }
            }
        }

        return [
            'directories_scanned' => count(array_filter($directories, 'is_dir')),
            'files_scanned' => $scanned,
            'files_deleted' => $deleted,
            'bytes_freed' => $bytesFreed,
        ];
    }
}
