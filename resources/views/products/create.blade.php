<!-- resources/views/products/create.blade.php -->

@extends('layouts.app')

@section('title', __('products.add_product'))

@section('content')
    <h2>{{ __('products.add_product') }}</h2>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('products._form')
        <button type="submit" class="btn btn-primary">{{ __('products.add_product') }}</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.cancel') }}</a>
    </form>
@endsection