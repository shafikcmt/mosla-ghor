<div class="pe-grid">
    <div data-media-preview>
        <h3>মূল ছবি</h3>
        @php $mainUrl = \App\Support\ProductMedia::url($product?->main_image); @endphp
        <img data-preview src="{{ $mainUrl }}" alt="বর্তমান মূল ছবি" class="pe-main-preview" @if(!$mainUrl) hidden @endif>
        @if($mainUrl)
        <small>বর্তমান ছবি — নতুন ছবি না দিলে এটি থাকবে।</small>
        <label class="pe-check pe-remove"><input type="checkbox" name="remove_main_image" value="1" @checked(old('remove_main_image'))> বর্তমান ছবি মুছুন</label>
        @endif
        <label>মূল ছবি {{ $mainUrl ? 'বদলান' : 'যোগ করুন' }}<input type="file" name="main_image_file" accept="image/jpeg,image/png,image/webp" data-preview-input></label>
        <small>JPG / PNG / WebP · সর্বোচ্চ ৫ MB</small>
        <details class="pe-details"><summary>বাহ্যিক ছবির লিংক ব্যবহার করুন</summary>
            <label>ছবির URL<input type="url" name="main_image" value="{{ old('main_image', preg_match('~^https?://~i', $product?->main_image ?? '') ? $product->main_image : '') }}" placeholder="https://…" maxlength="255"><small>খালি রাখলে বর্তমান ছবি মুছবে না। ফাইল আপলোড করলে সেটি অগ্রাধিকার পাবে।</small></label>
        </details>
    </div>
    <div>
        <h3>গ্যালারি</h3>
        <div class="pe-gallery">
            @foreach($product?->gallery_images ?? [] as $path)
            @php $token = hash('sha256', $path); @endphp
            <label class="pe-gallery-item">
                <img src="{{ \App\Support\ProductMedia::url($path) }}" alt="গ্যালারি ছবি {{ $loop->iteration }}" loading="lazy">
                <small title="{{ basename($path) }}">{{ \Illuminate\Support\Str::limit(basename($path), 22) }}</small>
                <span><input type="checkbox" name="remove_gallery[]" value="{{ $token }}" @checked(in_array($token, old('remove_gallery', []), true))> মুছুন</span>
            </label>
            @endforeach
        </div>
        <label>আরও ছবি যোগ করুন<input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
        <small>প্রতি আপলোডে সর্বোচ্চ ২০টি, প্রতিটি ৫ MB। আগের ছবির সাথে যোগ হবে।</small>
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
