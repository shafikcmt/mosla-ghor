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

    {{-- main_image URL (manual / legacy) --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">মূল ছবির URL (বাহ্যিক লিংক)</label>
        <input type="text" name="main_image"
               value="{{ old('main_image', $product?->main_image && str_starts_with($product->main_image, 'http') ? $product->main_image : '') }}"
               placeholder="https://example.com/holud.jpg"
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
        <p class="text-xs text-gray-400 mt-1">শুধু বাহ্যিক URL-এর জন্য। নিচে ফাইল আপলোড করলে সেটি অগ্রাধিকার পাবে।</p>
        @error('main_image')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- main_image_file upload --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">মূল ছবি আপলোড</label>
        @php
            $hasLocalMainImage = $product?->main_image && str_starts_with($product->main_image, 'storage/');
        @endphp
        @if($hasLocalMainImage)
        <div class="mb-3 flex items-start gap-3 p-3 bg-gray-50 border rounded">
            <img src="{{ asset($product->main_image) }}" alt="" class="w-24 h-20 object-cover rounded border flex-shrink-0">
            <div>
                <p class="text-xs text-gray-500 mb-1 break-all">{{ $product->main_image }}</p>
                <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_main_image" value="1" class="rounded">
                    বর্তমান ছবি মুছুন
                </label>
            </div>
        </div>
        @endif
        <input type="file" name="main_image_file" accept="image/jpeg,image/png,image/webp"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP — সর্বোচ্চ ৫ MB</p>
        @error('main_image_file')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- gallery_images upload --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">গ্যালারি ছবি আপলোড (একাধিক)</label>
        @php
            $galleryImages = $product?->gallery_images ?? [];
        @endphp
        @if(count($galleryImages))
        <div class="mb-3 flex flex-wrap gap-2 p-3 bg-gray-50 border rounded">
            @foreach($galleryImages as $gi)
            <div class="flex flex-col items-center gap-1">
                @if(str_starts_with($gi, 'storage/') || str_starts_with($gi, 'http'))
                <img src="{{ str_starts_with($gi, 'http') ? $gi : asset($gi) }}" alt=""
                     class="w-20 h-16 object-cover rounded border">
                @endif
                <label class="flex items-center gap-1 text-xs text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_gallery[]" value="{{ $gi }}" class="rounded">
                    মুছুন
                </label>
            </div>
            @endforeach
        </div>
        @endif
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
        <p class="text-xs text-gray-400 mt-1">একাধিক ছবি একসাথে বেছে নিন — JPG, PNG, WebP — প্রতিটি সর্বোচ্চ ৫ MB</p>
        @error('gallery_images.*')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- video_url (YouTube / external) --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ভিডিও URL (YouTube / বাহ্যিক)</label>
        <input type="text" name="video_url"
               value="{{ old('video_url', $product?->video_url) }}"
               placeholder="https://www.youtube.com/watch?v=..."
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
        @error('video_url')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- video_file upload --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ভিডিও আপলোড (লোকাল)</label>
        @php
            $hasLocalVideo = ($product?->video_path ?? null) && str_starts_with($product->video_path, 'storage/');
        @endphp
        @if($hasLocalVideo)
        <div class="mb-3 p-3 bg-gray-50 border rounded">
            <video src="{{ asset($product->video_path) }}" class="w-full max-w-xs h-28 rounded border bg-black" controls></video>
            <p class="text-xs text-gray-500 mt-1 break-all">{{ $product->video_path }}</p>
            <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer mt-1">
                <input type="checkbox" name="remove_video" value="1" class="rounded">
                বর্তমান ভিডিও মুছুন
            </label>
        </div>
        @endif
        <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
        <p class="text-xs text-gray-400 mt-1">MP4, WebM, MOV — সর্বোচ্চ ৫০ MB। YouTube URL থাকলে সেটি অগ্রাধিকার পাবে।</p>
        @error('video_file')
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
