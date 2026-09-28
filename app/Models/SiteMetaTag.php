<?php

namespace App\Models;

use App\Support\MetaTagRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Admin-managed <head> meta tag, e.g. facebook-domain-verification. Never raw HTML. */
class SiteMetaTag extends Model
{
    public const CACHE_KEY = 'site_meta_tags.active';
    public const CACHE_SECONDS = 3600;

    protected $fillable = ['label', 'attribute', 'name', 'content', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Every save (incl. on/off toggle) and delete clears the head cache immediately.
        $forget = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Active tags for the storefront <head>, cached for an hour. Re-checked against the
     * rules on the way out, so a row edited directly in the database can never inject markup.
     *
     * @return array<int, array{attribute: string, name: string, content: string}>
     */
    public static function forHead(): array
    {
        try {
            $rows = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => static::query()
                ->where('is_active', true)->orderBy('sort_order')->orderBy('id')
                ->get(['attribute', 'name', 'content'])->toArray());
        } catch (\Throwable $e) {
            Log::warning('Site meta tags unavailable; none rendered.', ['error' => get_class($e)]);
            return [];
        }

        return array_values(array_filter($rows, fn ($t) => MetaTagRules::safeForOutput(
            (string) ($t['attribute'] ?? ''), (string) ($t['name'] ?? ''), (string) ($t['content'] ?? '')
        )));
    }

    /** The exact tag this row produces (escaped), for the admin preview/list. */
    public function preview(): string
    {
        return MetaTagRules::render($this->attribute, $this->name, $this->content);
    }
}
