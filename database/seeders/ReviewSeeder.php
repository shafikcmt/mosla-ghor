<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'customer_name'     => 'রাহেলা বেগম',
                'customer_location' => 'মিরপুর, ঢাকা',
                'rating'            => 5,
                'review_text'       => 'অসাধারণ মসলা! এলাচ আর দারুচিনির গন্ধ রান্নাঘর ভরিয়ে দেয়। আগে বাজার থেকে কিনতাম কিন্তু এত ভালো মসলা পাইনি কখনো। ডেলিভারিও খুব দ্রুত হয়েছে।',
                'sort_order'        => 1,
            ],
            [
                'customer_name'     => 'মো. কামরুজ্জামান',
                'customer_location' => 'চট্টগ্রাম',
                'rating'            => 5,
                'review_text'       => 'ট্রায়াল কম্বো দিয়ে শুরু করেছিলাম। দাম অনুযায়ী মান অনেক ভালো। গোটা মসলা বাড়িতে ভাঙলে যে সুগন্ধ পাই সেটা বাজারের গুঁড়া মসলায় নেই। এরপর ফ্যামিলি কম্বো নিয়েছি।',
                'sort_order'        => 2,
            ],
            [
                'customer_name'     => 'সুমাইয়া আক্তার',
                'customer_location' => 'সিলেট',
                'rating'            => 5,
                'review_text'       => 'প্যাকেজিং অনেক সুন্দর এবং মসলা একদম তাজা। বিরিয়ানিতে দিয়েছি, স্বাদ সম্পূর্ণ আলাদা হয়ে গেছে। পরিবারের সবাই প্রশংসা করেছে। অবশ্যই আবার অর্ডার করবো।',
                'sort_order'        => 3,
            ],
            [
                'customer_name'     => 'নাজমুল হোসেন',
                'customer_location' => 'রাজশাহী',
                'rating'            => 4,
                'review_text'       => 'জিরা এবং গোলমরিচ অসাধারণ। একটু বেশি পরিমাণের প্যাক থাকলে ভালো হতো। তবে মান নিয়ে কোনো অভিযোগ নেই, ১০০% খাঁটি মনে হচ্ছে। কাস্টমার সার্ভিসও ভালো।',
                'sort_order'        => 4,
            ],
            [
                'customer_name'     => 'ফারহানা ইসলাম',
                'customer_location' => 'খুলনা',
                'rating'            => 5,
                'review_text'       => 'প্রিমিয়াম কম্বো অর্ডার করেছিলাম। সত্যিই প্রিমিয়াম মানের! কালো এলাচের সুগন্ধ অবিশ্বাস্য। দাম একটু বেশি মনে হয়েছিল কিন্তু মান দেখে মনে হলো পুরো দাম উঠে গেছে।',
                'sort_order'        => 5,
            ],
            [
                'customer_name'     => 'আবদুল করিম',
                'customer_location' => 'বগুড়া',
                'rating'            => 5,
                'review_text'       => 'বাড়িতে রান্না করার সময় এখন মশলার গন্ধেই বোঝা যায় কতটা খাঁটি। লবঙ্গ আর তেজপাতা দিয়ে গরুর মাংস রান্না করলে যে স্বাদ হয় সেটা আগে কখনো পাইনি। ধন্যবাদ মসলা ঘরকে।',
                'sort_order'        => 6,
            ],
        ];

        foreach ($reviews as $data) {
            Review::updateOrCreate(
                ['customer_name' => $data['customer_name'], 'customer_location' => $data['customer_location'] ?? null],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
