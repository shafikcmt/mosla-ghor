<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Support\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaintenanceSettingController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validateWithBag('maintenance', [
            'maintenance_enabled'       => 'nullable|boolean',
            'maintenance_mode'          => 'required|in:full,banner',
            'maintenance_block_orders'  => 'nullable|boolean',
            'maintenance_block_vendors' => 'nullable|boolean',
            'maintenance_title'         => 'nullable|string|max:120',
            'maintenance_message'       => 'nullable|string|max:1000',
            'maintenance_until'         => 'nullable|date_format:Y-m-d\TH:i',
            'maintenance_contact'       => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s-]{6,}$/'],
            'maintenance_allowed_ips'   => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                foreach (array_filter(preg_split('/[\s,]+/', (string) $value)) as $ip) {
                    if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                        $fail("IP ঠিকানা সঠিক নয়: {$ip}");
                        return;
                    }
                }
            }],
        ], [
            'maintenance_mode.required'          => 'মোড বেছে নিন।',
            'maintenance_mode.in'                => 'মোড সঠিক নয়।',
            'maintenance_title.max'              => 'শিরোনাম সর্বোচ্চ ১২০ অক্ষর হতে পারবে।',
            'maintenance_message.max'            => 'বার্তা সর্বোচ্চ ১০০০ অক্ষর হতে পারবে।',
            'maintenance_until.date_format'      => 'শেষ সময় সঠিক তারিখ ও সময় হতে হবে।',
            'maintenance_contact.regex'          => 'যোগাযোগ নম্বরে শুধু সংখ্যা, + বা - দিন।',
            'maintenance_contact.max'            => 'যোগাযোগ নম্বর সর্বোচ্চ ৩০ অক্ষর।',
            'maintenance_allowed_ips.max'        => 'IP তালিকা অনেক বড়।',
        ]);

        $values = [
            'maintenance_enabled'       => $request->boolean('maintenance_enabled') ? '1' : '0',
            'maintenance_mode'          => $data['maintenance_mode'],
            'maintenance_block_orders'  => $request->boolean('maintenance_block_orders') ? '1' : '0',
            'maintenance_block_vendors' => $request->boolean('maintenance_block_vendors') ? '1' : '0',
            'maintenance_title'         => trim($data['maintenance_title'] ?? ''),
            'maintenance_message'       => trim($data['maintenance_message'] ?? ''),
            // datetime-local value is Bangladesh time; stored as 'Y-m-d H:i' (see Maintenance::until()).
            'maintenance_until'         => ! empty($data['maintenance_until']) ? str_replace('T', ' ', $data['maintenance_until']) : '',
            'maintenance_contact'       => trim($data['maintenance_contact'] ?? ''),
            'maintenance_allowed_ips'   => implode(', ', array_filter(preg_split('/[\s,]+/', (string) ($data['maintenance_allowed_ips'] ?? '')))),
        ];

        $before = Maintenance::enabled();
        foreach ($values as $key => $value) {
            // updateOrCreate fires `saved`, which drops the per-request settings cache.
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($before !== ($values['maintenance_enabled'] === '1')) {
            Log::info('Maintenance mode '.($values['maintenance_enabled'] === '1' ? 'enabled' : 'disabled'), [
                'user_id' => $request->user()?->id, 'mode' => $values['maintenance_mode'],
            ]);
        }

        return redirect()->to(route('admin.website-settings.index').'#maintenance')
            ->with('success', $values['maintenance_enabled'] === '1'
                ? 'মেইনটেন্যান্স সেটিং সংরক্ষণ হয়েছে — মেইনটেন্যান্স চালু আছে।'
                : 'মেইনটেন্যান্স সেটিং সংরক্ষণ হয়েছে — ওয়েবসাইট স্বাভাবিকভাবে চলছে।');
    }

    /** Shows the admin the exact page customers get in full mode (200, not 503). */
    public function preview()
    {
        return response()->view('maintenance.notice', ['preview' => true]);
    }
}
