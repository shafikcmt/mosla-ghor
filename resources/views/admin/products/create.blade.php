@extends('admin.layout')
@section('title', 'নতুন পণ্য')
@section('content')
@include('partials.products.editor', ['product' => null, 'editorRole' => 'admin'])
@endsection
