<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One anonymous UTM-tagged landing. No IP, no user-agent, no PII. */
class MarketingVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'landing_path', 'referrer_host', 'device', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    /**
     * Delete visits older than $days (chunked). Touches ONLY marketing_visits.
     * $max = null means "everything older"; the self-prune passes 1000.
     */
    public static function pruneOlderThan(int $days, ?int $max = null): int
    {
        $cutoff = now()->subDays(max(1, $days));
        $removed = 0;
        $chunk = 500;
        do {
            $limit = $max === null ? $chunk : min($chunk, $max - $removed);
            if ($limit <= 0) {
                break;
            }
            $ids = static::query()->where('created_at', '<', $cutoff)->orderBy('id')->limit($limit)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $removed += static::query()->whereIn('id', $ids)->delete();
        } while (true);

        return $removed;
    }
}
