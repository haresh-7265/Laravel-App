@foreach($products as $product)
    <p>{{ $product->name }} - {{ $product->price }}</p>

    <a href="{{ route('products.edit', $product->id) }}">Edit</a>
    <a href="{{ route('products.show', $product->id) }}">Show</a>

    <form method="POST" action="{{ route('products.destroy', $product->id) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endforeach

<a href="{{ route('products.create') }}">Create New</a>