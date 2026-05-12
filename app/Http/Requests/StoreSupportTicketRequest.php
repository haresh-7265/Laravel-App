<?php

namespace App\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public support form — anyone can submit.
        // Swap to auth()->check() if login required.
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_email' => ['required', 'email', 'max:150'],
            'subject'        => ['required', 'string', 'max:255'],
            'body'           => ['nullable', 'string', 'max:5000'],
            'priority'       => [
                'sometimes',
                'string',
                'in:' . implode(',', [
                    SupportTicket::PRIORITY_LOW,
                    SupportTicket::PRIORITY_MEDIUM,
                    SupportTicket::PRIORITY_HIGH,
                    SupportTicket::PRIORITY_URGENT,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'  => 'Please enter your name.',
            'customer_email.required' => 'Please enter your email address.',
            'customer_email.email'    => 'Please enter a valid email address.',
            'subject.required'        => 'Please enter a subject for your ticket.',
            'priority.in'             => 'Priority must be low, medium, high, or urgent.',
        ];
    }
}