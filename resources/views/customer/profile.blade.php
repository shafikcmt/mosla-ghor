@extends('customer.layout')
@section('title', 'প্রোফাইল')

@section('content')
<div class="max-w-lg">
    <h1 class="text-xl font-bold text-gray-800 mb-5">প্রোফাইল আপডেট</h1>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <hr class="border-gray-100">
            <p class="text-sm font-medium text-gray-700">পাসওয়ার্ড পরিবর্তন <span class="text-gray-400 font-normal">(খালি রাখলে পরিবর্তন হবে না)</span></p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নতুন পাসওয়ার্ড</label>
                <input type="password" name="password" minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                আপডেট করুন
            </button>
        </form>
    </div>
</div>
@endsection
