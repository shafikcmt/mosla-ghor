<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Proves (without shell access) whether storage/app/private survives deploys: a marker
 * file records when and under which commit it was first written. If a later deploy runs
 * a different commit and the marker is still there, the folder persisted.
 */
class StoragePersistence
{
    public const MARKER = '.persistence-check';

    /** @return array{ok: bool, created_at: ?string, created_commit: ?string, current_commit: string, survived: bool, error: ?string} */
    public static function status(): array
    {
        $current = BuildInfo::commit();
        $disk = Storage::disk('local'); // storage/app/private
        try {
            if (! $disk->exists(self::MARKER)) {
                $disk->put(self::MARKER, json_encode(['created_at' => now()->toIso8601String(), 'commit' => $current]));
            }
            $data = json_decode((string) $disk->get(self::MARKER), true) ?: [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'created_at' => null, 'created_commit' => null, 'current_commit' => $current,
                'survived' => false, 'error' => get_class($e)];
        }

        $createdCommit = $data['commit'] ?? null;
        return [
            'ok' => true,
            'created_at' => $data['created_at'] ?? null,
            'created_commit' => $createdCommit,
            'current_commit' => $current,
            // Still here while a different commit is running → survived at least one deploy.
            'survived' => $createdCommit && $createdCommit !== 'unknown' && $current !== 'unknown' && $createdCommit !== $current,
            'error' => null,
        ];
    }
}
