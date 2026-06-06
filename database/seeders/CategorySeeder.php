<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            ['মসলা', 'Spices', ['এলাচ' => 'Cardamom', 'জিরা' => 'Cumin', 'গোলমরিচ' => 'Black Pepper']],
            ['ড্রাই ফ্রুটস', 'Dry Fruits', ['বাদাম' => 'Almond', 'কাজু বাদাম' => 'Cashew', 'কিসমিস' => 'Raisin']],
        ];

        $sort = 0;
        foreach ($tree as [$nameBn, $nameEn, $children]) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($nameEn)],
                ['name_bn' => $nameBn, 'name_en' => $nameEn, 'sort_order' => $sort++, 'is_active' => true],
            );

            $childSort = 0;
            foreach ($children as $childBn => $childEn) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($childEn)],
                    [
                        'name_bn'    => $childBn,
                        'name_en'    => $childEn,
                        'parent_id'  => $parent->id,
                        'sort_order' => $childSort++,
                        'is_active'  => true,
                    ],
                );
            }
        }
    }
}
