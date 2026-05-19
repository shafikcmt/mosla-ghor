<div class="px-6 py-5 space-y-5">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">জোনের নাম <span class="text-red-500">*</span></label>
            <input type="text" name="zone_name"
                   value="{{ old('zone_name', $zone?->zone_name) }}"
                   placeholder="যেমন: ঢাকা সিটি"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('zone_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">জোনের ধরন <span class="text-red-500">*</span></label>
            <select name="zone_type"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 bg-white">
                @foreach(['inside_dhaka' => 'ঢাকার ভেতরে', 'outside_dhaka' => 'ঢাকার বাইরে', 'custom' => 'কাস্টম'] as $val => $label)
                    <option value="{{ $val }}" {{ old('zone_type', $zone?->zone_type) === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('zone_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">ডেলিভারি চার্জ (৳) <span class="text-red-500">*</span></label>
            <input type="number" name="delivery_charge" min="0" step="1"
                   value="{{ old('delivery_charge', $zone?->delivery_charge) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('delivery_charge') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                ফ্রি ডেলিভারির ন্যূনতম পরিমাণ (৳)
                <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span>
            </label>
            <input type="number" name="free_delivery_minimum_amount" min="0" step="1"
                   value="{{ old('free_delivery_minimum_amount', $zone?->free_delivery_minimum_amount) }}"
                   placeholder="যেমন: 1500"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('free_delivery_minimum_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                সাজানোর ক্রম
                <span class="text-gray-400 font-normal">(ঐচ্ছিক, কম সংখ্যা আগে আসে)</span>
            </label>
            <input type="number" name="sort_order" min="0" step="1"
                   value="{{ old('sort_order', $zone?->sort_order) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('sort_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-end pb-1">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $zone ? ($zone->is_active ? '1' : '') : '1') ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <div>
                    <div class="text-sm font-semibold text-gray-700">জোন সক্রিয়</div>
                    <div class="text-xs text-gray-400">নিষ্ক্রিয় জোন অর্ডার ফর্মে দেখাবে না</div>
                </div>
            </label>
        </div>
    </div>

</div>
