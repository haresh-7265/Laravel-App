@extends('layouts.app')

@section('title', 'Trashed Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-900 mb-1">
                <i class="bi bi-trash3 text-red-500"></i>
                Trashed Products
            </h2>
            <p class="text-sm text-gray-500">
                {{ $products->total() }} soft-deleted {{ Str::plural('product', $products->total()) }} found
            </p>
        </div>
        <a href="{{ route('products.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="bi bi-arrow-left"></i>
            Back to Products
        </a>
    </div>

    {{-- Empty State --}}
    @if($products->isEmpty())
        <div class="bg-white border border-gray-200 rounded-xl px-6 py-16 text-center">
            <i class="bi bi-check-circle text-5xl text-gray-300 block mb-4"></i>
            <h5 class="text-base font-medium text-gray-700 mb-1">No trashed products</h5>
            <p class="text-sm text-gray-400">All products are active. Nothing in the trash.</p>
        </div>

    @else
        {{-- Table Card --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm" style="overflow: visible;">
            <div style="overflow-x: auto; overflow-y: visible;">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-12">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Product name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Deleted at</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($products as $product)
                            <tr class="hover:bg-gray-50 transition-colors">

                                {{-- ID --}}
                                <td class="px-4 py-3 text-xs text-gray-400 tabular-nums">
                                    {{ $product->id }}
                                </td>

                                {{-- Product --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100 border border-gray-200 flex items-center justify-center">
                                            @if($product->image)
                                                <img src="{{ asset('storage/' . $product->image) }}"
                                                     alt="{{ str($product->name)->limit(20) }}"
                                                     class="w-full h-full object-cover">
                                            @else
                                                <i class="bi bi-image text-gray-400 text-base"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800 leading-snug">{{ str($product->name)->limit(20,'...',true) }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ str($product->slug)->limit(20) }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                        {{ $product->category->name ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Price --}}
                                <td class="px-4 py-3 text-right font-semibold text-gray-800 tabular-nums">
                                    {{ format_price($product->price) }}
                                </td>

                                {{-- Deleted At --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-red-500 text-xs font-medium">
                                        <i class="bi bi-calendar-x"></i>
                                        <span title="{{ $product->deleted_at }}">{{ $product->deleted_at->format('d M Y') }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $product->deleted_at->diffForHumans() }}</p>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="relative inline-block" x-data="{ open: false }">

                                        {{-- 3-dot trigger --}}
                                        <button
                                            @click="open = !open"
                                            @click.outside="open = false"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 hover:border-gray-300 transition-colors"
                                            :class="open ? 'bg-gray-100 border-gray-300' : ''"
                                            aria-label="Actions for {{ $product->name }}">
                                            <i class="bi bi-three-dots-vertical text-base"></i>
                                        </button>

                                        {{-- Dropdown — appears below the button with arrow pointing up to 3-dot --}}
                                        <div
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            x-cloak
                                            style="z-index: 9999;"
                                            class="absolute top-full right-0 mt-3 w-44 bg-white border border-gray-200 rounded-xl shadow-xl py-1">

                                            {{-- Arrow pointing up toward the 3-dot button --}}
                                            <div class="absolute -top-[7px] right-3 w-3 h-3 bg-white border-l border-t border-gray-200 rotate-45"></div>

                                            {{-- Restore --}}
                                            <form action="{{ route('products.restore', $product->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-emerald-600 hover:bg-emerald-50 transition-colors text-left rounded-t-xl">
                                                    <i class="bi bi-arrow-counterclockwise text-base"></i>
                                                    Restore
                                                </button>
                                            </form>

                                            <div class="my-1 border-t border-gray-100"></div>

                                            {{-- Force Delete --}}
                                            <form action="{{ route('products.forceDelete', $product->id) }}" method="POST"
                                                  onsubmit="return confirm('Permanently delete \'{{ addslashes(str($product->name)->limit(20,'...', true)) }}\'? This cannot be undone!')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-500 hover:bg-red-50 transition-colors text-left rounded-b-xl">
                                                    <i class="bi bi-x-circle text-base"></i>
                                                    Delete forever
                                                </button>
                                            </form>

                                        </div>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="mt-5 flex justify-center">
                {{ $products->links() }}
            </div>
        @endif

    @endif
</div>
@endsection