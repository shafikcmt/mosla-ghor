{{-- Profile tabs. "Marketing" only while the admin master switch for vendor pixels is on. --}}
@php $showMarketing = (bool) optional(\App\Models\MarketingSetting::query()->first())->vendor_pixels_enabled; @endphp
@if($showMarketing)
<div class="flex gap-2 mb-6 border-b border-gray-200">
    <a href="{{ route('vendor.profile.index') }}"
       class="px-4 py-2 text-sm font-medium -mb-px border-b-2 {{ $active === 'profile' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">শপ প্রোফাইল</a>
    <a href="{{ route('vendor.profile.marketing') }}"
       class="px-4 py-2 text-sm font-medium -mb-px border-b-2 {{ $active === 'marketing' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Marketing</a>
</div>
@endif
