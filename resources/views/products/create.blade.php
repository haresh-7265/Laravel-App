<form method="POST" action="{{ route('products.store') }}">
    @csrf
    <input type="text" name="name" placeholder="Product Name" />
    <input type="text" name="price" placeholder="Price" />
    <button type="submit">Save</button>
</form>