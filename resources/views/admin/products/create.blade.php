@extends('admin.layout')
@section('title', 'নতুন পণ্য')
@section('hide_global_errors', '1')
@section('content')
@include('partials.products.editor', ['product' => null, 'editorRole' => 'admin'])
@endsection
