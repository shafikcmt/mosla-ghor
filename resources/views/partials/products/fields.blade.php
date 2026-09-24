@php
    $retail = (bool) old('show_in_retail', $product?->show_in_retail ?? true);
    $wholesale = (bool) old('show_in_wholesale', $product?->show_in_wholesale ?? false);
@endphp
<section class="pe-card" id="pe-basic">
    <div class="pe-section-title"><span>01</span><div><h2>পণ্যের পরিচয়</h2><p>সহজ নাম ও পরিষ্কার বিবরণ দিয়ে শুরু করুন।</p></div></div>
    <div class="pe-grid">
        @include('partials.category-select', ['selected' => $product?->category_id])
        <label>পণ্যের বাংলা নাম <span class="pe-required">*</span><input name="name_bn" value="{{ old('name_bn', $product?->name_bn) }}" maxlength="255" required autocomplete="off"></label>
        <label>ইংরেজি নাম <span class="pe-hint">ঐচ্ছিক</span><input name="name_en" value="{{ old('name_en', $product?->name_en) }}" maxlength="255"></label>
        <label class="pe-wide">সংক্ষিপ্ত বিবরণ<textarea name="short_description" maxlength="500" rows="2">{{ old('short_description', $product?->short_description) }}</textarea><small>কার্ড ও SEO বিবরণে ব্যবহার হয়। সর্বোচ্চ ৫০০ অক্ষর।</small></label>
        <label class="pe-wide">বিস্তারিত বিবরণ<textarea name="description" rows="4" maxlength="50000">{{ old('description', $product?->description) }}</textarea></label>
    </div>
</section>
<section class="pe-card" id="pe-channels">
    <div class="pe-section-title"><span>02</span><div><h2>কোথায় বিক্রি করবেন?</h2><p>খুচরা, পাইকারি অথবা দুটোই বেছে নিন।</p></div></div>
    <div class="pe-channel-grid">
        <label class="pe-channel"><input type="hidden" name="show_in_retail" value="0"><input type="checkbox" name="show_in_retail" value="1" data-channel="retail" @checked($retail)><span><strong>খুচরা বিক্রয়</strong><small>প্যাকের দাম, কার্ট ও সরাসরি অর্ডার</small></span></label>
        <label class="pe-channel"><input type="hidden" name="show_in_wholesale" value="0"><input type="checkbox" name="show_in_wholesale" value="1" data-channel="wholesale" @checked($wholesale)><span><strong>পাইকারি বিক্রয়</strong><small>পরিমাণ, enquiry ও কোটেশন</small></span></label>
    </div>
    <p class="pe-channel-message" role="status" aria-live="polite"></p>
</section>
<section class="pe-card" id="pe-pricing">
    <div class="pe-section-title"><span>03</span><div><h2>দাম ও ইনভেন্টরি</h2><p>পণ্যের বর্তমান স্টক ও খুচরা প্যাকের ভিত্তিমূল্য।</p></div></div>
    <div class="pe-grid">
        <fieldset data-channel-section="retail" @if(!$retail) hidden disabled @endif>
            <label>১ কেজির ভিত্তিমূল্য (৳) <span class="pe-required">*</span><input type="number" name="retail_price_1kg" value="{{ old('retail_price_1kg', $product?->retail_price_1kg) }}" step="0.01" min="0.01" max="99999999.99" required><small>এটি থেকে বিদ্যমান নিয়মে খুচরা প্যাকের দাম হিসাব হবে।</small></label>
        </fieldset>
        <label>খুচরা স্টক (পূর্ণ কেজি) <span class="pe-required">*</span><input type="number" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}" min="0" step="1" max="2147483647" required><small>বর্তমান কেজিভিত্তিক স্টক পদ্ধতি অপরিবর্তিত।</small></label>
    </div>
    @if($editorRole === 'vendor' || $product?->vendor_id)
    <details class="pe-details"><summary>অতিরিক্ত ইনভেন্টরি তথ্য</summary>
        <div class="pe-grid">
            @foreach(['sku'=>'SKU', 'brand'=>'ব্র্যান্ড', 'category'=>'পুরোনো ক্যাটাগরি লেবেল'] as $key=>$label)
            <label>{{ $label }}<input name="{{ $key }}" value="{{ old($key, $product?->{$key}) }}" maxlength="100"></label>
            @endforeach
            <label>ইনভেন্টরি একক<select name="unit">@foreach(\App\Models\Product::UNITS as $unit)<option value="{{ $unit }}" @selected(old('unit', $product?->unit ?? 'kg') === $unit)>{{ $unit }}</option>@endforeach</select></label>
            @foreach(['purchase_price'=>'ক্রয় মূল্য (৳)', 'selling_price'=>'POS বিক্রয় মূল্য (৳)', 'low_stock_threshold'=>'কম স্টকের সীমা'] as $key=>$label)
            <label>{{ $label }}<input type="number" name="{{ $key }}" value="{{ old($key, $product?->{$key}) }}" min="0" step="{{ $key === 'low_stock_threshold' ? '0.001' : '0.01' }}"></label>
            @endforeach
        </div>
        @if($product?->stock_qty !== null)<p>আলাদা ইনভেন্টরি স্টক: {{ $product->stock_qty }} {{ $product->unit }}। এটি স্টক ব্যবস্থাপনা পেজ থেকে বদলান।</p>@endif
    </details>
    @endif
</section>
<fieldset class="pe-card" data-channel-section="retail" @if(!$retail) hidden disabled @endif>
    <div class="pe-section-title"><span>04</span><div><h2>খুচরা প্যাক সাইজ ও দাম</h2><p>বিক্রয় মাধ্যম বদলালেও আগের প্যাক ও ম্যানুয়াল দাম মুছে যাবে না।</p></div></div>
    @include('partials.products.retail-packs')
</fieldset>
<fieldset class="pe-card" data-channel-section="wholesale" @if(!$wholesale) hidden disabled @endif>
    <div class="pe-section-title"><span>05</span><div><h2>পাইকারি সেটিং</h2><p>ক্রেতার enquiry ও কোটেশনের জন্য প্রয়োজনীয় তথ্য।</p></div></div>
    <label class="pe-check"><input type="hidden" name="wholesale_enquiry_enabled" value="0"><input type="checkbox" name="wholesale_enquiry_enabled" value="1" @checked(old('wholesale_enquiry_enabled', $product?->wholesale_enquiry_enabled ?? true))> পাইকারি enquiry গ্রহণ করুন</label>
    <div class="pe-grid">
        <label>সর্বনিম্ন অর্ডার (MOQ)<input type="number" name="min_order_quantity" value="{{ old('min_order_quantity', $product?->min_order_quantity) }}" min="0" step="0.01"></label>
        <label>MOQ একক<select name="min_order_unit">@foreach(['kg'=>'কেজি', 'gram'=>'গ্রাম', 'pcs'=>'পিস', 'piece'=>'পিস (পুরোনো)', 'bag'=>'ব্যাগ', 'carton'=>'কার্টন', 'packet'=>'প্যাকেট'] as $unit=>$label)<option value="{{ $unit }}" @selected(old('min_order_unit', $product?->min_order_unit ?? 'kg') === $unit)>{{ $label }}</option>@endforeach</select></label>
        <label>ডেলিভারির সময়<input name="delivery_time" value="{{ old('delivery_time', $product?->delivery_time) }}" maxlength="255" placeholder="যেমন: ৩–৫ দিন"></label>
        <label>পেমেন্টের শর্ত<input name="payment_terms" value="{{ old('payment_terms', $product?->payment_terms) }}" maxlength="255" placeholder="যেমন: ৩০% অগ্রিম"></label>
    </div>
</fieldset>
<section class="pe-card" id="pe-media">
    <div class="pe-section-title"><span>06</span><div><h2>পণ্যের ছবি ও ভিডিও</h2><p>নতুন ফাইল না দিলে আগের ছবিই থাকবে। মুছতে হলে আলাদাভাবে নির্বাচন করুন।</p></div></div>
    @include('partials.products.media')
</section>
<section class="pe-card" id="pe-variants">
    <div class="pe-section-title"><span>07</span><div><h2>ভ্যারিয়েন্ট <small>ঐচ্ছিক</small></h2><p>ব্র্যান্ড, সাইজ বা গ্রেড—প্রতিটি ধরন আলাদা কার্ডে সাজান।</p></div></div>
    @include('partials.products.variants')
</section>
<section class="pe-card" id="pe-seo">
    <div class="pe-section-title"><span>08</span><div><h2>SEO ও পণ্যের ট্যাগ</h2><p>প্রাসঙ্গিক কয়েকটি ট্যাগ পণ্য খুঁজে পেতে সাহায্য করে।</p></div></div>
    <label>পণ্যের লিংক (Slug)<input name="slug" value="{{ old('slug', $product?->slug) }}" maxlength="255" placeholder="যেমন: whole-cumin"><small>{{ $product ? 'আগের লিংক ঠিক রাখতে প্রয়োজন ছাড়া বদলাবেন না।' : 'খালি রাখলে নাম থেকে একটি লিংক তৈরি হবে।' }}</small></label>
    @php $tagValues = old('tags', $product?->tags->pluck('name')->all() ?? []); @endphp
    <label for="pe-tags">Product Tags</label>
    <textarea id="pe-tags" name="tags" rows="2" data-tag-source placeholder="জিরা, মসলা, Cumin, Whole Spice">{{ is_array($tagValues) ? implode(', ', $tagValues) : $tagValues }}</textarea>
    <div class="pe-tag-editor" data-tags hidden>
        <div data-tag-list class="pe-tag-list" aria-label="যোগ করা ট্যাগ"></div>
        <label class="pe-sr-only" for="pe-tag-input">নতুন ট্যাগ</label>
        <input id="pe-tag-input" data-tag-input placeholder="ট্যাগ লিখে Enter বা কমা চাপুন" autocomplete="off" maxlength="80">
        <button type="button" class="pe-button pe-secondary" data-tag-add>যোগ করুন</button>
    </div>
    <small>সর্বোচ্চ ২০টি ট্যাগ, প্রতিটি ৮০ অক্ষর। একই ট্যাগ একবারই সংরক্ষণ হবে।</small>
    <p class="pe-note">SEO title পণ্যের নাম থেকে, description সংক্ষিপ্ত বিবরণ থেকে এবং canonical লিংক slug থেকে তৈরি হয়।</p>
</section>
<section class="pe-card" id="pe-publishing">
    <div class="pe-section-title"><span>09</span><div><h2>প্রকাশের অবস্থা</h2><p>সক্রিয় পণ্য নির্বাচিত বিক্রয় মাধ্যমে দেখা যাবে। Vendor পণ্যে অ্যাডমিন অনুমোদনও প্রয়োজন।</p></div></div>
    <label class="pe-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))> পণ্য সক্রিয় রাখুন</label>
    @if($product)<p class="pe-note">বর্তমান অবস্থা: {{ $product->publicationStatus() }}</p>@endif
    @if($editorRole === 'admin' && $product?->vendor_id)
    <label>Vendor পণ্যের অনুমোদন<select name="approval_status">@foreach(['pending'=>'অপেক্ষায়', 'approved'=>'অনুমোদিত', 'rejected'=>'অনুমোদিত নয়'] as $value=>$label)<option value="{{ $value }}" @selected(old('approval_status', $product->approval_status) === $value)>{{ $label }}</option>@endforeach</select></label>
    @elseif($editorRole === 'vendor')
    <p class="pe-note">পণ্য পেন্ডিং থাকলে অ্যাডমিনকে অনুমোদনের অনুরোধ করুন। শুধু সক্রিয় করলেই অনুমোদন হয় না।</p>
    @endif
    @if($product && $product->is_active && (!$product->vendor_id || $product->approval_status === 'approved') && ($product->show_in_retail || $product->show_in_wholesale))
    <a class="pe-back" href="{{ route('products.show', ['product'=>$product->slug, 'mode'=>$product->isWholesale() ? 'wholesale' : 'retail']) }}" target="_blank" rel="noopener">ওয়েবসাইটে দেখুন ↗</a>
    @endif
</section>
