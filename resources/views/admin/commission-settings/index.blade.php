@extends('admin.layout')
@section('title', 'Commission সেটিং')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">Commission সেটিং</h2>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Add new setting --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-4">নতুন Commission সেটিং</h3>
            <form action="{{ route('admin.commission.settings.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Scope</label>
                    <select name="scope" id="scope-select"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                            onchange="toggleScopeId(this.value)">
                        <option value="global">Global (সকল Vendor)</option>
                        <option value="vendor">Vendor-specific</option>
                    </select>
                </div>

                <div id="vendor-scope-field" class="hidden">
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Vendor</label>
                    <select name="scope_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">— Vendor বেছে নিন —</option>
                        @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->shop_name ?? $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">প্রযোজ্য</label>
                    <select name="applies_to" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="wholesale">শুধু Wholesale</option>
                        <option value="retail">শুধু Retail</option>
                        <option value="both">উভয়</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Commission ধরন</label>
                    <select name="commission_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="percentage">শতকরা (%)</option>
                        <option value="fixed">নির্দিষ্ট পরিমাণ (৳)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">মান</label>
                    <input type="number" name="commission_value" step="0.01" min="0" required
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                           placeholder="যেমন: 5 (%) বা 50 (৳)">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">নোট</label>
                    <input type="text" name="note"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                           placeholder="ঐচ্ছিক">
                </div>

                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    সক্রিয়
                </label>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl text-sm transition-colors">
                    সেটিং যোগ করুন
                </button>
            </form>
        </div>
    </div>

    {{-- Existing settings --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-sm">বিদ্যমান সেটিংস</h3>
                <a href="{{ route('admin.commission.ledger.index') }}"
                   class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                    Ledger দেখুন →
                </a>
            </div>
            @if($settings->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">কোনো সেটিং নেই।</div>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Scope</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">প্রযোজ্য</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Commission</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($settings as $setting)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800 text-xs">
                            {{ $setting->scope === 'global' ? 'Global' : ($setting->scope === 'vendor' ? 'Vendor #'.$setting->scope_id : 'Category #'.$setting->scope_id) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs capitalize">{{ $setting->applies_to }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">
                            {{ $setting->commission_value }}{{ $setting->commission_type === 'percentage' ? '%' : '৳' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $setting->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $setting->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.commission.settings.destroy', $setting->id) }}" method="POST"
                                  onsubmit="return confirm('এই সেটিং মুছবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">মুছুন</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

<script>
function toggleScopeId(val) {
    document.getElementById('vendor-scope-field').classList.toggle('hidden', val !== 'vendor');
}
</script>
@endsection
