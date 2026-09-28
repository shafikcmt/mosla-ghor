@extends('admin.layout')
@section('title', 'পণ্য সম্পাদনা')
@section('hide_global_errors', '1')
@section('content')
@include('partials.products.editor', ['product' => $product, 'editorRole' => 'admin'])
@endsection
