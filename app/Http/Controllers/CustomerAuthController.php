<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpThrottleException;
use App\Services\Otp\OtpUnavailableException;
use App\Support\AuthSettings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        if (Auth::check() && Auth::user()->role === 'customer') {
            return redirect()->route('customer.account');
        }

        if (WebsiteSetting::get('customer_registration_enabled', '1') !== '1') {
            return view('auth.unavailable', [
                'title'   => 'রেজিস্ট্রেশন বন্ধ',
                'message' => 'বর্তমানে নতুন রেজিস্ট্রেশন চালু নেই।',
            ]);
        }

        return view('customer.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'mobile_number' => 'required|string|max:20|unique:users,phone',
            'email'         => 'nullable|email|max:150|unique:users,email',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'name.required'          => 'নাম লিখুন।',
            'mobile_number.required' => 'মোবাইল নম্বর লিখুন।',
            'mobile_number.unique'   => 'এই মোবাইল নম্বর দিয়ে আগেই অ্যাকাউন্ট আছে।',
            'email.unique'           => 'এই ইমেইল দিয়ে আগেই একটি অ্যাকাউন্ট আছে।',
            'password.required'      => 'পাসওয়ার্ড দিন।',
            'password.min'           => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।',
            'password.confirmed'     => 'পাসওয়ার্ড মিলছে না।',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'phone'    => $data['mobile_number'],
            'password' => Hash::make($data['password']),
            'role'     => 'customer',
            'is_admin' => false,
        ]);

        // Link to existing CRM record (from past orders) or create a new profile
        $customer = Customer::where('mobile_number', $data['mobile_number'])->first()
            ?? new Customer();

        $customer->fill([
            'name'          => $data['name'],
            'mobile_number' => $data['mobile_number'],
            'email'         => $data['email'] ?? null,
            'is_active'     => true,
        ])->save();

        Auth::login($user);

        return redirect()->route('customer.account')
            ->with('success', 'অ্যাকাউন্ট তৈরি হয়েছে। স্বাগতম!');
    }

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'customer') {
            return redirect()->route('customer.account');
        }

        if (WebsiteSetting::get('customer_login_enabled', '1') !== '1') {
            return view('auth.unavailable', [
                'title'   => 'লগইন বন্ধ',
                'message' => 'বর্তমানে কাস্টমার লগইন চালু নেই।',
            ]);
        }

        return view('customer.auth.login', ['redirectParam' => request()->query('redirect', '')]);
    }

    public function login(Request $request)
    {
        if (! AuthSettings::customerPasswordLogin()) {
            return back()->withErrors(['mobile_number' => 'পাসওয়ার্ড দিয়ে লগইন এই মুহূর্তে বন্ধ আছে।'])->withInput();
        }

        $data = $request->validate([
            'mobile_number' => 'required|string',
            'password'      => 'required|string',
        ], [
            'mobile_number.required' => 'মোবাইল নম্বর দিন।',
            'password.required'      => 'পাসওয়ার্ড দিন।',
        ]);

        $input = $data['mobile_number'];

        if (str_contains($input, '@')) {
            $user = User::where('email', $input)->where('role', 'customer')->first();
        } else {
            $user = User::where('phone', $input)->where('role', 'customer')->first();
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['mobile_number' => 'মোবাইল/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।'])
                ->withInput();
        }

        Auth::login($user, true); // Always remember customers — reduces friction
        $request->session()->regenerate();

        // Honor ?redirect= query param (used by wholesale enquiry flow)
        $redirectTo = $request->query('redirect');
        if ($redirectTo && str_starts_with($redirectTo, '/')) {
            return redirect($redirectTo);
        }

        return redirect()->intended(route('customer.account'));
    }

    // ── OTP login ───────────────────────────────────────────────────────────
    // Passwordless login: the customer enters a phone/email, receives a code via
    // an admin-enabled channel, and verifies it. Gated by AuthSettings so the
    // admin can switch the whole flow off without code changes.

    public function showOtpRequest()
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('customer.login');
        }

        if (Auth::check() && Auth::user()->role === 'customer') {
            return redirect()->route('customer.account');
        }

        return view('customer.auth.otp-request');
    }

    public function sendOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('customer.login');
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

        if (! $this->findCustomer($identifier)) {
            return back()->withErrors(['identifier' => 'এই নম্বর/ইমেইল দিয়ে কোনো অ্যাকাউন্ট পাওয়া যায়নি। আগে রেজিস্ট্রেশন করুন।'])->withInput();
        }

        $canonical = $this->canonical($identifier);
        $request->session()->put('otp_login', ['identifier' => $canonical, 'channel' => $channel]);

        try {
            $otp->issue($canonical, 'login', $channel, 'customer', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (OtpThrottleException $e) {
            // A code is still valid — send them to the verify screen.
            return redirect()->route('customer.login.otp.verify')->with('error', $e->getMessage());
        } catch (OtpUnavailableException $e) {
            $request->session()->forget('otp_login');
            return back()->withErrors(['identifier' => $e->getMessage()])->withInput();
        }

        return redirect()->route('customer.login.otp.verify')
            ->with('success', 'OTP পাঠানো হয়েছে: ' . $this->mask($canonical));
    }

    public function showOtpVerify(Request $request)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('customer.login');
        }

        $session = $request->session()->get('otp_login');
        if (! $session) {
            return redirect()->route('customer.login.otp');
        }

        return view('customer.auth.otp-verify', ['masked' => $this->mask($session['identifier'])]);
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('customer.login');
        }

        $session = $request->session()->get('otp_login');
        if (! $session) {
            return redirect()->route('customer.login.otp');
        }

        $data = $request->validate(
            ['code' => 'required|string'],
            ['code.required' => 'OTP কোড দিন।']
        );

        if (! $otp->verify($session['identifier'], 'login', trim($data['code']))) {
            return back()->withErrors(['code' => 'OTP কোড সঠিক নয় বা মেয়াদ শেষ হয়ে গেছে।']);
        }

        $user = $this->findCustomer($session['identifier']);
        if (! $user) {
            $request->session()->forget('otp_login');
            return redirect()->route('customer.login')->withErrors(['mobile_number' => 'অ্যাকাউন্ট পাওয়া যায়নি।']);
        }

        Auth::login($user, true);
        $request->session()->forget('otp_login');
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('success', 'সফলভাবে লগইন হয়েছে।');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        if (! $this->otpLoginAvailable()) {
            return redirect()->route('customer.login');
        }

        $session = $request->session()->get('otp_login');
        if (! $session) {
            return redirect()->route('customer.login.otp');
        }

        try {
            $otp->issue($session['identifier'], 'login', $session['channel'], 'customer', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (OtpThrottleException | OtpUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'নতুন OTP পাঠানো হয়েছে।');
    }

    /** OTP login is usable only when the admin enabled it AND a channel exists. */
    private function otpLoginAvailable(): bool
    {
        return AuthSettings::customerOtpLogin() && AuthSettings::enabledChannels() !== [];
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

    /** Find the customer account behind a phone/email, format-tolerant. */
    private function findCustomer(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return User::where('role', 'customer')
                ->where('email', mb_strtolower(trim($identifier)))
                ->first();
        }

        $normalized = Phone::normalize($identifier);

        return User::where('role', 'customer')
            ->where(function ($q) use ($identifier, $normalized) {
                $q->where('phone', trim($identifier));
                if ($normalized) {
                    $q->orWhere('phone', $normalized);
                }
            })
            ->first();
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function account()
    {
        $user = Auth::user();

        $customer = Customer::where('mobile_number', $user->phone)->first();

        if (! $customer) {
            $customer = Customer::create([
                'name'          => $user->name,
                'mobile_number' => $user->phone,
                'email'         => $user->email,
                'is_active'     => true,
            ]);
        }

        $orders = $customer->orders()->latest()->paginate(10);

        return view('customer.account', compact('customer', 'orders'));
    }
}
