<form method="POST" action="{{ route('products.store') }}">
    @csrf
    Name:
    <input type="text" name="name" placeholder="Product Name" value="{{ old('name') }}"/>
    @error('name')
    <span style="color: red;">{{ $message }}</span>
    @enderror
    <br><br>
    Price:
    <input type="number" name="price" placeholder="Price" value="{{ old('price') }}"/>
    @error('price')
    <span style="color: red;">{{ $message }}</span>
    @enderror
    <br><br>
    Catrgory:
    <select name="category">
        <option value="">Select Category</option>
        <option value="electronics" @selected(old('category')=='electronics') >Electronics</option>
        <option value="clothing" @selected(old('category')=='clothing')>Clothing</option>
        <option value="books" @selected(old('category')=='books')>Books</option>
    </select>
    @error('category')
    <span style="color: red;">{{ $message }}</span>
    @enderror
    <br><br>
    Description:
    <textarea name="description" placeholder="Description (optional)">{{ old('description') }}</textarea>
    @error('description')
    <span style="color: red;">{{ $message }}</span>
    @enderror
    <br><br>
    <button type="submit">Save Product</button>
</form>