@extends('vendor.khata.layout')
@section('title', 'স্টাফ')
@section('heading', 'স্টাফ')
@section('subheading', 'কে কোন কাজ করতে পারবে')
@section('back', route('vendor.khata.settings'))

@section('content')
<div class="lg:max-w-3xl">
    <a href="{{ route('vendor.khata.staff.create') }}" class="block w-full text-center bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3 rounded-xl">+ নতুন স্টাফ যোগ করুন</a>
    <p class="text-xs text-gray-500 mt-2">স্টাফ নিজের মোবাইল নম্বর ও পাসওয়ার্ড দিয়ে <span class="font-mono">{{ url('/vendor/login') }}</span> থেকে লগইন করবে এবং শুধু আপনার দেওয়া কাজগুলো করতে পারবে। সেটিং ও স্টাফ ব্যবস্থাপনা শুধু আপনার।</p>

    <div class="mt-4 space-y-3">
        @forelse($staff as $s)
        <a href="{{ route('vendor.khata.staff.edit', $s) }}" class="block rounded-2xl bg-white border border-gray-100 shadow-sm p-4 hover:border-[#0f7a3e]">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-bold truncate">{{ $s->name }}</p>
                    <p class="text-sm text-gray-500">{{ $s->phone }}</p>
                </div>
                <span class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full {{ $s->staff_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $s->staff_active ? 'চালু' : 'বন্ধ' }}</span>
            </div>
            <div class="flex flex-wrap gap-1 mt-2">
                @forelse((array) $s->staff_permissions as $p)
                    @isset(\App\Support\KhataStaff::PERMISSIONS[$p])<span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ \App\Support\KhataStaff::PERMISSIONS[$p] }}</span>@endisset
                @empty
                    <span class="text-[11px] text-gray-400">কোনো কাজের অনুমতি নেই</span>
                @endforelse
            </div>
        </a>
        @empty
        <div class="rounded-2xl bg-white border border-gray-100 p-8 text-center text-gray-500">এখনো কোনো স্টাফ নেই।</div>
        @endforelse
    </div>
</div>
@endsection
