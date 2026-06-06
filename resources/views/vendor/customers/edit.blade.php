@extends('vendor.layout')
@section('title', 'কাস্টমার সম্পাদনা')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">কাস্টমার সম্পাদনা</h1>
    <a href="{{ route('vendor.customers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← তালিকা</a>
</div>

<div class="bg-white rounded-xl border border-gray-100 p-6 max-w-3xl">
    <form method="POST" action="{{ route('vendor.customers.update', $customer) }}">
        @csrf @method('PUT')
        @include('vendor.customers._form')
    </form>
</div>
@endsection
