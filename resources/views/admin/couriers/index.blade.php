@extends('admin.layout')
@section('title', 'কুরিয়ার ম্যানেজমেন্ট')

@php
    $total       = $couriers->count();
    $activeCount = $couriers->where('status', 'active')->count();
    $apiCount    = $couriers->filter->supportsApi()->count();
    $manualCount = $total - $apiCount;
@endphp

@section('content')
<div x-data="courierMgmt()">

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">কুরিয়ার ম্যানেজমেন্ট</h2>
            <p class="text-xs text-gray-500 mt-0.5">কুরিয়ার তালিকা ও বেসিক তথ্য। API credential
                <a href="{{ route('admin.courier-api-settings.index') }}" class="text-indigo-600 hover:underline">API সেটিং</a> পেজে।</p>
        </div>
        <button @click="openCreate()"
                class="bg-[#14532d] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0d3520] transition-colors">
            + নতুন কুরিয়ার
        </button>
    </div>

    {{-- Summary stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <x-ui.stat-card label="মোট কুরিয়ার" :value="$total" color="gray" />
        <x-ui.stat-card label="সক্রিয়" :value="$activeCount" color="green" />
        <x-ui.stat-card label="API কুরিয়ার" :value="$apiCount" color="blue" />
        <x-ui.stat-card label="ম্যানুয়াল" :value="$manualCount" color="amber" />
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 mb-4 flex flex-wrap items-center gap-2">
        <input type="search" placeholder="নাম / slug খুঁজুন…" oninput="filterCouriers()" id="cf-q"
               class="flex-1 min-w-[180px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
        <select id="cf-status" onchange="filterCouriers()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">সব স্ট্যাটাস</option>
            <option value="active">সক্রিয়</option>
            <option value="inactive">নিষ্ক্রিয়</option>
        </select>
        <select id="cf-type" onchange="filterCouriers()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">সব টাইপ</option>
            <option value="api">API</option>
            <option value="manual">ম্যানুয়াল</option>
        </select>
        <select id="cf-vendor" onchange="filterCouriers()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">ভেন্ডর (সব)</option>
            <option value="1">অনুমোদিত</option>
            <option value="0">বন্ধ</option>
        </select>
    </div>

    {{-- Courier table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">কুরিয়ার</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">ব্যাজ</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">সর্বশেষ চেক</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="courier-rows">
                @forelse($couriers as $courier)
                <tr class="hover:bg-gray-50 courier-row"
                    data-name="{{ strtolower($courier->name . ' ' . $courier->slug) }}"
                    data-status="{{ $courier->status }}"
                    data-type="{{ $courier->supportsApi() ? 'api' : 'manual' }}"
                    data-vendor="{{ $courier->vendor_allowed ? '1' : '0' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $courier->name }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $courier->slug }}</p>
                    </td>
                    <td class="px-4 py-3"><x-courier.badges :courier="$courier" /></td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if($courier->supportsApi() && $courier->courier_api_last_checked_at)
                            <span class="{{ $courier->courier_api_last_status === 'success' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $courier->courier_api_last_status === 'success' ? '✓ সফল' : '✗ ব্যর্থ' }}
                            </span>
                            · {{ $courier->courier_api_last_checked_at->diffForHumans() }}
                        @else — @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <button @click='openEdit({!! json_encode([
                                        "id" => $courier->id,
                                        "name" => $courier->name,
                                        "slug" => $courier->slug,
                                        "status" => $courier->status,
                                        "vendor_allowed" => (bool) $courier->vendor_allowed,
                                        "is_default" => (bool) $courier->is_default,
                                        "notes" => $courier->notes,
                                        "update_url" => route("admin.couriers.update", $courier),
                                    ], JSON_HEX_APOS) !!})'
                                    class="text-xs px-2 py-1 rounded border border-gray-200 text-blue-600 hover:bg-blue-50">সম্পাদনা</button>
                            @if($courier->supportsApi())
                            <a href="{{ route('admin.courier-api-settings.index') }}"
                               class="text-xs px-2 py-1 rounded border border-gray-200 text-indigo-600 hover:bg-indigo-50">API</a>
                            @endif
                            <form method="POST" action="{{ route('admin.couriers.toggle', $courier) }}">
                                @csrf
                                <button class="text-xs px-2 py-1 rounded border border-gray-200 {{ $courier->status === 'active' ? 'text-orange-600 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50' }}">
                                    {{ $courier->status === 'active' ? 'নিষ্ক্রিয়' : 'সক্রিয়' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}"
                                  onsubmit="return confirm('এই কুরিয়ার মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button class="text-xs px-2 py-1 rounded border border-gray-200 text-red-500 hover:bg-red-50">মুছুন</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">কোনো কুরিয়ার নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="courier-empty" class="hidden px-4 py-8 text-center text-gray-400 text-sm">এই ফিল্টারে কোনো কুরিয়ার নেই।</div>
    </div>

    {{-- ── Slide-over: create / edit basic info ───────────────────────── --}}
    <div x-cloak x-show="open" class="fixed inset-0 z-40" x-transition.opacity>
        <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl flex flex-col"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <h3 class="font-semibold text-gray-800" x-text="mode==='edit' ? 'কুরিয়ার সম্পাদনা' : 'নতুন কুরিয়ার'"></h3>
                <button @click="open=false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <form method="POST" :action="mode==='edit' ? form.update_url : '{{ route('admin.couriers.store') }}'" class="flex-1 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="mode==='edit' ? 'PUT' : 'POST'">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-gray-400 text-xs">(ফাঁকা হলে অটো)</span></label>
                    <input type="text" name="slug" x-model="form.slug"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
                    <select name="status" x-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none">
                        <option value="active">সক্রিয়</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="vendor_allowed" value="1" x-model="form.vendor_allowed" class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডরদের জন্য অনুমোদিত
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="w-4 h-4 accent-[#14532d]">
                    ডিফল্ট কুরিয়ার
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">নোট</label>
                    <textarea name="notes" rows="3" x-model="form.notes"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none"></textarea>
                </div>
                <div class="pt-2 flex gap-2">
                    <button type="submit" class="bg-[#14532d] text-white text-sm px-5 py-2 rounded-lg hover:bg-[#0d3520]">সংরক্ষণ করুন</button>
                    <button type="button" @click="open=false" class="text-sm text-gray-500 px-5 py-2 border border-gray-300 rounded-lg">বাতিল</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function courierMgmt() {
    return {
        open: false,
        mode: 'edit',
        form: {},
        openEdit(d) { this.form = d; this.mode = 'edit'; this.open = true; },
        openCreate() {
            this.form = { name: '', slug: '', status: 'active', vendor_allowed: true, is_default: false, notes: '' };
            this.mode = 'create';
            this.open = true;
        },
    };
}
function filterCouriers() {
    const q = document.getElementById('cf-q').value.toLowerCase().trim();
    const st = document.getElementById('cf-status').value;
    const ty = document.getElementById('cf-type').value;
    const ve = document.getElementById('cf-vendor').value;
    let shown = 0;
    document.querySelectorAll('#courier-rows .courier-row').forEach(function (row) {
        const ok = (!q || row.dataset.name.includes(q))
            && (!st || row.dataset.status === st)
            && (!ty || row.dataset.type === ty)
            && (!ve || row.dataset.vendor === ve);
        row.classList.toggle('hidden', !ok);
        if (ok) shown++;
    });
    document.getElementById('courier-empty').classList.toggle('hidden', shown !== 0);
}
</script>
@endsection
