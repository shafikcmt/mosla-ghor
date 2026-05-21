<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
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
        $request->validate([
            'shop_name'   => 'required|string|max:150',
            'owner_name'  => 'required|string|max:100',
            'phone'       => 'required|string|max:20|unique:vendors,phone',
            'email'       => 'required|email|unique:users,email|unique:vendors,email',
            'password'    => 'required|string|min:8|confirmed',
            'address'     => 'nullable|string|max:500',
            'business_type' => 'nullable|string|max:100',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kyc_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'shop_name.required'  => 'দোকানের নাম দিন।',
            'owner_name.required' => 'মালিকের নাম দিন।',
            'phone.required'      => 'ফোন নম্বর দিন।',
            'phone.unique'        => 'এই ফোন নম্বর দিয়ে আগেই রেজিস্ট্রেশন আছে।',
            'email.required'      => 'ইমেইল দিন।',
            'email.unique'        => 'এই ইমেইল দিয়ে আগেই একটি অ্যাকাউন্ট আছে।',
            'password.required'   => 'পাসওয়ার্ড দিন।',
            'password.min'        => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।',
            'password.confirmed'  => 'পাসওয়ার্ড মিলছে না।',
        ]);

        $user = User::create([
            'name'     => $request->owner_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'vendor',
            'is_admin' => false,
        ]);

        $slug = Str::slug($request->shop_name);
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
            'shop_name'    => $request->shop_name,
            'slug'         => $slug,
            'owner_name'   => $request->owner_name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'address'      => $request->address,
            'business_type' => $request->business_type,
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
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'ইমেইল দিন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'role' => 'vendor'], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'ইমেইল বা পাসওয়ার্ড ভুল।'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
