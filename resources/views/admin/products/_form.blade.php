@php $product = $product ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- name_bn --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            বাংলা নাম <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name_bn" id="name_bn"
               value="{{ old('name_bn', $product?->name_bn) }}"
               required autofocus
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 @error('name_bn') border-red-400 @enderror">
        @error('name_bn')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- name_en --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">English Name</label>
        <input type="text" name="name_en" id="name_en"
               value="{{ old('name_en', $product?->name_en) }}"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 @error('name_en') border-red-400 @enderror">
        @error('name_en')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- slug --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Slug (URL) <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-2">
            <input type="text" name="slug" id="slug"
                   value="{{ old('slug', $product?->slug) }}"
                   required
                   class="flex-1 border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-400 @error('slug') border-red-400 @enderror">
            <button type="button" id="btn-gen-slug"
                    class="text-xs bg-gray-100 hover:bg-gray-200 border px-3 py-2 rounded text-gray-600 whitespace-nowrap">
                Auto-generate
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-1">উদাহরণ: holud-gura (URL-এ ব্যবহৃত হবে)</p>
        @error('slug')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- retail_price_1kg --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            খুচরা দাম — ১ কেজি (৳) <span class="text-red-500">*</span>
        </label>
        <input type="number" name="retail_price_1kg"
               value="{{ old('retail_price_1kg', $product?->retail_price_1kg) }}"
               step="0.01" min="0.01" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 @error('retail_price_1kg') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">সব প্যাকের দাম এই মূল্য থেকে স্বয়ংক্রিয়ভাবে হিসাব হবে।</p>
        @error('retail_price_1kg')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- wholesale_price_1kg --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">পাইকারি দাম — ১ কেজি (৳)</label>
        <input type="number" name="wholesale_price_1kg"
               value="{{ old('wholesale_price_1kg', $product?->wholesale_price_1kg) }}"
               step="0.01" min="0"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
        @error('wholesale_price_1kg')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- stock --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            স্টক (কেজি) <span class="text-red-500">*</span>
        </label>
        <input type="number" name="stock"
               value="{{ old('stock', $product?->stock ?? 0) }}"
               min="0" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 @error('stock') border-red-400 @enderror">
        @error('stock')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- is_active --}}
    <div class="flex items-center gap-3 pt-5">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }}
               class="w-4 h-4 rounded cursor-pointer">
        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
            পণ্যটি সক্রিয় <span class="text-gray-400 font-normal">(স্টোরে দেখাবে)</span>
        </label>
    </div>

    {{-- main_image --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">মূল ছবির Path / URL</label>
        <input type="text" name="main_image"
               value="{{ old('main_image', $product?->main_image) }}"
               placeholder="/images/products/holud.jpg"
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
        <p class="text-xs text-gray-400 mt-1">ফাইল আপলোড Phase 2-এ যোগ হবে।</p>
        @error('main_image')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- video_url --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ভিডিও URL</label>
        <input type="text" name="video_url"
               value="{{ old('video_url', $product?->video_url) }}"
               placeholder="https://www.youtube.com/watch?v=..."
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
        @error('video_url')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- short_description --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            সংক্ষিপ্ত বিবরণ
            <span class="text-gray-400 font-normal">(কার্ড ও লিস্টে দেখাবে, সর্বোচ্চ ৫০০ অক্ষর)</span>
        </label>
        <textarea name="short_description" rows="2"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none @error('short_description') border-red-400 @enderror"
                  >{{ old('short_description', $product?->short_description) }}</textarea>
        @error('short_description')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- description --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">বিস্তারিত বিবরণ</label>
        <textarea name="description" rows="5"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 @error('description') border-red-400 @enderror"
                  >{{ old('description', $product?->description) }}</textarea>
        @error('description')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

</div>

<script>
(function () {
    const nameEn  = document.getElementById('name_en');
    const slugEl  = document.getElementById('slug');
    const btnGen  = document.getElementById('btn-gen-slug');

    function toSlug(str) {
        return str.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    if (nameEn && slugEl) {
        nameEn.addEventListener('input', function () {
            if (!slugEl.dataset.edited && this.value) {
                slugEl.value = toSlug(this.value);
            }
        });
        slugEl.addEventListener('input', function () {
            this.dataset.edited = '1';
        });
    }

    if (btnGen && slugEl) {
        btnGen.addEventListener('click', function () {
            const source = (nameEn && nameEn.value) ? nameEn.value
                         : (document.getElementById('name_bn')?.value ?? '');
            if (source) {
                slugEl.value = toSlug(source);
                slugEl.dataset.edited = '';
            }
        });
    }
})();
</script>
