{{-- Images already uploaded one-per-request (TempUpload), kept after a validation error.
     Params: $name (hidden input name), $oldKey (dot key for old()), $kind. --}}
@php
    $tokenList = array_values(array_filter((array) old($oldKey, []), 'is_string'));
    $tokenItems = [];
    foreach ($tokenList as $t) {
        if ($u = \App\Support\TempUpload::previewUrl($t, (int) auth()->id(), $kind)) {
            $tokenItems[] = [$t, $u];
        }
    }
@endphp
@if($tokenItems)
<div class="pe-uploads" data-upload-list>
    @foreach($tokenItems as [$tok, $url])
    <div class="pe-upload" data-state="done">
        <img src="{{ $url }}" alt="">
        <div class="pe-upload-body">
            <small>আগে আপলোড করা ছবি — সংরক্ষণ করলে যোগ হবে</small>
            <span class="pe-upload-actions"><button type="button" class="pe-button pe-secondary" data-remove>সরান</button></span>
        </div>
        <input type="hidden" name="{{ $name }}" value="{{ $tok }}">
    </div>
    @endforeach
</div>
@endif
