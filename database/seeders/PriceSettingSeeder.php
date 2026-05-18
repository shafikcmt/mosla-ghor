<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceSettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::table('price_settings')->truncate();

        DB::table('price_settings')->insert([
            'markup_25g'             => 20.00,
            'markup_50g'             => 15.00,
            'markup_100g'            => 10.00,
            'markup_250g'            => 5.00,
            'markup_500g'            => 0.00,
            'markup_1000g'           => 0.00,
            'rounding_type'          => 'nearest_5',
            'default_packaging_cost' => 20.00,
            'currency_symbol'        => '৳',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }
}
