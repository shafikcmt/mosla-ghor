<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'zone_name'   => 'ঢাকা সিটি',
                'zone_type'   => 'inside_dhaka',
                'delivery_charge' => 80,
                'sort_order'  => 1,
                'locations'   => [
                    ['location_name' => 'মিরপুর',      'keywords' => 'mirpur, mirpur-10, mirpur 11, mirpur 12'],
                    ['location_name' => 'উত্তরা',       'keywords' => 'uttara, uttara sector'],
                    ['location_name' => 'ধানমন্ডি',     'keywords' => 'dhanmondi, dhanmandi'],
                    ['location_name' => 'মোহাম্মদপুর', 'keywords' => 'mohammadpur, mohammadpur, mohammedpur'],
                    ['location_name' => 'বাড্ডা',       'keywords' => 'badda, rampura'],
                    ['location_name' => 'গুলশান',       'keywords' => 'gulshan, banani, niketan'],
                ],
            ],
            [
                'zone_name'   => 'গাজীপুর',
                'zone_type'   => 'outside_dhaka',
                'delivery_charge' => 120,
                'sort_order'  => 2,
                'locations'   => [
                    ['location_name' => 'টঙ্গী',        'keywords' => 'tongi, tongee'],
                    ['location_name' => 'বোর্ড বাজার', 'keywords' => 'board bazar, boardbazar'],
                    ['location_name' => 'চৌরাস্তা',    'keywords' => 'chowrasta, gazipur chowrasta'],
                ],
            ],
            [
                'zone_name'   => 'টাঙ্গাইল',
                'zone_type'   => 'outside_dhaka',
                'delivery_charge' => 130,
                'sort_order'  => 3,
                'locations'   => [
                    ['location_name' => 'মির্জাপুর',    'keywords' => 'mirzapur'],
                    ['location_name' => 'টাঙ্গাইল সদর', 'keywords' => 'tangail sadar, tangail'],
                    ['location_name' => 'সখিপুর',       'keywords' => 'sakhipur'],
                ],
            ],
            [
                'zone_name'   => 'ঢাকার বাইরে',
                'zone_type'   => 'outside_dhaka',
                'delivery_charge' => 150,
                'sort_order'  => 4,
                'locations'   => [
                    ['location_name' => 'অন্যান্য জেলা',   'keywords' => 'other district, others'],
                    ['location_name' => 'কুরিয়ার ডেলিভারি', 'keywords' => 'courier, courier delivery'],
                ],
            ],
        ];

        foreach ($zones as $zoneData) {
            $locations = $zoneData['locations'];
            unset($zoneData['locations']);

            $zone = DeliveryZone::firstOrCreate(
                ['zone_name' => $zoneData['zone_name']],
                array_merge($zoneData, ['is_active' => true])
            );

            foreach ($locations as $loc) {
                $zone->locations()->firstOrCreate(
                    ['location_name' => $loc['location_name']],
                    array_merge($loc, ['is_active' => true])
                );
            }
        }
    }
}
