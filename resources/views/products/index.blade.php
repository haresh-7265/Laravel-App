@extends('layouts.app')

@section('title','Laravel app')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection

@section('header', $company_name)

@section('content')
@session('success')
<x-alert type="info" message="{{ session('success') }}"/>
@endsession
<h1>Total products: {{ $total_products }}</h1>
@foreach($products as $product)
    <x-product-card :product="$product" />
@endforeach

@section('sidebar')
@include('partials.navbar')
@endsection
 
@endsection

@section('footer')
<footer class="bg-lime-300 text-white text-center py-3 mt-auto">
    <p class="text-muted small mb-0">
        &copy; {{ date('Y') .' '. config('app.name')}} . All rights reserved.
    </p>
</footer>
@endsection