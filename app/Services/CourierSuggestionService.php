<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\DeliveryRate;
use App\Models\DeliveryZone;

class CourierSuggestionService
{
    public function suggest(DeliveryZone $zone, int $weightGram): ?Courier
    {
        $zoneType = $zone->zone_type;

        // Small parcel under 500g → prefer Pathao if active and has a rate
        if ($weightGram <= 500) {
            $pathao = Courier::where('slug', 'pathao')->where('status', 'active')->first();
            if ($pathao && DeliveryRate::findBestRate($zone, $weightGram, $pathao->id)) {
                return $pathao;
            }
        }

        // Inside Dhaka → Steadfast
        if (in_array($zoneType, ['inside_dhaka', 'dhaka_sub_area'])) {
            $steadfast = Courier::where('slug', 'steadfast')->where('status', 'active')->first();
            if ($steadfast) return $steadfast;
        }

        // Outside Dhaka → lowest active rate between Steadfast and Pathao
        if (in_array($zoneType, ['outside_dhaka', 'upazila'])) {
            $bestRate = DeliveryRate::where('is_active', true)
                ->where('min_weight', '<=', $weightGram)
                ->where('max_weight', '>=', $weightGram)
                ->where(function ($q) use ($zone) {
                    $q->where('delivery_zone_id', $zone->id)
                      ->orWhere('zone_type', $zone->zone_type);
                })
                ->orderBy('courier_cost')
                ->first();

            if ($bestRate) {
                return $bestRate->courier;
            }

            return Courier::where('slug', 'steadfast')->where('status', 'active')->first();
        }

        // Remote / union → Sundarban backup
        if ($zoneType === 'union') {
            $sundarban = Courier::where('slug', 'sundarban')->where('status', 'active')->first();
            if ($sundarban) return $sundarban;
        }

        // Fallback: default courier
        return Courier::default();
    }
}
