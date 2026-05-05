<!-- resources/views/products/edit.blade.php -->

@extends('layouts.app')

@section('title', __('products.edit_product'))

@section('content')
    <h2>{{ __('products.edit_product') }}</h2>

    <form action="{{ route('products.update', $product->slug) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('products._form')
        <button type="submit" class="btn btn-primary">{{ __('products.update_product') }}</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.cancel') }}</a>
    </form>
@endsection