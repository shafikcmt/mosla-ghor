@extends('admin.layout')
@section('title', 'Wholesale Enquiry সমূহ')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">Wholesale Enquiry সমূহ</h2>
    <div class="flex gap-2">
        @foreach(['all' => 'সব', 'pending' => 'অপেক্ষায়', 'quoted' => 'Quote পাঠানো', 'accepted' => 'গৃহীত', 'completed' => 'সম্পন্ন'] as $s => $label)
        <a href="{{ route('admin.wholesale.enquiry.index', $s !== 'all' ? ['status' => $s] : []) }}"
           class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                  {{ request('status', 'all') === $s ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:border-indigo-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

@if($enquiries->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📭</p>
    <p class="text-sm">কোনো enquiry পাওয়া যায়নি।</p>
</div>
@else
{{-- Bulk delete (e.g. test enquiries). Rows that already became an order are skipped server-side. --}}
<form id="enq-bulk" method="POST" action="{{ route('admin.wholesale.enquiry.bulk-destroy') }}"
      onsubmit="return confirm('নির্বাচিত enquiry গুলো (quote ও চ্যাটসহ) স্থায়ীভাবে মুছে ফেলবেন?');">
    @csrf
</form>
<div class="flex items-center gap-3 mb-3">
    <button type="submit" form="enq-bulk" id="enq-bulk-btn" disabled
            class="text-xs bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
        🗑 নির্বাচিতগুলো মুছুন (<span id="enq-bulk-count">0</span>)
    </button>
    <span class="text-xs text-gray-400">অর্ডার হয়ে যাওয়া enquiry মুছা যায় না।</span>
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="pl-4 py-3 w-8"><input type="checkbox" id="enq-all" aria-label="সব নির্বাচন" class="rounded border-gray-300"></th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">পরিমাণ</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Quote</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বার্তা</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($enquiries as $enquiry)
            <tr class="hover:bg-gray-50">
                <td class="pl-4 py-3"><input type="checkbox" name="ids[]" value="{{ $enquiry->id }}" form="enq-bulk" class="enq-pick rounded border-gray-300" aria-label="Enquiry #{{ $enquiry->id }} নির্বাচন"></td>
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $enquiry->id }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800 text-xs">{{ $enquiry->customer_name }}</p>
                    <p class="text-gray-400 text-xs">{{ $enquiry->customer?->email }}</p>
                </td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $enquiry->productLabel() }}</td>
                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}@if($enquiry->contact_channel && $enquiry->contact_channel !== 'form')<span class="ml-1 inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $enquiry->contact_channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}">{{ \App\Models\WholesaleEnquiry::CHANNELS[$enquiry->contact_channel] ?? $enquiry->contact_channel }}</span>@endif</td>
                <td class="px-4 py-3 hidden lg:table-cell">
                    @if($enquiry->latestQuote)
                    <span class="text-xs text-gray-600">{{ $enquiry->latestQuote->statusLabel() }}</span>
                    @else
                    <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($enquiry->unread_count > 0)
                    <span class="text-xs bg-red-500 text-white font-bold rounded-full px-2 py-0.5">{{ $enquiry->unread_count }}</span>
                    @else
                    <span class="text-xs text-gray-300">0</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        @if($enquiry->status === 'pending') bg-yellow-100 text-yellow-700
                        @elseif($enquiry->status === 'quoted') bg-blue-100 text-blue-700
                        @elseif($enquiry->status === 'accepted') bg-green-100 text-green-700
                        @elseif($enquiry->status === 'completed') bg-indigo-100 text-indigo-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $enquiry->statusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('admin.wholesale.enquiry.show', $enquiry->id) }}"
                           class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                            দেখুন
                        </a>
                        <form method="POST" action="{{ route('admin.wholesale.enquiry.destroy', $enquiry->id) }}"
                              onsubmit="return confirm('Enquiry #{{ $enquiry->id }} (quote ও চ্যাটসহ) স্থায়ীভাবে মুছে ফেলবেন?');">
                            @csrf @method('DELETE')
                            <button type="submit" title="মুছুন" aria-label="Enquiry #{{ $enquiry->id }} মুছুন"
                                    class="text-xs border border-red-200 text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition-colors">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($enquiries->hasPages())
    <div class="px-4 py-3 border-t">{{ $enquiries->links() }}</div>
    @endif
</div>
<script>
(function () {
    const all = document.getElementById('enq-all');
    const picks = () => [...document.querySelectorAll('.enq-pick')];
    const btn = document.getElementById('enq-bulk-btn'), count = document.getElementById('enq-bulk-count');
    function sync() {
        const n = picks().filter(c => c.checked).length;
        count.textContent = n; btn.disabled = n === 0;
        all.checked = n > 0 && n === picks().length;
    }
    all.addEventListener('change', () => { picks().forEach(c => c.checked = all.checked); sync(); });
    picks().forEach(c => c.addEventListener('change', sync));
})();
</script>
@endif
@endsection
