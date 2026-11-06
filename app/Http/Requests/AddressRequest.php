<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->check();
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
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'province' => ['required', 'string'],
            'district' => ['nullable', 'string', 'max:150'],
            'commune' => ['nullable', 'string', 'max:150'],
            'street_address' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ];
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
