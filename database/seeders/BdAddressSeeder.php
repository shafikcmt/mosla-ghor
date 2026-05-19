<?php

namespace Database\Seeders;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BdAddressSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        // ── Divisions ────────────────────────────────────────────────────────────
        $divisions = json_decode(File::get(database_path('date/bd-address/divisions.json')), true);
        $divisionRows = array_map(fn($d) => [
            'source_id'  => (string) $d['id'],
            'name'       => $d['name'],
            'bn_name'    => $d['bn_name'],
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $divisions);

        BdDivision::upsert($divisionRows, ['source_id'], ['name', 'bn_name', 'updated_at']);

        $divMap = BdDivision::pluck('id', 'source_id')->all();

        // ── Districts ────────────────────────────────────────────────────────────
        $districts = json_decode(File::get(database_path('date/bd-address/districts.json')), true);
        $districtRows = array_map(fn($d) => [
            'source_id'   => (string) $d['id'],
            'division_id' => $divMap[(string) $d['division_id']] ?? null,
            'name'        => $d['name'],
            'bn_name'     => $d['bn_name'],
            'lat'         => $d['lat'] ?? null,
            'lon'         => $d['lon'] ?? null,
            'is_active'   => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ], $districts);

        BdDistrict::upsert($districtRows, ['source_id'], ['division_id', 'name', 'bn_name', 'lat', 'lon', 'updated_at']);

        $distMap = BdDistrict::pluck('id', 'source_id')->all();

        // ── Upazilas ─────────────────────────────────────────────────────────────
        $upazilas = json_decode(File::get(database_path('date/bd-address/upazilas.json')), true);
        $upazilaRows = array_map(fn($u) => [
            'source_id'   => (string) $u['id'],
            'district_id' => $distMap[(string) $u['district_id']] ?? null,
            'name'        => $u['name'],
            'bn_name'     => $u['bn_name'],
            'is_active'   => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ], $upazilas);

        BdUpazila::upsert($upazilaRows, ['source_id'], ['district_id', 'name', 'bn_name', 'updated_at']);

        $upazilaMap = BdUpazila::pluck('id', 'source_id')->all();

        // ── Unions ───────────────────────────────────────────────────────────────
        // Note: JSON uses "upazilla_id" (double l) — normalize to "upazila_id" in DB
        $unions = json_decode(File::get(database_path('date/bd-address/unions.json')), true);
        $unionRows = array_map(fn($u) => [
            'source_id'  => (string) $u['id'],
            'upazila_id' => $upazilaMap[(string) $u['upazilla_id']] ?? null,
            'name'       => $u['name'],
            'bn_name'    => $u['bn_name'],
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $unions);

        foreach (array_chunk($unionRows, 500) as $chunk) {
            BdUnion::upsert($chunk, ['source_id'], ['upazila_id', 'name', 'bn_name', 'updated_at']);
        }

        $this->command->info('BD address data seeded: ' . count($divisions) . ' divisions, ' . count($districts) . ' districts, ' . count($upazilas) . ' upazilas, ' . count($unions) . ' unions.');
    }
}
