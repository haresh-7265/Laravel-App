<?php

namespace App\Http\Requests;

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
        return [
            'name' => ['sometimes','required', 'string', 'max:255', Rule::unique('products','name')->ignore($this->product)],
            'slug' => ['sometimes','required', 'alpha_dash', Rule::unique('products','slug')->ignore($this->product)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes','required', 'numeric', 'min:0', 'max:999999.99'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['sometimes','required', 'integer', 'min:0'],
            'category_id' => ['sometimes','required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tags' => ['sometimes','array', 'min:1'],
            'tags.*' => ['string', 'distinct']
        ];
        
    }
}
