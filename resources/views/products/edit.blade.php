<form method="POST" action="{{ route('products.update', $product->id) }}">
    @csrf
    @method('PUT')
    <input type="text" name="name" value="{{ $product->name }}" />
    <input type="text" name="price" value="{{ $product->price }}" />
    <button type="submit">Update</button>
</form>