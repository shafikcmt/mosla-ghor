<div class="px-6 py-5 space-y-5">

    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1" for="question">প্রশ্ন <span class="text-red-500">*</span></label>
        <textarea name="question" id="question" rows="2"
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none"
                  required>{{ old('question', $faq->question ?? '') }}</textarea>
        @error('question')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1" for="answer">উত্তর <span class="text-red-500">*</span></label>
        <textarea name="answer" id="answer" rows="5"
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-y"
                  required>{{ old('answer', $faq->answer ?? '') }}</textarea>
        @error('answer')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="sort_order">ক্রম (sort order)</label>
            <input type="number" name="sort_order" id="sort_order" min="0"
                   value="{{ old('sort_order', $faq->sort_order ?? 0) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <span class="text-sm text-gray-700">সক্রিয়</span>
            </label>
        </div>
    </div>

</div>
