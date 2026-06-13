{{--
    Product card (grid / card view) for the storefront product list.
    Receives $product (Eloquent) and $listMode ('retail'|'wholesale') from the
    parent foreach scope. All element IDs are preserved for the homepage JS.
--}}
            @php
                $directRetail = $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'retail');
                $initRetailPrices = $directRetail->isNotEmpty()
                    ? $directRetail
                    : ($product->activeVariants->first()?->activePrices->where('sell_type', 'retail') ?? collect());
                // Wholesale products link to the wholesale detail page; others to retail.
                $detailUrl = $product->is_wholesale
                    ? route('products.show', ['product' => $product->slug, 'mode' => 'wholesale'])
                    : route('products.show', $product->slug);
            @endphp
            <article data-card-product="{{ $product->id }}" style="{{ (($listMode === 'wholesale') === (bool) $product->is_wholesale) ? '' : 'display:none;' }}" class="product-card bg-white rounded-2xl overflow-hidden shadow border border-green-50 flex flex-col hover:shadow-lg hover:-translate-y-1 transition-all">

                {{-- Image slideshow / placeholder --}}
                @php
                    $cardSlides = collect([$product->main_image])
                        ->merge($product->gallery_images ?? [])
                        ->filter()->values();
                @endphp
                <a href="{{ $detailUrl }}" class="block">
                <div class="relative h-52 overflow-hidden flex-shrink-0" data-slideshow="{{ $product->id }}">
                    @if($cardSlides->isNotEmpty())
                        @foreach($cardSlides as $si => $slide)
                        <img src="{{ asset($slide) }}" alt="{{ $product->name_bn }}"
                             class="card-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                             style="{{ $si > 0 ? 'opacity:0;' : '' }}">
                        @endforeach
                        @if($cardSlides->count() > 1)
                        <div class="absolute bottom-2 left-0 right-0 flex justify-center gap-1 z-10 pointer-events-none">
                            @foreach($cardSlides as $si => $slide)
                            <span class="card-dot w-1.5 h-1.5 rounded-full bg-white transition-opacity"
                                  style="{{ $si === 0 ? 'opacity:.9;' : 'opacity:.4;' }}"></span>
                            @endforeach
                        </div>
                        @endif
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center relative">
                            <div class="absolute inset-0 pointer-events-none opacity-20">
                                <div class="absolute top-3 left-3 w-14 h-14 border border-[#c9a227] rounded-full"></div>
                                <div class="absolute bottom-3 right-3 w-10 h-10 border border-[#c9a227] rounded-full"></div>
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-28 h-28 border border-[#c9a227] rounded-full"></div>
                            </div>
                            <div class="z-10 text-center px-4">
                                <div class="text-[#c9a227] font-serif-bn text-2xl font-bold leading-tight">{{ $product->name_bn }}</div>
                            </div>
                        </div>
                    @endif
                    <span class="absolute top-3 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full shadow
                        {{ $product->isInStock() ? 'bg-[#c9a227] text-[#0f3d22]' : 'bg-red-500 text-white' }}">
                        {{ $product->isInStock() ? 'স্টকে আছে' : 'স্টক শেষ' }}
                    </span>
                    @if($product->cat)
                    <span class="absolute top-3 right-3 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/90 text-[#14532d] shadow">
                        {{ $product->cat->name_bn }}
                    </span>
                    @endif
                </div>
                </a>

                {{-- Body --}}
                <div class="p-5 flex flex-col flex-1">
                    <a href="{{ $detailUrl }}" class="hover:underline">
                        <h3 class="font-serif-bn text-[#14532d] text-xl font-bold leading-snug">{{ $product->name_bn }}</h3>
                    </a>

                    @if($product->short_description)
                        <p class="text-gray-500 text-sm mt-2 leading-relaxed line-clamp-2">{{ $product->short_description }}</p>
                    @endif

                    @if($product->is_wholesale)
                    {{-- Paykari product: price hidden on card, enquiry-only --}}
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-full">পাইকারি</span>
                        @if($product->min_order_quantity)
                        <span class="text-[11px] text-gray-500">MOQ: {{ rtrim(rtrim(number_format($product->min_order_quantity, 2, '.', ''), '0'), '.') }}{{ $product->min_order_unit ?: 'kg' }}</span>
                        @endif
                    </div>
                    <div class="flex-1 min-h-3"></div>
                    {{-- Listing stays simple: enquiry/contact actions live on the details page. --}}
                    <div class="mt-4">
                        <a href="{{ $detailUrl }}"
                           class="block w-full bg-[#14532d] hover:bg-[#166534] text-white text-center py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                            বিস্তারিত দেখুন
                        </a>
                    </div>
                    @else
                    <div id="card-price-wrap-{{ $product->id }}" class="mt-3 flex items-baseline gap-1.5"
                         style="{{ $initRetailPrices->isEmpty() ? 'display:none;' : '' }}">
                        <span id="card-from-{{ $product->id }}" class="text-[#c9a227] font-serif-bn text-2xl font-bold">
                            {{ $initRetailPrices->isNotEmpty() ? '৳' . number_format($initRetailPrices->first()->final_price, 0) : '' }}
                        </span>
                        <span class="text-gray-400 text-xs">থেকে শুরু</span>
                    </div>

                    {{-- Pack price chips --}}
                    <div id="card-chips-{{ $product->id }}" class="mt-3 grid grid-cols-3 gap-1.5">
                        @foreach($initRetailPrices as $price)
                            <div class="p-chip p-1.5 text-center">
                                <div class="chip-lbl text-gray-400 text-[10px] leading-tight">{{ $price->label }}</div>
                                <div class="chip-val text-[#14532d] text-[13px] font-semibold leading-tight mt-0.5">
                                    ৳{{ number_format($price->final_price, 0) }}
                                </div>
                                @if($price->is_manual_override)
                                    <div class="text-[#c9a227] text-[9px] leading-none mt-0.5">★</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Wholesale enquiry info (shown only in পাইকারি mode) --}}
                    <div id="card-wholesale-ui-{{ $product->id }}" style="display:none;" class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-full">দর জানতে চাই</span>
                        @if($product->min_order_quantity)
                        <span class="text-[11px] text-gray-500">MOQ: {{ rtrim(rtrim(number_format($product->min_order_quantity, 2, '.', ''), '0'), '.') }}{{ $product->min_order_unit ?: 'kg' }}</span>
                        @endif
                    </div>

                    <div class="flex-1 min-h-3"></div>

                    {{-- Retail mode buttons --}}
                    <div id="card-retail-btns-{{ $product->id }}" class="mt-4 flex gap-2">
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="flex-1 bg-[#14532d] hover:bg-[#166534] text-white text-center py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                            বিস্তারিত দেখুন
                        </a>
                        <button onclick="goToCombo({{ $product->id }})"
                                class="flex-1 border border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            বাক্সে যোগ
                        </button>
                    </div>

                    {{-- Wholesale mode button (hidden in retail mode): Details only --}}
                    <div id="card-wholesale-btns-{{ $product->id }}" style="display:none;" class="mt-4">
                        <a href="{{ route('products.show', ['product' => $product->slug, 'mode' => 'wholesale']) }}"
                           class="block w-full bg-[#14532d] hover:bg-[#166534] text-white text-center py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                            বিস্তারিত দেখুন
                        </a>
                    </div>
                    @endif
                </div>
            </article>
