@extends('admin.layout')
@section('title', 'আমার প্রোফাইল')

@section('content')

<h2 class="text-lg font-bold text-gray-800 mb-6">আমার প্রোফাইল</h2>

<form method="POST" action="{{ route('admin.profile.update') }}" autocomplete="off">
    @csrf @method('PUT')

    {{-- Account details --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-5 max-w-2xl">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">অ্যাকাউন্ট তথ্য</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $admin->name) }}" required
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">লগইন ও নোটিফিকেশন ইমেইলের জন্য ব্যবহৃত হয়।</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ফোন</label>
                <input type="text" name="phone" value="{{ old('phone', $admin->phone) }}"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    {{-- Password change (optional) --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-5 max-w-2xl">
        <h3 class="font-semibold text-gray-700 text-sm mb-1 pb-2 border-b">পাসওয়ার্ড পরিবর্তন</h3>
        <p class="text-xs text-gray-400 mt-2 mb-4">পরিবর্তন করতে চাইলে নিচের ঘরগুলো পূরণ করুন, নাহলে খালি রাখুন।</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">বর্তমান পাসওয়ার্ড</label>
                <input type="password" name="current_password" autocomplete="current-password"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নতুন পাসওয়ার্ড</label>
                <input type="password" name="password" autocomplete="new-password"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নতুন পাসওয়ার্ড (পুনরায়)</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        প্রোফাইল আপডেট করুন
    </button>
</form>

@endsection
