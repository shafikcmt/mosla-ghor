<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Marketing-source (UTM) helpers. No PII is ever handled here: only utm_* tags,
 * the landing path (no query string) and the referrer host.
 */
class MarketingSource
{
    public const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    /** Columns copied from an enquiry to an order created from it. */
    public const COLUMNS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'landing_path', 'referrer_host', 'visitor_id'];

    public const COOKIE = 'ms_attr';

    /** trim, max 100 chars, keep only [A-Za-z0-9 _.\-|:+]; null when nothing is left. */
    public static function clean(mixed $value, int $max = 100): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = preg_replace('/[^A-Za-z0-9 _.\-|:+]/', '', $value) ?? '';
        $value = mb_substr(trim($value), 0, $max);

        return $value === '' ? null : $value;
    }

    /** Valid utm_* values found in a query array (empty when none). */
    public static function utmFromQuery(array $query): array
    {
        $out = [];
        foreach (self::UTM_KEYS as $key) {
            $v = self::clean($query[$key] ?? null);
            if ($v !== null) {
                $out[$key] = $v;
            }
        }

        return $out;
    }

    /** Path only (never a query string), must start with "/". */
    public static function cleanPath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }
        $path = explode('?', explode('#', $path)[0])[0];
        $path = preg_replace('/[^A-Za-z0-9_.\-\/%~]/', '', $path) ?? '';
        if ($path === '' || $path[0] !== '/') {
            return null;
        }

        return mb_substr($path, 0, 255);
    }

    public static function cleanHost(mixed $host): ?string
    {
        if (! is_string($host)) {
            return null;
        }
        $host = strtolower(preg_replace('/[^A-Za-z0-9.\-]/', '', $host) ?? '');

        return $host === '' ? null : mb_substr($host, 0, 100);
    }

    /** Column array from the `ms_attr` (+ `ms_vid`) cookies; [] when absent or invalid. */
    public static function fromRequest(Request $request): array
    {
        $out = [];
        try {
            $vid = $request->cookie(VisitLogger::COOKIE);
            if (VisitLogger::validVisitorId($vid)) {
                $out['visitor_id'] = strtolower($vid);
            }
            $raw = $request->cookie(self::COOKIE);
            if (! is_string($raw) || $raw === '') {
                return $out;
            }
            $data = json_decode($raw, true);
            if (! is_array($data)) {
                return $out;
            }
            $utm = self::utmFromQuery($data);
            if (! $utm) {
                return $out;
            }
            $out += $utm;
            if (($p = self::cleanPath($data['landing_path'] ?? null)) !== null) {
                $out['landing_path'] = $p;
            }
            if (($h = self::cleanHost($data['referrer_host'] ?? null)) !== null) {
                $out['referrer_host'] = $h;
            }

            return $out;
        } catch (\Throwable $e) {
            return $out;
        }
    }

    /** Same columns copied from a stored record (e.g. the enquiry an order was created from). */
    public static function fromModel(?object $model): array
    {
        if (! $model) {
            return [];
        }
        $out = [];
        foreach (self::COLUMNS as $col) {
            $v = $model->{$col} ?? null;
            if ($v !== null && $v !== '') {
                $out[$col] = $v;
            }
        }

        return $out;
    }

    /** Compact admin label: "facebook / automation / wholesale_comment", or null when untagged. */
    public static function label(?object $model): ?string
    {
        if (! $model) {
            return null;
        }
        $parts = array_filter([
            $model->utm_source ?? null,
            $model->utm_medium ?? null,
            $model->utm_campaign ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $parts ? implode(' / ', $parts) : null;
    }
}
