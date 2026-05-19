<div class="px-6 py-5 space-y-5">

    <div class="grid grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="customer_name">গ্রাহকের নাম <span class="text-red-500">*</span></label>
            <input type="text" name="customer_name" id="customer_name" maxlength="100"
                   value="{{ old('customer_name', $review->customer_name ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                   required>
            @error('customer_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="customer_location">অবস্থান (ঐচ্ছিক)</label>
            <input type="text" name="customer_location" id="customer_location" maxlength="100"
                   value="{{ old('customer_location', $review->customer_location ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('customer_location')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1" for="rating">রেটিং <span class="text-red-500">*</span></label>
        <select name="rating" id="rating"
                class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 bg-white">
            @for($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" {{ old('rating', $review->rating ?? 5) == $i ? 'selected' : '' }}>
                    {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }} — {{ $i }} তারা
                </option>
            @endfor
        </select>
        @error('rating')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1" for="review_text">রিভিউ <span class="text-red-500">*</span></label>
        <textarea name="review_text" id="review_text" rows="4" maxlength="1000"
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-y"
                  required>{{ old('review_text', $review->review_text ?? '') }}</textarea>
        @error('review_text')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1" for="image">ছবির URL (ঐচ্ছিক)</label>
        <input type="text" name="image" id="image" maxlength="500"
               value="{{ old('image', $review->image ?? '') }}"
               placeholder="https://..."
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
        @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="sort_order">ক্রম (sort order)</label>
            <input type="number" name="sort_order" id="sort_order" min="0"
                   value="{{ old('sort_order', $review->sort_order ?? 0) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $review->is_active ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <span class="text-sm text-gray-700">সক্রিয়</span>
            </label>
        </div>
    </div>

</div>
