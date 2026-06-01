<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\ValidDiscountPrice;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    // protected $stopOnFirstFailure = true;
    public function authorize(): bool
    {
        return current_user()?->can('create', Product::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'slug' => ['required', 'alpha_dash', 'unique:products'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'discount_price' => ['nullable', 'numeric', new ValidDiscountPrice((float) $this->input('price', 0))],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tags' => ['array', 'min:1'],
            'tags.*' => ['string', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->filled('name')) {
            $data['name'] = str($this->name)->squish()->title()->toString();
        }

        if ($this->filled('slug')) {
            $data['slug'] = str($this->slug)->squish()->slug()->toString();
        }

        if ($this->filled('description')) {
            $data['description'] = str($this->description)->squish()->stripTags()->toString();
        }

        if ($this->filled('price')) {
            $data['price'] = round((float) $this->price, 2);
        }

        if ($this->filled('discount_price')) {
            $data['discount_price'] = round((float) $this->discount_price, 2);
        }

        if ($this->has('stock')) {
            $data['stock'] = (int) $this->stock;
        }

        if ($this->has('is_active')) {
            $data['is_active'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->filled('tags') && is_array($this->tags)) {
            $data['tags'] = array_values(
                array_unique(
                    array_map(
                        fn ($tag) => str($tag)->squish()->lower()->toString(),
                        $this->tags
                    )
                )
            );
        }

        $this->merge($data);
    }

    public function messages(): array
    {
        return [
            // Name
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a valid string.',
            'name.max' => 'Product name must not exceed 255 characters.',
            'name.unique' => 'This product name already exists.',

            // Slug
            'slug.required' => 'Slug is required.',
            'slug.alpha_dash' => 'Slug can only contain letters, numbers, dashes, and underscores.',
            'slug.unique' => 'This slug is already in use.',

            // Description
            'description.string' => 'Description must be a valid text.',

            // Price
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price must not exceed 999999.99.',

            // Discount Price
            'discount_price.numeric' => 'Discount price must be a valid number.',

            // Stock
            'stock.required' => 'Stock quantity is required.',
            'stock.integer' => 'Stock must be a whole number.',
            'stock.min' => 'Stock cannot be negative.',

            // Category
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',

            // Status
            'is_active.required' => 'Product status is required.',
            'is_active.boolean' => 'Product status must be active or inactive.',

            // Image
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be a JPG, JPEG, or PNG file.',
            'image.max' => 'Image size must not exceed 2MB.',

            // Tags
            'tags.array' => 'Tags must be an array.',
            'tags.min' => 'At least one tag is required.',
            'tags.*.string' => 'Each tag must be a valid string.',
            'tags.*.distinct' => 'Duplicate tags are not allowed.',
        ];
    }
}
