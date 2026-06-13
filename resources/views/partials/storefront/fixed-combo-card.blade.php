{{--
    Ready-made (fixed) combo card. Receives $combo from the parent foreach scope.
    data-combo-type drives show/hide by retail/wholesale; orderFixedCombo() is JS.
--}}
<div data-combo-type="{{ $combo->sell_type }}"
     class="bg-white rounded-2xl overflow-hidden shadow border border-green-50 flex flex-col transition-shadow hover:shadow-lg"
     style="{{ $combo->sell_type === 'wholesale' ? 'display:none;' : '' }}">

    <div class="bg-gradient-to-br from-[#14532d] to-[#1a6b3a] px-5 pt-5 pb-4 relative">
        @if($combo->badge_text)
        <span class="absolute top-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#c9a227] text-[#0f3d22]">
            {{ $combo->badge_text }}
        </span>
        @endif
        <h3 class="font-serif-bn text-[#c9a227] text-xl font-bold leading-snug pr-16">{{ $combo->name }}</h3>
        @if($combo->short_description)
        <p class="text-green-300 text-xs mt-1 leading-relaxed">{{ $combo->short_description }}</p>
        @endif
        <div class="mt-3 text-[#fef9ee] font-serif-bn text-3xl font-bold">
            ৳{{ number_format($combo->sell_price, 0) }}
        </div>
    </div>

    <div class="px-5 py-4 flex-1">
        <ul class="space-y-1.5">
            @foreach($combo->items as $item)
            <li class="flex justify-between items-baseline text-sm">
                <span class="font-medium text-[#14532d]">{{ $item->product?->name_bn ?? 'পণ্য' }}</span>
                <span class="text-gray-400 text-xs ml-2 flex-shrink-0">
                    {{ $item->quantity_gram >= 1000 ? ($item->quantity_gram / 1000).' কেজি' : $item->quantity_gram.' গ্রাম' }}
                </span>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="px-5 pb-5">
        <button onclick="orderFixedCombo({{ $combo->id }})"
                class="w-full bg-[#c9a227] hover:bg-[#e2bb45] text-[#0f3d22] font-bold py-3 rounded-xl text-sm shadow transition-colors">
            এখনই অর্ডার করুন →
        </button>
    </div>
</div>
