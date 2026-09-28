<?php

namespace App\Support;

/**
 * Rules for admin-managed <head> meta tags. Admins never supply HTML: only an
 * attribute type, a name and a content value; render() builds the tag with both
 * values escaped. The same checks run on save AND on output.
 */
class MetaTagRules
{
    public const ATTRIBUTES = ['name', 'property'];
    public const NAME_PATTERN = '/^[A-Za-z0-9:._-]{1,100}$/';
    public const CONTENT_MAX = 500;

    /** Names the site already outputs itself (SEO, social, pixel). Checked case-insensitively. */
    public const RESERVED = ['description', 'keywords', 'robots', 'viewport', 'csrf-token', 'theme-color'];
    public const RESERVED_PREFIXES = ['og:', 'twitter:', 'fb:', 'product:'];

    /** Common verification tags: preset key => [label, attribute, name]. */
    public const PRESETS = [
        'facebook'  => ['Facebook (Meta) ডোমেইন ভেরিফিকেশন', 'name', 'facebook-domain-verification'],
        'google'    => ['Google Search Console', 'name', 'google-site-verification'],
        'bing'      => ['Bing Webmaster', 'name', 'msvalidate.01'],
        'yandex'    => ['Yandex Webmaster', 'name', 'yandex-verification'],
        'pinterest' => ['Pinterest', 'name', 'p:domain_verify'],
    ];

    public static function isReserved(string $name): bool
    {
        $lower = strtolower(trim($name));
        if (in_array($lower, self::RESERVED, true)) {
            return true;
        }
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public static function contentOk(string $content): bool
    {
        return mb_strlen($content) <= self::CONTENT_MAX && ! preg_match('/[<>]/', $content);
    }

    /** Final gate used when printing the tag. */
    public static function safeForOutput(string $attribute, string $name, string $content): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true)
            && preg_match(self::NAME_PATTERN, $name) === 1
            && ! self::isReserved($name)
            && trim($content) !== ''
            && self::contentOk($content);
    }

    /** Laravel validation rules for the admin form. */
    public static function rules(): array
    {
        return [
            'label'      => ['nullable', 'string', 'max:100'],
            'attribute'  => ['required', 'in:'.implode(',', self::ATTRIBUTES)],
            'name'       => ['required', 'string', 'regex:'.self::NAME_PATTERN, function ($attr, $value, $fail) {
                if (self::isReserved((string) $value)) {
                    $fail('এই নামের ট্যাগ (description, keywords, robots, viewport, og:*, twitter:* ইত্যাদি) সাইট নিজেই তৈরি করে — এখানে যোগ করা যাবে না।');
                }
            }],
            'content'    => ['required', 'string', 'max:'.self::CONTENT_MAX, 'not_regex:/[<>]/'],
            'is_active'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public static function messages(): array
    {
        return [
            'attribute.required' => 'ট্যাগের ধরন (name বা property) বেছে নিন।',
            'attribute.in'       => 'ট্যাগের ধরন শুধু name বা property হতে পারে।',
            'name.required'      => 'ট্যাগের নাম দিন।',
            'name.regex'         => 'নামে শুধু ইংরেজি অক্ষর, সংখ্যা এবং : . _ - ব্যবহার করা যাবে (সর্বোচ্চ ১০০ অক্ষর)।',
            'content.required'   => 'Content (মান) দিন।',
            'content.max'        => 'Content সর্বোচ্চ ৫০০ অক্ষর হতে পারে।',
            'content.not_regex'  => 'Content-এ < বা > চিহ্ন দেওয়া যাবে না — শুধু মানটি দিন, পুরো HTML নয়।',
            'label.max'          => 'লেবেল সর্বোচ্চ ১০০ অক্ষর।',
            'sort_order.integer' => 'ক্রম একটি পূর্ণ সংখ্যা হতে হবে।',
        ];
    }

    /** The exact tag the site will print (both values HTML-escaped). */
    public static function render(string $attribute, string $name, string $content): string
    {
        $attr = $attribute === 'property' ? 'property' : 'name';
        return '<meta '.$attr.'="'.e($name).'" content="'.e($content).'">';
    }

    /**
     * Smart paste: accept ONLY a single <meta name|property="…" content="…"> tag and
     * return its structured fields. Anything else (scripts, several tags, other
     * attributes such as http-equiv/charset) returns an error message. The raw
     * string is never stored.
     *
     * @return array{attribute: string, name: string, content: string}|string  fields, or a Bangla error
     */
    public static function parsePaste(string $raw): array|string
    {
        $raw = trim($raw);
        $invalid = 'শুধু একটি <meta …> ট্যাগ পেস্ট করুন (যেমন Meta/Google যেটি দেয়)। অন্য কোনো কোড গ্রহণযোগ্য নয়।';

        if ($raw === '' || strlen($raw) > 1000) {
            return $invalid;
        }
        // Exactly one tag: one '<' at the start, one '>' at the end.
        if (substr_count($raw, '<') !== 1 || substr_count($raw, '>') !== 1
            || ! preg_match('/^<meta\s+(.*?)\s*\/?>$/is', $raw, $m)) {
            return $invalid;
        }

        $attrs = [];
        $rest = preg_replace_callback('/([A-Za-z_:][A-Za-z0-9_:.-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', function ($a) use (&$attrs) {
            $key = strtolower($a[1]);
            $attrs[$key] = html_entity_decode($a[2] !== '' ? $a[2] : ($a[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '';
        }, $m[1], -1, $count);

        // Nothing left over (no bare/unquoted attributes), only the attributes we allow, each once.
        if (trim((string) $rest) !== '' || $count !== count($attrs)
            || array_diff(array_keys($attrs), ['name', 'property', 'content']) !== []) {
            return $invalid;
        }
        if (isset($attrs['name']) === isset($attrs['property']) || ! isset($attrs['content'])) {
            return 'ট্যাগে name (বা property) এবং content দুটোই থাকতে হবে।';
        }

        $attribute = isset($attrs['name']) ? 'name' : 'property';
        $name = trim($attrs[$attribute]);
        $content = trim($attrs['content']);

        if (! preg_match(self::NAME_PATTERN, $name)) {
            return 'ট্যাগের নাম গ্রহণযোগ্য নয়।';
        }
        if (self::isReserved($name)) {
            return 'এই নামের ট্যাগ সাইট নিজেই তৈরি করে — এখানে যোগ করা যাবে না।';
        }
        if ($content === '' || ! self::contentOk($content)) {
            return 'Content খালি, ৫০০ অক্ষরের বেশি, বা < > চিহ্ন আছে।';
        }

        return ['attribute' => $attribute, 'name' => $name, 'content' => $content];
    }

    /** Preset key matching a name, for pre-selecting the dropdown. */
    public static function presetFor(string $name): string
    {
        foreach (self::PRESETS as $key => [, , $presetName]) {
            if (strcasecmp($presetName, $name) === 0) {
                return $key;
            }
        }
        return 'custom';
    }
}
