<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Services\MailConfigurator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailSettingController extends Controller
{
    public function edit()
    {
        $settings = MailSetting::current();

        return view('admin.settings.mail', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = MailSetting::current();

        // Credentials (password) are only touched when the admin explicitly opts
        // in — blocks browser autofill from silently overwriting the SMTP password.
        $replace = $request->boolean('replace_password');

        $rules = [
            'driver'       => 'required|in:smtp,log',
            'host'         => 'nullable|string|max:255',
            'port'         => 'nullable|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'encryption'   => 'nullable|in:tls,ssl',
            'from_address' => 'nullable|email|max:255',
            'from_name'    => 'nullable|string|max:255',
            'is_enabled'   => 'nullable|boolean',
        ];

        if ($replace) {
            $rules['password'] = ['nullable', 'string', 'max:255'];
        }

        $data = $request->validate($rules, [
            'from_address.email' => 'সঠিক প্রেরক ইমেইল দিন।',
            'port.integer'       => 'পোর্ট একটি সংখ্যা হতে হবে।',
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['encryption'] = $data['encryption'] ?? null;

        // Leave the stored password untouched unless the admin opted in AND typed one.
        if (! $replace || blank($request->input('password'))) {
            unset($data['password']);
        }

        $settings->fill($data)->save();

        return redirect()->route('admin.mail-settings.edit')
            ->with('success', 'মেইল সেটিং সংরক্ষণ হয়েছে।');
    }

    /**
     * Send a real test email to confirm the SMTP config works. Applies the
     * just-saved DB settings first, then reports success/failure inline.
     * The password is never written to logs.
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ], [
            'test_email.required' => 'টেস্ট ইমেইল ঠিকানা দিন।',
            'test_email.email'    => 'সঠিক ইমেইল দিন।',
        ]);

        // Ensure the latest DB settings are active for this request.
        MailConfigurator::apply();

        try {
            Mail::raw(
                "এটি একটি টেস্ট ইমেইল — MoslaMart মেইল সেটিং সঠিকভাবে কাজ করছে।\n\nThis is a test email from MoslaMart. If you received this, SMTP is configured correctly.",
                function ($message) use ($validated) {
                    $message->to($validated['test_email'])
                            ->subject('MoslaMart — টেস্ট ইমেইল / Test Email');
                }
            );
        } catch (\Throwable $e) {
            // Log the failure WITHOUT any credentials.
            Log::error('Mail test failed', [
                'to'    => $validated['test_email'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.mail-settings.edit')
                ->with('error', 'টেস্ট ইমেইল পাঠানো যায়নি: ' . $e->getMessage());
        }

        $driver = config('mail.default');
        $note = $driver === 'log'
            ? ' (বর্তমানে log ড্রাইভার — ইমেইল storage/logs এ লেখা হয়েছে, বাস্তবে পাঠানো হয়নি। SMTP চালু করুন।)'
            : '';

        return redirect()->route('admin.mail-settings.edit')
            ->with('success', "টেস্ট ইমেইল {$validated['test_email']} এ পাঠানো হয়েছে।" . $note);
    }
}
