<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Support\AuthSettings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google / Facebook" for customers.
 *
 * A customer account is tied to its CRM profile by phone (User::customer), and
 * neither provider gives us a phone — so a first-time social user is asked for
 * a mobile number once (complete-phone step) before going back to where they were.
 */
class SocialAuthController extends Controller
{
    private const SESSION_REDIRECT = 'social_login.redirect';

    public function redirect(Request $request, string $provider)
    {
        abort_unless(AuthSettings::socialEnabled($provider), 404);

        $request->session()->put(self::SESSION_REDIRECT, $this->safePath($request->query('redirect')));

        $driver = Socialite::driver($provider);
        if ($provider === 'facebook') {
            $driver->scopes(['email']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(AuthSettings::socialEnabled($provider), 404);

        $back = $request->session()->pull(self::SESSION_REDIRECT) ?: '/';

        if ($request->has('error') || ! $request->has('code')) {
            return redirect()->to(AuthSettings::loginUrl($back))->with('error', 'লগইন বাতিল করা হয়েছে।');
        }

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning("Social login ({$provider}) failed: ".$e->getMessage());

            return redirect()->to(AuthSettings::loginUrl($back))
                ->with('error', AuthSettings::SOCIAL_PROVIDERS[$provider].' দিয়ে লগইন করা যায়নি, আবার চেষ্টা করুন।');
        }

        $column = $provider.'_id';
        $email  = $social->getEmail() ? mb_strtolower(trim($social->getEmail())) : null;

        // 1) Already linked → 2) same (provider-verified) email → 3) new account.
        $user = User::where($column, $social->getId())->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
            if ($user && $user->role !== 'customer') {
                return redirect()->to(AuthSettings::loginUrl($back))
                    ->with('error', 'এই ইমেইলটি অন্য ধরনের অ্যাকাউন্টে ব্যবহার হচ্ছে। মোবাইল নম্বর দিয়ে লগইন করুন।');
            }
        }

        if (! $user) {
            if (! AuthSettings::customerRegistrationEnabled()) {
                return redirect()->to(AuthSettings::loginUrl($back))
                    ->with('error', 'বর্তমানে নতুন অ্যাকাউন্ট খোলা বন্ধ আছে।');
            }

            $user = User::create([
                'name'     => $social->getName() ?: ($social->getNickname() ?: 'Customer'),
                'email'    => $email,
                'password' => Hash::make(Str::random(40)), // social-only; a password can be set later
                'role'     => 'customer',
                'is_admin' => false,
            ]);
            if ($email) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        } elseif ($user->role !== 'customer') {
            return redirect()->to(AuthSettings::loginUrl($back))->with('error', 'এই অ্যাকাউন্ট দিয়ে এখানে লগইন করা যাবে না।');
        }

        $user->forceFill([
            $column      => $social->getId(),
            'avatar_url' => $user->avatar_url ?: $social->getAvatar(),
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        if (empty($user->phone)) {
            $request->session()->put(self::SESSION_REDIRECT, $back);

            return redirect()->route('customer.social.phone');
        }

        return redirect()->to($back)->with('success', 'সফলভাবে লগইন হয়েছে।');
    }

    /** One-time "add your mobile number" step for social accounts. */
    public function showPhone(Request $request)
    {
        $user = $request->user();
        if (! empty($user->phone)) {
            return redirect()->to($request->session()->pull(self::SESSION_REDIRECT) ?: '/');
        }

        return view('customer.auth.social-phone', ['user' => $user]);
    }

    public function savePhone(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'mobile_number' => ['required', 'string', 'max:20'],
        ], ['mobile_number.required' => 'মোবাইল নম্বর দিন।']);

        $phone = Phone::normalize($data['mobile_number']);
        if (! Phone::isValidBd($phone)) {
            return back()->withInput()->withErrors(['mobile_number' => 'সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।']);
        }

        // Never hand an existing account's history to an unverified social login:
        // the number must be free, or its profile must carry this same email.
        $taken = User::where('phone', $phone)->where('id', '!=', $user->id)->exists();
        $existingCustomer = Customer::where('mobile_number', $phone)->first();
        $sameOwner = $existingCustomer && $user->email
            && mb_strtolower((string) $existingCustomer->email) === mb_strtolower($user->email);

        if ($taken || ($existingCustomer && ! $sameOwner)) {
            return back()->withInput()->withErrors([
                'mobile_number' => 'এই নম্বরে আগে থেকেই অ্যাকাউন্ট আছে। ওই নম্বর দিয়ে (OTP/পাসওয়ার্ড) লগইন করুন, অথবা অন্য নম্বর দিন।',
            ]);
        }

        $user->update(['phone' => $phone]);
        Customer::firstOrCreate(
            ['mobile_number' => $phone],
            ['name' => $user->name, 'email' => $user->email, 'is_active' => true]
        );

        return redirect()->to($request->session()->pull(self::SESSION_REDIRECT) ?: '/')
            ->with('success', 'সফলভাবে লগইন হয়েছে।');
    }

    private function safePath(?string $path): string
    {
        return ($path && str_starts_with($path, '/') && ! str_starts_with($path, '//')) ? $path : '/';
    }
}
