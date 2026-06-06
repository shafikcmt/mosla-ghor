@extends('customer.layout')
@section('title', $product->display_name)

@php
    // Resolve a usable URL for a stored image path (local 'storage/...' or full http URL).
    $imgUrl = fn($p) => $p ? (\Illuminate\Support\Str::startsWith($p, 'http') ? $p : asset($p)) : null;

    $main = $imgUrl($product->main_image) ?: 'https://placehold.co/600x600/f1f5f3/14532d?text=' . urlencode($product->display_name);
    $gallery = collect($product->gallery_images ?? [])->map($imgUrl)->filter()->values();

    // Build a YouTube embed URL from a watch/share link, if present.
    $youtubeEmbed = null;
    if ($product->video_url) {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~', $product->video_url, $m)) {
            $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
        }
    }
    $localVideo = ($product->video_path && \Illuminate\Support\Str::startsWith($product->video_path, 'storage/'))
        ? asset($product->video_path) : null;

    $cat = $product->cat; // Category model (null-safe accessor)
    $authName  = auth()->user()?->name ?? '';
    $authPhone = auth()->user()?->phone ?? '';
@endphp

@section('content')

{{-- Breadcrumb --}}
<nav class="text-xs text-gray-400 mb-4 flex flex-wrap items-center gap-1.5">
    <a href="/" class="hover:text-[#14532d]">হোম</a>
    <span>/</span>
    @if($cat)
        @if($cat->parent)
            <span class="text-gray-500">{{ $cat->parent->name_bn }}</span>
            <span>/</span>
        @endif
        <span class="text-gray-500">{{ $cat->name_bn }}</span>
        <span>/</span>
    @endif
    <span class="text-gray-700 font-medium">{{ $product->display_name }}</span>
</nav>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-7">

    {{-- ── (a) Media gallery ──────────────────────────────────────────────── --}}
    <div x-data>
        <div class="rounded-xl overflow-hidden border border-gray-100 bg-gray-50 aspect-square">
            {{-- Image pane --}}
            <img id="pd-main-image" src="{{ $main }}" alt="{{ $product->display_name }}"
                 class="w-full h-full object-cover pd-pane">
            {{-- YouTube pane --}}
            @if($youtubeEmbed)
            <div id="pd-youtube-pane" class="pd-pane hidden w-full h-full">
                <iframe class="w-full h-full" src="{{ $youtubeEmbed }}" title="YouTube video"
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            @endif
            {{-- Local video pane --}}
            @if($localVideo)
            <div id="pd-video-pane" class="pd-pane hidden w-full h-full bg-black">
                <video class="w-full h-full" src="{{ $localVideo }}" controls></video>
            </div>
            @endif
        </div>

        {{-- Thumbnails + media tabs --}}
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" onclick="pdShowImage('{{ $main }}', this)"
                    class="pd-thumb w-16 h-16 rounded-lg overflow-hidden border-2 border-[#14532d]">
                <img src="{{ $main }}" alt="" class="w-full h-full object-cover">
            </button>
            @foreach($gallery as $g)
            <button type="button" onclick="pdShowImage('{{ $g }}', this)"
                    class="pd-thumb w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent">
                <img src="{{ $g }}" alt="" class="w-full h-full object-cover">
            </button>
            @endforeach

            @if($youtubeEmbed)
            <button type="button" onclick="pdShowPane('pd-youtube-pane', this)"
                    class="pd-thumb w-16 h-16 rounded-lg border-2 border-transparent bg-red-50 flex items-center justify-center text-red-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
            @endif
            @if($localVideo)
            <button type="button" onclick="pdShowPane('pd-video-pane', this)"
                    class="pd-thumb w-16 h-16 rounded-lg border-2 border-transparent bg-gray-100 flex items-center justify-center text-gray-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
            @endif
        </div>
    </div>

    {{-- ── (b) Product info ───────────────────────────────────────────────── --}}
    <div>
        @if($cat)
        <a href="{{ route('customer.wholesale.enquiry.index') }}"
           class="inline-block text-xs font-medium text-[#14532d] bg-green-50 px-2.5 py-1 rounded-full mb-2">
            {{ $cat->name_bn }}
        </a>
        @endif
        <h1 class="text-2xl font-bold text-gray-800 leading-tight">{{ $product->name_bn }}</h1>
        @if($product->name_en)
            <p class="text-sm text-gray-400 mt-0.5">{{ $product->name_en }}</p>
        @endif

        @if($product->vendor)
            <p class="text-xs text-gray-500 mt-2">সরবরাহকারী: <span class="font-medium text-gray-700">{{ $product->vendor->shop_name ?? $product->vendor->name ?? 'MoslaMart' }}</span></p>
        @endif

        @if($product->short_description)
            <p class="text-sm text-gray-600 mt-4 leading-relaxed">{{ $product->short_description }}</p>
        @endif

        @if($product->description)
        <div class="mt-3">
            <div id="pd-desc" class="text-sm text-gray-600 leading-relaxed overflow-hidden transition-all" style="max-height: 4.5rem;">
                {!! nl2br(e($product->description)) !!}
            </div>
            <button type="button" id="pd-desc-toggle" onclick="pdToggleDesc()"
                    class="text-xs font-semibold text-[#14532d] mt-1 hover:underline">আরও পড়ুন ▾</button>
        </div>
        @endif

        {{-- ── (c) Wholesale prices ───────────────────────────────────────── --}}
        <div class="mt-6">
            <h2 class="text-sm font-bold text-gray-800 mb-2">পাইকারি মূল্য</h2>
            @if($wholesalePrices->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-gray-100">
                <table class="w-full text-sm">
                    <thead class="bg-green-50 text-[#14532d]">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">প্যাক</th>
                            <th class="px-4 py-2 text-left font-semibold">পরিমাণ</th>
                            <th class="px-4 py-2 text-right font-semibold">মূল্য</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($wholesalePrices as $price)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $price->label ?: '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $price->quantity_gram >= 1000 ? ($price->quantity_gram / 1000) . ' কেজি' : $price->quantity_gram . ' গ্রাম' }}
                                @if($price->min_order_qty)
                                    <span class="text-xs text-gray-400">(সর্বনিম্ন {{ $price->min_order_qty }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-800">৳ {{ number_format((float) $price->final_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">
                পাইকারি মূল্য জানতে enquiry পাঠান।
            </div>
            @endif
        </div>

        {{-- ── (d) Enquiry ────────────────────────────────────────────────── --}}
        <div class="mt-6 rounded-xl border border-green-100 bg-green-50/50 p-4">
            <h2 class="text-sm font-bold text-[#14532d] mb-3">দাম জানুন / Enquiry পাঠান</h2>
            <form action="{{ route('customer.wholesale.enquiry.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">পরিমাণ (কেজি) <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity_kg" min="1" step="0.01" value="{{ old('quantity_kg', 1) }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ব্যবসার ধরন <span class="text-red-500">*</span></label>
                        <select name="business_type" required
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            <option value="">— বেছে নিন —</option>
                            <option value="shop" @selected(old('business_type')==='shop')>শপ / দোকান</option>
                            <option value="restaurant" @selected(old('business_type')==='restaurant')>রেস্তোরাঁ</option>
                            <option value="dealer" @selected(old('business_type')==='dealer')>ডিলার</option>
                            <option value="retailer" @selected(old('business_type')==='retailer')>রিটেইলার</option>
                            <option value="other" @selected(old('business_type')==='other')>অন্যান্য</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">নাম <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $authName) }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ফোন <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $authPhone) }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">ডেলিভারি এলাকা <span class="text-red-500">*</span></label>
                    <input type="text" name="delivery_location" value="{{ old('delivery_location') }}" required placeholder="যেমন: ঢাকা, চট্টগ্রাম"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">বার্তা (ঐচ্ছিক)</label>
                    <textarea name="message" rows="2"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] resize-none">{{ old('message') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                    Send Enquiry / দাম জানুন
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ── (e) Related products ───────────────────────────────────────────────── --}}
@if($relatedProducts->isNotEmpty())
<div class="mt-8">
    <h2 class="text-lg font-bold text-gray-800 mb-3">একই ক্যাটাগরির পণ্য</h2>
    <div class="flex gap-4 overflow-x-auto pb-3" style="scroll-snap-type: x mandatory;">
        @foreach($relatedProducts as $rp)
        @php
            $rpImg = $rp->main_image
                ? (\Illuminate\Support\Str::startsWith($rp->main_image, 'http') ? $rp->main_image : asset($rp->main_image))
                : 'https://placehold.co/300x300/f1f5f3/14532d?text=' . urlencode($rp->display_name);
        @endphp
        <a href="{{ route('customer.wholesale.products.show', $rp->slug) }}"
           class="flex-shrink-0 w-40 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
           style="scroll-snap-align: start;">
            <div class="aspect-square rounded-t-xl overflow-hidden bg-gray-50">
                <img src="{{ $rpImg }}" alt="{{ $rp->display_name }}" class="w-full h-full object-cover">
            </div>
            <div class="p-2.5">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $rp->name_bn }}</p>
                <p class="text-xs text-[#14532d] mt-1 font-semibold">দাম জানুন →</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

<style>
    .pd-thumb.active, .pd-thumb:focus { border-color: #14532d !important; outline: none; }
</style>
<script>
function pdHideAllPanes() {
    document.querySelectorAll('.pd-pane').forEach(el => el.classList.add('hidden'));
}
function pdSetActiveThumb(btn) {
    document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
}
function pdShowImage(src, btn) {
    pdHideAllPanes();
    const img = document.getElementById('pd-main-image');
    img.src = src;
    img.classList.remove('hidden');
    pdSetActiveThumb(btn);
}
function pdShowPane(id, btn) {
    pdHideAllPanes();
    const pane = document.getElementById(id);
    if (pane) pane.classList.remove('hidden');
    pdSetActiveThumb(btn);
}
function pdToggleDesc() {
    const d = document.getElementById('pd-desc');
    const btn = document.getElementById('pd-desc-toggle');
    if (d.dataset.open === '1') {
        d.style.maxHeight = '4.5rem'; d.dataset.open = '0'; btn.textContent = 'আরও পড়ুন ▾';
    } else {
        d.style.maxHeight = d.scrollHeight + 'px'; d.dataset.open = '1'; btn.textContent = 'কম দেখুন ▴';
    }
}
</script>

@endsection
