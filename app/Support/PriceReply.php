<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductPrice;

/**
 * Price data + a ready Bangla reply for one product and one customer type
 * (retail = খুচরা, wholesale = পাইকারি). Shared by the admin price board and the
 * Bot API so the Facebook reply always matches what the admin set.
 * Expects activeRetailPrices.variant to be eager-loaded for retail packs.
 */
class PriceReply
{
    public const TYPES = ['retail' => 'খুচরা', 'wholesale' => 'পাইকারি'];

    public static function money(float $amount): string
    {
        return '৳'.Product::formatQty(round($amount, 2));
    }

    public static function retail(Product $p): ?array
    {
        if (! $p->show_in_retail) {
            return null;
        }
        $packs = $p->activeRetailPrices->map(fn (ProductPrice $pr) => [
            'label' => trim($pr->weight_label.($pr->variant ? ' ('.$pr->variant->name.')' : '')),
            'price' => (float) $pr->final_price,
        ])->values()->all();

        return [
            'price_per_kg' => (float) $p->retail_price_1kg > 0 ? (float) $p->retail_price_1kg : null,
            'packs' => $packs,
        ];
    }

    public static function wholesale(Product $p): ?array
    {
        if (! $p->show_in_wholesale) {
            return null;
        }

        return [
            'price_per_kg' => $p->wholesalePricePerKg(),
            'moq' => $p->moqLabel() ? Product::formatQty((float) $p->min_order_quantity).' '.Product::unitLabel($p->min_order_unit ?: 'kg') : null,
            'unit_prices' => $p->wholesaleUnitPrices(),
            'unit_conversions' => $p->unitConversionLabels(),
            'delivery_time' => $p->delivery_time,
            'payment_terms' => $p->payment_terms,
        ];
    }

    /** Ready-to-send Bangla reply text for the customer type. */
    public static function text(Product $p, string $type): string
    {
        $name = $p->display_name;
        $lines = [];

        if ($type === 'wholesale') {
            $w = self::wholesale($p);
            if (! $w) {
                return "{$name} বর্তমানে পাইকারি বিক্রি হচ্ছে না।".(self::retail($p) ? "\n\n".self::text($p, 'retail') : '');
            }
            $lines[] = "{$name} — পাইকারি দাম";
            $lines[] = $w['price_per_kg'] !== null ? '• প্রতি কেজি: '.self::money($w['price_per_kg']) : '• দাম: পরিমাণ অনুযায়ী কোটেশন দেওয়া হবে';
            foreach ($w['unit_prices'] as $u) {
                $lines[] = '• প্রতি '.$u['unit_label'].' ('.Product::formatQty($u['kg']).' কেজি): '.self::money($u['price']);
            }
            if ($w['moq']) { $lines[] = '• সর্বনিম্ন অর্ডার: '.$w['moq']; }
            if ($w['delivery_time']) { $lines[] = '• ডেলিভারি: '.$w['delivery_time']; }
            if ($w['payment_terms']) { $lines[] = '• পেমেন্ট: '.$w['payment_terms']; }
            $lines[] = 'অর্ডার বা কোটেশনের জন্য পরিমাণ ও মোবাইল নম্বর দিন।';
        } else {
            $r = self::retail($p);
            if (! $r) {
                return "{$name} শুধু পাইকারি বিক্রি হয়।\n\n".self::text($p, 'wholesale');
            }
            $lines[] = "{$name} — খুচরা দাম";
            foreach ($r['packs'] as $pack) {
                $lines[] = '• '.$pack['label'].': '.self::money($pack['price']);
            }
            if (! $r['packs'] && $r['price_per_kg'] !== null) { $lines[] = '• প্রতি কেজি: '.self::money($r['price_per_kg']); }
            $lines[] = $p->isInStock() ? 'স্টকে আছে।' : 'এই মুহূর্তে স্টকে নেই।';
            $lines[] = 'অর্ডার করুন: '.route('products.show', $p->slug);
        }

        return implode("\n", $lines);
    }
}
