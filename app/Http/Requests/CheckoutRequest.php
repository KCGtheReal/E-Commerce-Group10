<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required_without:first_name', 'nullable', 'string', 'max:200'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'province' => ['required', 'string'],
            'district' => ['nullable', 'string', 'max:150'],
            'commune' => ['nullable', 'string', 'max:150'],
            'street_address' => ['required', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'in:standard,express'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }

    public function passedValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('first_name')) {
            $parts = explode(' ', trim((string) $this->input('name')), 2);
            $this->merge([
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? '',
            ]);
        }
    }
}
