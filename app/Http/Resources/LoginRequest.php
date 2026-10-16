<?php

namespace App\Http\Resources;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'], // e.g., "X-POS Terminal 01", "ALmax Web App"
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_name.required' => 'A unique device_name is required to issue an offline-capable client token.',
        ];
    }
}
