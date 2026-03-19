<form method="POST" action="{{ route('products.store') }}">
    @csrf
    Name:
    <input type="text" name="name" placeholder="Product Name" />
    <br><br>
    Price:
    <input type="number" name="price" placeholder="Price" />
    <br><br>
    Catrgory:
    <select name="category">
        <option value="">Select Category</option>
        <option value="electronics">Electronics</option>
        <option value="clothing">Clothing</option>
        <option value="books">Books</option>
    </select>
    <br><br>
    Description:
    <textarea name="description" placeholder="Description (optional)"></textarea>
    <br><br>
    <button type="submit">Save Product</button>
</form>