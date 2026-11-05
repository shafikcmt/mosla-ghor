<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\WebsiteSetting;
use App\Support\MetaPixel;
use App\Support\ProductMedia;
use Illuminate\Support\Str;

/**
 * Meta (Facebook / Instagram) product catalog feed — RSS 2.0 with the g: namespace.
 *
 * One item per ACTIVE retail pack. The item id is MetaPixel::contentId(product, pack)
 * ("{product_id}-{product_prices.id}"), exactly what the Pixel / Conversions API send as
 * content_ids, so catalog ads can match shoppers' events to catalog items. Packs of one
 * product share item_group_id = product id so Meta groups them as variants.
 *
 * Wholesale-only products (price hidden, enquiry only), inactive products, packs without a
 * real photo, and — when the platform pixel scope is "own" — vendor products are left out.
 */
class MetaFeedController extends Controller
{
    private const MAX_EXTRA_IMAGES = 10;

    public function show()
    {
        $site = WebsiteSetting::siteName();

        $products = Product::active()
            ->where('show_in_retail', true)
            ->with(['category', 'prices' => fn ($q) => $q->where('is_active', true)->where('sell_type', 'retail')->with('variant')])
            ->get();

        $items = '';
        foreach ($products as $product) {
            if ($product->isWholesale() || ! MetaPixel::platformAllowsProduct($product->vendor_id)) {
                continue;
            }
            $image = ProductMedia::url($product->main_image);
            if (! $image) {
                continue; // Meta rejects items without a photo (the SVG placeholder is not accepted)
            }
            foreach ($product->prices as $price) {
                if ((float) $price->final_price <= 0) {
                    continue;
                }
                $items .= $this->item($product, $price, $image, $site);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>'."\n"
            .'<title>'.$this->e($site).'</title>'."\n"
            .'<link>'.$this->e(url('/')).'</link>'."\n"
            .'<description>'.$this->e($site.' product catalog').'</description>'."\n"
            .$items
            .'</channel></rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    private function item(Product $product, ProductPrice $price, string $image, string $site): string
    {
        $variant = $price->variant?->name;
        $title = trim($product->display_name.($variant ? ' ('.$variant.')' : '').' — '.$price->label);
        $description = trim(strip_tags((string) ($product->short_description ?: $product->description)));
        $description = Str::limit($description !== '' ? $description : $product->display_name, 4900, '');

        $fields = [
            'g:id' => MetaPixel::contentId($product->id, $price->id),
            'g:item_group_id' => (string) $product->id,
            'g:title' => Str::limit($title, 195, ''),
            'g:description' => $description,
            'g:availability' => $product->isInStock() ? 'in stock' : 'out of stock',
            'g:condition' => 'new',
            'g:price' => number_format((float) $price->final_price, 2, '.', '').' BDT',
            'g:link' => route('products.show', $product->slug),
            'g:image_link' => $image,
            'g:brand' => trim((string) $product->brand) ?: $site,
            'g:product_type' => $product->category?->name_bn ?: null,
        ];

        $compare = (float) $price->compare_price;
        if ($compare > (float) $price->final_price) {
            // Meta: "price" is the regular price, "sale_price" the discounted one.
            $fields['g:price'] = number_format($compare, 2, '.', '').' BDT';
            $fields['g:sale_price'] = number_format((float) $price->final_price, 2, '.', '').' BDT';
        }

        $out = "<item>\n";
        foreach ($fields as $tag => $value) {
            if ($value !== null && $value !== '') {
                $out .= "<{$tag}>".$this->e((string) $value)."</{$tag}>\n";
            }
        }
        $extra = 0;
        foreach ((array) $product->gallery_images as $path) {
            $url = is_string($path) ? ProductMedia::url($path) : null;
            if ($url && $url !== $image && $extra < self::MAX_EXTRA_IMAGES) {
                $out .= '<g:additional_image_link>'.$this->e($url)."</g:additional_image_link>\n";
                $extra++;
            }
        }
        return $out."</item>\n";
    }

    private function e(string $value): string
    {
        // Strip characters that are invalid in XML 1.0, then escape.
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
