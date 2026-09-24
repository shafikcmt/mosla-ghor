@extends('admin.layout')
@section('title', 'ভেন্ডর পণ্য অনুমোদন')

@section('content')
@php
    $statusLabels = ['pending' => 'পেন্ডিং', 'approved' => 'অনুমোদিত', 'rejected' => 'প্রত্যাখ্যাত'];
    $statusClasses = [
        'pending'  => 'bg-yellow-100 text-yellow-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];
    $cards = [
        ['key' => 'pending',  'label' => 'পেন্ডিং',     'box' => 'bg-yellow-50 border-yellow-200', 'text' => 'text-yellow-700'],
        ['key' => 'approved', 'label' => 'অনুমোদিত',    'box' => 'bg-green-50 border-green-200',   'text' => 'text-green-700'],
        ['key' => 'rejected', 'label' => 'প্রত্যাখ্যাত', 'box' => 'bg-red-50 border-red-200',       'text' => 'text-red-700'],
        ['key' => 'total',    'label' => 'মোট',         'box' => 'bg-gray-50 border-gray-200',     'text' => 'text-gray-700'],
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <h2 class="text-xl font-bold text-gray-800">ভেন্ডর পণ্য অনুমোদন</h2>
    <form method="GET" class="flex gap-2">
        <select name="status"
                class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">— সব অবস্থা —</option>
            @foreach($statusLabels as $value => $label)
            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-800 text-white text-sm rounded-lg px-4 py-2 hover:bg-gray-700">ফিল্টার</button>
        @if($status)
        <a href="{{ route('admin.vendor-products.index') }}" class="text-sm text-gray-500 px-2 py-2 hover:text-gray-700">রিসেট</a>
        @endif
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach($cards as $card)
    @php $href = $card['key'] === 'total' ? route('admin.vendor-products.index') : route('admin.vendor-products.index', ['status' => $card['key']]); @endphp
    <a href="{{ $href }}" class="block rounded-xl border px-4 py-3 {{ $card['box'] }} hover:shadow-sm transition">
        <p class="text-xs font-medium {{ $card['text'] }}">{{ $card['label'] }}</p>
        <p class="text-2xl font-bold {{ $card['text'] }} mt-1">{{ $stats[$card['key']] }}</p>
    </a>
    @endforeach
</div>

<form id="bulk-form" method="POST" action="{{ route('admin.vendor-products.bulk-approve') }}">
    @csrf
</form>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($products->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">কোনো ভেন্ডর পণ্য পাওয়া যায়নি।</div>
    @else
    {{-- Bulk action bar --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50">
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
            সব নির্বাচন করুন
        </label>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500"><span id="selected-count">0</span>টি নির্বাচিত</span>
            <button type="submit" form="bulk-form" id="bulk-approve-btn" disabled
                    class="text-sm bg-green-600 text-white rounded-lg px-3 py-1.5 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed">
                নির্বাচিতগুলো অনুমোদন
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 w-10"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">ভেন্ডর</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden sm:table-cell">স্টক</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অবস্থা</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($products as $product)
            @php $st = $product->approval_status ?? 'pending'; @endphp
            <tr class="hover:bg-gray-50 align-top">
                <td class="px-4 py-3">
                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="bulk-form"
                           class="row-check rounded border-gray-300 text-green-600 focus:ring-green-500">
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        @if($product->main_image)
                        <img src="{{ \App\Support\ProductMedia::url($product->main_image) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-100 shrink-0">
                        @else
                        <div class="w-10 h-10 rounded-lg bg-gray-100 shrink-0"></div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $product->name_bn }}</p>
                            <p class="text-xs text-gray-400">{{ $product->created_at?->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500 md:hidden">{{ $product->vendor?->shop_name }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <p class="font-medium text-gray-700">{{ $product->vendor?->shop_name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $product->vendor?->owner_name }}</p>
                </td>
                <td class="px-4 py-3 hidden sm:table-cell font-mono">
                    {{ rtrim(rtrim(number_format($product->onHand(), 3, '.', ''), '0'), '.') }} {{ $product->unit }}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium {{ $statusClasses[$st] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $statusLabels[$st] ?? $st }}
                    </span>
                    @if($st === 'rejected' && $product->rejection_reason)
                    <p class="text-xs text-red-500 mt-1 max-w-48">{{ $product->rejection_reason }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if($st !== 'approved')
                    <form method="POST" action="{{ route('admin.vendor-products.approve', $product) }}" class="inline">
                        @csrf
                        <button class="text-xs text-green-700 border border-green-200 rounded px-2 py-0.5 hover:bg-green-50 mr-1">অনুমোদন</button>
                    </form>
                    @endif
                    @if($st !== 'rejected')
                    <button type="button"
                            data-reject-url="{{ route('admin.vendor-products.reject', $product) }}"
                            data-product-name="{{ $product->name_bn }}"
                            class="reject-btn text-xs text-red-600 border border-red-200 rounded px-2 py-0.5 hover:bg-red-50">
                        প্রত্যাখ্যান
                    </button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">{{ $products->links() }}</div>
    @endif
    @endif
</div>

{{-- Reject modal --}}
<div id="reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <form id="reject-form" method="POST" action="">
            @csrf
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">পণ্য প্রত্যাখ্যান</h3>
                <p id="reject-product-name" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
            <div class="px-5 py-4">
                <label for="reject-reason" class="block text-sm font-medium text-gray-700 mb-1">কারণ (ঐচ্ছিক)</label>
                <textarea id="reject-reason" name="reason" rows="3" maxlength="200"
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="ভেন্ডরকে জানানোর জন্য কারণ লিখুন…"></textarea>
                <p class="text-xs text-gray-400 text-right mt-1"><span id="reason-count">0</span>/200</p>
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button type="button" id="reject-cancel" class="text-sm text-gray-600 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-100">বাতিল</button>
                <button type="submit" class="text-sm bg-red-600 text-white rounded-lg px-4 py-2 hover:bg-red-700">প্রত্যাখ্যান করুন</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const rowChecks = () => Array.from(document.querySelectorAll('.row-check'));
    const countEl = document.getElementById('selected-count');
    const bulkBtn = document.getElementById('bulk-approve-btn');

    function refreshCount() {
        const checks = rowChecks();
        const n = checks.filter(c => c.checked).length;
        if (countEl) countEl.textContent = n;
        if (bulkBtn) bulkBtn.disabled = n === 0;
        if (selectAll) {
            selectAll.checked = n > 0 && n === checks.length;
            selectAll.indeterminate = n > 0 && n < checks.length;
        }
    }

    selectAll?.addEventListener('change', () => {
        rowChecks().forEach(c => { c.checked = selectAll.checked; });
        refreshCount();
    });
    rowChecks().forEach(c => c.addEventListener('change', refreshCount));

    document.getElementById('bulk-form')?.addEventListener('submit', (e) => {
        const n = rowChecks().filter(c => c.checked).length;
        if (n === 0 || !confirm(n + 'টি পণ্য অনুমোদন করবেন?')) e.preventDefault();
    });

    // Reject modal
    const modal = document.getElementById('reject-modal');
    const form = document.getElementById('reject-form');
    const reason = document.getElementById('reject-reason');
    const reasonCount = document.getElementById('reason-count');
    const nameEl = document.getElementById('reject-product-name');

    function openModal(url, name) {
        form.action = url;
        nameEl.textContent = name;
        reason.value = '';
        reasonCount.textContent = '0';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        reason.focus();
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.rejectUrl, btn.dataset.productName));
    });
    document.getElementById('reject-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    reason.addEventListener('input', () => { reasonCount.textContent = reason.value.length; });

    refreshCount();
})();
</script>
@endsection
