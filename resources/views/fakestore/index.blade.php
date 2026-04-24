@extends('layouts.app')

@section('title', 'FakeStore Products')

@section('content')

    {{-- Header --}}
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-10">
        🛒 FakeStore Products
    </h1>

    @if (!empty($products))
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">

            @foreach ($products as $product)
                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col p-5 gap-3">

                    {{-- Image --}}
                    <div class="flex justify-center items-center h-44 bg-gray-50 rounded-xl p-3">
                        <img
                            src="{{ $product['image'] }}"
                            alt="{{ $product['title'] }}"
                            class="max-h-40 max-w-full object-contain"
                            loading="lazy"
                        >
                    </div>

                    {{-- Category Badge --}}
                    <span class="text-xs font-semibold uppercase tracking-widest text-white bg-indigo-500 rounded-full px-3 py-1 w-fit">
                        {{ $product['category'] }}
                    </span>

                    {{-- Title --}}
                    <h2 class="text-sm font-semibold text-gray-800 leading-snug line-clamp-2">
                        {{ $product['title'] }}
                    </h2>

                    {{-- Description --}}
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2">
                        {{ $product['description'] }}
                    </p>

                    {{-- Rating --}}
                    <div class="flex items-center gap-2 text-sm">

                        {{-- Stars --}}
                        <div class="flex text-amber-400">
                            @php $rate = round($product['rating']['rate']); @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $rate)
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @else
                                    <svg class="w-4 h-4 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @endif
                            @endfor
                        </div>

                        <span class="text-gray-600 font-medium">{{ $product['rating']['rate'] }}</span>
                        <span class="text-gray-400 text-xs">({{ $product['rating']['count'] }})</span>
                    </div>

                    {{-- Price + ID --}}
                    <div class="flex justify-between items-center mt-auto pt-3 border-t border-gray-100">
                        <span class="text-xl font-bold text-rose-500">
                            {{ format_price($product['price']) }}
                        </span>
                        <span class="text-xs text-gray-400 font-medium">
                            #{{ $product['id'] }}
                        </span>
                    </div>

                </div>
            @endforeach

        </div>

    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center mt-24 text-gray-400 gap-3">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-lg font-medium">No products found.</p>
        </div>
    @endif

@endsection