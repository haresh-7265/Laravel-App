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
            'name' => ['required'],
            'price' => ['required', 'numeric'],
            'category' => ['required'],
            'description' => ['nullable','max:500'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg']
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'price' => 'product price',
            'category' => 'product category',
            'description' => 'product description',
            'image' => 'product image'
        ];
    }
}
