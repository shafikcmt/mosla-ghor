<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name'          => 'মসলা ঘর',
            'hero_badge_text'    => 'ঈদ স্পেশাল কালেকশন',
            'hero_title'         => 'খাঁটি মশলার',
            'hero_subtitle'      => 'প্রকৃতির সেরা উপাদান থেকে তৈরি, ভেজালমুক্ত খাঁটি মশলা — আপনার রান্নাকে করে তুলুন অতুলনীয় ও সুস্বাদু।',
            'primary_cta_text'   => 'পণ্য দেখুন',
            'secondary_cta_text' => 'কম্বো দেখুন',
            'hero_image_url'     => '',
            'whatsapp_number'    => '01700000000',
            'messenger_url'      => '',
            'facebook_page_url'  => '',
            'footer_text'        => 'সমস্ত অধিকার সংরক্ষিত।',
        ];

        foreach ($defaults as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
