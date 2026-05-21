<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    private function guard()
    {
        return Auth::guard('customer');
    }

    public function showRegister()
    {
        if ($this->guard()->check()) {
            return redirect()->route('customer.account');
        }

        return view('customer.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'mobile_number' => 'required|string|max:20',
            'email'         => 'nullable|email|max:150',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'name.required'          => 'নাম লিখুন।',
            'mobile_number.required' => 'মোবাইল নম্বর লিখুন।',
            'password.required'      => 'পাসওয়ার্ড দিন।',
            'password.min'           => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।',
            'password.confirmed'     => 'পাসওয়ার্ড মিলছে না।',
        ]);

        // Block duplicate registration for the same mobile number that already has a password
        if (Customer::where('mobile_number', $data['mobile_number'])->whereNotNull('password')->exists()) {
            return back()
                ->withErrors(['mobile_number' => 'এই মোবাইল নম্বর দিয়ে আগেই অ্যাকাউন্ট আছে।'])
                ->withInput();
        }

        // Re-use existing CRM record (from past orders) if available, else create new
        $customer = Customer::where('mobile_number', $data['mobile_number'])->whereNull('password')->first()
            ?? new Customer();

        $customer->fill([
            'name'          => $data['name'],
            'mobile_number' => $data['mobile_number'],
            'email'         => $data['email'] ?? null,
            'password'      => Hash::make($data['password']),
            'is_active'     => true,
        ])->save();

        $this->guard()->login($customer);

        return redirect()->route('customer.account')
            ->with('success', 'অ্যাকাউন্ট তৈরি হয়েছে। স্বাগতম!');
    }

    public function showLogin()
    {
        if ($this->guard()->check()) {
            return redirect()->route('customer.account');
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

        $customer = Customer::where('mobile_number', $data['mobile_number'])
            ->whereNotNull('password')
            ->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            return back()
                ->withErrors(['mobile_number' => 'মোবাইল নম্বর বা পাসওয়ার্ড ভুল।'])
                ->withInput();
        }

        $this->guard()->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'));
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function account()
    {
        $customer = $this->guard()->user();
        $orders   = $customer->orders()->latest()->paginate(10);

        return view('customer.account', compact('customer', 'orders'));
    }
}
