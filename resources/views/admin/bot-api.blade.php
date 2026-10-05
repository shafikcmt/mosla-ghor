@extends('admin.layout')

@section('title', 'Bot API ও সোশ্যাল লিড')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-2">Bot API ও সোশ্যাল লিড</h1>
<p class="text-sm text-gray-500 mb-6">আলাদা Facebook automation অ্যাপ (Messenger / কমেন্ট রিপ্লাই, ডেইলি পোস্ট) এই টোকেন দিয়ে পণ্যের দাম, স্টক ও অর্ডার স্ট্যাটাস পড়ে, আর লিড এখানে জমা দেয়। API ডকুমেন্টেশন: প্রজেক্টের <span class="font-mono">BOT_API.md</span>।</p>

@if(session('bot_plain_token'))
<div class="bg-amber-50 border border-amber-300 rounded p-4 mb-6">
    <p class="text-sm font-semibold text-amber-800 mb-2">নতুন টোকেন — এখনই কপি করে বট অ্যাপের <span class="font-mono">.env</span>-এ রাখুন। এই পেজ ছাড়লে আর দেখা যাবে না।</p>
    <input type="text" readonly value="{{ session('bot_plain_token') }}" onclick="this.select()"
           class="w-full font-mono text-sm border border-amber-300 rounded px-3 py-2 bg-white">
    <p class="text-xs text-amber-700 mt-2">ব্যবহার: <span class="font-mono">Authorization: Bearer &lt;token&gt;</span> — Base URL: <span class="font-mono">{{ url('/api/bot/v1') }}</span></p>
</div>
@endif

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-600">নতুন টোকেন তৈরি</h2></div>
        <form action="{{ route('admin.bot-api.tokens.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="name">নাম</label>
                <input id="name" name="name" maxlength="100" value="{{ old('name') }}" placeholder="Facebook Bot"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <span class="block text-xs font-medium text-gray-600 mb-2">অনুমতি</span>
                @foreach(\App\Models\BotApiToken::ABILITIES as $ability => $label)
                <label class="flex items-start gap-2 mb-1.5 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="abilities[]" value="{{ $ability }}" class="mt-1 rounded border-gray-300"
                           @checked(in_array($ability, old('abilities', array_keys(\App\Models\BotApiToken::ABILITIES)), true))>
                    <span>{{ $label }} <span class="text-xs text-gray-400 font-mono">{{ $ability }}</span></span>
                </label>
                @endforeach
                @error('abilities')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm font-medium hover:bg-gray-700">টোকেন তৈরি করুন</button>
        </form>
    </div>

    <div class="bg-white rounded shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-600">টোকেন তালিকা</h2></div>
        @if($tokens->isEmpty())
            <p class="px-6 py-5 text-sm text-gray-400">এখনও কোনো টোকেন নেই।</p>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($tokens as $t)
            <div class="px-6 py-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium {{ $t->isRevoked() ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $t->name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ implode(', ', (array) $t->abilities) }}</p>
                    <p class="text-xs text-gray-400">শেষ ব্যবহার: {{ $t->last_used_at ? $t->last_used_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') : 'কখনো না' }}</p>
                </div>
                @if($t->isRevoked())
                    <span class="text-xs text-red-500 shrink-0">বন্ধ</span>
                @else
                <form method="POST" action="{{ route('admin.bot-api.tokens.revoke', $t) }}" onsubmit="return confirm('এই টোকেন বন্ধ করবেন? বট অ্যাপ আর কাজ করবে না।')" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-red-600">বন্ধ করুন</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
        <h2 class="text-sm font-semibold text-gray-600 mr-3">সোশ্যাল লিড</h2>
        <a href="{{ route('admin.bot-api.index') }}" class="text-xs px-2.5 py-1 rounded-full {{ $status ? 'bg-gray-100 text-gray-600' : 'bg-gray-800 text-white' }}">সব ({{ $counts->sum() }})</a>
        @foreach(\App\Models\BotLead::STATUSES as $key => $label)
            <a href="{{ route('admin.bot-api.index', ['status' => $key]) }}" class="text-xs px-2.5 py-1 rounded-full {{ $status === $key ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </div>
    @if($leads->isEmpty())
        <p class="px-6 py-5 text-sm text-gray-400">কোনো লিড নেই।</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">সময় / উৎস</th>
                    <th class="px-4 py-3 text-left">কাস্টমার</th>
                    <th class="px-4 py-3 text-left">মেসেজ</th>
                    <th class="px-4 py-3 text-left">স্ট্যাটাস</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 align-top">
                @foreach($leads as $lead)
                <tr>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {{ $lead->created_at->timezone('Asia/Dhaka')->format('d M, h:i A') }}<br>
                        <span class="text-xs">{{ \App\Models\BotLead::SOURCES[$lead->source] ?? $lead->source }}@if($lead->page_name) · {{ $lead->page_name }}@endif</span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-gray-800">{{ $lead->customer_name ?: '—' }}</p>
                        <a href="tel:{{ $lead->phone }}" class="text-xs font-mono text-blue-600">{{ $lead->phone }}</a>
                        @if($wa = \App\Support\Phone::toWa($lead->phone))
                            · <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="text-xs text-green-700">WhatsApp</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700 max-w-md">
                        <p class="whitespace-pre-line break-all">{{ \Illuminate\Support\Str::limit($lead->message, 300) }}</p>
                        @if($lead->product)
                            <p class="text-xs mt-1">পণ্য: <a href="{{ route('products.show', $lead->product->slug) }}" target="_blank" class="text-blue-600">{{ $lead->product->display_name }}</a>@if($lead->quantity) · {{ $lead->quantity }}@endif</p>
                        @elseif($lead->quantity)
                            <p class="text-xs mt-1">পরিমাণ: {{ $lead->quantity }}</p>
                        @endif
                        @if($lead->address)<p class="text-xs text-gray-500 mt-1">ঠিকানা: {{ $lead->address }}</p>@endif
                    </td>
                    <td class="px-4 py-3 min-w-[200px]">
                        <form method="POST" action="{{ route('admin.bot-api.leads.update', $lead) }}" class="space-y-1.5">
                            @csrf @method('PATCH')
                            <select name="status" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                @foreach(\App\Models\BotLead::STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected($lead->status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="admin_note" maxlength="1000" value="{{ $lead->admin_note }}" placeholder="নোট"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                            <button type="submit" class="text-xs font-medium text-gray-700 underline">সংরক্ষণ</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())<div class="px-6 py-4">{{ $leads->links() }}</div>@endif
    @endif
</div>

@endsection
