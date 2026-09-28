<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Recompress images uploaded before automatic optimisation existed.
 * Same format, same path → no database changes. Dry run unless --apply.
 */
class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize-existing
        {--apply : Actually overwrite files (a backup of every original is kept)}
        {--min-kb=150 : Skip files smaller than this}
        {--backup-root= : Backup directory (default: storage/app/image-backups)}';

    protected $description = 'Recompress old uploaded images in place (dry run by default)';

    /** Public-disk folder => ImageOptimizer profile. KYC is never listed. */
    private const FOLDERS = [
        'products/images'     => 'product',
        'products/variants'   => 'product',
        'hero-slides'         => 'hero',
        'vendors/logos'       => 'logo',
        'vendors/banners'     => 'banner',
        'reviews'             => 'review',
        'return-requests'     => 'return',
        'payment-screenshots' => 'payment',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $minBytes = max(0, (int) $this->option('min-kb')) * 1024;
        $disk = Storage::disk('public');
        $backupBase = $this->option('backup-root') ?: storage_path('app/image-backups');
        $backupRoot = now()->format('Y-m-d_His');
        $backupDisk = Storage::build(['driver' => 'local', 'root' => $backupBase]);
        $rows = [];
        $totalBefore = $totalAfter = 0;
        $counts = ['optimized' => 0, 'skipped' => 0];

        $this->line($apply ? '<comment>APPLY mode: files will be overwritten (originals backed up to '.$backupBase.'/'.$backupRoot.').</comment>'
                           : '<info>Dry run: nothing will be changed. Use --apply to write.</info>');

        foreach (self::FOLDERS as $folder => $profileName) {
            if (! $disk->exists($folder)) {
                continue;
            }
            $profile = ImageOptimizer::profile($profileName);
            foreach ($disk->allFiles($folder) as $relative) {
                if (! preg_match('/\.(jpe?g|png|webp)$/i', $relative)) {
                    continue;
                }
                $full = $disk->path($relative);
                $before = (int) @filesize($full);
                if ($before < $minBytes) {
                    $counts['skipped']++;
                    continue;
                }

                // Same format + same path; og crop is not re-applied to old files.
                $result = ImageOptimizer::optimize($full, ['crop' => null] + $profile, $profileName, sameFormat: true);
                if ($result === null || strlen($result['bytes']) >= $before) {
                    $counts['skipped']++;
                    continue;
                }

                $after = strlen($result['bytes']);
                $totalBefore += $before;
                $totalAfter += $after;
                $counts['optimized']++;
                $rows[] = [$relative, $this->kb($before), $this->kb($after), round(100 - $after / $before * 100).'%'];

                if ($apply) {
                    // storage/app/image-backups/<date>/<path> (private, not web-reachable)
                    $backup = $backupRoot.'/'.$relative;
                    $backupDisk->put($backup, (string) file_get_contents($full));
                    if (! $backupDisk->exists($backup) || $backupDisk->size($backup) !== $before) {
                        $this->error("Backup failed, skipped: {$relative}");
                        continue;
                    }
                    file_put_contents($full, $result['bytes'], LOCK_EX);
                }
            }
        }

        if ($rows) {
            $this->table(['File', 'Now', ($apply ? 'New' : 'Estimated'), 'Saving'], $rows);
        }
        $this->info(sprintf('%d file(s) %s, %d skipped (already small, not smaller, or unreadable). Total: %s → %s (saves %s).',
            $counts['optimized'], $apply ? 'optimized' : 'can be optimized', $counts['skipped'],
            $this->kb($totalBefore), $this->kb($totalAfter), $this->kb($totalBefore - $totalAfter)));

        return self::SUCCESS;
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 2).' MB' : round($bytes / 1024).' KB';
    }
}
