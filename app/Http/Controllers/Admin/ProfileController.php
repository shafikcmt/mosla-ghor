<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit', ['admin' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => 'এই ইমেইল অন্য অ্যাকাউন্টে ব্যবহৃত হচ্ছে।',
            'email.email'  => 'সঠিক ইমেইল দিন।',
            'name.required' => 'নাম দিন।',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        // Password change is optional — only when a new password is supplied.
        // Requires the current password so a hijacked session can't silently
        // change credentials.
        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password'         => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'current_password.required'     => 'বর্তমান পাসওয়ার্ড দিন।',
                'current_password.current_password' => 'বর্তমান পাসওয়ার্ড সঠিক নয়।',
                'password.confirmed'            => 'নতুন পাসওয়ার্ড দুইবার মেলেনি।',
                'password.min'                  => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            ]);

            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()->route('admin.profile.edit')
            ->with('success', 'প্রোফাইল সফলভাবে আপডেট হয়েছে।');
    }
}
