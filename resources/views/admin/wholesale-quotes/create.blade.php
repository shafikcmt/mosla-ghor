@extends('admin.layout')
@section('title', 'কোটেশন পাঠান')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('admin.wholesale.enquiry.show', $enquiry->id) }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry বিস্তারিত</a>
</div>

@include('partials.wholesale-quote-form', [
    'enquiry' => $enquiry,
    'action'  => route('admin.wholesale.quote.store', $enquiry->id),
    'backUrl' => route('admin.wholesale.enquiry.show', $enquiry->id),
])
@endsection
