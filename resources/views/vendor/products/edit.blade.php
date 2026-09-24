@extends('vendor.layout')
@section('title', 'পণ্য সম্পাদনা')
@section('content')
@include('partials.products.editor', ['product' => $product, 'editorRole' => 'vendor'])
@endsection
