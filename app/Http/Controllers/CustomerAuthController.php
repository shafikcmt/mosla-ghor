<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WebsiteSetting;
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

        return view('customer.auth.login');
    }

    public function login(Request $request)
    {
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

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'));
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
