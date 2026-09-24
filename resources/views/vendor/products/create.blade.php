@extends('vendor.layout')
@section('title', 'নতুন পণ্য')
@section('content')
@include('partials.products.editor', ['product' => null, 'editorRole' => 'vendor'])
@endsection
