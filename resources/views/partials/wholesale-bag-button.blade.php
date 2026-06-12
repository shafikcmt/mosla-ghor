{{-- Reusable "add to Paykari enquiry bag" button.
     Expects $product. Uses the shared msBagAdd()/msCartOpen() from partials/mini-cart.
     min_order_quantity/unit are passed through so the drawer enforces the product MOQ;
     retail-only products (no MOQ) default to 5kg via the drawer's wholesale rules. --}}
@php
    $wbImg  = $product->main_image ? asset($product->main_image) : null;
    $wbMoq  = $product->min_order_quantity ? (float) $product->min_order_quantity : null;
    $wbUnit = $product->min_order_unit ?: 'kg';
@endphp
<button type="button"
        onclick="msBagAdd({{ $product->id }}, {{ \Illuminate\Support\Js::from($product->slug) }}, {{ \Illuminate\Support\Js::from($product->name_bn) }}, {{ \Illuminate\Support\Js::from($wbImg) }}, {{ $wbMoq ?: 5 }}, {{ \Illuminate\Support\Js::from($wbUnit) }}, {{ $wbMoq ?: 'null' }}, {{ \Illuminate\Support\Js::from($product->min_order_unit ?: null) }}); msCartOpen('paykari');"
        class="block w-full bg-amber-700 hover:bg-amber-800 text-white text-center py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
    পাইকারি ব্যাগে যোগ করুন
</button>
