<?php

namespace App\Http\Requests;

use App\Rules\ValidDiscountPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        $price = (float) $this->input('price', $this->route('product')?->price ?? 0);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($this->product)],
            'slug' => ['sometimes', 'required', 'alpha_dash', Rule::unique('products', 'slug')->ignore($this->product)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'discount_price' => ['nullable', 'numeric', new ValidDiscountPrice($price)],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tags' => ['sometimes', 'array', 'min:1'],
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
}
