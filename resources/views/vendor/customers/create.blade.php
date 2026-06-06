@extends('vendor.layout')
@section('title', 'নতুন কাস্টমার')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">নতুন কাস্টমার</h1>
    <a href="{{ route('vendor.customers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← তালিকা</a>
</div>

<div class="bg-white rounded-xl border border-gray-100 p-6 max-w-3xl">
    <form method="POST" action="{{ route('vendor.customers.store') }}">
        @csrf
        @include('vendor.customers._form')
    </form>
</div>
@endsection
