{{--
    Category filter pills for the product grid.
    Each link carries the current listing mode ($modeQuery) so a click keeps
    retail/wholesale state. Relies on $navCategories, $selectedCategory, $modeQuery.
--}}
@if($navCategories->isNotEmpty())
<div class="flex flex-wrap items-center gap-2 mb-8">
    <a data-mode-link href="{{ url()->current() }}{{ $modeQuery ? '?'.$modeQuery : '' }}#products"
       class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-colors
              {{ empty($selectedCategory) ? 'bg-[#14532d] text-white border-[#14532d]' : 'bg-white text-gray-600 border-gray-200 hover:border-[#14532d] hover:text-[#14532d]' }}">
        সব পণ্য
    </a>
    @foreach($navCategories as $navCat)
    <a data-mode-link href="{{ url()->current() }}?category={{ $navCat->slug }}{{ $modeQuery ? '&'.$modeQuery : '' }}#products"
       class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-colors
              {{ (!empty($selectedCategory) && $selectedCategory->id === $navCat->id) ? 'bg-[#14532d] text-white border-[#14532d]' : 'bg-white text-gray-600 border-gray-200 hover:border-[#14532d] hover:text-[#14532d]' }}">
        {{ $navCat->name_bn }}
    </a>
    @endforeach
</div>
@endif
