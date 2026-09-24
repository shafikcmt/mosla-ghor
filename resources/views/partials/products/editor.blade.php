@php
    $editing = $product?->exists ?? false;
    $prefix = $editorRole . '.products.';
    $retailPrices = $retailPrices ?? collect();
@endphp
<link rel="stylesheet" href="{{ asset('css/product-editor.css') }}?v=20260924">
<div class="pe" data-product-editor>
    <header class="pe-heading">
        <div>
            <a href="{{ route($prefix.'index') }}" class="pe-back">← পণ্য তালিকা</a>
            <h1>{{ $editing ? 'পণ্য সম্পাদনা' : 'নতুন পণ্য' }}</h1>
            <p>{{ $editing ? $product->name_bn : 'তথ্য, ছবি ও বিক্রয়ের মাধ্যম এক জায়গায় সাজান।' }}</p>
        </div>
        @if($editing)<span class="pe-status">{{ $product->publicationStatus() }}</span>@endif
    </header>
    @if($errors->any())
    <div class="pe-errors" role="alert" tabindex="-1">
        <strong>সংরক্ষণ হয়নি। নিচের তথ্যগুলো ঠিক করুন।</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <p>নিরাপত্তার জন্য নতুন ফাইলগুলো আবার বেছে নিন। আগের সংরক্ষিত ছবি অক্ষত আছে।</p>
    </div>
    @endif
    <nav class="pe-nav" aria-label="পণ্য ফর্মের বিভাগ">
        @foreach(['basic'=>'তথ্য', 'channels'=>'বিক্রয় মাধ্যম', 'pricing'=>'দাম ও স্টক', 'media'=>'ছবি', 'variants'=>'ভ্যারিয়েন্ট', 'seo'=>'ট্যাগ', 'publishing'=>'প্রকাশ'] as $anchor=>$label)
        <a href="#pe-{{ $anchor }}">{{ $label }}</a>
        @endforeach
    </nav>
    <form id="product-editor-form" action="{{ $editing ? route($prefix.'update', $product) : route($prefix.'store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif
        @include($editorRole.'.products._form')
        <div class="pe-actions">
            <div><strong>সব পরিবর্তন একসাথে সংরক্ষণ করুন</strong><span>ছবি না বদলালে আগের ছবিই থাকবে।</span></div>
            <a href="{{ route($prefix.'index') }}" class="pe-button pe-secondary">বাতিল</a>
            <button type="submit" class="pe-button pe-primary">{{ $editing ? 'পরিবর্তন সংরক্ষণ' : 'পণ্য তৈরি করুন' }}</button>
        </div>
    </form>
    @if($editing)
    <details class="pe-danger">
        <summary>পণ্য মুছে ফেলার অপশন</summary>
        <form method="POST" action="{{ route($prefix.'destroy', $product) }}" onsubmit="return confirm('পণ্যটি স্থায়ীভাবে মুছে ফেলবেন? প্রকাশ বন্ধ করতে সক্রিয় অপশন বন্ধ করতে পারেন।')">
            @csrf @method('DELETE')
            <p>শুধু ওয়েবসাইট থেকে সরাতে পণ্যটি নিষ্ক্রিয় করুন।</p>
            <button class="pe-button pe-secondary" type="submit">পণ্য মুছুন</button>
        </form>
    </details>
    @endif
</div>
<script src="{{ asset('js/product-editor.js') }}?v=20260924" defer></script>
