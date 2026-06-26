{{--
    Product list item (compact list view) for the storefront product list.
    Receives $product and $listMode from the parent foreach scope.
--}}
            @php
                $directRetail = $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'retail');
                $initRetailPrices = $directRetail->isNotEmpty()
                    ? $directRetail
                    : ($product->activeVariants->first()?->activePrices->where('sell_type', 'retail') ?? collect());
                $wholesaleOnly = ! $product->show_in_retail;
                $visibleInMode = $listMode === 'wholesale' ? $product->show_in_wholesale : $product->show_in_retail;
                $detailUrl = $wholesaleOnly
                    ? route('products.show', ['product' => $product->slug, 'mode' => 'wholesale'])
                    : route('products.show', $product->slug);
            @endphp
            <article data-list-product="{{ $product->id }}" style="{{ $visibleInMode ? '' : 'display:none;' }}" class="bg-white rounded-xl border border-green-50 shadow-sm hover:shadow-md transition-shadow flex overflow-hidden">

                {{-- Thumb --}}
                <div class="w-28 sm:w-36 flex-shrink-0 relative min-h-[110px]">
                    @if($product->main_image)
                        <img src="{{ asset($product->main_image) }}" alt="{{ $product->name_bn }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center">
                            <div class="text-center px-2">
                                <div class="text-[#c9a227] font-serif-bn text-base font-bold leading-tight">{{ $product->name_bn }}</div>
                            </div>
                        </div>
                    @endif
                    <span class="absolute top-2 left-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                        {{ $product->isInStock() ? 'bg-[#c9a227] text-[#0f3d22]' : 'bg-red-500 text-white' }}">
                        {{ $product->isInStock() ? '✓' : '✗' }}
                    </span>
                </div>

                {{-- Content --}}
                <div class="flex-1 p-4 flex flex-col sm:flex-row sm:items-center gap-3 min-w-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <h3 class="font-serif-bn text-[#14532d] font-bold text-lg leading-tight">{{ $product->name_bn }}</h3>
                        </div>
                        @if($product->short_description)
                            <p class="text-gray-500 text-sm mt-0.5 line-clamp-1">{{ $product->short_description }}</p>
                        @endif

                        @if($wholesaleOnly)
                        <div class="mt-2">
                            <span class="text-[11px] font-semibold text-orange-700 bg-orange-50 border border-orange-100 px-2 py-0.5 rounded-full">পাইকারি — দাম জানতে enquiry</span>
                        </div>
                        @else
                        <div id="list-prices-{{ $product->id }}" class="mt-2 flex flex-wrap gap-1">
                            @foreach($initRetailPrices as $price)
                                <button type="button"
                                        class="list-chip card-chip text-[11px] border px-2 py-0.5 rounded-full whitespace-nowrap transition-colors {{ $loop->first ? 'active' : '' }}"
                                        data-price-id="{{ $price->id }}"
                                        data-price="{{ (float) $price->final_price }}"
                                        data-label="{{ $price->label }}"
                                        data-variant-id="{{ $price->product_variant_id }}"
                                        onclick="cardSelectPack(this)">
                                    {{ $price->label }} · ৳{{ number_format($price->final_price, 0) }}
                                </button>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Price + buttons --}}
                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 flex-shrink-0">
                        @if($product->activePrices->isNotEmpty() && ! $wholesaleOnly)
                            <div class="text-right">
                                <div id="list-from-{{ $product->id }}" class="text-[#c9a227] font-bold text-xl font-serif-bn">
                                    {{ $initRetailPrices->isNotEmpty() ? '৳' . number_format($initRetailPrices->first()->final_price, 0) : '' }}
                                </div>
                                <div class="text-gray-400 text-[10px]">থেকে শুরু</div>
                            </div>
                        @endif
                        <div class="flex gap-2">
                            <a href="{{ $detailUrl }}"
                               class="bg-[#14532d] hover:bg-[#166534] text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                                বিস্তারিত
                            </a>
                            @unless($wholesaleOnly)
                                @if($initRetailPrices->isNotEmpty())
                                <button type="button" onclick="cardAddToBag(this, {{ $product->id }})" {{ $product->isInStock() ? '' : 'disabled' }}
                                        class="bg-[#14532d] hover:bg-[#166534] text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed">
                                    🛍️ ব্যাগে
                                </button>
                                @else
                                <button onclick="goToCombo({{ $product->id }})"
                                        class="border border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                                    বাক্সে যোগ
                                </button>
                                @endif
                            @endunless
                        </div>
                    </div>
                </div>
            </article>
