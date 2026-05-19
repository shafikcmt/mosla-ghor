<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $combos = [
            [
                'name'              => 'ট্রায়াল কম্বো',
                'slug'              => 'trial-combo',
                'short_description' => 'নতুন গ্রাহকদের জন্য পারফেক্ট শুরু',
                'badge_text'        => 'নতুনদের জন্য',
                'sell_price'        => 499,
                'sort_order'        => 1,
                'items'             => [
                    ['slug' => 'jira',      'quantity_gram' => 100],
                    ['slug' => 'daruchini', 'quantity_gram' => 100],
                    ['slug' => 'tejpata',   'quantity_gram' => 100],
                ],
            ],
            [
                'name'              => 'ফ্যামিলি কম্বো',
                'slug'              => 'family-combo',
                'short_description' => 'পরিবারের রান্নার জন্য সম্পূর্ণ প্যাক',
                'badge_text'        => 'জনপ্রিয়',
                'sell_price'        => 999,
                'sort_order'        => 2,
                'items'             => [
                    ['slug' => 'jira',      'quantity_gram' => 250],
                    ['slug' => 'daruchini', 'quantity_gram' => 250],
                    ['slug' => 'lobongo',   'quantity_gram' => 100],
                    ['slug' => 'tejpata',   'quantity_gram' => 250],
                ],
            ],
            [
                'name'              => 'প্রিমিয়াম কম্বো',
                'slug'              => 'premium-combo',
                'short_description' => 'উৎকৃষ্ট মশলার সংকলন',
                'badge_text'        => 'প্রিমিয়াম',
                'sell_price'        => 1499,
                'sort_order'        => 3,
                'items'             => [
                    ['slug' => 'jira',       'quantity_gram' => 500],
                    ['slug' => 'elach',      'quantity_gram' => 100],
                    ['slug' => 'daruchini',  'quantity_gram' => 250],
                    ['slug' => 'lobongo',    'quantity_gram' => 100],
                    ['slug' => 'golmorich',  'quantity_gram' => 100],
                ],
            ],
            [
                'name'              => 'মেগা কম্বো',
                'slug'              => 'mega-combo',
                'short_description' => 'সব মশলা একসাথে — সর্বোচ্চ সাশ্রয়',
                'badge_text'        => 'সেরা ডিল',
                'sell_price'        => 1999,
                'sort_order'        => 4,
                'items'             => [
                    ['slug' => 'jira',       'quantity_gram' => 500],
                    ['slug' => 'elach',      'quantity_gram' => 100],
                    ['slug' => 'daruchini',  'quantity_gram' => 500],
                    ['slug' => 'lobongo',    'quantity_gram' => 100],
                    ['slug' => 'golmorich',  'quantity_gram' => 250],
                    ['slug' => 'tejpata',    'quantity_gram' => 250],
                    ['slug' => 'kalo-elach', 'quantity_gram' => 100],
                ],
            ],
        ];

        foreach ($combos as $comboData) {
            $items = $comboData['items'];
            unset($comboData['items']);

            $combo = Combo::firstOrCreate(
                ['slug' => $comboData['slug']],
                array_merge($comboData, ['is_active' => true])
            );

            if ($combo->wasRecentlyCreated) {
                $newItems = [];
                foreach ($items as $itemData) {
                    $product = Product::where('slug', $itemData['slug'])->first();
                    if (! $product) {
                        continue;
                    }

                    $price = ProductPrice::where('product_id', $product->id)
                        ->where('quantity_gram', $itemData['quantity_gram'])
                        ->where('is_active', true)
                        ->first();

                    if (! $price) {
                        continue;
                    }

                    $newItems[] = [
                        'product_id'    => $product->id,
                        'quantity_gram' => $itemData['quantity_gram'],
                        'unit_price'    => (float) $price->final_price,
                        'line_total'    => (float) $price->final_price,
                    ];
                }

                if (! empty($newItems)) {
                    $combo->items()->createMany($newItems);
                }
            }
        }
    }
}
