{{-- Single accordion FAQ item. Expects: $idx (unique key), $q (question), $a (answer). --}}
<div class="border border-amber-100 rounded-xl overflow-hidden bg-white shadow-sm">
    <button type="button" onclick="toggleFaq('{{ $idx }}')"
            class="w-full text-left px-6 py-4 flex items-center justify-between gap-4 hover:bg-amber-50 transition-colors">
        <span class="text-[#14532d] font-semibold text-sm md:text-base leading-snug">{{ $q }}</span>
        <svg id="faq-icon-{{ $idx }}" class="w-5 h-5 text-[#c9a227] flex-shrink-0 transition-transform duration-200"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="faq-body-{{ $idx }}" class="hidden px-6 pb-5">
        <div class="h-px bg-amber-100 mb-4"></div>
        <p class="text-gray-600 text-sm leading-relaxed">{{ $a }}</p>
    </div>
</div>
