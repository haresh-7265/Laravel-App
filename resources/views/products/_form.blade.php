<!-- resources/views/products/_form.blade.php -->

@section('style')
<style>
#tags-container .badge {
    font-size: 14px;
    padding: 8px 10px;
    border-radius: 20px;
}
</style>
@endsection

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $product->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Slug</label>
    <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror"
           value="{{ old('slug', $product->slug ?? '') }}">
    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" rows="3"
              class="form-control @error('description') is-invalid @enderror">
        {{ old('description', $product->description ?? '') }}
    </textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Price</label>
        <input type="number" name="price" step="0.01"
               class="form-control @error('price') is-invalid @enderror"
               value="{{ old('price', $product->price ?? '') }}">
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Discount Price</label>
        <input type="number" name="discount_price" step="0.01"
               class="form-control @error('discount_price') is-invalid @enderror"
               value="{{ old('discount_price', $product->discount_price ?? '') }}">
        @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Stock</label>
        <input type="number" name="stock"
               class="form-control @error('stock') is-invalid @enderror"
               value="{{ old('stock', $product->stock ?? '') }}">
        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
        <option value="">-- Select Category --</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}"
                {{ old('category_id', $product->category->id ?? '') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Tags</label>

    <!-- Input -->
    <input type="text" id="tag-input" class="form-control" placeholder="Type tag and press Enter">

    <!-- Chips Container -->
    <div id="tags-container" class="mt-2 d-flex flex-wrap gap-2"></div>
</div>

<div class="mb-3">
    <label class="form-label">Image</label>
    @isset($product->image)
        <div class="mb-2">
            <img src="{{ asset('storage/'. $product->image) }}" width="80" height="80" style="object-fit:cover;">
        </div>
    @endisset
    <input type="file" name="image" accept="image/*"
           class="form-control @error('image') is-invalid @enderror">
    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@push('scripts')
<script>
let tags = @json($product->tags ?? []);

const input = document.getElementById('tag-input');
const container = document.getElementById('tags-container');

// Load existing tags on page load
document.addEventListener('DOMContentLoaded', function () {
    tags.forEach(tag => addTag(tag));
});

// Add new tag
input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();

        let value = input.value.trim().toLowerCase();

        if (value && !tags.includes(value)) {
            tags.push(value);
            addTag(value);
        }

        input.value = '';
    }
});

// Create chip
function addTag(tag) {
    const chip = document.createElement('div');
    chip.className = 'badge bg-primary d-flex align-items-center';

    chip.innerHTML = `
        <span class="me-2">${tag}</span>
        <span style="cursor:pointer;" onclick="removeTag('${tag}', this)">✖</span>
        <input type="hidden" name="tags[]" value="${tag}">
    `;

    container.appendChild(chip);
}

// Remove tag
function removeTag(tag, element) {
    tags = tags.filter(t => t !== tag);
    element.parentElement.remove();
}
</script>
@endpush