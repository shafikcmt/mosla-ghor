<div class="px-6 py-5 space-y-5">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="name_bn">নাম (বাংলা) <span class="text-red-500">*</span></label>
            <input type="text" name="name_bn" id="name_bn"
                   value="{{ old('name_bn', $category->name_bn ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]"
                   required>
            @error('name_bn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="name_en">নাম (English)</label>
            <input type="text" name="name_en" id="name_en"
                   value="{{ old('name_en', $category->name_en ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            @error('name_en')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="parent_id">প্যারেন্ট ক্যাটাগরি</label>
            <select name="parent_id" id="parent_id"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <option value="">— টপ-লেভেল (কোনো প্যারেন্ট নেই) —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}"
                        {{ (string) old('parent_id', $category->parent_id ?? '') === (string) $parent->id ? 'selected' : '' }}>
                        {{ $parent->name_bn }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">সাব-ক্যাটাগরি বানাতে একটি প্যারেন্ট নির্বাচন করুন।</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="slug">Slug (URL)</label>
            <input type="text" name="slug" id="slug"
                   value="{{ old('slug', $category->slug ?? '') }}"
                   placeholder="খালি রাখলে নাম থেকে স্বয়ংক্রিয়ভাবে তৈরি হবে"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="sort_order">ক্রম (sort order)</label>
            <input type="number" name="sort_order" id="sort_order" min="0"
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 accent-[#14532d]">
                <span class="text-sm text-gray-700">সক্রিয়</span>
            </label>
        </div>
    </div>

</div>
