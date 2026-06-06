<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpThrottleException;
use App\Services\Otp\OtpUnavailableException;
use App\Support\AuthSettings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        if (Auth::check() && Auth::user()->isVendor()) {
            return redirect()->route('vendor.dashboard');
        }

        if (WebsiteSetting::get('vendor_registration_enabled', '0') !== '1') {
            return view('auth.unavailable', [
                'title'      => 'মার্চেন্ট রেজিস্ট্রেশন বন্ধ',
                'message'    => WebsiteSetting::get(
                    'vendor_registration_message',
                    'বর্তমানে মার্চেন্ট রেজিস্ট্রেশন চালু নেই। মার্চেন্ট হতে চাইলে অ্যাডমিনের সাথে যোগাযোগ করুন।'
                ),
                'contactUrl' => url('/#contact'),
            ]);
        }

        return view('vendor.auth.register');
    }

    public function register(Request $request)
    {
        // Phone-first: normalise before validating so uniqueness + storage use
        // the canonical 01XXXXXXXXX form everywhere.
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? trim((string) $request->input('phone'))]);

        $data = $request->validate([
            'shop_name'   => 'required|string|max:150',
            'owner_name'  => 'required|string|max:100',
            'phone'       => ['required', 'string', 'max:20', 'regex:/^01[3-9]\d{8}$/', 'unique:vendors,phone'],
            'email'       => 'nullable|email|max:150|unique:users,email|unique:vendors,email',
            'password'    => 'required|string|min:8|confirmed',
            'address'     => 'nullable|string|max:500',
            'business_type' => 'nullable|string|max:100',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kyc_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'shop_name.required'  => 'দোকানের নাম দিন।',
            'owner_name.required' => 'মালিকের নাম দিন।',
            'phone.required'      => 'ফোন নম্বর দিন।',
            'phone.regex'         => 'সঠিক মোবাইল নম্বর দিন (যেমন ০১XXXXXXXXX)।',
            'phone.unique'        => 'এই ফোন নম্বর দিয়ে আগেই রেজিস্ট্রেশন আছে।',
            'email.email'         => 'সঠিক ইমেইল দিন।',
            'email.unique'        => 'এই ইমেইল দিয়ে আগেই একটি অ্যাকাউন্ট আছে।',
            'password.required'   => 'পাসওয়ার্ড দিন।',
            'password.min'        => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।',
            'password.confirmed'  => 'পাসওয়ার্ড মিলছে না।',
        ]);

        // vendors.email is NOT-NULL + unique; mint a safe placeholder when blank
        // so phone-only merchants can still register (mirrors admin create).
        $email = ($data['email'] ?? null) ?: $this->placeholderEmail($data['phone']);

        $user = User::create([
            'name'     => $data['owner_name'],
            'email'    => $email,
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
            'role'     => 'vendor',
            'is_admin' => false,
        ]);

        $slug = Str::slug($data['shop_name']);
        $originalSlug = $slug;
        $i = 1;
        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = 'storage/' . $request->file('logo')->store('vendors/logos', 'public');
        }

        $kycPath = null;
        if ($request->hasFile('kyc_document')) {
            $kycPath = 'storage/' . $request->file('kyc_document')->store('vendors/kyc', 'public');
        }

        $autoApprove = filter_var(
            \App\Models\WebsiteSetting::get('vendor_auto_approve', '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        Vendor::create([
            'user_id'      => $user->id,
            'shop_name'    => $data['shop_name'],
            'slug'         => $slug,
            'owner_name'   => $data['owner_name'],
            'phone'        => $data['phone'],
            'email'        => $email,
            'address'      => $data['address'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'logo'         => $logoPath,
            'kyc_document' => $kycPath,
            'status'       => $autoApprove ? 'approved' : 'pending',
            'is_active'    => true,
        ]);

        Auth::login($user);

        return redirect()->route('vendor.dashboard')
            ->with('success', 'রেজিস্ট্রেশন সম্পন্ন হয়েছে। অ্যাডমিন অনুমোদনের পর আপনি পণ্য যোগ করতে পারবেন।');
    }

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->isVendor()) {
            return redirect()->route('vendor.dashboard');
        }

        if (WebsiteSetting::get('vendor_login_enabled', '1') !== '1') {
            return view('auth.unavailable', [
                'title'   => 'মার্চেন্ট লগইন বন্ধ',
                'message' => 'মার্চেন্ট লগইন বর্তমানে বন্ধ আছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।',
            ]);
        }

        return view('vendor.auth.login');
    }

    public function login(Request $request)
    {
        if (! AuthSettings::vendorPasswordLogin()) {
            return back()->withErrors(['identifier' => 'পাসওয়ার্ড দিয়ে লগইন এই মুহূর্তে বন্ধ আছে।'])->withInput();
        }

        $data = $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ], [
            'identifier.required' => 'ফোন নম্বর বা ইমেইল দিন।',
            'password.required'   => 'পাসওয়ার্ড দিন।',
        ]);

        $user = $this->findVendorUser($data['identifier']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['identifier' => 'ফোন/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    // ── OTP login ───────────────────────────────────────────────────────────
    // Passwordless merchant login mirroring the customer flow, gated by the
    // admin-controlled AuthSettings::vendorOtpLogin().

    public function showOtpRequest()
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('vendor.login');
        }

        if (Auth::check() && Auth::user()->isVendor()) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.auth.otp-request');
    }

    public function sendOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('vendor.login');
        }

        $data = $request->validate(
            ['identifier' => 'required|string|max:150'],
            ['identifier.required' => 'মোবাইল নম্বর বা ইমেইল দিন।']
        );

        $identifier = trim($data['identifier']);

        $channel = $this->resolveChannel($identifier);
        if (! $channel) {
            return back()->withErrors(['identifier' => 'এই পরিচয় দিয়ে OTP লগইন এই মুহূর্তে সম্ভব নয়।'])->withInput();
        }

        if (! $this->findVendorUser($identifier)) {
            return back()->withErrors(['identifier' => 'এই নম্বর/ইমেইল দিয়ে কোনো মার্চেন্ট অ্যাকাউন্ট পাওয়া যায়নি।'])->withInput();
        }

        $canonical = $this->canonical($identifier);
        $request->session()->put('vendor_otp_login', ['identifier' => $canonical, 'channel' => $channel]);

        try {
            $otp->issue($canonical, 'login', $channel, 'vendor', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (OtpThrottleException $e) {
            return redirect()->route('vendor.login.otp.verify')->with('error', $e->getMessage());
        } catch (OtpUnavailableException $e) {
            $request->session()->forget('vendor_otp_login');
            return back()->withErrors(['identifier' => $e->getMessage()])->withInput();
        }

        return redirect()->route('vendor.login.otp.verify')
            ->with('success', 'OTP পাঠানো হয়েছে: ' . $this->mask($canonical));
    }

    public function showOtpVerify(Request $request)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('vendor.login');
        }

        $session = $request->session()->get('vendor_otp_login');
        if (! $session) {
            return redirect()->route('vendor.login.otp');
        }

        return view('vendor.auth.otp-verify', ['masked' => $this->mask($session['identifier'])]);
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('vendor.login');
        }

        $session = $request->session()->get('vendor_otp_login');
        if (! $session) {
            return redirect()->route('vendor.login.otp');
        }

        $data = $request->validate(
            ['code' => 'required|string'],
            ['code.required' => 'OTP কোড দিন।']
        );

        if (! $otp->verify($session['identifier'], 'login', trim($data['code']))) {
            return back()->withErrors(['code' => 'OTP কোড সঠিক নয় বা মেয়াদ শেষ হয়ে গেছে।']);
        }

        $user = $this->findVendorUser($session['identifier']);
        if (! $user) {
            $request->session()->forget('vendor_otp_login');
            return redirect()->route('vendor.login')->withErrors(['identifier' => 'অ্যাকাউন্ট পাওয়া যায়নি।']);
        }

        Auth::login($user, true);
        $request->session()->forget('vendor_otp_login');
        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'))->with('success', 'সফলভাবে লগইন হয়েছে।');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('vendor.login');
        }

        $session = $request->session()->get('vendor_otp_login');
        if (! $session) {
            return redirect()->route('vendor.login.otp');
        }

        try {
            $otp->issue($session['identifier'], 'login', $session['channel'], 'vendor', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (OtpThrottleException | OtpUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'নতুন OTP পাঠানো হয়েছে।');
    }

    /** OTP login usable only when admin enabled it AND a channel exists. */
    private function otpLoginAvailable(): bool
    {
        return AuthSettings::vendorOtpLogin() && AuthSettings::enabledChannels() !== [];
    }

    /** Pick a delivery channel that fits the identifier and is admin-enabled. */
    private function resolveChannel(string $identifier): ?string
    {
        $enabled = AuthSettings::enabledChannels();

        if (str_contains($identifier, '@')) {
            return in_array('email', $enabled, true) ? 'email' : null;
        }

        foreach (['sms', 'whatsapp'] as $channel) {
            if (in_array($channel, $enabled, true)) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * Find the merchant's user account by phone or email, tolerant of legacy
     * vendors whose linked user.phone was never populated (falls back to the
     * vendors.phone column).
     */
    private function findVendorUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (str_contains($identifier, '@')) {
            return User::where('role', 'vendor')
                ->where('email', mb_strtolower($identifier))
                ->first();
        }

        $normalized = Phone::normalize($identifier);

        $user = User::where('role', 'vendor')
            ->where(function ($q) use ($identifier, $normalized) {
                $q->where('phone', $identifier);
                if ($normalized) {
                    $q->orWhere('phone', $normalized);
                }
            })
            ->first();

        if ($user) {
            return $user;
        }

        // Legacy fallback: match the vendor record, then its linked user.
        $vendor = Vendor::where(function ($q) use ($identifier, $normalized) {
            $q->where('phone', $identifier);
            if ($normalized) {
                $q->orWhere('phone', $normalized);
            }
        })->first();

        return $vendor?->user;
    }

    /** The canonical identifier shared by issue + verify (must agree). */
    private function canonical(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            return mb_strtolower(trim($identifier));
        }

        return Phone::normalize($identifier) ?? trim($identifier);
    }

    /** Mask an identifier for safe on-screen display. */
    private function mask(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$user, $domain] = explode('@', $identifier, 2);
            return mb_substr($user, 0, 2) . '***@' . $domain;
        }

        return mb_substr($identifier, 0, 3) . '****' . mb_substr($identifier, -2);
    }

    /** Safe unique placeholder email when a merchant registers phone-only. */
    private function placeholderEmail(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?: Str::random(8);
        $base   = 'vendor_' . $digits;
        $email  = $base . '@mosla.local';
        $i = 1;
        while (User::where('email', $email)->exists() || Vendor::where('email', $email)->exists()) {
            $email = $base . '-' . $i++ . '@mosla.local';
        }
        return $email;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
