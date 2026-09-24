<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        $forget = fn () => request()->attributes->remove('website_settings.keyed');
        static::saved($forget);
        static::deleted($forget);
    }

    public static function get(string $key, string $default = ''): string
    {
        return static::allKeyed()[$key] ?? $default;
    }

    public static function allKeyed(): array
    {
        $attributes = request()->attributes;
        if (!$attributes->has('website_settings.keyed')) {
            $attributes->set('website_settings.keyed', static::pluck('value', 'key')->all());
        }

        return $attributes->get('website_settings.keyed');
    }

    public static function siteName(): string
    {
        return trim(static::get('site_name')) ?: (trim((string) config('app.name')) ?: 'মসলা ঘর');
    }
}
