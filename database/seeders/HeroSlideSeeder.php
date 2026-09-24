<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Optional development samples. Intentionally not registered in DatabaseSeeder. */
class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo Hero Slides must not be seeded in production.');
        }

        $image = 'images/product-placeholder.svg';
        if (! is_file(public_path($image))) {
            throw new \RuntimeException('The local demo image fallback is missing.');
        }

        $catalogue = '/#products';
        $wholesale = '/?mode=wholesale#products';
        $enquiry = route('wholesale.enquiry-bag', [], false);
        $productUrl = function (string $slug) use ($catalogue): string {
            $product = Product::active()->where('slug', $slug)->first();

            return $product ? route('products.show', $product->slug, false) : $catalogue;
        };

        $slides = [
            ['title'=>'[ডেমো] প্রতিদিনের রান্নায় মসলা', 'eyebrow'=>'প্রতিদিনের রান্নার জন্য',
                'subtitle'=>'জিরা, এলাচ, দারুচিনি ও গোলমরিচসহ প্রয়োজনীয় মসলা বেছে নিন।',
                'primary_label'=>'পণ্য দেখুন', 'primary_url'=>$catalogue,
                'secondary_label'=>'পাইকারি দেখুন', 'secondary_url'=>$wholesale],
            ['title'=>'[ডেমো] ব্যবসার জন্য বাল্ক অর্ডার', 'eyebrow'=>'ব্যবসার জন্য পাইকারি মসলা',
                'subtitle'=>'দোকান ও রেস্টুরেন্টের জন্য MOQ অনুযায়ী পণ্য দেখুন এবং পাইকারি দর জানতে enquiry করুন।',
                'primary_label'=>'পাইকারি পণ্য দেখুন', 'primary_url'=>$wholesale,
                'secondary_label'=>'Enquiry করুন', 'secondary_url'=>$enquiry],
            ['title'=>'[ডেমো] রান্নায় এলাচের সুবাস', 'eyebrow'=>'এলাচ বেছে নিন',
                'subtitle'=>'রান্নার প্রয়োজন অনুযায়ী এলাচের পণ্য ও উপলব্ধ প্যাকের তথ্য দেখুন।',
                'primary_label'=>'এলাচ দেখুন', 'primary_url'=>$productUrl('elach'),
                'secondary_label'=>'পাইকারি দেখুন', 'secondary_url'=>$wholesale],
            ['title'=>'[ডেমো] জিরা, খুচরা ও পাইকারি', 'eyebrow'=>'রান্নাঘরের প্রয়োজনীয় মসলা',
                'subtitle'=>'ছোট প্যাক থেকে বাল্ক প্রয়োজন—MoslaMart-এ জিরার পণ্য ও অর্ডারের বিকল্প দেখুন।',
                'primary_label'=>'জিরা দেখুন', 'primary_url'=>$productUrl('jira'),
                'secondary_label'=>'সব পণ্য', 'secondary_url'=>$catalogue],
            ['title'=>'[ডেমো] আপনার প্রয়োজন অনুযায়ী মসলা', 'eyebrow'=>'খুচরা ও পাইকারি মার্কেটপ্লেস',
                'subtitle'=>'বাসার রান্না ও ব্যবসার বাল্ক প্রয়োজনের জন্য এক জায়গায় খুচরা ও পাইকারি পণ্য দেখুন।',
                'primary_label'=>'এখনই কিনুন', 'primary_url'=>$catalogue,
                'secondary_label'=>'ব্যবসায়িক enquiry', 'secondary_url'=>$enquiry],
        ];

        DB::transaction(function () use ($slides, $image) {
            foreach ($slides as $index => $slide) {
                // Insert only: never reset admin edits, active state, artwork or ordering.
                HeroSlide::firstOrCreate(['title'=>$slide['title']], $slide + [
                    'image_path'=>$image, 'is_active'=>true, 'sort_order'=>$index + 1,
                ]);
            }
        });
    }
}
