<?php

namespace App\Support;

use App\Models\MarketingVisit;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

/** Anonymous visit logging for UTM-tagged landings (see CaptureMarketingSource). */
class VisitLogger
{
    public const COOKIE = 'ms_vid';
    public const SETTING = 'visit_logging_enabled';
    public const DEDUPE_MINUTES = 30;
    public const RETENTION_DAYS = 90;

    /** Admin toggle (default on). */
    public static function enabled(): bool
    {
        return WebsiteSetting::get(self::SETTING, '1') !== '0';
    }

    public static function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return $ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|facebot|meta-externalagent|whatsapp|telegrambot|preview|headless|lighthouse/i', $ua) === 1;
    }

    public static function device(?string $ua): string
    {
        $ua = (string) $ua;
        if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua) || (stripos($ua, 'android') !== false && stripos($ua, 'mobile') === false)) {
            return 'tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function validVisitorId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) === 1;
    }

    /**
     * Insert one visit row unless the same visitor+campaign was already logged in the last 30 minutes.
     * Returns true when a row was written. Occasionally prunes old rows (no scheduler needed).
     */
    public static function record(array $row): bool
    {
        $key = 'mv:'.$row['visitor_id'].':'.($row['utm_campaign'] ?? '-');
        if (! Cache::add($key, 1, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }
        MarketingVisit::create($row + ['created_at' => now()]);

        if (random_int(1, 100) === 1) {
            self::selfPrune();
        }

        return true;
    }

    /** At most 1000 rows older than 90 days per run. */
    public static function selfPrune(): int
    {
        try {
            return MarketingVisit::pruneOlderThan(self::RETENTION_DAYS, 1000);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
