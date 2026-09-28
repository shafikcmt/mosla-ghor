<?php

namespace App\Support;

/**
 * Which code is running, read from the checked-out .git (the app container bind-mounts
 * the project folder). Used to detect pages opened before a deploy and for diagnostics.
 */
class BuildInfo
{
    /** Short commit hash, or 'unknown' when .git is not readable. */
    public static function commit(): string
    {
        static $commit = null;
        if ($commit !== null) {
            return $commit;
        }

        try {
            $git = base_path('.git');
            $head = trim((string) @file_get_contents($git.'/HEAD'));
            if (preg_match('/^[0-9a-f]{40}$/', $head)) {
                return $commit = substr($head, 0, 7);
            }
            if (preg_match('#^ref: (refs/[A-Za-z0-9._/-]+)$#', $head, $m)) {
                $hash = trim((string) @file_get_contents($git.'/'.$m[1]));
                if (! preg_match('/^[0-9a-f]{40}$/', $hash)) {
                    // Packed refs: "<hash> <ref>" lines.
                    foreach (@file($git.'/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                        if (str_ends_with($line, ' '.$m[1])) {
                            $hash = strtok($line, ' ');
                            break;
                        }
                    }
                }
                if (preg_match('/^[0-9a-f]{40}$/', (string) $hash)) {
                    return $commit = substr($hash, 0, 7);
                }
            }
        } catch (\Throwable) {
            // fall through
        }

        return $commit = 'unknown';
    }
}
