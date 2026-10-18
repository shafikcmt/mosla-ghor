@php $bothChannels = (bool) old('show_in_retail', $product?->show_in_retail ?? true) && (bool) old('show_in_wholesale', $product?->show_in_wholesale ?? false); @endphp
<div class="pe-grid">
    <div data-media-preview class="pe-photo-main">
        <h3>মূল ছবি <span class="pe-hint" data-channel-section="both" @if(!$bothChannels) hidden @endif>— খুচরা কভার</span></h3>
        @php $mainUrl = \App\Support\ProductMedia::url($product?->main_image); @endphp
        <div class="pe-photo-frame">
            <img data-preview src="{{ $mainUrl }}" alt="বর্তমান মূল ছবি" class="pe-main-preview" @if(!$mainUrl) hidden @endif>
            <div class="pe-photo-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 16l4.5-4.5a2 2 0 012.8 0L16 16m-2-2l1.5-1.5a2 2 0 012.8 0L20 14M4 6h16v12H4zM9 9.5a1 1 0 11-2 0 1 1 0 012 0"/></svg><span>এখনো মূল ছবি নেই</span></div>
            <span class="pe-photo-badge pe-badge-remove">মুছে যাবে</span>
        </div>
        <div class="pe-photo-actions">
            <label class="pe-photo-btn pe-photo-btn-primary">{{ $mainUrl ? 'ছবি বদলান' : 'ছবি যোগ করুন' }}<input type="file" name="main_image_file" class="pe-sr-only" accept="image/jpeg,image/png,image/webp" data-preview-input data-async-upload="main" data-token-name="main_image_token" {{ \App\Support\ImageOptimizer::inputAttributes('product') }}></label>
            @if($mainUrl)
            <label class="pe-photo-btn pe-photo-btn-danger"><input type="checkbox" name="remove_main_image" value="1" @checked(old('remove_main_image'))> মুছুন</label>
            @endif
        </div>
@include('partials.products.upload-tokens', ['name' => 'main_image_token', 'oldKey' => 'main_image_token', 'kind' => 'main'])
        <small>JPG / PNG / WebP · সর্বোচ্চ ১০ MB — বড় ছবি নিজে থেকে ছোট হয়ে আপলোড হবে। নতুন ছবি দিলে আগেরটি সংরক্ষণের সময় বদলে যাবে।</small>
        <label>মূল ছবির বিবরণ (alt) <span class="pe-hint">ঐচ্ছিক</span><input name="main_image_alt" maxlength="255" value="{{ old('main_image_alt', $product?->main_image_alt) }}" placeholder="{{ $product?->display_name ?: 'খালি রাখলে পণ্যের নাম' }}"><small>Google ও স্ক্রিন-রিডার এই লেখা পড়ে। যেমন: “গোটা জিরা ২৫০ গ্রাম প্যাক”। খালি রাখলে পণ্যের নাম ব্যবহার হবে।</small></label>
        <details class="pe-details"><summary>বাহ্যিক ছবির লিংক ব্যবহার করুন</summary>
            <label>ছবির URL<input type="url" name="main_image" value="{{ old('main_image', preg_match('~^https?://~i', $product?->main_image ?? '') ? $product->main_image : '') }}" placeholder="https://…" maxlength="255"><small>খালি রাখলে বর্তমান ছবি মুছবে না। ফাইল আপলোড করলে সেটি অগ্রাধিকার পাবে।</small></label>
        </details>
    </div>
    {{-- Separate পাইকারি cover — only meaningful when the product sells in BOTH channels. --}}
    <fieldset data-channel-section="both" data-media-preview class="pe-note" @if(!$bothChannels) hidden disabled @endif>
        <h3>পাইকারি কভার ছবি <span class="pe-hint">ঐচ্ছিক</span></h3>
        @php $wsUrl = \App\Support\ProductMedia::url($product?->wholesale_main_image); @endphp
        <small>পাইকারি তালিকা ও পাইকারি পেজে এই ছবি কভার হিসেবে দেখাবে। খালি রাখলে মূল ছবিই দেখাবে। গ্যালারি দুই জায়গাতেই একই থাকবে।</small>
        <img data-preview src="{{ $wsUrl }}" alt="বর্তমান পাইকারি কভার ছবি" class="pe-main-preview" @if(!$wsUrl) hidden @endif>
        @if($wsUrl)
        <label class="pe-check pe-remove"><input type="checkbox" name="remove_wholesale_main_image" value="1" @checked(old('remove_wholesale_main_image'))> পাইকারি কভার ছবি মুছুন</label>
        @endif
        <label>পাইকারি কভার {{ $wsUrl ? 'বদলান' : 'যোগ করুন' }}<input type="file" name="wholesale_main_image_file" accept="image/jpeg,image/png,image/webp" data-preview-input data-async-upload="wholesale_main" data-token-name="wholesale_main_image_token" {{ \App\Support\ImageOptimizer::inputAttributes('product') }}></label>
@include('partials.products.upload-tokens', ['name' => 'wholesale_main_image_token', 'oldKey' => 'wholesale_main_image_token', 'kind' => 'wholesale_main'])
        <small>JPG / PNG / WebP · সর্বোচ্চ ১০ MB</small>
        <label>পাইকারি কভারের বিবরণ (alt) <span class="pe-hint">ঐচ্ছিক</span><input name="wholesale_main_image_alt" maxlength="255" value="{{ old('wholesale_main_image_alt', $product?->wholesale_main_image_alt) }}" placeholder="খালি রাখলে মূল ছবির বিবরণ"></label>
    </fieldset>
    <div class="pe-wide">
        <h3>গ্যালারি <span class="pe-hint" data-channel-section="both" @if(!$bothChannels) hidden @endif>— খুচরা ও পাইকারি দুই জায়গাতেই একই</span></h3>
        @php $galleryPaths = $product?->gallery_images ?? []; @endphp
        @if($galleryPaths)
        <p class="pe-hint">{{ \App\Support\UploadErrors::bn(count($galleryPaths)) }}টি ছবি · প্রতিটি ছবি বদলাতে, মুছতে বা মূল ছবি করতে নিচের বাটন ব্যবহার করুন, তারপর সংরক্ষণ করুন।</p>
        @endif
        <div class="pe-photo-grid">
            @forelse($galleryPaths as $path)
            @php $token = hash('sha256', $path); @endphp
            <div class="pe-photo" data-photo>
                <div class="pe-photo-frame">
                    <img src="{{ \App\Support\ProductMedia::url($path) }}" alt="{{ $product->imageAlt($path, $loop->iteration + 1) }}" loading="lazy" data-photo-img>
                    <span class="pe-photo-num">{{ \App\Support\UploadErrors::bn($loop->iteration) }}</span>
                    <span class="pe-photo-badge pe-badge-remove">মুছে যাবে</span>
                    <span class="pe-photo-badge pe-badge-main">মূল ছবি হবে</span>
                    <span class="pe-photo-badge pe-badge-new">নতুন ছবি</span>
                </div>
                <div class="pe-photo-actions">
                    <label class="pe-photo-btn" title="এই ছবিটি মূল ছবি করুন — বর্তমান মূল ছবি গ্যালারিতে চলে যাবে"><input type="radio" name="make_main" value="{{ $token }}" @checked(old('make_main') === $token)> মূল ছবি করুন</label>
                    <label class="pe-photo-btn">বদলান<input type="file" class="pe-sr-only" accept="image/jpeg,image/png,image/webp" data-async-upload="gallery" data-single data-replace-preview data-token-name="gallery_replace[{{ $token }}]" {{ \App\Support\ImageOptimizer::inputAttributes('product') }}></label>
                    <label class="pe-photo-btn pe-photo-btn-danger"><input type="checkbox" name="remove_gallery[]" value="{{ $token }}" @checked(in_array($token, old('remove_gallery', []), true))> মুছুন</label>
                </div>
                <input name="gallery_alts[{{ $token }}]" maxlength="255" aria-label="গ্যালারির {{ \App\Support\UploadErrors::bn($loop->iteration) }} নম্বর ছবির বিবরণ (alt)"
                       value="{{ old('gallery_alts.'.$token, ($product->gallery_alts ?? [])[$token] ?? '') }}" placeholder="ছবির বিবরণ (alt)" class="pe-photo-alt">
            </div>
            @empty
            <div class="pe-photo-empty pe-photo-empty-wide"><span>গ্যালারিতে এখনো কোনো ছবি নেই — নিচে থেকে যোগ করুন।</span></div>
            @endforelse
        </div>
        <label class="pe-photo-add">+ গ্যালারিতে ছবি যোগ করুন<input type="file" name="gallery_images[]" class="pe-sr-only" accept="image/jpeg,image/png,image/webp" multiple data-async-upload="gallery" data-token-name="gallery_tokens[]" {{ \App\Support\ImageOptimizer::inputAttributes('product') }}></label>
@include('partials.products.upload-tokens', ['name' => 'gallery_tokens[]', 'oldKey' => 'gallery_tokens', 'kind' => 'gallery'])
        <small>একসাথে সর্বোচ্চ ২০টি, প্রতিটি ১০ MB। প্রতিটি ছবি আলাদাভাবে আপলোড হয় এবং আগের ছবির সাথে যোগ হবে। নতুন ছবির বিবরণ (alt) সংরক্ষণের পর দিতে পারবেন।</small>
    </div>
    <label>ভিডিও লিংক<input type="url" name="video_url" value="{{ old('video_url', $product?->video_url) }}" maxlength="255" placeholder="YouTube বা বাহ্যিক https:// লিংক"></label>
    <div>
        @if($product?->video_path)
        <video class="pe-video" src="{{ \App\Support\ProductMedia::url($product->video_path) }}" controls preload="metadata"></video>
        <label class="pe-check pe-remove"><input type="checkbox" name="remove_video" value="1" @checked(old('remove_video'))> বর্তমান ভিডিও মুছুন</label>
        @endif
        <label>ভিডিও আপলোড<input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime"></label>
        <small>MP4 / WebM / MOV · সর্বোচ্চ ৫০ MB</small>
    </div>
</div>
