@php
    // $panel = 'vendor' | 'admin' | 'customer'
    $u      = auth()->user();
    $unread = $u ? $u->unreadNotifications()->count() : 0;
    $recent = $u ? $u->notifications()->latest()->take(8)->get() : collect();
    $rIndex   = $panel . '.notifications.index';
    $rRead    = $panel . '.notifications.read';
    $rReadAll = $panel . '.notifications.readAll';
    $levelDot = ['success' => 'bg-green-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-red-500', 'info' => 'bg-indigo-500'];
@endphp

<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="relative flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unread > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden"
         style="display:none;">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="text-sm font-bold text-gray-700">নোটিফিকেশন</span>
            @if($unread > 0)
            <form method="POST" action="{{ route($rReadAll) }}">
                @csrf
                <button class="text-xs text-indigo-600 hover:underline">সব পড়া হয়েছে</button>
            </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            @forelse($recent as $n)
                @php $d = $n->data; $dot = $levelDot[$d['level'] ?? 'info'] ?? 'bg-indigo-500'; @endphp
                <a href="{{ route($rRead, $n->id) }}"
                   class="flex gap-3 px-4 py-3 hover:bg-gray-50 transition-colors {{ $n->read_at ? 'opacity-60' : '' }}">
                    <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 {{ $dot }}"></span>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-800 truncate">{{ $d['title_bn'] ?? 'নোটিফিকেশন' }}</div>
                        <div class="text-xs text-gray-500 line-clamp-2">{{ $d['body_bn'] ?? '' }}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">কোনো নোটিফিকেশন নেই।</div>
            @endforelse
        </div>

        <a href="{{ route($rIndex) }}" class="block text-center text-xs text-indigo-600 hover:bg-gray-50 py-2.5 border-t border-gray-100">সব দেখুন</a>
    </div>
</div>
<style>[x-cloak]{display:none!important;}</style>
