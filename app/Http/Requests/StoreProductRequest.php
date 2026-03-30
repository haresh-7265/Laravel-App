<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    protected $stopOnFirstFailure = true;
    public function authorize(): bool
    {
        return true;
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
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tags' => ['array', 'min:1'],
            'tags.*' => ['string', 'distinct']
        ];
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
            'discount_price.min' => 'Discount price cannot be negative.',
            'discount_price.lt' => 'Discount price must be less than the original price.',

            // Stock
            'stock.required' => 'Stock quantity is required.',
            'stock.integer' => 'Stock must be a whole number.',
            'stock.min' => 'Stock cannot be negative.',

            // Category
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',

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
