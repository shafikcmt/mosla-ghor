<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;

class MetaUserData
{
    /** Only original checkout context; no names guessed from an unsplit full name. */
    public static function forPurchase(Order $order, Request $request): array
    {
        $data = [];
        $email = trim(mb_strtolower((string) ($request->input('customer_email') ?: $request->user()?->email)));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['em'] = [hash('sha256', $email)];
        }
        if (Phone::isValidBd($order->mobile_number)) {
            $data['ph'] = [hash('sha256', Phone::toWa($order->mobile_number))];
        }
        // This checkout validates the Bangladesh administrative hierarchy.
        if ($order->bd_division_id && $order->bd_district_id && $order->bd_upazila_id) {
            $data['country'] = [hash('sha256', 'bd')];
        }
        if ($request->user()?->role === 'customer') {
            $data['external_id'] = [hash('sha256', 'moslamart:user:'.$request->user()->id)];
        }
        if (filter_var($request->ip(), FILTER_VALIDATE_IP)) {
            $data['client_ip_address'] = $request->ip();
        }
        $agent = $request->userAgent();
        if (is_string($agent) && $agent !== '' && strlen($agent) <= 1024 && ! preg_match('/[\x00-\x1f\x7f]/', $agent)) {
            $data['client_user_agent'] = $agent;
        }
        foreach (['fbp', 'fbc'] as $key) {
            $value = $request->cookie('_'.$key);
            $pattern = $key === 'fbp' ? '/^fb\.\d+\.\d{13}\.\d+$/' : '/^fb\.\d+\.\d{13}\.[A-Za-z0-9_-]+$/';
            if (is_string($value) && strlen($value) <= 255 && preg_match($pattern, $value)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
