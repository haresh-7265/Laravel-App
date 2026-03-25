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
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'price'       => ['required','numeric','min:0'],
            'stock'       => ['required','integer','min:0'],
            'category_id' => ['required','exists:categories,id'],
            'image'       => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Product name is required.',
            'price.required'    => 'Product price is required.',
            'price.numeric'     => 'Price must be a valid number.',
            'stock.required'    => 'Stock quantity is required.',
            'category_id.required' => 'Category is required.',
            'image.image'       => 'File must be an image.',
            'image.max'         => 'Image size must not exceed 2MB.',
        ];
    }
}
