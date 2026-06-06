@extends('vendor.layout')
@section('title', 'নোটিফিকেশন')

@section('content')
@php $levelDot = ['success' => 'bg-green-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-red-500', 'info' => 'bg-indigo-500']; @endphp

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">নোটিফিকেশন</h1>
    @if(auth()->user()->unreadNotifications()->count() > 0)
    <form method="POST" action="{{ route('vendor.notifications.readAll') }}">
        @csrf
        <button class="text-sm text-indigo-600 hover:underline">সব পড়া হয়েছে</button>
    </form>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden divide-y divide-gray-50">
    @forelse($notifications as $n)
        @php $d = $n->data; $dot = $levelDot[$d['level'] ?? 'info'] ?? 'bg-indigo-500'; @endphp
        <a href="{{ route('vendor.notifications.read', $n->id) }}"
           class="flex gap-3 px-5 py-4 hover:bg-gray-50 transition-colors {{ $n->read_at ? 'opacity-60' : 'bg-indigo-50/30' }}">
            <span class="mt-1.5 w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $dot }}"></span>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-gray-800">{{ $d['title_bn'] ?? 'নোটিফিকেশন' }}</div>
                <div class="text-sm text-gray-500">{{ $d['body_bn'] ?? '' }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</div>
            </div>
            @unless($n->read_at)<span class="text-[10px] text-indigo-600 font-bold uppercase self-start">নতুন</span>@endunless
        </a>
    @empty
        <div class="px-5 py-16 text-center text-gray-400">কোনো নোটিফিকেশন নেই।</div>
    @endforelse
</div>

<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
