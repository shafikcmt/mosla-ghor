<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $products = [
            [
                'name_bn'             => 'জিরা',
                'name_en'             => 'Cumin',
                'slug'                => 'jira',
                'short_description'   => 'সুগন্ধি জিরা — রান্নায় অনন্য স্বাদ ও সুবাস দেয়।',
                'retail_price_1kg'    => 380.00,
                'wholesale_price_1kg' => 320.00,
                'stock'               => 80,
                'sort_order'          => 1,
            ],
            [
                'name_bn'             => 'এলাচ',
                'name_en'             => 'Cardamom',
                'slug'                => 'elach',
                'short_description'   => 'সুগন্ধি এলাচ — মিষ্টি, পোলাও ও চায়ে অপরিহার্য।',
                'retail_price_1kg'    => 2800.00,
                'wholesale_price_1kg' => 2400.00,
                'stock'               => 25,
                'sort_order'          => 2,
            ],
            [
                'name_bn'             => 'দারুচিনি',
                'name_en'             => 'Cinnamon',
                'slug'                => 'daruchini',
                'short_description'   => 'খাঁটি দারুচিনি — গরম মশলার প্রধান উপাদান।',
                'retail_price_1kg'    => 650.00,
                'wholesale_price_1kg' => 560.00,
                'stock'               => 60,
                'sort_order'          => 3,
            ],
            [
                'name_bn'             => 'লবঙ্গ',
                'name_en'             => 'Clove',
                'slug'                => 'lobongo',
                'short_description'   => 'সুগন্ধি লবঙ্গ — রান্না ও ঔষধি গুণে ভরপুর।',
                'retail_price_1kg'    => 1400.00,
                'wholesale_price_1kg' => 1200.00,
                'stock'               => 40,
                'sort_order'          => 4,
            ],
            [
                'name_bn'             => 'গোলমরিচ',
                'name_en'             => 'Black Pepper',
                'slug'                => 'golmorich',
                'short_description'   => 'খাঁটি গোলমরিচ — রান্না ও সস তৈরিতে অপরিহার্য।',
                'retail_price_1kg'    => 850.00,
                'wholesale_price_1kg' => 730.00,
                'stock'               => 55,
                'sort_order'          => 5,
            ],
            [
                'name_bn'             => 'তেজপাতা',
                'name_en'             => 'Bay Leaf',
                'slug'                => 'tejpata',
                'short_description'   => 'তেজপাতা — বিরিয়ানি ও মাংসের রান্নায় অনন্য সুবাস।',
                'retail_price_1kg'    => 450.00,
                'wholesale_price_1kg' => 380.00,
                'stock'               => 70,
                'sort_order'          => 6,
            ],
            [
                'name_bn'             => 'কালো এলাচ',
                'name_en'             => 'Black Cardamom',
                'slug'                => 'kalo-elach',
                'short_description'   => 'কালো এলাচ — গরম মশলা ও মাংসের রান্নায় গভীর স্বাদ।',
                'retail_price_1kg'    => 1600.00,
                'wholesale_price_1kg' => 1380.00,
                'stock'               => 35,
                'sort_order'          => 7,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
            if ($product->wasRecentlyCreated) {
                $product->syncPrices();
            }
        }
    }
}
