@php $combo = $combo ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">কম্বোর নাম <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $combo?->name) }}" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
        <input type="text" name="slug" value="{{ old('slug', $combo?->slug) }}" required
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('slug') border-red-400 @enderror">
        @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">বিক্রয় ধরন <span class="text-red-500">*</span></label>
        <select name="sell_type" required
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('sell_type') border-red-400 @enderror">
            <option value="">— বেছে নিন —</option>
            <option value="retail" {{ old('sell_type', $combo?->sell_type) === 'retail' ? 'selected' : '' }}>খুচরা (Retail)</option>
            <option value="wholesale" {{ old('sell_type', $combo?->sell_type) === 'wholesale' ? 'selected' : '' }}>পাইকারি (Wholesale)</option>
        </select>
        <p class="text-xs text-gray-400 mt-1">খুচরা কম্বোতে শুধু খুচরা পণ্য অপশন দেখাবে।</p>
        @error('sell_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">বিক্রয় মূল্য (৳) <span class="text-red-500">*</span></label>
        <input type="number" name="sell_price" value="{{ old('sell_price', $combo?->sell_price) }}" step="0.01" min="1" required
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('sell_price') border-red-400 @enderror">
        @error('sell_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ব্যাজ টেক্সট</label>
        <input type="text" name="badge_text" value="{{ old('badge_text', $combo?->badge_text) }}" placeholder="যেমন: সেরা মূল্য"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">সর্ট অর্ডার</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $combo?->sort_order ?? 0) }}" min="0"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">সংক্ষিপ্ত বিবরণ</label>
        <textarea name="short_description" rows="2"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 resize-none">{{ old('short_description', $combo?->short_description) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <input type="checkbox" name="is_active" id="combo_is_active" value="1"
               {{ old('is_active', $combo?->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded">
        <label for="combo_is_active" class="text-sm font-medium text-gray-700 cursor-pointer">সক্রিয়</label>
    </div>

</div>
