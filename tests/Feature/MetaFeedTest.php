<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Support\MetaPixel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaFeedTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $over = []): Product
    {
        return Product::create($over + [
            'name_bn' => 'পেস্তা বাদাম', 'slug' => 'pesta', 'stock' => 10, 'retail_price_1kg' => 3800,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false,
            'main_image' => 'products/images/pesta.jpg',
            'gallery_images' => ['products/images/pesta-2.jpg', 'https://cdn.example.com/p3.jpg'],
            'short_description' => 'খাঁটি <b>পেস্তা</b> & বাদাম',
        ]);
    }

    private function pack(Product $p, int $gram, string $label, float $price, array $over = []): ProductPrice
    {
        return ProductPrice::create($over + [
            'product_id' => $p->id, 'sell_type' => 'retail', 'label' => $label, 'quantity_gram' => $gram,
            'auto_price' => $price, 'manual_price' => $price, 'final_price' => $price,
            'is_manual_override' => true, 'is_active' => true,
        ]);
    }

    private function items(): array
    {
        $response = $this->get('/feeds/meta.xml');
        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'feed must be well-formed XML');
        $items = [];
        foreach ($xml->channel->item as $item) {
            $g = $item->children('http://base.google.com/ns/1.0');
            $items[(string) $g->id] = $g;
        }
        return $items;
    }

    public function test_item_ids_match_the_pixel_content_ids_and_packs_are_grouped(): void
    {
        $p = $this->product();
        $a = $this->pack($p, 100, '১০০ গ্রাম', 420);
        $b = $this->pack($p, 1000, '১ কেজি', 3800);

        $items = $this->items();

        $this->assertSame([MetaPixel::contentId($p->id, $a->id), MetaPixel::contentId($p->id, $b->id)], array_keys($items));
        $item = $items[MetaPixel::contentId($p->id, $a->id)];
        $this->assertSame((string) $p->id, (string) $item->item_group_id);
        $this->assertSame('420.00 BDT', (string) $item->price);
        $this->assertSame('in stock', (string) $item->availability);
        $this->assertSame(route('products.show', 'pesta'), (string) $item->link);
        $this->assertStringContainsString('storage/products/images/pesta.jpg', (string) $item->image_link);
        $this->assertSame('পেস্তা বাদাম — ১০০ গ্রাম', (string) $item->title);
        $this->assertSame('খাঁটি পেস্তা & বাদাম', (string) $item->description); // tags stripped, & escaped properly
        $this->assertCount(2, $item->additional_image_link);
    }

    public function test_left_out_when_inactive_wholesale_only_out_of_stock_or_unusable(): void
    {
        $inactive = $this->product(['slug' => 'inactive', 'is_active' => false]);
        $this->pack($inactive, 100, '১০০ গ্রাম', 100);

        $wholesale = $this->product(['slug' => 'whole', 'show_in_retail' => false, 'show_in_wholesale' => true]);
        $this->pack($wholesale, 100, '১০০ গ্রাম', 100);

        $noImage = $this->product(['slug' => 'noimg', 'main_image' => null]);
        $this->pack($noImage, 100, '১০০ গ্রাম', 100);

        $ok = $this->product(['slug' => 'ok', 'stock' => 0]);
        $this->pack($ok, 100, '১০০ গ্রাম', 100, ['is_active' => false]); // inactive pack
        $zero = $this->pack($ok, 250, '২৫০ গ্রাম', 0);                    // zero price
        $good = $this->pack($ok, 500, '৫০০ গ্রাম', 900);
        $wholesaleRow = $this->pack($ok, 1000, '১ কেজি', 1700, ['sell_type' => 'wholesale']);

        $items = $this->items();

        $this->assertSame([MetaPixel::contentId($ok->id, $good->id)], array_keys($items));
        $this->assertSame('out of stock', (string) $items[MetaPixel::contentId($ok->id, $good->id)]->availability);
    }

    public function test_compare_price_becomes_sale_price(): void
    {
        $p = $this->product();
        $pack = $this->pack($p, 100, '১০০ গ্রাম', 420, ['compare_price' => 500]);

        $item = $this->items()[MetaPixel::contentId($p->id, $pack->id)];

        $this->assertSame('500.00 BDT', (string) $item->price);
        $this->assertSame('420.00 BDT', (string) $item->sale_price);
    }

    public function test_vendor_products_are_left_out_when_the_platform_pixel_scope_is_own(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'vendor']);
        $shop = \App\Models\Vendor::create(['user_id' => $user->id, 'shop_name' => 'Shop', 'slug' => 'shop', 'owner_name' => 'Owner',
            'phone' => '01700000001', 'email' => 'shop@example.com', 'status' => 'approved', 'is_active' => true]);
        $vendor = $this->product(['slug' => 'vendor-item', 'vendor_id' => $shop->id, 'approval_status' => 'approved']);
        $this->pack($vendor, 100, '১০০ গ্রাম', 100);
        $own = $this->product();
        $ownPack = $this->pack($own, 100, '১০০ গ্রাম', 420);

        \App\Models\MarketingSetting::current()->update(['platform_pixel_scope' => 'own']);

        $this->assertSame([MetaPixel::contentId($own->id, $ownPack->id)], array_keys($this->items()));
    }

    public function test_feed_is_not_blocked_by_robots_and_empty_catalog_is_valid_xml(): void
    {
        $this->assertSame([], $this->items());
        $this->assertStringNotContainsString('/feeds', $this->get('/robots.txt')->getContent());
    }
}
