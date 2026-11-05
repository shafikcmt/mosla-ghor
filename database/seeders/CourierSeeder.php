<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $couriers = [
            [
                'name'       => 'Steadfast',
                'slug'       => 'steadfast',
                'status'     => 'active',
                'api_enabled'=> false,
                'base_url'   => 'https://portal.steadfast.com.bd/api/v1',
                'is_default' => true,
                'notes'      => 'Default courier. API: Api-Key + Secret-Key headers.',
            ],
            [
                'name'       => 'Pathao',
                'slug'       => 'pathao',
                'status'     => 'active',
                'api_enabled'=> false,
                'base_url'   => 'https://api-hermes.pathao.com',
                'is_default' => false,
                'notes'      => 'Pathao Merchant API (OAuth: Client ID/Secret + login email/password + Store ID).',
            ],
            [
                'name'       => 'Sundarban',
                'slug'       => 'sundarban',
                'status'     => 'active',
                'api_enabled'=> false,
                'base_url'   => null,
                'is_default' => false,
                'notes'      => 'Manual booking only. Good for remote/rural areas.',
            ],
        ];

        foreach ($couriers as $data) {
            Courier::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
