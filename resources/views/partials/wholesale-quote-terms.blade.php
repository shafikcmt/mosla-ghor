{{-- Standard quote terms (delivery charge applicable, COD → advance rule). Needs $quote. --}}
@if(! empty($quote->terms))
<div class="{{ $termsClass ?? 'mt-3' }} bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-900">
    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider mb-1">শর্তাবলী</p>
    <ul class="list-disc pl-5 space-y-0.5">
        @foreach((array) $quote->terms as $term)<li>{{ $term }}</li>@endforeach
    </ul>
</div>
@endif
