@props(['product'])
<div class="max-w-sm bg-white border border-gray-200 rounded-2xl shadow-md overflow-hidden">

    <!-- Product Image -->
    <img 
        src="{{ asset('storage/'.$product['image']) }}" 
        alt="{{ $product['name'] }}" 
        class="w-full h-48 object-cover"
    >

    <div class="p-4">

        <span class="text-xs text-gray-500 uppercase">
            {{ $product['category'] }}
        </span>

        <!-- Product Name -->
        <h2 class="text-lg font-semibold text-gray-800 mt-1">
            {{ $product['name'] }}
        </h2>

        <!-- Description -->
        <p class="text-sm text-gray-600 mt-2">
            {{ $product['description'] }}
        </p>

        <!-- Price -->
        <div class="mt-4 flex items-center justify-between">
            <span class="text-xl font-bold text-green-600">
                ₹{{ $product['price'] }}
            </span>

            <a href="{{ route('products.edit', $product->id) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Edit</a>
            <a href="{{ route('products.show', $product->id) }} " class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Show</a>

            <form method="POST" action="{{ route('products.destroy', $product->id) }}">
                @csrf
                @method('DELETE')
                {{-- <button type="submit">Delete</button> --}}
                <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    Delete
                </button>
            </form>
        </div>

    </div>
</div>