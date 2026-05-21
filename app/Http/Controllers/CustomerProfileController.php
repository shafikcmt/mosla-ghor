<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerProfileController extends CustomerBaseController
{
    public function edit()
    {
        return view('customer.profile', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email'    => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required'    => 'নাম লিখুন।',
            'phone.required'   => 'মোবাইল নম্বর লিখুন।',
            'phone.unique'     => 'এই মোবাইল নম্বর অন্য অ্যাকাউন্টে ব্যবহৃত।',
            'email.unique'     => 'এই ইমেইল অন্য অ্যাকাউন্টে ব্যবহৃত।',
            'password.min'     => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।',
            'password.confirmed' => 'পাসওয়ার্ড মিলছে না।',
        ]);

        $updates = [
            'name'  => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
        ];

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        $user->update($updates);

        // Keep customer CRM record in sync
        Customer::where('mobile_number', $user->phone)
            ->update(['name' => $data['name'], 'email' => $data['email'] ?? null]);

        return back()->with('success', 'প্রোফাইল আপডেট হয়েছে।');
    }
}
