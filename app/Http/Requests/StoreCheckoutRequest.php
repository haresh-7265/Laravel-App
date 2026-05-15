<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'required|string|size:10',
            'address' => 'required|string',
            'city'    => 'required|string',
            'state'   => 'required|string',
            'pincode' => 'required|string|size:6',
            'notes'   => 'nullable|string|max:1000'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Name is required.',
            'name.string'      => 'Name must be a valid string.',
            'name.max'         => 'Name must not exceed 255 characters.',

            'email.required'   => 'Email address is required.',
            'email.email'      => 'Please provide a valid email address.',

            'phone.required'   => 'Phone number is required.',
            'phone.string'     => 'Phone number must be a valid string.',
            'phone.size'       => 'Phone number must be exactly 10 digits.',

            'address.required' => 'Address is required.',
            'address.string'   => 'Address must be a valid string.',

            'city.required'    => 'City is required.',
            'city.string'      => 'City must be a valid string.',

            'state.required'   => 'State is required.',
            'state.string'     => 'State must be a valid string.',

            'pincode.required' => 'Pincode is required.',
            'pincode.string'   => 'Pincode must be a valid string.',
            'pincode.size'     => 'Pincode must be exactly 6 digits.',

            'notes.string'     => 'Notes must be a valid string.',
            'notes.max'        => 'Notes must not exceed 1000 characters.',
        ];
    }
}