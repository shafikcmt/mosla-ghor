@extends('admin.layout')
@section('title', 'মেইল সেটিং (SMTP)')

@section('content')

<h2 class="text-lg font-bold text-gray-800 mb-1">মেইল সেটিং (SMTP)</h2>
<p class="text-sm text-gray-500 mb-6">অর্ডার ও কোটেশন ইমেইল নোটিফিকেশনের জন্য SMTP কনফিগার করুন। বন্ধ থাকলে <code>.env</code> সেটিং ব্যবহৃত হবে।</p>

{{-- Save settings --}}
<form method="POST" action="{{ route('admin.mail-settings.update') }}" autocomplete="off" x-data="{ replace: false }">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-5 max-w-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b">
            <h3 class="font-semibold text-gray-700 text-sm">SMTP কনফিগারেশন</h3>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_enabled" value="1" {{ $settings->is_enabled ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="font-medium text-gray-700">চালু</span>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ড্রাইভার</label>
                <select name="driver" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="smtp" {{ $settings->driver === 'smtp' ? 'selected' : '' }}>SMTP</option>
                    <option value="log"  {{ $settings->driver === 'log'  ? 'selected' : '' }}>Log (টেস্ট)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">এনক্রিপশন</label>
                <select name="encryption" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value=""    {{ $settings->encryption === null  ? 'selected' : '' }}>None</option>
                    <option value="tls" {{ $settings->encryption === 'tls' ? 'selected' : '' }}>TLS (587)</option>
                    <option value="ssl" {{ $settings->encryption === 'ssl' ? 'selected' : '' }}>SSL (465)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">হোস্ট</label>
                <input type="text" name="host" value="{{ old('host', $settings->host) }}" placeholder="smtp.gmail.com"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পোর্ট</label>
                <input type="number" name="port" value="{{ old('port', $settings->port) }}" placeholder="587"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইউজারনেম</label>
                <input type="text" name="username" value="{{ old('username', $settings->username) }}" autocomplete="off"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড</label>
                @if($settings->maskedPassword())
                <p class="text-xs text-gray-400 mb-1">বর্তমান: <span class="font-mono">{{ $settings->maskedPassword() }}</span></p>
                @endif
                <label class="inline-flex items-center gap-2 text-xs text-gray-600 mb-2">
                    <input type="checkbox" x-model="replace" name="replace_password" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    পাসওয়ার্ড পরিবর্তন করুন
                </label>
                <input type="password" name="password" x-bind:disabled="!replace" autocomplete="new-password"
                       placeholder="নতুন পাসওয়ার্ড"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">প্রেরক ইমেইল (From)</label>
                <input type="email" name="from_address" value="{{ old('from_address', $settings->from_address) }}" placeholder="no-reply@moslamart.com"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">প্রেরক নাম (From Name)</label>
                <input type="text" name="from_name" value="{{ old('from_name', $settings->from_name) }}" placeholder="MoslaMart"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        সেটিং সংরক্ষণ করুন
    </button>
</form>

{{-- Send test email --}}
<form method="POST" action="{{ route('admin.mail-settings.test') }}" class="mt-6">
    @csrf
    <div class="bg-white rounded-xl border border-gray-100 p-6 max-w-2xl">
        <h3 class="font-semibold text-gray-700 text-sm mb-1 pb-2 border-b">টেস্ট ইমেইল পাঠান</h3>
        <p class="text-xs text-gray-400 mt-2 mb-4">সেটিং সংরক্ষণ করার পর একটি বাস্তব টেস্ট ইমেইল পাঠিয়ে ডেলিভারি যাচাই করুন।</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="email" name="test_email" value="{{ old('test_email', auth()->user()->email) }}" required
                   class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors whitespace-nowrap">
                টেস্ট ইমেইল পাঠান
            </button>
        </div>
    </div>
</form>

@endsection
